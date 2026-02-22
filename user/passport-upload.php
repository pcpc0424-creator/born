<?php
/**
 * 본투어 인터내셔날 - 여권 정보 등록
 */

require_once __DIR__ . '/../includes/auth.php';
require_user_auth();

$user = get_logged_in_user();
$visibility = get_page_visibility($user['event_id']);

if (!$visibility['passport_upload']) {
    redirect('/born/user/main.php');
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
    <link rel="stylesheet" href="/born/assets/css/animations.css">
    <link rel="stylesheet" href="/born/assets/css/user.css">
    <link rel="stylesheet" href="/born/assets/css/user-pc.css">
    <!-- Tesseract.js for OCR -->
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <style>
        .ocr-section {
            margin-top: 12px;
            padding: 12px;
            background: var(--primary-50);
            border-radius: var(--radius-md);
            border: 1px dashed var(--primary-300);
        }
        .ocr-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            background: var(--primary-600);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }
        .ocr-btn:hover {
            background: var(--primary-700);
        }
        .ocr-btn:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }
        .ocr-progress {
            display: none;
            margin-top: 12px;
        }
        .ocr-progress-bar {
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
        }
        .ocr-progress-fill {
            height: 100%;
            background: var(--primary-600);
            width: 0%;
            transition: width 0.3s ease;
        }
        .ocr-progress-text {
            font-size: 12px;
            color: var(--gray-600);
            margin-top: 8px;
            text-align: center;
        }
    </style>
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
                                <!-- OCR 스캔 섹션 -->
                                <div id="ocrSection" class="ocr-section" style="display: none;">
                                    <button type="button" id="ocrBtn" class="ocr-btn" onclick="runOCR()">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                            <circle cx="12" cy="13" r="4"/>
                                        </svg>
                                        여권 정보 자동 인식 (OCR)
                                    </button>
                                    <div id="ocrProgress" class="ocr-progress">
                                        <div class="ocr-progress-bar">
                                            <div id="ocrProgressFill" class="ocr-progress-fill"></div>
                                        </div>
                                        <p id="ocrProgressText" class="ocr-progress-text">준비 중...</p>
                                    </div>
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

                                <div class="form-group">
                                    <label class="form-label" for="nameEn">영문 이름 <span class="required">*</span></label>
                                    <input type="text" id="nameEn" name="name_en" class="form-input"
                                           value="<?= h($passport['name_en'] ?? $user['name_en'] ?? '') ?>"
                                           placeholder="여권상 영문 이름" required>
                                    <small class="form-hint">여권에 기재된 영문 이름을 정확히 입력해주세요.</small>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="gender">성별</label>
                                        <select id="gender" name="gender" class="form-select">
                                            <option value="">선택</option>
                                            <option value="M" <?= ($passport['gender'] ?? '') === 'M' ? 'selected' : '' ?>>남성</option>
                                            <option value="F" <?= ($passport['gender'] ?? '') === 'F' ? 'selected' : '' ?>>여성</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="birthDate">생년월일</label>
                                        <input type="date" id="birthDate" name="birth_date" class="form-input"
                                               value="<?= h($passport['birth_date'] ?? '') ?>">
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
                                    <label class="form-label" for="expiryDate">여권 만료일</label>
                                    <input type="date" id="expiryDate" name="expiry_date" class="form-input"
                                           value="<?= h($passport['expiry_date'] ?? '') ?>">
                                    <small class="form-hint">여행일 기준 6개월 이상 유효해야 합니다.</small>
                                </div>
                            </div>

                            <!-- 연락처 -->
                            <div class="form-section">
                                <h3 class="form-section-title">연락처</h3>

                                <div class="form-group">
                                    <label class="form-label" for="phone">휴대폰 번호</label>
                                    <input type="tel" id="phone" name="phone" class="form-input"
                                           value="<?= h($passport['phone'] ?? $user['phone'] ?? '') ?>"
                                           placeholder="010-0000-0000">
                                </div>
                            </div>

                            <!-- 주민번호 뒷자리 (선택) -->
                            <div class="form-section">
                                <h3 class="form-section-title">추가 정보 <span class="optional">(선택)</span></h3>

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

    <script src="/born/assets/js/user.js"></script>
    <script>
        let currentImageData = null;

        // 이미지 미리보기
        document.getElementById('passportImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentImageData = e.target.result;
                    const preview = document.getElementById('imagePreview');
                    preview.querySelector('img').src = e.target.result;
                    preview.style.display = 'block';
                    document.querySelector('.passport-image-upload').style.display = 'none';
                    // Show OCR section
                    document.getElementById('ocrSection').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        function removeImage() {
            document.getElementById('passportImage').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            document.querySelector('.passport-image-upload').style.display = 'block';
            document.getElementById('ocrSection').style.display = 'none';
            currentImageData = null;
        }

        // OCR 실행
        async function runOCR() {
            if (!currentImageData) {
                BornUser.toast('이미지를 먼저 업로드해주세요.', 'error');
                return;
            }

            const ocrBtn = document.getElementById('ocrBtn');
            const ocrProgress = document.getElementById('ocrProgress');
            const progressFill = document.getElementById('ocrProgressFill');
            const progressText = document.getElementById('ocrProgressText');

            ocrBtn.disabled = true;
            ocrProgress.style.display = 'block';

            try {
                progressText.textContent = 'OCR 엔진 로딩 중...';
                progressFill.style.width = '10%';

                const result = await Tesseract.recognize(
                    currentImageData,
                    'eng',
                    {
                        logger: m => {
                            if (m.status === 'recognizing text') {
                                const progress = Math.round(m.progress * 80) + 10;
                                progressFill.style.width = progress + '%';
                                progressText.textContent = `텍스트 인식 중... ${Math.round(m.progress * 100)}%`;
                            }
                        }
                    }
                );

                progressFill.style.width = '100%';
                progressText.textContent = '분석 중...';

                // Parse the recognized text
                const text = result.data.text;
                console.log('OCR Result:', text);

                // Try to parse MRZ (Machine Readable Zone)
                const passportData = parseMRZ(text) || parseGeneralText(text);

                if (passportData) {
                    fillFormWithData(passportData);
                    BornUser.toast('여권 정보가 자동으로 입력되었습니다.', 'success');
                } else {
                    BornUser.toast('여권 정보를 인식하지 못했습니다. 수동으로 입력해주세요.', 'warning');
                }

            } catch (error) {
                console.error('OCR Error:', error);
                BornUser.toast('OCR 처리 중 오류가 발생했습니다.', 'error');
            } finally {
                ocrBtn.disabled = false;
                ocrProgress.style.display = 'none';
                progressFill.style.width = '0%';
            }
        }

        // MRZ 파싱 (여권 기계 판독 영역)
        function parseMRZ(text) {
            const lines = text.split('\n').map(l => l.trim().replace(/\s/g, ''));

            // Find MRZ lines (start with P and contain < characters)
            let mrzLines = [];
            for (let i = 0; i < lines.length; i++) {
                const line = lines[i];
                if (line.length >= 40 && (line.startsWith('P') || line.includes('<') || /^[A-Z0-9<]{40,}$/.test(line))) {
                    mrzLines.push(line);
                }
            }

            if (mrzLines.length < 2) return null;

            // Get the last two valid MRZ lines
            const line1 = mrzLines[mrzLines.length - 2] || '';
            const line2 = mrzLines[mrzLines.length - 1] || '';

            if (line1.length < 44 || line2.length < 44) return null;

            const result = {};

            // Parse Line 1: P<CTRFAMILYNAME<<GIVENNAMES<<<<<<<<<<<<<<<<
            if (line1.startsWith('P')) {
                const namePart = line1.substring(5);
                const names = namePart.split('<<');
                if (names.length >= 2) {
                    const familyName = names[0].replace(/</g, ' ').trim();
                    const givenName = names[1].replace(/</g, ' ').trim();
                    result.nameEn = (givenName + ' ' + familyName).toUpperCase();
                }
            }

            // Parse Line 2: PASSPORT_NUMBERCTR BIRTH_DATE GENDER EXPIRY_DATE REST
            // Format: [Passport# 9][Check 1][Nationality 3][DOB 6][Check 1][Sex 1][Expiry 6][Check 1]...
            if (line2.length >= 28) {
                result.passportNo = line2.substring(0, 9).replace(/</g, '').replace(/0/g, 'O').replace(/O(?=[0-9])/g, '0');

                const birthDate = line2.substring(13, 19);
                if (/^\d{6}$/.test(birthDate)) {
                    const year = parseInt(birthDate.substring(0, 2));
                    const fullYear = year > 30 ? 1900 + year : 2000 + year;
                    result.birthDate = `${fullYear}-${birthDate.substring(2, 4)}-${birthDate.substring(4, 6)}`;
                }

                const gender = line2.charAt(20);
                if (gender === 'M' || gender === 'F') {
                    result.gender = gender;
                }

                const expiryDate = line2.substring(21, 27);
                if (/^\d{6}$/.test(expiryDate)) {
                    const year = 2000 + parseInt(expiryDate.substring(0, 2));
                    result.expiryDate = `${year}-${expiryDate.substring(2, 4)}-${expiryDate.substring(4, 6)}`;
                }
            }

            return Object.keys(result).length > 0 ? result : null;
        }

        // 일반 텍스트에서 여권 정보 추출
        function parseGeneralText(text) {
            const result = {};
            const upperText = text.toUpperCase();

            // 여권번호 패턴 (한국 여권: M + 8자리 숫자)
            const passportMatch = upperText.match(/[MP][A-Z]?\d{7,8}/);
            if (passportMatch) {
                result.passportNo = passportMatch[0];
            }

            // 날짜 패턴들
            const datePatterns = [
                /(\d{4})[.\-/](\d{2})[.\-/](\d{2})/g,  // YYYY-MM-DD
                /(\d{2})[.\-/](\d{2})[.\-/](\d{4})/g,  // DD-MM-YYYY
            ];

            const dates = [];
            for (const pattern of datePatterns) {
                let match;
                while ((match = pattern.exec(text)) !== null) {
                    dates.push(match[0]);
                }
            }

            // 영문 이름 패턴 (대문자 2개 이상 단어의 조합)
            const nameMatch = upperText.match(/([A-Z]{2,}\s+)+[A-Z]{2,}/);
            if (nameMatch) {
                result.nameEn = nameMatch[0].trim();
            }

            // 성별
            if (upperText.includes(' M ') || upperText.includes('/M/') || upperText.includes('MALE')) {
                result.gender = 'M';
            } else if (upperText.includes(' F ') || upperText.includes('/F/') || upperText.includes('FEMALE')) {
                result.gender = 'F';
            }

            return Object.keys(result).length > 0 ? result : null;
        }

        // 폼에 데이터 입력
        function fillFormWithData(data) {
            if (data.nameEn) {
                document.getElementById('nameEn').value = data.nameEn;
            }
            if (data.passportNo) {
                document.getElementById('passportNo').value = data.passportNo;
            }
            if (data.birthDate) {
                document.getElementById('birthDate').value = data.birthDate;
            }
            if (data.gender) {
                document.getElementById('gender').value = data.gender;
            }
            if (data.expiryDate) {
                document.getElementById('expiryDate').value = data.expiryDate;
            }
        }

        // 폼 제출
        document.getElementById('passportForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'upload');

            try {
                const response = await fetch('/born/api/passports.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    BornUser.toast(result.message || '저장되었습니다.', 'success');
                    setTimeout(() => {
                        location.href = '/born/user/main.php';
                    }, 1500);
                } else {
                    BornUser.toast(result.error || '저장에 실패했습니다.', 'error');
                }
            } catch (error) {
                BornUser.toast('오류가 발생했습니다.', 'error');
            }
        });
    </script>
</body>
</html>
