<?php
/**
 * 본투어 인터내셔날 - 호텔 에디터
 */

$pageTitle = '호텔 에디터';
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

// 호텔 목록
$hotels = [];
if ($event) {
    $stmt = $db->prepare("SELECT * FROM hotels WHERE event_id = ? ORDER BY sort_order ASC, check_in_date ASC");
    $stmt->execute([$eventId]);
    $hotels = $stmt->fetchAll();
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
                <button type="button" class="btn btn-primary" onclick="openHotelModal()">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    호텔 추가
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($event): ?>
    <!-- 호텔 목록 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= h($event['event_name']) ?> 호텔 (<?= count($hotels) ?>개)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($hotels)): ?>
                <div style="padding: 60px 20px; text-align: center; color: var(--gray-500);">
                    등록된 호텔이 없습니다.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>순서</th>
                                <th>호텔명</th>
                                <th>주소</th>
                                <th>연락처</th>
                                <th>체크인</th>
                                <th>체크아웃</th>
                                <th style="width: 100px;">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hotels as $hotel): ?>
                                <tr data-id="<?= $hotel['id'] ?>">
                                    <td><?= $hotel['sort_order'] ?></td>
                                    <td>
                                        <strong><?= h($hotel['hotel_name']) ?></strong>
                                        <?php if ($hotel['description']): ?>
                                            <p style="font-size: 12px; color: var(--gray-500); margin-top: 4px;">
                                                <?= h(mb_substr($hotel['description'], 0, 40)) ?><?= mb_strlen($hotel['description']) > 40 ? '...' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 13px;"><?= h($hotel['address'] ?: '-') ?></td>
                                    <td style="font-size: 13px;"><?= h($hotel['phone'] ?: '-') ?></td>
                                    <td style="font-size: 13px;">
                                        <?= $hotel['check_in_date'] ? date('m.d', strtotime($hotel['check_in_date'])) : '-' ?>
                                        <?= $hotel['check_in_time'] ? date('H:i', strtotime($hotel['check_in_time'])) : '' ?>
                                    </td>
                                    <td style="font-size: 13px;">
                                        <?= $hotel['check_out_date'] ? date('m.d', strtotime($hotel['check_out_date'])) : '-' ?>
                                        <?= $hotel['check_out_time'] ? date('H:i', strtotime($hotel['check_out_time'])) : '' ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px;">
                                            <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="editHotel(<?= $hotel['id'] ?>)" title="수정">
                                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="deleteHotel(<?= $hotel['id'] ?>)" title="삭제" style="color: var(--error);">
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

<!-- 호텔 모달 -->
<div class="modal-backdrop" id="hotel-modal">
    <div class="modal" style="max-width: 640px;">
        <div class="modal-header">
            <h3 class="modal-title" id="hotel-modal-title">호텔 추가</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('hotel-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <form id="hotel-form" onsubmit="saveHotel(event)">
            <input type="hidden" name="id" id="hotel-id">
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">호텔명 (한글) <span class="required">*</span></label>
                    <input type="text" name="hotel_name" id="hotel-name" class="form-input" required placeholder="빌라폰테뉴 그랜드 하네다호텔">
                </div>

                <div style="display: grid; grid-template-columns: 1fr auto; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">호텔명 (영문)</label>
                        <input type="text" name="hotel_name_en" id="hotel-name-en" class="form-input" placeholder="Hotel Villa Fontaine Grand Haneda Airport">
                    </div>
                    <div class="form-group">
                        <label class="form-label">성급</label>
                        <select name="star_rating" id="hotel-star-rating" class="form-select" style="width: 100px;">
                            <option value="0">없음</option>
                            <option value="1">1성급</option>
                            <option value="2">2성급</option>
                            <option value="3">3성급</option>
                            <option value="4">4성급</option>
                            <option value="5">5성급</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">주소</label>
                    <input type="text" name="address" id="hotel-address" class="form-input" placeholder="호텔 주소를 입력하세요">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">연락처</label>
                        <input type="text" name="phone" id="hotel-phone" class="form-input" placeholder="호텔 전화번호">
                    </div>
                    <div class="form-group">
                        <label class="form-label">정렬 순서</label>
                        <input type="number" name="sort_order" id="hotel-sort-order" class="form-input" min="0" value="0">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">체크인 날짜</label>
                        <input type="date" name="check_in_date" id="hotel-check-in-date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">체크인 시간</label>
                        <input type="time" name="check_in_time" id="hotel-check-in-time" class="form-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">체크아웃 날짜</label>
                        <input type="date" name="check_out_date" id="hotel-check-out-date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">체크아웃 시간</label>
                        <input type="time" name="check_out_time" id="hotel-check-out-time" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">설명</label>
                    <textarea name="description" id="hotel-description" class="form-textarea" rows="3" maxlength="2000" placeholder="호텔 소개 등"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">시설 및 서비스</label>
                    <input type="text" name="facilities" id="hotel-facilities" class="form-input" placeholder="쉼표로 구분 (예: Wi-Fi, 수영장, 조식, 헬스장, 주차)">
                    <span style="font-size: 12px; color: var(--gray-500);">쉼표(,)로 구분하여 입력</span>
                </div>

                <div class="form-group">
                    <label class="form-label">부대시설</label>
                    <textarea name="amenities" id="hotel-amenities" class="form-textarea" rows="4" maxlength="2000" placeholder="줄바꿈으로 항목 구분&#10;예:&#10;피트니스센터 (B1층)&#10;수영장 (3층 야외)&#10;비즈니스센터 (1층 로비)&#10;코인 세탁실 (각 층)"></textarea>
                    <span style="font-size: 12px; color: var(--gray-500);">줄바꿈으로 항목을 구분합니다</span>
                </div>

                <div class="form-group">
                    <label class="form-label">부대시설 운영시간</label>
                    <textarea name="amenities_hours" id="hotel-amenities-hours" class="form-textarea" rows="4" maxlength="2000" placeholder="줄바꿈으로 항목 구분&#10;예:&#10;피트니스센터: 06:00~22:00&#10;수영장: 09:00~21:00 (하절기)&#10;비즈니스센터: 24시간&#10;조식: 07:00~10:00 (1층 레스토랑)"></textarea>
                    <span style="font-size: 12px; color: var(--gray-500);">줄바꿈으로 항목을 구분합니다</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">지도 URL</label>
                        <input type="url" name="map_url" id="hotel-map-url" class="form-input" placeholder="Google Maps 링크">
                    </div>
                    <div class="form-group">
                        <label class="form-label">상세보기 URL</label>
                        <input type="url" name="detail_url" id="hotel-detail-url" class="form-input" placeholder="호텔 상세 페이지 링크">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">이미지 URL</label>
                    <input type="url" name="image_url" id="hotel-image-url" class="form-input" placeholder="호텔 대표 이미지 URL">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('hotel-modal')">취소</button>
                <button type="submit" class="btn btn-primary">저장</button>
            </div>
        </form>
    </div>
</div>

<script>
const eventId = <?= $eventId ?: 'null' ?>;

function changeEvent(id) {
    if (id) {
        window.location.href = `/admin/hotel.php?event_id=${id}`;
    }
}

function openHotelModal() {
    document.getElementById('hotel-modal-title').textContent = '호텔 추가';
    document.getElementById('hotel-form').reset();
    document.getElementById('hotel-id').value = '';
    document.getElementById('hotel-sort-order').value = '0';
    BornAdmin.openModal('hotel-modal');
}

async function editHotel(id) {
    try {
        const response = await BornAdmin.api(`/api/hotels.php?action=get&id=${id}`);
        const hotel = response.data;

        document.getElementById('hotel-modal-title').textContent = '호텔 수정';
        document.getElementById('hotel-id').value = hotel.id;
        document.getElementById('hotel-name').value = hotel.hotel_name;
        document.getElementById('hotel-name-en').value = hotel.hotel_name_en || '';
        document.getElementById('hotel-star-rating').value = hotel.star_rating || 0;
        document.getElementById('hotel-address').value = hotel.address || '';
        document.getElementById('hotel-phone').value = hotel.phone || '';
        document.getElementById('hotel-sort-order').value = hotel.sort_order || 0;
        document.getElementById('hotel-check-in-date').value = hotel.check_in_date || '';
        document.getElementById('hotel-check-in-time').value = hotel.check_in_time || '';
        document.getElementById('hotel-check-out-date').value = hotel.check_out_date || '';
        document.getElementById('hotel-check-out-time').value = hotel.check_out_time || '';
        document.getElementById('hotel-description').value = hotel.description || '';
        document.getElementById('hotel-facilities').value = hotel.facilities || '';
        document.getElementById('hotel-amenities').value = hotel.amenities || '';
        document.getElementById('hotel-amenities-hours').value = hotel.amenities_hours || '';
        document.getElementById('hotel-map-url').value = hotel.map_url || '';
        document.getElementById('hotel-detail-url').value = hotel.detail_url || '';
        document.getElementById('hotel-image-url').value = hotel.image_url || '';

        BornAdmin.openModal('hotel-modal');
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function saveHotel(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('hotel-form'));
    const data = Object.fromEntries(formData.entries());
    data.action = data.id ? 'update' : 'create';

    try {
        await BornAdmin.api('/api/hotels.php', {
            method: 'POST',
            body: data
        });
        BornAdmin.toast('저장되었습니다.', 'success');
        BornAdmin.closeModal('hotel-modal');
        setTimeout(() => location.reload(), 500);
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function deleteHotel(id) {
    if (!await BornAdmin.confirmDelete('이 호텔')) return;

    try {
        await BornAdmin.api('/api/hotels.php', {
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
