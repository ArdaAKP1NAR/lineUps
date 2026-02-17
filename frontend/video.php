<?php
/**
 * Video stream endpoint: uploads/videos/ içindeki dosyayı sunar.
 * f parametresi: dosya adı (örn. video_3_1771353204.mp4). basePath config'ten; path DOCUMENT_ROOT üzerinden.
 */
error_reporting(0);
ini_set('display_errors', '0');

$configPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'adminpanel' . DIRECTORY_SEPARATOR . 'config.php';
$config = is_file($configPath) ? (require $configPath) : [];
$apiPath = isset($config['apiPath']) ? $config['apiPath'] : '';
$apiPath = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $apiPath), DIRECTORY_SEPARATOR);
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) : '';

$filename = isset($_GET['f']) ? $_GET['f'] : '';
$filename = basename($filename);
if ($filename === '' || preg_match('/[^a-zA-Z0-9_\-\.]/', $filename)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Geçersiz dosya adı.';
    exit;
}

if ($docRoot !== '' && $apiPath !== '') {
    $videoDir = realpath($docRoot . DIRECTORY_SEPARATOR . $apiPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'videos');
} else {
    $videoDir = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'videos');
}

if (!$videoDir || !is_dir($videoDir)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Video klasörü bulunamadı.';
    exit;
}

$filePath = $videoDir . DIRECTORY_SEPARATOR . $filename;
if (!is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    $list = @scandir($videoDir) ?: [];
    $files = array_diff($list, ['.', '..', 'index.php']);
    $fileList = count($files) > 0 ? implode(', ', array_slice($files, 0, 20)) : '(klasör boş)';
    echo "Video dosyası bulunamadı: " . $filename . "\n\n";
    echo "Aranan konum: uploads/videos/\n";
    echo "Klasördeki dosyalar: " . $fileList . "\n\n";
    echo "Çözüm: Admin panelden bu videoyu düzenleyin, Video dosyası alanından dosyayı tekrar seçip Güncelle deyin.";
    exit;
}

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mimeMap = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mov' => 'video/quicktime',
    'avi' => 'video/x-msvideo',
    'mkv' => 'video/x-matroska',
    'm4v' => 'video/mp4',
];
$contentType = $mimeMap[$ext] ?? 'video/mp4';

$size = filesize($filePath);

if (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: ' . $contentType);
header('Content-Length: ' . $size);
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
    $start = (int) $m[1];
    $end = $m[2] !== '' ? (int) $m[2] : $size - 1;
    $end = min($end, $size - 1);
    $length = $end - $start + 1;
    http_response_code(206);
    header('Content-Length: ' . $length);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    $fp = fopen($filePath, 'rb');
    if ($fp) {
        fseek($fp, $start);
        echo fread($fp, $length);
        fclose($fp);
    }
    exit;
}

readfile($filePath);
