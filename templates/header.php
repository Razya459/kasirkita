<?php
/**
 * Header Template - RestoKita POS Kasir
 */

use RestoKita\Auth;
use RestoKita\Helper;

// Muat autoloader Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Pastikan session sudah aktif
Helper::startSession();

// Periksa proteksi login kecuali jika halaman login
if (!isset($isLoginPage) || $isLoginPage !== true) {
    Auth::requireLogin();
}

// Deteksi base URL secara dinamis agar aman dijalankan di php -S maupun subfolder XAMPP
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$publicPos = strpos($scriptName, '/public');

if ($publicPos !== false) {
    // Jika dijalankan di subfolder XAMPP/Laragon (contoh: /Kasir 2/public/...)
    $baseUrl = substr($scriptName, 0, $publicPos + 7);
} else {
    // Jika dijalankan langsung dari folder public (contoh: php -S localhost:8000 -t public)
    $baseUrl = '';
}
$baseUrl = rtrim($baseUrl, '/');

// Variabel user aktif
$currentUser = Auth::user();
$pageTitle = $pageTitle ?? 'RestoKita - POS Kasir Restoran';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::e($pageTitle) ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Style RestoKita -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body>
<?php if (!isset($isLoginPage) || $isLoginPage !== true): ?>
<div class="app-wrapper">
    <!-- Sidebar -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <header class="app-header">
            <div class="d-flex align-items-center gap-3">
                <span class="fs-5 fw-bold text-dark mb-0"><?= Helper::e($pageTitle) ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-semibold text-dark"><?= Helper::e($currentUser['nama'] ?? 'User') ?></div>
                    <small class="badge <?= ($currentUser['role'] ?? '') === 'admin' ? 'bg-primary' : 'bg-success' ?>">
                        <?= strtoupper(Helper::e($currentUser['role'] ?? 'Kasir')) ?>
                    </small>
                </div>
                <div class="rounded-circle bg-resto-light text-resto p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-person-fill fs-5"></i>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="app-content">
            <?php
            // Tampilkan Flash Message jika ada
            $flash = Helper::getFlash();
            if ($flash):
            ?>
                <div class="alert alert-<?= Helper::e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
                        <div><?= Helper::e($flash['message']) ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
<?php endif; ?>
