<?php
/**
 * 본투어 인터내셔날 - 선택관광 신청
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['optional_tour']) {
    redirect('/born/user/main.php');
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

// 신청된 선택관광 ID 목록
$selectedTourIds = [];
if ($eventMember && $eventMember['optional_tour_ids']) {
    $selectedTourIds = json_decode($eventMember['optional_tour_ids'], true) ?: [];
}

// 선택관광 목록
$stmt = $db->prepare("SELECT * FROM optional_tours WHERE status = 'active' ORDER BY id");
$stmt->execute();
$tours = $stmt->fetchAll();

$pageTitle = '선택관광 신청';
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
    <style>
        .tour-preview-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: 12px;
            color: var(--gray-700);
            cursor: pointer;
            margin-top: 8px;
        }
        .tour-preview-btn:hover {
            background: var(--gray-200);
        }
        .tour-preview-btn svg {
            width: 14px;
            height: 14px;
        }
        .auto-save-indicator {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gray-800);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }
        .auto-save-indicator.show {
            opacity: 1;
        }
        .auto-save-indicator svg {
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        /* Modal styles for mobile */
        .tour-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .tour-modal-backdrop.open {
            opacity: 1;
            visibility: visible;
        }
        .tour-modal {
            width: 100%;
            max-width: 420px;
            max-height: 85vh;
            background: white;
            border-radius: 20px 20px 0 0;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .tour-modal-backdrop.open .tour-modal {
            transform: translateY(0);
        }
        .tour-modal-handle {
            width: 40px;
            height: 4px;
            background: var(--gray-300);
            border-radius: 2px;
            margin: 12px auto;
        }
        .tour-modal-header {
            padding: 0 20px 16px;
            border-bottom: 1px solid var(--gray-200);
        }
        .tour-modal-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
        }
        .tour-modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }
        .tour-modal-section {
            margin-bottom: 20px;
        }
        .tour-modal-section h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 8px;
        }
        .tour-modal-section p {
            font-size: 15px;
            color: var(--gray-800);
            line-height: 1.6;
        }
        .tour-modal-info {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .tour-modal-info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: var(--gray-50);
            border-radius: var(--radius-md);
            font-size: 14px;
            color: var(--gray-700);
        }
        .tour-modal-info-item svg {
            width: 16px;
            height: 16px;
            color: var(--primary-600);
        }
        .tour-modal-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--gray-200);
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
                        <?php if (empty($tours)): ?>
                            <div class="empty-state page-enter">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                                <p>등록된 선택관광이 없습니다.</p>
                            </div>
                        <?php else: ?>
                            <!-- 안내 메시지 -->
                            <div class="info-banner page-enter">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                <p>원하시는 선택관광을 선택 후 저장해주세요.</p>
                            </div>

                            <!-- 선택관광 목록 -->
                            <form id="optionalTourForm" class="optional-tour-form">
                                <?php foreach ($tours as $index => $tour): ?>
                                    <div class="tour-card page-enter" style="animation-delay: <?= ($index + 1) * 0.1 ?>s;">
                                        <label class="tour-checkbox">
                                            <input type="checkbox" name="tour_ids[]" value="<?= $tour['id'] ?>"
                                                <?= in_array($tour['id'], $selectedTourIds) ? 'checked' : '' ?>>
                                            <span class="checkbox-custom">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                    <polyline points="20 6 9 17 4 12"/>
                                                </svg>
                                            </span>
                                        </label>

                                        <div class="tour-content">
                                            <h3 class="tour-name"><?= h($tour['tour_name']) ?></h3>

                                            <?php if ($tour['description']): ?>
                                                <p class="tour-description"><?= h($tour['description']) ?></p>
                                            <?php endif; ?>

                                            <div class="tour-meta">
                                                <?php if ($tour['duration']): ?>
                                                    <span class="tour-duration">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <polyline points="12 6 12 12 16 14"/>
                                                        </svg>
                                                        <?= h($tour['duration']) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($tour['price'] > 0): ?>
                                                    <span class="tour-price"><?= number_format($tour['price']) ?>원</span>
                                                <?php else: ?>
                                                    <span class="tour-price free">무료</span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- 살펴보기 버튼 -->
                                            <button type="button" class="tour-preview-btn" onclick="showTourPreview(<?= $tour['id'] ?>)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                                살펴보기
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- 투어 데이터 (JavaScript용) -->
                                <script type="application/json" id="toursData">
                                    <?= json_encode(array_map(function($t) {
                                        return [
                                            'id' => $t['id'],
                                            'name' => $t['tour_name'],
                                            'description' => $t['description'],
                                            'notice' => $t['notice'],
                                            'price' => $t['price'],
                                            'duration' => $t['duration'],
                                            'meeting_time' => $t['meeting_time'] ?? null
                                        ];
                                    }, $tours)) ?>
                                </script>

                                <!-- 선택 현황 및 저장 버튼 -->
                                <div class="tour-footer page-enter" style="animation-delay: <?= (count($tours) + 1) * 0.1 ?>s;">
                                    <div class="selected-count">
                                        <span id="selectedCount"><?= count($selectedTourIds) ?></span>개 선택됨
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                                        선택 저장하기
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 자동 저장 표시 -->
    <div id="autoSaveIndicator" class="auto-save-indicator">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
        <span>저장 중...</span>
    </div>

    <!-- 선택관광 상세 모달 -->
    <div id="tourModal" class="tour-modal-backdrop" onclick="closeTourModal(event)">
        <div class="tour-modal" onclick="event.stopPropagation()">
            <div class="tour-modal-handle"></div>
            <div class="tour-modal-header">
                <h3 id="tourModalTitle" class="tour-modal-title"></h3>
            </div>
            <div class="tour-modal-body">
                <div class="tour-modal-section">
                    <div id="tourModalInfo" class="tour-modal-info"></div>
                </div>
                <div id="tourModalDescription" class="tour-modal-section" style="display: none;">
                    <h4>상세 설명</h4>
                    <p id="tourModalDescriptionText"></p>
                </div>
                <div id="tourModalNotice" class="tour-modal-section" style="display: none;">
                    <h4>유의사항</h4>
                    <p id="tourModalNoticeText"></p>
                </div>
            </div>
            <div class="tour-modal-footer">
                <button type="button" class="btn btn-primary btn-block" onclick="closeTourModal()">닫기</button>
            </div>
        </div>
    </div>

    <script src="/born/assets/js/user.js"></script>
    <script>
        // 투어 데이터
        const toursData = JSON.parse(document.getElementById('toursData')?.textContent || '[]');
        let autoSaveTimeout = null;

        // 선택 개수 업데이트 및 자동 저장
        document.querySelectorAll('input[name="tour_ids[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateCount();
                autoSave();
            });
        });

        function updateCount() {
            const count = document.querySelectorAll('input[name="tour_ids[]"]:checked').length;
            document.getElementById('selectedCount').textContent = count;
        }

        // 자동 저장 기능
        function autoSave() {
            // 기존 타임아웃 취소
            if (autoSaveTimeout) {
                clearTimeout(autoSaveTimeout);
            }

            // 디바운스: 500ms 후 저장
            autoSaveTimeout = setTimeout(async () => {
                const indicator = document.getElementById('autoSaveIndicator');
                indicator.classList.add('show');

                try {
                    const tourIds = [];
                    document.querySelectorAll('input[name="tour_ids[]"]:checked').forEach(cb => {
                        tourIds.push(parseInt(cb.value));
                    });

                    const response = await fetch('/born/api/optional-tours.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'user_select',
                            tour_ids: tourIds
                        })
                    });

                    const result = await response.json();

                    setTimeout(() => {
                        indicator.classList.remove('show');
                        if (result.success) {
                            BornUser.toast('자동 저장되었습니다.', 'success');
                        }
                    }, 500);

                } catch (error) {
                    indicator.classList.remove('show');
                    BornUser.toast('저장에 실패했습니다.', 'error');
                }
            }, 500);
        }

        // 폼 제출 (수동 저장)
        document.getElementById('optionalTourForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            // 자동 저장 타임아웃 취소
            if (autoSaveTimeout) {
                clearTimeout(autoSaveTimeout);
            }

            const formData = new FormData(this);
            const tourIds = [];
            formData.getAll('tour_ids[]').forEach(id => tourIds.push(parseInt(id)));

            try {
                const response = await fetch('/born/api/optional-tours.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'user_select',
                        tour_ids: tourIds
                    })
                });

                const result = await response.json();

                if (result.success) {
                    BornUser.toast(result.message || '저장되었습니다.', 'success');
                } else {
                    BornUser.toast(result.error || '저장에 실패했습니다.', 'error');
                }
            } catch (error) {
                BornUser.toast('오류가 발생했습니다.', 'error');
            }
        });

        // 선택관광 살펴보기 모달
        function showTourPreview(tourId) {
            const tour = toursData.find(t => t.id === tourId);
            if (!tour) return;

            document.getElementById('tourModalTitle').textContent = tour.name;

            // 정보 표시
            let infoHtml = '';
            if (tour.duration) {
                infoHtml += `
                    <div class="tour-modal-info-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        ${escapeHtml(tour.duration)}
                    </div>
                `;
            }
            if (tour.meeting_time) {
                infoHtml += `
                    <div class="tour-modal-info-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        미팅 ${tour.meeting_time}
                    </div>
                `;
            }
            if (tour.price > 0) {
                infoHtml += `
                    <div class="tour-modal-info-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        ${Number(tour.price).toLocaleString()}원
                    </div>
                `;
            } else {
                infoHtml += `
                    <div class="tour-modal-info-item" style="background: var(--success-light); color: var(--success);">
                        무료
                    </div>
                `;
            }
            document.getElementById('tourModalInfo').innerHTML = infoHtml;

            // 설명
            const descSection = document.getElementById('tourModalDescription');
            if (tour.description) {
                document.getElementById('tourModalDescriptionText').innerHTML = escapeHtml(tour.description).replace(/\n/g, '<br>');
                descSection.style.display = 'block';
            } else {
                descSection.style.display = 'none';
            }

            // 유의사항
            const noticeSection = document.getElementById('tourModalNotice');
            if (tour.notice) {
                document.getElementById('tourModalNoticeText').innerHTML = escapeHtml(tour.notice).replace(/\n/g, '<br>');
                noticeSection.style.display = 'block';
            } else {
                noticeSection.style.display = 'none';
            }

            document.getElementById('tourModal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeTourModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('tourModal').classList.remove('open');
            document.body.style.overflow = '';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
