<?php
/**
 * Riwayat Transaksi Penjualan - RestoKita
 */

use RestoKita\Auth;
use RestoKita\Helper;
use RestoKita\Transaction;

require_once __DIR__ . '/../vendor/autoload.php';

$pageTitle = 'Riwayat Transaksi - RestoKita';
$activePage = 'riwayat';

require_once __DIR__ . '/../templates/header.php';

$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date'] ?? '');
$search    = trim($_GET['search'] ?? '');

try {
    $transactionModel = new Transaction();
    $transactions = $transactionModel->getAll($startDate, $endDate, $search);

    // Hitung akumulasi omzet dari daftar transaksi yang difilter
    $totalOmzet = array_sum(array_column($transactions, 'total_bayar'));
    $totalDiskonDiberikan = array_sum(array_column($transactions, 'diskon'));
    $totalPajakTerkumpul = array_sum(array_column($transactions, 'pajak'));
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">Error: ' . Helper::e($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}
?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Riwayat Transaksi Penjualan</h4>
            <p class="text-muted small mb-0">Daftar rekam jejak pesanan, diskon, pajak, dan nota penjualan restoran.</p>
        </div>
        <div>
            <a href="<?= $baseUrl ?>/kasir.php" class="btn btn-resto d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm">
                <i class="bi bi-cart-plus"></i>
                <span>Transaksi Kasir Baru</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar: Tanggal & Pencarian -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="riwayat.php" method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Dari Tanggal:</label>
                    <input type="date" name="start_date" class="form-control form-control-sm bg-light" value="<?= Helper::e($startDate) ?>">
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Sampai Tanggal:</label>
                    <input type="date" name="end_date" class="form-control form-control-sm bg-light" value="<?= Helper::e($endDate) ?>">
                </div>

                <div class="col-12 col-sm-8 col-md-4">
                    <label class="form-label small fw-semibold text-secondary mb-1">Cari Kode / Kasir:</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control bg-light" placeholder="TRX-... atau nama kasir" value="<?= Helper::e($search) ?>">
                    </div>
                </div>

                <div class="col-12 col-sm-4 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <?php if ($startDate !== '' || $endDate !== '' || $search !== ''): ?>
                        <a href="riwayat.php" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Ringkasan Filter Card -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Jumlah Transaksi</small>
                <div class="fs-4 fw-bold text-dark mt-1"><?= count($transactions) ?> Nota</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Diskon Diberikan</small>
                <div class="fs-4 fw-bold text-danger mt-1"><?= Helper::formatRupiah($totalDiskonDiberikan) ?></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Penerimaan (Omzet)</small>
                <div class="fs-4 fw-bold text-success mt-1"><?= Helper::formatRupiah($totalOmzet) ?></div>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat Transaksi -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">No</th>
                            <th>Kode Transaksi</th>
                            <th>Tanggal & Waktu</th>
                            <th>Kasir</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end">PPN (10%)</th>
                            <th class="text-end">Total Bayar</th>
                            <th class="text-center pe-3" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Tidak ada data transaksi yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $idx => $t): ?>
                                <tr>
                                    <td class="ps-3 text-center text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= Helper::e($t['kode_transaksi']) ?></div>
                                    </td>
                                    <td class="small text-muted">
                                        <?= Helper::formatTanggal($t['tanggal']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= Helper::e($t['nama_kasir']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end small">
                                        <?= Helper::formatRupiah($t['total']) ?>
                                    </td>
                                    <td class="text-end small text-danger">
                                        <?= (float)$t['diskon'] > 0 ? '- ' . Helper::formatRupiah($t['diskon']) : 'Rp 0' ?>
                                    </td>
                                    <td class="text-end small text-muted">
                                        <?= Helper::formatRupiah($t['pajak']) ?>
                                    </td>
                                    <td class="text-end fw-bold text-resto">
                                        <?= Helper::formatRupiah($t['total_bayar']) ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= $baseUrl ?>/detail_transaksi.php?id=<?= $t['id_transaksi'] ?>" class="btn btn-outline-primary" title="Lihat Rincian Item">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                            <a href="<?= $baseUrl ?>/cetak_struk.php?id=<?= $t['id_transaksi'] ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Struk">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </div>
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

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
