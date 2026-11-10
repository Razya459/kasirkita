<?php
/**
 * Sidebar Navigation Template - RestoKita POS Kasir
 */

use RestoKita\Auth;
use RestoKita\Helper;

$currentUri = $_SERVER['SCRIPT_NAME'] ?? '';
$activePage = $activePage ?? '';
?>
<aside class="app-sidebar">
    <!-- Brand Logo & Nama Restoran -->
    <div class="sidebar-brand">
        <i class="bi bi-fire"></i>
        <span>RestoKita</span>
    </div>

    <!-- Navigasi Menu Sidebar -->
    <nav class="sidebar-nav">
        <small class="text-uppercase text-secondary fw-semibold px-3 mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">Menu Utama</small>
        
        <a href="<?= $baseUrl ?>/dashboard.php" class="nav-link <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
            <i class="bi bi-grid-fill"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= $baseUrl ?>/kasir.php" class="nav-link <?= ($activePage === 'kasir') ? 'active' : '' ?>">
            <i class="bi bi-calculator"></i>
            <span>Kasir POS</span>
        </a>

        <a href="<?= $baseUrl ?>/produk/index.php" class="nav-link <?= ($activePage === 'produk') ? 'active' : '' ?>">
            <i class="bi bi-basket2-fill"></i>
            <span>Daftar Menu</span>
        </a>

        <a href="<?= $baseUrl ?>/riwayat.php" class="nav-link <?= ($activePage === 'riwayat') ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Riwayat Transaksi</span>
        </a>

        <div class="mt-4">
            <small class="text-uppercase text-secondary fw-semibold px-3 mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">Akun & Sesi</small>
        </div>

        <a href="<?= $baseUrl ?>/logout.php" class="nav-link text-danger-emphasis" onclick="return confirm('Apakah Anda yakin ingin logout dari sistem?')">
            <i class="bi bi-box-arrow-right"></i>
            <span>Keluar (Logout)</span>
        </a>
    </nav>

    <!-- Sidebar Footer / Info Pengguna Aktif -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-resto text-white p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: var(--resto-primary);">
                <i class="bi bi-person"></i>
            </div>
            <div class="overflow-hidden">
                <div class="text-white small fw-bold text-truncate"><?= Helper::e($currentUser['nama'] ?? 'Staff') ?></div>
                <div class="text-secondary" style="font-size: 0.75rem;">Role: <?= Helper::e($currentUser['role'] ?? '-') ?></div>
            </div>
        </div>
    </div>
</aside>
