<?php
/**
 * 본투어 인터내셔날 - 호텔 API
 */

require_once __DIR__ . '/../includes/auth.php';

// JSON 입력 처리
$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];

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
        getHotel();
        break;

    case 'list':
        getHotels();
        break;

    case 'create':
        createHotel();
        break;

    case 'update':
        updateHotel();
        break;

    case 'delete':
        deleteHotel();
        break;

    default:
        json_error('잘못된 요청입니다.', 400);
}

function getHotel(): void {
    $id = input('id');
    $db = db();
    $stmt = $db->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();

    if (!$hotel) {
        json_error('호텔 정보를 찾을 수 없습니다.', 404);
    }

    json_success($hotel);
}

function getHotels(): void {
    $eventId = input('event_id');
    $db = db();

    $stmt = $db->prepare("SELECT * FROM hotels WHERE event_id = ? ORDER BY sort_order ASC, check_in_date ASC");
    $stmt->execute([$eventId]);

    json_success($stmt->fetchAll());
}

function createHotel(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    try {
        $db = db();
        $stmt = $db->prepare("
            INSERT INTO hotels (event_id, hotel_name, hotel_name_en, star_rating, address, phone, check_in_date, check_out_date, check_in_time, check_out_time, description, facilities, amenities, amenities_hours, map_url, detail_url, image_url, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            input('event_id'),
            input('hotel_name'),
            input('hotel_name_en'),
            intval(input('star_rating', 0)),
            input('address'),
            input('phone'),
            input('check_in_date') ?: null,
            input('check_out_date') ?: null,
            input('check_in_time') ?: null,
            input('check_out_time') ?: null,
            input('description'),
            input('facilities'),
            input('amenities'),
            input('amenities_hours'),
            input('map_url'),
            input('detail_url'),
            input('image_url'),
            intval(input('sort_order', 0))
        ]);

        json_success(['id' => $db->lastInsertId()], '호텔 정보가 등록되었습니다.');
    } catch (Exception $e) {
        json_error('저장 실패: ' . $e->getMessage(), 500);
    }
}

function updateHotel(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    try {
        $db = db();
        $stmt = $db->prepare("
            UPDATE hotels SET hotel_name = ?, hotel_name_en = ?, star_rating = ?, address = ?, phone = ?,
                check_in_date = ?, check_out_date = ?, check_in_time = ?, check_out_time = ?,
                description = ?, facilities = ?, amenities = ?, amenities_hours = ?,
                map_url = ?, detail_url = ?, image_url = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->execute([
            input('hotel_name'),
            input('hotel_name_en'),
            intval(input('star_rating', 0)),
            input('address'),
            input('phone'),
            input('check_in_date') ?: null,
            input('check_out_date') ?: null,
            input('check_in_time') ?: null,
            input('check_out_time') ?: null,
            input('description'),
            input('facilities'),
            input('amenities'),
            input('amenities_hours'),
            input('map_url'),
            input('detail_url'),
            input('image_url'),
            intval(input('sort_order', 0)),
            input('id')
        ]);

        json_success(null, '수정되었습니다.');
    } catch (Exception $e) {
        json_error('수정 실패: ' . $e->getMessage(), 500);
    }
}

function deleteHotel(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("DELETE FROM hotels WHERE id = ?");
    $stmt->execute([input('id')]);

    json_success(null, '삭제되었습니다.');
}
