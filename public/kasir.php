<?php
/**
 * Kasir POS - Transaksi Penjualan RestoKita
 */

use RestoKita\Auth;
use RestoKita\Helper;
use RestoKita\Product;
use RestoKita\Transaction;
use RestoKita\Validator;

require_once __DIR__ . '/../vendor/autoload.php';

$pageTitle = 'Kasir POS - RestoKita';
$activePage = 'kasir';

$lastTransaction = null;
$errorMessage = null;

// Proses simpan transaksi saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartPayload = $_POST['cart_payload'] ?? '';
    $rawItems = json_decode($cartPayload, true);

    $validator = new Validator();
    $validationErrors = $validator->validateCart(is_array($rawItems) ? $rawItems : []);

    if (!empty($validationErrors)) {
        $errorMessage = reset($validationErrors);
    } else {
        try {
            $transactionModel = new Transaction();
            $idPengguna = Auth::id() ?? 1;

            $lastTransaction = $transactionModel->process($idPengguna, $rawItems);
            Helper::setFlash('success', "Transaksi {$lastTransaction['kode_transaksi']} berhasil diproses!");
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }
    }
}

// Ambil daftar seluruh produk untuk ditampilkan di katalog menu kasir
try {
    $productModel = new Product();
    $products = $productModel->getAll();
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">Koneksi Database Bermasalah: ' . Helper::e($e->getMessage()) . '</div>';
    exit;
}

require_once __DIR__ . '/../templates/header.php';
$extraScripts = [$baseUrl . '/assets/js/kasir.js'];
?>

<div class="container-fluid p-0">
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-x-circle-fill fs-5"></i>
                <div><strong>Transaksi Gagal:</strong> <?= Helper::e($errorMessage) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ========================================== -->
        <!-- KOLOM KIRI: KATALOG MENU (CARD GRID & FILTER) -->
        <!-- ========================================== -->
        <div class="col-12 col-xl-7 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-3">
                    <!-- Baris Pencarian & Filter Kategori -->
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
                        <!-- Pencarian Realtime -->
                        <div class="input-group" style="max-width: 320px;">
                            <span class="input-group-text bg-light text-muted border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" id="menu-search" class="form-control bg-light border-start-0" placeholder="Cari nama menu...">
                        </div>

                        <!-- Tombol Pill Kategori -->
                        <div class="d-flex gap-1 overflow-auto pb-1">
                            <button type="button" class="btn btn-sm btn-cat-pill active btn-resto px-3 py-1 rounded-pill" data-category="all">
                                Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-cat-pill btn-outline-secondary px-3 py-1 rounded-pill" data-category="Makanan">
                                Makanan
                            </button>
                            <button type="button" class="btn btn-sm btn-cat-pill btn-outline-secondary px-3 py-1 rounded-pill" data-category="Minuman">
                                Minuman
                            </button>
                            <button type="button" class="btn btn-sm btn-cat-pill btn-outline-secondary px-3 py-1 rounded-pill" data-category="Snack">
                                Snack
                            </button>
                        </div>
                    </div>

                    <!-- Grid Daftar Menu -->
                    <div class="row g-3" id="menu-grid">
                        <?php foreach ($products as $p): ?>
                            <?php
                            $isOutOfStock = ((int) $p['stok'] <= 0);
                            $iconClass = match ($p['kategori']) {
                                'Makanan' => 'bi-fire',
                                'Minuman' => 'bi-cup-straw',
                                'Snack'   => 'bi-cookie',
                                default   => 'bi-box'
                            };
                            ?>
                            <div class="col-6 col-md-4 menu-grid-item" 
                                 data-category="<?= Helper::e($p['kategori']) ?>" 
                                 data-nama="<?= Helper::e($p['nama_produk']) ?>">
                                <div class="card menu-card border h-100 <?= $isOutOfStock ? 'opacity-50' : '' ?>">
                                    <div class="menu-icon-wrapper">
                                        <i class="bi <?= $iconClass ?>"></i>
                                    </div>
                                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">
                                                    <?= Helper::e($p['kategori']) ?>
                                                </span>
                                                <small class="<?= $isOutOfStock ? 'text-danger fw-bold' : 'text-muted' ?>" style="font-size: 0.75rem;">
                                                    <?= $isOutOfStock ? 'Habis' : 'Stok: ' . (int) $p['stok'] ?>
                                                </small>
                                            </div>
                                            <div class="fw-bold text-dark text-truncate small mb-1" title="<?= Helper::e($p['nama_produk']) ?>">
                                                <?= Helper::e($p['nama_produk']) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-resto small">
                                                <?= Helper::formatRupiah($p['harga']) ?>
                                            </span>
                                            <button type="button" 
                                                    class="btn btn-sm btn-resto btn-add-to-cart px-2 py-1" 
                                                    data-id="<?= (int) $p['id_produk'] ?>"
                                                    data-nama="<?= Helper::e($p['nama_produk']) ?>"
                                                    data-harga="<?= (float) $p['harga'] ?>"
                                                    data-stok="<?= (int) $p['stok'] ?>"
                                                    <?= $isOutOfStock ? 'disabled' : '' ?>>
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Notice Jika Pencarian Tidak Ditemukan -->
                    <div id="no-menu-results" class="text-center py-5 text-muted d-none">
                        <i class="bi bi-search fs-2 d-block mb-2"></i>
                        Tidak ada menu yang sesuai dengan kata kunci atau kategori yang dipilih.
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- KOLOM KANAN: PANEL PESANAN KASIR (CART)   -->
        <!-- ========================================== -->
        <div class="col-12 col-xl-5 col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 cart-panel">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-cart3 text-resto fs-5"></i>
                        <span>Pesanan Saat Ini</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-cart" title="Kosongkan Pesanan">
                        <i class="bi bi-trash"></i> Reset
                    </button>
                </div>

                <div class="card-body p-3">
                    <!-- Notice Keranjang Masih Kosong -->
                    <div id="empty-cart-notice" class="text-center py-5 text-muted">
                        <i class="bi bi-bag-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <p class="mb-0 small">Belum ada menu yang dipilih.</p>
                        <small class="text-secondary">Klik tombol <strong>+</strong> pada menu di samping untuk menambahkan ke pesanan.</small>
                    </div>

                    <!-- Kontainer Daftar Item Pesanan -->
                    <div id="cart-items" class="cart-items-wrapper mb-3"></div>

                    <!-- Kontainer Rincian Biaya & Form Checkout -->
                    <div id="cart-summary" class="d-none border-top pt-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Subtotal:</span>
                            <span id="summary-subtotal" class="fw-semibold text-dark">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Diskon Bertingkat (<span id="summary-discount-rate">0%</span>):</span>
                            <span id="summary-discount" class="text-success fw-semibold">- Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Dasar Pengenaan Pajak (DPP):</span>
                            <span id="summary-tax-base" class="fw-semibold text-dark">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Pajak Restoran (PPN 10%):</span>
                            <span id="summary-tax" class="fw-semibold text-dark">Rp 0</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center p-3 bg-resto-light rounded-3 mb-3 border border-warning-subtle">
                            <span class="fw-bold text-dark">TOTAL AKHIR:</span>
                            <span id="summary-total" class="fs-4 fw-bold text-resto">Rp 0</span>
                        </div>

                        <!-- Input Uang Diterima & Kembalian -->
                        <div class="mb-3">
                            <label for="input-cash" class="form-label small fw-semibold text-secondary mb-1">
                                Uang Diterima Pelanggan:
                            </label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" id="input-cash" class="form-control" placeholder="Masukkan jumlah nominal uang..." min="0" step="500">
                            </div>
                            <!-- Tombol Cepat Pecahan Uang Rupiah -->
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-cash" data-val="pas">Uang Pas</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-cash" data-val="50000">50.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-cash" data-val="100000">100.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-cash" data-val="200000">200.000</button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded small" id="change-wrapper">
                            <span class="fw-semibold">Kembalian:</span>
                            <span id="summary-change" class="fw-bold fs-6">Rp 0</span>
                        </div>

                        <!-- Form POST untuk Menyimpan Transaksi -->
                        <form action="kasir.php" method="POST" id="form-checkout">
                            <input type="hidden" name="cart_payload" id="cart-input-payload" value="">
                            <button type="submit" class="btn btn-resto w-100 py-2 rounded-3 fw-bold shadow-sm" id="btn-submit-order" disabled>
                                <i class="bi bi-check-circle me-1"></i> Proses Transaksi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP SUKSES TRANSAKSI & STRUK KASIR -->
<!-- ========================================== -->
<?php if ($lastTransaction): ?>
<div class="modal fade" id="modalSuccessTrx" tabindex="-1" aria-labelledby="modalSuccessLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold" id="modalSuccessLabel">
                    <i class="bi bi-check-circle-fill me-2"></i>Transaksi Berhasil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="display-6 fw-bold text-success mb-1">
                        <?= Helper::formatRupiah($lastTransaction['total_bayar']) ?>
                    </div>
                    <span class="badge bg-light text-dark border">
                        <?= Helper::e($lastTransaction['kode_transaksi']) ?>
                    </span>
                </div>

                <div class="border rounded-3 p-3 bg-light small mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Subtotal:</span>
                        <span><?= Helper::formatRupiah($lastTransaction['subtotal']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Diskon:</span>
                        <span class="text-success">- <?= Helper::formatRupiah($lastTransaction['diskon']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Pajak (PPN 10%):</span>
                        <span><?= Helper::formatRupiah($lastTransaction['pajak']) ?></span>
                    </div>
                    <div class="border-top pt-1 mt-1 d-flex justify-content-between fw-bold">
                        <span>Total Bayar:</span>
                        <span class="text-resto"><?= Helper::formatRupiah($lastTransaction['total_bayar']) ?></span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="cetak_struk.php?id=<?= $lastTransaction['id_transaksi'] ?>" target="_blank" class="btn btn-resto fw-semibold py-2">
                        <i class="bi bi-printer me-1"></i> Cetak Struk Pembayaran
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Selesai / Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Buka modal secara otomatis jika transaksi baru saja berhasil
document.addEventListener('DOMContentLoaded', function () {
    const successModal = new bootstrap.Modal(document.getElementById('modalSuccessTrx'));
    successModal.show();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
