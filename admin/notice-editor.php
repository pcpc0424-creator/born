<?php
/**
 * 본투어 인터내셔날 - 공지/문의 에디터
 */

$pageTitle = '공지/문의 에디터';
require_once __DIR__ . '/../includes/header.php';

$db = db();
$category = input('category', 'notice');

// 목록 조회
$stmt = $db->prepare("SELECT * FROM notices WHERE category = ? ORDER BY sort_order ASC, id DESC");
$stmt->execute([$category]);
$notices = $stmt->fetchAll();
?>

<!-- 탭 -->
<div style="display: flex; gap: 8px; margin-bottom: 24px;">
    <a href="?category=notice" class="btn <?= $category === 'notice' ? 'btn-primary' : 'btn-secondary' ?>">공지사항</a>
    <a href="?category=faq" class="btn <?= $category === 'faq' ? 'btn-primary' : 'btn-secondary' ?>">자주 묻는 질문 (FAQ)</a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= $category === 'notice' ? '공지사항' : 'FAQ' ?> (<?= count($notices) ?>개)</h3>
        <button type="button" class="btn btn-primary btn-sm" onclick="openNoticeModal()">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            추가
        </button>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($notices)): ?>
            <div style="padding: 60px 20px; text-align: center; color: var(--gray-500);">
                등록된 <?= $category === 'notice' ? '공지사항' : 'FAQ' ?>이 없습니다.
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">순서</th>
                            <th>제목</th>
                            <th>등록일</th>
                            <th style="width: 100px;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notices as $notice): ?>
                            <tr data-id="<?= $notice['id'] ?>">
                                <td>
                                    <input type="number" class="form-input" style="width: 60px; padding: 6px 8px; font-size: 13px; text-align: center;"
                                           value="<?= $notice['sort_order'] ?>" onchange="updateOrder(<?= $notice['id'] ?>, this.value)">
                                </td>
                                <td>
                                    <strong><?= h($notice['title']) ?></strong>
                                    <p style="font-size: 12px; color: var(--gray-500); margin-top: 4px;">
                                        <?= h(mb_substr(strip_tags($notice['content']), 0, 80)) ?>...
                                    </p>
                                </td>
                                <td style="font-size: 13px; color: var(--gray-500);">
                                    <?= date('Y.m.d', strtotime($notice['created_at'])) ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px;">
                                        <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="editNotice(<?= $notice['id'] ?>)">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="deleteNotice(<?= $notice['id'] ?>)" style="color: var(--error);">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 모달 -->
<div class="modal-backdrop" id="notice-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title" id="notice-modal-title"><?= $category === 'notice' ? '공지사항' : 'FAQ' ?> 추가</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('notice-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <form id="notice-form" onsubmit="saveNotice(event)">
            <input type="hidden" name="id" id="notice-id">
            <input type="hidden" name="category" value="<?= $category ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">제목 <span class="required">*</span></label>
                    <input type="text" name="title" id="notice-title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">내용</label>
                    <div class="expandable-textarea-wrapper" onclick="openContentModal()">
                        <textarea name="content" id="notice-content" class="form-textarea expandable-textarea" rows="8" maxlength="1000"
                                  placeholder="클릭하여 내용 입력 (최대 1000자)" readonly></textarea>
                        <div class="expand-hint">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 3 21 3 21 9"/>
                                <polyline points="9 21 3 21 3 15"/>
                                <line x1="21" y1="3" x2="14" y2="10"/>
                                <line x1="3" y1="21" x2="10" y2="14"/>
                            </svg>
                            최대 1000자
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('notice-modal')">취소</button>
                <button type="submit" class="btn btn-primary">저장</button>
            </div>
        </form>
    </div>
</div>

<!-- 텍스트 확장 입력 모달 -->
<div class="modal-backdrop" id="content-expand-modal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3 class="modal-title">내용 입력 (최대 1000자)</h3>
            <span class="modal-close" onclick="closeContentModal()">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <div class="modal-body">
            <textarea id="content-expand-input" class="form-textarea" rows="15" maxlength="1000" style="font-size: 15px; line-height: 1.7;"></textarea>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                <span id="content-expand-count" style="font-size: 13px; color: var(--gray-500);">0 / 1000 자</span>
                <span style="font-size: 12px; color: var(--gray-400);">넓은 입력창에서 편하게 작성하세요</span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeContentModal()">취소</button>
            <button type="button" class="btn btn-primary" onclick="saveContentModal()">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                저장
            </button>
        </div>
    </div>
</div>

<style>
/* 텍스트 확장 입력 스타일 */
.expandable-textarea-wrapper {
    position: relative;
    cursor: pointer;
}
.expandable-textarea-wrapper:hover .expandable-textarea {
    border-color: var(--primary-400);
    background: var(--primary-50);
}
.expandable-textarea {
    cursor: pointer !important;
    transition: all 0.2s ease;
}
.expand-hint {
    position: absolute;
    bottom: 8px;
    right: 8px;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: var(--gray-500);
    background: var(--white);
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--gray-200);
    pointer-events: none;
}
.expandable-textarea-wrapper:hover .expand-hint {
    background: var(--primary-100);
    border-color: var(--primary-300);
    color: var(--primary-700);
}
</style>

<script>
// 내용 입력 모달 열기
function openContentModal() {
    const content = document.getElementById('notice-content').value;
    document.getElementById('content-expand-input').value = content;
    updateContentCount();
    BornAdmin.openModal('content-expand-modal');
    setTimeout(() => document.getElementById('content-expand-input').focus(), 100);
}

// 내용 카운트 업데이트
function updateContentCount() {
    const input = document.getElementById('content-expand-input');
    const count = input.value.length;
    const maxLength = 1000;
    document.getElementById('content-expand-count').textContent = count + ' / ' + maxLength + ' 자';

    if (count >= maxLength * 0.9) {
        document.getElementById('content-expand-count').style.color = 'var(--danger-600)';
    } else if (count >= maxLength * 0.7) {
        document.getElementById('content-expand-count').style.color = 'var(--warning-600)';
    } else {
        document.getElementById('content-expand-count').style.color = 'var(--gray-500)';
    }
}

// 내용 저장
function saveContentModal() {
    document.getElementById('notice-content').value = document.getElementById('content-expand-input').value;
    closeContentModal();
    BornAdmin.toast('내용이 저장되었습니다.', 'success');
}

// 모달 닫기
function closeContentModal() {
    BornAdmin.closeModal('content-expand-modal');
}

// 입력 이벤트
document.getElementById('content-expand-input')?.addEventListener('input', updateContentCount);

function openNoticeModal() {
    document.getElementById('notice-modal-title').textContent = '<?= $category === 'notice' ? '공지사항' : 'FAQ' ?> 추가';
    document.getElementById('notice-form').reset();
    document.getElementById('notice-id').value = '';
    BornAdmin.openModal('notice-modal');
}

async function editNotice(id) {
    try {
        const response = await BornAdmin.api(`/born/api/notices.php?action=get&id=${id}`);
        const notice = response.data;

        document.getElementById('notice-modal-title').textContent = '<?= $category === 'notice' ? '공지사항' : 'FAQ' ?> 수정';
        document.getElementById('notice-id').value = notice.id;
        document.getElementById('notice-title').value = notice.title;
        document.getElementById('notice-content').value = notice.content || '';

        BornAdmin.openModal('notice-modal');
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function saveNotice(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('notice-form'));
    const data = Object.fromEntries(formData.entries());
    data.action = data.id ? 'update' : 'create';

    try {
        await BornAdmin.api('/born/api/notices.php', { method: 'POST', body: data });
        BornAdmin.toast('저장되었습니다.', 'success');
        BornAdmin.closeModal('notice-modal');
        setTimeout(() => location.reload(), 500);
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function deleteNotice(id) {
    if (!await BornAdmin.confirmDelete()) return;

    try {
        await BornAdmin.api('/born/api/notices.php', { method: 'POST', body: { action: 'delete', id } });
        BornAdmin.toast('삭제되었습니다.', 'success');
        document.querySelector(`tr[data-id="${id}"]`).remove();
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function updateOrder(id, order) {
    try {
        await BornAdmin.api('/born/api/notices.php', { method: 'POST', body: { action: 'update_order', id, sort_order: order } });
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
