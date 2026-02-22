<?php
/**
 * 본투어 인터내셔날 - 공지/FAQ API
 */

require_once __DIR__ . '/../includes/auth.php';

// JSON 입력 처리
$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];
if (!empty($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

$action = $jsonInput['action'] ?? input('action');

$adminActions = ['create', 'update', 'delete', 'update_order'];
if (in_array($action, $adminActions)) {
    require_admin_auth();
}

switch ($action) {
    case 'get':
        getNotice();
        break;
    case 'list':
        getNotices();
        break;
    case 'create':
        createNotice();
        break;
    case 'update':
        updateNotice();
        break;
    case 'delete':
        deleteNotice();
        break;
    case 'update_order':
        updateOrder();
        break;
    default:
        json_error('잘못된 요청입니다.', 400);
}

function getNotice(): void {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM notices WHERE id = ?");
    $stmt->execute([input('id')]);
    json_success($stmt->fetch());
}

function getNotices(): void {
    $db = db();
    $category = input('category', 'notice');
    $eventId = input('event_id');

    $sql = "SELECT * FROM notices WHERE category = ?";
    $params = [$category];

    if ($eventId) {
        $sql .= " AND (event_id = ? OR event_id IS NULL)";
        $params[] = $eventId;
    }

    $sql .= " ORDER BY sort_order ASC, id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    json_success($stmt->fetchAll());
}

function createNotice(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("INSERT INTO notices (event_id, category, title, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        input('event_id') ?: null,
        input('category', 'notice'),
        input('title'),
        input('content')
    ]);
    json_success(['id' => $db->lastInsertId()], '등록되었습니다.');
}

function updateNotice(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("UPDATE notices SET title = ?, content = ? WHERE id = ?");
    $stmt->execute([input('title'), input('content'), input('id')]);
    json_success(null, '수정되었습니다.');
}

function deleteNotice(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("DELETE FROM notices WHERE id = ?");
    $stmt->execute([input('id')]);
    json_success(null, '삭제되었습니다.');
}

function updateOrder(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("UPDATE notices SET sort_order = ? WHERE id = ?");
    $stmt->execute([intval(input('sort_order', 0)), input('id')]);
    json_success(null);
}
