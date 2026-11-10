<?php
/**
 * Setup Database Otomatis - RestoKita
 * Skrip instalasi untuk membuat database dan mengisi seed data.
 * Mendukung eksekusi via CLI maupun Browser.
 */

$isCli = (php_sapi_name() === 'cli');

$config = require __DIR__ . '/config/database.php';

$host     = $config['host'];
$port     = $config['port'];
$dbname   = $config['dbname'];
$username = $config['username'];
$password = $config['password'];

if (!$isCli) {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Instalasi Database RestoKita</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '</head><body class="bg-light p-5"><div class="container" style="max-width: 650px;"><div class="card shadow-sm p-4">';
    echo '<h3 class="fw-bold text-dark mb-3">Instalasi Database RestoKita</h3>';
}

function printMsg(string $msg, string $type = 'info', bool $isCli = true): void {
    if ($isCli) {
        $prefix = match ($type) {
            'success' => '[BERHASIL] ',
            'error'   => '[GAGAL] ',
            default   => '[INFO] ',
        };
        echo $prefix . $msg . PHP_EOL;
    } else {
        $alertClass = match ($type) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            default   => 'alert-info',
        };
        echo "<div class='alert {$alertClass} py-2 mb-2'>{$msg}</div>";
    }
}

/**
 * Menjalankan file SQL query per statement dengan aman.
 */
function executeSqlFile(PDO $pdo, string $filePath): void {
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException("Gagal membaca file: {$filePath}");
    }

    // Hapus komentar SQL
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Pisahkan query berdasarkan tanda titik koma (;)
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        $trimmed = trim($query);
        if ($trimmed !== '') {
            $pdo->exec($trimmed);
        }
    }
}

try {
    printMsg("Menghubungkan ke server MySQL pada {$host}:{$port}...", 'info', $isCli);

    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    printMsg("Membuat database '{$dbname}' jika belum ada...", 'info', $isCli);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");

    printMsg("Mengeksekusi database/schema.sql...", 'info', $isCli);
    executeSqlFile($pdo, __DIR__ . '/database/schema.sql');
    printMsg("Skema tabel berhasil dibuat (pengguna, produk, transaksi, detail_transaksi).", 'success', $isCli);

    printMsg("Mengeksekusi database/seed.sql...", 'info', $isCli);
    executeSqlFile($pdo, __DIR__ . '/database/seed.sql');
    printMsg("Data awal (seed) berhasil diisikan (Admin, Kasir, 12 Menu Restoran).", 'success', $isCli);

    printMsg("Database siap digunakan!", 'success', $isCli);

    if (!$isCli) {
        echo '<div class="mt-4"><a href="public/login.php" class="btn btn-primary w-100 fw-bold">Buka Aplikasi RestoKita &raquo;</a></div>';
    }
} catch (PDOException $e) {
    printMsg("Koneksi gagal: " . $e->getMessage(), 'error', $isCli);
    printMsg("Pastikan Apache & MySQL di XAMPP / Laragon sudah dalam keadaan START.", 'error', $isCli);
}

if (!$isCli) {
    echo '</div></div></body></html>';
}
