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

    // Validasi file gambar jika ada yang diunggah
    $imageFile = $_FILES['gambar'] ?? null;
    $imageErrors = $validator->validateImage($imageFile, false);
    if (!empty($imageErrors)) {
        $errors = array_merge($errors, $imageErrors);
    }

    if (empty($errors)) {
        try {
            $updatePayload = [
                'nama_produk' => $formData['nama_produk'],
                'kategori'    => $formData['kategori'],
                'harga'       => (float) $formData['harga'],
                'stok'        => (int) $formData['stok'],
            ];

            // Cek apakah ada file gambar baru yang diunggah
            if ($imageFile && isset($imageFile['error']) && $imageFile['error'] === UPLOAD_ERR_OK) {
                $newGambar = Helper::uploadImage($imageFile);
                if ($newGambar) {
                    Helper::deleteImage($product['gambar'] ?? null);
                    $updatePayload['gambar'] = $newGambar;
                }
            } elseif (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] === '1') {
                Helper::deleteImage($product['gambar'] ?? null);
                $updatePayload['gambar'] = null;
            }

            $productModel->update($id, $updatePayload);

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

                    <form action="edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" novalidate>
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
                        <div class="mb-3">
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

                        <!-- Foto / Gambar Menu -->
                        <div class="mb-4">
                            <label for="gambar" class="form-label fw-semibold small">
                                <i class="bi bi-image text-resto me-1"></i>Foto / Gambar Menu <span class="text-muted fw-normal">(Opsional)</span>
                            </label>

                            <?php if (!empty($product['gambar'])): ?>
                                <div class="mb-2 p-2 border rounded-3 bg-light d-flex align-items-center gap-3">
                                    <img src="<?= $baseUrl ?>/<?= Helper::e($product['gambar']) ?>" alt="Foto Menu Saat Ini" class="rounded object-fit-cover shadow-sm" style="width: 75px; height: 60px;">
                                    <div>
                                        <div class="small fw-semibold text-dark">Foto Menu Saat Ini</div>
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="hapus_gambar" value="1" id="hapus_gambar">
                                            <label class="form-check-label small text-danger" for="hapus_gambar">
                                                Hapus foto ini (gunakan icon bawaan)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <input type="file" 
                                   class="form-control <?= isset($errors['gambar']) ? 'is-invalid' : '' ?>" 
                                   id="gambar" 
                                   name="gambar" 
                                   accept="image/jpeg,image/png,image/webp,image/jpg" 
                                   onchange="previewImage(this)">
                            <?php if (isset($errors['gambar'])): ?>
                                <div class="invalid-feedback"><?= Helper::e($errors['gambar']) ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Pilih file baru jika ingin mengganti foto. Format: JPG, JPEG, PNG, WEBP (Maksimal 2 MB).</small>

                            <!-- Live Preview Box -->
                            <div id="image-preview-wrapper" class="mt-2 d-none">
                                <div class="p-2 border rounded-3 bg-light d-inline-block text-center">
                                    <div class="small fw-semibold text-primary mb-1">Preview Foto Baru:</div>
                                    <img id="image-preview" src="#" alt="Preview Gambar" class="rounded object-fit-cover shadow-sm" style="width: 140px; height: 110px;">
                                    <div class="mt-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;" onclick="resetImagePreview()">
                                            <i class="bi bi-x-circle me-1"></i> Batal Ganti Foto
                                        </button>
                                    </div>
                                </div>
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

<script>
function previewImage(input) {
    const previewWrapper = document.getElementById('image-preview-wrapper');
    const previewImg = document.getElementById('image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewWrapper.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        resetImagePreview();
    }
}

function resetImagePreview() {
    const input = document.getElementById('gambar');
    if (input) input.value = '';
    const previewWrapper = document.getElementById('image-preview-wrapper');
    if (previewWrapper) previewWrapper.classList.add('d-none');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
