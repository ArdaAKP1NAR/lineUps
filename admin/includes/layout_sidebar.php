<?php
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isVideo = ($currentScript === 'videoOperations.php');
$isCategory = ($currentScript === 'categoryOperations.php');
$isDashboard = ($currentScript === 'dashboard.php' || $currentScript === 'index.php');
?>
<aside id="sidebar" class="fixed left-0 top-0 z-40 h-screen w-64 border-r border-slate-700/50 bg-slate-900/95 transition-all duration-300 ease-in-out" x-data="{ collapsed: false }">
    <div class="flex h-full flex-col">
        <div class="flex h-16 items-center justify-between border-b border-slate-700/50 px-4">
            <a href="dashboard.php" class="flex items-center gap-2 font-semibold text-white">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400">
                    <i data-lucide="layout-grid" class="h-5 w-5"></i>
                </div>
                <span x-show="!collapsed" x-collapse>LineUp</span>
            </a>
            <button @click="collapsed = !collapsed" class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-800 hover:text-white">
                <i data-lucide="panel-left-close" class="h-5 w-5" x-show="!collapsed"></i>
                <i data-lucide="panel-left" class="h-5 w-5" x-show="collapsed" x-cloak style="display: none;"></i>
            </button>
        </div>
        <nav class="flex-1 space-y-0.5 overflow-y-auto p-3">
            <a href="dashboard.php" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all <?= $isDashboard ? 'bg-indigo-500/15 text-indigo-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
                <i data-lucide="layout-dashboard" class="h-5 w-5 shrink-0"></i>
                <span x-show="!collapsed" x-collapse>Dashboard</span>
            </a>
            <a href="videoOperations.php" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all <?= $isVideo ? 'bg-indigo-500/15 text-indigo-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
                <i data-lucide="video" class="h-5 w-5 shrink-0"></i>
                <span x-show="!collapsed" x-collapse>Video Yönetimi</span>
            </a>
            <a href="categoryOperations.php" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all <?= $isCategory ? 'bg-indigo-500/15 text-indigo-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
                <i data-lucide="folder-tree" class="h-5 w-5 shrink-0"></i>
                <span x-show="!collapsed" x-collapse>Kategori Ayarları</span>
            </a>
        </nav>
        <div class="border-t border-slate-700/50 p-3" x-show="!collapsed">
            <div class="rounded-xl bg-slate-800/50 px-3 py-2 text-xs text-slate-500">LineUp Admin Panel</div>
        </div>
    </div>
</aside>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
