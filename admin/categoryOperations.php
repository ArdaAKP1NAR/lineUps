<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/dbConnection.php';
require_once __DIR__ . '/includes/authHelper.php';

$currentUser = checkLogin();
requireRole(['admin', 'editor']);

$successMsg = getFlash('success');
$errorMsg = getFlash('error');

// Kaydet / Güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST, 'action');
    $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : null;
    $categoryName = sanitize($_POST, 'name');
    $categorySlug = sanitize($_POST, 'slug');
    $categoryType = sanitize($_POST, 'type');
    $parentId = sanitize($_POST, 'parent_id', 'int');
    $orderIndex = sanitize($_POST, 'order_index', 'int');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!in_array($categoryType, ['navbar', 'game_content', 'footer'], true)) {
        $categoryType = 'navbar';
    }
    if (!$categoryName) {
        setFlash('error', 'Kategori adı zorunludur.');
    } else {
        if (!$categorySlug) {
            $categorySlug = slugify($categoryName);
        }
        try {
            if ($action === 'edit' && $categoryId) {
                $stmt = $pdo->prepare('UPDATE categories SET parent_id = ?, name = ?, type = ?, order_index = ?, slug = ?, is_active = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$parentId ?: null, $categoryName, $categoryType, $orderIndex ?? 0, $categorySlug, $isActive, $categoryId]);
                setFlash('success', 'Kategori güncellendi.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (parent_id, name, type, order_index, slug, is_active) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$parentId ?: null, $categoryName, $categoryType, $orderIndex ?? 0, $categorySlug, $isActive]);
                setFlash('success', 'Kategori eklendi.');
            }
        } catch (Exception $e) {
            setFlash('error', 'Kayıt hatası: ' . $e->getMessage());
        }
    }
    header('Location: categoryOperations.php');
    exit;
}

$categoriesRaw = $pdo->query(
    'SELECT c.id, c.parent_id, c.name, c.slug, c.type, c.order_index, c.icon, c.is_active, c.created_at,
            p.name AS parent_name
     FROM categories c
     LEFT JOIN categories p ON p.id = c.parent_id
     ORDER BY c.type, c.order_index, c.name'
)->fetchAll(PDO::FETCH_ASSOC);

// Hiyerarşik sıra: önce üst seviye, sonra alt kategoriler (girintili)
$byParent = [];
foreach ($categoriesRaw as $c) {
    $pid = $c['parent_id'] !== null ? (int) $c['parent_id'] : 'root';
    if (!isset($byParent[$pid])) {
        $byParent[$pid] = [];
    }
    $byParent[$pid][] = $c;
}
$categories = [];
$addLevel = function ($parentKey, $depth) use (&$addLevel, &$byParent, &$categories) {
    foreach ($byParent[$parentKey] ?? [] as $cat) {
        $cat['_depth'] = $depth;
        $cat['_is_top'] = ($depth === 0);
        $categories[] = $cat;
        $addLevel((int) $cat['id'], $depth + 1);
    }
};
$addLevel('root', 0);

$parentOptions = $pdo->query('SELECT id, name, type FROM categories WHERE is_active = 1 ORDER BY type, order_index, name')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Kategori Ayarları';
require_once __DIR__ . '/includes/layout_header.php';
require_once __DIR__ . '/includes/layout_sidebar.php';
?>
<div class="min-h-screen pl-64 transition-[padding] duration-300">
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-700/50 bg-slate-900/80 px-6 backdrop-blur-md">
        <h1 class="text-lg font-semibold text-white">Kategori Ayarları</h1>
        <div class="flex items-center gap-3">
            <a href="categoryOperations.php?add=1" class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2 text-sm font-medium text-white transition-all hover:bg-indigo-600">
                <i data-lucide="plus" class="h-4 w-5"></i>
                Yeni Kategori
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

        <div class="mb-4 rounded-lg border border-slate-600/50 bg-slate-800/30 px-4 py-3 text-sm text-slate-400">
            <span class="font-medium text-slate-300">Hiyerarşi:</span> Girintili satırlar alt kategoriyi gösterir. <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs bg-slate-600/50 text-slate-300">Üst seviye</span> = menüde en üstte, altındakiler onun altında listelenir.
        </div>
        <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-700 bg-slate-800/80 text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium">Seviye</th>
                            <th class="px-4 py-3 font-medium">Tip</th>
                            <th class="px-4 py-3 font-medium">Üst / Alt</th>
                            <th class="px-4 py-3 font-medium">Sıra</th>
                            <th class="px-4 py-3 font-medium">Durum</th>
                            <th class="px-4 py-3 font-medium text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat):
                            $depth = (int) ($cat['_depth'] ?? 0);
                            $isTop = !empty($cat['_is_top']);
                            $padClass = $depth === 0 ? 'pl-4' : ($depth === 1 ? 'pl-8' : 'pl-12');
                        ?>
                            <tr class="border-b border-slate-700/50 transition-colors hover:bg-slate-700/30 <?= $depth > 0 ? 'bg-slate-800/40' : '' ?>">
                                <td class="py-3 <?= $padClass ?>">
                                    <?php if ($depth > 0): ?>
                                        <span class="mr-2 text-slate-500" aria-hidden="true">↳</span>
                                    <?php endif; ?>
                                    <div class="font-medium text-white"><?= htmlspecialchars($cat['name']) ?></div>
                                    <div class="text-xs text-slate-500">/<?= htmlspecialchars($cat['slug']) ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($isTop): ?>
                                        <span class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-xs font-medium bg-amber-500/20 text-amber-400" title="En üstte, başka kategorinin altında değil">
                                            <i data-lucide="corner-up-left" class="h-3.5 w-3.5"></i>
                                            Üst seviye
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-xs font-medium bg-slate-500/30 text-slate-400" title="Alt kategori">
                                            <i data-lucide="corner-down-right" class="h-3.5 w-3.5"></i>
                                            Alt (<?= $depth ?>. seviye)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-lg px-2 py-0.5 text-xs font-medium
                                        <?= $cat['type'] === 'navbar' ? 'bg-indigo-500/20 text-indigo-400' : ($cat['type'] === 'footer' ? 'bg-slate-500/20 text-slate-400' : 'bg-violet-500/20 text-violet-400') ?>">
                                        <?= $cat['type'] === 'navbar' ? 'Navbar' : ($cat['type'] === 'footer' ? 'Footer' : 'İçerik') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-400">
                                    <?php if ($isTop): ?>
                                        <span class="text-slate-500">— Başka kategorinin altında değil</span>
                                    <?php else: ?>
                                        <span class="text-indigo-300/90" title="Bu kategori şunun altında: <?= htmlspecialchars($cat['parent_name'] ?? '') ?>"><?= htmlspecialchars($cat['parent_name'] ?? '—') ?> altında</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-400"><?= (int) $cat['order_index'] ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium <?= $cat['is_active'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                                        <?= $cat['is_active'] ? 'Aktif' : 'Pasif' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="categoryOperations.php?edit=<?= (int) $cat['id'] ?>" class="inline-flex rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white" title="Düzenle">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (empty($categories)): ?>
                <div class="px-4 py-12 text-center text-slate-500">Henüz kategori yok. "Yeni Kategori" ile ekleyebilirsiniz.</div>
            <?php endif; ?>
        </div>

        <?php
        $editCategory = null;
        if (isset($_GET['edit'])) {
            $editId = (int) $_GET['edit'];
            foreach ($categories as $c) {
                if ((int) $c['id'] === $editId) {
                    $editCategory = $c;
                    break;
                }
            }
        }
        ?>
        <?php if ($editCategory): ?>
            <div class="mt-6 rounded-xl border border-slate-700/50 bg-slate-800/50 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Kategori düzenle</h2>
                <form method="post" class="max-w-xl space-y-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" value="<?= (int) $editCategory['id'] ?>">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Ad</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($editCategory['name']) ?>"
                               class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Slug</label>
                        <input type="text" name="slug" value="<?= htmlspecialchars($editCategory['slug']) ?>"
                               class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Tip</label>
                        <select name="type" class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                            <option value="navbar" <?= $editCategory['type'] === 'navbar' ? 'selected' : '' ?>>Navbar</option>
                            <option value="game_content" <?= $editCategory['type'] === 'game_content' ? 'selected' : '' ?>>İçerik</option>
                            <option value="footer" <?= $editCategory['type'] === 'footer' ? 'selected' : '' ?>>Footer</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Üst kategori</label>
                        <select name="parent_id" class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                            <option value="">— Yok —</option>
                            <?php foreach ($parentOptions as $p): ?>
                                <?php if ((int) $p['id'] !== (int) $editCategory['id']): ?>
                                    <option value="<?= (int) $p['id'] ?>" <?= (int) $editCategory['parent_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?> (<?= $p['type'] ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Sıra</label>
                        <input type="number" name="order_index" value="<?= (int) $editCategory['order_index'] ?>"
                               class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" min="0">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="cat_active" value="1" <?= $editCategory['is_active'] ? 'checked' : '' ?>
                               class="rounded border-slate-500 text-indigo-500 focus:ring-indigo-500">
                        <label for="cat_active" class="text-sm text-slate-300">Aktif</label>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="rounded-xl bg-indigo-500 px-5 py-2.5 font-medium text-white transition-all hover:bg-indigo-600">Güncelle</button>
                        <a href="categoryOperations.php" class="rounded-xl border border-slate-600 px-5 py-2.5 text-slate-300 hover:bg-slate-800">İptal</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['add']) && !$editCategory): ?>
            <div class="mt-6 rounded-xl border border-slate-700/50 bg-slate-800/50 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Yeni kategori</h2>
                <form method="post" class="max-w-xl space-y-4">
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Ad</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                               class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Slug</label>
                        <input type="text" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>"
                               class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" placeholder="otomatik üretilir">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Tip</label>
                        <select name="type" class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                            <option value="navbar">Navbar</option>
                            <option value="game_content">İçerik</option>
                            <option value="footer">Footer</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Üst kategori</label>
                        <select name="parent_id" class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                            <option value="">— Yok —</option>
                            <?php foreach ($parentOptions as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['type'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Sıra</label>
                        <input type="number" name="order_index" value="0"
                               class="w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-3 text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" min="0">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="cat_add_active" value="1" checked
                               class="rounded border-slate-500 text-indigo-500 focus:ring-indigo-500">
                        <label for="cat_add_active" class="text-sm text-slate-300">Aktif</label>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="rounded-xl bg-indigo-500 px-5 py-2.5 font-medium text-white transition-all hover:bg-indigo-600">Ekle</button>
                        <a href="categoryOperations.php" class="rounded-xl border border-slate-600 px-5 py-2.5 text-slate-300 hover:bg-slate-800">İptal</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
