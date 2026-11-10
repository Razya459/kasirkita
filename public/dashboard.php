<?php
/**
 * Dashboard Utama - RestoKita POS Kasir
 */

use RestoKita\Auth;
use RestoKita\Helper;
use RestoKita\Product;
use RestoKita\Transaction;

require_once __DIR__ . '/../vendor/autoload.php';

$pageTitle = 'Dashboard - RestoKita';
$activePage = 'dashboard';

require_once __DIR__ . '/../templates/header.php';

try {
    $productModel = new Product();
    $transactionModel = new Transaction();

    $totalProduk = $productModel->count();
    $todaySummary = $transactionModel->getTodaySummary();
    $recentTransactions = $transactionModel->getRecent(5);
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">Koneksi Database Bermasalah: ' . Helper::e($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}
?>

<div class="container-fluid p-0">
    <!-- Welcome Greeting Banner -->
    <div class="card bg-resto text-white border-0 shadow-sm mb-4 rounded-4" style="background: linear-gradient(135deg, #ff6b35 0%, #f97316 100%);">
        <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h4 class="fw-bold mb-1">Selamat Datang di RestoKita, <?= Helper::e($currentUser['nama'] ?? 'Staff') ?>!</h4>
                <p class="mb-0 opacity-75 small">Sistem Point of Sales &bull; Kasir Restoran Modern, Cepat, dan Akurat.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= $baseUrl ?>/kasir.php" class="btn btn-light text-resto fw-bold px-3 py-2 rounded-3 shadow-sm">
                    <i class="bi bi-cart-plus me-1"></i> Buka Kasir POS
                </a>
            </div>
        </div>
    </div>

    <!-- Ringkasan Statistik Utama (3 Kartu Sesuai Persyaratan Fitur) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Menu / Produk -->
        <div class="col-12 col-md-4">
            <div class="card stat-card shadow-sm h-100 border-0 border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Menu / Produk</div>
                        <h2 class="fw-bold text-dark mt-2 mb-0"><?= number_format($totalProduk) ?></h2>
                        <small class="text-muted">Item menu aktif di sistem</small>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-cup-hot-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Transaksi Hari Ini -->
        <div class="col-12 col-md-4">
            <div class="card stat-card shadow-sm h-100 border-0 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Transaksi Hari Ini</div>
                        <h2 class="fw-bold text-dark mt-2 mb-0"><?= number_format($todaySummary['total_transaksi']) ?></h2>
                        <small class="text-muted">Nota transaksi berhasil</small>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Pendapatan Hari Ini -->
        <div class="col-12 col-md-4">
            <div class="card stat-card shadow-sm h-100 border-0 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Pendapatan Hari Ini</div>
                        <h2 class="fw-bold text-success mt-2 mb-0"><?= Helper::formatRupiah($todaySummary['total_pendapatan']) ?></h2>
                        <small class="text-muted">Total penerimaan kasir</small>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Kiri: Transaksi Terbaru -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="fw-bold text-dark">
                        <i class="bi bi-clock-history text-resto me-2"></i>Transaksi Terbaru
                    </div>
                    <a href="<?= $baseUrl ?>/riwayat.php" class="btn btn-sm btn-outline-secondary">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Kode Transaksi</th>
                                    <th>Waktu</th>
                                    <th>Kasir</th>
                                    <th class="text-end">Total Bayar</th>
                                    <th class="text-center pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentTransactions)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            Belum ada transaksi hari ini. Silakan mulai transaksi di menu Kasir POS.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentTransactions as $trx): ?>
                                        <tr>
                                            <td class="ps-3 fw-semibold text-dark">
                                                <?= Helper::e($trx['kode_transaksi']) ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?= Helper::formatTanggal($trx['tanggal']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= Helper::e($trx['nama_kasir']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                <?= Helper::formatRupiah($trx['total_bayar']) ?>
                                            </td>
                                            <td class="text-center pe-3">
                                                <a href="<?= $baseUrl ?>/detail_transaksi.php?id=<?= $trx['id_transaksi'] ?>" class="btn btn-sm btn-light text-primary border" title="Lihat Rincian">
                                                    <i class="bi bi-eye"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Aturan Diskon Bertingkat & Info UKK -->
        <div class="col-12 col-lg-4">
            <!-- Card Aturan Diskon Bertingkat -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white py-3 fw-bold text-dark">
                    <i class="bi bi-percent text-resto me-2"></i>Aturan Diskon Bertingkat
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Diskon otomatis dihitung berdasarkan total belanja (Subtotal) sebelum pajak:
                    </p>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Subtotal &lt; Rp 50.000</span>
                            <span class="badge bg-secondary">Diskon 0%</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Rp 50.000 s.d. Rp 99.999</span>
                            <span class="badge bg-info text-dark">Diskon 10%</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Rp 100.000 s.d. Rp 199.999</span>
                            <span class="badge bg-warning text-dark">Diskon 15%</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Subtotal &ge; Rp 200.000</span>
                            <span class="badge bg-danger">Diskon 20%</span>
                        </li>
                    </ul>
                    <div class="mt-3 p-2 bg-light rounded small border">
                        <div class="fw-semibold text-secondary mb-1">Rumus Perhitungan:</div>
                        <code>Dasar Pajak = Subtotal - Diskon</code><br>
                        <code>PPN (10%) = Dasar Pajak &times; 10%</code><br>
                        <code>Total = Dasar Pajak + PPN</code>
                    </div>
                </div>
            </div>

            <!-- Card Navigasi Cepat -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 fw-bold text-dark">
                    <i class="bi bi-lightning-charge text-warning me-2"></i>Aksi Cepat
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="<?= $baseUrl ?>/kasir.php" class="btn btn-resto text-start d-flex align-items-center justify-content-between py-2">
                        <span><i class="bi bi-cart-check me-2"></i> Transaksi Kasir Baru</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <?php if (Auth::isAdmin()): ?>
                    <a href="<?= $baseUrl ?>/produk/tambah.php" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between py-2">
                        <span><i class="bi bi-plus-circle me-2"></i> Tambah Menu Baru</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <?php endif; ?>
                    <a href="<?= $baseUrl ?>/riwayat.php" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between py-2">
                        <span><i class="bi bi-file-earmark-spreadsheet me-2"></i> Laporan Riwayat Transaksi</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
