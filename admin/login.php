<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/dbConnection.php';
require_once __DIR__ . '/includes/authHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['admin_user']['id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = sanitize($_POST, 'username');
    $password = $_POST['password'] ?? '';
    if (!$usernameOrEmail || !$password) {
        $error = 'Kullanıcı adı veya e-posta ve şifre gerekli.';
    } else {
        $user = doLogin($pdo, $usernameOrEmail, $password);
        if ($user) {
            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            $redirect = str_starts_with($redirect, '/') ? 'dashboard.php' : $redirect;
            header('Location: ' . $redirect);
            exit;
        }
        $error = 'Kullanıcı adı veya şifre hatalı.';
    }
}

$pageTitle = 'Giriş';
require_once __DIR__ . '/includes/layout_header.php';
?>
<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="glass w-full max-w-md rounded-2xl border border-slate-700/60 p-8 shadow-2xl">
        <div class="mb-8 flex justify-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-indigo-500/20 text-indigo-400">
                <i data-lucide="layout-grid" class="h-8 w-8"></i>
            </div>
        </div>
        <h1 class="mb-2 text-center text-xl font-semibold text-white">LineUp Admin</h1>
        <p class="mb-6 text-center text-sm text-slate-400">Hesabınıza giriş yapın</p>

        <?php if ($error): ?>
            <div class="mb-4 flex items-center gap-2 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                <i data-lucide="alert-circle" class="h-4 w-4 shrink-0"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="space-y-5">
            <div>
                <label for="username" class="mb-1.5 block text-sm font-medium text-slate-300">Kullanıcı adı veya e-posta</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       class="w-full rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-3 text-white placeholder-slate-500 transition-all focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                       placeholder="admin veya admin@site.com" autocomplete="username" required autofocus>
            </div>
            <div x-data="{ showPass: false }">
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-300">Şifre</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'" id="password" name="password"
                           class="w-full rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-3 pr-12 text-white placeholder-slate-500 transition-all focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                           placeholder="••••••••" autocomplete="current-password" required>
                    <button type="button" @click="showPass = !showPass" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white" :aria-label="showPass ? 'Şifreyi gizle' : 'Şifreyi göster'">
                        <i data-lucide="eye" class="h-5 w-5" x-show="!showPass"></i>
                        <i data-lucide="eye-off" class="h-5 w-5" x-show="showPass" x-cloak style="display: none;"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="w-full rounded-xl bg-indigo-500 py-3 font-medium text-white transition-all hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                Giriş yap
            </button>
        </form>
    </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
