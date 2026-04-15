<?php
/**
 * 본투어 인터내셔날 - 상수 정의
 */

// 경로 설정
define('BASE_PATH', '/var/www/born');
define('BASE_URL', '');
define('ADMIN_URL', '/admin/');
define('USER_URL', '/user/');
define('API_URL', '/api/');
define('ASSETS_URL', '/assets/');

// 업로드 경로
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('UPLOAD_LOGOS', UPLOAD_PATH . '/logos');
define('UPLOAD_PASSPORTS', UPLOAD_PATH . '/passports');
define('UPLOAD_WEATHER', UPLOAD_PATH . '/weather');
define('UPLOAD_TEMP', UPLOAD_PATH . '/temp');

// 파일 업로드 제한
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif']);

// 세션 설정
define('SESSION_LIFETIME', 7200); // 2시간
define('SESSION_NAME', 'BORNTOUR_SESSION');

// 페이지네이션
define('ITEMS_PER_PAGE', 20);
define('ITEMS_PER_PAGE_ADMIN', 30);

// 시간대
define('APP_TIMEZONE', 'Asia/Seoul');
date_default_timezone_set(APP_TIMEZONE);

// 날짜 형식
define('DATE_FORMAT_KR', 'Y년 m월 d일');
define('DATE_FORMAT_DB', 'Y-m-d');
define('DATETIME_FORMAT_KR', 'Y년 m월 d일 H:i');
define('TIME_FORMAT_KR', 'H:i');

// 상태 값
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');

// 성별
define('GENDER_MALE', 'M');
define('GENDER_FEMALE', 'F');
define('GENDER_LABELS', [
    'M' => '남성',
    'F' => '여성'
]);

// 설문 질문 타입
define('QUESTION_TYPE_MULTIPLE', 'multiple');
define('QUESTION_TYPE_SHORT', 'short');
define('QUESTION_TYPE_LONG', 'long');
define('QUESTION_TYPE_LABELS', [
    'multiple' => '객관식',
    'short' => '단답형',
    'long' => '서술형'
]);

// 공지/문의 카테고리
define('NOTICE_CATEGORY_NOTICE', 'notice');
define('NOTICE_CATEGORY_FAQ', 'faq');
define('NOTICE_CATEGORY_LABELS', [
    'notice' => '공지사항',
    'faq' => '자주 묻는 질문'
]);

// 본투어 연락처 정보
define('COMPANY_NAME', '본투어 인터내셔날');
define('COMPANY_PHONE', '02-539-4666');
define('COMPANY_KAKAO', 'borntour');
define('COMPANY_EMAIL', 'info@borntour.co.kr');
define('KAKAO_CHANNEL_URL', 'https://pf.kakao.com/_borntour'); // 카카오톡 채널 URL

// 에러 메시지
define('ERROR_MESSAGES', [
    'auth_required' => '로그인이 필요합니다.',
    'invalid_credentials' => '아이디 또는 비밀번호가 올바르지 않습니다.',
    'permission_denied' => '접근 권한이 없습니다.',
    'not_found' => '요청하신 정보를 찾을 수 없습니다.',
    'invalid_request' => '잘못된 요청입니다.',
    'file_upload_error' => '파일 업로드에 실패했습니다.',
    'file_type_error' => '허용되지 않는 파일 형식입니다.',
    'file_size_error' => '파일 크기가 너무 큽니다.',
    'db_error' => '데이터 처리 중 오류가 발생했습니다.',
    'duplicate_entry' => '이미 등록된 정보입니다.',
    'validation_error' => '입력 정보를 확인해주세요.'
]);

// 성공 메시지
define('SUCCESS_MESSAGES', [
    'login_success' => '로그인되었습니다.',
    'logout_success' => '로그아웃되었습니다.',
    'save_success' => '저장되었습니다.',
    'delete_success' => '삭제되었습니다.',
    'update_success' => '수정되었습니다.',
    'upload_success' => '업로드되었습니다.',
    'submit_success' => '제출되었습니다.'
]);

// QR 코드 설정
define('QR_SIZE', 300);
define('QR_MARGIN', 2);

// 엑셀 설정
define('EXCEL_MAX_ROWS', 5000);
