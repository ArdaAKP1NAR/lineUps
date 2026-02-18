<?php
/**
 * Video oynatma: Veritabanındaki sadece dosya adı (f=) alınır,
 * uploads/videos/ klasörüyle birleştirilerek sunulur.
 * Accept-Ranges ve Content-Length ile tarayıcıda takılmadan oynatma desteklenir.
 */
error_reporting(0);
ini_set('display_errors', '0');

$fileName = isset($_GET['f']) ? basename($_GET['f']) : '';

if ($fileName === '' || preg_match('/[^a-zA-Z0-9_\-\.]/', $fileName)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Geçersiz dosya adı.';
    exit;
}

$uploadsRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads');
$videosDir = $uploadsRoot ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . 'videos') : null;

$filePath = null;
if ($videosDir && is_dir($videosDir)) {
    $candidatePath = $videosDir . DIRECTORY_SEPARATOR . $fileName;
    if (is_file($candidatePath) && is_readable($candidatePath)) {
        $filePath = $candidatePath;
    }
}
if (!$filePath && $uploadsRoot && is_dir($uploadsRoot)) {
    $candidatePath = $uploadsRoot . DIRECTORY_SEPARATOR . $fileName;
    if (is_file($candidatePath) && is_readable($candidatePath)) {
        $filePath = $candidatePath;
    }
}

if (!$filePath) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Video dosyası bulunamadı: ' . htmlspecialchars($fileName);
    exit;
}

$fileSize = filesize($filePath);
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$contentType = 'video/mp4';
if ($fileExt === 'webm') $contentType = 'video/webm';
elseif ($fileExt === 'mov') $contentType = 'video/quicktime';
elseif ($fileExt === 'avi') $contentType = 'video/x-msvideo';
elseif ($fileExt === 'mkv' || $fileExt === 'm4v') $contentType = $fileExt === 'm4v' ? 'video/mp4' : 'video/x-matroska';

if (ob_get_level()) ob_end_clean();

header('Content-Type: ' . $contentType);
header('Accept-Ranges: bytes');
header('Content-Length: ' . $fileSize);
header('Cache-Control: public, max-age=3600');

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $rangeMatch)) {
    $rangeStart = (int) $rangeMatch[1];
    $rangeEnd = ($rangeMatch[2] !== '') ? (int) $rangeMatch[2] : $fileSize - 1;
    $rangeEnd = min($rangeEnd, $fileSize - 1);
    $rangeLength = $rangeEnd - $rangeStart + 1;
    http_response_code(206);
    header('Content-Length: ' . $rangeLength);
    header('Content-Range: bytes ' . $rangeStart . '-' . $rangeEnd . '/' . $fileSize);
    $fileHandle = fopen($filePath, 'rb');
    if ($fileHandle) {
        fseek($fileHandle, $rangeStart);
        echo fread($fileHandle, $rangeLength);
        fclose($fileHandle);
    }
    exit;
}

readfile($filePath);
