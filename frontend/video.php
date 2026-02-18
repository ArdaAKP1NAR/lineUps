<?php
/**
 * Video dosyasını güvenli şekilde sunar.
 * Admin panelde yüklenen videolar uploads/videos/ içinde; bu script oradan okur ve stream eder.
 */
error_reporting(0);
$filename = isset($_GET['f']) ? $_GET['f'] : '';
$filename = basename($filename);
if ($filename === '' || preg_match('/[^a-zA-Z0-9_\-\.]/', $filename)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Geçersiz dosya adı.';
    exit;
}

$videoDir = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'videos');
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
    echo "Çözüm: Admin panelden bu videoyu düzenleyin, 'Video dosyası' alanından dosyayı tekrar seçip Güncelle deyin. Dosya 'DosyaAdı_zaman.mp4' olarak kaydedilir.";
    exit;
}

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mimes = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mov' => 'video/quicktime',
    'avi' => 'video/x-msvideo',
    'mkv' => 'video/x-matroska',
    'm4v' => 'video/mp4',
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

$size = filesize($filePath);
header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    if (preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
        $start = (int) $m[1];
        $end = $m[2] !== '' ? (int) $m[2] : $size - 1;
        $end = min($end, $size - 1);
        $length = $end - $start + 1;
        http_response_code(206);
        header('Content-Length: ' . $length);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        $fp = fopen($filePath, 'rb');
        fseek($fp, $start);
        echo fread($fp, $length);
        fclose($fp);
        exit;
    }
}

readfile($filePath);
