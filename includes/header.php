<?php
/**
 * 본투어 인터내셔날 - 관리자 헤더
 */

require_once __DIR__ . '/auth.php';
require_admin_auth();

$currentAdmin = get_current_admin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' - ' : '' ?>본투어 인터내셔날 관리자</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <link rel="stylesheet" href="/born/assets/css/animations.css">
    <link rel="stylesheet" href="/born/assets/css/admin.css">
    <?php if (isset($additionalCss)): ?>
        <?php foreach ($additionalCss as $css): ?>
            <link rel="stylesheet" href="<?= h($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="admin-layout">
        <!-- 사이드바 -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="/born/assets/images/logo/logo.png" alt="본투어" onerror="this.style.display='none'" style="filter: brightness(0) invert(1);">
                    <h1>본투어</h1>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">행사 관리</div>
                    <a href="/born/admin/index.php" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        <span>대시보드</span>
                    </a>
                    <a href="/born/admin/event-editor.php" class="nav-item <?= $currentPage === 'event-editor' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>행사 에디터</span>
                    </a>
                    <a href="/born/admin/event-member.php" class="nav-item <?= $currentPage === 'event-member' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span>행사-개인 에디터</span>
                    </a>
                    <a href="/born/admin/optional-tour.php" class="nav-item <?= $currentPage === 'optional-tour' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polygon points="10 8 16 12 10 16 10 8"/>
                        </svg>
                        <span>선택관광 에디터</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">콘텐츠 관리</div>
                    <a href="/born/admin/notice-editor.php" class="nav-item <?= $currentPage === 'notice-editor' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <span>공지/문의 에디터</span>
                    </a>
                    <a href="/born/admin/survey-editor.php" class="nav-item <?= $currentPage === 'survey-editor' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        <span>설문 에디터</span>
                    </a>
                    <a href="/born/admin/survey-stats.php" class="nav-item <?= $currentPage === 'survey-stats' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                        <span>설문 통계</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">회원 & 보안</div>
                    <a href="/born/admin/members.php" class="nav-item <?= $currentPage === 'members' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>회원 관리</span>
                    </a>
                    <a href="/born/admin/passport.php" class="nav-item <?= $currentPage === 'passport' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <circle cx="12" cy="10" r="3"/>
                            <line x1="7" y1="16" x2="17" y2="16"/>
                        </svg>
                        <span>여권사본 확인</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">설정</div>
                    <a href="/born/admin/page-visibility.php" class="nav-item <?= $currentPage === 'page-visibility' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <span>페이지 노출 관리</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- 메인 영역 -->
        <main class="admin-main">
            <!-- 헤더 -->
            <header class="admin-header">
                <h2 class="header-title"><?= isset($pageTitle) ? h($pageTitle) : '대시보드' ?></h2>

                <div class="header-actions">
                    <div class="user-menu" onclick="document.getElementById('logout-form').submit()">
                        <div class="user-avatar"><?= strtoupper(substr($currentAdmin['username'], 0, 1)) ?></div>
                        <span><?= h($currentAdmin['username']) ?></span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </div>
                    <form id="logout-form" action="/born/api/auth.php" method="POST" style="display:none;">
                        <input type="hidden" name="action" value="admin_logout">
                    </form>
                </div>
            </header>

            <!-- 콘텐츠 -->
            <div class="admin-content">
