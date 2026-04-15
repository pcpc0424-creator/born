<?php
/**
 * 본투어 인터내셔날 - 항공 스케줄
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['flight']) {
    redirect('/user/main.php');
}

$db = db();
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$user['event_id']]);
$event = $stmt->fetch();

// 현지 도착 시간 계산 함수
function calculateArrivalTime($departureTime, $flightDuration, $timezoneOffset) {
    if (!$departureTime || !$flightDuration) return null;

    $departure = new DateTime($departureTime);
    $departure->modify("+{$flightDuration} minutes");
    $departure->modify("{$timezoneOffset} hours");

    return $departure->format('H:i');
}

// 출국편 현지 도착 시간 (직접 입력값 우선, 없으면 계산)
$departureArrivalTime = null;
if ($event['flight_time_departure_arrival']) {
    $departureArrivalTime = date('H:i', strtotime($event['flight_time_departure_arrival']));
} elseif ($event['flight_time_departure'] && $event['flight_duration_departure']) {
    $departureArrivalTime = calculateArrivalTime(
        $event['flight_time_departure'],
        $event['flight_duration_departure'],
        $event['timezone_offset'] ?? 0
    );
}

// 귀국편 한국 도착 시간 (직접 입력값 우선, 없으면 계산)
$returnArrivalTime = null;
if ($event['flight_time_return_arrival']) {
    $returnArrivalTime = date('H:i', strtotime($event['flight_time_return_arrival']));
} elseif ($event['flight_time_return'] && $event['flight_duration_return']) {
    $returnArrivalTime = calculateArrivalTime(
        $event['flight_time_return'],
        $event['flight_duration_return'],
        -($event['timezone_offset'] ?? 0)
    );
}

$pageTitle = '항공 스케줄';
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
                        <!-- 출국편 -->
                        <div class="flight-card page-enter">
                            <div class="flight-header">
                                <div class="flight-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 2L11 13"/>
                                        <path d="M22 2l-7 20-4-9-9-4 20-7z"/>
                                    </svg>
                                </div>
                                <h3>출국편</h3>
                            </div>

                            <div class="flight-route">
                                <div class="flight-city">
                                    <div class="code"><?= h($event['departure_airport_code'] ?: 'ICN') ?></div>
                                    <div class="name"><?= h($event['departure_airport'] ?: '인천') ?></div>
                                </div>
                                <div class="flight-line">
                                    <div class="line"></div>
                                    <div class="plane">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                                        </svg>
                                    </div>
                                    <div class="line"></div>
                                </div>
                                <div class="flight-city">
                                    <div class="code"><?= h($event['arrival_airport_code'] ?: '-') ?></div>
                                    <div class="name"><?= h($event['arrival_airport'] ?: '-') ?></div>
                                </div>
                            </div>

                            <div class="flight-details">
                                <div class="flight-detail-item">
                                    <label>항공편</label>
                                    <span><?= h($event['airline'] ?? '-') ?> <?= h($event['flight_departure'] ?? '') ?></span>
                                </div>
                                <div class="flight-detail-item">
                                    <label>출발 시간</label>
                                    <span><?= $event['flight_time_departure'] ? date('H:i', strtotime($event['flight_time_departure'])) : '-' ?></span>
                                </div>
                                <?php if ($departureArrivalTime): ?>
                                <div class="flight-detail-item">
                                    <label>도착 시간 <small style="color: var(--gray-500);">(현지)</small></label>
                                    <span style="color: var(--primary-600); font-weight: 600;"><?= $departureArrivalTime ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="flight-detail-item">
                                    <label>출발일</label>
                                    <span><?= preg_replace('/(년)\s/', '$1<br>', format_date_kr($event['start_date'])) ?></span>
                                </div>
                                <?php if ($event['flight_duration_departure']): ?>
                                <div class="flight-detail-item">
                                    <label>비행시간</label>
                                    <span><?= floor($event['flight_duration_departure'] / 60) ?>시간 <?= $event['flight_duration_departure'] % 60 ?>분</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 귀국편 -->
                        <div class="flight-card page-enter" style="animation-delay: 0.1s;">
                            <div class="flight-header">
                                <div class="flight-icon return">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 2L11 13"/>
                                        <path d="M22 2l-7 20-4-9-9-4 20-7z"/>
                                    </svg>
                                </div>
                                <h3>귀국편</h3>
                            </div>

                            <div class="flight-route">
                                <div class="flight-city">
                                    <div class="code"><?= h($event['arrival_airport_code'] ?: '-') ?></div>
                                    <div class="name"><?= h($event['arrival_airport'] ?: '-') ?></div>
                                </div>
                                <div class="flight-line">
                                    <div class="line"></div>
                                    <div class="plane">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                                        </svg>
                                    </div>
                                    <div class="line"></div>
                                </div>
                                <div class="flight-city">
                                    <div class="code"><?= h($event['departure_airport_code'] ?: 'ICN') ?></div>
                                    <div class="name"><?= h($event['departure_airport'] ?: '인천') ?></div>
                                </div>
                            </div>

                            <div class="flight-details">
                                <div class="flight-detail-item">
                                    <label>항공편</label>
                                    <span><?= h($event['airline'] ?? '-') ?> <?= h($event['flight_return'] ?? '') ?></span>
                                </div>
                                <div class="flight-detail-item">
                                    <label>출발 시간 <small style="color: var(--gray-500);">(현지)</small></label>
                                    <span><?= $event['flight_time_return'] ? date('H:i', strtotime($event['flight_time_return'])) : '-' ?></span>
                                </div>
                                <?php if ($returnArrivalTime): ?>
                                <div class="flight-detail-item">
                                    <label>도착 시간 <small style="color: var(--gray-500);">(한국)</small></label>
                                    <span style="color: var(--primary-600); font-weight: 600;"><?= $returnArrivalTime ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="flight-detail-item">
                                    <label>귀국일</label>
                                    <span><?= preg_replace('/(년)\s/', '$1<br>', format_date_kr($event['end_date'])) ?></span>
                                </div>
                                <?php if ($event['flight_duration_return']): ?>
                                <div class="flight-detail-item">
                                    <label>비행시간</label>
                                    <span><?= floor($event['flight_duration_return'] / 60) ?>시간 <?= $event['flight_duration_return'] % 60 ?>분</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 수하물 규정 -->
                        <?php if ($event['baggage_info']): ?>
                            <div class="info-card page-enter" style="animation-delay: 0.2s;">
                                <div class="info-card-header">
                                    <h3>수하물 규정</h3>
                                </div>
                                <div class="info-card-body">
                                    <p style="font-size: 14px; color: var(--gray-600); line-height: 1.7;">
                                        <?= nl2br(h($event['baggage_info'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 기타사항 -->
                        <?php if ($event['flight_etc']): ?>
                            <div class="info-card page-enter" style="animation-delay: 0.3s;">
                                <div class="info-card-header">
                                    <h3>기타 안내</h3>
                                </div>
                                <div class="info-card-body">
                                    <p style="font-size: 14px; color: var(--gray-600); line-height: 1.7;">
                                        <?= nl2br(h($event['flight_etc'])) ?>
                                    </p>
                                </div>
                            </div>
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
