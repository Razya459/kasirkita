<?php
/**
 * Edit Menu Produk - RestoKita
 */

use RestoKita\Auth;
use RestoKita\Helper;
use RestoKita\Product;
use RestoKita\Validator;

require_once __DIR__ . '/../../vendor/autoload.php';

Auth::requireAdmin('index.php');

$pageTitle = 'Edit Menu - RestoKita';
$activePage = 'produk';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    Helper::setFlash('danger', 'ID Produk tidak valid.');
    Helper::redirect('index.php');
}

$productModel = new Product();
$product = $productModel->getById($id);

if (!$product) {
    Helper::setFlash('danger', 'Produk tidak ditemukan di database.');
    Helper::redirect('index.php');
}

$errors = [];
$formData = [
    'nama_produk' => $product['nama_produk'],
    'kategori'    => $product['kategori'],
    'harga'       => (string) ((int) $product['harga']),
    'stok'        => (string) $product['stok'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'nama_produk' => trim($_POST['nama_produk'] ?? ''),
        'kategori'    => trim($_POST['kategori'] ?? ''),
        'harga'       => trim($_POST['harga'] ?? ''),
        'stok'        => trim($_POST['stok'] ?? ''),
    ];

    $validator = new Validator();
    $errors = $validator->validateProduct($formData);

    if (empty($errors)) {
        try {
            $productModel->update($id, [
                'nama_produk' => $formData['nama_produk'],
                'kategori'    => $formData['kategori'],
                'harga'       => (float) $formData['harga'],
                'stok'        => (int) $formData['stok'],
            ]);

            Helper::setFlash('success', "Data menu '{$formData['nama_produk']}' berhasil diperbarui.");
            Helper::redirect('index.php');
        } catch (\Throwable $e) {
            $errors['general'] = 'Gagal memperbarui menu: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid p-0">
    <div class="mb-4">
        <a href="index.php" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Menu
        </a>
        <h4 class="fw-bold mb-1 text-dark">Edit Menu Produk</h4>
        <p class="text-muted small mb-0">Ubah informasi menu, harga, atau stok produk.</p>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger small py-2"><?= Helper::e($errors['general']) ?></div>
                    <?php endif; ?>

                    <form action="edit.php?id=<?= $id ?>" method="POST" novalidate>
                        <!-- Nama Produk -->
                        <div class="mb-3">
                            <label for="nama_produk" class="form-label fw-semibold small">Nama Menu Produk <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['nama_produk']) ? 'is-invalid' : '' ?>" 
                                   id="nama_produk" 
                                   name="nama_produk" 
                                   value="<?= Helper::e($formData['nama_produk']) ?>" 
                                   required>
                            <?php if (isset($errors['nama_produk'])): ?>
                                <div class="invalid-feedback"><?= Helper::e($errors['nama_produk']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Kategori & Stok -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="kategori" class="form-label fw-semibold small">Kategori Menu <span class="text-danger">*</span></label>
                                <select class="form-select <?= isset($errors['kategori']) ? 'is-invalid' : '' ?>" id="kategori" name="kategori" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach (Validator::ALLOWED_CATEGORIES as $kat): ?>
                                        <option value="<?= $kat ?>" <?= ($formData['kategori'] === $kat) ? 'selected' : '' ?>>
                                            <?= $kat ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['kategori'])): ?>
                                    <div class="invalid-feedback"><?= Helper::e($errors['kategori']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="stok" class="form-label fw-semibold small">Stok (Porsi/Pcs) <span class="text-danger">*</span></label>
                                <input type="number" 
                                       class="form-control <?= isset($errors['stok']) ? 'is-invalid' : '' ?>" 
                                       id="stok" 
                                       name="stok" 
                                       min="0" 
                                       value="<?= Helper::e($formData['stok']) ?>" 
                                       required>
                                <?php if (isset($errors['stok'])): ?>
                                    <div class="invalid-feedback"><?= Helper::e($errors['stok']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Harga Satuan -->
                        <div class="mb-4">
                            <label for="harga" class="form-label fw-semibold small">Harga Satuan (Rupiah) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" 
                                       class="form-control <?= isset($errors['harga']) ? 'is-invalid' : '' ?>" 
                                       id="harga" 
                                       name="harga" 
                                       min="100" 
                                       step="500" 
                                       value="<?= Helper::e($formData['harga']) ?>" 
                                       required>
                                <?php if (isset($errors['harga'])): ?>
                                    <div class="invalid-feedback"><?= Helper::e($errors['harga']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-resto px-4 py-2 fw-semibold">
                                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                            </button>
                            <a href="index.php" class="btn btn-light border px-4 py-2">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
