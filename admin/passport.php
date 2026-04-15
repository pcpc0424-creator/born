<?php
/**
 * 본투어 인터내셔날 - 여권사본 확인
 */

$pageTitle = '여권사본 확인하기';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/encryption.php';

// 여권번호 마스킹 (앞 2자리 + **** + 뒤 2자리)
function mask_passport_no($passportNo) {
    if (empty($passportNo)) return '-';
    $len = strlen($passportNo);
    if ($len <= 4) return $passportNo;
    return substr($passportNo, 0, 2) . str_repeat('*', $len - 4) . substr($passportNo, -2);
}

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
$unsubmittedCount = 0;
if ($event) {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM event_members WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $totalMembers = $stmt->fetch()['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM passports WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $submittedCount = $stmt->fetch()['total'];

    // 미제출은 최소 0
    $unsubmittedCount = max(0, $totalMembers - $submittedCount);
    // 전체 참가자가 0이면 제출 수를 기준으로 보정
    if ($totalMembers === 0 && $submittedCount > 0) {
        $totalMembers = $submittedCount;
        $unsubmittedCount = 0;
    }
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
                <h3><?= $unsubmittedCount ?></h3>
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
                <h3><?= $totalMembers > 0 ? round(($submittedCount / $totalMembers) * 100) : ($submittedCount > 0 ? 100 : 0) ?>%</h3>
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
                                <th>비자</th>
                                <th>사본</th>
                                <th style="width: 60px;">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rowNum = 1; foreach ($passports as $p): ?>
                                <tr>
                                    <td><?= $rowNum++ ?></td>
                                    <td><strong><?= h($p['name_ko'] ?? '') ?></strong></td>
                                    <td><?= h($p['name_en'] ?? '') ?></td>
                                    <td><?= ($p['gender'] ?? '') === 'M' ? '남' : (($p['gender'] ?? '') === 'F' ? '여' : '-') ?></td>
                                    <td><?= h(formatBirthDateDDMMYY($p['birth_date_decrypted'])) ?></td>
                                    <td style="font-family: monospace;"><?= mask_passport_no($p['passport_no_decrypted']) ?></td>
                                    <td><?= $p['expiry_date'] ? date('Y.m.d', strtotime($p['expiry_date'])) : '-' ?></td>
                                    <td><?= $p['phone'] ? format_phone($p['phone']) : '-' ?></td>
                                    <td>
                                        <select class="form-select" style="width: 60px; padding: 4px 6px; font-size: 13px; font-weight: 600; color: <?= ($p['visa_status'] ?? 'N') === 'Y' ? '#2e7d32' : '#c62828' ?>;"
                                                onchange="updateVisa(<?= $p['id'] ?>, this.value, this)">
                                            <option value="N" <?= ($p['visa_status'] ?? 'N') === 'N' ? 'selected' : '' ?>>N</option>
                                            <option value="Y" <?= ($p['visa_status'] ?? 'N') === 'Y' ? 'selected' : '' ?>>Y</option>
                                        </select>
                                    </td>
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
                                    <td style="white-space: nowrap;">
                                        <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                            'id' => $p['id'],
                                            'name_ko' => $p['name_ko'] ?? '',
                                            'name_en' => $p['name_en'] ?? '',
                                            'gender' => $p['gender'] ?? '',
                                            'birth_date' => $p['birth_date_decrypted'] ?? '',
                                            'passport_no' => $p['passport_no_decrypted'] ?? '',
                                            'expiry_date' => $p['expiry_date'] ?? '',
                                            'phone' => $p['phone'] ?? '',
                                        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)" title="수정" style="color: var(--primary-600);">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-ghost btn-icon" onclick="deletePassport(<?= $p['id'] ?>)" title="삭제" style="color: var(--error);">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
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
                            <option value="<?= $m['member_id'] ?>"><?= h($m['name_ko']) ?><?= $m['phone'] ? ' (' . format_phone($m['phone']) . ')' : '' ?></option>
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

<!-- 여권 정보 수정 모달 -->
<div class="modal-backdrop" id="edit-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">여권 정보 수정</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('edit-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </span>
        </div>
        <div class="modal-body">
            <form id="edit-form">
                <input type="hidden" id="edit-id">
                <div class="form-group">
                    <label class="form-label">한글이름</label>
                    <input type="text" id="edit-name-ko" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">영문이름</label>
                    <input type="text" id="edit-name-en" class="form-input" style="text-transform: uppercase;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">성별</label>
                        <select id="edit-gender" class="form-select">
                            <option value="">선택</option>
                            <option value="M">남성</option>
                            <option value="F">여성</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">생년월일</label>
                        <input type="date" id="edit-birth-date" class="form-input">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">여권번호</label>
                        <input type="text" id="edit-passport-no" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">만료일</label>
                        <input type="date" id="edit-expiry-date" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">연락처</label>
                    <input type="tel" id="edit-phone" class="form-input" placeholder="010-0000-0000">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('edit-modal')">취소</button>
            <button type="button" class="btn btn-primary" onclick="submitPassportEdit()">저장</button>
        </div>
    </div>
</div>

<script>
const eventId = <?= $eventId ?: 'null' ?>;

async function updateVisa(id, value, selectEl) {
    try {
        const response = await fetch('/api/passports.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update_visa', id: id, visa_status: value })
        });
        const result = await response.json();
        if (result.success) {
            selectEl.style.color = value === 'Y' ? '#2e7d32' : '#c62828';
            BornAdmin.toast('비자 상태가 변경되었습니다.', 'success');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        BornAdmin.toast(error.message || '변경에 실패했습니다.', 'error');
    }
}

function changeEvent(id) {
    if (id) {
        window.location.href = `/admin/passport.php?event_id=${id}`;
    }
}

function exportPassports() {
    window.location.href = `/api/passports.php?action=export&event_id=${eventId}`;
}

function downloadPassportImages() {
    window.location.href = `/api/passports.php?action=download_images&event_id=${eventId}`;
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
        const response = await fetch('/api/passports.php', {
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

async function deletePassport(id) {
    if (!confirm('이 여권사본을 삭제하시겠습니까?\n삭제된 데이터는 복구할 수 없습니다.')) return;

    try {
        const response = await fetch('/api/passports.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });
        const result = await response.json();

        if (result.success) {
            BornAdmin.toast('여권사본이 삭제되었습니다.', 'success');
            location.reload();
        } else {
            throw new Error(result.message || '삭제에 실패했습니다.');
        }
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

async function viewPassportImage(id) {
    try {
        const response = await fetch(`/api/passports.php?action=get_image&id=${id}`);
        if (!response.ok) {
            // JSON 에러 응답 확인
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const result = await response.json();
                throw new Error(result.message || '이미지를 불러올 수 없습니다.');
            }
            throw new Error('이미지 파일이 존재하지 않습니다.');
        }

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        document.getElementById('passport-image').src = url;
        BornAdmin.openModal('image-modal');
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}

function downloadSinglePassport(id) {
    window.open(`/api/passports.php?action=download_single&id=${id}`, '_blank');
}

function openEditModal(data) {
    document.getElementById('edit-id').value = data.id;
    document.getElementById('edit-name-ko').value = data.name_ko;
    document.getElementById('edit-name-en').value = data.name_en;
    document.getElementById('edit-gender').value = data.gender;
    document.getElementById('edit-birth-date').value = data.birth_date;
    document.getElementById('edit-passport-no').value = data.passport_no;
    document.getElementById('edit-expiry-date').value = data.expiry_date;
    document.getElementById('edit-phone').value = data.phone;
    BornAdmin.openModal('edit-modal');
}

async function submitPassportEdit() {
    const id = document.getElementById('edit-id').value;
    const nameKo = document.getElementById('edit-name-ko').value.trim();
    const passportNo = document.getElementById('edit-passport-no').value.trim();

    if (!nameKo || !passportNo) {
        BornAdmin.toast('한글이름과 여권번호는 필수입니다.', 'error');
        return;
    }

    try {
        const response = await fetch('/api/passports.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'admin_edit',
                id: id,
                name_ko: nameKo,
                name_en: document.getElementById('edit-name-en').value.trim().toUpperCase(),
                gender: document.getElementById('edit-gender').value,
                birth_date: document.getElementById('edit-birth-date').value,
                passport_no: passportNo,
                expiry_date: document.getElementById('edit-expiry-date').value,
                phone: document.getElementById('edit-phone').value.trim()
            })
        });
        const result = await response.json();

        if (result.success) {
            BornAdmin.toast('여권 정보가 수정되었습니다.', 'success');
            BornAdmin.closeModal('edit-modal');
            location.reload();
        } else {
            throw new Error(result.message || '수정에 실패했습니다.');
        }
    } catch (error) {
        BornAdmin.toast(error.message, 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
