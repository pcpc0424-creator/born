<?php
/**
 * 본투어 인터내셔날 - 회원 관리
 */

$pageTitle = '회원 관리';
require_once __DIR__ . '/../includes/header.php';

$db = db();

// 행사 목록 (필터용)
$stmt = $db->query("SELECT id, event_name FROM events ORDER BY start_date DESC");
$allEvents = $stmt->fetchAll();

// 페이지네이션 설정
$page = max(1, intval(input('page', 1)));
$search = input('search', '');
$eventFilter = input('event_id', '');
$perPage = ITEMS_PER_PAGE_ADMIN;

// 검색 조건
$where = "1=1";
$params = [];
$joinEvent = "";

if (!empty($search)) {
    $where .= " AND (m.name_ko LIKE ? OR m.name_en LIKE ? OR m.phone LIKE ? OR m.login_id LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($eventFilter)) {
    $joinEvent = "INNER JOIN event_members emf ON emf.member_id = m.id AND emf.event_id = ?";
    $params[] = (int)$eventFilter;
}

// 전체 회원 수
$stmt = $db->prepare("SELECT COUNT(DISTINCT m.id) as total FROM members m {$joinEvent} WHERE {$where}");
$stmt->execute($params);
$totalItems = $stmt->fetch()['total'];
$pagination = calculate_pagination($totalItems, $page, $perPage);

// 회원 목록
$sql = "
    SELECT m.*,
           (SELECT GROUP_CONCAT(e.event_name SEPARATOR ', ')
            FROM event_members em
            JOIN events e ON em.event_id = e.id
            WHERE em.member_id = m.id) as events
    FROM members m
    {$joinEvent}
    WHERE {$where}
    GROUP BY m.id
    ORDER BY m.created_at DESC
    LIMIT {$pagination['offset']}, {$perPage}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();
?>

<!-- 검색 및 액션 바 -->
<div class="search-bar">
    <div class="search-input-wrapper">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
        </svg>
        <input type="text" class="form-input search-input" id="search-input"
               placeholder="이름, 연락처, 아이디 검색..."
               value="<?= h($search) ?>">
    </div>
    <div style="margin: 0 12px;">
        <select id="event-filter" class="form-select" onchange="filterByEvent(this.value)" style="min-width: 180px;">
            <option value="">전체 행사</option>
            <?php foreach ($allEvents as $ev): ?>
                <option value="<?= $ev['id'] ?>" <?= $eventFilter == $ev['id'] ? 'selected' : '' ?>><?= h($ev['event_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <button type="button" class="btn btn-secondary" onclick="exportExcel()">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            엑셀 다운로드
        </button>
        <button type="button" class="btn btn-secondary" onclick="BornAdmin.openModal('import-modal')">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            엑셀 업로드
        </button>
        <button type="button" class="btn btn-primary" onclick="openMemberModal()">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            회원 추가
        </button>
    </div>
</div>

<!-- 회원 목록 테이블 -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table" id="members-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" class="check-all">
                        </th>
                        <th>아이디</th>
                        <th>이름</th>
                        <th>성별</th>
                        <th>생년월일</th>
                        <th>연락처</th>
                        <th>참여 행사</th>
                        <th>가입일</th>
                        <th style="width: 100px;">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 60px 20px; color: var(--gray-500);">
                                <?= empty($search) ? '등록된 회원이 없습니다.' : '검색 결과가 없습니다.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $member): ?>
                            <tr data-id="<?= $member['id'] ?>">
                                <td>
                                    <input type="checkbox" class="check-item" value="<?= $member['id'] ?>">
                                </td>
                                <td style="font-family: monospace;"><?= h($member['login_id']) ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?= h($member['name_ko']) ?></div>
                                    <?php if ($member['name_en']): ?>
                                        <div style="font-size: 12px; color: var(--gray-500);"><?= h($member['name_en']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($member['gender']): ?>
                                        <span class="badge <?= $member['gender'] === 'M' ? 'badge-primary' : 'badge-error' ?>" style="<?= $member['gender'] === 'F' ? 'background: #fce4ec; color: #c2185b;' : '' ?>">
                                            <?= GENDER_LABELS[$member['gender']] ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= $member['birth_date'] ? date('Y.m.d', strtotime($member['birth_date'])) : '-' ?></td>
                                <td><?= $member['phone'] ? format_phone($member['phone']) : '-' ?></td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= $member['events'] ? h($member['events']) : '<span style="color: var(--gray-400);">없음</span>' ?>
                                </td>
                                <td style="font-size: 13px; color: var(--gray-500);">
                                    <?= date('Y.m.d', strtotime($member['created_at'])) ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px;">
                                        <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="editMember(<?= $member['id'] ?>)" title="수정">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="deleteMember(<?= $member['id'] ?>)" title="삭제" style="color: var(--error);">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <div class="pagination">
                <button class="pagination-btn" onclick="goToPage(1)" <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="11 17 6 12 11 7"/>
                        <polyline points="18 17 13 12 18 7"/>
                    </svg>
                </button>
                <button class="pagination-btn" onclick="goToPage(<?= $pagination['current_page'] - 1 ?>)" <?= !$pagination['has_prev'] ? 'disabled' : '' ?>>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>

                <?php
                $startPage = max(1, $pagination['current_page'] - 2);
                $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <button class="pagination-btn <?= $i === $pagination['current_page'] ? 'active' : '' ?>" onclick="goToPage(<?= $i ?>)">
                        <?= $i ?>
                    </button>
                <?php endfor; ?>

                <button class="pagination-btn" onclick="goToPage(<?= $pagination['current_page'] + 1 ?>)" <?= !$pagination['has_next'] ? 'disabled' : '' ?>>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
                <button class="pagination-btn" onclick="goToPage(<?= $pagination['total_pages'] ?>)" <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="13 17 18 12 13 7"/>
                        <polyline points="6 17 11 12 6 7"/>
                    </svg>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- 회원 추가/수정 모달 -->
<div class="modal-backdrop" id="member-modal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title" id="member-modal-title">회원 추가</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('member-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <form id="member-form" onsubmit="saveMember(event)">
            <input type="hidden" name="id" id="member-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">아이디 <span class="required">*</span></label>
                    <input type="text" name="login_id" id="member-login-id" class="form-input" required>
                    <span class="form-hint">영문, 숫자만 사용 가능합니다.</span>
                </div>

                <div class="form-group">
                    <label class="form-label">비밀번호 <span class="required" id="password-required">*</span></label>
                    <input type="password" name="password" id="member-password" class="form-input">
                    <span class="form-hint" id="password-hint">비밀번호를 입력하지 않으면 기존 비밀번호가 유지됩니다.</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">한글 이름 <span class="required">*</span></label>
                        <input type="text" name="name_ko" id="member-name-ko" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">영문 이름</label>
                        <input type="text" name="name_en" id="member-name-en" class="form-input" placeholder="HONG / GILDONG">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">성별</label>
                        <select name="gender" id="member-gender" class="form-select">
                            <option value="">선택</option>
                            <option value="M">남성</option>
                            <option value="F">여성</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">생년월일</label>
                        <input type="date" name="birth_date" id="member-birth-date" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">연락처</label>
                    <input type="tel" name="phone" id="member-phone" class="form-input" placeholder="010-0000-0000">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('member-modal')">취소</button>
                <button type="submit" class="btn btn-primary" id="member-submit-btn">저장</button>
            </div>
        </form>
    </div>
</div>

<!-- 엑셀 업로드 모달 -->
<div class="modal-backdrop" id="import-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">엑셀 업로드</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('import-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <div class="modal-body">
            <div style="background: var(--info-light); color: var(--info); padding: 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px;">
                <strong>엑셀 양식 안내</strong><br>
                아이디, 비밀번호, 한글이름, 영문이름, 성별, 생년월일, 연락처 순으로 작성해주세요.
            </div>

            <form id="import-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">엑셀 파일 선택</label>
                    <input type="file" name="excel_file" id="excel-file" class="form-input" accept=".xlsx,.xls,.csv">
                    <span class="form-hint">.xlsx, .xls, .csv 파일 업로드 가능합니다.</span>
                </div>
            </form>

            <div style="margin-top: 16px;">
                <a href="/api/members.php?action=download_template" class="btn btn-secondary btn-sm">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    양식 다운로드
                </a>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('import-modal')">취소</button>
            <button type="button" class="btn btn-primary" onclick="importExcel()">업로드</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// 검색
document.getElementById('search-input').addEventListener('input', BornAdmin.debounce(function(e) {
    const url = new URL(window.location);
    if (e.target.value) {
        url.searchParams.set('search', e.target.value);
    } else {
        url.searchParams.delete('search');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}, 500));

// 행사 필터
function filterByEvent(eventId) {
    const url = new URL(window.location);
    if (eventId) {
        url.searchParams.set('event_id', eventId);
    } else {
        url.searchParams.delete('event_id');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// 페이지 이동
function goToPage(page) {
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}

// 회원 모달 열기 (추가)
function openMemberModal() {
    document.getElementById('member-modal-title').textContent = '회원 추가';
    document.getElementById('member-form').reset();
    document.getElementById('member-id').value = '';
    document.getElementById('member-password').required = true;
    document.getElementById('password-required').style.display = 'inline';
    document.getElementById('password-hint').style.display = 'none';
    BornAdmin.openModal('member-modal');
}

// 회원 수정
async function editMember(id) {
    try {
        const response = await BornAdmin.api(`/api/members.php?action=get&id=${id}`);
        const member = response.data;

        document.getElementById('member-modal-title').textContent = '회원 수정';
        document.getElementById('member-id').value = member.id;
        document.getElementById('member-login-id').value = member.login_id;
        document.getElementById('member-password').value = '';
        document.getElementById('member-password').required = false;
        document.getElementById('password-required').style.display = 'none';
        document.getElementById('password-hint').style.display = 'block';
        document.getElementById('member-name-ko').value = member.name_ko;
        document.getElementById('member-name-en').value = member.name_en || '';
        document.getElementById('member-phone').value = member.phone || '';
        document.getElementById('member-birth-date').value = member.birth_date || '';
        document.getElementById('member-gender').value = member.gender || '';

        BornAdmin.openModal('member-modal');
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 회원 저장
async function saveMember(event) {
    event.preventDefault();

    const form = document.getElementById('member-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    const isEdit = !!data.id;
    data.action = isEdit ? 'update' : 'create';

    try {
        BornAdmin.showLoading('#member-submit-btn');
        await BornAdmin.api('/api/members.php', {
            method: 'POST',
            body: data
        });

        BornAdmin.toast(isEdit ? '회원 정보가 수정되었습니다.' : '회원이 추가되었습니다.', 'success');
        BornAdmin.closeModal('member-modal');
        setTimeout(() => location.reload(), 500);
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    } finally {
        BornAdmin.hideLoading('#member-submit-btn');
    }
}

// 회원 삭제
async function deleteMember(id) {
    if (!await BornAdmin.confirmDelete('이 회원')) return;

    try {
        await BornAdmin.api('/api/members.php', {
            method: 'POST',
            body: { action: 'delete', id: id }
        });

        BornAdmin.toast('회원이 삭제되었습니다.', 'success');
        document.querySelector(`tr[data-id="${id}"]`).remove();
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 엑셀 다운로드
function exportExcel() {
    const search = document.getElementById('search-input').value;
    const eventId = document.getElementById('event-filter').value;
    let url = `/api/members.php?action=export&search=${encodeURIComponent(search)}`;
    if (eventId) url += `&event_id=${eventId}`;
    window.location.href = url;
}

// 엑셀 업로드
async function importExcel() {
    const fileInput = document.getElementById('excel-file');
    if (!fileInput.files.length) {
        BornAdmin.toast('파일을 선택해주세요.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'import');
    formData.append('excel_file', fileInput.files[0]);

    try {
        const response = await BornAdmin.api('/api/members.php', {
            method: 'POST',
            body: formData
        });

        BornAdmin.toast(`${response.data.imported}명의 회원이 등록되었습니다.`, 'success');
        BornAdmin.closeModal('import-modal');
        setTimeout(() => location.reload(), 500);
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 테이블 선택 초기화
BornAdmin.initTableSelect('members-table');
</script>
