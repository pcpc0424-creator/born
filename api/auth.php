<?php
/**
 * 본투어 인터내셔날 - 인증 API
 */

require_once __DIR__ . '/../includes/auth.php';

// JSON 입력 처리
$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];
if (!empty($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

$action = $jsonInput['action'] ?? input('action');

switch ($action) {
    case 'admin_login':
        handleAdminLogin();
        break;

    case 'admin_logout':
        handleAdminLogout();
        break;

    case 'user_login':
        handleUserLogin();
        break;

    case 'user_logout':
        handleUserLogout();
        break;

    case 'check_session':
        handleCheckSession();
        break;

    default:
        json_error('잘못된 요청입니다.', 400);
}

/**
 * 관리자 로그인
 */
function handleAdminLogin(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $username = input('username');
    $password = input('password');

    if (empty($username) || empty($password)) {
        json_error('아이디와 비밀번호를 입력해주세요.');
    }

    $result = admin_login($username, $password);

    if ($result['success']) {
        json_success(['redirect' => '/born/admin/index.php'], '로그인되었습니다.');
    } else {
        json_error($result['error']);
    }
}

/**
 * 관리자 로그아웃
 */
function handleAdminLogout(): void {
    admin_logout();

    if (is_ajax()) {
        json_success(['redirect' => '/born/admin/login.php'], '로그아웃되었습니다.');
    } else {
        redirect('/born/admin/login.php');
    }
}

/**
 * 사용자 로그인
 */
function handleUserLogin(): void {
    if (!is_post()) {
        json_error('잘못된 요청입니다.', 405);
    }

    $loginId = input('login_id');
    $password = input('password');

    if (empty($loginId) || empty($password)) {
        json_error('아이디와 비밀번호를 입력해주세요.');
    }

    $result = user_login($loginId, $password);

    if ($result['success']) {
        json_success(['redirect' => '/born/user/main.php'], '로그인되었습니다.');
    } else {
        json_error($result['error']);
    }
}

/**
 * 사용자 로그아웃
 */
function handleUserLogout(): void {
    user_logout();

    if (is_ajax()) {
        json_success(['redirect' => '/born/user/index.php'], '로그아웃되었습니다.');
    } else {
        redirect('/born/user/index.php');
    }
}

/**
 * 세션 확인
 */
function handleCheckSession(): void {
    $type = input('type', 'user');

    if ($type === 'admin') {
        if (check_admin_auth()) {
            json_success(['valid' => true, 'user' => get_current_admin()]);
        } else {
            json_success(['valid' => false]);
        }
    } else {
        if (check_user_auth()) {
            json_success(['valid' => true, 'user' => get_logged_in_user()]);
        } else {
            json_success(['valid' => false]);
        }
    }
}
