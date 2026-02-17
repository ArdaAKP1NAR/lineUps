<?php
/**
 * Video debug API — sunucu yolları ve dosya listesi (JSON).
 * Sadece geliştirme / sorun giderme için. Canlıda kapatılabilir.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$fileName = isset($_GET['f']) ? basename($_GET['f']) : '';
$uploadsRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads');
$videosDir = $uploadsRoot ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . 'videos') : null;

$filesInUploads = [];
$filesInVideos = [];
if ($uploadsRoot && is_dir($uploadsRoot)) {
    $filesInUploads = array_values(array_diff(scandir($uploadsRoot), ['.', '..', '.htaccess', 'index.php']));
}
if ($videosDir && is_dir($videosDir)) {
    $filesInVideos = array_values(array_diff(scandir($videosDir), ['.', '..', '.htaccess', 'index.php']));
}

$resolved = [
    'requested_file' => $fileName,
    'found' => false,
    'path_used' => null,
    'in_videos_dir' => false,
    'in_uploads_root' => false,
];
if ($fileName !== '' && preg_match('/^[a-zA-Z0-9_\-\.]+$/', $fileName)) {
    if ($videosDir) {
        $try = $videosDir . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($try) && is_readable($try)) {
            $resolved['found'] = true;
            $resolved['path_used'] = $try;
            $resolved['in_videos_dir'] = true;
        }
    }
    if (!$resolved['found'] && $uploadsRoot) {
        $try = $uploadsRoot . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($try) && is_readable($try)) {
            $resolved['found'] = true;
            $resolved['path_used'] = $try;
            $resolved['in_uploads_root'] = true;
        }
    }
}

$videoPhpDir = __DIR__;
$videoPhpPath = $videoPhpDir . DIRECTORY_SEPARATOR . 'video.php';

echo json_encode([
    'ok' => true,
    'server' => [
        'document_root' => isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : null,
        'script_dir_frontend' => $videoPhpDir,
        'video_php_exists' => is_file($videoPhpPath),
        'uploads_path' => $uploadsRoot ?: null,
        'uploads_videos_path' => $videosDir ?: null,
        'uploads_path_readable' => $uploadsRoot ? is_readable($uploadsRoot) : false,
        'uploads_videos_readable' => $videosDir ? is_readable($videosDir) : false,
    ],
    'files' => [
        'in_uploads_root' => $filesInUploads,
        'in_uploads_videos' => $filesInVideos,
    ],
    'resolved' => $resolved,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
