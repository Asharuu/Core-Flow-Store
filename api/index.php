<?php
// Router Serverless Function untuk Vercel
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = trim($requestUri, '/');

// Jika root path ('/' atau 'index.php'), arahkan ke index.php utama
if ($requestUri === '' || $requestUri === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$rootPath = realpath(__DIR__ . '/..');
$targetFile = realpath(__DIR__ . '/../' . $requestUri);

// Pastikan file berada dalam root direktori project (keamanan path traversal)
if ($targetFile && strpos($targetFile, $rootPath) === 0 && is_file($targetFile)) {
    $ext = pathinfo($targetFile, PATHINFO_EXTENSION);
    if ($ext === 'php') {
        require $targetFile;
        exit;
    }
}

// Cek apakah ada file PHP yang cocok jika URL dipanggil tanpa ekstensi .php (contoh: /pos atau /vendor)
$targetWithExt = realpath(__DIR__ . '/../' . $requestUri . '.php');
if ($targetWithExt && strpos($targetWithExt, $rootPath) === 0 && is_file($targetWithExt)) {
    require $targetWithExt;
    exit;
}

http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($requestUri);
