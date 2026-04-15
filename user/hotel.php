<?php
/**
 * 본투어 인터내셔날 - 호텔 정보
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['hotel']) {
    redirect('/user/main.php');
}

$db = db();
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$user['event_id']]);
$event = $stmt->fetch();

// 일정표 일차별 호텔 정보 조회 (schedule_days에 hotel_id가 있으면 hotels 테이블과 조인)
$stmt = $db->prepare("
    SELECT sd.day_number, sd.hotel_name as schedule_hotel_name, sd.hotel_id,
           h.id as h_id, h.hotel_name, h.hotel_name_en, h.star_rating,
           h.phone, h.address, h.facilities, h.amenities, h.amenities_hours, h.description,
           h.map_url, h.detail_url, h.image_url,
           h.check_in_date, h.check_out_date, h.check_in_time, h.check_out_time
    FROM schedule_days sd
    LEFT JOIN hotels h ON sd.hotel_id = h.id
    WHERE sd.event_id = ?
    ORDER BY sd.day_number
");
$stmt->execute([$user['event_id']]);
$dayHotels = $stmt->fetchAll();

// schedule_days가 없으면 기존 hotels 테이블에서 직접 조회
if (empty($dayHotels)) {
    $stmt = $db->prepare("SELECT * FROM hotels WHERE event_id = ? ORDER BY sort_order ASC, check_in_date ASC");
    $stmt->execute([$user['event_id']]);
    $fallbackHotels = $stmt->fetchAll();
}

$pageTitle = '호텔정보';
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
        .hotel-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 20px 16px 8px;
        }

        .hotel-logo img {
            height: 28px;
            max-width: 80px;
            object-fit: contain;
        }

        .hotel-logo .logo-divider {
            color: var(--gray-300);
            font-size: 18px;
            font-weight: 300;
        }

        .hotel-logo .logo-text {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary-600);
            letter-spacing: 1px;
        }

        .hotel-page-title {
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

        .hotel-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.06);
        }

        .hotel-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            cursor: pointer;
        }

        .hotel-card-header .hotel-title-area {
            flex: 1;
        }

        .hotel-card-header .hotel-name-ko {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
            line-height: 1.4;
        }

        .hotel-card-header .hotel-star {
            color: #f59e0b;
            font-size: 13px;
            letter-spacing: 1px;
        }

        .hotel-card-header .hotel-name-en {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 2px;
            line-height: 1.4;
        }

        .hotel-card-header .arrow {
            color: var(--gray-400);
            flex-shrink: 0;
            transition: transform 0.3s;
        }

        .hotel-card-header .arrow svg {
            width: 20px;
            height: 20px;
        }

        .hotel-card.open .hotel-card-header .arrow {
            transform: rotate(90deg);
        }

        .hotel-card-detail {
            display: none;
            border-top: 1px solid var(--gray-100);
            padding: 16px 18px;
        }

        .hotel-card.open .hotel-card-detail {
            display: block;
        }

        .hotel-detail-item {
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
            color: var(--gray-600);
            line-height: 1.6;
        }

        .hotel-detail-item:last-child {
            border-bottom: none;
        }

        .hotel-detail-item .label {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
            margin-bottom: 4px;
        }

        .hotel-detail-item .value {
            color: var(--gray-700);
        }

        .hotel-detail-item .value a {
            color: var(--primary-600);
            text-decoration: none;
            font-weight: 600;
        }

        .hotel-facilities-section {
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .hotel-facilities-section .label {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
            margin-bottom: 8px;
        }

        .hotel-facilities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .hotel-facility-tag {
            display: inline-block;
            padding: 4px 10px;
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 12px;
            border-radius: 20px;
        }

        .hotel-facilities-empty {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--gray-400);
            font-size: 13px;
        }

        .hotel-facilities-empty svg {
            width: 16px;
            height: 16px;
        }

        .hotel-actions {
            display: flex;
            gap: 8px;
            padding-top: 12px;
        }

        .hotel-action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .hotel-action-btn svg {
            width: 16px;
            height: 16px;
        }

        .hotel-action-btn.map {
            background: var(--primary-50);
            color: var(--primary-600);
        }

        .hotel-action-btn.call {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .hotel-action-btn.detail {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .no-hotel {
            padding: 20px 0;
            text-align: center;
            color: var(--gray-400);
            font-size: 14px;
        }

        .hotel-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-400);
        }

        .hotel-empty svg {
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
            opacity: 0.4;
        }

        .hotel-empty p {
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
                        <div class="header-back">
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
                        <div class="hotel-logo page-enter">
                            <?php if (!empty($event['client_logo'])): ?>
                                <img src="/uploads/logos/<?= h($event['client_logo']) ?>" alt="거래처 로고">
                                <span class="logo-divider">×</span>
                            <?php endif; ?>
                            <span class="logo-text">(주)본투어인터내셔날</span>
                        </div>

                        <!-- 타이틀 -->
                        <div class="hotel-page-title page-enter">[ 호텔정보 ]</div>

                        <?php if (!empty($dayHotels)): ?>
                            <!-- 일차별 호텔 표시 -->
                            <?php
                            $prevDayNumber = null;
                            foreach ($dayHotels as $idx => $dh):
                                $dayNumber = $dh['day_number'];
                                // 호텔이 없는 일차는 건너뛰기
                                $hasHotel = !empty($dh['h_id']) || !empty($dh['schedule_hotel_name']);
                                if (!$hasHotel) continue;

                                // 같은 일차가 반복되지 않도록
                                if ($dayNumber === $prevDayNumber) continue;
                                $prevDayNumber = $dayNumber;

                                // 날짜 자동 계산
                                $dayDate = new DateTime($event['start_date']);
                                $dayDate->modify('+' . ($dayNumber - 1) . ' days');
                                $weekdays = ['일', '월', '화', '수', '목', '금', '토'];
                                $dateStr = $dayDate->format('y') . '년' . $dayDate->format('m') . '월' . $dayDate->format('d') . '일(' . $weekdays[(int)$dayDate->format('w')] . ')';

                                // 호텔 정보 결정
                                $hotelName = $dh['hotel_name'] ?: $dh['schedule_hotel_name'];
                                $hotelNameEn = $dh['hotel_name_en'] ?? '';
                                $starRating = (int)($dh['star_rating'] ?? 0);
                                $starStr = $starRating > 0 ? '(' . $starRating . '성급 ' . str_repeat('★', $starRating) . ')' : '';
                                $hasDetail = !empty($dh['h_id']);
                            ?>
                                <div class="day-section page-enter" style="animation-delay: <?= $idx * 0.05 ?>s;">
                                    <div class="day-header">
                                        <span class="day-num"><?= $dayNumber ?>일차</span>
                                        <span class="day-date"><?= $dateStr ?></span>
                                    </div>

                                    <div class="hotel-card" id="hotel-card-<?= $dayNumber ?>" onclick="toggleHotelCard(<?= $dayNumber ?>)">
                                        <div class="hotel-card-header">
                                            <div class="hotel-title-area">
                                                <div class="hotel-name-ko">
                                                    <?= h($hotelName) ?>
                                                    <?php if ($starStr): ?>
                                                        <span class="hotel-star"><?= $starStr ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($hotelNameEn): ?>
                                                    <div class="hotel-name-en">
                                                        <?= h($hotelNameEn) ?>
                                                        <?php if ($starRating > 0): ?>
                                                            (<?= $starRating ?>성급 <?= str_repeat('★', $starRating) ?>)
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="arrow">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="9 18 15 12 9 6"/>
                                                </svg>
                                            </div>
                                        </div>

                                        <?php if ($hasDetail): ?>
                                        <div class="hotel-card-detail" onclick="event.stopPropagation();">
                                            <?php if (!empty($dh['phone'])): ?>
                                                <div class="hotel-detail-item">
                                                    <div class="label">연락처</div>
                                                    <div class="value">Tel : <?= h($dh['phone']) ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($dh['address'])): ?>
                                                <div class="hotel-detail-item">
                                                    <div class="label">주소</div>
                                                    <div class="value"><?= h($dh['address']) ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($dh['check_in_date']) || !empty($dh['check_out_date'])): ?>
                                                <div class="hotel-detail-item">
                                                    <div class="label">체크인/체크아웃</div>
                                                    <div class="value">
                                                        <?php if ($dh['check_in_date']): ?>
                                                            체크인 <?= format_date_kr($dh['check_in_date']) ?><?= $dh['check_in_time'] ? ' ' . date('H:i', strtotime($dh['check_in_time'])) : '' ?>
                                                        <?php endif; ?>
                                                        <?php if ($dh['check_in_date'] && $dh['check_out_date']): ?><br><?php endif; ?>
                                                        <?php if ($dh['check_out_date']): ?>
                                                            체크아웃 <?= format_date_kr($dh['check_out_date']) ?><?= $dh['check_out_time'] ? ' ' . date('H:i', strtotime($dh['check_out_time'])) : '' ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="hotel-facilities-section">
                                                <div class="label">시설 및 서비스</div>
                                                <?php if (!empty($dh['facilities'])): ?>
                                                    <div class="hotel-facilities-list">
                                                        <?php foreach (array_filter(array_map('trim', explode(',', $dh['facilities']))) as $facility): ?>
                                                            <span class="hotel-facility-tag"><?= h($facility) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="hotel-facilities-empty">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <line x1="12" y1="8" x2="12" y2="12"/>
                                                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                                                        </svg>
                                                        정보가 없습니다
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($dh['amenities'])): ?>
                                                <div class="hotel-facilities-section">
                                                    <div class="label">부대시설</div>
                                                    <div style="font-size: 14px; color: var(--gray-700); line-height: 1.8;">
                                                        <?= nl2br(h($dh['amenities'])) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($dh['amenities_hours'])): ?>
                                                <div class="hotel-facilities-section">
                                                    <div class="label">부대시설 운영시간</div>
                                                    <div style="font-size: 14px; color: var(--gray-700); line-height: 1.8;">
                                                        <?= nl2br(h($dh['amenities_hours'])) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($dh['description'])): ?>
                                                <div class="hotel-detail-item">
                                                    <div class="label">호텔 소개</div>
                                                    <div class="value"><?= nl2br(h($dh['description'])) ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($dh['detail_url']) || !empty($dh['map_url']) || !empty($dh['phone'])): ?>
                                                <div class="hotel-actions">
                                                    <?php if (!empty($dh['detail_url'])): ?>
                                                        <a href="<?= h($dh['detail_url']) ?>" target="_blank" class="hotel-action-btn detail">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                                                <polyline points="15 3 21 3 21 9"/>
                                                                <line x1="10" y1="14" x2="21" y2="3"/>
                                                            </svg>
                                                            상세보기
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($dh['map_url'])): ?>
                                                        <a href="<?= h($dh['map_url']) ?>" target="_blank" class="hotel-action-btn map">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                                <circle cx="12" cy="10" r="3"/>
                                                            </svg>
                                                            지도
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($dh['phone'])): ?>
                                                        <a href="tel:<?= h($dh['phone']) ?>" class="hotel-action-btn call">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                                                            </svg>
                                                            전화
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php elseif (!empty($fallbackHotels)): ?>
                            <!-- 일정표 없이 호텔만 등록된 경우 -->
                            <?php foreach ($fallbackHotels as $idx => $hotel): ?>
                                <div class="day-section page-enter" style="animation-delay: <?= $idx * 0.05 ?>s;">
                                    <div class="hotel-card open">
                                        <div class="hotel-card-header" onclick="toggleHotelCard('fb-<?= $idx ?>')" >
                                            <div class="hotel-title-area">
                                                <div class="hotel-name-ko">
                                                    <?= h($hotel['hotel_name']) ?>
                                                    <?php if ($hotel['star_rating'] > 0): ?>
                                                        <span class="hotel-star">(<?= $hotel['star_rating'] ?>성급 <?= str_repeat('★', $hotel['star_rating']) ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($hotel['hotel_name_en'])): ?>
                                                    <div class="hotel-name-en"><?= h($hotel['hotel_name_en']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="arrow">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="9 18 15 12 9 6"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="hotel-card-detail" id="hotel-card-fb-<?= $idx ?>">
                                            <?php if (!empty($hotel['phone'])): ?>
                                                <div class="hotel-detail-item">
                                                    <div class="label">연락처</div>
                                                    <div class="value">Tel : <?= h($hotel['phone']) ?></div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($hotel['address'])): ?>
                                                <div class="hotel-detail-item">
                                                    <div class="label">주소</div>
                                                    <div class="value"><?= h($hotel['address']) ?></div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="hotel-facilities-section">
                                                <div class="label">시설 및 서비스</div>
                                                <?php if (!empty($hotel['facilities'])): ?>
                                                    <div class="hotel-facilities-list">
                                                        <?php foreach (array_filter(array_map('trim', explode(',', $hotel['facilities']))) as $facility): ?>
                                                            <span class="hotel-facility-tag"><?= h($facility) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="hotel-facilities-empty">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <line x1="12" y1="8" x2="12" y2="12"/>
                                                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                                                        </svg>
                                                        정보가 없습니다
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($hotel['amenities'])): ?>
                                                <div class="hotel-facilities-section">
                                                    <div class="label">부대시설</div>
                                                    <div style="font-size: 14px; color: var(--gray-700); line-height: 1.8;">
                                                        <?= nl2br(h($hotel['amenities'])) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($hotel['amenities_hours'])): ?>
                                                <div class="hotel-facilities-section">
                                                    <div class="label">부대시설 운영시간</div>
                                                    <div style="font-size: 14px; color: var(--gray-700); line-height: 1.8;">
                                                        <?= nl2br(h($hotel['amenities_hours'])) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <div class="hotel-empty page-enter">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M3 21h18M3 7v14M21 7v14M6 11h4v4H6zM14 11h4v4h-4zM6 3h12v4H6z"/>
                                </svg>
                                <p>호텔 정보가 아직 준비되지 않았습니다.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/user.js"></script>
    <script>
    function toggleHotelCard(id) {
        const card = document.getElementById('hotel-card-' + id) || event.currentTarget.closest('.hotel-card');
        card.classList.toggle('open');
    }
    </script>
</body>
</html>
