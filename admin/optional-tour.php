<?php
/**
 * 본투어 인터내셔날 - 선택관광 에디터
 */

$pageTitle = '선택관광 에디터';
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

// 선택관광 목록
$tours = [];
if ($event) {
    $stmt = $db->prepare("SELECT * FROM optional_tours WHERE event_id = ? ORDER BY id DESC");
    $stmt->execute([$eventId]);
    $tours = $stmt->fetchAll();
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
                <button type="button" class="btn btn-primary" onclick="openTourModal()">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    선택관광 추가
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($event): ?>
    <!-- 선택관광 목록 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= h($event['event_name']) ?> 선택관광 (<?= count($tours) ?>개)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($tours)): ?>
                <div style="padding: 60px 20px; text-align: center; color: var(--gray-500);">
                    등록된 선택관광이 없습니다.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>선택관광명</th>
                                <th>진행 일차</th>
                                <th>금액</th>
                                <th>소요시간</th>
                                <th>미팅시간</th>
                                <th>상태</th>
                                <th style="width: 100px;">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tours as $tour): ?>
                                <tr data-id="<?= $tour['id'] ?>">
                                    <td>
                                        <strong><?= h($tour['tour_name']) ?></strong>
                                        <?php if ($tour['description']): ?>
                                            <p style="font-size: 12px; color: var(--gray-500); margin-top: 4px;">
                                                <?= h(mb_substr($tour['description'], 0, 50)) ?>...
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px;">
                                        <?php
                                        $tourDays = !empty($tour['tour_dates']) ? json_decode($tour['tour_dates'], true) : [];
                                        if (!empty($tourDays)) {
                                            $startDt = new DateTime($event['start_date']);
                                            $dayLabels = [];
                                            foreach ($tourDays as $td) {
                                                if (is_numeric($td)) {
                                                    $dayDt = clone $startDt;
                                                    $dayDt->modify('+' . ((int)$td - 1) . ' days');
                                                    $dayLabels[] = $td . '일차<small style="color:var(--gray-400)">(' . $dayDt->format('m.d') . ')</small>';
                                                } else {
                                                    $dayLabels[] = date('m.d', strtotime($td));
                                                }
                                            }
                                            echo implode('<br>', $dayLabels);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?= format_price($tour['price']) ?></td>
                                    <td><?= h($tour['duration'] ?? '-') ?></td>
                                    <td><?= $tour['meeting_time'] ? date('H:i', strtotime($tour['meeting_time'])) : '-' ?></td>
                                    <td>
                                        <span class="badge <?= $tour['status'] === 'active' ? 'badge-success' : 'badge-gray' ?>">
                                            <?= $tour['status'] === 'active' ? '활성' : '비활성' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px;">
                                            <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="editTour(<?= $tour['id'] ?>)" title="수정">
                                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="deleteTour(<?= $tour['id'] ?>)" title="삭제" style="color: var(--error);">
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
<?php else: ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 80px 20px; color: var(--gray-500);">
            행사를 선택해주세요
        </div>
    </div>
<?php endif; ?>

<!-- 선택관광 모달 -->
<div class="modal-backdrop" id="tour-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title" id="tour-modal-title">선택관광 추가</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('tour-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <form id="tour-form" onsubmit="saveTour(event)">
            <input type="hidden" name="id" id="tour-id">
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">선택관광명 <span class="required">*</span></label>
                    <input type="text" name="tour_name" id="tour-name" class="form-input" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">금액</label>
                        <input type="number" name="price" id="tour-price" class="form-input" min="0" value="0">
                        <span class="form-hint">0원이면 무료로 표시됩니다.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">소요시간</label>
                        <input type="text" name="duration" id="tour-duration" class="form-input" placeholder="약 2시간">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">미팅시간</label>
                    <input type="time" name="meeting_time" id="tour-meeting-time" class="form-input">
                    <span class="form-hint">선택관광 미팅 시간을 입력하세요.</span>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        진행 일차
                        <small style="color: var(--gray-500); font-weight: normal;">(선택관광 진행일)</small>
                    </label>
                    <div id="tour-days-container" style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php if ($event):
                            $startDt = new DateTime($event['start_date']);
                            $endDt = new DateTime($event['end_date']);
                            $days = (int)$startDt->diff($endDt)->days + 1;
                            for ($d = 1; $d <= $days; $d++):
                                $dayDt = clone $startDt;
                                $dayDt->modify('+' . ($d - 1) . ' days');
                        ?>
                            <label style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: var(--gray-100); border-radius: var(--radius-md); cursor: pointer; font-size: 13px; transition: all 0.2s;">
                                <input type="checkbox" name="tour_day_checks[]" value="<?= $d ?>" style="accent-color: var(--primary-600);">
                                <span><?= $d ?>일차</span>
                                <small style="color: var(--gray-500);">(<?= $dayDt->format('m.d') ?>)</small>
                            </label>
                        <?php endfor; endif; ?>
                    </div>
                    <span class="form-hint">선택관광이 진행되는 일차를 선택하세요. (복수 선택 가능)</span>
                </div>

                <div class="form-group">
                    <label class="form-label">설명</label>
                    <textarea name="description" id="tour-description" class="form-textarea" rows="3" maxlength="1000"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">유의사항</label>
                    <textarea name="notice" id="tour-notice" class="form-textarea" rows="3" maxlength="1000"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">상태</label>
                    <select name="status" id="tour-status" class="form-select">
                        <option value="active">활성</option>
                        <option value="inactive">비활성</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('tour-modal')">취소</button>
                <button type="submit" class="btn btn-primary">저장</button>
            </div>
        </form>
    </div>
</div>

<script>
const eventId = <?= $eventId ?: 'null' ?>;

function changeEvent(id) {
    if (id) {
        window.location.href = `/admin/optional-tour.php?event_id=${id}`;
    }
}

function openTourModal() {
    document.getElementById('tour-modal-title').textContent = '선택관광 추가';
    document.getElementById('tour-form').reset();
    document.getElementById('tour-id').value = '';
    // 체크박스 모두 해제
    document.querySelectorAll('input[name="tour_day_checks[]"]').forEach(cb => cb.checked = false);
    BornAdmin.openModal('tour-modal');
}

// 일차 체크박스 로드
function loadTourDays(days) {
    document.querySelectorAll('input[name="tour_day_checks[]"]').forEach(cb => cb.checked = false);
    if (days && Array.isArray(days)) {
        days.forEach(d => {
            const cb = document.querySelector(`input[name="tour_day_checks[]"][value="${d}"]`);
            if (cb) cb.checked = true;
        });
    }
}

async function editTour(id) {
    try {
        const response = await BornAdmin.api(`/api/optional-tours.php?action=get&id=${id}`);
        const tour = response.data;

        document.getElementById('tour-modal-title').textContent = '선택관광 수정';
        document.getElementById('tour-id').value = tour.id;
        document.getElementById('tour-name').value = tour.tour_name;
        document.getElementById('tour-price').value = tour.price;
        document.getElementById('tour-duration').value = tour.duration || '';
        document.getElementById('tour-meeting-time').value = tour.meeting_time || '';
        document.getElementById('tour-description').value = tour.description || '';
        document.getElementById('tour-notice').value = tour.notice || '';
        document.getElementById('tour-status').value = tour.status;

        // 일차 로드
        const tourDays = tour.tour_dates ? JSON.parse(tour.tour_dates) : [];
        loadTourDays(tourDays);

        BornAdmin.openModal('tour-modal');
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function saveTour(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('tour-form'));
    const data = Object.fromEntries(formData.entries());
    data.action = data.id ? 'update' : 'create';

    // 일차 체크박스 수집
    const tourDays = formData.getAll('tour_day_checks[]').filter(d => d);
    data.tour_dates = tourDays;
    delete data['tour_day_checks[]'];

    try {
        await BornAdmin.api('/api/optional-tours.php', {
            method: 'POST',
            body: data
        });
        BornAdmin.toast('저장되었습니다.', 'success');
        BornAdmin.closeModal('tour-modal');
        setTimeout(() => location.reload(), 500);
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function deleteTour(id) {
    if (!await BornAdmin.confirmDelete('이 선택관광')) return;

    try {
        await BornAdmin.api('/api/optional-tours.php', {
            method: 'POST',
            body: { action: 'delete', id: id }
        });
        BornAdmin.toast('삭제되었습니다.', 'success');
        document.querySelector(`tr[data-id="${id}"]`).remove();
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
