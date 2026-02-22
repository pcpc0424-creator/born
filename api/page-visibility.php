<?php
/**
 * 본투어 인터내셔날 - 페이지 노출 API
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
    case 'get': getVisibility(); break;
    case 'update': updateVisibility(); break;
    default: json_error('잘못된 요청입니다.', 400);
}

function getVisibility(): void {
    $eventId = input('event_id');
    $db = db();

    $stmt = $db->prepare("SELECT * FROM page_visibility WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $visibility = $stmt->fetch();

    json_success($visibility ?: []);
}

function updateVisibility(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $eventId = input('event_id');
    $page = input('page');
    $visible = input('visible') === 'true' || input('visible') === true ? 1 : 0;

    $allowedPages = [
        'notice', 'event_name', 'event_date', 'schedule', 'flight', 'meeting',
        'hotel', 'travel_notice', 'reservation', 'passport_upload',
        'optional_tour', 'survey', 'announcements', 'faq'
    ];

    if (!in_array($page, $allowedPages)) {
        json_error('잘못된 페이지입니다.');
    }

    $db = db();
    $stmt = $db->prepare("UPDATE page_visibility SET {$page} = ? WHERE event_id = ?");
    $stmt->execute([$visible, $eventId]);

    json_success(null, '저장되었습니다.');
}
