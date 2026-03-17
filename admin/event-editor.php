<?php
/**
 * 본투어 인터내셔날 - 행사 에디터
 */

$pageTitle = '행사 에디터';
require_once __DIR__ . '/../includes/header.php';

$db = db();

// 수정 모드 확인
$eventId = input('id');
$event = null;
$isEdit = false;

if ($eventId) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    $isEdit = (bool)$event;
}

// 행사 목록 (왼쪽 사이드)
$stmt = $db->query("
    SELECT e.*,
           (SELECT COUNT(*) FROM event_members WHERE event_id = e.id) as member_count
    FROM events e
    ORDER BY e.start_date DESC
");
$events = $stmt->fetchAll();
?>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 24px;">
    <!-- 행사 목록 -->
    <div class="card">
        <div class="card-header" style="flex-direction: column; align-items: stretch; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">행사 목록</h3>
                <button type="button" class="btn btn-sm btn-primary" onclick="newEvent()">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    새 행사
                </button>
            </div>
            <input type="text" class="form-input" id="event-search" placeholder="행사명 검색..." style="font-size: 13px;">
        </div>
        <div class="card-body" style="padding: 0; max-height: calc(100vh - 280px); overflow-y: auto;">
            <div id="event-list">
                <?php if (empty($events)): ?>
                    <div style="padding: 40px 20px; text-align: center; color: var(--gray-500);">
                        등록된 행사가 없습니다.
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $ev): ?>
                        <?php $dday = calculate_dday($ev['start_date']); ?>
                        <div class="event-list-item <?= $eventId == $ev['id'] ? 'active' : '' ?>" data-id="<?= $ev['id'] ?>" onclick="loadEvent(<?= $ev['id'] ?>)">
                            <div class="event-list-info">
                                <h4><?= h($ev['event_name']) ?></h4>
                                <p>
                                    <?= date('m.d', strtotime($ev['start_date'])) ?> ~ <?= date('m.d', strtotime($ev['end_date'])) ?>
                                    <span class="badge <?= $ev['status'] === 'active' ? 'badge-success' : 'badge-gray' ?>" style="margin-left: 8px; font-size: 10px;">
                                        <?= $ev['status'] === 'active' ? '활성' : '비활성' ?>
                                    </span>
                                </p>
                            </div>
                            <div class="event-list-meta">
                                <span class="dday <?= $dday['isPast'] ? 'past' : '' ?>"><?= $dday['text'] ?></span>
                                <span class="count"><?= $ev['member_count'] ?>명</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 행사 상세/수정 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" id="form-title"><?= $isEdit ? '행사 수정' : '새 행사 등록' ?></h3>
            <?php if ($isEdit): ?>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="showQRCode()">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                        </svg>
                        QR 코드
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="copyEventLink()">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                        </svg>
                        링크 복사
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteEvent(<?= $eventId ?>)">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        삭제
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <form id="event-form" onsubmit="saveEvent(event)">
            <input type="hidden" name="id" id="event-id" value="<?= $event['id'] ?? '' ?>">
            <div class="card-body" style="max-height: calc(100vh - 280px); overflow-y: auto;">
                <!-- 기본 정보 -->
                <div style="background: var(--gray-50); padding: 20px; border-radius: var(--radius-md); margin-bottom: 24px;">
                    <h4 style="font-size: 14px; font-weight: 600; color: var(--primary-700); margin-bottom: 16px;">기본 정보</h4>

                    <div class="form-group">
                        <label class="form-label">행사명 <span class="required">*</span></label>
                        <input type="text" name="event_name" id="event-name" class="form-input"
                               value="<?= h($event['event_name'] ?? '') ?>" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">시작일 <span class="required">*</span></label>
                            <input type="date" name="start_date" id="event-start-date" class="form-input"
                                   value="<?= $event['start_date'] ?? '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">종료일 <span class="required">*</span></label>
                            <input type="date" name="end_date" id="event-end-date" class="form-input"
                                   value="<?= $event['end_date'] ?? '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">상태</label>
                            <select name="status" id="event-status" class="form-select">
                                <option value="active" <?= ($event['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>활성</option>
                                <option value="inactive" <?= ($event['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>비활성</option>
                            </select>
                        </div>
                    </div>

                    <!-- 추가 출발일 -->
                    <div class="form-group">
                        <label class="form-label">
                            추가 출발일
                            <small style="color: var(--gray-500); font-weight: normal;">(다수 출발일이 있는 경우)</small>
                        </label>
                        <div id="additional-dates-container">
                            <?php
                            $additionalDates = [];
                            if (!empty($event['additional_start_dates'])) {
                                $additionalDates = json_decode($event['additional_start_dates'], true) ?: [];
                            }
                            foreach ($additionalDates as $idx => $date):
                            ?>
                            <div class="additional-date-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="date" name="additional_start_dates[]" class="form-input" value="<?= h($date) ?>" style="flex: 1;">
                                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">삭제</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addAdditionalDate()">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            출발일 추가
                        </button>
                    </div>

                    <div class="form-group">
                        <label class="form-label">거래처 로고</label>
                        <input type="file" name="client_logo" id="event-logo" class="form-input" accept="image/*">
                        <?php if (!empty($event['client_logo'])): ?>
                            <div style="margin-top: 8px;">
                                <img src="/born/uploads/logos/<?= h($event['client_logo']) ?>" alt="" style="max-height: 40px;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 항공 정보 -->
                <div style="background: var(--gray-50); padding: 20px; border-radius: var(--radius-md); margin-bottom: 24px;">
                    <h4 style="font-size: 14px; font-weight: 600; color: var(--primary-700); margin-bottom: 16px;">항공 정보</h4>

                    <div class="form-group">
                        <label class="form-label">항공사</label>
                        <input type="text" name="airline" id="event-airline" class="form-input"
                               value="<?= h($event['airline'] ?? '') ?>" placeholder="대한항공, 아시아나 등">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">출국편</label>
                            <input type="text" name="flight_departure" id="event-flight-dep" class="form-input"
                                   value="<?= h($event['flight_departure'] ?? '') ?>" placeholder="KE001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">귀국편</label>
                            <input type="text" name="flight_return" id="event-flight-ret" class="form-input"
                                   value="<?= h($event['flight_return'] ?? '') ?>" placeholder="KE002">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">출발 시간</label>
                            <input type="time" name="flight_time_departure" id="event-time-dep" class="form-input"
                                   value="<?= $event['flight_time_departure'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">도착 시간</label>
                            <input type="time" name="flight_time_return" id="event-time-ret" class="form-input"
                                   value="<?= $event['flight_time_return'] ?? '' ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">출발 공항</label>
                            <input type="text" name="departure_airport" id="event-airport-dep" class="form-input"
                                   value="<?= h($event['departure_airport'] ?? '인천국제공항') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">도착 공항</label>
                            <input type="text" name="arrival_airport" id="event-airport-arr" class="form-input"
                                   value="<?= h($event['arrival_airport'] ?? '') ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">도착지 시차</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="timezone_offset" id="event-timezone" class="form-input"
                                       value="<?= $event['timezone_offset'] ?? 0 ?>" min="-12" max="14" style="width: 80px;">
                                <span style="color: var(--gray-600); font-size: 13px;">시간 (한국 기준)</span>
                            </div>
                            <span class="form-hint">예: 일본 0, 베트남 -2, 유럽 -8</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">출국 비행시간</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="flight_duration_departure" id="event-duration-dep" class="form-input"
                                       value="<?= $event['flight_duration_departure'] ?? '' ?>" min="0" style="width: 80px;">
                                <span style="color: var(--gray-600); font-size: 13px;">분</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">귀국 비행시간</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="flight_duration_return" id="event-duration-ret" class="form-input"
                                       value="<?= $event['flight_duration_return'] ?? '' ?>" min="0" style="width: 80px;">
                                <span style="color: var(--gray-600); font-size: 13px;">분</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">수하물 규정</label>
                        <div class="expandable-textarea-wrapper" onclick="openTextModal('event-baggage', '수하물 규정', 250)">
                            <textarea name="baggage_info" id="event-baggage" class="form-textarea expandable-textarea" rows="3"
                                      maxlength="250" placeholder="클릭하여 내용 입력 (최대 250자)" readonly><?= h($event['baggage_info'] ?? '') ?></textarea>
                            <div class="expand-hint">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 3 21 3 21 9"/>
                                    <polyline points="9 21 3 21 3 15"/>
                                    <line x1="21" y1="3" x2="14" y2="10"/>
                                    <line x1="3" y1="21" x2="10" y2="14"/>
                                </svg>
                                최대 250자
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">항공 기타사항</label>
                        <div class="expandable-textarea-wrapper" onclick="openTextModal('event-flight-etc', '항공 기타사항', 500)">
                            <textarea name="flight_etc" id="event-flight-etc" class="form-textarea expandable-textarea" rows="3"
                                      maxlength="500" placeholder="클릭하여 내용 입력 (최대 500자)" readonly><?= h($event['flight_etc'] ?? '') ?></textarea>
                            <div class="expand-hint">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 3 21 3 21 9"/>
                                    <polyline points="9 21 3 21 3 15"/>
                                    <line x1="21" y1="3" x2="14" y2="10"/>
                                    <line x1="3" y1="21" x2="10" y2="14"/>
                                </svg>
                                최대 500자
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 미팅 정보 -->
                <div style="background: var(--gray-50); padding: 20px; border-radius: var(--radius-md); margin-bottom: 24px;">
                    <h4 style="font-size: 14px; font-weight: 600; color: var(--primary-700); margin-bottom: 16px;">공항 미팅 정보</h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">미팅 일자</label>
                            <input type="date" name="meeting_date" id="event-meeting-date" class="form-input"
                                   value="<?= $event['meeting_date'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">미팅 시간</label>
                            <input type="time" name="meeting_time" id="event-meeting-time" class="form-input"
                                   value="<?= $event['meeting_time'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">미팅 장소</label>
                        <input type="text" name="meeting_place" id="event-meeting-place" class="form-input"
                               value="<?= h($event['meeting_place'] ?? '') ?>" placeholder="인천공항 제1터미널 3층 H카운터 앞">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">담당자명</label>
                            <input type="text" name="meeting_manager" id="event-manager" class="form-input"
                                   value="<?= h($event['meeting_manager'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">담당자 연락처</label>
                            <input type="tel" name="manager_phone" id="event-manager-phone" class="form-input"
                                   value="<?= h($event['manager_phone'] ?? '') ?>" placeholder="010-0000-0000">
                        </div>
                    </div>
                </div>

                <!-- 외부 링크 -->
                <div style="background: var(--gray-50); padding: 20px; border-radius: var(--radius-md); margin-bottom: 24px;">
                    <h4 style="font-size: 14px; font-weight: 600; color: var(--primary-700); margin-bottom: 16px;">외부 링크</h4>

                    <div class="form-group">
                        <label class="form-label">행사 일정 URL</label>
                        <input type="url" name="schedule_url" id="event-schedule-url" class="form-input"
                               value="<?= h($event['schedule_url'] ?? '') ?>" placeholder="https://...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">호텔 정보 URL</label>
                        <input type="url" name="hotel_url" id="event-hotel-url" class="form-input"
                               value="<?= h($event['hotel_url'] ?? '') ?>" placeholder="https://...">
                    </div>
                </div>

                <!-- 기타 정보 -->
                <div style="background: var(--gray-50); padding: 20px; border-radius: var(--radius-md);">
                    <h4 style="font-size: 14px; font-weight: 600; color: var(--primary-700); margin-bottom: 16px;">기타 정보</h4>

                    <div class="form-group">
                        <label class="form-label">여행 전 유의사항</label>
                        <div class="expandable-textarea-wrapper" onclick="openTextModal('event-notice', '여행 전 유의사항', 1000)">
                            <textarea name="travel_notice" id="event-notice" class="form-textarea expandable-textarea" rows="5"
                                      maxlength="1000" placeholder="클릭하여 내용 입력 (최대 1000자)" readonly><?= h($event['travel_notice'] ?? '') ?></textarea>
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

                    <div class="form-group">
                        <label class="form-label">날씨 이미지</label>
                        <input type="file" name="weather_image" id="event-weather" class="form-input" accept="image/*">
                        <?php if (!empty($event['weather_image'])): ?>
                            <div style="margin-top: 8px;">
                                <img src="/born/uploads/weather/<?= h($event['weather_image']) ?>" alt="" style="max-width: 200px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isEdit && $event['unique_code']): ?>
                        <div class="form-group">
                            <label class="form-label">고유 링크</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <?php
                                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                    $fullUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/born/e/' . $event['unique_code'];
                                ?>
                                <code style="flex: 1; padding: 12px; background: var(--white); border-radius: var(--radius-sm); font-size: 13px;" id="event-link">
                                    <?= h($fullUrl) ?>
                                </code>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="copyEventLink()">복사</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary" id="save-btn">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    저장
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.event-list-item {
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s ease;
}
.event-list-item:hover {
    background: var(--gray-50);
}
.event-list-item.active {
    background: var(--primary-50);
    border-left: 3px solid var(--primary-600);
}
.event-list-item h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 4px;
}
.event-list-item p {
    font-size: 12px;
    color: var(--gray-500);
}
.event-list-meta {
    text-align: right;
}
.event-list-meta .dday {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary-600);
}
.event-list-meta .dday.past {
    color: var(--gray-400);
}
.event-list-meta .count {
    font-size: 11px;
    color: var(--gray-400);
}

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

<!-- QR 코드 모달 -->
<div class="modal-backdrop" id="qr-modal">
    <div class="modal" style="max-width: 420px;">
        <div class="modal-header">
            <h3 class="modal-title">행사 QR 코드</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('qr-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <div class="modal-body" style="text-align: center;">
            <h4 id="qr-event-name" style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: var(--gray-800);"></h4>
            <p id="qr-event-url" style="font-size: 12px; color: var(--gray-500); margin-bottom: 20px; word-break: break-all;"></p>

            <div style="background: white; padding: 20px; border-radius: var(--radius-md); display: inline-block; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <img id="qr-image" src="" alt="QR Code" style="width: 250px; height: 250px;">
            </div>

            <p style="font-size: 13px; color: var(--gray-600); margin-top: 16px;">
                이 QR 코드를 스캔하면 여행자용 페이지로 이동합니다.
            </p>
        </div>
        <div class="modal-footer" style="justify-content: center; gap: 12px;">
            <button type="button" class="btn btn-secondary" onclick="copyEventLink(); BornAdmin.closeModal('qr-modal');">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                링크 복사
            </button>
            <a id="qr-download-link" href="" download class="btn btn-primary" onclick="downloadQRCode(); return false;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                다운로드
            </a>
        </div>
    </div>
</div>

<!-- 텍스트 확장 입력 모달 -->
<div class="modal-backdrop" id="text-expand-modal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3 class="modal-title" id="text-expand-title">내용 입력</h3>
            <span class="modal-close" onclick="closeTextModal()">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <div class="modal-body">
            <textarea id="text-expand-input" class="form-textarea" rows="12" style="font-size: 15px; line-height: 1.7;"></textarea>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                <span id="text-expand-count" style="font-size: 13px; color: var(--gray-500);">0 / 0 자</span>
                <span style="font-size: 12px; color: var(--gray-400);">넓은 입력창에서 편하게 작성하세요</span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeTextModal()">취소</button>
            <button type="button" class="btn btn-primary" onclick="saveTextModal()">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                저장
            </button>
        </div>
    </div>
</div>

<script>
// 텍스트 확장 모달 관련 변수
let currentTextFieldId = null;
let currentMaxLength = 0;

// 텍스트 확장 모달 열기
function openTextModal(fieldId, title, maxLength) {
    currentTextFieldId = fieldId;
    currentMaxLength = maxLength;

    const field = document.getElementById(fieldId);
    const value = field.value;

    document.getElementById('text-expand-title').textContent = title + ' (최대 ' + maxLength + '자)';
    document.getElementById('text-expand-input').value = value;
    document.getElementById('text-expand-input').maxLength = maxLength;
    updateTextCount();

    BornAdmin.openModal('text-expand-modal');

    // 포커스
    setTimeout(() => {
        document.getElementById('text-expand-input').focus();
    }, 100);
}

// 텍스트 카운트 업데이트
function updateTextCount() {
    const input = document.getElementById('text-expand-input');
    const count = input.value.length;
    document.getElementById('text-expand-count').textContent = count + ' / ' + currentMaxLength + ' 자';

    // 경고 색상
    if (count >= currentMaxLength * 0.9) {
        document.getElementById('text-expand-count').style.color = 'var(--danger-600)';
    } else if (count >= currentMaxLength * 0.7) {
        document.getElementById('text-expand-count').style.color = 'var(--warning-600)';
    } else {
        document.getElementById('text-expand-count').style.color = 'var(--gray-500)';
    }
}

// 텍스트 모달 저장
function saveTextModal() {
    const input = document.getElementById('text-expand-input');
    const field = document.getElementById(currentTextFieldId);

    field.value = input.value;
    closeTextModal();
    BornAdmin.toast('내용이 저장되었습니다.', 'success');
}

// 텍스트 모달 닫기
function closeTextModal() {
    BornAdmin.closeModal('text-expand-modal');
    currentTextFieldId = null;
}

// 입력 이벤트 리스너
document.getElementById('text-expand-input')?.addEventListener('input', updateTextCount);

// 행사 검색
document.getElementById('event-search').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    document.querySelectorAll('.event-list-item').forEach(item => {
        const name = item.querySelector('h4').textContent.toLowerCase();
        item.style.display = name.includes(query) ? '' : 'none';
    });
});

// 새 행사
function newEvent() {
    window.location.href = '/born/admin/event-editor.php';
}

// 행사 불러오기
function loadEvent(id) {
    window.location.href = `/born/admin/event-editor.php?id=${id}`;
}

// 행사 저장
async function saveEvent(e) {
    e.preventDefault();

    const form = document.getElementById('event-form');
    const formData = new FormData(form);
    formData.append('action', formData.get('id') ? 'update' : 'create');

    try {
        BornAdmin.showLoading('#save-btn');
        const response = await BornAdmin.api('/born/api/events.php', {
            method: 'POST',
            body: formData
        });

        BornAdmin.toast('저장되었습니다.', 'success');

        if (!formData.get('id') && response.data.id) {
            window.location.href = `/born/admin/event-editor.php?id=${response.data.id}`;
        } else {
            setTimeout(() => location.reload(), 500);
        }
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    } finally {
        BornAdmin.hideLoading('#save-btn');
    }
}

// 행사 삭제
async function deleteEvent(id) {
    if (!await BornAdmin.confirmDelete('이 행사')) return;

    try {
        await BornAdmin.api('/born/api/events.php', {
            method: 'POST',
            body: { action: 'delete', id: id }
        });

        BornAdmin.toast('삭제되었습니다.', 'success');
        window.location.href = '/born/admin/event-editor.php';
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

// 링크 복사
function copyEventLink() {
    const link = document.getElementById('event-link');
    if (link) {
        const url = link.textContent.trim();

        // navigator.clipboard API 사용 (HTTPS 환경)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(() => {
                BornAdmin.toast('링크가 복사되었습니다.', 'success');
            }).catch(() => {
                fallbackCopyText(url);
            });
        } else {
            // HTTP 환경을 위한 fallback
            fallbackCopyText(url);
        }
    }
}

// HTTP 환경용 복사 fallback
function fallbackCopyText(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    textArea.style.top = '-9999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        BornAdmin.toast('링크가 복사되었습니다.', 'success');
    } catch (err) {
        BornAdmin.toast('복사에 실패했습니다. 직접 복사해주세요.', 'error');
    }

    document.body.removeChild(textArea);
}

// QR 코드 표시
function showQRCode() {
    const link = document.getElementById('event-link');
    if (!link) {
        BornAdmin.toast('행사 링크가 없습니다.', 'error');
        return;
    }

    const eventUrl = 'https://' + link.textContent.trim();
    const eventName = document.getElementById('event-name').value || '행사';

    // QR 코드 생성 (QR Server API 사용)
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(eventUrl)}`;

    document.getElementById('qr-event-name').textContent = eventName;
    document.getElementById('qr-event-url').textContent = eventUrl;
    document.getElementById('qr-image').src = qrUrl;
    document.getElementById('qr-download-link').href = qrUrl;

    BornAdmin.openModal('qr-modal');
}

// QR 코드 다운로드
async function downloadQRCode() {
    const qrImage = document.getElementById('qr-image');
    const eventName = document.getElementById('qr-event-name').textContent;

    try {
        const response = await fetch(qrImage.src);
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `QR_${eventName.replace(/[^a-zA-Z0-9가-힣]/g, '_')}.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        BornAdmin.toast('QR 코드가 다운로드되었습니다.', 'success');
    } catch (error) {
        BornAdmin.toast('다운로드에 실패했습니다.', 'error');
    }
}

// 추가 출발일 추가
function addAdditionalDate() {
    const container = document.getElementById('additional-dates-container');
    const row = document.createElement('div');
    row.className = 'additional-date-row';
    row.style.cssText = 'display: flex; gap: 8px; margin-bottom: 8px;';
    row.innerHTML = `
        <input type="date" name="additional_start_dates[]" class="form-input" style="flex: 1;">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">삭제</button>
    `;
    container.appendChild(row);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
