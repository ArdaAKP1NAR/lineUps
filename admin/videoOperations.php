<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/dbConnection.php';
require_once __DIR__ . '/includes/authHelper.php';

$currentUser = checkLogin();
requireRole(['admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_video'])) {
    require_once __DIR__ . '/videoSave.php';
    exit;
}

$successMsg = getFlash('success');
$errorMsg = getFlash('error');

// Toggle yayın durumu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['video_id'])) {
    if ($_POST['action'] === 'toggle_published') {
        $videoId = (int) $_POST['video_id'];
        $stmt = $pdo->prepare('UPDATE videos SET is_published = NOT is_published WHERE id = ? AND is_deleted = 0');
        $stmt->execute([$videoId]);
        setFlash('success', 'Yayın durumu güncellendi.');
        header('Location: videoOperations.php');
        exit;
    }
}

// Liste: videolar + author + kategoriler
$stmt = $pdo->query(
    'SELECT v.id, v.author_id, v.title, v.slug, v.video_url, v.thumbnail_url, v.is_published, v.is_deleted, v.created_at,
            u.username AS author_name
     FROM videos v
     LEFT JOIN users u ON u.id = v.author_id
     WHERE v.is_deleted = 0
     ORDER BY v.created_at DESC'
);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($videos as &$v) {
    $v['categoryIds'] = [];
    $st = $pdo->prepare('SELECT category_id FROM video_categories WHERE video_id = ?');
    $st->execute([$v['id']]);
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $v['categoryIds'][] = (int) $row['category_id'];
    }
}
unset($v);

$categoriesForSelect = $pdo->query('SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Video Yönetimi';
require_once __DIR__ . '/includes/layout_header.php';
require_once __DIR__ . '/includes/layout_sidebar.php';
?>
<div class="min-h-screen pl-64 transition-[padding] duration-300">
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-700/50 bg-slate-900/80 px-6 backdrop-blur-md">
        <h1 class="text-lg font-semibold text-white">Video Yönetimi</h1>
        <div class="flex items-center gap-3">
            <a href="videoOperations.php?add=1" class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2 text-sm font-medium text-white transition-all hover:bg-indigo-600">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Yeni Video
            </a>
            <a href="dashboard.php" class="rounded-xl p-2 text-slate-400 hover:bg-slate-800 hover:text-white">
                <i data-lucide="user" class="h-5 w-5"></i>
            </a>
        </div>
    </header>

    <main class="p-6">
        <?php if ($successMsg): ?>
            <div class="mb-4 flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                <i data-lucide="check-circle" class="h-4 w-4"></i>
                <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="mb-4 flex items-center gap-2 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                <i data-lucide="alert-circle" class="h-4 w-4"></i>
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
            <?php
            $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
            $videoTitle = $videoSlug = $videoUrl = $thumbnailUrl = $description = '';
            $selectedCategoryIds = [];
            $galleryJson = '[]';
            if ($editId) {
                $st = $pdo->prepare('SELECT * FROM videos WHERE id = ? AND is_deleted = 0');
                $st->execute([$editId]);
                $editRow = $st->fetch(PDO::FETCH_ASSOC);
                if (!$editRow) {
                    setFlash('error', 'Video bulunamadı.');
                    header('Location: videoOperations.php');
                    exit;
                }
                $videoTitle = $editRow['title'];
                $videoSlug = $editRow['slug'];
                $videoUrl = $editRow['video_url'];
                $thumbnailUrl = $editRow['thumbnail_url'] ?? '';
                $description = $editRow['description'] ?? '';
                $galleryJson = $editRow['gallery_json'] ?? '[]';
                if (is_array($galleryJson)) {
                    $galleryJson = json_encode($galleryJson);
                }
                $st = $pdo->prepare('SELECT category_id FROM video_categories WHERE video_id = ?');
                $st->execute([$editId]);
                $selectedCategoryIds = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'category_id');
            }
            ?>
            <script>window.__galleryInitial = <?= json_encode($galleryJson ?? '[]') ?>;</script>
            <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-6">
                <h2 class="mb-6 text-lg font-semibold text-white"><?= $editId ? 'Videoyu düzenle' : 'Yeni video ekle' ?></h2>

                <form method="post" action="" enctype="multipart/form-data" x-data="videoForm()">
                    <input type="hidden" name="save_video" value="1">
                    <?php if ($editId): ?>
                        <input type="hidden" name="video_id" value="<?= $editId ?>">
                    <?php endif; ?>

                    <div class="space-y-6">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Başlık</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($videoTitle) ?>"
                                   class="w-full max-w-xl rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                                   required>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Slug (URL)</label>
                            <input type="text" name="slug" value="<?= htmlspecialchars($videoSlug) ?>"
                                   class="w-full max-w-xl rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                                   placeholder="sova-bind-a-site">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Video dosyası</label>
                            <?php if ($editId && $videoUrl): ?>
                                <p class="mb-2 text-sm text-slate-400">Mevcut: <a href="<?= htmlspecialchars($videoUrl) ?>" target="_blank" class="text-indigo-400 hover:underline"><?= htmlspecialchars(basename(parse_url($videoUrl, PHP_URL_PATH))) ?></a></p>
                                <p class="mb-2 text-xs text-slate-500">Yeni dosya seçerseniz mevcut video değişir.</p>
                            <?php endif; ?>
                            <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-600 bg-slate-800/50 py-8 transition-all hover:border-indigo-500/50 hover:bg-slate-800 max-w-xl">
                                <i data-lucide="video" class="mb-2 h-10 w-10 text-slate-500"></i>
                                <span class="mb-1 text-sm font-medium text-slate-300">Video yükle</span>
                                <span class="text-xs text-slate-500">MP4, WebM, MOV, AVI, MKV (sürükle-bırak veya tıklayın)</span>
                                <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska,.mp4,.webm,.mov,.avi,.mkv,.m4v" class="mt-2 hidden" <?= $editId ? '' : 'required' ?>>
                                <p class="mt-2 text-xs text-slate-500">Kaydedilen ad: <strong>DosyaAdı_zaman.mp4</strong> (örn. DenemeKayit_1739123456.mp4). Sitede index bu isimle oynatır.</p>
                            </label>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Kapak görseli URL</label>
                            <input type="url" name="thumbnail_url" value="<?= htmlspecialchars($thumbnailUrl) ?>"
                                   class="w-full max-w-xl rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Kapak / thumbnail (sürükle-bırak veya seç)</label>
                            <div class="mt-2 flex flex-wrap gap-4">
                                <label class="flex h-32 w-48 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-600 bg-slate-800/50 transition-all hover:border-indigo-500/50 hover:bg-slate-800">
                                    <i data-lucide="upload" class="mb-2 h-8 w-8 text-slate-500"></i>
                                    <span class="text-sm text-slate-400">Dosya seç</span>
                                    <input type="file" name="thumbnail_file" accept="image/*" class="hidden">
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Açıklama</label>
                            <textarea name="description" rows="4" class="w-full max-w-xl rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"><?= htmlspecialchars($description) ?></textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Kategoriler</label>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($categoriesForSelect as $cat): ?>
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-600 bg-slate-800 px-3 py-1.5 text-sm transition-all hover:border-indigo-500/50 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-500/20 has-[:checked]:text-indigo-300">
                                        <input type="checkbox" name="category_ids[]" value="<?= (int) $cat['id'] ?>"
                                               <?= in_array((int) $cat['id'], $selectedCategoryIds) ? 'checked' : '' ?>
                                               class="rounded border-slate-500 text-indigo-500 focus:ring-indigo-500">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Seçilenler badge olarak listelenir.</p>
                        </div>
                        <div x-data="galleryEditor(window.__galleryInitial || '[]')">
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Galeri (ek görsel/video URL’leri)</label>
                            <template x-for="(item, index) in items" :key="index">
                                <div class="mb-2 flex gap-2">
                                    <input type="url" :value="item.url" @input="item.url = $event.target.value"
                                           class="flex-1 rounded-xl border border-slate-600 bg-slate-800 px-4 py-2 text-white focus:border-indigo-500 focus:outline-none"
                                           :placeholder="'URL ' + (index + 1)">
                                    <button type="button" @click="removeItem(index)" class="rounded-xl p-2 text-slate-400 hover:bg-red-500/20 hover:text-red-400" title="Sil">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </template>
                            <input type="hidden" name="gallery_json" :value="JSON.stringify(items)">
                            <button type="button" @click="addItem()" class="mt-2 inline-flex items-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-4 py-2 text-sm text-slate-300 transition-all hover:border-indigo-500/50 hover:text-white">
                                <i data-lucide="plus" class="h-4 w-4"></i>
                                Yeni Ekle
                            </button>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="rounded-xl bg-indigo-500 px-5 py-2.5 font-medium text-white transition-all hover:bg-indigo-600">
                                <?= $editId ? 'Güncelle' : 'Kaydet' ?>
                            </button>
                            <a href="videoOperations.php" class="rounded-xl border border-slate-600 px-5 py-2.5 text-slate-300 hover:bg-slate-800">İptal</a>
                        </div>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-700 bg-slate-800/80 text-slate-400">
                            <tr>
                                <th class="px-4 py-3 font-medium">Video</th>
                                <th class="px-4 py-3 font-medium">Yazar</th>
                                <th class="px-4 py-3 font-medium">Yayın</th>
                                <th class="px-4 py-3 font-medium">Tarih</th>
                                <th class="px-4 py-3 font-medium text-right">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($videos as $video): ?>
                                <tr class="border-b border-slate-700/50 transition-colors hover:bg-slate-700/30">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-white"><?= htmlspecialchars($video['title']) ?></div>
                                        <div class="text-xs text-slate-500">/<?= htmlspecialchars($video['slug']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-400"><?= htmlspecialchars($video['author_name'] ?? '') ?></td>
                                    <td class="px-4 py-3">
                                        <form method="post" class="inline">
                                            <input type="hidden" name="action" value="toggle_published">
                                            <input type="hidden" name="video_id" value="<?= (int) $video['id'] ?>">
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition-all <?= $video['is_published'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                                                <?= $video['is_published'] ? 'Yayında' : 'Taslak' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500"><?= date('d.m.Y', strtotime($video['created_at'])) ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="videoOperations.php?edit=<?= (int) $video['id'] ?>" class="inline-flex rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white" title="Düzenle">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($videos)): ?>
                    <div class="px-4 py-12 text-center text-slate-500">Henüz video yok. "Yeni Video" ile ekleyebilirsiniz.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
<script>
function videoForm() {
    return {};
}
function galleryEditor(initial) {
    var arr = [];
    try {
        arr = typeof initial === 'string' ? JSON.parse(initial) : (Array.isArray(initial) ? initial : []);
    } catch (e) { arr = []; }
    if (!Array.isArray(arr) || arr.length === 0) arr = [{ url: '' }];
    return {
        items: arr.map(function (x) { return { url: typeof x === 'string' ? x : (x && x.url) || '' }; }),
        addItem: function () { this.items.push({ url: '' }); },
        removeItem: function (i) { this.items.splice(i, 1); if (this.items.length === 0) this.items = [{ url: '' }]; }
    };
}
</script>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
