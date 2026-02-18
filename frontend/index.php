<?php
/**
 * Frontend veri motoru — HTML döndürmez.
 * Veritabanından videos ve categories çekip JSON olarak index.js'e sunar.
 * Tüm sorgularda PDO kullanılır.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

require_once __DIR__ . '/../admin/includes/helpers.php';

try {
    require_once __DIR__ . '/../admin/includes/dbConnection.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Veritabanı bağlantısı kurulamadı.', 'success' => false]);
    exit;
}

// Sadece aktif kategoriler
$stmt = $pdo->query(
    "SELECT id, parent_id, name, slug, type, order_index, icon
     FROM categories
     WHERE is_active = 1
     ORDER BY type, order_index, name"
);
$categoriesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$navbar = [];
$game_content = [];
foreach ($categoriesRaw as $c) {
    $row = [
        'id'       => (int) $c['id'],
        'parent_id'=> $c['parent_id'] !== null ? (int) $c['parent_id'] : null,
        'name'     => $c['name'],
        'slug'     => $c['slug'],
        'order_index' => (int) $c['order_index'],
        'icon'     => $c['icon'],
    ];
    if ($c['type'] === 'navbar') {
        $navbar[] = $row;
    } elseif ($c['type'] === 'game_content') {
        $game_content[] = $row;
    }
}

// Yayında ve silinmemiş videolar
$stmt = $pdo->query(
    "SELECT v.id, v.title, v.slug, v.description, v.video_url, v.thumbnail_url, v.gallery_json
     FROM videos v
     WHERE v.is_published = 1 AND v.is_deleted = 0
     ORDER BY v.created_at DESC"
);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Her video için category_id listesi
foreach ($videos as &$v) {
    $v['id'] = (int) $v['id'];
    $v['category_ids'] = [];
    $st = $pdo->prepare('SELECT category_id FROM video_categories WHERE video_id = ?');
    $st->execute([$v['id']]);
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $v['category_ids'][] = (int) $row['category_id'];
    }
    $v['gallery_json'] = $v['gallery_json'] ? json_decode($v['gallery_json'], true) : [];
}
unset($v);

// base_path: config.php apiPath (tek merkez). Video/thumbnail linkleri bu prefix ile oluşturulur.
$configPath = __DIR__ . '/../adminpanel/config.php';
$appConfig = is_file($configPath) ? (require $configPath) : [];
$basePath = isset($appConfig['apiPath']) ? rtrim($appConfig['apiPath'], '/') : '';
if ($basePath === '' && ($projectDir = basename(dirname(__DIR__))) !== '' && $projectDir !== '.') {
    $basePath = '/' . $projectDir;
}

echo json_encode([
    'success' => true,
    'base_path' => $basePath,
    'categories' => [
        'navbar'       => $navbar,
        'game_content' => $game_content,
    ],
    'videos' => $videos,
], JSON_UNESCAPED_UNICODE);
