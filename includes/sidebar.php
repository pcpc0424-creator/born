<?php
/**
 * 본투어 인터내셔날 - 사용자 사이드바
 */

$currentUser = get_logged_in_user();
$visibility = get_page_visibility($currentUser['event_id']);
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- 사이드바 오버레이 -->
<div class="sidebar-overlay"></div>

<!-- 사이드바 메뉴 -->
<nav class="sidebar-menu">
    <div class="sidebar-header">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= mb_substr($currentUser['name'], 0, 1) ?></div>
            <div class="sidebar-user-info">
                <h4><?= h($currentUser['name']) ?></h4>
                <span><?= h($currentUser['event_name']) ?></span>
            </div>
        </div>
        <div class="sidebar-close" onclick="BornUser.closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </div>
    </div>

    <div class="sidebar-nav">
        <?php if ($currentPage !== 'main'): ?>
        <a href="/user/main.php" class="sidebar-nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>메인</span>
        </a>
        <?php endif; ?>

        <?php if ($visibility['schedule']): ?>
        <a href="/user/schedule.php" class="sidebar-nav-item <?= $currentPage === 'schedule' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span>일정표</span>
        </a>
        <?php endif; ?>

        <?php if ($visibility['flight']): ?>
        <a href="/user/flight.php" class="sidebar-nav-item <?= $currentPage === 'flight' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 2L11 13"/>
                <path d="M22 2l-7 20-4-9-9-4 20-7z"/>
            </svg>
            <span>항공 스케줄</span>
        </a>
        <?php endif; ?>

        <?php if ($visibility['meeting']): ?>
        <a href="/user/meeting.php" class="sidebar-nav-item <?= $currentPage === 'meeting' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            <span>공항 미팅</span>
        </a>
        <?php endif; ?>

        <?php if ($visibility['hotel']): ?>
        <a href="/user/hotel.php" class="sidebar-nav-item <?= $currentPage === 'hotel' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 21h18M3 7v14M21 7v14M6 11h4v4H6zM14 11h4v4h-4zM6 3h12v4H6z"/>
            </svg>
            <span>호텔 정보</span>
        </a>
        <?php endif; ?>

        <?php if ($visibility['travel_notice']): ?>
        <a href="/user/notice.php" class="sidebar-nav-item <?= $currentPage === 'notice' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <span>유의사항</span>
        </a>
        <?php endif; ?>

        <?php if ($visibility['reservation']): ?>
        <a href="/user/reservation.php" class="sidebar-nav-item <?= $currentPage === 'reservation' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span>예약 상세</span>
        </a>
        <?php endif; ?>

        <div class="sidebar-divider"></div>

        <?php if ($visibility['passport_upload']): ?>
        <a href="/user/passport-upload.php" class="sidebar-nav-item <?= $currentPage === 'passport-upload' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="16" rx="2"/>
                <circle cx="12" cy="10" r="3"/>
                <line x1="7" y1="16" x2="17" y2="16"/>
            </svg>
            <span>여권 업로드</span>
        </a>
        <?php endif; ?>

        <?php if ($visibility['optional_tour']): ?>
        <a href="/user/optional-tour.php" class="sidebar-nav-item <?= $currentPage === 'optional-tour' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polygon points="10 8 16 12 10 16 10 8"/>
            </svg>
            <span>선택관광</span>
        </a>
        <?php endif; ?>

        <?php if ($visibility['survey']): ?>
        <a href="/user/survey.php" class="sidebar-nav-item <?= $currentPage === 'survey' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            <span>설문</span>
        </a>
        <?php endif; ?>

        <div class="sidebar-divider"></div>

        <?php if ($visibility['announcements']): ?>
        <a href="/user/announcements.php" class="sidebar-nav-item <?= $currentPage === 'announcements' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span>공지사항</span>
        </a>
        <?php endif; ?>

        <?php if ($visibility['faq']): ?>
        <a href="/user/faq.php" class="sidebar-nav-item <?= $currentPage === 'faq' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span>문의하기</span>
        </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <a href="/api/auth.php?action=user_logout" class="sidebar-logout">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>로그아웃</span>
        </a>
    </div>
</nav>

<!-- 플로팅 문의 버튼 -->
<div class="floating-contact-buttons">
<a href="javascript:void(0)" class="floating-btn phone" onclick="BornUser.openPhoneContact()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
        </svg>
        <span class="floating-label">전화문의</span>
    </a>
</div>

<!-- PC용 연락처 모달 -->
<div class="contact-modal-overlay" id="contactModal" onclick="BornUser.closeContactModal()">
    <div class="contact-modal" onclick="event.stopPropagation()">
        <button class="contact-modal-close" onclick="BornUser.closeContactModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
        <div class="contact-modal-content" id="contactModalContent">
            <!-- 동적으로 내용 채워짐 -->
        </div>
    </div>
</div>

<!-- 연락처 정보 (JavaScript용) -->
<script>
    window.BORN_CONTACT = {
        phone: '<?= COMPANY_PHONE ?>',
        kakao: '<?= COMPANY_KAKAO ?>',
        kakaoUrl: '<?= KAKAO_CHANNEL_URL ?>'
    };
</script>
