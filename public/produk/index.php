<?php
/**
 * Manajemen Produk - Daftar Menu Restoran
 */

use RestoKita\Auth;
use RestoKita\Helper;
use RestoKita\Product;

require_once __DIR__ . '/../../vendor/autoload.php';

$pageTitle = 'Manajemen Menu - RestoKita';
$activePage = 'produk';

require_once __DIR__ . '/../../templates/header.php';

$search = trim($_GET['search'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');

try {
    $productModel = new Product();
    $products = $productModel->getAll($search, $kategori);
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">Error: ' . Helper::e($e->getMessage()) . '</div>';
    require_once __DIR__ . '/../../templates/footer.php';
    exit;
}
?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Daftar Menu & Produk</h4>
            <p class="text-muted small mb-0">Kelola daftar makanan, minuman, harga, dan stok restoran.</p>
        </div>
        <?php if (Auth::isAdmin()): ?>
        <div>
            <a href="<?= $baseUrl ?>/produk/tambah.php" class="btn btn-resto d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm">
                <i class="bi bi-plus-lg"></i>
                <span>Tambah Menu Baru</span>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filter & Pencarian Bar -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="index.php" method="GET" class="row g-2 align-items-center">
                <!-- Filter Kategori Tabs/Select -->
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-tags"></i></span>
                        <select name="kategori" class="form-select bg-light" onchange="this.form.submit()">
                            <option value="">-- Semua Kategori --</option>
                            <option value="Makanan" <?= ($kategori === 'Makanan') ? 'selected' : '' ?>>Makanan</option>
                            <option value="Minuman" <?= ($kategori === 'Minuman') ? 'selected' : '' ?>>Minuman</option>
                            <option value="Snack" <?= ($kategori === 'Snack') ? 'selected' : '' ?>>Snack</option>
                        </select>
                    </div>
                </div>

                <!-- Kolom Pencarian -->
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               name="search" 
                               class="form-control bg-light" 
                               placeholder="Cari nama menu..." 
                               value="<?= Helper::e($search) ?>">
                        <?php if ($search !== '' || $kategori !== ''): ?>
                            <a href="index.php" class="btn btn-outline-secondary" title="Reset Filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Produk -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 text-center" style="width: 60px;">No</th>
                            <th>Nama Menu</th>
                            <th>Kategori</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center pe-3" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    Tidak ada data menu yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $idx => $p): ?>
                                <tr>
                                    <td class="ps-3 text-center text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= Helper::e($p['nama_produk']) ?></div>
                                        <small class="text-muted">ID: #<?= (int) $p['id_produk'] ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeColor = match ($p['kategori']) {
                                            'Makanan' => 'bg-primary',
                                            'Minuman' => 'bg-info text-dark',
                                            'Snack'   => 'bg-warning text-dark',
                                            default   => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeColor ?> px-2 py-1 rounded-pill">
                                            <?= Helper::e($p['kategori']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold text-dark">
                                        <?= Helper::formatRupiah($p['harga']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ((int)$p['stok'] <= 0): ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php elseif ((int)$p['stok'] < 10): ?>
                                            <span class="badge bg-warning text-dark"><?= (int) $p['stok'] ?> porsi (Kritis)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-75"><?= (int) $p['stok'] ?> porsi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php if (Auth::isAdmin()): ?>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= $baseUrl ?>/produk/edit.php?id=<?= $p['id_produk'] ?>" class="btn btn-outline-primary" title="Edit Produk">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </a>
                                                <a href="<?= $baseUrl ?>/produk/hapus.php?id=<?= $p['id_produk'] ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus produk <?= Helper::e($p['nama_produk']) ?>?')" 
                                                   title="Hapus Produk">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">Khusus Admin</span>
                                        <?php endif; ?>
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

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
