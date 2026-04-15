<?php
/**
 * 본투어 인터내셔날 - 인트로 화면
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// 이미 로그인된 경우 메인으로
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    redirect('/user/main.php');
}

// 행사 코드 확인
$eventCode = input('code');
$event = null;

if ($eventCode) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM events WHERE unique_code = ? AND status = 'active'");
    $stmt->execute([$eventCode]);
    $event = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title><?= $event ? h($event['event_name']) . ' - ' : '' ?>본투어 인터내셔날</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow: hidden;
            background: #000;
        }

        /* 비디오 배경 */
        .intro-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .video-background {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
        }

        /* 비디오 오버레이 */
        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                to bottom,
                rgba(0, 0, 0, 0.3) 0%,
                rgba(0, 0, 0, 0.1) 40%,
                rgba(0, 0, 0, 0.1) 60%,
                rgba(0, 0, 0, 0.5) 100%
            );
        }

        /* 콘텐츠 */
        .intro-content {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            padding: 40px 24px;
            text-align: center;
            color: white;
        }

        /* 로고 영역 */
        .intro-logo {
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s ease forwards;
            animation-delay: 0.3s;
        }

        .intro-logo img {
            height: 60px;
            filter: brightness(0) invert(1);
        }

        .intro-brand {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 4px;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.5);
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s ease forwards;
            animation-delay: 0.5s;
        }

        .intro-slogan {
            font-size: 14px;
            font-weight: 400;
            letter-spacing: 2px;
            margin-top: 8px;
            opacity: 0.8;
            text-shadow: 0 1px 10px rgba(0, 0, 0, 0.5);
            opacity: 0;
            animation: fadeInUp 1s ease forwards;
            animation-delay: 0.7s;
        }

        /* 행사 정보 (코드가 있을 경우) */
        .intro-event {
            margin-top: 40px;
            opacity: 0;
            animation: fadeInUp 1s ease forwards;
            animation-delay: 0.9s;
        }

        .intro-event-name {
            font-size: 24px;
            font-weight: 700;
            text-shadow: 0 2px 15px rgba(0, 0, 0, 0.5);
        }

        .intro-event-date {
            font-size: 14px;
            margin-top: 8px;
            opacity: 0.9;
        }

        /* 로그인 버튼 */
        .intro-actions {
            position: absolute;
            bottom: 80px;
            left: 0;
            right: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 0 40px;
            opacity: 0;
            animation: fadeInUp 1s ease forwards;
            animation-delay: 1.1s;
        }

        .intro-login-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            max-width: 320px;
            padding: 18px 32px;
            background: rgba(255, 255, 255, 0.95);
            color: #1a1a2e;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }

        .intro-login-btn:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
        }

        .intro-login-btn:active {
            transform: translateY(0);
        }

        .intro-login-btn svg {
            width: 20px;
            height: 20px;
        }

        /* 하단 정보 */
        .intro-footer {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
            opacity: 0;
            animation: fadeIn 1s ease forwards;
            animation-delay: 1.5s;
        }

        /* 스크롤 힌트 */
        .scroll-hint {
            position: absolute;
            bottom: 120px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            opacity: 0;
            animation: fadeIn 1s ease forwards, bounce 2s ease infinite;
            animation-delay: 1.5s, 2s;
        }

        .scroll-hint svg {
            width: 24px;
            height: 24px;
            animation: bounce 2s ease infinite;
        }

        /* 애니메이션 */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateX(-50%) translateY(0);
            }
            40% {
                transform: translateX(-50%) translateY(-10px);
            }
            60% {
                transform: translateX(-50%) translateY(-5px);
            }
        }

        /* 페이지 전환 효과 */
        .page-transition {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease;
        }

        .page-transition.active {
            opacity: 1;
            pointer-events: all;
        }

        /* 로딩 인디케이터 */
        .video-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            z-index: 5;
        }

        .video-loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 비디오 로드 완료 시 로딩 숨김 */
        .video-loaded .video-loading {
            display: none;
        }

        /* PC 대응 */
        @media (min-width: 768px) {
            .intro-brand {
                font-size: 48px;
                letter-spacing: 8px;
            }

            .intro-slogan {
                font-size: 16px;
                letter-spacing: 4px;
            }

            .intro-event-name {
                font-size: 32px;
            }

            .intro-login-btn {
                padding: 20px 48px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="intro-container" id="introContainer">
        <!-- 비디오 배경 -->
        <video class="video-background" id="bgVideo" autoplay muted loop playsinline>
            <source src="/assets/videos/intro-bg.mp4" type="video/mp4">
        </video>

        <!-- 비디오 로딩 -->
        <div class="video-loading" id="videoLoading">로딩중</div>

        <!-- 오버레이 -->
        <div class="video-overlay"></div>

        <!-- 콘텐츠 -->
        <div class="intro-content">
            <!-- 로고 -->
            <div class="intro-logo">
                <img src="/assets/images/logo/logo.png" alt="본투어" onerror="this.style.display='none'">
            </div>
            <h1 class="intro-brand">(주)본투어인터내셔날</h1>
            <p class="intro-slogan">세계를 추억으로</p>

            <?php if ($event): ?>
                <!-- 행사 정보 -->
                <div class="intro-event">
                    <h2 class="intro-event-name"><?= h($event['event_name']) ?></h2>
                    <p class="intro-event-date"><?= format_date_range($event['start_date'], $event['end_date']) ?></p>
                </div>
            <?php endif; ?>

            <!-- 로그인 버튼 -->
            <div class="intro-actions">
                <a href="/user/index.php<?= $eventCode ? '?code=' . h($eventCode) : '' ?>" class="intro-login-btn" id="loginBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    로그인
                </a>
            </div>

            <!-- 하단 -->
            <div class="intro-footer">
                © <?= date('Y') ?> <?= COMPANY_NAME ?>
            </div>
        </div>
    </div>

    <!-- 페이지 전환 -->
    <div class="page-transition" id="pageTransition"></div>

    <script>
        // 비디오 로드 완료 처리
        const video = document.getElementById('bgVideo');
        const container = document.getElementById('introContainer');

        video.addEventListener('loadeddata', function() {
            container.classList.add('video-loaded');
        });

        video.addEventListener('error', function() {
            // 비디오 로드 실패 시 배경 이미지로 대체
            container.classList.add('video-loaded');
            container.style.background = 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)';
        });

        // 페이지 전환 효과
        document.getElementById('loginBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            const transition = document.getElementById('pageTransition');

            transition.classList.add('active');

            setTimeout(function() {
                window.location.href = href;
            }, 500);
        });

        // 3초 후 비디오 로딩 강제 숨김
        setTimeout(function() {
            container.classList.add('video-loaded');
        }, 3000);
    </script>
</body>
</html>
