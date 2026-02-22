<?php
/**
 * 본투어 인터내셔날 - 행사-개인 에디터
 */

$pageTitle = '행사-개인 에디터';
require_once __DIR__ . '/../includes/header.php';

$db = db();

// 현재 선택된 행사
$eventId = input('event_id');
$event = null;

if ($eventId) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
}

// 행사 목록
$stmt = $db->query("SELECT id, event_name, start_date, end_date FROM events WHERE status = 'active' ORDER BY start_date DESC");
$events = $stmt->fetchAll();

// 행사 참가자 목록
$eventMembers = [];
$optionalTours = [];
if ($event) {
    $stmt = $db->prepare("
        SELECT em.*, m.name_ko, m.name_en, m.phone, m.birth_date, m.gender, m.login_id
        FROM event_members em
        JOIN members m ON em.member_id = m.id
        WHERE em.event_id = ?
        ORDER BY m.name_ko ASC
    ");
    $stmt->execute([$eventId]);
    $eventMembers = $stmt->fetchAll();

    // 선택관광 목록 조회
    $stmt = $db->prepare("SELECT id, tour_name FROM optional_tours WHERE event_id = ? AND status = 'active' ORDER BY id");
    $stmt->execute([$eventId]);
    $optionalTours = $stmt->fetchAll();
}
?>

<!-- 행사 선택 -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body">
        <div style="display: flex; gap: 16px; align-items: center;">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <select id="event-select" class="form-select" onchange="changeEvent(this.value)">
                    <option value="">행사를 선택하세요</option>
                    <?php foreach ($events as $ev): ?>
                        <option value="<?= $ev['id'] ?>" <?= $eventId == $ev['id'] ? 'selected' : '' ?>>
                            <?= h($ev['event_name']) ?> (<?= date('m.d', strtotime($ev['start_date'])) ?> ~ <?= date('m.d', strtotime($ev['end_date'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($event): ?>
                <button type="button" class="btn btn-secondary" onclick="BornAdmin.openModal('add-member-modal')">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                        <line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                    회원 추가
                </button>
                <button type="button" class="btn btn-secondary" onclick="BornAdmin.openModal('import-modal')">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    엑셀 업로드
                </button>
                <button type="button" class="btn btn-secondary" onclick="exportEventMembers()">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    엑셀 다운로드
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($event): ?>
    <!-- 참가자 목록 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= h($event['event_name']) ?> 참가자 (<?= count($eventMembers) ?>명)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table" id="event-members-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="check-all">
                            </th>
                            <th>이름</th>
                            <th>연락처</th>
                            <th>생년월일</th>
                            <?php if (!empty($optionalTours)): ?>
                            <th>선택관광</th>
                            <?php endif; ?>
                            <th>버스</th>
                            <th>만찬장</th>
                            <th>객실</th>
                            <th style="width: 100px;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eventMembers)): ?>
                            <tr>
                                <td colspan="<?= !empty($optionalTours) ? 9 : 8 ?>" style="text-align: center; padding: 60px 20px; color: var(--gray-500);">
                                    등록된 참가자가 없습니다.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eventMembers as $em):
                                // 참가자의 선택관광 ID 목록 파싱
                                $memberTourIds = [];
                                if (!empty($em['optional_tour_ids'])) {
                                    $memberTourIds = json_decode($em['optional_tour_ids'], true) ?: [];
                                }
                            ?>
                                <tr data-id="<?= $em['id'] ?>">
                                    <td>
                                        <input type="checkbox" class="check-item" value="<?= $em['id'] ?>">
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?= h($em['name_ko']) ?></div>
                                        <?php if ($em['name_en']): ?>
                                            <div style="font-size: 12px; color: var(--gray-500);"><?= h($em['name_en']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $em['phone'] ? format_phone($em['phone']) : '-' ?></td>
                                    <td><?= $em['birth_date'] ? date('Y.m.d', strtotime($em['birth_date'])) : '-' ?></td>
                                    <?php if (!empty($optionalTours)): ?>
                                    <td>
                                        <?php
                                        $selectedTours = [];
                                        foreach ($optionalTours as $tour) {
                                            if (in_array($tour['id'], $memberTourIds)) {
                                                $selectedTours[] = h($tour['tour_name']);
                                            }
                                        }
                                        if (!empty($selectedTours)):
                                        ?>
                                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                <?php foreach ($selectedTours as $tourName): ?>
                                                    <span class="badge badge-primary" style="font-size: 11px;"><?= $tourName ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--gray-400);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <input type="text" class="form-input" style="width: 80px; padding: 6px 10px; font-size: 13px;"
                                               value="<?= h($em['bus_number'] ?? '') ?>"
                                               onchange="updateEventMember(<?= $em['id'] ?>, 'bus_number', this.value)">
                                    </td>
                                    <td>
                                        <input type="text" class="form-input" style="width: 80px; padding: 6px 10px; font-size: 13px;"
                                               value="<?= h($em['dinner_table'] ?? '') ?>"
                                               onchange="updateEventMember(<?= $em['id'] ?>, 'dinner_table', this.value)">
                                    </td>
                                    <td>
                                        <input type="text" class="form-input" style="width: 80px; padding: 6px 10px; font-size: 13px;"
                                               value="<?= h($em['room_number'] ?? '') ?>"
                                               onchange="updateEventMember(<?= $em['id'] ?>, 'room_number', this.value)">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="removeEventMember(<?= $em['id'] ?>)" title="제거" style="color: var(--error);">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($eventMembers)): ?>
            <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeSelectedMembers()">선택 삭제</button>
                <span style="color: var(--gray-500); font-size: 13px;">
                    ※ 버스, 만찬장, 객실 정보는 입력 시 자동 저장됩니다.
                </span>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 80px 20px; color: var(--gray-500);">
            <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 16px; opacity: 0.5;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <p style="font-size: 16px; margin-bottom: 8px;">행사를 선택해주세요</p>
            <p style="font-size: 14px;">행사 참가자를 관리하려면 먼저 행사를 선택하세요.</p>
        </div>
    </div>
<?php endif; ?>

<!-- 회원 추가 모달 -->
<div class="modal-backdrop" id="add-member-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">참가자 추가</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('add-member-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <input type="text" class="form-input" id="member-search" placeholder="이름 또는 연락처로 회원 검색...">
            </div>

            <div id="member-search-results" style="max-height: 300px; overflow-y: auto; border: 1px solid var(--gray-200); border-radius: var(--radius-md);">
                <div style="padding: 40px 20px; text-align: center; color: var(--gray-500);">
                    검색어를 입력하세요
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('add-member-modal')">닫기</button>
        </div>
    </div>
</div>

<!-- 엑셀 업로드 모달 -->
<div class="modal-backdrop" id="import-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">예약내역 엑셀 업로드</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('import-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <div class="modal-body">
            <div style="background: var(--info-light); color: var(--info); padding: 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px;">
                <strong>엑셀 양식 안내</strong><br>
                이름, 생년월일, 버스, 만찬장, 객실 순으로 작성해주세요.<br>
                이름+생년월일로 기존 회원과 자동 매칭됩니다.
            </div>

            <form id="import-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">엑셀 파일 선택</label>
                    <input type="file" name="excel_file" id="excel-file" class="form-input" accept=".xlsx,.xls,.csv">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('import-modal')">취소</button>
            <button type="button" class="btn btn-primary" onclick="importEventMembers()">업로드</button>
        </div>
    </div>
</div>

<style>
.member-search-item {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: background 0.2s ease;
}
.member-search-item:hover {
    background: var(--gray-50);
}
.member-search-item:last-child {
    border-bottom: none;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const eventId = <?= $eventId ?: 'null' ?>;

// 행사 변경
function changeEvent(id) {
    if (id) {
        window.location.href = `/born/admin/event-member.php?event_id=${id}`;
    }
}

// 회원 검색
document.getElementById('member-search')?.addEventListener('input', BornAdmin.debounce(async function(e) {
    const query = e.target.value.trim();
    const results = document.getElementById('member-search-results');

    if (query.length < 2) {
        results.innerHTML = '<div style="padding: 40px 20px; text-align: center; color: var(--gray-500);">2자 이상 입력하세요</div>';
        return;
    }

    try {
        const response = await BornAdmin.api(`/born/api/members.php?action=list&search=${encodeURIComponent(query)}&per_page=20`);
        const members = response.data.members;

        if (members.length === 0) {
            results.innerHTML = '<div style="padding: 40px 20px; text-align: center; color: var(--gray-500);">검색 결과가 없습니다</div>';
            return;
        }

        results.innerHTML = members.map(m => `
            <div class="member-search-item" onclick="addMemberToEvent(${m.id})">
                <div>
                    <strong>${m.name_ko}</strong>
                    ${m.name_en ? `<span style="color: var(--gray-500); font-size: 12px;">(${m.name_en})</span>` : ''}
                </div>
                <div style="font-size: 13px; color: var(--gray-500);">
                    ${m.phone || '-'} / ${m.birth_date || '-'}
                </div>
            </div>
        `).join('');
    } catch (error) {
        results.innerHTML = '<div style="padding: 40px 20px; text-align: center; color: var(--error);">오류가 발생했습니다</div>';
    }
}, 300));

// 참가자 추가
async function addMemberToEvent(memberId) {
    try {
        await BornAdmin.api('/born/api/event-members.php', {
            method: 'POST',
            body: {
                action: 'add',
                event_id: eventId,
                member_id: memberId
            }
        });

        BornAdmin.toast('참가자가 추가되었습니다.', 'success');
        BornAdmin.closeModal('add-member-modal');
        location.reload();
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 참가자 정보 수정
async function updateEventMember(id, field, value) {
    try {
        await BornAdmin.api('/born/api/event-members.php', {
            method: 'POST',
            body: {
                action: 'update',
                id: id,
                field: field,
                value: value
            }
        });
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 참가자 제거
async function removeEventMember(id) {
    if (!await BornAdmin.confirmDelete('이 참가자')) return;

    try {
        await BornAdmin.api('/born/api/event-members.php', {
            method: 'POST',
            body: { action: 'remove', id: id }
        });

        BornAdmin.toast('참가자가 제거되었습니다.', 'success');
        document.querySelector(`tr[data-id="${id}"]`).remove();
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 선택 삭제
async function removeSelectedMembers() {
    const ids = BornAdmin.getSelectedIds('event-members-table');
    if (ids.length === 0) {
        BornAdmin.toast('삭제할 참가자를 선택하세요.', 'warning');
        return;
    }

    if (!await BornAdmin.confirmDelete(`${ids.length}명의 참가자`)) return;

    try {
        await BornAdmin.api('/born/api/event-members.php', {
            method: 'POST',
            body: { action: 'remove_multiple', ids: ids }
        });

        BornAdmin.toast('삭제되었습니다.', 'success');
        location.reload();
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 엑셀 다운로드
function exportEventMembers() {
    window.location.href = `/born/api/event-members.php?action=export&event_id=${eventId}`;
}

// 엑셀 업로드
async function importEventMembers() {
    const fileInput = document.getElementById('excel-file');
    if (!fileInput.files.length) {
        BornAdmin.toast('파일을 선택해주세요.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'import');
    formData.append('event_id', eventId);
    formData.append('excel_file', fileInput.files[0]);

    try {
        const response = await BornAdmin.api('/born/api/event-members.php', {
            method: 'POST',
            body: formData
        });

        BornAdmin.toast(`${response.data.imported}명이 등록/매칭되었습니다.`, 'success');
        BornAdmin.closeModal('import-modal');
        setTimeout(() => location.reload(), 500);
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 테이블 선택 초기화
BornAdmin.initTableSelect('event-members-table');
</script>
</body>
</html>
