<?php
/**
 * 본투어 인터내셔날 - 일정표
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['schedule']) {
    redirect('/user/main.php');
}

$db = db();

// 행사 정보
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$user['event_id']]);
$event = $stmt->fetch();

// 일정표 데이터
$stmt = $db->prepare("
    SELECT sd.*
    FROM schedule_days sd
    WHERE sd.event_id = ?
    ORDER BY sd.day_number
");
$stmt->execute([$user['event_id']]);
$days = $stmt->fetchAll();

// 각 일차별 세부 항목 조회
foreach ($days as &$day) {
    $stmt = $db->prepare("SELECT * FROM schedule_items WHERE schedule_day_id = ? ORDER BY sort_order");
    $stmt->execute([$day['id']]);
    $day['items'] = $stmt->fetchAll();
}
unset($day);

$pageTitle = '일정표';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6dc5d1">
    <title><?= $pageTitle ?> - 본투어</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="stylesheet" href="/assets/css/user.css">
    <link rel="stylesheet" href="/assets/css/user-pc.css">
    <style>
        .schedule-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 20px 16px 8px;
        }

        .schedule-logo img {
            height: 28px;
            max-width: 80px;
            object-fit: contain;
        }

        .schedule-logo .logo-divider {
            color: var(--gray-300);
            font-size: 18px;
            font-weight: 300;
        }

        .schedule-logo .logo-text {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary-600);
            letter-spacing: 1px;
        }

        .schedule-title {
            text-align: center;
            font-size: 22px;
            font-weight: 800;
            color: var(--gray-800);
            margin: 16px 0 24px;
            letter-spacing: 2px;
        }

        .day-section {
            margin-bottom: 28px;
        }

        .day-header {
            display: flex;
            align-items: baseline;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gray-800);
            margin-bottom: 16px;
        }

        .day-header .day-num {
            font-size: 18px;
            font-weight: 800;
            color: var(--gray-800);
        }

        .day-header .day-date {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-500);
        }

        .day-body {
            padding: 0 4px;
        }

        .day-location {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 12px;
        }

        .day-location .loc-icon {
            color: var(--primary-600);
            flex-shrink: 0;
            margin-top: 1px;
        }

        .day-location .loc-icon svg {
            width: 18px;
            height: 18px;
        }

        .day-location .loc-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .day-items {
            padding-left: 26px;
            margin-bottom: 14px;
        }

        .day-item {
            margin-bottom: 6px;
        }

        .day-item .item-title {
            font-size: 14px;
            color: var(--gray-700);
            line-height: 1.6;
        }

        .day-item .item-desc {
            font-size: 12px;
            color: var(--gray-500);
            padding-left: 8px;
            line-height: 1.5;
        }

        .day-hotel {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .day-hotel .hotel-icon {
            flex-shrink: 0;
        }

        .day-hotel .hotel-icon svg {
            width: 18px;
            height: 18px;
        }

        .day-hotel .hotel-label {
            font-size: 15px;
            font-weight: 800;
            color: var(--gray-800);
        }

        .day-hotel .hotel-name {
            font-size: 15px;
            font-weight: 500;
            color: var(--gray-700);
        }

        .day-meal {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .day-meal .meal-icon {
            flex-shrink: 0;
        }

        .day-meal .meal-icon svg {
            width: 18px;
            height: 18px;
        }

        .day-meal .meal-label {
            font-size: 15px;
            font-weight: 800;
            color: var(--gray-800);
        }

        .day-meal .meal-detail {
            font-size: 14px;
            color: var(--gray-600);
        }

        .day-meal .meal-sep {
            color: var(--gray-300);
            margin: 0 2px;
        }

        .schedule-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-400);
        }

        .schedule-empty svg {
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
            opacity: 0.4;
        }

        .schedule-empty p {
            font-size: 15px;
        }
    </style>
</head>
<body>
    <div class="phone-frame">
        <div class="phone-screen">
            <div class="phone-screen-inner">
                <div class="user-layout">
                    <header class="user-header">
                        <div class="header-back" >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"/>
                            </svg>
                        </div>
                        <h1 class="header-title"><?= $pageTitle ?></h1>
                        <div class="header-menu" onclick="BornUser.openSidebar()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="3" y1="12" x2="21" y2="12"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <line x1="3" y1="18" x2="21" y2="18"/>
                            </svg>
                        </div>
                    </header>

                    <div class="user-content">
                        <!-- 로고 영역 -->
                        <div class="schedule-logo page-enter">
                            <?php if (!empty($event['client_logo'])): ?>
                                <img src="/uploads/logos/<?= h($event['client_logo']) ?>" alt="거래처 로고">
                                <span class="logo-divider">×</span>
                            <?php endif; ?>
                            <span class="logo-text">(주)본투어인터내셔날</span>
                        </div>

                        <!-- 타이틀 -->
                        <div class="schedule-title page-enter">[ 일정표 ]</div>

                        <?php if (empty($days)): ?>
                            <div class="schedule-empty page-enter">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <p>일정표가 아직 준비되지 않았습니다.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($days as $idx => $day): ?>
                                <?php
                                // 날짜 자동 계산
                                $dayDate = new DateTime($event['start_date']);
                                $dayDate->modify('+' . ($day['day_number'] - 1) . ' days');
                                $weekdays = ['일', '월', '화', '수', '목', '금', '토'];
                                $dateStr = $dayDate->format('y') . '년' . $dayDate->format('m') . '월' . $dayDate->format('d') . '일(' . $weekdays[(int)$dayDate->format('w')] . ')';
                                ?>
                                <div class="day-section page-enter" style="animation-delay: <?= $idx * 0.05 ?>s;">
                                    <div class="day-header">
                                        <span class="day-num"><?= $day['day_number'] ?>일차</span>
                                        <span class="day-date"><?= $dateStr ?></span>
                                    </div>

                                    <div class="day-body">
                                        <?php if ($day['location']): ?>
                                            <div class="day-location">
                                                <span class="loc-icon">
                                                    <svg viewBox="0 0 24 24" fill="var(--primary-600)" stroke="none">
                                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                                    </svg>
                                                </span>
                                                <span class="loc-name"><?= h($day['location']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($day['items'])): ?>
                                            <div class="day-items">
                                                <?php foreach ($day['items'] as $item): ?>
                                                    <div class="day-item">
                                                        <div class="item-title"><?= h($item['title']) ?></div>
                                                        <?php if (!empty($item['description'])): ?>
                                                            <div class="item-desc"><?= nl2br(h($item['description'])) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($day['hotel_name']): ?>
                                            <div class="day-hotel">
                                                <span class="hotel-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M3 21h18M3 7v14M21 7v14M6 11h4v4H6zM14 11h4v4h-4zM6 3h12v4H6z"/>
                                                    </svg>
                                                </span>
                                                <span class="hotel-label">호텔</span>
                                                <span class="hotel-name"><?= h($day['hotel_name']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($day['meal_breakfast'] || $day['meal_lunch'] || $day['meal_dinner']): ?>
                                            <div class="day-meal">
                                                <span class="meal-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                                                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                                                        <line x1="6" y1="1" x2="6" y2="4"/>
                                                        <line x1="10" y1="1" x2="10" y2="4"/>
                                                        <line x1="14" y1="1" x2="14" y2="4"/>
                                                    </svg>
                                                </span>
                                                <span class="meal-label">식사</span>
                                                <span class="meal-detail">
                                                    <?php
                                                    $meals = [];
                                                    if ($day['meal_breakfast']) $meals[] = '조식 ' . h($day['meal_breakfast']);
                                                    if ($day['meal_lunch']) $meals[] = '중식 ' . h($day['meal_lunch']);
                                                    if ($day['meal_dinner']) $meals[] = '석식 ' . h($day['meal_dinner']);
                                                    echo implode(' <span class="meal-sep">·</span> ', $meals);
                                                    ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/user.js"></script>
</body>
</html>
