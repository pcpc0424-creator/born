<?php
/**
 * 본투어 인터내셔날 - 공통 함수
 */

/**
 * XSS 방지를 위한 HTML 이스케이프
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * JSON 응답 출력
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 성공 응답
 */
function json_success(mixed $data = null, string $message = ''): void {
    json_response([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * 에러 응답
 */
function json_error(string $message, int $statusCode = 400, array $errors = []): void {
    json_response([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $statusCode);
}

/**
 * CSRF 토큰 생성
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF 토큰 검증
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 리다이렉트
 */
function redirect(string $url, int $statusCode = 302): void {
    http_response_code($statusCode);
    header("Location: {$url}");
    exit;
}

/**
 * 현재 URL 반환
 */
function current_url(): string {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * 랜덤 문자열 생성
 */
function generate_random_string(int $length = 16): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 고유 코드 생성 (행사용)
 */
function generate_unique_code(): string {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

/**
 * 날짜 포맷팅 (한국어)
 */
function format_date_kr(string $date, bool $includeTime = false): string {
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    $format = $includeTime ? DATE_FORMAT_KR . ' H:i' : DATE_FORMAT_KR;
    return date($format, $timestamp);
}

/**
 * 날짜 포맷팅 (간단)
 */
function format_date_short(string $date): string {
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('m.d', $timestamp);
}

/**
 * 날짜+시간 포맷팅 (한국어)
 */
function format_datetime_kr(string $datetime): string {
    return format_date_kr($datetime, true);
}

/**
 * 시간 포맷팅
 */
function format_time(string $time): string {
    if (empty($time)) {
        return '';
    }

    $timestamp = strtotime($time);
    if ($timestamp === false) {
        return $time;
    }

    return date('H:i', $timestamp);
}

/**
 * D-Day 계산
 */
function calculate_dday(string $targetDate): array {
    $today = new DateTime('today');
    $target = new DateTime($targetDate);
    $diff = $today->diff($target);

    $days = (int)$diff->format('%r%a');

    if ($days === 0) {
        return ['text' => 'D-Day', 'days' => 0, 'isPast' => false];
    } elseif ($days > 0) {
        return ['text' => "D-{$days}", 'days' => $days, 'isPast' => false];
    } else {
        $absDays = abs($days);
        return ['text' => "D+{$absDays}", 'days' => $absDays, 'isPast' => true];
    }
}

/**
 * 전화번호 포맷팅
 */
function format_phone(string $phone): string {
    $cleaned = preg_replace('/[^0-9]/', '', $phone);

    if (strlen($cleaned) === 11) {
        return substr($cleaned, 0, 3) . '-' . substr($cleaned, 3, 4) . '-' . substr($cleaned, 7);
    } elseif (strlen($cleaned) === 10) {
        return substr($cleaned, 0, 3) . '-' . substr($cleaned, 3, 3) . '-' . substr($cleaned, 6);
    }

    return $phone;
}

/**
 * 금액 포맷팅
 */
function format_price(int $price): string {
    if ($price === 0) {
        return '무료';
    }
    return number_format($price) . '원';
}

/**
 * 파일 확장자 추출
 */
function get_file_extension(string $filename): string {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * 파일 업로드 처리
 */
function handle_file_upload(array $file, string $uploadDir, array $allowedTypes = null): array {
    // 업로드 에러 체크
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => '파일 업로드에 실패했습니다.'];
    }

    // 파일 크기 체크
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => '파일 크기가 너무 큽니다. (최대 10MB)'];
    }

    // 파일 타입 체크
    $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => '허용되지 않는 파일 형식입니다.'];
    }

    // 파일명 생성
    $extension = get_file_extension($file['name']);
    $newFilename = generate_random_string(16) . '.' . $extension;
    $uploadPath = $uploadDir . '/' . $newFilename;

    // 디렉토리 생성
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 파일 이동
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => false, 'error' => '파일 저장에 실패했습니다.'];
    }

    return [
        'success' => true,
        'filename' => $newFilename,
        'path' => $uploadPath,
        'mime_type' => $mimeType,
        'size' => $file['size']
    ];
}

/**
 * 파일 삭제
 */
function delete_file(string $filepath): bool {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true;
}

/**
 * 세션에서 플래시 메시지 가져오기
 */
function get_flash_message(string $key): ?string {
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

/**
 * 세션에 플래시 메시지 설정
 */
function set_flash_message(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

/**
 * 요청 메소드 확인
 */
function is_post(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function is_get(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

function is_ajax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * 입력값 가져오기
 */
function input(string $key, mixed $default = null): mixed {
    if (isset($_POST[$key])) {
        return is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
    }
    if (isset($_GET[$key])) {
        return is_string($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key];
    }
    return $default;
}

/**
 * 필수 입력값 검증
 */
function validate_required(array $fields, array $data): array {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty($data[$field])) {
            $errors[$field] = "{$label}을(를) 입력해주세요.";
        }
    }
    return $errors;
}

/**
 * 이메일 검증
 */
function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 전화번호 검증 (한국 형식)
 */
function validate_phone(string $phone): bool {
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^01[0-9]{8,9}$/', $cleaned) === 1;
}

/**
 * 생년월일 검증
 */
function validate_birth_date(string $date): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    $parts = explode('-', $date);
    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

/**
 * 페이지네이션 계산
 */
function calculate_pagination(int $totalItems, int $currentPage, int $perPage = ITEMS_PER_PAGE): array {
    $totalPages = max(1, ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * 요일 반환 (한국어)
 */
function get_weekday_kr(string $date): string {
    $weekdays = ['일', '월', '화', '수', '목', '금', '토'];
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }
    return $weekdays[date('w', $timestamp)];
}

/**
 * 날짜 범위 텍스트 생성
 */
function format_date_range(string $startDate, string $endDate): string {
    $start = strtotime($startDate);
    $end = strtotime($endDate);

    if ($start === false || $end === false) {
        return '';
    }

    $startYear = date('Y', $start);
    $endYear = date('Y', $end);

    if ($startYear === $endYear) {
        return date('Y년 m월 d일', $start) . ' ~ ' . date('m월 d일', $end);
    }

    return date('Y년 m월 d일', $start) . ' ~ ' . date('Y년 m월 d일', $end);
}

/**
 * 로그 기록
 */
function app_log(string $message, string $level = 'info'): void {
    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * 디버그 출력 (개발용)
 */
function dd(mixed $data): void {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    exit;
}
