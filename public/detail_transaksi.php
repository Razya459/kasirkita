<?php
/**
 * Rincian Detail Transaksi - RestoKita
 */

use RestoKita\Auth;
use RestoKita\Helper;
use RestoKita\Transaction;

require_once __DIR__ . '/../vendor/autoload.php';

$pageTitle = 'Detail Transaksi - RestoKita';
$activePage = 'riwayat';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    Helper::setFlash('danger', 'ID Transaksi tidak valid.');
    Helper::redirect('riwayat.php');
}

try {
    $transactionModel = new Transaction();
    $transaksi = $transactionModel->getById($id);

    if (!$transaksi) {
        Helper::setFlash('danger', 'Data transaksi tidak ditemukan.');
        Helper::redirect('riwayat.php');
    }

    $details = $transactionModel->getDetails($id);
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">Error: ' . Helper::e($e->getMessage()) . '</div>';
    exit;
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <a href="riwayat.php" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
            </a>
            <h4 class="fw-bold mb-1 text-dark">Rincian Transaksi: <?= Helper::e($transaksi['kode_transaksi']) ?></h4>
            <p class="text-muted small mb-0">Diproses pada <?= Helper::formatTanggal($transaksi['tanggal']) ?> oleh Kasir: <strong><?= Helper::e($transaksi['nama_kasir']) ?></strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="cetak_struk.php?id=<?= $transaksi['id_transaksi'] ?>" target="_blank" class="btn btn-resto d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm">
                <i class="bi bi-printer"></i>
                <span>Cetak Struk</span>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tabel Item Pesanan -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 fw-bold text-dark">
                    <i class="bi bi-basket2 text-resto me-2"></i>Daftar Menu yang Dipesan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 text-center" style="width: 50px;">No</th>
                                    <th>Nama Menu</th>
                                    <th>Kategori</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end pe-3">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details as $idx => $d): ?>
                                    <tr>
                                        <td class="ps-3 text-center text-muted"><?= $idx + 1 ?></td>
                                        <td class="fw-bold text-dark"><?= Helper::e($d['nama_produk']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">
                                                <?= Helper::e($d['kategori']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><?= Helper::formatRupiah($d['harga_satuan']) ?></td>
                                        <td class="text-center fw-semibold"><?= (int) $d['qty'] ?></td>
                                        <td class="text-end pe-3 fw-bold text-dark"><?= Helper::formatRupiah($d['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ringkasan Perhitungan -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 fw-bold text-dark">
                    <i class="bi bi-calculator text-resto me-2"></i>Ringkasan Pembayaran
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Subtotal:</span>
                        <span class="fw-semibold"><?= Helper::formatRupiah($transaksi['total']) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Potongan Diskon:</span>
                        <span class="text-danger fw-semibold">- <?= Helper::formatRupiah($transaksi['diskon']) ?></span>
                    </div>

                    <?php
                    $dpp = (float)$transaksi['total'] - (float)$transaksi['diskon'];
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Dasar Pajak (DPP):</span>
                        <span class="fw-semibold"><?= Helper::formatRupiah($dpp) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted small">Pajak Restoran (PPN 10%):</span>
                        <span class="fw-semibold"><?= Helper::formatRupiah($transaksi['pajak']) ?></span>
                    </div>

                    <div class="border-top pt-3 d-flex justify-content-between align-items-center bg-resto-light p-3 rounded-3">
                        <span class="fw-bold text-dark">TOTAL BAYAR:</span>
                        <span class="fs-4 fw-bold text-resto"><?= Helper::formatRupiah($transaksi['total_bayar']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
