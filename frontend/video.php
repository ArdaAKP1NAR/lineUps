<?php
/**
 * Video stream: uploads/videos/ içindeki dosyayı sunar.
 * f = dosya adı (örn. video_3_1771353204.mp4). Accept-Ranges ile seek desteklenir.
 */
error_reporting(0);
ini_set('display_errors', '0');

$fileName = $_GET['f'] ?? '';
$fileName = basename($fileName);

if (!$fileName || preg_match('/[^a-zA-Z0-9_\-\.]/', $fileName)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Geçersiz dosya adı.';
    exit;
}

$uploadsRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads');
$videosDir = $uploadsRoot ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . 'videos') : null;
// Önce uploads/videos/, yoksa uploads/ kökü (eski yüklemeler için)
$filePath = null;
if ($videosDir && is_dir($videosDir)) {
    $try = $videosDir . DIRECTORY_SEPARATOR . $fileName;
    if (is_file($try) && is_readable($try)) $filePath = $try;
}
if (!$filePath && $uploadsRoot && is_dir($uploadsRoot)) {
    $try = $uploadsRoot . DIRECTORY_SEPARATOR . $fileName;
    if (is_file($try) && is_readable($try)) $filePath = $try;
}
if (!$filePath) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Video dosyası bulunamadı: ' . htmlspecialchars($fileName);
    exit;
}

$size = filesize($filePath);
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$mime = 'video/mp4';
if ($ext === 'webm') $mime = 'video/webm';
elseif ($ext === 'mov') $mime = 'video/quicktime';
elseif ($ext === 'avi') $mime = 'video/x-msvideo';
elseif ($ext === 'mkv' || $ext === 'm4v') $mime = $ext === 'm4v' ? 'video/mp4' : 'video/x-matroska';

if (ob_get_level()) ob_end_clean();

header('Content-Type: ' . $mime);
header('Accept-Ranges: bytes');
header('Content-Length: ' . $size);
header('Cache-Control: public, max-age=3600');

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
    $start = (int) $m[1];
    $end = ($m[2] !== '') ? (int) $m[2] : $size - 1;
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
