<?php
/**
 * 본투어 인터내셔날 - 선택관광 API
 */

require_once __DIR__ . '/../includes/auth.php';

// JSON 입력 처리
$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];

// JSON 입력을 $_POST에 병합 (input() 함수가 사용할 수 있도록)
if (!empty($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

$action = $jsonInput['action'] ?? input('action');

// 관리자 전용 액션
$adminActions = ['create', 'update', 'delete'];
if (in_array($action, $adminActions)) {
    require_admin_auth();
}

switch ($action) {
    case 'get':
        getTour();
        break;

    case 'list':
        getTours();
        break;

    case 'create':
        createTour();
        break;

    case 'update':
        updateTour();
        break;

    case 'delete':
        deleteTour();
        break;

    case 'apply':
        applyTour();
        break;

    case 'user_select':
        userSelectTours();
        break;

    default:
        json_error('잘못된 요청입니다.', 400);
}

function getTour(): void {
    $id = input('id');
    $db = db();
    $stmt = $db->prepare("SELECT * FROM optional_tours WHERE id = ?");
    $stmt->execute([$id]);
    $tour = $stmt->fetch();

    if (!$tour) {
        json_error('선택관광을 찾을 수 없습니다.', 404);
    }

    json_success($tour);
}

function getTours(): void {
    $eventId = input('event_id');
    $db = db();

    $sql = "SELECT * FROM optional_tours WHERE event_id = ? AND status = 'active' ORDER BY id";
    $stmt = $db->prepare($sql);
    $stmt->execute([$eventId]);

    json_success($stmt->fetchAll());
}

function createTour(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    try {
        // 행사일 처리
        $tourDates = $_POST['tour_dates'] ?? [];
        if (is_array($tourDates)) {
            $tourDates = array_filter($tourDates); // 빈 값 제거
        } else {
            $tourDates = [];
        }
        $tourDatesJson = !empty($tourDates) ? json_encode(array_values($tourDates)) : null;

        $db = db();
        $stmt = $db->prepare("
            INSERT INTO optional_tours (event_id, tour_name, description, notice, price, duration, meeting_time, tour_dates, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            input('event_id'),
            input('tour_name'),
            input('description'),
            input('notice'),
            intval(input('price', 0)),
            input('duration'),
            input('meeting_time') ?: null,
            $tourDatesJson,
            input('status', 'active')
        ]);

        json_success(['id' => $db->lastInsertId()], '선택관광이 등록되었습니다.');
    } catch (Exception $e) {
        json_error('저장 실패: ' . $e->getMessage(), 500);
    }
}

function updateTour(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    try {
        // 행사일 처리
        $tourDates = $_POST['tour_dates'] ?? [];
        if (is_array($tourDates)) {
            $tourDates = array_filter($tourDates); // 빈 값 제거
        } else {
            $tourDates = [];
        }
        $tourDatesJson = !empty($tourDates) ? json_encode(array_values($tourDates)) : null;

        $db = db();
        $stmt = $db->prepare("
            UPDATE optional_tours SET tour_name = ?, description = ?, notice = ?, price = ?, duration = ?, meeting_time = ?, tour_dates = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([
            input('tour_name'),
            input('description'),
            input('notice'),
            intval(input('price', 0)),
            input('duration'),
            input('meeting_time') ?: null,
            $tourDatesJson,
            input('status', 'active'),
            input('id')
        ]);

        json_success(null, '수정되었습니다.');
    } catch (Exception $e) {
        json_error('수정 실패: ' . $e->getMessage(), 500);
    }
}

function deleteTour(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("DELETE FROM optional_tours WHERE id = ?");
    $stmt->execute([input('id')]);

    json_success(null, '삭제되었습니다.');
}

function applyTour(): void {
    require_user_auth();
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $user = get_logged_in_user();
    $tourId = input('tour_id');
    $apply = input('apply', true);

    $db = db();

    // event_member_id 찾기
    $stmt = $db->prepare("SELECT id FROM event_members WHERE event_id = ? AND member_id = ?");
    $stmt->execute([$user['event_id'], $user['id']]);
    $em = $stmt->fetch();

    if (!$em) {
        json_error('참가자 정보를 찾을 수 없습니다.');
    }

    if ($apply) {
        $stmt = $db->prepare("INSERT IGNORE INTO member_optional_tours (event_member_id, optional_tour_id) VALUES (?, ?)");
        $stmt->execute([$em['id'], $tourId]);
        json_success(null, '신청되었습니다.');
    } else {
        $stmt = $db->prepare("DELETE FROM member_optional_tours WHERE event_member_id = ? AND optional_tour_id = ?");
        $stmt->execute([$em['id'], $tourId]);
        json_success(null, '취소되었습니다.');
    }
}

function userSelectTours(): void {
    global $jsonInput;
    require_user_auth();
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $user = get_logged_in_user();

    // JSON 입력에서 tour_ids 가져오기
    $tourIds = $jsonInput['tour_ids'] ?? [];

    $db = db();

    // event_members에서 사용자 찾기
    $stmt = $db->prepare("SELECT id FROM event_members WHERE event_id = ? AND member_id = ?");
    $stmt->execute([$user['event_id'], $user['id']]);
    $eventMember = $stmt->fetch();

    if (!$eventMember) {
        // event_members에 추가
        $stmt = $db->prepare("INSERT INTO event_members (event_id, member_id) VALUES (?, ?)");
        $stmt->execute([$user['event_id'], $user['id']]);
        $eventMemberId = $db->lastInsertId();
    } else {
        $eventMemberId = $eventMember['id'];
    }

    // 기존 선택관광 JSON 업데이트
    $tourIdsJson = !empty($tourIds) ? json_encode($tourIds) : null;
    $stmt = $db->prepare("UPDATE event_members SET optional_tour_ids = ? WHERE id = ?");
    $stmt->execute([$tourIdsJson, $eventMemberId]);

    json_success(null, '저장되었습니다.');
}
