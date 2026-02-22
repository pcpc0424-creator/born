<?php
/**
 * 본투어 인터내셔날 - 메인 라우터
 */

// 기본 경로 설정
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// 정적 파일은 직접 서빙
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf'];
$extension = pathinfo($path, PATHINFO_EXTENSION);

if (in_array($extension, $staticExtensions)) {
    return false;
}

// 라우팅
switch (true) {
    // 메인 페이지 - 인트로 화면으로 리다이렉트
    case $path === '/' || $path === '':
        header('Location: /born/user/intro.php');
        exit;

    // 관리자 페이지
    case str_starts_with($path, '/admin'):
        $adminPath = str_replace('/admin', '', $path) ?: '/index.php';
        if ($adminPath === '/') $adminPath = '/index.php';
        $file = __DIR__ . '/admin' . $adminPath;
        if (file_exists($file)) {
            include $file;
        } else {
            header('Location: /born/admin/index.php');
        }
        exit;

    // 사용자 페이지
    case str_starts_with($path, '/user'):
        $userPath = str_replace('/user', '', $path) ?: '/index.php';
        if ($userPath === '/') $userPath = '/index.php';
        $file = __DIR__ . '/user' . $userPath;
        if (file_exists($file)) {
            include $file;
        } else {
            header('Location: /born/user/index.php');
        }
        exit;

    // API
    case str_starts_with($path, '/api'):
        $apiPath = str_replace('/api', '', $path);
        $file = __DIR__ . '/api' . $apiPath;
        if (file_exists($file)) {
            include $file;
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
        }
        exit;

    // QR 코드/고유 링크로 접근
    case preg_match('/^\/e\/([A-Z0-9]+)$/i', $path, $matches):
        $uniqueCode = strtoupper($matches[1]);
        header("Location: /born/user/intro.php?code={$uniqueCode}");
        exit;

    // 기본 - 인트로 화면으로
    default:
        header('Location: /born/user/intro.php');
        exit;
}
