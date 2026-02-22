<?php
/**
 * 본투어 인터내셔날 - 문의하기
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['faq']) {
    redirect('/born/user/main.php');
}

$db = db();

// FAQ 조회
$stmt = $db->prepare("SELECT * FROM notices WHERE category = 'faq' ORDER BY sort_order ASC, created_at DESC");
$stmt->execute();
$faqs = $stmt->fetchAll();

// 행사 정보 (인솔자 연락처)
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$user['event_id']]);
$event = $stmt->fetch();

$pageTitle = '문의하기';
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
                        <!-- 자주 찾는 질문 -->
                        <div class="faq-section page-enter">
                            <h3 class="faq-section-title">자주 찾는 질문</h3>

                            <?php if (!empty($faqs)): ?>
                                <div class="faq-list">
                                    <?php foreach ($faqs as $index => $faq): ?>
                                        <div class="faq-item">
                                            <button type="button" class="faq-question-btn" onclick="this.closest('.faq-item').classList.toggle('open')">
                                                <span class="faq-question-text"><?= h($faq['title']) ?></span>
                                                <svg class="faq-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="6 9 12 15 18 9"/>
                                                </svg>
                                            </button>
                                            <div class="faq-answer-wrap">
                                                <div class="faq-answer-text">
                                                    <?= nl2br(h($faq['content'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                    <h3>등록된 질문이 없습니다</h3>
                                    <p>궁금한 사항은 카카오톡이나 전화로 문의해주세요.</p>
                                </div>
                            <?php endif; ?>
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
