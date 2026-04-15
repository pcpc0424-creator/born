<?php
/**
 * 본투어 인터내셔날 - 여행자 로그인
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
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    redirect('/user/main.php');
}

$error = '';
$expired = isset($_GET['expired']);
$registered = isset($_GET['registered']);
$eventCode = input('code');
$event = null;

// 행사 코드로 행사 정보 조회
if ($eventCode) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM events WHERE unique_code = ? AND status = 'active'");
    $stmt->execute([$eventCode]);
    $event = $stmt->fetch();
}

// 로그인 처리
if (is_post()) {
    $loginId = input('login_id');
    $password = input('password');

    if (empty($loginId) || empty($password)) {
        $error = '아이디와 비밀번호를 입력해주세요.';
    } else {
        require_once __DIR__ . '/../includes/auth.php';
        $result = user_login($loginId, $password, $eventCode);

        if ($result['success']) {
            redirect('/user/main.php');
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = $event ? h($event['event_name']) . ' - 본투어' : '로그인 - 본투어 인터내셔날';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#7ec8e3">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="stylesheet" href="/assets/css/user.css">
    <link rel="stylesheet" href="/assets/css/user-pc.css">
    <style>
        .login-logos {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        .login-logos img {
            max-height: 80px;
            max-width: 200px;
            object-fit: contain;
        }
        .login-logos .logo-divider {
            width: 1px;
            height: 30px;
            background: var(--gray-300);
        }
        .login-event-name {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            text-align: center;
        }
        .login-event-date {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.85);
            text-align: center;
            margin-bottom: 24px;
        }
        .born-logo-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }
        .born-logo-footer img {
            height: 36px;
            margin-bottom: 8px;
        }
        .born-logo-footer .born-logo-text {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-600);
            letter-spacing: 1px;
        }
        .born-logo-footer .born-slogan {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <!-- 휴대폰 프레임 (PC에서만 보임) -->
    <div class="phone-frame">
        <div class="side-button-left"></div>
        <div class="side-button-right-1"></div>
        <div class="side-button-right-2"></div>
        <div class="phone-screen">
            <div class="phone-screen-inner">
                <div class="login-page">
                    <div class="login-header page-enter">
                        <?php if ($event): ?>
                            <!-- 거래처 로고 + 본투어 로고 조합 -->
                            <div class="login-logos">
                                <?php if (!empty($event['client_logo'])): ?>
                                    <img src="/uploads/logos/<?= h($event['client_logo']) ?>" alt="거래처 로고">
                                    <span class="logo-divider"></span>
                                <?php endif; ?>
                                <div style="text-align: center;">
                                    <span class="born-logo-text" style="font-size: 16px; font-weight: 700; color: var(--primary-600);">(주)본투어인터내셔날</span>
                                </div>
                            </div>
                            <!-- 행사명 -->
                            <h1 class="login-event-name"><?= h($event['event_name']) ?></h1>
                            <!-- 행사 일정 -->
                            <p class="login-event-date">
                                <?= format_date_range($event['start_date'], $event['end_date']) ?>
                            </p>
                        <?php else: ?>
                            <h1>본투어 인터내셔날</h1>
                            <p>행사 정보 확인 및 여행 준비</p>
                        <?php endif; ?>
                    </div>

                    <div class="login-form-container page-enter" style="animation-delay: 0.1s;">
                        <?php if ($error): ?>
                            <div style="background: var(--error-light); color: var(--error); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px;">
                                <?= h($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($registered): ?>
                            <div style="background: #e8f5e9; color: #2e7d32; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px;">
                                회원가입이 완료되었습니다. 로그인해주세요.
                            </div>
                        <?php endif; ?>

                        <?php if ($expired): ?>
                            <div style="background: var(--warning-light); color: var(--warning); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px;">
                                세션이 만료되었습니다. 다시 로그인해주세요.
                            </div>
                        <?php endif; ?>

                        <form class="login-form" method="POST" action="">
                            <?php if ($eventCode): ?>
                                <input type="hidden" name="code" value="<?= h($eventCode) ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label class="form-label" for="login_id">아이디</label>
                                <input type="text" id="login_id" name="login_id" class="form-input"
                                       placeholder="아이디를 입력하세요" autocomplete="username"
                                       value="<?= h(input('login_id', '')) ?>" autofocus required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password">비밀번호</label>
                                <input type="password" id="password" name="password" class="form-input"
                                       placeholder="비밀번호를 입력하세요" autocomplete="current-password" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                로그인
                            </button>
                        </form>

                        <div style="margin-top: 20px; text-align: center;">
                            <a href="/user/register.php<?= $eventCode ? '?code=' . h($eventCode) : '' ?>"
                               style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; background: var(--gray-50); color: var(--gray-700); border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-size: 15px; font-weight: 600; text-decoration: none; transition: all 0.3s ease;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="8.5" cy="7" r="4"/>
                                    <line x1="20" y1="8" x2="20" y2="14"/>
                                    <line x1="23" y1="11" x2="17" y2="11"/>
                                </svg>
                                회원가입
                            </a>
                        </div>

                        <div style="margin-top: 16px; text-align: center;">
                            <p style="font-size: 13px; color: var(--gray-700); font-weight: 500;">
                                로그인 정보를 모르시나요?
                            </p>
                            <div class="contact-buttons" style="margin-top: 12px;">
                                <a href="tel:<?= COMPANY_PHONE ?>" class="contact-btn phone">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                    </svg>
                                    전화 문의
                                </a>
                            </div>
                        </div>

                        <!-- 본투어 로고 (하단) -->
                        <div class="born-logo-footer">
                            <span class="born-logo-text">(주)본투어인터내셔날</span>
                            <span class="born-slogan">"세계를 추억으로" 본투어 인터내셔날이 함께합니다</span>
                        </div>
                    </div>

                    <div class="login-footer page-enter" style="animation-delay: 0.2s;">
                        <p>© <?= date('Y') ?> <?= COMPANY_NAME ?>. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/user.js"></script>
</body>
</html>
