<?php
/**
 * 본투어 인터내셔날 - 여행자 메인
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

$db = db();

// 행사 정보
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$user['event_id']]);
$event = $stmt->fetch();

// D-Day 계산
$dday = calculate_dday($event['start_date']);

// 최신 공지사항 조회
$latestNotice = null;
if ($visibility['announcements']) {
    $stmt = $db->prepare("SELECT * FROM notices WHERE category = 'notice' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $latestNotice = $stmt->fetch();
}

// 배경 이미지 랜덤 선택
$backgrounds = ['beach.jpg', 'travel.jpg', 'adventure.jpg'];
$bgImage = $backgrounds[array_rand($backgrounds)];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#7ec8e3">
    <title><?= h($event['event_name']) ?> - 본투어 인터내셔날</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <link rel="stylesheet" href="/born/assets/css/animations.css">
    <link rel="stylesheet" href="/born/assets/css/user.css">
    <link rel="stylesheet" href="/born/assets/css/user-pc.css">
    <style>
        .main-hero-new {
            position: relative;
            margin: -20px -20px 24px -20px;
            padding: 40px 24px;
            background: linear-gradient(135deg, #a8d5e5 0%, #f5b8c2 50%, #ffd6a5 100%);
            border-radius: 0 0 32px 32px;
            color: #2d3748;
            text-align: center;
            overflow: hidden;
        }

        .main-hero-new::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, transparent 60%);
            animation: shimmer 8s infinite linear;
        }

        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-welcome {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
        }

        .hero-user-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #2d3748;
        }

        .hero-user-name span {
            color: #e53e3e;
        }

        .hero-event-title {
            font-size: 18px;
            font-weight: 600;
            margin: 16px 0 8px;
            color: #2d3748;
        }

        .hero-event-dates {
            font-size: 14px;
            color: #4a5568;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .hero-event-dates svg {
            width: 16px;
            height: 16px;
        }

        .dday-badge-new {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 20px;
            padding: 12px 28px;
            background: linear-gradient(135deg, #ff8a9b 0%, #ff6b7a 100%);
            color: white;
            font-size: 20px;
            font-weight: 800;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(255, 107, 122, 0.4);
            animation: pulse-glow 2s infinite;
        }

        .dday-badge-new.past {
            background: linear-gradient(135deg, #b8c5d0 0%, #8fa3b0 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(143, 163, 176, 0.4);
        }

        @keyframes pulse-glow {
            0%, 100% { transform: scale(1); box-shadow: 0 4px 20px rgba(255, 107, 122, 0.4); }
            50% { transform: scale(1.02); box-shadow: 0 6px 30px rgba(255, 107, 122, 0.6); }
        }

        .dday-badge-new svg {
            width: 20px;
            height: 20px;
        }

        /* 퀵 메뉴 */
        .quick-menu {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .quick-menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px 8px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            text-decoration: none;
            color: var(--gray-700);
            transition: all 0.3s ease;
        }

        .quick-menu-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(255, 138, 155, 0.2);
        }

        .quick-menu-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #a8d5e5 0%, #7ec8e3 100%);
            color: white;
        }

        .quick-menu-icon svg {
            width: 24px;
            height: 24px;
        }

        .quick-menu-icon.accent {
            background: linear-gradient(135deg, #e07a5f 0%, #c45a3d 100%);
        }

        .quick-menu-icon.green {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
        }

        .quick-menu-icon.orange {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }

        .quick-menu-icon.purple {
            background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
        }

        .quick-menu-item span {
            font-size: 12px;
            font-weight: 600;
        }

        /* 섹션 타이틀 */
        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 16px;
        }

        .section-title svg {
            width: 20px;
            height: 20px;
            color: var(--primary-600);
        }

        /* 정보 카드 개선 */
        .info-card-new {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .info-card-new .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .info-card-new .card-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-100) 0%, var(--primary-50) 100%);
            color: var(--primary-600);
        }

        .info-card-new .card-icon svg {
            width: 22px;
            height: 22px;
        }

        .info-card-new .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .info-card-new .card-subtitle {
            font-size: 13px;
            color: var(--gray-500);
            margin-top: 2px;
        }

        .info-card-new .card-body {
            font-size: 14px;
            color: var(--gray-600);
            line-height: 1.7;
        }

        .info-card-new .card-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
            padding: 12px;
            background: var(--primary-50);
            color: var(--primary-600);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .info-card-new .card-link:hover {
            background: var(--primary-100);
        }

        .info-card-new .card-link svg {
            width: 18px;
            height: 18px;
        }

        /* 하단 네비게이션 */
        .bottom-nav {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            padding: 12px 16px;
            background: white;
            border-radius: 20px;
            margin-top: 24px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        }

        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--gray-500);
            font-size: 11px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .bottom-nav-item:hover,
        .bottom-nav-item.active {
            color: var(--primary-600);
            background: var(--primary-50);
        }

        .bottom-nav-item svg {
            width: 22px;
            height: 22px;
        }

        /* 공지사항 배너 */
        .notice-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
            border-radius: 14px;
            margin-bottom: 20px;
            text-decoration: none;
            border: 1px solid rgba(255, 193, 7, 0.3);
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.15);
            transition: all 0.3s ease;
        }

        .notice-banner:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.25);
        }

        .notice-banner-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            border-radius: 10px;
            color: white;
            flex-shrink: 0;
        }

        .notice-banner-icon svg {
            width: 18px;
            height: 18px;
        }

        .notice-banner-content {
            flex: 1;
            min-width: 0;
        }

        .notice-banner-label {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            color: #f57c00;
            background: rgba(255, 152, 0, 0.15);
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 6px;
        }

        .notice-banner-title {
            font-size: 13px;
            font-weight: 600;
            color: #5d4037;
        }

        .notice-banner-arrow {
            width: 18px;
            height: 18px;
            color: #f57c00;
            flex-shrink: 0;
        }

        /* 푸터 */
        .main-footer {
            margin-top: 32px;
            padding: 24px 16px;
            background: var(--gray-50);
            border-radius: 20px 20px 0 0;
            text-align: center;
        }

        .main-footer-logo {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary-600);
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .main-footer-info {
            font-size: 11px;
            color: var(--gray-500);
            line-height: 1.8;
        }

        .main-footer-info p {
            margin: 0;
        }

        .main-footer-copyright {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
            font-size: 11px;
            color: var(--gray-400);
        }
    </style>
</head>
<body>
    <!-- PC 배경 장식 -->
    <div class="pc-decoration circle-1"></div>
    <div class="pc-decoration circle-2"></div>
    <div class="pc-decoration circle-3"></div>

    <!-- PC 브랜딩 (1400px 이상) -->
    <div class="pc-branding">
        <h1>BORN TOUR</h1>
        <p>여행의 시작부터 끝까지<br>함께하는 든든한 파트너</p>
        <span class="tagline">Special Travel Experience</span>
    </div>

    <div class="phone-frame">
        <div class="phone-screen">
            <div class="phone-screen-inner">
                <div class="user-layout">
                    <!-- 헤더 -->
                    <header class="user-header" style="background: transparent; position: absolute; top: 0; left: 0; right: 0; z-index: 10;">
                        <div class="header-logo" style="color: white; display: flex; align-items: center; gap: 8px;">
                            <?php if (!empty($event['client_logo'])): ?>
                                <img src="/born/uploads/logos/<?= h($event['client_logo']) ?>" alt="" style="height: 24px; max-width: 60px; object-fit: contain; filter: brightness(0) invert(1);">
                                <span style="color: rgba(255,255,255,0.5);">×</span>
                            <?php endif; ?>
                            <span style="font-weight: 700;">BORN TOUR</span>
                        </div>
                        <div class="header-menu" onclick="BornUser.openSidebar()" style="color: white;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="3" y1="12" x2="21" y2="12"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <line x1="3" y1="18" x2="21" y2="18"/>
                            </svg>
                        </div>
                    </header>

                    <!-- 콘텐츠 -->
                    <div class="user-content" style="padding-top: 0;">
                        <!-- 히어로 섹션 -->
                        <div class="main-hero-new page-enter">
                            <div class="hero-content">
                                <p class="hero-welcome">환영합니다</p>
                                <h1 class="hero-user-name"><?= h($user['name_ko']) ?><span>님</span></h1>

                                <?php if ($visibility['event_name']): ?>
                                    <h2 class="hero-event-title"><?= h($event['event_name']) ?></h2>
                                <?php endif; ?>

                                <?php if ($visibility['event_date']): ?>
                                    <p class="hero-event-dates">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <?= format_date_range($event['start_date'], $event['end_date']) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="dday-badge-new <?= $dday['isPast'] ? 'past' : '' ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <?= $dday['text'] ?>
                                </div>
                            </div>
                        </div>

                        <!-- 최신 공지사항 배너 -->
                        <?php if ($latestNotice): ?>
                            <a href="/born/user/announcements.php" class="notice-banner page-enter" style="animation-delay: 0.05s;">
                                <div class="notice-banner-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                    </svg>
                                </div>
                                <div class="notice-banner-content">
                                    <span class="notice-banner-label">공지</span>
                                    <span class="notice-banner-title"><?= h(mb_substr($latestNotice['title'], 0, 20)) ?><?= mb_strlen($latestNotice['title']) > 20 ? '...' : '' ?></span>
                                </div>
                                <svg class="notice-banner-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </a>
                        <?php endif; ?>

                        <!-- 퀵 메뉴 -->
                        <div class="quick-menu page-enter" style="animation-delay: 0.1s;">
                            <?php if ($visibility['flight']): ?>
                                <a href="/born/user/flight.php" class="quick-menu-item">
                                    <div class="quick-menu-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                                        </svg>
                                    </div>
                                    <span>항공</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($visibility['meeting']): ?>
                                <a href="/born/user/meeting.php" class="quick-menu-item">
                                    <div class="quick-menu-icon orange">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                    </div>
                                    <span>미팅</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($visibility['schedule'] && $event['schedule_url']): ?>
                                <a href="<?= h($event['schedule_url']) ?>" target="_blank" class="quick-menu-item">
                                    <div class="quick-menu-icon green">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                    </div>
                                    <span>일정표</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($visibility['reservation']): ?>
                                <a href="/born/user/reservation.php" class="quick-menu-item">
                                    <div class="quick-menu-icon purple">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                        </svg>
                                    </div>
                                    <span>예약확인</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- 여행 준비 섹션 -->
                        <div class="section-title page-enter" style="animation-delay: 0.15s;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            여행 준비
                        </div>

                        <div class="quick-menu page-enter" style="animation-delay: 0.2s; grid-template-columns: repeat(3, 1fr);">
                            <?php if ($visibility['passport_upload']): ?>
                                <a href="/born/user/passport-upload.php" class="quick-menu-item">
                                    <div class="quick-menu-icon accent">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                                            <circle cx="12" cy="10" r="3"/>
                                            <line x1="7" y1="16" x2="17" y2="16"/>
                                        </svg>
                                    </div>
                                    <span>여권등록</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($visibility['optional_tour']): ?>
                                <a href="/born/user/optional-tour.php" class="quick-menu-item">
                                    <div class="quick-menu-icon green">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polygon points="10 8 16 12 10 16 10 8"/>
                                        </svg>
                                    </div>
                                    <span>선택관광</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($visibility['survey']): ?>
                                <a href="/born/user/survey.php" class="quick-menu-item">
                                    <div class="quick-menu-icon orange">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </div>
                                    <span>설문</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- 유의사항 카드 -->
                        <?php if ($visibility['notice'] && $event['travel_notice']): ?>
                            <div class="info-card-new page-enter" style="animation-delay: 0.25s;">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="12" y1="16" x2="12" y2="12"/>
                                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="card-title">여행 전 유의사항</div>
                                        <div class="card-subtitle">출발 전 꼭 확인해주세요</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?= nl2br(h(mb_substr($event['travel_notice'], 0, 100))) ?>...
                                </div>
                                <a href="/born/user/notice.php" class="card-link">
                                    자세히 보기
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"/>
                                    </svg>
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- 하단 네비게이션 -->
                        <div class="bottom-nav page-enter" style="animation-delay: 0.3s;">
                            <a href="/born/user/main.php" class="bottom-nav-item active">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                                홈
                            </a>
                            <a href="/born/user/announcements.php" class="bottom-nav-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                </svg>
                                공지
                            </a>
                            <a href="/born/user/faq.php" class="bottom-nav-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                문의
                            </a>
                            <a href="#" class="bottom-nav-item" onclick="BornUser.openSidebar(); return false;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="3" y1="12" x2="21" y2="12"/>
                                    <line x1="3" y1="6" x2="21" y2="6"/>
                                    <line x1="3" y1="18" x2="21" y2="18"/>
                                </svg>
                                메뉴
                            </a>
                        </div>

                        <!-- 푸터 (사업자 정보) -->
                        <div class="main-footer page-enter" style="animation-delay: 0.35s;">
                            <div class="main-footer-logo">BORN TOUR INTERNATIONAL</div>
                            <div class="main-footer-info">
                                <p><?= COMPANY_NAME ?></p>
                                <p>대표: 이은정 | 사업자등록번호: 123-45-67890</p>
                                <p>주소: 서울특별시 중구 을지로 100</p>
                                <p>TEL: <?= COMPANY_PHONE ?> | FAX: 02-1234-5679</p>
                                <p>통신판매업신고: 제2024-서울중구-0000호</p>
                            </div>
                            <div class="main-footer-copyright">
                                © <?= date('Y') ?> <?= COMPANY_NAME ?>. All rights reserved.
                            </div>
                        </div>
                    </div>

                    <!-- 사이드바 -->
                    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/born/assets/js/user.js"></script>
</body>
</html>
