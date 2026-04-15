<?php
/**
 * 본투어 인터내셔날 - 회원 API
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
        getMember();
        break;

    case 'list':
        getMembers();
        break;

    case 'create':
        createMember();
        break;

    case 'update':
        updateMember();
        break;

    case 'delete':
        deleteMember();
        break;

    case 'export':
        exportMembers();
        break;

    case 'import':
        importMembers();
        break;

    case 'download_template':
        downloadTemplate();
        break;

    default:
        json_error('잘못된 요청입니다.', 400);
}

/**
 * 회원 상세 조회
 */
function getMember(): void {
    $id = input('id');

    if (empty($id)) {
        json_error('회원 ID가 필요합니다.');
    }

    $db = db();
    $stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$id]);
    $member = $stmt->fetch();

    if (!$member) {
        json_error('회원을 찾을 수 없습니다.', 404);
    }

    unset($member['password']);
    json_success($member);
}

/**
 * 회원 목록 조회
 */
function getMembers(): void {
    $db = db();
    $search = input('search', '');
    $page = max(1, intval(input('page', 1)));
    $perPage = intval(input('per_page', ITEMS_PER_PAGE));

    $where = "1=1";
    $params = [];

    if (!empty($search)) {
        $where .= " AND (name_ko LIKE ? OR name_en LIKE ? OR phone LIKE ? OR login_id LIKE ?)";
        $searchParam = "%{$search}%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }

    // 전체 수
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM members WHERE {$where}");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    // 목록
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("SELECT id, login_id, name_ko, name_en, phone, birth_date, gender, created_at FROM members WHERE {$where} ORDER BY created_at DESC LIMIT ?, ?");
    $params[] = $offset;
    $params[] = $perPage;
    $stmt->execute($params);
    $members = $stmt->fetchAll();

    json_success([
        'members' => $members,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ]);
}

/**
 * 회원 생성
 */
function createMember(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $loginId = input('login_id');
    $password = input('password');
    $nameKo = input('name_ko');
    $nameEn = input('name_en');
    $phone = input('phone');
    $birthDate = input('birth_date');
    $gender = input('gender');

    // 유효성 검사
    if (empty($loginId) || empty($password) || empty($nameKo)) {
        json_error('필수 항목을 입력해주세요.');
    }

    // 아이디 형식 검사
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $loginId)) {
        json_error('아이디는 영문, 숫자, 밑줄만 사용 가능하며 4-20자여야 합니다.');
    }

    $db = db();

    // 아이디 중복 체크
    $stmt = $db->prepare("SELECT id FROM members WHERE login_id = ?");
    $stmt->execute([$loginId]);
    if ($stmt->fetch()) {
        json_error('이미 사용 중인 아이디입니다.');
    }

    // 비밀번호 해시
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 전화번호 정리
    if ($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
    }

    $stmt = $db->prepare("
        INSERT INTO members (login_id, password, name_ko, name_en, phone, birth_date, gender)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $loginId,
        $hashedPassword,
        $nameKo,
        $nameEn ?: null,
        $phone ?: null,
        $birthDate ?: null,
        $gender ?: null
    ]);

    json_success(['id' => $db->lastInsertId()], '회원이 등록되었습니다.');
}

/**
 * 회원 수정
 */
function updateMember(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $id = input('id');
    $loginId = input('login_id');
    $password = input('password');
    $nameKo = input('name_ko');
    $nameEn = input('name_en');
    $phone = input('phone');
    $birthDate = input('birth_date');
    $gender = input('gender');

    if (empty($id) || empty($loginId) || empty($nameKo)) {
        json_error('필수 항목을 입력해주세요.');
    }

    $db = db();

    // 회원 존재 여부 확인
    $stmt = $db->prepare("SELECT id FROM members WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_error('회원을 찾을 수 없습니다.', 404);
    }

    // 아이디 중복 체크 (자신 제외)
    $stmt = $db->prepare("SELECT id FROM members WHERE login_id = ? AND id != ?");
    $stmt->execute([$loginId, $id]);
    if ($stmt->fetch()) {
        json_error('이미 사용 중인 아이디입니다.');
    }

    // 전화번호 정리
    if ($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
    }

    // 업데이트 쿼리 구성
    $sql = "UPDATE members SET login_id = ?, name_ko = ?, name_en = ?, phone = ?, birth_date = ?, gender = ?";
    $params = [$loginId, $nameKo, $nameEn ?: null, $phone ?: null, $birthDate ?: null, $gender ?: null];

    // 비밀번호가 입력된 경우에만 변경
    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    json_success(null, '회원 정보가 수정되었습니다.');
}

/**
 * 회원 삭제
 */
function deleteMember(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $id = input('id');

    if (empty($id)) {
        json_error('회원 ID가 필요합니다.');
    }

    $db = db();

    $stmt = $db->prepare("DELETE FROM members WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        json_error('회원을 찾을 수 없습니다.', 404);
    }

    json_success(null, '회원이 삭제되었습니다.');
}

/**
 * 회원 목록 엑셀 다운로드
 */
function exportMembers(): void {
    $db = db();
    $search = input('search', '');
    $eventId = input('event_id', '');

    $where = "1=1";
    $params = [];
    $joinEvent = "";

    if (!empty($search)) {
        $where .= " AND (m.name_ko LIKE ? OR m.name_en LIKE ? OR m.phone LIKE ? OR m.login_id LIKE ?)";
        $searchParam = "%{$search}%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }

    if (!empty($eventId)) {
        $joinEvent = "INNER JOIN event_members emf ON emf.member_id = m.id AND emf.event_id = ?";
        $params[] = (int)$eventId;
    }

    $stmt = $db->prepare("
        SELECT DISTINCT m.login_id, m.name_ko, m.name_en, m.phone, m.birth_date, m.gender, m.created_at
        FROM members m
        {$joinEvent}
        WHERE {$where}
        ORDER BY m.created_at DESC
    ");
    $stmt->execute($params);
    $members = $stmt->fetchAll();

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="members_' . date('Ymd_His') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><style>td,th{mso-number-format:"\@";border:1px solid #ccc;padding:4px 8px;font-size:12px;}th{background:#f0f0f0;font-weight:bold;}</style></head>';
    echo '<body><table>';

    echo '<tr><th>아이디</th><th>한글이름</th><th>영문이름</th><th>성별</th><th>생년월일</th><th>연락처</th><th>가입일</th></tr>';

    foreach ($members as $member) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($member['login_id']) . '</td>';
        echo '<td>' . htmlspecialchars($member['name_ko']) . '</td>';
        echo '<td>' . htmlspecialchars($member['name_en'] ?? '') . '</td>';
        echo '<td>' . ($member['gender'] ? GENDER_LABELS[$member['gender']] : '') . '</td>';
        echo '<td>' . htmlspecialchars($member['birth_date'] ?? '') . '</td>';
        echo '<td>' . ($member['phone'] ? format_phone($member['phone']) : '') . '</td>';
        echo '<td>' . date('Y-m-d H:i', strtotime($member['created_at'])) . '</td>';
        echo '</tr>';
    }

    echo '</table></body></html>';
    exit;
}

/**
 * 회원 일괄 등록 (엑셀)
 */
function importMembers(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        json_error('파일 업로드에 실패했습니다.');
    }

    $file = $_FILES['excel_file'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
        json_error('지원하지 않는 파일 형식입니다.');
    }

    $rows = parseUploadedFile($file['tmp_name'], $extension);

    if (empty($rows)) {
        json_error('파일에서 데이터를 읽을 수 없습니다. CSV 또는 양식에 맞는 엑셀 파일을 업로드해 주세요.');
    }

    $db = db();
    $imported = 0;
    $errors = [];

    foreach ($rows as $rowNum => $row) {
        if (count($row) < 3 || empty(trim($row[0])) || empty(trim($row[2]))) {
            continue;
        }

        $loginId = trim($row[0]);
        $password = trim($row[1] ?? '');
        $nameKo = trim($row[2]);
        $nameEn = isset($row[3]) ? trim($row[3]) : null;
        $gender = isset($row[4]) ? (strtoupper(trim($row[4])) === 'M' || trim($row[4]) === '남성' ? 'M' : (strtoupper(trim($row[4])) === 'F' || trim($row[4]) === '여성' ? 'F' : null)) : null;
        $birthDate = isset($row[5]) ? trim($row[5]) : null;
        $phone = isset($row[6]) ? preg_replace('/[^0-9]/', '', trim($row[6])) : null;

        // 아이디 형식 검사
        if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $loginId)) {
            $errors[] = "행 {$rowNum}: 아이디 형식 오류 ({$loginId})";
            continue;
        }

        // 아이디 중복 체크
        $stmt = $db->prepare("SELECT id FROM members WHERE login_id = ?");
        $stmt->execute([$loginId]);
        if ($stmt->fetch()) {
            $errors[] = "행 {$rowNum}: 이미 존재하는 아이디 ({$loginId})";
            continue;
        }

        if (empty($password)) {
            $password = $loginId;
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO members (login_id, password, name_ko, name_en, phone, birth_date, gender)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $loginId,
                password_hash($password, PASSWORD_DEFAULT),
                $nameKo,
                $nameEn ?: null,
                $phone ?: null,
                $birthDate ?: null,
                $gender
            ]);
            $imported++;
        } catch (Exception $e) {
            $errors[] = "행 {$rowNum}: 등록 실패";
        }
    }

    json_success([
        'imported' => $imported,
        'errors' => $errors
    ], "{$imported}명의 회원이 등록되었습니다.");
}

// 파일 파싱 공통 함수
require_once __DIR__ . '/members.php.inc';

/**
 * 엑셀 양식 다운로드
 */
function downloadTemplate(): void {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="member_template.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><style>td,th{mso-number-format:"\@";border:1px solid #ccc;padding:4px 8px;font-size:12px;}th{background:#f0f0f0;font-weight:bold;}</style></head>';
    echo '<body><table>';
    echo '<tr><th>아이디</th><th>비밀번호</th><th>한글이름</th><th>영문이름</th><th>성별</th><th>생년월일</th><th>연락처</th></tr>';
    echo '<tr><td>hong123</td><td>1234</td><td>홍길동</td><td>HONG GILDONG</td><td>남성</td><td>1990-01-01</td><td>010-1234-5678</td></tr>';
    echo '<tr><td>kim456</td><td>1234</td><td>김철수</td><td>KIM CHEOLSU</td><td>남성</td><td>1985-05-15</td><td>010-9876-5432</td></tr>';
    echo '</table></body></html>';
    exit;
}
