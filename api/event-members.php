<?php
/**
 * 본투어 인터내셔날 - 행사-회원 매칭 API
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
    case 'list':
        getEventMembers();
        break;

    case 'add':
        addEventMember();
        break;

    case 'update':
        updateEventMember();
        break;

    case 'remove':
        removeEventMember();
        break;

    case 'remove_multiple':
        removeMultipleEventMembers();
        break;

    case 'export':
        exportEventMembers();
        break;

    case 'import':
        importEventMembers();
        break;

    default:
        json_error('잘못된 요청입니다.', 400);
}

/**
 * 행사 참가자 목록 조회
 */
function getEventMembers(): void {
    $eventId = input('event_id');

    if (empty($eventId)) {
        json_error('행사 ID가 필요합니다.');
    }

    $db = db();
    $stmt = $db->prepare("
        SELECT em.*, m.name_ko, m.name_en, m.phone, m.birth_date, m.gender, m.login_id
        FROM event_members em
        JOIN members m ON em.member_id = m.id
        WHERE em.event_id = ?
        ORDER BY m.name_ko ASC
    ");
    $stmt->execute([$eventId]);
    $members = $stmt->fetchAll();

    json_success($members);
}

/**
 * 참가자 추가
 */
function addEventMember(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $eventId = input('event_id');
    $memberId = input('member_id');

    if (empty($eventId) || empty($memberId)) {
        json_error('필수 정보가 누락되었습니다.');
    }

    $db = db();

    // 중복 체크
    $stmt = $db->prepare("SELECT id FROM event_members WHERE event_id = ? AND member_id = ?");
    $stmt->execute([$eventId, $memberId]);
    if ($stmt->fetch()) {
        json_error('이미 등록된 참가자입니다.');
    }

    $stmt = $db->prepare("INSERT INTO event_members (event_id, member_id) VALUES (?, ?)");
    $stmt->execute([$eventId, $memberId]);

    json_success(['id' => $db->lastInsertId()], '참가자가 추가되었습니다.');
}

/**
 * 참가자 정보 수정
 */
function updateEventMember(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $id = input('id');
    $field = input('field');
    $value = input('value');

    $allowedFields = ['bus_number', 'dinner_table', 'room_number', 'optional_tour_ids'];

    if (empty($id) || !in_array($field, $allowedFields)) {
        json_error('잘못된 요청입니다.');
    }

    $db = db();
    $stmt = $db->prepare("UPDATE event_members SET {$field} = ? WHERE id = ?");
    $stmt->execute([$value ?: null, $id]);

    json_success(null, '수정되었습니다.');
}

/**
 * 참가자 제거
 */
function removeEventMember(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $id = input('id');

    if (empty($id)) {
        json_error('ID가 필요합니다.');
    }

    $db = db();
    $stmt = $db->prepare("DELETE FROM event_members WHERE id = ?");
    $stmt->execute([$id]);

    json_success(null, '참가자가 제거되었습니다.');
}

/**
 * 여러 참가자 제거
 */
function removeMultipleEventMembers(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $ids = input('ids');

    if (empty($ids) || !is_array($ids)) {
        json_error('삭제할 항목을 선택하세요.');
    }

    $db = db();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("DELETE FROM event_members WHERE id IN ({$placeholders})");
    $stmt->execute($ids);

    json_success(null, '삭제되었습니다.');
}

/**
 * 참가자 목록 엑셀 다운로드
 */
function exportEventMembers(): void {
    $eventId = input('event_id');

    if (empty($eventId)) {
        die('행사 ID가 필요합니다.');
    }

    $db = db();

    // 행사 정보
    $stmt = $db->prepare("SELECT event_name FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    // 참가자 목록
    $stmt = $db->prepare("
        SELECT m.name_ko, m.name_en, m.phone, m.birth_date, m.gender,
               em.bus_number, em.dinner_table, em.room_number
        FROM event_members em
        JOIN members m ON em.member_id = m.id
        WHERE em.event_id = ?
        ORDER BY m.name_ko ASC
    ");
    $stmt->execute([$eventId]);
    $members = $stmt->fetchAll();

    // CSV 형태로 출력
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="event_members_' . date('Ymd_His') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    echo "한글이름\t영문이름\t연락처\t생년월일\t성별\t버스\t만찬장\t객실\n";

    foreach ($members as $m) {
        echo implode("\t", [
            $m['name_ko'],
            $m['name_en'] ?? '',
            $m['phone'] ? format_phone($m['phone']) : '',
            $m['birth_date'] ?? '',
            $m['gender'] ? GENDER_LABELS[$m['gender']] : '',
            $m['bus_number'] ?? '',
            $m['dinner_table'] ?? '',
            $m['room_number'] ?? ''
        ]) . "\n";
    }
    exit;
}

/**
 * 예약내역 엑셀 업로드 (자동 매칭)
 */
function importEventMembers(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $eventId = input('event_id');

    if (empty($eventId)) {
        json_error('행사 ID가 필요합니다.');
    }

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        json_error('파일 업로드에 실패했습니다.');
    }

    $file = $_FILES['excel_file'];
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        json_error('파일을 읽을 수 없습니다.');
    }

    $db = db();
    $imported = 0;
    $errors = [];
    $rowNum = 0;

    // 헤더 스킵
    fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        $rowNum++;

        if (count($row) < 1 || empty(trim($row[0]))) {
            continue;
        }

        $nameKo = trim($row[0]);
        $birthDate = isset($row[1]) ? trim($row[1]) : null;
        $busNumber = isset($row[2]) ? trim($row[2]) : null;
        $dinnerTable = isset($row[3]) ? trim($row[3]) : null;
        $roomNumber = isset($row[4]) ? trim($row[4]) : null;

        // 회원 찾기 (이름 + 생년월일로 매칭)
        $memberId = null;

        if ($birthDate) {
            // 생년월일 형식 정리
            $birthDate = preg_replace('/[^0-9\-]/', '', $birthDate);
            if (strlen($birthDate) === 8) {
                $birthDate = substr($birthDate, 0, 4) . '-' . substr($birthDate, 4, 2) . '-' . substr($birthDate, 6, 2);
            }

            $stmt = $db->prepare("SELECT id FROM members WHERE name_ko = ? AND birth_date = ?");
            $stmt->execute([$nameKo, $birthDate]);
            $member = $stmt->fetch();
            if ($member) {
                $memberId = $member['id'];
            }
        }

        // 이름만으로 검색 (생년월일 없거나 매칭 실패 시)
        if (!$memberId) {
            $stmt = $db->prepare("SELECT id FROM members WHERE name_ko = ?");
            $stmt->execute([$nameKo]);
            $member = $stmt->fetch();
            if ($member) {
                $memberId = $member['id'];
            }
        }

        // 회원 없으면 새로 생성
        if (!$memberId) {
            // 로그인 아이디 생성
            $loginId = 'user' . time() . rand(100, 999);
            $password = password_hash($loginId, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO members (login_id, password, name_ko, birth_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$loginId, $password, $nameKo, $birthDate]);
            $memberId = $db->lastInsertId();
        }

        // 행사-회원 매칭 (중복 체크)
        $stmt = $db->prepare("SELECT id FROM event_members WHERE event_id = ? AND member_id = ?");
        $stmt->execute([$eventId, $memberId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // 기존 데이터 업데이트
            $stmt = $db->prepare("UPDATE event_members SET bus_number = ?, dinner_table = ?, room_number = ? WHERE id = ?");
            $stmt->execute([$busNumber, $dinnerTable, $roomNumber, $existing['id']]);
        } else {
            // 새로 추가
            $stmt = $db->prepare("INSERT INTO event_members (event_id, member_id, bus_number, dinner_table, room_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$eventId, $memberId, $busNumber, $dinnerTable, $roomNumber]);
        }

        $imported++;
    }

    fclose($handle);

    json_success([
        'imported' => $imported,
        'errors' => $errors
    ], "{$imported}명이 등록/매칭되었습니다.");
}
