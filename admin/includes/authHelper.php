<?php
/**
 * Oturum ve yetki kontrolleri (checkLogin, checkRole)
 */
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Giriş yapmış kullanıcıyı döndürür; yoksa login sayfasına yönlendirir.
 * @return array{id: int, username: string, email: string, role_id: int, role_slug: string}
 */
function checkLogin(): array
{
    $user = $_SESSION['admin_user'] ?? null;
    if (!$user || empty($user['id'])) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
    return $user;
}

/**
 * Kullanıcının rollerinden biri izinli mi kontrol eder.
 * @param array $allowedRoles Örn: ['admin', 'editor']
 * @return bool
 */
function checkRole(array $allowedRoles): bool
{
    $user = $_SESSION['admin_user'] ?? null;
    if (!$user) {
        return false;
    }
    $roleSlug = $user['role_slug'] ?? '';
    return in_array($roleSlug, $allowedRoles, true);
}

/**
 * Sadece belirtilen roller erişebilir; değilse dashboard'a yönlendirir.
 */
function requireRole(array $allowedRoles): void
{
    if (!checkRole($allowedRoles)) {
        setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Giriş işlemi (login.php'den çağrılır)
 */
function doLogin(PDO $pdo, string $usernameOrEmail, string $password): ?array
{
    $stmt = $pdo->prepare(
        'SELECT u.id, u.username, u.email, u.password_hash, u.role_id, r.slug AS role_slug
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.is_active = 1 AND (u.username = ? OR u.email = ?)'
    );
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }
    unset($user['password_hash']);
    $_SESSION['admin_user'] = $user;
    $pdo->prepare('UPDATE users SET last_login_at = NOW(), failed_attempts = 0 WHERE id = ?')->execute([$user['id']]);
    return $user;
}

/**
 * Çıkış
 */
function doLogout(): void
{
    $_SESSION['admin_user'] = null;
    session_destroy();
    header('Location: login.php');
    exit;
}
