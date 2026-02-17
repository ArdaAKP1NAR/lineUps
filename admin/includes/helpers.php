<?php
/**
 * Form sanitize ve yardımcı fonksiyonlar (camelCase uyumlu)
 */

/**
 * POST/GET verilerini temizler ve döndürür.
 * @param array $source $_POST veya $_GET
 * @param string $key
 * @param string $type 'string'|'int'|'email'|'array'
 * @return string|int|array|null
 */
function sanitize($source, $key, $type = 'string')
{
    $raw = $source[$key] ?? null;
    if ($raw === null || $raw === '') {
        return $type === 'array' ? [] : null;
    }
    switch ($type) {
        case 'int':
            return (int) $raw;
        case 'email':
            return filter_var(trim($raw), FILTER_SANITIZE_EMAIL) ?: null;
        case 'array':
            return is_array($raw) ? array_map(function ($v) {
                return is_string($v) ? trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8')) : $v;
            }, $raw) : [];
        default:
            return trim(htmlspecialchars((string) $raw, ENT_QUOTES, 'UTF-8'));
    }
}

/**
 * URL-friendly slug üretir (Türkçe karakterler dahil)
 */
function slugify($text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $map = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\-]+/', '-', $text);
    return trim(preg_replace('/-+/', '-', $text), '-');
}

/**
 * Flash mesaj ayarla
 */
function setFlash($key, $message): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = $message;
}

/**
 * Flash mesaj oku ve sil
 */
function getFlash($key): ?string
{
    $msg = $_SESSION['flash'][$key] ?? null;
    if (isset($_SESSION['flash'][$key])) {
        unset($_SESSION['flash'][$key]);
    }
    return $msg;
}
