<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Makassar'); // WITA (Banjarmasin / Kab. Banjar)

if (session_status() === PHP_SESSION_NONE) {
    $sessDir = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessDir)) {
        mkdir($sessDir, 0775, true);
    }
    session_save_path($sessDir);
    session_name('SIMFLPP_SESS');
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/ExcelService.php';
require_once __DIR__ . '/IdebDocxService.php';
require_once __DIR__ . '/BsnSuratService.php';
require_once __DIR__ . '/Database.php';

$pdo = Database::getConnection();

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Sesi tidak valid atau telah kedaluwarsa (CSRF). Silakan muat ulang halaman.');
    }
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user']['id']);
}

function isAdmin(): bool {
    return (($_SESSION['user']['role'] ?? '') === 'admin');
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        setFlash('error', 'Akses ditolak. Akun Anda hanya memiliki hak akses Lihat Saja (Read-Only).');
        header('Location: index.php?page=dashboard');
        exit;
    }
}
