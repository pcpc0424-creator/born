<?php
/**
 * 본투어 인터내셔날 - 행사 API
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin_auth();

// JSON 입력 처리
$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];
if (!empty($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

$action = $jsonInput['action'] ?? input('action');

switch ($action) {
    case 'get':
        getEvent();
        break;

    case 'list':
        getEvents();
        break;

    case 'create':
        createEvent();
        break;

    case 'update':
        updateEvent();
        break;

    case 'delete':
        deleteEvent();
        break;

    default:
        json_error('잘못된 요청입니다.', 400);
}

/**
 * 행사 상세 조회
 */
function getEvent(): void {
    $id = input('id');

    if (empty($id)) {
        json_error('행사 ID가 필요합니다.');
    }

    $db = db();
    $stmt = $db->prepare("
        SELECT e.*,
               (SELECT COUNT(*) FROM event_members WHERE event_id = e.id) as member_count
        FROM events e
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if (!$event) {
        json_error('행사를 찾을 수 없습니다.', 404);
    }

    json_success($event);
}

/**
 * 행사 목록 조회
 */
function getEvents(): void {
    $db = db();
    $status = input('status', '');
    $search = input('search', '');

    $where = "1=1";
    $params = [];

    if (!empty($status)) {
        $where .= " AND e.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $where .= " AND e.event_name LIKE ?";
        $params[] = "%{$search}%";
    }

    $stmt = $db->prepare("
        SELECT e.*,
               (SELECT COUNT(*) FROM event_members WHERE event_id = e.id) as member_count
        FROM events e
        WHERE {$where}
        ORDER BY e.start_date DESC
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    json_success($events);
}

/**
 * 행사 생성
 */
function createEvent(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $eventName = input('event_name');
    $startDate = input('start_date');
    $endDate = input('end_date');

    if (empty($eventName) || empty($startDate) || empty($endDate)) {
        json_error('필수 항목을 입력해주세요.');
    }

    $db = db();

    // 고유 코드 생성
    $uniqueCode = generate_unique_code();
    while (true) {
        $stmt = $db->prepare("SELECT id FROM events WHERE unique_code = ?");
        $stmt->execute([$uniqueCode]);
        if (!$stmt->fetch()) break;
        $uniqueCode = generate_unique_code();
    }

    // 파일 업로드 처리
    $clientLogo = null;
    if (!empty($_FILES['client_logo']['name'])) {
        $result = handle_file_upload($_FILES['client_logo'], UPLOAD_LOGOS);
        if ($result['success']) {
            $clientLogo = $result['filename'];
        }
    }

    $weatherImage = null;
    if (!empty($_FILES['weather_image']['name'])) {
        $result = handle_file_upload($_FILES['weather_image'], UPLOAD_WEATHER);
        if ($result['success']) {
            $weatherImage = $result['filename'];
        }
    }

    // 추가 출발일 처리
    $additionalDates = $_POST['additional_start_dates'] ?? [];
    $additionalDates = array_filter($additionalDates); // 빈 값 제거
    $additionalDatesJson = !empty($additionalDates) ? json_encode(array_values($additionalDates)) : null;

    $stmt = $db->prepare("
        INSERT INTO events (
            event_name, start_date, end_date, additional_start_dates, airline,
            flight_departure, flight_return,
            flight_time_departure, flight_time_departure_arrival,
            flight_time_return, flight_time_return_arrival,
            departure_airport, departure_airport_code, arrival_airport, arrival_airport_code,
            timezone_offset, flight_duration_departure, flight_duration_return,
            baggage_info, flight_etc,
            client_logo, schedule_url, hotel_url,
            meeting_place, meeting_time, meeting_date, meeting_manager, manager_phone,
            meeting_notice, travel_notice, departure_checklist, prohibited_items,
            weather_image, unique_code, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $eventName,
        $startDate,
        $endDate,
        $additionalDatesJson,
        input('airline'),
        input('flight_departure'),
        input('flight_return'),
        input('flight_time_departure') ?: null,
        input('flight_time_departure_arrival') ?: null,
        input('flight_time_return') ?: null,
        input('flight_time_return_arrival') ?: null,
        input('departure_airport', '인천국제공항'),
        strtoupper(input('departure_airport_code', 'ICN')),
        input('arrival_airport'),
        strtoupper(input('arrival_airport_code', '')),
        intval(input('timezone_offset', 0)),
        input('flight_duration_departure') ? intval(input('flight_duration_departure')) : null,
        input('flight_duration_return') ? intval(input('flight_duration_return')) : null,
        input('baggage_info'),
        input('flight_etc'),
        $clientLogo,
        input('schedule_url'),
        input('hotel_url'),
        input('meeting_place'),
        input('meeting_time') ?: null,
        input('meeting_date') ?: null,
        input('meeting_manager'),
        input('manager_phone'),
        input('meeting_notice'),
        input('travel_notice'),
        input('departure_checklist'),
        input('prohibited_items'),
        $weatherImage,
        $uniqueCode,
        input('status', 'active')
    ]);

    $eventId = $db->lastInsertId();

    json_success(['id' => $eventId, 'unique_code' => $uniqueCode], '행사가 등록되었습니다.');
}

/**
 * 행사 수정
 */
function updateEvent(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $id = input('id');
    $eventName = input('event_name');
    $startDate = input('start_date');
    $endDate = input('end_date');

    if (empty($id) || empty($eventName) || empty($startDate) || empty($endDate)) {
        json_error('필수 항목을 입력해주세요.');
    }

    $db = db();

    // 기존 행사 확인
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if (!$event) {
        json_error('행사를 찾을 수 없습니다.', 404);
    }

    // 파일 업로드 처리
    $clientLogo = $event['client_logo'];
    if (!empty($_FILES['client_logo']['name'])) {
        $result = handle_file_upload($_FILES['client_logo'], UPLOAD_LOGOS);
        if ($result['success']) {
            // 기존 파일 삭제
            if ($clientLogo) {
                delete_file(UPLOAD_LOGOS . '/' . $clientLogo);
            }
            $clientLogo = $result['filename'];
        }
    }

    $weatherImage = $event['weather_image'];
    if (!empty($_FILES['weather_image']['name'])) {
        $result = handle_file_upload($_FILES['weather_image'], UPLOAD_WEATHER);
        if ($result['success']) {
            // 기존 파일 삭제
            if ($weatherImage) {
                delete_file(UPLOAD_WEATHER . '/' . $weatherImage);
            }
            $weatherImage = $result['filename'];
        }
    }

    // 추가 출발일 처리
    $additionalDates = $_POST['additional_start_dates'] ?? [];
    $additionalDates = array_filter($additionalDates); // 빈 값 제거
    $additionalDatesJson = !empty($additionalDates) ? json_encode(array_values($additionalDates)) : null;

    $stmt = $db->prepare("
        UPDATE events SET
            event_name = ?, start_date = ?, end_date = ?, additional_start_dates = ?, airline = ?,
            flight_departure = ?, flight_return = ?,
            flight_time_departure = ?, flight_time_departure_arrival = ?,
            flight_time_return = ?, flight_time_return_arrival = ?,
            departure_airport = ?, departure_airport_code = ?, arrival_airport = ?, arrival_airport_code = ?,
            timezone_offset = ?, flight_duration_departure = ?, flight_duration_return = ?,
            baggage_info = ?, flight_etc = ?,
            client_logo = ?, schedule_url = ?, hotel_url = ?,
            meeting_place = ?, meeting_time = ?, meeting_date = ?, meeting_manager = ?, manager_phone = ?,
            meeting_notice = ?, travel_notice = ?, departure_checklist = ?, prohibited_items = ?,
            weather_image = ?, status = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $eventName,
        $startDate,
        $endDate,
        $additionalDatesJson,
        input('airline'),
        input('flight_departure'),
        input('flight_return'),
        input('flight_time_departure') ?: null,
        input('flight_time_departure_arrival') ?: null,
        input('flight_time_return') ?: null,
        input('flight_time_return_arrival') ?: null,
        input('departure_airport', '인천국제공항'),
        strtoupper(input('departure_airport_code', 'ICN')),
        input('arrival_airport'),
        strtoupper(input('arrival_airport_code', '')),
        intval(input('timezone_offset', 0)),
        input('flight_duration_departure') ? intval(input('flight_duration_departure')) : null,
        input('flight_duration_return') ? intval(input('flight_duration_return')) : null,
        input('baggage_info'),
        input('flight_etc'),
        $clientLogo,
        input('schedule_url'),
        input('hotel_url'),
        input('meeting_place'),
        input('meeting_time') ?: null,
        input('meeting_date') ?: null,
        input('meeting_manager'),
        input('manager_phone'),
        input('meeting_notice'),
        input('travel_notice'),
        input('departure_checklist'),
        input('prohibited_items'),
        $weatherImage,
        input('status', 'active'),
        $id
    ]);

    json_success(null, '행사가 수정되었습니다.');
}

/**
 * 행사 삭제
 */
function deleteEvent(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $id = input('id');

    if (empty($id)) {
        json_error('행사 ID가 필요합니다.');
    }

    $db = db();

    // 기존 파일 정보 가져오기
    $stmt = $db->prepare("SELECT client_logo, weather_image FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    if (!$event) {
        json_error('행사를 찾을 수 없습니다.', 404);
    }

    // 파일 삭제
    if ($event['client_logo']) {
        delete_file(UPLOAD_LOGOS . '/' . $event['client_logo']);
    }
    if ($event['weather_image']) {
        delete_file(UPLOAD_WEATHER . '/' . $event['weather_image']);
    }

    // 행사 삭제 (CASCADE로 관련 데이터도 삭제됨)
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);

    json_success(null, '행사가 삭제되었습니다.');
}
