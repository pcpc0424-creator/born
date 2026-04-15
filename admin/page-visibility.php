<?php
/**
 * 본투어 인터내셔날 - 페이지 노출 관리
 */

$pageTitle = '페이지 노출 관리';
require_once __DIR__ . '/../includes/header.php';

$db = db();

// 현재 선택된 행사
$eventId = input('event_id');
$event = null;
$visibility = null;

if ($eventId) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if ($event) {
        $stmt = $db->prepare("SELECT * FROM page_visibility WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $visibility = $stmt->fetch();
    }
}

// 행사 목록
$stmt = $db->query("SELECT id, event_name, start_date FROM events WHERE status = 'active' ORDER BY start_date DESC");
$events = $stmt->fetchAll();

// 페이지 목록
$pages = [
    'notice' => ['label' => '유의사항', 'desc' => '여행 전 유의사항 페이지'],
    'event_name' => ['label' => '행사명 표시', 'desc' => '메인 페이지에 행사명 표시'],
    'event_date' => ['label' => '행사 기간 표시', 'desc' => '메인 페이지에 행사 기간 표시'],
    'schedule' => ['label' => '일정표', 'desc' => '행사 일정표 링크'],
    'flight' => ['label' => '항공 스케줄', 'desc' => '항공편 정보 페이지'],
    'meeting' => ['label' => '공항 미팅', 'desc' => '미팅 장소/시간 페이지'],
    'hotel' => ['label' => '호텔 정보', 'desc' => '호텔 정보 링크'],
    'travel_notice' => ['label' => '유의사항 메뉴', 'desc' => '사이드바 유의사항 메뉴'],
    'reservation' => ['label' => '예약 상세', 'desc' => '예약 내역 페이지'],
    'passport_upload' => ['label' => '여권 업로드', 'desc' => '여권 사본 업로드 페이지'],
    'optional_tour' => ['label' => '선택관광', 'desc' => '선택관광 신청 페이지'],
    'survey' => ['label' => '설문', 'desc' => '설문 참여 페이지'],
    'announcements' => ['label' => '공지사항', 'desc' => '공지사항 목록 페이지'],
    'faq' => ['label' => '문의하기', 'desc' => 'FAQ/문의 페이지'],
];
?>

<!-- 행사 선택 -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body">
        <div class="form-group" style="margin-bottom: 0; max-width: 400px;">
            <select id="event-select" class="form-select" onchange="changeEvent(this.value)">
                <option value="">행사를 선택하세요</option>
                <?php foreach ($events as $ev): ?>
                    <option value="<?= $ev['id'] ?>" <?= $eventId == $ev['id'] ? 'selected' : '' ?>>
                        <?= h($ev['event_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<?php if ($event && $visibility): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= h($event['event_name']) ?> - 페이지 노출 설정</h3>
        </div>
        <div class="card-body">
            <p style="color: var(--gray-500); margin-bottom: 24px;">
                각 페이지/메뉴의 노출 여부를 설정할 수 있습니다. 비활성화된 페이지는 여행자에게 표시되지 않습니다.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                <?php foreach ($pages as $key => $page): ?>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 14px;"><?= $page['label'] ?></strong>
                            <p style="font-size: 12px; color: var(--gray-500); margin-top: 2px;"><?= $page['desc'] ?></p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="<?= $key ?>" <?= $visibility[$key] ? 'checked' : '' ?>
                                   onchange="updateVisibility('<?= $key ?>', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 80px 20px; color: var(--gray-500);">
            행사를 선택해주세요
        </div>
    </div>
<?php endif; ?>

<script>
const eventId = <?= $eventId ?: 'null' ?>;

function changeEvent(id) {
    if (id) {
        window.location.href = `/admin/page-visibility.php?event_id=${id}`;
    }
}

async function updateVisibility(page, visible) {
    try {
        await BornAdmin.api('/api/page-visibility.php', {
            method: 'POST',
            body: {
                action: 'update',
                event_id: eventId,
                page: page,
                visible: visible
            }
        });
        BornAdmin.toast('설정이 저장되었습니다.', 'success');
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
