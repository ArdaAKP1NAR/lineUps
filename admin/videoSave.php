<?php
/**
 * Video ekleme/güncelleme işlemi (POST ile videoOperations.php'den yönlendirilir)
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/dbConnection.php';
require_once __DIR__ . '/includes/authHelper.php';

$currentUser = checkLogin();
requireRole(['admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['save_video'])) {
    header('Location: videoOperations.php');
    exit;
}

$videoId = isset($_POST['video_id']) ? (int) $_POST['video_id'] : null;
$videoTitle = sanitize($_POST, 'title');
$videoSlug = sanitize($_POST, 'slug');
$thumbnailUrl = sanitize($_POST, 'thumbnail_url');
$description = sanitize($_POST, 'description');
$categoryIds = isset($_POST['category_ids']) && is_array($_POST['category_ids'])
    ? array_map('intval', $_POST['category_ids'])
    : [];
$galleryRaw = $_POST['gallery_json'] ?? '[]';
$galleryDecoded = json_decode($galleryRaw, true);
$galleryJson = is_array($galleryDecoded) ? json_encode(array_values(array_filter(array_map(function ($x) {
    $url = is_string($x) ? $x : (isset($x['url']) ? $x['url'] : '');
    return $url ? ['url' => $url] : null;
}, $galleryDecoded)))) : '[]';

if (!$videoTitle) {
    setFlash('error', 'Başlık zorunludur.');
    header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
    exit;
}

// Video dosyası: yeni yükleme veya düzenlemede mevcut
$videoUrl = null;
$videoUploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR;
if (!empty($_FILES['video_file']['tmp_name']) && is_uploaded_file($_FILES['video_file']['tmp_name'])) {
    $allowedVideo = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'];
    $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION) ?: '');
    if (!in_array($ext, $allowedVideo, true)) {
        setFlash('error', 'Geçersiz video formatı. İzin verilen: ' . implode(', ', $allowedVideo));
        header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
        exit;
    }
    if (!is_dir($videoUploadDir)) {
        @mkdir($videoUploadDir, 0755, true);
    }
    $videoFileName = 'video_' . ($videoId ?: 'new') . '_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES['video_file']['tmp_name'], $videoUploadDir . $videoFileName)) {
        $videoUrl = '/uploads/videos/' . $videoFileName;
    } else {
        setFlash('error', 'Video dosyası yüklenirken hata oluştu.');
        header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
        exit;
    }
} elseif ($videoId) {
    $stmt = $pdo->prepare('SELECT video_url FROM videos WHERE id = ? AND is_deleted = 0');
    $stmt->execute([$videoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $videoUrl = $row ? $row['video_url'] : null;
}

if (!$videoUrl) {
    setFlash('error', 'Video dosyası yükleyin veya düzenlemede mevcut video korunacaktır.');
    header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
    exit;
}

if (!$videoSlug) {
    $videoSlug = slugify($videoTitle);
}

// Dosya yükleme (thumbnail)
$uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}
if (!empty($_FILES['thumbnail_file']['tmp_name']) && is_uploaded_file($_FILES['thumbnail_file']['tmp_name'])) {
    $ext = pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $safeExt = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? strtolower($ext) : 'jpg';
    $newName = 'thumb_' . ($videoId ?: 'new') . '_' . time() . '.' . $safeExt;
    if (move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $uploadDir . $newName)) {
        $thumbnailUrl = '/uploads/thumbnails/' . $newName;
    }
}

try {
    if ($videoId) {
        $stmt = $pdo->prepare(
            'UPDATE videos SET title = ?, slug = ?, video_url = ?, thumbnail_url = ?, description = ?, gallery_json = ?, updated_at = NOW() WHERE id = ? AND is_deleted = 0'
        );
        $stmt->execute([$videoTitle, $videoSlug, $videoUrl, $thumbnailUrl ?: null, $description ?: null, $galleryJson, $videoId]);
        $pdo->prepare('DELETE FROM video_categories WHERE video_id = ?')->execute([$videoId]);
        $insertVideoId = $videoId;
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO videos (author_id, title, slug, video_url, thumbnail_url, description, gallery_json, is_published, is_deleted) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)'
        );
        $stmt->execute([$currentUser['id'], $videoTitle, $videoSlug, $videoUrl, $thumbnailUrl ?: null, $description ?: null, $galleryJson]);
        $insertVideoId = (int) $pdo->lastInsertId();
    }

    foreach ($categoryIds as $cid) {
        if ($cid > 0) {
            $pdo->prepare('INSERT INTO video_categories (video_id, category_id) VALUES (?, ?)')->execute([$insertVideoId, $cid]);
        }
    }

    setFlash('success', $videoId ? 'Video güncellendi.' : 'Video eklendi.');
} catch (Exception $e) {
    setFlash('error', 'Kayıt sırasında hata: ' . $e->getMessage());
}
header('Location: videoOperations.php');
exit;
