<?php
/**
 * 본투어 인터내셔날 - 여행 후기 설문 에디터
 */

$pageTitle = '여행 후기 설문 에디터';
require_once __DIR__ . '/../includes/header.php';

$db = db();

// 설문 목록
$stmt = $db->query("
    SELECT s.*,
           (SELECT COUNT(DISTINCT member_id) FROM survey_completions WHERE survey_id = s.id) as response_count
    FROM surveys s
    ORDER BY s.created_at DESC
");
$surveys = $stmt->fetchAll();

// 현재 선택된 설문
$surveyId = input('id');
$survey = null;
$pages = [];
$questions = [];

if ($surveyId) {
    $stmt = $db->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch();

    if ($survey) {
        $stmt = $db->prepare("SELECT * FROM survey_pages WHERE survey_id = ? ORDER BY page_order");
        $stmt->execute([$surveyId]);
        $pages = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY page_id, question_order");
        $stmt->execute([$surveyId]);
        $questions = $stmt->fetchAll();
    }
}

// 페이지별 질문 그룹화
$pageQuestions = [];
foreach ($questions as $q) {
    $pageQuestions[$q['page_id']][] = $q;
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">여행 후기 설문 에디터</h3>
        <a href="/admin/" class="btn btn-sm btn-ghost btn-icon" title="메인으로">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </a>
    </div>
    <div class="card-body">
        <!-- 설문 선택 -->
        <div style="display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: end; margin-bottom: 24px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">설문 선택</label>
                <select id="survey-select" class="form-select" onchange="changeSurvey(this.value)">
                    <option value="">새 설문 만들기</option>
                    <?php foreach ($surveys as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $surveyId == $s['id'] ? 'selected' : '' ?>>
                            <?= h($s['title']) ?> (<?= $s['response_count'] ?>명 응답)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($survey): ?>
                <a href="/admin/survey-stats.php?id=<?= $surveyId ?>" class="btn btn-secondary">통계 보기</a>
            <?php endif; ?>
        </div>

        <form id="survey-form" onsubmit="saveSurvey(event)">
            <input type="hidden" name="id" id="survey-id" value="<?= $surveyId ?>">

            <!-- 설문 제목 -->
            <div class="form-group">
                <label class="form-label">설문 제목</label>
                <input type="text" name="title" id="survey-title" class="form-input" value="<?= h($survey['title'] ?? '') ?>" placeholder="설문 제목을 입력하세요" required>
            </div>

            <!-- 설문 기간 -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">시작일</label>
                    <input type="date" name="start_date" id="survey-start" class="form-input" value="<?= $survey['start_date'] ?? '' ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">종료일</label>
                    <input type="date" name="end_date" id="survey-end" class="form-input" value="<?= $survey['end_date'] ?? '' ?>">
                </div>
            </div>

            <?php if ($survey): ?>
                <!-- 질문/페이지 추가 버튼 -->
                <div style="display: flex; gap: 12px; margin-bottom: 24px;">
                    <button type="button" class="btn btn-secondary" onclick="openQuestionModal()">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        질문추가
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="addPage()">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        페이지 추가
                    </button>
                </div>

                <!-- 질문 목록 -->
                <div id="questions-container">
                    <?php if (empty($pages)): ?>
                        <p style="text-align: center; color: var(--gray-500); padding: 40px;">질문을 추가해주세요.</p>
                    <?php else: ?>
                        <?php foreach ($pages as $page): ?>
                            <div class="question-page" data-page-id="<?= $page['id'] ?>">
                                <div style="background: var(--primary-100); color: var(--primary-700); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                                    <strong>페이지 <?= $page['page_order'] ?></strong>
                                    <?php if (count($pages) > 1): ?>
                                        <button type="button" class="btn btn-sm btn-ghost" onclick="deletePage(<?= $page['id'] ?>)" style="color: var(--error);">삭제</button>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($pageQuestions[$page['id']])): ?>
                                    <?php foreach ($pageQuestions[$page['id']] as $q): ?>
                                        <div class="question-item" data-id="<?= $q['id'] ?>">
                                            <div style="display: flex; justify-content: space-between; align-items: start; gap: 16px;">
                                                <div style="flex: 1;">
                                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                                        <span class="badge badge-primary"><?= QUESTION_TYPE_LABELS[$q['question_type']] ?></span>
                                                    </div>
                                                    <p style="font-weight: 500; margin-bottom: 8px;"><?= h($q['question_text']) ?></p>
                                                    <?php if ($q['question_type'] === 'multiple' && $q['options']): ?>
                                                        <div style="font-size: 13px; color: var(--gray-600);">
                                                            <?php foreach (json_decode($q['options'], true) as $idx => $opt): ?>
                                                                <div style="padding: 4px 0;">• <?= h($opt) ?></div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="display: flex; gap: 4px; flex-shrink: 0;">
                                                    <button type="button" class="btn btn-sm btn-secondary" onclick="editQuestion(<?= $q['id'] ?>)">수정</button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteQuestion(<?= $q['id'] ?>)">삭제</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="text-align: center; color: var(--gray-500); padding: 20px;">이 페이지에 질문이 없습니다.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- 버튼 영역 -->
            <div style="display: flex; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--gray-200);">
                <?php if ($survey): ?>
                    <button type="button" class="btn btn-danger" onclick="deleteSurvey(<?= $surveyId ?>)">삭제하기</button>
                <?php endif; ?>
                <div style="flex: 1;"></div>
                <?php if ($survey): ?>
                    <button type="submit" class="btn btn-primary">수정하기</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary">저장하기</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- 질문 추가/수정 모달 -->
<div class="modal-backdrop" id="question-modal">
    <div class="modal" style="max-width: 550px;">
        <div class="modal-header">
            <h3 class="modal-title" id="question-modal-title">질문 추가</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('question-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </span>
        </div>
        <form id="question-form" onsubmit="saveQuestion(event)">
            <input type="hidden" name="id" id="q-id">
            <input type="hidden" name="survey_id" value="<?= $surveyId ?>">
            <input type="hidden" name="page_id" id="q-page-id">
            <div class="modal-body">
                <!-- 페이지 선택 -->
                <div class="form-group">
                    <label class="form-label">페이지</label>
                    <select name="page_id_select" id="q-page-select" class="form-select">
                        <?php foreach ($pages as $page): ?>
                            <option value="<?= $page['id'] ?>">페이지 <?= $page['page_order'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 답변 형태 -->
                <div class="form-group">
                    <label class="form-label">답변 형태</label>
                    <select name="question_type" id="q-type" class="form-select" onchange="toggleOptions()">
                        <option value="multiple">객관식</option>
                        <option value="short">단답형</option>
                        <option value="long">서술형</option>
                    </select>
                </div>

                <!-- 질문 -->
                <div class="form-group">
                    <label class="form-label">질문</label>
                    <textarea name="question_text" id="q-text" class="form-textarea" rows="2" required placeholder="질문 내용을 입력하세요"></textarea>
                </div>

                <!-- 선택지 (객관식) -->
                <div class="form-group" id="options-group">
                    <label class="form-label">선택지</label>
                    <div id="options-container">
                        <!-- 동적으로 추가됨 -->
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="addOption()" style="margin-top: 8px;">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        선택지 추가
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('question-modal')">취소</button>
                <button type="submit" class="btn btn-primary">저장</button>
            </div>
        </form>
    </div>
</div>

<style>
.question-page {
    margin-bottom: 24px;
}
.question-item {
    background: var(--gray-50);
    padding: 16px;
    border-radius: var(--radius-md);
    margin-bottom: 12px;
    border: 1px solid var(--gray-200);
}
.option-row {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}
.option-row input {
    flex: 1;
}
</style>

<script>
const surveyId = <?= $surveyId ?: 'null' ?>;

function changeSurvey(id) {
    if (id) {
        location.href = `/admin/survey-editor.php?id=${id}`;
    } else {
        location.href = '/admin/survey-editor.php';
    }
}

async function saveSurvey(e) {
    e.preventDefault();
    const title = document.getElementById('survey-title').value;
    const startDate = document.getElementById('survey-start').value;
    const endDate = document.getElementById('survey-end').value;
    const id = document.getElementById('survey-id').value;

    try {
        if (id) {
            // 수정
            await BornAdmin.api('/api/surveys.php', {
                method: 'POST',
                body: { action: 'update', id, title, start_date: startDate, end_date: endDate }
            });
            BornAdmin.toast('수정되었습니다.', 'success');
        } else {
            // 새로 만들기
            const response = await BornAdmin.api('/api/surveys.php', {
                method: 'POST',
                body: { action: 'create', title }
            });
            BornAdmin.toast('생성되었습니다.', 'success');
            location.href = `/admin/survey-editor.php?id=${response.data.id}`;
        }
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function deleteSurvey(id) {
    if (!await BornAdmin.confirmDelete('이 설문')) return;
    try {
        await BornAdmin.api('/api/surveys.php', { method: 'POST', body: { action: 'delete', id } });
        location.href = '/admin/survey-editor.php';
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function addPage() {
    try {
        await BornAdmin.api('/api/surveys.php', {
            method: 'POST',
            body: { action: 'add_page', survey_id: surveyId }
        });
        BornAdmin.toast('페이지가 추가되었습니다.', 'success');
        location.reload();
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function deletePage(pageId) {
    if (!await BornAdmin.confirmDelete('이 페이지')) return;
    try {
        await BornAdmin.api('/api/surveys.php', {
            method: 'POST',
            body: { action: 'delete_page', id: pageId }
        });
        BornAdmin.toast('삭제되었습니다.', 'success');
        location.reload();
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

function toggleOptions() {
    const type = document.getElementById('q-type').value;
    document.getElementById('options-group').style.display = type === 'multiple' ? 'block' : 'none';
}

function addOption(value = '') {
    const container = document.getElementById('options-container');
    const row = document.createElement('div');
    row.className = 'option-row';
    row.innerHTML = `
        <input type="text" class="form-input option-input" value="${value}" placeholder="선택지 입력">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">삭제</button>
    `;
    container.appendChild(row);
}

function openQuestionModal(pageId = null) {
    document.getElementById('question-modal-title').textContent = '질문 추가';
    document.getElementById('question-form').reset();
    document.getElementById('q-id').value = '';
    document.getElementById('options-container').innerHTML = '';

    // 기본 선택지 2개 추가
    addOption();
    addOption();

    if (pageId) {
        document.getElementById('q-page-select').value = pageId;
    }

    toggleOptions();
    BornAdmin.openModal('question-modal');
}

async function editQuestion(id) {
    try {
        const response = await BornAdmin.api(`/api/surveys.php?action=get_question&id=${id}`);
        const q = response.data;

        document.getElementById('question-modal-title').textContent = '질문 수정';
        document.getElementById('q-id').value = q.id;
        document.getElementById('q-type').value = q.question_type;
        document.getElementById('q-text').value = q.question_text;
        document.getElementById('q-page-select').value = q.page_id;

        // 선택지 로드
        const container = document.getElementById('options-container');
        container.innerHTML = '';
        if (q.options) {
            const options = JSON.parse(q.options);
            options.forEach(opt => addOption(opt));
        }

        toggleOptions();
        BornAdmin.openModal('question-modal');
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function saveQuestion(e) {
    e.preventDefault();

    const id = document.getElementById('q-id').value;
    const questionType = document.getElementById('q-type').value;
    const questionText = document.getElementById('q-text').value;
    const pageId = document.getElementById('q-page-select').value;

    let options = null;
    if (questionType === 'multiple') {
        const optionInputs = document.querySelectorAll('.option-input');
        const optionValues = Array.from(optionInputs).map(i => i.value.trim()).filter(v => v);
        if (optionValues.length < 2) {
            BornAdmin.toast('선택지를 2개 이상 입력해주세요.', 'warning');
            return;
        }
        options = JSON.stringify(optionValues);
    }

    const data = {
        action: id ? 'update_question' : 'add_question',
        survey_id: surveyId,
        page_id: pageId,
        question_type: questionType,
        question_text: questionText,
        options: options
    };

    if (id) data.id = id;

    try {
        await BornAdmin.api('/api/surveys.php', { method: 'POST', body: data });
        BornAdmin.toast('저장되었습니다.', 'success');
        BornAdmin.closeModal('question-modal');
        location.reload();
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function deleteQuestion(id) {
    if (!await BornAdmin.confirmDelete('이 질문')) return;
    try {
        await BornAdmin.api('/api/surveys.php', { method: 'POST', body: { action: 'delete_question', id } });
        document.querySelector(`.question-item[data-id="${id}"]`).remove();
        BornAdmin.toast('삭제되었습니다.', 'success');
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 초기화
toggleOptions();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
