<?php
/**
 * 본투어 인터내셔날 - 예약 상세
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['reservation']) {
    redirect('/user/main.php');
}

$db = db();

// 행사 정보
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$user['event_id']]);
$event = $stmt->fetch();

// 행사-회원 매칭 정보
$stmt = $db->prepare("SELECT * FROM event_members WHERE event_id = ? AND member_id = ?");
$stmt->execute([$user['event_id'], $user['id']]);
$eventMember = $stmt->fetch();

// 선택관광 정보
$optionalTours = [];
if ($eventMember && $eventMember['optional_tour_ids']) {
    $tourIds = json_decode($eventMember['optional_tour_ids'], true);
    if (!empty($tourIds)) {
        $placeholders = implode(',', array_fill(0, count($tourIds), '?'));
        $stmt = $db->prepare("SELECT * FROM optional_tours WHERE id IN ($placeholders)");
        $stmt->execute($tourIds);
        $optionalTours = $stmt->fetchAll();
    }
}

$pageTitle = '예약 상세';
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
                        <!-- 예약자 정보 -->
                        <div class="info-card page-enter">
                            <div class="info-card-header">
                                <h3>예약자 정보</h3>
                            </div>
                            <div class="info-card-body">
                                <div class="reservation-details">
                                    <div class="reservation-row">
                                        <span class="label">이름</span>
                                        <span class="value"><?= h($user['name_ko']) ?></span>
                                    </div>
                                    <?php if ($user['name_en']): ?>
                                        <div class="reservation-row">
                                            <span class="label">영문명</span>
                                            <span class="value"><?= h($user['name_en']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($user['phone']): ?>
                                        <div class="reservation-row">
                                            <span class="label">연락처</span>
                                            <span class="value"><?= h($user['phone']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 행사 정보 -->
                        <div class="info-card page-enter" style="animation-delay: 0.1s;">
                            <div class="info-card-header">
                                <h3>행사 정보</h3>
                            </div>
                            <div class="info-card-body">
                                <div class="reservation-details">
                                    <div class="reservation-row">
                                        <span class="label">행사명</span>
                                        <span class="value"><?= h($event['event_name']) ?></span>
                                    </div>
                                    <div class="reservation-row">
                                        <span class="label">여행 기간</span>
                                        <span class="value"><?= format_date_range($event['start_date'], $event['end_date']) ?></span>
                                    </div>
                                    <?php if ($event['airline']): ?>
                                        <div class="reservation-row">
                                            <span class="label">항공사</span>
                                            <span class="value"><?= h($event['airline']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 배정 정보 -->
                        <?php if ($eventMember): ?>
                            <div class="info-card page-enter" style="animation-delay: 0.2s;">
                                <div class="info-card-header">
                                    <h3>배정 정보</h3>
                                </div>
                                <div class="info-card-body">
                                    <div class="assignment-grid">
                                        <?php
                                        $busData = [];
                                        $hasBus = false;
                                        if (!empty($eventMember['bus_number'])) {
                                            $decoded = json_decode($eventMember['bus_number'], true);
                                            if (is_array($decoded)) {
                                                $busData = array_filter($decoded);
                                                $hasBus = !empty($busData);
                                            } else {
                                                $hasBus = true;
                                            }
                                        }
                                        ?>

                                        <?php if ($eventMember['room_number']): ?>
                                            <div class="assignment-item">
                                                <div class="assignment-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/>
                                                    </svg>
                                                </div>
                                                <div class="assignment-info">
                                                    <span class="assignment-label">객실</span>
                                                    <span class="assignment-value"><?= h($eventMember['room_number']) ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($eventMember['dinner_table']): ?>
                                            <div class="assignment-item">
                                                <div class="assignment-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                                                        <line x1="6" y1="1" x2="6" y2="4"/>
                                                        <line x1="10" y1="1" x2="10" y2="4"/>
                                                        <line x1="14" y1="1" x2="14" y2="4"/>
                                                    </svg>
                                                </div>
                                                <div class="assignment-info">
                                                    <span class="assignment-label">만찬<br>테이블</span>
                                                    <span class="assignment-value"><?= h($eventMember['dinner_table']) ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($hasBus): ?>
                                            <div class="assignment-item full-width">
                                                <div class="assignment-icon">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M8 6v6m4-6v6m4-6v6M3 14h18M3 18h18M5 22h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"/>
                                                    </svg>
                                                </div>
                                                <div class="assignment-info">
                                                    <span class="assignment-label">버스</span>
                                                    <span class="assignment-value"><?php
                                                        if (!empty($busData)) {
                                                            $parts = [];
                                                            foreach ($busData as $day => $bus) {
                                                                if ($bus) $parts[] = $day . '일차: ' . h($bus) . '호차';
                                                            }
                                                            echo implode('<br>', $parts);
                                                        } else {
                                                            echo h($eventMember['bus_number']) . '호차';
                                                        }
                                                    ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!$hasBus && !$eventMember['room_number'] && !$eventMember['dinner_table']): ?>
                                        <p class="no-data">배정 정보가 아직 등록되지 않았습니다.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 선택관광 -->
                        <?php if (!empty($optionalTours)): ?>
                            <div class="info-card page-enter" style="animation-delay: 0.3s;">
                                <div class="info-card-header">
                                    <h3>신청 선택관광</h3>
                                </div>
                                <div class="info-card-body">
                                    <div class="optional-tour-list">
                                        <?php foreach ($optionalTours as $tour): ?>
                                            <div class="optional-tour-item">
                                                <div class="tour-name"><?= h($tour['tour_name']) ?></div>
                                                <?php if ($tour['price'] > 0): ?>
                                                    <div class="tour-price"><?= number_format($tour['price']) ?>원</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 항공편 정보 -->
                        <div class="info-card page-enter" style="animation-delay: 0.4s;">
                            <div class="info-card-header">
                                <h3>항공편 정보</h3>
                            </div>
                            <div class="info-card-body">
                                <div class="flight-summary">
                                    <div class="flight-summary-item">
                                        <span class="flight-direction">출국</span>
                                        <div class="flight-info">
                                            <span class="flight-date"><?= format_date_kr($event['start_date']) ?></span>
                                            <span class="flight-detail">
                                                <?= h($event['airline'] ?? '-') ?>
                                                <?= h($event['flight_departure'] ?? '') ?>
                                                <?= $event['flight_time_departure'] ? date('H:i', strtotime($event['flight_time_departure'])) : '' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flight-summary-item">
                                        <span class="flight-direction return">귀국</span>
                                        <div class="flight-info">
                                            <span class="flight-date"><?= format_date_kr($event['end_date']) ?></span>
                                            <span class="flight-detail">
                                                <?= h($event['airline'] ?? '-') ?>
                                                <?= h($event['flight_return'] ?? '') ?>
                                                <?= $event['flight_time_return'] ? date('H:i', strtotime($event['flight_time_return'])) : '' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/user.js"></script>
</body>
</html>
