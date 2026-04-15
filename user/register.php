<?php
/**
 * 본투어 인터내셔날 - 회원가입
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// 이미 로그인된 경우
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    redirect('/user/main.php');
}

$error = '';
$success = '';
$eventCode = input('code');
$event = null;

// 행사 코드로 행사 정보 조회
if ($eventCode) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM events WHERE unique_code = ? AND status = 'active'");
    $stmt->execute([$eventCode]);
    $event = $stmt->fetch();
}

// 회원가입 처리
if (is_post()) {
    $loginId = input('login_id');
    $password = input('password');
    $passwordConfirm = input('password_confirm');
    $nameKo = input('name_ko');
    $nameEn = input('name_en');
    $phone = input('phone');
    $birthDate = input('birth_date');
    $gender = input('gender');
    $eventCode = input('code');

    // 유효성 검증
    if (empty($loginId) || empty($password) || empty($nameKo)) {
        $error = '아이디, 비밀번호, 이름(한글)은 필수 입력입니다.';
    } elseif (strlen($loginId) < 4 || strlen($loginId) > 50) {
        $error = '아이디는 4자 이상 50자 이하로 입력해주세요.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $loginId)) {
        $error = '아이디는 영문, 숫자, 밑줄(_)만 사용 가능합니다.';
    } elseif (strlen($password) < 4) {
        $error = '비밀번호는 4자 이상으로 입력해주세요.';
    } elseif ($password !== $passwordConfirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } elseif ($phone && !validate_phone($phone)) {
        $error = '올바른 전화번호를 입력해주세요.';
    } elseif ($birthDate && !validate_birth_date($birthDate)) {
        $error = '올바른 생년월일을 입력해주세요.';
    } else {
        $db = db();

        // 아이디 중복 체크
        $stmt = $db->prepare("SELECT id FROM members WHERE login_id = ?");
        $stmt->execute([$loginId]);
        if ($stmt->fetch()) {
            $error = '이미 사용 중인 아이디입니다.';
        } else {
            try {
                $db->beginTransaction();

                // 회원 등록
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO members (login_id, password, name_ko, name_en, phone, birth_date, gender)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $loginId,
                    $hashedPassword,
                    $nameKo,
                    $nameEn ?: null,
                    $phone ?: null,
                    $birthDate ?: null,
                    $gender ?: null
                ]);
                $memberId = $db->lastInsertId();

                // 행사 코드가 있으면 자동 매핑
                if ($eventCode) {
                    $stmt = $db->prepare("SELECT id FROM events WHERE unique_code = ? AND status = 'active'");
                    $stmt->execute([$eventCode]);
                    $eventRow = $stmt->fetch();

                    if ($eventRow) {
                        $stmt = $db->prepare("INSERT INTO event_members (event_id, member_id) VALUES (?, ?)");
                        $stmt->execute([$eventRow['id'], $memberId]);
                    }
                }

                $db->commit();

                // 가입 후 자동 로그인
                session_regenerate_id(true);

                $_SESSION['user_id'] = $memberId;
                $_SESSION['user_login_id'] = $loginId;
                $_SESSION['user_name'] = $nameKo;
                $_SESSION['user_name_en'] = $nameEn;
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_last_activity'] = time();

                if ($eventCode && isset($eventRow)) {
                    $stmt = $db->prepare("SELECT event_name, unique_code FROM events WHERE id = ?");
                    $stmt->execute([$eventRow['id']]);
                    $ev = $stmt->fetch();
                    $_SESSION['user_event_id'] = $eventRow['id'];
                    $_SESSION['user_event_name'] = $ev['event_name'];
                    $_SESSION['user_event_code'] = $ev['unique_code'];
                }

                // 행사 정보가 있으면 메인으로, 없으면 로그인 페이지로
                if (!empty($_SESSION['user_event_id'])) {
                    redirect('/user/main.php');
                } else {
                    // 행사 매핑 없이 가입된 경우 - 로그아웃 후 로그인 페이지로
                    user_logout();
                    $redirectUrl = '/user/index.php?registered=1';
                    redirect($redirectUrl);
                }

            } catch (Exception $e) {
                $db->rollBack();
                $error = '회원가입 중 오류가 발생했습니다. 다시 시도해주세요.';
                app_log('Registration error: ' . $e->getMessage(), 'error');
            }
        }
    }

    // 에러 시 행사 정보 다시 로드
    if ($error && $eventCode) {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM events WHERE unique_code = ? AND status = 'active'");
        $stmt->execute([$eventCode]);
        $event = $stmt->fetch();
    }
}

$pageTitle = $event ? h($event['event_name']) . ' - 회원가입' : '회원가입 - 본투어 인터내셔날';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#7ec8e3">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.min.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="stylesheet" href="/assets/css/user.css">
    <link rel="stylesheet" href="/assets/css/user-pc.css">
    <style>
        .register-back {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            color: var(--gray-600);
            text-decoration: none;
            margin-bottom: 20px;
        }
        .register-back svg {
            width: 18px;
            height: 18px;
        }
        .register-back:hover {
            color: var(--primary-600);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .event-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #2e7d32;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .event-badge svg {
            width: 16px;
            height: 16px;
        }
        .born-logo-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }
        .born-logo-footer .born-logo-text {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-600);
            letter-spacing: 1px;
        }
        .born-logo-footer .born-slogan {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="phone-frame">
        <div class="side-button-left"></div>
        <div class="side-button-right-1"></div>
        <div class="side-button-right-2"></div>
        <div class="phone-screen">
            <div class="phone-screen-inner">
                <div class="login-page">
                    <div class="login-header page-enter">
                        <a href="/user/index.php<?= $eventCode ? '?code=' . h($eventCode) : '' ?>" class="register-back">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"/>
                            </svg>
                            로그인으로 돌아가기
                        </a>
                        <?php if ($event): ?>
                            <div class="event-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                <?= h($event['event_name']) ?> 참가 등록
                            </div>
                        <?php endif; ?>
                        <h1>회원가입</h1>
                        <p>본투어 인터내셔날에 오신 것을 환영합니다</p>
                    </div>

                    <div class="login-form-container page-enter" style="animation-delay: 0.1s;">
                        <?php if ($error): ?>
                            <div style="background: var(--error-light); color: var(--error); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px;">
                                <?= h($error) ?>
                            </div>
                        <?php endif; ?>

                        <form class="login-form" method="POST" action="">
                            <?php if ($eventCode): ?>
                                <input type="hidden" name="code" value="<?= h($eventCode) ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label class="form-label" for="login_id">아이디 <span style="color: var(--error);">*</span></label>
                                <input type="text" id="login_id" name="login_id" class="form-input"
                                       placeholder="영문, 숫자, 밑줄 4자 이상" autocomplete="username"
                                       value="<?= h(input('login_id', '')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password">비밀번호 <span style="color: var(--error);">*</span></label>
                                <input type="password" id="password" name="password" class="form-input"
                                       placeholder="4자 이상" autocomplete="new-password" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password_confirm">비밀번호 확인 <span style="color: var(--error);">*</span></label>
                                <input type="password" id="password_confirm" name="password_confirm" class="form-input"
                                       placeholder="비밀번호를 다시 입력하세요" autocomplete="new-password" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="name_ko">이름 (한글) <span style="color: var(--error);">*</span></label>
                                <input type="text" id="name_ko" name="name_ko" class="form-input"
                                       placeholder="홍길동" value="<?= h(input('name_ko', '')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="name_en">이름 (영문)</label>
                                <input type="text" id="name_en" name="name_en" class="form-input"
                                       placeholder="HONG/GILDONG (성/이름)" value="<?= h(input('name_en', '')) ?>"
                                       style="text-transform: uppercase;">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="phone">연락처</label>
                                <input type="tel" id="phone" name="phone" class="form-input"
                                       placeholder="010-0000-0000" value="<?= h(input('phone', '')) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">생년월일</label>
                                <input type="hidden" id="birth_date" name="birth_date" value="<?= h(input('birth_date', '')) ?>">
                                <div class="form-row" style="grid-template-columns: 1.2fr 0.8fr 0.8fr;">
                                    <select id="birth_year" class="form-input" onchange="updateBirthDate()">
                                        <option value="">연도</option>
                                    </select>
                                    <select id="birth_month" class="form-input" disabled onchange="updateBirthDate()">
                                        <option value="">월</option>
                                    </select>
                                    <select id="birth_day" class="form-input" disabled onchange="updateBirthDate()">
                                        <option value="">일</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="gender">성별</label>
                                <select id="gender" name="gender" class="form-input">
                                    <option value="">선택</option>
                                    <option value="M" <?= input('gender') === 'M' ? 'selected' : '' ?>>남성</option>
                                    <option value="F" <?= input('gender') === 'F' ? 'selected' : '' ?>>여성</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 8px;">
                                회원가입
                            </button>
                        </form>

                        <div class="born-logo-footer">
                            <span class="born-logo-text">(주)본투어인터내셔날</span>
                            <span class="born-slogan">"세계를 추억으로" 본투어 인터내셔날이 함께합니다</span>
                        </div>
                    </div>

                    <div class="login-footer page-enter" style="animation-delay: 0.2s;">
                        <p>&copy; <?= date('Y') ?> <?= COMPANY_NAME ?>. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/user.js"></script>
    <script>
    (function() {
        const yearEl = document.getElementById('birth_year');
        const monthEl = document.getElementById('birth_month');
        const dayEl = document.getElementById('birth_day');
        const hiddenEl = document.getElementById('birth_date');

        // 연도 옵션 생성 (현재년도 ~ 1920)
        const currentYear = new Date().getFullYear();
        for (let y = currentYear; y >= 1920; y--) {
            yearEl.appendChild(new Option(y + '년', y));
        }

        // 월 옵션 생성
        for (let m = 1; m <= 12; m++) {
            monthEl.appendChild(new Option(m + '월', String(m).padStart(2, '0')));
        }

        function populateDays() {
            const year = parseInt(yearEl.value);
            const month = parseInt(monthEl.value);
            const prevDay = dayEl.value;
            dayEl.innerHTML = '<option value="">일</option>';
            if (!year || !month) return;
            const daysInMonth = new Date(year, month, 0).getDate();
            for (let d = 1; d <= daysInMonth; d++) {
                dayEl.appendChild(new Option(d + '일', String(d).padStart(2, '0')));
            }
            if (prevDay && parseInt(prevDay) <= daysInMonth) {
                dayEl.value = prevDay;
            }
        }

        window.updateBirthDate = function() {
            // 연도 선택 시 월 활성화
            if (yearEl.value) {
                monthEl.disabled = false;
            } else {
                monthEl.disabled = true;
                monthEl.value = '';
                dayEl.disabled = true;
                dayEl.value = '';
            }
            // 월 선택 시 일 활성화 및 일수 갱신
            if (yearEl.value && monthEl.value) {
                dayEl.disabled = false;
                populateDays();
            } else {
                dayEl.disabled = true;
                dayEl.value = '';
            }
            // hidden 필드에 값 설정
            if (yearEl.value && monthEl.value && dayEl.value) {
                hiddenEl.value = yearEl.value + '-' + monthEl.value + '-' + dayEl.value;
            } else {
                hiddenEl.value = '';
            }
        };

        // 기존 값이 있으면 복원
        const existing = hiddenEl.value;
        if (existing) {
            const parts = existing.split('-');
            if (parts.length === 3) {
                yearEl.value = parts[0];
                monthEl.disabled = false;
                monthEl.value = parts[1];
                dayEl.disabled = false;
                populateDays();
                dayEl.value = parts[2];
            }
        }
    })();
    </script>
</body>
</html>
