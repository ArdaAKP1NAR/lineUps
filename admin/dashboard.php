<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/dbConnection.php';
require_once __DIR__ . '/includes/authHelper.php';

$currentUser = checkLogin();
requireRole(['admin', 'editor', 'viewer']);

$pageTitle = 'Dashboard';

// İstatistikler
$stmt = $pdo->query('SELECT COUNT(*) FROM videos WHERE is_deleted = 0');
$videoCount = (int) $stmt->fetchColumn();
$stmt = $pdo->query('SELECT COUNT(*) FROM videos WHERE is_deleted = 0 AND is_published = 1');
$publishedCount = (int) $stmt->fetchColumn();
$stmt = $pdo->query('SELECT COUNT(*) FROM categories WHERE is_active = 1');
$categoryCount = (int) $stmt->fetchColumn();

$successMsg = getFlash('success');
$errorMsg = getFlash('error');

require_once __DIR__ . '/includes/layout_header.php';
require_once __DIR__ . '/includes/layout_sidebar.php';
?>
<div class="min-h-screen pl-64 transition-[padding] duration-300">
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-700/50 bg-slate-900/80 px-6 backdrop-blur-md">
        <div class="flex items-center gap-4">
            <h1 class="text-lg font-semibold text-white">Dashboard</h1>
            <div class="hidden md:block">
                <input type="search" placeholder="Ara..." class="rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-2 text-sm text-white placeholder-slate-500 transition-all focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 w-64">
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button class="relative rounded-xl p-2 text-slate-400 transition-colors hover:bg-slate-800 hover:text-white">
                <i data-lucide="bell" class="h-5 w-5"></i>
                <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-indigo-500"></span>
            </button>
            <div class="flex items-center gap-3 rounded-xl border border-slate-700/50 bg-slate-800/50 px-3 py-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400">
                    <i data-lucide="user" class="h-5 w-5"></i>
                </div>
                <div class="hidden sm:block">
                    <p class="text-sm font-medium text-white"><?= htmlspecialchars($currentUser['username']) ?></p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($currentUser['role_slug'] ?? '') ?></p>
                </div>
            </div>
            <a href="logout.php" class="rounded-xl p-2 text-slate-400 transition-colors hover:bg-slate-800 hover:text-white" title="Çıkış">
                <i data-lucide="log-out" class="h-5 w-5"></i>
            </a>
        </div>
    </header>

    <main class="p-6">
        <?php if ($successMsg): ?>
            <div class="mb-4 flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                <i data-lucide="check-circle" class="h-4 w-4 shrink-0"></i>
                <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="mb-4 flex items-center gap-2 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                <i data-lucide="alert-circle" class="h-4 w-4 shrink-0"></i>
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-5 transition-all hover:border-slate-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-400">Toplam Video</p>
                        <p class="mt-1 text-2xl font-semibold text-white"><?= $videoCount ?></p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/20 text-indigo-400">
                        <i data-lucide="video" class="h-6 w-6"></i>
                    </div>
                </div>
                <a href="videoOperations.php" class="mt-3 inline-flex items-center text-sm text-indigo-400 transition-colors hover:text-indigo-300">
                    Videoları yönet <i data-lucide="arrow-right" class="ml-1 h-4 w-4"></i>
                </a>
            </div>
            <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-5 transition-all hover:border-slate-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-400">Yayında</p>
                        <p class="mt-1 text-2xl font-semibold text-white"><?= $publishedCount ?></p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400">
                        <i data-lucide="eye" class="h-6 w-6"></i>
                    </div>
                </div>
                <p class="mt-3 text-sm text-slate-500">Yayınlanmış video sayısı</p>
            </div>
            <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-5 transition-all hover:border-slate-600 sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-400">Kategoriler</p>
                        <p class="mt-1 text-2xl font-semibold text-white"><?= $categoryCount ?></p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500/20 text-violet-400">
                        <i data-lucide="folder-tree" class="h-6 w-6"></i>
                    </div>
                </div>
                <a href="categoryOperations.php" class="mt-3 inline-flex items-center text-sm text-indigo-400 transition-colors hover:text-indigo-300">
                    Kategorileri yönet <i data-lucide="arrow-right" class="ml-1 h-4 w-4"></i>
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-5">
            <h2 class="mb-4 text-base font-semibold text-white">Hoş geldiniz</h2>
            <p class="text-slate-400">LineUp admin paneline giriş yaptınız. Sol menüden video ve kategori yönetimine erişebilirsiniz.</p>
        </div>
    </main>
</div>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
