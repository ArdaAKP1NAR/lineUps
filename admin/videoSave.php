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
$categoryIds = [];
if (isset($_POST['category_ids']) && is_array($_POST['category_ids'])) {
    $categoryIds = array_map('intval', $_POST['category_ids']);
} elseif (!empty($_POST['category_ids'])) {
    $categoryIds = is_array($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : [(int) $_POST['category_ids']];
}
$categoryIds = array_filter($categoryIds, function ($id) { return $id > 0; });
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

// Video yükleme: proje kökü/uploads/videos/ (tam yol, klasör yoksa oluşturulur)
$videoUrl = null;
$projectRoot = dirname(__DIR__);
$videoUploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR;

$uploadError = $_FILES['video_file']['error'] ?? null;
if ($uploadError !== null && $uploadError !== UPLOAD_ERR_OK) {
    if ($uploadError === UPLOAD_ERR_NO_FILE && $videoId) {
        // Düzenlemede yeni dosya seçilmemiş; mevcut video korunacak
    } else {
        $uploadErrorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Dosya sunucu limitini aşıyor (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE => 'Dosya form limitini aşıyor.',
            UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi. Lütfen tekrar deneyin.',
            UPLOAD_ERR_NO_FILE => 'Video dosyası seçin (yeni video için zorunlu).',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucuda geçici klasör bulunamadı.',
            UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı. Klasör izinlerini kontrol edin.',
            UPLOAD_ERR_EXTENSION => 'Bir PHP eklentisi yüklemeyi durdurdu.',
        ];
        setFlash('error', 'Video yükleme hatası: ' . ($uploadErrorMessages[$uploadError] ?? 'Kod ' . $uploadError));
        header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
        exit;
    }
}

if (!empty($_FILES['video_file']['tmp_name']) && is_uploaded_file($_FILES['video_file']['tmp_name'])) {
    $allowedVideo = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'];
    $originalFileName = $_FILES['video_file']['name'];
    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION) ?: '');

    if (!in_array($fileExtension, $allowedVideo, true)) {
        $tmpPath = $_FILES['video_file']['tmp_name'];
        if ($fileExtension === '' && is_file($tmpPath) && function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = @finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
                $mimeToExt = ['video/mp4' => 'mp4', 'video/x-mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov', 'video/x-msvideo' => 'avi', 'video/x-matroska' => 'mkv'];
                if (!empty($mime) && isset($mimeToExt[$mime])) {
                    $fileExtension = $mimeToExt[$mime];
                } elseif (!empty($mime) && strpos($mime, 'video/') === 0) {
                    $fileExtension = 'mp4';
                }
            }
        }
        if ($fileExtension === '' && !empty($_FILES['video_file']['type']) && strpos($_FILES['video_file']['type'], 'video/') === 0) {
            $fileExtension = 'mp4';
        }
        if (!in_array($fileExtension, $allowedVideo, true)) {
            setFlash('error', 'Geçersiz video formatı. İzin verilen: ' . implode(', ', $allowedVideo) . '.');
            header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
            exit;
        }
    }

    if (!is_dir($videoUploadDir)) {
        if (!@mkdir($videoUploadDir, 0755, true)) {
            setFlash('error', 'uploads/videos klasörü oluşturulamadı. Klasör izinlerini kontrol edin.');
            header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
            exit;
        }
    }

    $uploadedFileName = basename($_FILES['video_file']['name']);
    $uploadedFileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $uploadedFileName);
    if ($uploadedFileName === '') {
        $uploadedFileName = 'video.' . $fileExtension;
    }
    $destinationPath = $videoUploadDir . $uploadedFileName;
    if (file_exists($destinationPath)) {
        $uploadedFileName = time() . '_' . $uploadedFileName;
        $destinationPath = $videoUploadDir . $uploadedFileName;
    }

    if (!move_uploaded_file($_FILES['video_file']['tmp_name'], $destinationPath)) {
        setFlash('error', 'Video dosyası taşınamadı. Klasör yazma iznini ve sunucu limitlerini kontrol edin.');
        header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
        exit;
    }
    if (!is_file($destinationPath) || !is_readable($destinationPath)) {
        @unlink($destinationPath);
        setFlash('error', 'Video kaydedildi ancak dosya okunamıyor. Klasör izinlerini kontrol edin.');
        header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
        exit;
    }

    $videoUrl = $uploadedFileName;
} elseif ($videoId) {
    $stmt = $pdo->prepare('SELECT video_url, thumbnail_url FROM videos WHERE id = ? AND is_deleted = 0');
    $stmt->execute([$videoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $videoUrl = $row['video_url'];
        if ($videoUrl !== null && $videoUrl !== '') {
            $videoUrl = basename($videoUrl);
        }
        if (empty($thumbnailUrl) && !empty($row['thumbnail_url'])) {
            $thumbnailUrl = $row['thumbnail_url'];
        }
    }
}

if (!$videoUrl) {
    setFlash('error', 'Video dosyası yükleyin veya düzenlemede mevcut video korunacaktır.');
    header('Location: videoOperations.php?' . ($videoId ? 'edit=' . $videoId : 'add=1'));
    exit;
}

if (!$videoSlug) {
    $videoSlug = slugify($videoTitle);
}

// Thumbnail: proje kökü uploads/thumbnails/
$thumbDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR;
if (!is_dir($thumbDir)) {
    @mkdir($thumbDir, 0755, true);
}
if (!empty($_FILES['thumbnail_file']['tmp_name']) && is_uploaded_file($_FILES['thumbnail_file']['tmp_name'])) {
    $ext = pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $safeExt = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? strtolower($ext) : 'jpg';
    $thumbName = 'thumb_' . ($videoId ?: 'new') . '_' . time() . '.' . $safeExt;
    if (move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $thumbDir . $thumbName)) {
        $thumbnailUrl = '/uploads/thumbnails/' . $thumbName;
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
