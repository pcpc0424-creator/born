<?php
/**
 * 본투어 인터내셔날 - 설문 참여
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['survey']) {
    redirect('/user/main.php');
}

$db = db();

// 해당 행사의 활성화된 설문 조회
$stmt = $db->prepare("SELECT * FROM surveys WHERE event_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
$stmt->execute([$user['event_id']]);
$survey = $stmt->fetch();

if (!$survey) {
    $pageTitle = '설문';
} else {
    $pageTitle = $survey['title'];

    // 이미 완료했는지 확인
    $stmt = $db->prepare("SELECT id FROM survey_completions WHERE survey_id = ? AND member_id = ?");
    $stmt->execute([$survey['id'], $user['id']]);
    $completed = $stmt->fetch();

    // 설문 페이지 및 질문 조회
    $stmt = $db->prepare("
        SELECT sp.id as page_id, sp.page_order,
               sq.id as question_id, sq.question_type, sq.question_text, sq.options, sq.question_order
        FROM survey_pages sp
        LEFT JOIN survey_questions sq ON sp.id = sq.page_id
        WHERE sp.survey_id = ?
        ORDER BY sp.page_order, sq.question_order
    ");
    $stmt->execute([$survey['id']]);
    $rows = $stmt->fetchAll();

    // 페이지별로 질문 정리
    $pages = [];
    foreach ($rows as $row) {
        $pageId = $row['page_id'];
        if (!isset($pages[$pageId])) {
            $pages[$pageId] = [
                'page_order' => $row['page_order'],
                'questions' => []
            ];
        }
        if ($row['question_id']) {
            $pages[$pageId]['questions'][] = [
                'id' => $row['question_id'],
                'type' => $row['question_type'],
                'text' => $row['question_text'],
                'options' => $row['options'] ? json_decode($row['options'], true) : []
            ];
        }
    }
    $pages = array_values($pages);

    // 임시저장 데이터 조회
    $stmt = $db->prepare("SELECT draft_data FROM survey_drafts WHERE survey_id = ? AND member_id = ?");
    $stmt->execute([$survey['id'], $user['id']]);
    $draft = $stmt->fetch();
    $draftData = $draft ? json_decode($draft['draft_data'], true) : [];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6dc5d1">
    <title><?= h($pageTitle) ?> - 본투어</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="stylesheet" href="/assets/css/user.css">
    <link rel="stylesheet" href="/assets/css/user-pc.css">
    <style>
        /* 텍스트 확장 모달 */
        .text-expand-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            padding: 20px;
        }
        .text-expand-modal.open {
            opacity: 1;
            visibility: visible;
        }
        .text-expand-content {
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            background: white;
            border-radius: var(--radius-lg);
            display: flex;
            flex-direction: column;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        .text-expand-modal.open .text-expand-content {
            transform: scale(1);
        }
        .text-expand-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .text-expand-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }
        .text-expand-close {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--gray-500);
        }
        .text-expand-body {
            padding: 20px;
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .text-expand-textarea {
            width: 100%;
            flex: 1;
            min-height: 200px;
            max-height: 50vh;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            padding: 12px;
            font-size: 15px;
            line-height: 1.6;
            resize: none;
        }
        .text-expand-textarea:focus {
            outline: none;
            border-color: var(--primary-500);
        }
        .text-expand-counter {
            margin-top: 8px;
            font-size: 12px;
            color: var(--gray-500);
            text-align: right;
        }
        .text-expand-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--gray-200);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        /* 확장 버튼 */
        .expand-btn {
            position: absolute;
            right: 8px;
            bottom: 8px;
            padding: 4px 8px;
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-sm);
            font-size: 11px;
            color: var(--gray-600);
            cursor: pointer;
            display: none;
        }
        .expand-btn:hover {
            background: var(--gray-200);
        }
        .textarea-wrapper {
            position: relative;
        }
        .textarea-wrapper.has-content .expand-btn {
            display: block;
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
                        <h1 class="header-title"><?= h($pageTitle) ?></h1>
                        <div class="header-menu" onclick="BornUser.openSidebar()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="3" y1="12" x2="21" y2="12"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <line x1="3" y1="18" x2="21" y2="18"/>
                            </svg>
                        </div>
                    </header>

                    <div class="user-content">
                        <?php if (!$survey): ?>
                            <div class="empty-state page-enter">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                <p>등록된 설문조사가 없습니다.</p>
                                <p class="sub" style="font-size: 13px; color: var(--gray-400); margin-top: 4px;">설문조사가 등록되면 이곳에서 참여할 수 있습니다.</p>
                            </div>
                        <?php elseif ($completed): ?>
                            <div class="completion-state page-enter">
                                <div class="completion-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                </div>
                                <h2>설문 참여 완료</h2>
                                <p>설문에 참여해 주셔서 감사합니다.</p>
                                <a href="/user/main.php" class="btn btn-primary">메인으로 돌아가기</a>
                            </div>
                        <?php else: ?>
                            <!-- 설문 진행 -->
                            <form id="surveyForm" class="survey-form">
                                <input type="hidden" name="survey_id" value="<?= $survey['id'] ?>">

                                <!-- 페이지 인디케이터 -->
                                <?php if (count($pages) > 1): ?>
                                    <div class="survey-progress page-enter">
                                        <div class="progress-bar">
                                            <div class="progress-fill" id="progressFill" style="width: <?= 100 / count($pages) ?>%"></div>
                                        </div>
                                        <span class="progress-text"><span id="currentPage">1</span> / <?= count($pages) ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- 설문 페이지들 -->
                                <?php foreach ($pages as $pageIndex => $page): ?>
                                    <div class="survey-page <?= $pageIndex === 0 ? 'active' : '' ?>" data-page="<?= $pageIndex ?>">
                                        <?php foreach ($page['questions'] as $qIndex => $question): ?>
                                            <div class="question-card page-enter" style="animation-delay: <?= $qIndex * 0.1 ?>s;">
                                                <div class="question-number">Q<?= $qIndex + 1 ?></div>
                                                <div class="question-text"><?= h($question['text']) ?></div>

                                                <?php if ($question['type'] === 'multiple'): ?>
                                                    <!-- 객관식 -->
                                                    <div class="question-options">
                                                        <?php foreach ($question['options'] as $optIndex => $option): ?>
                                                            <label class="option-item">
                                                                <input type="radio"
                                                                       name="answers[<?= $question['id'] ?>]"
                                                                       value="<?= h($option) ?>"
                                                                       <?= ($draftData[$question['id']] ?? '') === $option ? 'checked' : '' ?>>
                                                                <span class="option-radio"></span>
                                                                <span class="option-text"><?= h($option) ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>

                                                <?php elseif ($question['type'] === 'short'): ?>
                                                    <!-- 단답형 -->
                                                    <input type="text"
                                                           name="answers[<?= $question['id'] ?>]"
                                                           class="form-input"
                                                           placeholder="답변을 입력하세요"
                                                           value="<?= h($draftData[$question['id']] ?? '') ?>">

                                                <?php else: ?>
                                                    <!-- 서술형 -->
                                                    <div class="textarea-wrapper <?= !empty($draftData[$question['id']]) ? 'has-content' : '' ?>">
                                                        <textarea name="answers[<?= $question['id'] ?>]"
                                                                  class="form-textarea expandable-textarea"
                                                                  rows="4"
                                                                  data-question-text="<?= h($question['text']) ?>"
                                                                  placeholder="답변을 입력하세요"><?= h($draftData[$question['id']] ?? '') ?></textarea>
                                                        <button type="button" class="expand-btn" onclick="openExpandModal(this.previousElementSibling)">
                                                            확장 입력
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>

                                <!-- 네비게이션 버튼 -->
                                <div class="survey-nav page-enter" style="animation-delay: 0.3s;">
                                    <?php if (count($pages) > 1): ?>
                                        <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
                                            이전
                                        </button>
                                        <button type="button" class="btn btn-primary" id="nextBtn">
                                            다음
                                        </button>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" <?= count($pages) > 1 ? 'style="display: none;"' : '' ?>>
                                        제출하기
                                    </button>
                                </div>

                                <!-- 자동저장 표시 -->
                                <div class="autosave-indicator" id="autosaveIndicator">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    <span>자동 저장됨</span>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 텍스트 확장 모달 -->
    <div id="textExpandModal" class="text-expand-modal" onclick="closeExpandModal(event)">
        <div class="text-expand-content" onclick="event.stopPropagation()">
            <div class="text-expand-header">
                <h3 id="expandModalTitle">답변 입력</h3>
                <div class="text-expand-close" onclick="closeExpandModal()">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </div>
            </div>
            <div class="text-expand-body">
                <textarea id="expandTextarea" class="text-expand-textarea" placeholder="답변을 입력하세요"></textarea>
                <div class="text-expand-counter">
                    <span id="expandCharCount">0</span>자 입력
                </div>
            </div>
            <div class="text-expand-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExpandModal()">취소</button>
                <button type="button" class="btn btn-primary" onclick="applyExpandText()">적용</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/user.js"></script>
    <script>
        // 텍스트 확장 모달 관련
        let currentExpandTextarea = null;
        const EXPAND_THRESHOLD = 50; // 50자 이상 입력 시 확장 버튼 표시

        function openExpandModal(textarea) {
            currentExpandTextarea = textarea;
            const modal = document.getElementById('textExpandModal');
            const expandTextarea = document.getElementById('expandTextarea');
            const title = textarea.dataset.questionText || '답변 입력';

            document.getElementById('expandModalTitle').textContent = title;
            expandTextarea.value = textarea.value;
            updateCharCount();

            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
            expandTextarea.focus();
        }

        function closeExpandModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('textExpandModal').classList.remove('open');
            document.body.style.overflow = '';
            currentExpandTextarea = null;
        }

        function applyExpandText() {
            if (currentExpandTextarea) {
                currentExpandTextarea.value = document.getElementById('expandTextarea').value;
                // wrapper에 has-content 클래스 토글
                const wrapper = currentExpandTextarea.closest('.textarea-wrapper');
                if (wrapper) {
                    wrapper.classList.toggle('has-content', currentExpandTextarea.value.length > 0);
                }
                // 자동저장 트리거
                currentExpandTextarea.dispatchEvent(new Event('input', { bubbles: true }));
            }
            closeExpandModal();
        }

        function updateCharCount() {
            const count = document.getElementById('expandTextarea').value.length;
            document.getElementById('expandCharCount').textContent = count;
        }

        document.getElementById('expandTextarea')?.addEventListener('input', updateCharCount);

        // 서술형 textarea 입력 시 확장 버튼 표시
        document.querySelectorAll('.expandable-textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                const wrapper = this.closest('.textarea-wrapper');
                if (wrapper) {
                    wrapper.classList.toggle('has-content', this.value.length >= EXPAND_THRESHOLD);
                }
            });
        });
    </script>

    <?php if ($survey && !$completed): ?>
    <script>
        const totalPages = <?= count($pages) ?>;
        let currentPage = 0;

        // 페이지 네비게이션
        document.getElementById('nextBtn')?.addEventListener('click', () => {
            if (currentPage < totalPages - 1) {
                showPage(currentPage + 1);
            }
        });

        document.getElementById('prevBtn')?.addEventListener('click', () => {
            if (currentPage > 0) {
                showPage(currentPage - 1);
            }
        });

        function showPage(pageIndex) {
            document.querySelectorAll('.survey-page').forEach((page, index) => {
                page.classList.toggle('active', index === pageIndex);
            });

            currentPage = pageIndex;

            // 버튼 표시 상태 업데이트
            if (totalPages > 1) {
                document.getElementById('prevBtn').style.display = currentPage > 0 ? 'inline-flex' : 'none';
                document.getElementById('nextBtn').style.display = currentPage < totalPages - 1 ? 'inline-flex' : 'none';
                document.getElementById('submitBtn').style.display = currentPage === totalPages - 1 ? 'inline-flex' : 'none';

                // 프로그레스 업데이트
                document.getElementById('currentPage').textContent = currentPage + 1;
                document.getElementById('progressFill').style.width = ((currentPage + 1) / totalPages * 100) + '%';
            }

            // 스크롤 상단으로
            document.querySelector('.user-content').scrollTop = 0;
        }

        // 자동저장
        let autoSaveTimeout;
        document.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(autoSave, 2000);
            });
        });

        async function autoSave() {
            const formData = new FormData(document.getElementById('surveyForm'));
            const answers = {};
            for (const [key, value] of formData.entries()) {
                const match = key.match(/answers\[(\d+)\]/);
                if (match) {
                    answers[match[1]] = value;
                }
            }

            try {
                await fetch('/api/surveys.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_draft',
                        survey_id: <?= $survey['id'] ?>,
                        answers: answers
                    })
                });

                // 자동저장 인디케이터 표시
                const indicator = document.getElementById('autosaveIndicator');
                indicator.classList.add('show');
                setTimeout(() => indicator.classList.remove('show'), 2000);
            } catch (error) {
                console.error('Auto save failed:', error);
            }
        }

        // 폼 제출
        document.getElementById('surveyForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const answers = {};
            for (const [key, value] of formData.entries()) {
                const match = key.match(/answers\[(\d+)\]/);
                if (match) {
                    answers[match[1]] = value;
                }
            }

            try {
                const response = await fetch('/api/surveys.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'submit',
                        survey_id: <?= $survey['id'] ?>,
                        answers: answers
                    })
                });

                const result = await response.json();

                if (result.success) {
                    BornUser.toast('설문이 제출되었습니다.', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    BornUser.toast(result.error || '제출에 실패했습니다.', 'error');
                }
            } catch (error) {
                BornUser.toast('오류가 발생했습니다.', 'error');
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
