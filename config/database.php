<?php
/**
 * Konfigurasi Koneksi Database MySQL
 * Aplikasi RestoKita - POS Kasir Restoran
 */

// Deteksi otomatis port MySQL (3306 standar atau 3307 jika XAMPP diubah)
$defaultPort = '3306';
if (!getenv('DB_PORT')) {
    $fp = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 0.1);
    if ($fp) {
        fclose($fp);
        $defaultPort = '3306';
    } else {
        // Cek port 3307 (biasa dipakai di lab SMK / XAMPP alternatif)
        $fp7 = @fsockopen('127.0.0.1', 3307, $errno, $errstr, 0.1);
        if ($fp7) {
            fclose($fp7);
            $defaultPort = '3307';
        }
    }
}

return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: $defaultPort,
    'dbname'   => getenv('DB_NAME') ?: 'restokita',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
