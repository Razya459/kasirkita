<?php

namespace RestoKita;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Class Database
 *
 * Mengelola koneksi basis data MySQL menggunakan PDO (PHP Data Objects).
 * Menerapkan pola Singleton sederhana agar koneksi tidak dibuat berulang-ulang
 * dalam satu siklus request HTTP.
 *
 * @package RestoKita
 */
class Database
{
    /**
     * Instance PDO tunggal.
     */
    private static ?PDO $instance = null;

    /**
     * Private constructor untuk mencegah instansiasi langsung (Singleton).
     */
    private function __construct()
    {
    }

    /**
     * Mendapatkan objek koneksi PDO yang aktif.
     *
     * @param array<string, mixed>|null $customConfig Konfigurasi opsional (berguna untuk testing)
     * @return PDO Objek koneksi PDO
     * @throws RuntimeException Jika koneksi ke database gagal
     */
    public static function getConnection(?array $customConfig = null): PDO
    {
        if (self::$instance === null || $customConfig !== null) {
            $config = $customConfig ?? require __DIR__ . '/../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                $pdo = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );

                if ($customConfig === null) {
                    self::$instance = $pdo;
                } else {
                    return $pdo;
                }
            } catch (PDOException $e) {
                // Berikan pesan ramah bagi siswa SMK jika database belum siap
                throw new RuntimeException(
                    "Gagal terhubung ke database MySQL [{$config['dbname']}]: " . $e->getMessage() . 
                    ". Pastikan MySQL (XAMPP/Laragon) aktif dan database sudah diimport melalui file database/schema.sql."
                );
            }
        }

        return self::$instance;
    }

    /**
     * Mengatur ulang instance koneksi (berguna untuk testing atau reset).
     *
     * @return void
     */
    public static function resetConnection(): void
    {
        self::$instance = null;
    }
}
