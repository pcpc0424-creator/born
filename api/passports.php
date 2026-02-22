<?php
/**
 * 본투어 인터내셔날 - 여권 API
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/encryption.php';

// JSON 입력 처리
$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];
if (!empty($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

$action = $jsonInput['action'] ?? input('action');

switch ($action) {
    case 'upload': uploadPassport(); break;
    case 'get': getPassport(); break;
    case 'get_image': getPassportImage(); break;
    case 'export': exportPassports(); break;
    case 'download_images': downloadPassportImages(); break;
    case 'download_single': downloadSinglePassport(); break;
    case 'admin_upload': adminUploadPassport(); break;
    default: json_error('잘못된 요청입니다.', 400);
}

function uploadPassport(): void {
    require_user_auth();
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $user = get_logged_in_user();

    // 필수 정보
    $nameKo = input('name_ko');
    $nameEn = input('name_en');
    $gender = input('gender');
    $birthDate = input('birth_date');
    $passportNo = input('passport_no');
    $expiryDate = input('expiry_date');
    $phone = input('phone');

    if (empty($nameKo) || empty($nameEn) || empty($passportNo)) {
        json_error('필수 정보를 입력해주세요.');
    }

    // 암호화
    $birthDateEncrypted = encrypt_sensitive($birthDate);
    $passportNoEncrypted = encrypt_sensitive($passportNo);
    $ssnBackEncrypted = input('ssn_back') ? encrypt_sensitive(input('ssn_back')) : null;

    // 이미지 업로드
    $passportImage = null;
    if (!empty($_FILES['passport_image']['name'])) {
        $result = handle_file_upload($_FILES['passport_image'], UPLOAD_PASSPORTS, ALLOWED_DOCUMENT_TYPES);
        if ($result['success']) {
            // 파일 암호화
            $encryptedPath = UPLOAD_PASSPORTS . '/enc_' . $result['filename'];
            if (encrypt_file($result['path'], $encryptedPath)) {
                unlink($result['path']); // 원본 삭제
                $passportImage = 'enc_' . $result['filename'];
            } else {
                $passportImage = $result['filename'];
            }
        }
    }

    $db = db();

    // 기존 여권 확인
    $stmt = $db->prepare("SELECT id, passport_image FROM passports WHERE member_id = ? AND event_id = ?");
    $stmt->execute([$user['id'], $user['event_id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        // 기존 이미지 삭제
        if ($existing['passport_image'] && $passportImage) {
            delete_file(UPLOAD_PASSPORTS . '/' . $existing['passport_image']);
        }

        $stmt = $db->prepare("UPDATE passports SET
            name_ko = ?, name_en = ?, gender = ?, birth_date_encrypted = ?,
            passport_no_encrypted = ?, ssn_back_encrypted = ?, expiry_date = ?, phone = ?,
            passport_image = COALESCE(?, passport_image)
            WHERE id = ?");
        $stmt->execute([
            $nameKo, $nameEn, $gender, $birthDateEncrypted,
            $passportNoEncrypted, $ssnBackEncrypted, $expiryDate ?: null, $phone,
            $passportImage, $existing['id']
        ]);
    } else {
        $stmt = $db->prepare("INSERT INTO passports
            (member_id, event_id, name_ko, name_en, gender, birth_date_encrypted,
             passport_no_encrypted, ssn_back_encrypted, expiry_date, phone, passport_image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user['id'], $user['event_id'], $nameKo, $nameEn, $gender, $birthDateEncrypted,
            $passportNoEncrypted, $ssnBackEncrypted, $expiryDate ?: null, $phone, $passportImage
        ]);
    }

    json_success(null, '여권 정보가 저장되었습니다.');
}

function getPassport(): void {
    require_user_auth();

    $user = get_logged_in_user();

    $db = db();
    $stmt = $db->prepare("SELECT * FROM passports WHERE member_id = ? AND event_id = ?");
    $stmt->execute([$user['id'], $user['event_id']]);
    $passport = $stmt->fetch();

    if ($passport) {
        // 복호화
        $passport['birth_date'] = decrypt_sensitive($passport['birth_date_encrypted']);
        $passport['passport_no'] = decrypt_sensitive($passport['passport_no_encrypted']);
        $passport['ssn_back'] = $passport['ssn_back_encrypted'] ? decrypt_sensitive($passport['ssn_back_encrypted']) : '';

        unset($passport['birth_date_encrypted'], $passport['passport_no_encrypted'], $passport['ssn_back_encrypted']);
    }

    json_success($passport);
}

function getPassportImage(): void {
    require_admin_auth();

    $id = input('id');
    $db = db();

    $stmt = $db->prepare("SELECT passport_image FROM passports WHERE id = ?");
    $stmt->execute([$id]);
    $passport = $stmt->fetch();

    if (!$passport || !$passport['passport_image']) {
        http_response_code(404);
        exit;
    }

    $imagePath = UPLOAD_PASSPORTS . '/' . $passport['passport_image'];

    // 암호화된 파일인 경우 복호화
    if (strpos($passport['passport_image'], 'enc_') === 0) {
        $decrypted = decrypt_file($imagePath);
        if ($decrypted === false) {
            http_response_code(500);
            exit;
        }

        header('Content-Type: image/jpeg');
        echo $decrypted;
    } else {
        // 일반 파일
        $mimeType = mime_content_type($imagePath);
        header('Content-Type: ' . $mimeType);
        readfile($imagePath);
    }
    exit;
}

function exportPassports(): void {
    require_admin_auth();

    $eventId = input('event_id');
    $db = db();

    $stmt = $db->prepare("
        SELECT p.*, m.login_id
        FROM passports p
        JOIN members m ON p.member_id = m.id
        WHERE p.event_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$eventId]);
    $passports = $stmt->fetchAll();

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="passports_' . date('Ymd_His') . '.xls"');
    echo "\xEF\xBB\xBF";

    echo "한글이름\t영문이름\t성별\t생년월일\t여권번호\t만료일\t연락처\t제출일\n";

    foreach ($passports as $p) {
        echo implode("\t", [
            $p['name_ko'],
            $p['name_en'],
            $p['gender'] ? GENDER_LABELS[$p['gender']] : '',
            decrypt_sensitive($p['birth_date_encrypted']),
            decrypt_sensitive($p['passport_no_encrypted']),
            $p['expiry_date'] ?? '',
            $p['phone'] ?? '',
            date('Y-m-d H:i', strtotime($p['created_at']))
        ]) . "\n";
    }
    exit;
}

/**
 * 여권사본 이미지 일괄 다운로드 (ZIP)
 */
function downloadPassportImages(): void {
    require_admin_auth();

    $eventId = input('event_id');
    $db = db();

    $stmt = $db->prepare("
        SELECT p.id, p.passport_image, p.name_ko
        FROM passports p
        WHERE p.event_id = ? AND p.passport_image IS NOT NULL
        ORDER BY p.name_ko
    ");
    $stmt->execute([$eventId]);
    $passports = $stmt->fetchAll();

    if (empty($passports)) {
        die('다운로드할 여권사본이 없습니다.');
    }

    // 이벤트 이름 가져오기
    $stmt = $db->prepare("SELECT event_name FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    $eventName = $event ? preg_replace('/[^가-힣a-zA-Z0-9]/u', '_', $event['event_name']) : 'event';

    // ZIP 파일 생성
    $zipFile = sys_get_temp_dir() . '/passports_' . $eventId . '_' . time() . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
        die('ZIP 파일을 생성할 수 없습니다.');
    }

    foreach ($passports as $p) {
        $imagePath = UPLOAD_PASSPORTS . '/' . $p['passport_image'];
        if (file_exists($imagePath)) {
            // 암호화된 파일인 경우 복호화
            if (strpos($p['passport_image'], 'enc_') === 0) {
                $decrypted = decrypt_file($imagePath);
                if ($decrypted !== false) {
                    $ext = pathinfo($p['passport_image'], PATHINFO_EXTENSION);
                    $zip->addFromString($p['name_ko'] . '_' . $p['id'] . '.' . $ext, $decrypted);
                }
            } else {
                $ext = pathinfo($p['passport_image'], PATHINFO_EXTENSION);
                $zip->addFile($imagePath, $p['name_ko'] . '_' . $p['id'] . '.' . $ext);
            }
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $eventName . '_여권사본_' . date('Ymd') . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);
    unlink($zipFile);
    exit;
}

/**
 * 단일 여권사본 다운로드
 */
function downloadSinglePassport(): void {
    require_admin_auth();

    $id = input('id');
    $db = db();

    $stmt = $db->prepare("SELECT passport_image, name_ko FROM passports WHERE id = ?");
    $stmt->execute([$id]);
    $passport = $stmt->fetch();

    if (!$passport || !$passport['passport_image']) {
        http_response_code(404);
        die('여권사본을 찾을 수 없습니다.');
    }

    $imagePath = UPLOAD_PASSPORTS . '/' . $passport['passport_image'];
    $ext = pathinfo($passport['passport_image'], PATHINFO_EXTENSION);
    $filename = $passport['name_ko'] . '_여권사본.' . $ext;

    // 암호화된 파일인 경우 복호화
    if (strpos($passport['passport_image'], 'enc_') === 0) {
        $decrypted = decrypt_file($imagePath);
        if ($decrypted === false) {
            http_response_code(500);
            die('파일 복호화에 실패했습니다.');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($decrypted));
        echo $decrypted;
    } else {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($imagePath));
        readfile($imagePath);
    }
    exit;
}

/**
 * 관리자 여권사본 업로드
 */
function adminUploadPassport(): void {
    require_admin_auth();
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $eventId = input('event_id');
    $memberId = input('member_id');

    if (empty($eventId) || empty($memberId)) {
        json_error('행사와 회원을 선택해주세요.');
    }

    // 이미지 업로드
    $passportImage = null;
    if (!empty($_FILES['passport_image']['name'])) {
        $result = handle_file_upload($_FILES['passport_image'], UPLOAD_PASSPORTS, ALLOWED_DOCUMENT_TYPES);
        if ($result['success']) {
            // 파일 암호화
            $encryptedPath = UPLOAD_PASSPORTS . '/enc_' . $result['filename'];
            if (encrypt_file($result['path'], $encryptedPath)) {
                unlink($result['path']); // 원본 삭제
                $passportImage = 'enc_' . $result['filename'];
            } else {
                $passportImage = $result['filename'];
            }
        } else {
            json_error($result['message'] ?? '파일 업로드에 실패했습니다.');
        }
    } else {
        json_error('여권사본 이미지를 선택해주세요.');
    }

    $db = db();

    // 기존 여권 확인
    $stmt = $db->prepare("SELECT id, passport_image FROM passports WHERE member_id = ? AND event_id = ?");
    $stmt->execute([$memberId, $eventId]);
    $existing = $stmt->fetch();

    // 회원 정보 가져오기
    $stmt = $db->prepare("SELECT name_ko, phone FROM members WHERE id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();

    if ($existing) {
        // 기존 이미지 삭제
        if ($existing['passport_image']) {
            delete_file(UPLOAD_PASSPORTS . '/' . $existing['passport_image']);
        }

        $stmt = $db->prepare("UPDATE passports SET passport_image = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$passportImage, $existing['id']]);
    } else {
        // 새로 생성 (기본 정보만)
        $stmt = $db->prepare("INSERT INTO passports
            (member_id, event_id, name_ko, passport_image)
            VALUES (?, ?, ?, ?)");
        $stmt->execute([$memberId, $eventId, $member['name_ko'] ?? '', $passportImage]);
    }

    json_success(null, '여권사본이 업로드되었습니다.');
}
