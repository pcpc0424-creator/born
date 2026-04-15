<?php
/**
 * 본투어 인터내셔날 - 공항 미팅
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['meeting']) {
    redirect('/user/main.php');
}

$db = db();
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$user['event_id']]);
$event = $stmt->fetch();

$pageTitle = '공항 미팅';
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
                        <!-- 미팅 정보 카드 -->
                        <div class="meeting-card page-enter">
                            <div class="meeting-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </div>
                            <h2>인천국제공항</h2>
                            <p class="meeting-subtitle">출국장 미팅</p>
                        </div>

                        <!-- 미팅 상세 정보 -->
                        <div class="info-card page-enter" style="animation-delay: 0.1s;">
                            <div class="info-card-body">
                                <div class="meeting-detail-list">
                                    <div class="meeting-detail-item">
                                        <div class="detail-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                                <line x1="16" y1="2" x2="16" y2="6"/>
                                                <line x1="8" y1="2" x2="8" y2="6"/>
                                                <line x1="3" y1="10" x2="21" y2="10"/>
                                            </svg>
                                        </div>
                                        <div class="detail-content">
                                            <label>미팅 날짜</label>
                                            <span><?= $event['meeting_date'] ? format_date_kr($event['meeting_date']) : format_date_kr($event['start_date']) ?></span>
                                        </div>
                                    </div>

                                    <div class="meeting-detail-item">
                                        <div class="detail-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                        </div>
                                        <div class="detail-content">
                                            <label>미팅 시간</label>
                                            <span><?= $event['meeting_time'] ? date('H:i', strtotime($event['meeting_time'])) : '-' ?></span>
                                        </div>
                                    </div>

                                    <div class="meeting-detail-item">
                                        <div class="detail-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                        </div>
                                        <div class="detail-content">
                                            <label>미팅 장소</label>
                                            <span><?= h($event['meeting_place'] ?: '인천국제공항 제2터미널 3층 출국장') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 인솔자 정보 -->
                        <div class="info-card page-enter" style="animation-delay: 0.2s;">
                            <div class="info-card-header">
                                <h3>인솔자 정보</h3>
                            </div>
                            <div class="info-card-body">
                                <div class="manager-info">
                                    <div class="manager-avatar">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                    </div>
                                    <div class="manager-details">
                                        <h4><?= h($event['meeting_manager'] ?: '담당자') ?></h4>
                                        <p>본투어 인터내셔날</p>
                                    </div>
                                </div>

                                <?php if ($event['manager_phone']): ?>
                                    <div style="margin-top: 16px; display: flex; align-items: center; gap: 8px; padding: 12px 16px; background: var(--primary-50); border-radius: var(--radius-md);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--primary-600)" stroke-width="2" style="width: 18px; height: 18px; flex-shrink: 0;">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                        </svg>
                                        <span style="font-size: 15px; font-weight: 600; color: var(--gray-800);"><?= h($event['manager_phone']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 안내사항 -->
                        <?php
                        $meetingNotice = $event['meeting_notice'] ?? '';
                        if (empty($meetingNotice)) {
                            // 기본 안내사항
                            $defaultNotices = [
                                '미팅 시간 10분 전까지 미팅 장소에 도착해 주세요.',
                                '여권과 항공권(e-ticket)을 반드시 지참해 주세요.',
                                '미팅 장소를 찾기 어려우시면 인솔자에게 연락 주세요.',
                                '본투어 인터내셔날 피켓을 들고 있는 인솔자를 찾아주세요.',
                            ];
                        } else {
                            $defaultNotices = array_filter(array_map('trim', explode("\n", $meetingNotice)));
                        }
                        ?>
                        <div class="info-card page-enter" style="animation-delay: 0.3s;">
                            <div class="info-card-header">
                                <h3>안내사항</h3>
                            </div>
                            <div class="info-card-body">
                                <ul class="notice-list">
                                    <?php foreach ($defaultNotices as $notice): ?>
                                        <li><?= h($notice) ?></li>
                                    <?php endforeach; ?>
                                </ul>
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
