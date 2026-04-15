<?php
/**
 * 본투어 인터내셔날 - 여권 정보 등록
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['passport_upload']) {
    redirect('/user/main.php');
}

$db = db();

// 행사 정보
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$user['event_id']]);
$event = $stmt->fetch();

// 기존 여권 정보 조회
$stmt = $db->prepare("SELECT * FROM passports WHERE member_id = ? AND event_id = ?");
$stmt->execute([$user['id'], $user['event_id']]);
$passport = $stmt->fetch();

// 복호화
if ($passport) {
    require_once __DIR__ . '/../config/encryption.php';
    $passport['birth_date'] = decrypt_sensitive($passport['birth_date_encrypted']);
    $passport['passport_no'] = decrypt_sensitive($passport['passport_no_encrypted']);
    $passport['ssn_back'] = $passport['ssn_back_encrypted'] ? decrypt_sensitive($passport['ssn_back_encrypted']) : '';
}

$pageTitle = '여권 정보 등록';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6dc5d1">
    <title><?= $pageTitle ?> - 본투어</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="stylesheet" href="/assets/css/user.css">
    <link rel="stylesheet" href="/assets/css/user-pc.css">
</head>
<body>
    <div class="phone-frame">
        <div class="phone-screen">
            <div class="phone-screen-inner">
                <div class="user-layout">
                    <header class="user-header">
                        <div class="header-back" >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"/>
                            </svg>
                        </div>
                        <h1 class="header-title"><?= $pageTitle ?></h1>
                        <div class="header-menu" onclick="BornUser.openSidebar()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="3" y1="12" x2="21" y2="12"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <line x1="3" y1="18" x2="21" y2="18"/>
                            </svg>
                        </div>
                    </header>

                    <div class="user-content">
                        <!-- 안내 메시지 -->
                        <div class="info-banner page-enter">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                            <p>여권 정보는 암호화되어 안전하게 보관됩니다.</p>
                        </div>

                        <!-- 여권 정보 폼 -->
                        <form id="passportForm" class="passport-form page-enter" style="animation-delay: 0.1s;">
                            <!-- 여권 사진 업로드 -->
                            <div class="passport-upload-section">
                                <label class="form-label">여권 사본</label>
                                <div class="passport-image-upload" id="passportImageUpload">
                                    <input type="file" id="passportImage" name="passport_image" accept="image/*" hidden>
                                    <?php if ($passport && $passport['passport_image']): ?>
                                        <div class="uploaded-preview">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                            <span>여권 사본 등록됨</span>
                                            <button type="button" class="change-btn" onclick="document.getElementById('passportImage').click()">변경</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="upload-placeholder" onclick="document.getElementById('passportImage').click()">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                <polyline points="21 15 16 10 5 21"/>
                                            </svg>
                                            <span>여권 사본 업로드</span>
                                            <small>클릭하여 사진 선택</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div id="imagePreview" class="image-preview" style="display: none;">
                                    <img src="" alt="미리보기">
                                    <button type="button" class="remove-image" onclick="removeImage()">×</button>
                                </div>
                            </div>

                            <!-- 기본 정보 -->
                            <div class="form-section">
                                <h3 class="form-section-title">기본 정보</h3>

                                <div class="form-group">
                                    <label class="form-label" for="nameKo">한글 이름 <span class="required">*</span></label>
                                    <input type="text" id="nameKo" name="name_ko" class="form-input"
                                           value="<?= h($passport['name_ko'] ?? $user['name_ko']) ?>" required>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="nameEnLast">영문 성 <span class="required">*</span></label>
                                        <input type="text" id="nameEnLast" name="name_en_last" class="form-input"
                                               value="<?= h($passport['name_en_last'] ?? '') ?>"
                                               placeholder="LEE" style="text-transform: uppercase;" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="nameEnFirst">영문 이름 <span class="required">*</span></label>
                                        <input type="text" id="nameEnFirst" name="name_en_first" class="form-input"
                                               value="<?= h($passport['name_en_first'] ?? '') ?>"
                                               placeholder="EUNJUNG" style="text-transform: uppercase;" required>
                                    </div>
                                </div>
                                <input type="hidden" id="nameEn" name="name_en" value="<?= h($passport['name_en'] ?? $user['name_en'] ?? '') ?>">
                                <small class="form-hint" style="margin-top: -8px; margin-bottom: 12px; display: block;">여권에 기재된 영문 성/이름을 정확히 입력해주세요.</small>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="gender">성별 <span class="required">*</span></label>
                                        <select id="gender" name="gender" class="form-select" required>
                                            <option value="">선택</option>
                                            <option value="M" <?= ($passport['gender'] ?? '') === 'M' ? 'selected' : '' ?>>남성</option>
                                            <option value="F" <?= ($passport['gender'] ?? '') === 'F' ? 'selected' : '' ?>>여성</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="birthDate">생년월일 <span class="required">*</span></label>
                                        <input type="date" id="birthDate" name="birth_date" class="form-input"
                                               value="<?= h($passport['birth_date'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <!-- 여권 정보 -->
                            <div class="form-section">
                                <h3 class="form-section-title">여권 정보</h3>

                                <div class="form-group">
                                    <label class="form-label" for="passportNo">여권번호 <span class="required">*</span></label>
                                    <input type="text" id="passportNo" name="passport_no" class="form-input"
                                           value="<?= h($passport['passport_no'] ?? '') ?>"
                                           placeholder="M12345678" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="expiryDate">여권 만료일 <span class="required">*</span></label>
                                    <input type="date" id="expiryDate" name="expiry_date" class="form-input"
                                           value="<?= h($passport['expiry_date'] ?? '') ?>" required>
                                    <small class="form-hint">여행일 기준 6개월 이상 유효해야 합니다.</small>
                                </div>
                            </div>

                            <!-- 연락처 -->
                            <div class="form-section">
                                <h3 class="form-section-title">연락처</h3>

                                <div class="form-group">
                                    <label class="form-label" for="phone">휴대폰 번호 <span class="required">*</span></label>
                                    <input type="tel" id="phone" name="phone" class="form-input"
                                           value="<?= h($passport['phone'] ?? $user['phone'] ?? '') ?>"
                                           placeholder="010-0000-0000" required>
                                </div>
                            </div>

                            <!-- 주민번호 뒷자리 (선택) -->
                            <div class="form-section">
                                <h3 class="form-section-title">추가 정보</h3>

                                <div class="form-group">
                                    <label class="form-label" for="ssnBack">주민번호 뒷자리</label>
                                    <input type="password" id="ssnBack" name="ssn_back" class="form-input"
                                           value="<?= h($passport['ssn_back'] ?? '') ?>"
                                           placeholder="●●●●●●●" maxlength="7">
                                    <small class="form-hint">보험 가입 등에 필요한 경우에만 입력해주세요.</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                저장하기
                            </button>
                        </form>
                    </div>

                    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/user.js"></script>
    <script>
        // 기존 name_en을 성/이름으로 분리하여 초기값 설정
        (function() {
            const lastEl = document.getElementById('nameEnLast');
            const firstEl = document.getElementById('nameEnFirst');
            const fullEl = document.getElementById('nameEn');
            if (!lastEl.value && !firstEl.value && fullEl.value) {
                const parts = fullEl.value.trim().split(/\s+/);
                if (parts.length >= 2) {
                    lastEl.value = parts[0].toUpperCase();
                    firstEl.value = parts.slice(1).join(' ').toUpperCase();
                } else {
                    lastEl.value = fullEl.value.toUpperCase();
                }
            }
            // 성/이름 변경 시 hidden에 합치기
            function syncNameEn() {
                fullEl.value = (lastEl.value.trim() + ' ' + firstEl.value.trim()).trim().toUpperCase();
            }
            lastEl.addEventListener('input', syncNameEn);
            firstEl.addEventListener('input', syncNameEn);
        })();

        // 이미지 미리보기
        document.getElementById('passportImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.querySelector('img').src = e.target.result;
                    preview.style.display = 'block';
                    document.querySelector('.passport-image-upload').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        function removeImage() {
            document.getElementById('passportImage').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            document.querySelector('.passport-image-upload').style.display = 'block';
        }

        // 폼 제출
        document.getElementById('passportForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'upload');

            try {
                const response = await fetch('/api/passports.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    BornUser.toast(result.message || '저장되었습니다.', 'success');
                    setTimeout(() => {
                        location.href = '/user/main.php';
                    }, 1500);
                } else {
                    BornUser.toast(result.message || result.error || '저장에 실패했습니다.', 'error');
                }
            } catch (error) {
                BornUser.toast('오류가 발생했습니다.', 'error');
            }
        });
    </script>
</body>
</html>
