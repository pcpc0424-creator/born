<?php
/**
 * 본투어 인터내셔날 - 인증 체크
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

/**
 * 관리자 로그인 체크
 */
function check_admin_auth(): bool {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * 관리자 인증 필수 (미인증시 로그인 페이지로)
 */
function require_admin_auth(): void {
    if (!check_admin_auth()) {
        if (is_ajax()) {
            json_error('로그인이 필요합니다.', 401);
        }
        redirect('/admin/login.php');
    }

    // 세션 갱신
    if (isset($_SESSION['admin_last_activity'])) {
        if (time() - $_SESSION['admin_last_activity'] > SESSION_LIFETIME) {
            admin_logout();
            if (is_ajax()) {
                json_error('세션이 만료되었습니다.', 401);
            }
            redirect('/admin/login.php?expired=1');
        }
    }
    $_SESSION['admin_last_activity'] = time();
}

/**
 * 관리자 로그인 처리
 */
function admin_login(string $username, string $password): array {
    $db = db();

    $stmt = $db->prepare("SELECT id, username, password FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        return ['success' => false, 'error' => '아이디 또는 비밀번호가 올바르지 않습니다.'];
    }

    // 세션 재생성 (세션 고정 공격 방지)
    session_regenerate_id(true);

    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_last_activity'] = time();

    return ['success' => true];
}

/**
 * 관리자 로그아웃
 */
function admin_logout(): void {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_last_activity']);
    session_regenerate_id(true);
}

/**
 * 사용자 로그인 체크
 */
function check_user_auth(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

/**
 * 사용자 인증 필수 (미인증시 로그인 페이지로)
 */
function require_user_auth(): void {
    if (!check_user_auth()) {
        if (is_ajax()) {
            json_error('로그인이 필요합니다.', 401);
        }
        redirect('/user/index.php');
    }

    // 행사 정보 없는 세션은 로그아웃 처리
    if (empty($_SESSION['user_event_id'])) {
        user_logout();
        if (is_ajax()) {
            json_error('등록된 행사가 없습니다.', 401);
        }
        redirect('/user/index.php');
    }

    // 세션 갱신
    if (isset($_SESSION['user_last_activity'])) {
        if (time() - $_SESSION['user_last_activity'] > SESSION_LIFETIME) {
            user_logout();
            if (is_ajax()) {
                json_error('세션이 만료되었습니다.', 401);
            }
            redirect('/user/index.php?expired=1');
        }
    }
    $_SESSION['user_last_activity'] = time();
}

/**
 * 사용자 로그인 처리
 */
function user_login(string $loginId, string $password, ?string $eventCode = null): array {
    $db = db();

    $stmt = $db->prepare("
        SELECT m.id, m.login_id, m.password, m.name_ko, m.name_en,
               em.event_id, e.event_name, e.unique_code
        FROM members m
        LEFT JOIN event_members em ON m.id = em.member_id
        LEFT JOIN events e ON em.event_id = e.id AND e.status = 'active'
        WHERE m.login_id = ?
        ORDER BY e.start_date DESC
        LIMIT 1
    ");
    $stmt->execute([$loginId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => '아이디 또는 비밀번호가 올바르지 않습니다.'];
    }

    // event_members 매핑이 없으면 event code로 자동 생성
    if (empty($user['event_id']) && !empty($eventCode)) {
        $stmt = $db->prepare("SELECT id, event_name, unique_code FROM events WHERE unique_code = ? AND status = 'active'");
        $stmt->execute([$eventCode]);
        $event = $stmt->fetch();

        if ($event) {
            // 중복 방지 후 event_members 생성
            $stmt = $db->prepare("INSERT IGNORE INTO event_members (event_id, member_id) VALUES (?, ?)");
            $stmt->execute([$event['id'], $user['id']]);

            $user['event_id'] = $event['id'];
            $user['event_name'] = $event['event_name'];
            $user['unique_code'] = $event['unique_code'];
        }
    }

    if (empty($user['event_id'])) {
        return ['success' => false, 'error' => '등록된 행사가 없습니다. 행사코드를 입력해주세요.'];
    }

    // 세션 재생성 (세션 고정 공격 방지)
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_login_id'] = $user['login_id'];
    $_SESSION['user_name'] = $user['name_ko'];
    $_SESSION['user_name_en'] = $user['name_en'];
    $_SESSION['user_event_id'] = $user['event_id'];
    $_SESSION['user_event_name'] = $user['event_name'];
    $_SESSION['user_event_code'] = $user['unique_code'];
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_last_activity'] = time();

    return ['success' => true, 'event_id' => $user['event_id']];
}

/**
 * 사용자 로그아웃
 */
function user_logout(): void {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_login_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_name_en']);
    unset($_SESSION['user_event_id']);
    unset($_SESSION['user_event_name']);
    unset($_SESSION['user_event_code']);
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['user_last_activity']);
    session_regenerate_id(true);
}

/**
 * 현재 사용자 정보 가져오기
 */
function get_logged_in_user(): ?array {
    if (!check_user_auth()) {
        return null;
    }

    // DB에서 최신 회원 정보 조회
    $db = db();
    $stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $member = $stmt->fetch();

    if (!$member) {
        return null;
    }

    return [
        'id' => $member['id'],
        'login_id' => $member['login_id'],
        'name' => $member['name_ko'],
        'name_ko' => $member['name_ko'],
        'name_en' => $member['name_en'],
        'phone' => $member['phone'],
        'birth_date' => $member['birth_date'],
        'gender' => $member['gender'],
        'event_id' => $_SESSION['user_event_id'] ?? null,
        'event_name' => $_SESSION['user_event_name'] ?? null,
        'event_code' => $_SESSION['user_event_code'] ?? null
    ];
}

/**
 * 현재 관리자 정보 가져오기
 */
function get_current_admin(): ?array {
    if (!check_admin_auth()) {
        return null;
    }

    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username']
    ];
}

/**
 * 사용자 이벤트 접근 권한 체크
 */
function check_event_access(int $eventId): bool {
    if (!check_user_auth()) {
        return false;
    }
    return $_SESSION['user_event_id'] === $eventId;
}

/**
 * 페이지 노출 설정 가져오기
 */
function get_page_visibility(int $eventId): array {
    $db = db();

    $stmt = $db->prepare("SELECT * FROM page_visibility WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $visibility = $stmt->fetch();

    if (!$visibility) {
        // 기본값 반환
        return [
            'notice' => true,
            'event_name' => true,
            'event_date' => true,
            'schedule' => true,
            'flight' => true,
            'meeting' => true,
            'hotel' => true,
            'travel_notice' => true,
            'reservation' => true,
            'passport_upload' => true,
            'optional_tour' => true,
            'survey' => true,
            'announcements' => true,
            'faq' => true
        ];
    }

    return $visibility;
}
