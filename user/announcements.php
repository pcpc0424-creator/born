<?php
/**
 * 본투어 인터내셔날 - 공지사항
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['announcements']) {
    redirect('/born/user/main.php');
}

$db = db();

// 공지사항 조회
$stmt = $db->prepare("SELECT * FROM notices WHERE category = 'notice' ORDER BY sort_order ASC, created_at DESC");
$stmt->execute();
$notices = $stmt->fetchAll();

$pageTitle = '공지사항';
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
                        <?php if (empty($notices)): ?>
                            <div class="empty-state page-enter">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                <p>등록된 공지사항이 없습니다.</p>
                            </div>
                        <?php else: ?>
                            <!-- 공지사항 아코디언 -->
                            <div class="accordion-list page-enter">
                                <?php foreach ($notices as $index => $notice): ?>
                                    <div class="accordion-item" style="animation-delay: <?= $index * 0.05 ?>s;">
                                        <button type="button" class="accordion-header" onclick="this.closest('.accordion-item').classList.toggle('open')">
                                            <div class="accordion-title">
                                                <span class="notice-badge">공지</span>
                                                <span class="notice-title"><?= h($notice['title']) ?></span>
                                            </div>
                                            <svg class="accordion-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="6 9 12 15 18 9"/>
                                            </svg>
                                        </button>
                                        <div class="accordion-content">
                                            <div class="accordion-body">
                                                <div class="notice-content">
                                                    <?= nl2br(h($notice['content'])) ?>
                                                </div>
                                                <div class="notice-date">
                                                    <?= date('Y.m.d', strtotime($notice['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/born/assets/js/user.js"></script>
    <script>
    document.querySelectorAll('.accordion-header').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const item = this.closest('.accordion-item');
            if (item) {
                item.classList.toggle('open');
            }
        });
    });
    </script>
</body>
</html>
