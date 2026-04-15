<?php
/**
 * 본투어 인터내셔날 - 일정표 API
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin_auth();

$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];
if (!empty($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

$action = $jsonInput['action'] ?? input('action');

switch ($action) {
    case 'get':
        getSchedule();
        break;

    case 'save':
        saveSchedule();
        break;

    default:
        json_error('잘못된 요청입니다.', 400);
}

/**
 * 일정표 조회
 */
function getSchedule(): void {
    $eventId = input('event_id');

    if (empty($eventId)) {
        json_error('행사 ID가 필요합니다.');
    }

    $db = db();

    $stmt = $db->prepare("
        SELECT sd.*,
               GROUP_CONCAT(
                   CONCAT(si.id, '||', IFNULL(si.title, ''), '||', IFNULL(si.description, ''), '||', si.sort_order)
                   ORDER BY si.sort_order
                   SEPARATOR '^^'
               ) as items_raw
        FROM schedule_days sd
        LEFT JOIN schedule_items si ON si.schedule_day_id = sd.id
        WHERE sd.event_id = ?
        GROUP BY sd.id
        ORDER BY sd.day_number
    ");
    $stmt->execute([$eventId]);
    $days = $stmt->fetchAll();

    $result = [];
    foreach ($days as $day) {
        $items = [];
        if ($day['items_raw']) {
            foreach (explode('^^', $day['items_raw']) as $itemRaw) {
                $parts = explode('||', $itemRaw);
                if (count($parts) >= 4) {
                    $items[] = [
                        'id' => (int)$parts[0],
                        'title' => $parts[1],
                        'description' => $parts[2],
                        'sort_order' => (int)$parts[3],
                    ];
                }
            }
        }

        $result[] = [
            'id' => (int)$day['id'],
            'day_number' => (int)$day['day_number'],
            'location' => $day['location'],
            'hotel_name' => $day['hotel_name'],
            'hotel_id' => $day['hotel_id'] ? (int)$day['hotel_id'] : null,
            'meal_breakfast' => $day['meal_breakfast'],
            'meal_lunch' => $day['meal_lunch'],
            'meal_dinner' => $day['meal_dinner'],
            'items' => $items,
        ];
    }

    json_success($result);
}

/**
 * 일정표 저장 (전체 덮어쓰기)
 */
function saveSchedule(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $eventId = input('event_id');
    $days = $_POST['days'] ?? [];

    if (empty($eventId)) {
        json_error('행사 ID가 필요합니다.');
    }

    $db = db();

    // 기존 데이터 삭제 (CASCADE로 items도 삭제됨)
    $stmt = $db->prepare("DELETE FROM schedule_days WHERE event_id = ?");
    $stmt->execute([$eventId]);

    // 새 데이터 삽입
    foreach ($days as $day) {
        $dayNumber = (int)($day['day_number'] ?? 0);
        if ($dayNumber <= 0) continue;

        $hotelId = !empty($day['hotel_id']) ? (int)$day['hotel_id'] : null;

        $stmt = $db->prepare("
            INSERT INTO schedule_days (event_id, day_number, location, hotel_name, hotel_id, meal_breakfast, meal_lunch, meal_dinner)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $eventId,
            $dayNumber,
            $day['location'] ?? '',
            $day['hotel_name'] ?? '',
            $hotelId,
            $day['meal_breakfast'] ?? '',
            $day['meal_lunch'] ?? '',
            $day['meal_dinner'] ?? '',
        ]);
        $dayId = $db->lastInsertId();

        // 세부 항목 삽입
        $items = $day['items'] ?? [];
        foreach ($items as $idx => $item) {
            $title = trim($item['title'] ?? '');
            if (empty($title)) continue;

            $stmt = $db->prepare("
                INSERT INTO schedule_items (schedule_day_id, title, description, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $dayId,
                $title,
                $item['description'] ?? '',
                $idx,
            ]);
        }
    }

    json_success(null, '일정표가 저장되었습니다.');
}
