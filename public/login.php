<?php
/**
 * Halaman Login - RestoKita POS Kasir
 */

use RestoKita\Auth;
use RestoKita\Helper;
use RestoKita\Validator;

require_once __DIR__ . '/../vendor/autoload.php';

Helper::startSession();

// Jika sudah login, langsung alihkan ke dashboard
if (Auth::check()) {
    Helper::redirect('dashboard.php');
}

$errors = [];
$usernameInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = trim($_POST['username'] ?? '');
    $passwordInput = (string) ($_POST['password'] ?? '');

    $validator = new Validator();
    $errors = $validator->validateLogin([
        'username' => $usernameInput,
        'password' => $passwordInput,
    ]);

    if (empty($errors)) {
        try {
            $auth = new Auth();
            if ($auth->login($usernameInput, $passwordInput)) {
                Helper::setFlash('success', 'Selamat datang kembali, ' . Auth::user()['nama'] . '!');
                Helper::redirect('dashboard.php');
            } else {
                $errors['general'] = 'Username atau password yang Anda masukkan salah.';
            }
        } catch (\Throwable $e) {
            $errors['general'] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Login - RestoKita POS';
$isLoginPage = true;
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="card shadow-lg border-0 rounded-4" style="max-width: 440px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <!-- Logo & Brand Header -->
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-resto-light text-resto mb-3" style="width: 70px; height: 70px;">
                    <i class="bi bi-fire fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">RestoKita</h3>
                <p class="text-muted small">Point of Sales &bull; Kasir Restoran</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 small py-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= Helper::e($errors['general']) ?></div>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="login.php" method="POST" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold small text-secondary">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                        <input type="text" 
                               class="form-control bg-light border-start-0 <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                               id="username" 
                               name="username" 
                               placeholder="Masukkan username" 
                               value="<?= Helper::e($usernameInput) ?>" 
                               required 
                               autofocus>
                    </div>
                    <?php if (isset($errors['username'])): ?>
                        <div class="text-danger small mt-1"><?= Helper::e($errors['username']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold small text-secondary">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" 
                               class="form-control bg-light border-start-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               id="password" 
                               name="password" 
                               placeholder="Masukkan password" 
                               required>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="text-danger small mt-1"><?= Helper::e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-resto w-100 py-2 rounded-3 fw-bold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
                </button>
            </form>

            <!-- Bantuan Akun Cepat untuk Uji Kompetensi (UKK) -->
            <div class="mt-4 pt-3 border-top text-center">
                <small class="text-muted d-block mb-2">Akun Pengujian UKK (Klik untuk isi cepat):</small>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="quickFill('admin', 'admin123')">
                        <i class="bi bi-shield-lock"></i> Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="quickFill('kasir', 'kasir123')">
                        <i class="bi bi-person-badge"></i> Kasir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickFill(user, pass) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
