<?php
/**
 * 본투어 인터내셔날 - 관리자 로그인
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// 이미 로그인된 경우
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect('/born/admin/index.php');
}

$error = '';
$expired = isset($_GET['expired']);

// 로그인 처리
if (is_post()) {
    $username = input('username');
    $password = input('password');

    if (empty($username) || empty($password)) {
        $error = '아이디와 비밀번호를 입력해주세요.';
    } else {
        try {
            $db = db();
            $stmt = $db->prepare("SELECT id, username, password FROM admin WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_last_activity'] = time();

                redirect('/born/admin/index.php');
            } else {
                $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
            }
        } catch (Exception $e) {
            $error = '로그인 처리 중 오류가 발생했습니다.';
            error_log("Admin login error: " . $e->getMessage());
        }
    }
}

$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인 - 본투어 인터내셔날</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <link rel="stylesheet" href="/born/assets/css/animations.css">
    <link rel="stylesheet" href="/born/assets/css/admin.css">
</head>
<body>
    <div class="login-page">
        <div class="login-card page-enter">
            <div class="login-logo">
                <img src="/born/assets/images/logo/logo.png" alt="본투어 인터내셔날" onerror="this.style.display='none'">
                <h1>본투어 인터내셔날</h1>
                <p>관리자 로그인</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" style="background: var(--error-light); color: var(--error); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px;">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($expired): ?>
                <div class="alert alert-warning" style="background: var(--warning-light); color: var(--warning); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px;">
                    세션이 만료되었습니다. 다시 로그인해주세요.
                </div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

                <div class="form-group">
                    <label class="form-label" for="username">아이디</label>
                    <input type="text" id="username" name="username" class="form-input"
                           placeholder="아이디를 입력하세요" autocomplete="username"
                           value="<?= h(input('username', '')) ?>" autofocus required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">비밀번호</label>
                    <input type="password" id="password" name="password" class="form-input"
                           placeholder="비밀번호를 입력하세요" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    로그인
                </button>
            </form>
        </div>
    </div>

    <script>
        // 폼 제출 시 버튼 비활성화
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span> 로그인 중...';
        });
    </script>
</body>
</html>
