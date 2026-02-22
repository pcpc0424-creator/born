<?php
/**
 * 본투어 인터내셔날 - 여권사본 확인
 */

$pageTitle = '여권사본 확인하기';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/encryption.php';

// 생년월일 DDMMYY 형식으로 변환
function formatBirthDateDDMMYY($date) {
    if (empty($date)) return '-';
    // YYYY-MM-DD or YYMMDD 형식을 DDMMYY로 변환
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        // 이미 DDMMYY 형식일 수 있음
        return $date;
    }
    return date('dmy', $timestamp);
}

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
$stmt = $db->query("SELECT id, event_name, start_date FROM events WHERE status = 'active' ORDER BY start_date DESC");
$events = $stmt->fetchAll();

// 여권 목록
$passports = [];
if ($event) {
    $stmt = $db->prepare("
        SELECT p.*, m.name_ko as member_name, m.phone as member_phone
        FROM passports p
        JOIN members m ON p.member_id = m.id
        WHERE p.event_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$eventId]);
    $passports = $stmt->fetchAll();

    // 복호화
    foreach ($passports as &$p) {
        $p['birth_date_decrypted'] = $p['birth_date_encrypted'] ? decrypt_sensitive($p['birth_date_encrypted']) : '';
        $p['passport_no_decrypted'] = $p['passport_no_encrypted'] ? decrypt_sensitive($p['passport_no_encrypted']) : '';
    }
}

// 전체 통계
$totalMembers = 0;
$submittedCount = 0;
if ($event) {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM event_members WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $totalMembers = $stmt->fetch()['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM passports WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $submittedCount = $stmt->fetch()['total'];
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
                            <?= h($ev['event_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($event): ?>
                <button type="button" class="btn btn-secondary" onclick="downloadPassportImages()">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <circle cx="12" cy="10" r="3"/>
                        <line x1="7" y1="16" x2="17" y2="16"/>
                    </svg>
                    여권사본 다운로드
                </button>
                <button type="button" class="btn btn-secondary" onclick="exportPassports()">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    명단 엑셀 다운로드
                </button>
                <button type="button" class="btn btn-primary" onclick="openUploadModal()">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    여권사본 업로드
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($event): ?>
    <!-- 제출 현황 -->
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-icon primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <div class="stat-info">
                <h3><?= $totalMembers ?></h3>
                <p>전체 참가자</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                    <circle cx="12" cy="10" r="3"/>
                    <line x1="7" y1="16" x2="17" y2="16"/>
                </svg>
            </div>
            <div class="stat-info">
                <h3><?= $submittedCount ?></h3>
                <p>여권 제출</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="stat-info">
                <h3><?= $totalMembers - $submittedCount ?></h3>
                <p>미제출</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon accent">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </div>
            <div class="stat-info">
                <h3><?= $totalMembers > 0 ? round(($submittedCount / $totalMembers) * 100) : 0 ?>%</h3>
                <p>제출률</p>
            </div>
        </div>
    </div>

    <!-- 여권 목록 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">여권 제출 목록</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($passports)): ?>
                <div style="padding: 60px 20px; text-align: center; color: var(--gray-500);">
                    제출된 여권이 없습니다.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th>한글이름</th>
                                <th>영문이름</th>
                                <th>성별</th>
                                <th>생년월일</th>
                                <th>여권번호</th>
                                <th>만료일</th>
                                <th>연락처</th>
                                <th>사본</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rowNum = 1; foreach ($passports as $p): ?>
                                <tr>
                                    <td><?= $rowNum++ ?></td>
                                    <td><strong><?= h($p['name_ko']) ?></strong></td>
                                    <td><?= h($p['name_en']) ?></td>
                                    <td><?= h($p['gender'] === 'M' ? '남' : ($p['gender'] === 'F' ? '여' : '-')) ?></td>
                                    <td><?= h(formatBirthDateDDMMYY($p['birth_date_decrypted'])) ?></td>
                                    <td style="font-family: monospace;"><?= mask_passport_no($p['passport_no_decrypted']) ?></td>
                                    <td><?= $p['expiry_date'] ? date('Y.m.d', strtotime($p['expiry_date'])) : '-' ?></td>
                                    <td><?= $p['phone'] ? format_phone($p['phone']) : '-' ?></td>
                                    <td>
                                        <?php if ($p['passport_image']): ?>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="viewPassportImage(<?= $p['id'] ?>)">
                                                보기
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline" onclick="downloadSinglePassport(<?= $p['id'] ?>)">
                                                다운로드
                                            </button>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
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

<!-- 이미지 뷰어 모달 -->
<div class="modal-backdrop" id="image-modal">
    <div class="modal" style="max-width: 90vw; max-height: 90vh;">
        <div class="modal-header">
            <h3 class="modal-title">여권 사본</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('image-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </span>
        </div>
        <div class="modal-body" style="text-align: center; padding: 20px;">
            <img id="passport-image" src="" alt="여권 사본" style="max-width: 100%; max-height: 70vh;">
        </div>
    </div>
</div>

<!-- 여권사본 업로드 모달 -->
<div class="modal-backdrop" id="upload-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">여권사본 업로드</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('upload-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </span>
        </div>
        <div class="modal-body">
            <form id="upload-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">회원 선택</label>
                    <select id="upload-member-id" class="form-select" required>
                        <option value="">회원을 선택하세요</option>
                        <?php if ($event):
                            $stmt = $db->prepare("SELECT em.member_id, m.name_ko, m.phone FROM event_members em JOIN members m ON em.member_id = m.id WHERE em.event_id = ? ORDER BY m.name_ko");
                            $stmt->execute([$eventId]);
                            $members = $stmt->fetchAll();
                            foreach ($members as $m):
                        ?>
                            <option value="<?= $m['member_id'] ?>"><?= h($m['name_ko']) ?> (<?= format_phone($m['phone']) ?>)</option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">여권사본 이미지</label>
                    <input type="file" id="upload-passport-file" class="form-control" accept="image/*" required>
                    <small style="color: var(--gray-500); display: block; margin-top: 4px;">JPG, PNG 파일만 업로드 가능합니다.</small>
                </div>
                <div id="upload-preview" style="display: none; margin-top: 12px;">
                    <img id="upload-preview-img" src="" alt="미리보기" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('upload-modal')">취소</button>
            <button type="button" class="btn btn-primary" onclick="submitPassportUpload()">업로드</button>
        </div>
    </div>
</div>

<script>
const eventId = <?= $eventId ?: 'null' ?>;

function changeEvent(id) {
    if (id) {
        window.location.href = `/born/admin/passport.php?event_id=${id}`;
    }
}

function exportPassports() {
    window.location.href = `/born/api/passports.php?action=export&event_id=${eventId}`;
}

function downloadPassportImages() {
    window.location.href = `/born/api/passports.php?action=download_images&event_id=${eventId}`;
}

function downloadSinglePassport(id) {
    window.location.href = `/born/api/passports.php?action=download_single&id=${id}`;
}

function openUploadModal() {
    document.getElementById('upload-form').reset();
    document.getElementById('upload-preview').style.display = 'none';
    BornAdmin.openModal('upload-modal');
}

// 이미지 미리보기
document.getElementById('upload-passport-file')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('upload-preview-img').src = e.target.result;
            document.getElementById('upload-preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

async function submitPassportUpload() {
    const memberId = document.getElementById('upload-member-id').value;
    const fileInput = document.getElementById('upload-passport-file');

    if (!memberId || !fileInput.files[0]) {
        BornAdmin.toast('회원과 이미지를 선택해주세요.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'admin_upload');
    formData.append('event_id', eventId);
    formData.append('member_id', memberId);
    formData.append('passport_image', fileInput.files[0]);

    try {
        const response = await fetch('/born/api/passports.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            BornAdmin.toast('여권사본이 업로드되었습니다.', 'success');
            BornAdmin.closeModal('upload-modal');
            location.reload();
        } else {
            throw new Error(result.message || '업로드에 실패했습니다.');
        }
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function viewPassportImage(id) {
    try {
        const response = await fetch(`/born/api/passports.php?action=get_image&id=${id}`);
        if (!response.ok) throw new Error('이미지를 불러올 수 없습니다.');

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        document.getElementById('passport-image').src = url;
        BornAdmin.openModal('image-modal');
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
