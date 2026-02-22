<?php
/**
 * 본투어 인터내셔날 - 여행 전 유의사항
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['notice']) {
    redirect('/born/user/main.php');
}

$db = db();
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$user['event_id']]);
$event = $stmt->fetch();

$pageTitle = '여행 전 유의사항';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6dc5d1">
    <title><?= $pageTitle ?> - 본투어</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <link rel="stylesheet" href="/born/assets/css/animations.css">
    <link rel="stylesheet" href="/born/assets/css/user.css">
    <link rel="stylesheet" href="/born/assets/css/user-pc.css">
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
                        <!-- 메인 유의사항 -->
                        <?php if ($event['travel_notice']): ?>
                            <div class="info-card page-enter">
                                <div class="info-card-header">
                                    <h3>여행 전 확인사항</h3>
                                </div>
                                <div class="info-card-body">
                                    <div class="notice-content">
                                        <?= nl2br(h($event['travel_notice'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 기본 유의사항 -->
                        <div class="info-card page-enter" style="animation-delay: 0.1s;">
                            <div class="info-card-header">
                                <h3>출국 준비물</h3>
                            </div>
                            <div class="info-card-body">
                                <ul class="checklist">
                                    <li>
                                        <span class="check-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </span>
                                        <span>여권 (유효기간 6개월 이상)</span>
                                    </li>
                                    <li>
                                        <span class="check-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </span>
                                        <span>항공권 (e-ticket)</span>
                                    </li>
                                    <li>
                                        <span class="check-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </span>
                                        <span>여행자보험 가입증명서</span>
                                    </li>
                                    <li>
                                        <span class="check-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </span>
                                        <span>현지 통화 또는 신용카드</span>
                                    </li>
                                    <li>
                                        <span class="check-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </span>
                                        <span>상비약 (개인 복용약)</span>
                                    </li>
                                    <li>
                                        <span class="check-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </span>
                                        <span>휴대폰 충전기 및 어댑터</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- 기내 반입 금지 물품 -->
                        <div class="info-card page-enter" style="animation-delay: 0.2s;">
                            <div class="info-card-header">
                                <h3>기내 반입 금지 물품</h3>
                            </div>
                            <div class="info-card-body">
                                <ul class="warning-list">
                                    <li>액체류 100ml 초과 (기내 반입 불가)</li>
                                    <li>라이터, 성냥 (1인 1개만 소지 가능)</li>
                                    <li>날카로운 물품 (칼, 가위 등)</li>
                                    <li>보조배터리 160Wh 초과</li>
                                </ul>
                                <p class="warning-note">
                                    * 액체류는 100ml 이하 용기에 담아 1L 투명 비닐백에 보관하세요.
                                </p>
                            </div>
                        </div>

                        <!-- 수하물 규정 -->
                        <?php if ($event['baggage_info']): ?>
                            <div class="info-card page-enter" style="animation-delay: 0.3s;">
                                <div class="info-card-header">
                                    <h3>수하물 규정</h3>
                                </div>
                                <div class="info-card-body">
                                    <div class="notice-content">
                                        <?= nl2br(h($event['baggage_info'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 비상 연락처 -->
                        <div class="info-card page-enter" style="animation-delay: 0.4s;">
                            <div class="info-card-header">
                                <h3>비상 연락처</h3>
                            </div>
                            <div class="info-card-body">
                                <div class="emergency-contacts">
                                    <div class="emergency-item">
                                        <span class="emergency-label">본투어 대표전화</span>
                                        <a href="tel:<?= COMPANY_PHONE ?>" class="emergency-number"><?= COMPANY_PHONE ?></a>
                                    </div>
                                    <?php if ($event['manager_phone']): ?>
                                        <div class="emergency-item">
                                            <span class="emergency-label">인솔자 연락처</span>
                                            <a href="tel:<?= h($event['manager_phone']) ?>" class="emergency-number"><?= h($event['manager_phone']) ?></a>
                                        </div>
                                    <?php endif; ?>
                                    <div class="emergency-item">
                                        <span class="emergency-label">외교부 영사콜센터</span>
                                        <a href="tel:+82-2-3210-0404" class="emergency-number">+82-2-3210-0404</a>
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

    <script src="/born/assets/js/user.js"></script>
</body>
</html>
