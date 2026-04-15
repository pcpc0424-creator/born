<?php
/**
 * 본투어 인터내셔날 - 일정표 에디터
 */

$pageTitle = '일정표 에디터';
require_once __DIR__ . '/../includes/header.php';

$db = db();

// 행사 목록
$stmt = $db->query("SELECT id, event_name, start_date, end_date, status FROM events ORDER BY start_date DESC");
$events = $stmt->fetchAll();
?>

<style>
    .schedule-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
        min-height: calc(100vh - 160px);
    }

    .event-list-panel {
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--gray-200);
        overflow: hidden;
    }

    .event-list-panel .panel-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--gray-200);
        font-size: 14px;
        font-weight: 700;
        color: var(--gray-700);
    }

    .event-list-panel .event-item {
        display: block;
        padding: 14px 20px;
        border-bottom: 1px solid var(--gray-100);
        text-decoration: none;
        color: var(--gray-700);
        transition: background 0.2s;
        cursor: pointer;
    }

    .event-list-panel .event-item:hover,
    .event-list-panel .event-item.active {
        background: var(--primary-50);
    }

    .event-list-panel .event-item.active {
        border-left: 3px solid var(--primary-600);
    }

    .event-list-panel .event-item h4 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .event-list-panel .event-item .event-meta {
        font-size: 12px;
        color: var(--gray-500);
    }

    .schedule-editor-panel {
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--gray-200);
        overflow: hidden;
    }

    .schedule-editor-panel .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 24px;
        border-bottom: 1px solid var(--gray-200);
    }

    .schedule-editor-panel .panel-header h3 {
        font-size: 16px;
        font-weight: 700;
        color: var(--gray-800);
    }

    .schedule-editor-panel .panel-body {
        padding: 24px;
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 80px 24px;
        color: var(--gray-400);
        text-align: center;
    }

    .empty-state svg {
        width: 64px;
        height: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state p {
        font-size: 15px;
    }

    /* Day card */
    .day-card {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
        padding: 20px;
        margin-bottom: 16px;
    }

    .day-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .day-card-header h4 {
        font-size: 15px;
        font-weight: 700;
        color: var(--primary-700);
    }

    .day-card-header .day-date {
        font-size: 13px;
        color: var(--gray-500);
        margin-left: 8px;
        font-weight: 400;
    }

    .day-card-header .btn-remove-day {
        background: none;
        border: none;
        color: var(--gray-400);
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s;
    }

    .day-card-header .btn-remove-day:hover {
        color: #e53e3e;
        background: #fee;
    }

    .day-fields {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 16px;
    }

    .day-fields .field-full {
        grid-column: 1 / -1;
    }

    .day-fields label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-600);
        margin-bottom: 4px;
    }

    .day-fields input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-family: inherit;
        transition: border-color 0.2s;
    }

    .day-fields input:focus {
        outline: none;
        border-color: var(--primary-500);
    }

    .items-section {
        border-top: 1px solid var(--gray-200);
        padding-top: 12px;
    }

    .items-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .items-section-header h5 {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-600);
    }

    .item-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
        margin-bottom: 10px;
        align-items: start;
        background: var(--white);
        padding: 10px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
    }

    .item-row .item-fields {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .item-row input,
    .item-row textarea {
        padding: 8px 12px;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-family: inherit;
        width: 100%;
    }

    .item-row textarea {
        resize: vertical;
        min-height: 36px;
        line-height: 1.5;
    }

    .item-row input:focus,
    .item-row textarea:focus {
        outline: none;
        border-color: var(--primary-500);
    }

    .btn-remove-item {
        background: none;
        border: none;
        color: var(--gray-400);
        cursor: pointer;
        padding: 8px 4px;
    }

    .btn-remove-item:hover {
        color: #e53e3e;
    }

    .btn-add-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: none;
        border: 1px dashed var(--gray-300);
        color: var(--gray-500);
        padding: 6px 12px;
        border-radius: var(--radius-sm);
        font-size: 12px;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s;
    }

    .btn-add-item:hover {
        border-color: var(--primary-400);
        color: var(--primary-600);
    }

    .meal-fields {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        border-top: 1px solid var(--gray-200);
        padding-top: 12px;
        margin-top: 12px;
    }

    .meal-fields label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-600);
        margin-bottom: 4px;
    }

    .meal-fields input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-family: inherit;
    }

    .meal-fields input:focus {
        outline: none;
        border-color: var(--primary-500);
    }

    .btn-add-day {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 14px;
        border: 2px dashed var(--gray-300);
        border-radius: var(--radius-md);
        background: none;
        color: var(--gray-500);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s;
    }

    .btn-add-day:hover {
        border-color: var(--primary-400);
        color: var(--primary-600);
        background: var(--primary-50);
    }

    .schedule-actions {
        display: flex;
        gap: 12px;
    }

    .schedule-actions .btn {
        padding: 8px 20px;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s;
    }

    .schedule-actions .btn-primary {
        background: var(--primary-600);
        color: white;
    }

    .schedule-actions .btn-primary:hover {
        background: var(--primary-700);
    }

    @media (max-width: 1024px) {
        .schedule-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="schedule-layout">
    <!-- 행사 목록 -->
    <div class="event-list-panel">
        <div class="panel-header">행사 선택</div>
        <?php foreach ($events as $ev): ?>
            <div class="event-item" data-event-id="<?= $ev['id'] ?>" data-start-date="<?= $ev['start_date'] ?>" onclick="loadSchedule(<?= $ev['id'] ?>, '<?= $ev['start_date'] ?>')">
                <h4><?= h($ev['event_name']) ?></h4>
                <div class="event-meta">
                    <?= format_date_range($ev['start_date'], $ev['end_date']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 일정표 에디터 -->
    <div class="schedule-editor-panel">
        <div class="panel-header">
            <h3 id="editor-title">일정표 편집</h3>
            <div class="schedule-actions" id="schedule-actions" style="display: none;">
                <button class="btn btn-primary" onclick="saveSchedule()">저장</button>
            </div>
        </div>
        <div class="panel-body" id="editor-body">
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p>왼쪽에서 행사를 선택해 주세요</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentEventId = null;
let currentStartDate = null;
let eventHotels = [];

function loadSchedule(eventId, startDate) {
    currentEventId = eventId;
    currentStartDate = startDate;

    document.querySelectorAll('.event-item').forEach(el => el.classList.remove('active'));
    document.querySelector(`.event-item[data-event-id="${eventId}"]`).classList.add('active');
    document.getElementById('schedule-actions').style.display = 'flex';

    // 호텔 목록과 일정표를 동시에 로드
    Promise.all([
        fetch(`/api/schedule.php?action=get&event_id=${eventId}`).then(r => r.json()),
        fetch(`/api/hotels.php?action=list&event_id=${eventId}`).then(r => r.json()),
    ]).then(([scheduleRes, hotelsRes]) => {
        eventHotels = hotelsRes.success ? hotelsRes.data : [];
        if (scheduleRes.success) {
            renderEditor(scheduleRes.data);
        }
    });
}

function calcDate(dayNumber) {
    if (!currentStartDate) return '';
    const d = new Date(currentStartDate);
    d.setDate(d.getDate() + dayNumber - 1);
    const y = String(d.getFullYear()).slice(2);
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const weekdays = ['일', '월', '화', '수', '목', '금', '토'];
    return `${y}년${m}월${dd}일(${weekdays[d.getDay()]})`;
}

function renderEditor(days) {
    const container = document.getElementById('editor-body');

    if (!days || days.length === 0) {
        container.innerHTML = `
            <div id="days-container"></div>
            <button class="btn-add-day" onclick="addDay()">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
                일차 추가
            </button>`;
        addDay();
        return;
    }

    container.innerHTML = `
        <div id="days-container"></div>
        <button class="btn-add-day" onclick="addDay()">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
            </svg>
            일차 추가
        </button>`;

    days.forEach(day => {
        addDay(day);
    });
}

function addDay(data = null) {
    const container = document.getElementById('days-container');
    const dayCount = container.children.length + 1;
    const dayNumber = data ? data.day_number : dayCount;

    const div = document.createElement('div');
    div.className = 'day-card';
    div.dataset.dayNumber = dayNumber;

    const dateStr = calcDate(dayNumber);

    div.innerHTML = `
        <div class="day-card-header">
            <h4>${dayNumber}일차 <span class="day-date">${dateStr}</span></h4>
            <button class="btn-remove-day" onclick="removeDay(this)" title="삭제">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="day-fields">
            <div class="field-full">
                <label>지역</label>
                <input type="text" class="day-location" value="${escHtml(data?.location || '')}" placeholder="예: 도쿄">
            </div>
            <div class="field-full">
                <label>호텔</label>
                <div style="display:flex;gap:8px;">
                    <select class="day-hotel-select" onchange="onHotelSelect(this)" style="padding:8px 12px;border:1px solid var(--gray-300);border-radius:var(--radius-sm);font-size:13px;font-family:inherit;flex:1;">
                        <option value="">호텔 DB에서 선택 (선택사항)</option>
                        ${eventHotels.map(h => `<option value="${h.id}" ${data?.hotel_id == h.id ? 'selected' : ''}>${escHtml(h.hotel_name)}</option>`).join('')}
                    </select>
                    <input type="text" class="day-hotel" value="${escHtml(data?.hotel_name || '')}" placeholder="직접 입력" style="flex:1;">
                </div>
                <input type="hidden" class="day-hotel-id" value="${data?.hotel_id || ''}">
            </div>
        </div>

        <div class="items-section">
            <div class="items-section-header">
                <h5>관광지 / 일정 항목</h5>
                <button class="btn-add-item" onclick="addItem(this)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    항목 추가
                </button>
            </div>
            <div class="items-list">
                ${(data?.items || []).map(item => itemRowHtml(item)).join('')}
            </div>
        </div>

        <div class="meal-fields">
            <div>
                <label>조식</label>
                <input type="text" class="meal-breakfast" value="${escHtml(data?.meal_breakfast || '')}" placeholder="호텔식">
            </div>
            <div>
                <label>중식</label>
                <input type="text" class="meal-lunch" value="${escHtml(data?.meal_lunch || '')}" placeholder="현지식">
            </div>
            <div>
                <label>석식</label>
                <input type="text" class="meal-dinner" value="${escHtml(data?.meal_dinner || '')}" placeholder="호텔식">
            </div>
        </div>
    `;

    container.appendChild(div);

    // 항목이 없으면 기본 1개 추가
    if (!data || !data.items || data.items.length === 0) {
        const addBtn = div.querySelector('.btn-add-item');
        addItem(addBtn);
    }
}

function itemRowHtml(item) {
    return `
        <div class="item-row">
            <div class="item-fields">
                <input type="text" class="item-title" value="${escHtml(item?.title || '')}" placeholder="관광지/일정 이름">
                <textarea class="item-desc" rows="2" maxlength="500" placeholder="설명 (선택, 최대 500자)">${escHtml(item?.description || '')}</textarea>
            </div>
            <button class="btn-remove-item" onclick="this.closest('.item-row').remove()" title="삭제">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    `;
}

function addItem(btn) {
    const list = btn.closest('.items-section').querySelector('.items-list');
    list.insertAdjacentHTML('beforeend', itemRowHtml(null));
}

function removeDay(btn) {
    if (!confirm('이 일차를 삭제하시겠습니까?')) return;
    btn.closest('.day-card').remove();
    renumberDays();
}

function renumberDays() {
    const container = document.getElementById('days-container');
    const cards = container.querySelectorAll('.day-card');
    cards.forEach((card, idx) => {
        const num = idx + 1;
        card.dataset.dayNumber = num;
        const dateStr = calcDate(num);
        card.querySelector('h4').innerHTML = `${num}일차 <span class="day-date">${dateStr}</span>`;
    });
}

function onHotelSelect(sel) {
    const card = sel.closest('.day-card');
    const hotelInput = card.querySelector('.day-hotel');
    const hotelIdInput = card.querySelector('.day-hotel-id');
    if (sel.value) {
        const hotel = eventHotels.find(h => h.id == sel.value);
        if (hotel) {
            hotelInput.value = hotel.hotel_name;
            hotelIdInput.value = hotel.id;
        }
    } else {
        hotelIdInput.value = '';
    }
}

function collectData() {
    const days = [];
    document.querySelectorAll('.day-card').forEach(card => {
        const items = [];
        card.querySelectorAll('.item-row').forEach(row => {
            const title = row.querySelector('.item-title').value.trim();
            if (title) {
                items.push({
                    title: title,
                    description: row.querySelector('.item-desc').value.trim(),
                });
            }
        });

        days.push({
            day_number: parseInt(card.dataset.dayNumber),
            location: card.querySelector('.day-location').value.trim(),
            hotel_name: card.querySelector('.day-hotel').value.trim(),
            hotel_id: card.querySelector('.day-hotel-id').value || null,
            meal_breakfast: card.querySelector('.meal-breakfast').value.trim(),
            meal_lunch: card.querySelector('.meal-lunch').value.trim(),
            meal_dinner: card.querySelector('.meal-dinner').value.trim(),
            items: items,
        });
    });
    return days;
}

function saveSchedule() {
    const days = collectData();

    fetch('/api/schedule.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'save',
            event_id: currentEventId,
            days: days,
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('일정표가 저장되었습니다.');
        } else {
            alert('저장 실패: ' + res.message);
        }
    })
    .catch(err => {
        alert('오류가 발생했습니다.');
    });
}

function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
