<?php

namespace RestoKita;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Class Product
 *
 * Mengelola operasi CRUD (Create, Read, Update, Delete) data produk/menu restoran
 * menggunakan PDO prepared statement untuk keamanan dari SQL Injection.
 *
 * @package RestoKita
 */
class Product
{
    private PDO $db;

    /**
     * Inisialisasi model Product.
     *
     * @param PDO|null $db Objek PDO
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Mengambil seluruh produk dengan opsi pencarian kata kunci dan filter kategori.
     *
     * @param string $search Kata kunci nama produk (opsional)
     * @param string $kategori Filter kategori Makanan/Minuman/Snack (opsional)
     * @return array<int, array<string, mixed>> Daftar produk
     */
    public function getAll(string $search = '', string $kategori = ''): array
    {
        $sql = 'SELECT id_produk, nama_produk, kategori, harga, stok, created_at, updated_at 
                FROM produk 
                WHERE 1=1';
        $params = [];

        if (trim($search) !== '') {
            $sql .= ' AND nama_produk LIKE :search';
            $params[':search'] = '%' . trim($search) . '%';
        }

        if (trim($kategori) !== '') {
            $sql .= ' AND kategori = :kategori';
            $params[':kategori'] = trim($kategori);
        }

        $sql .= ' ORDER BY kategori ASC, nama_produk ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Mengambil satu data produk berdasarkan ID.
     *
     * @param int $id ID produk
     * @return array<string, mixed>|null Data produk atau null jika tidak ditemukan
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id_produk, nama_produk, kategori, harga, stok, created_at, updated_at 
             FROM produk 
             WHERE id_produk = :id 
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Menyimpan data produk baru ke database.
     *
     * @param array{nama_produk: string, kategori: string, harga: float|int, stok: int} $data
     * @return int ID produk yang baru saja dibuat
     * @throws RuntimeException Jika penyimpanan gagal
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO produk (nama_produk, kategori, harga, stok) 
                VALUES (:nama_produk, :kategori, :harga, :stok)';

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':nama_produk' => trim($data['nama_produk']),
            ':kategori'    => trim($data['kategori']),
            ':harga'       => (float) $data['harga'],
            ':stok'        => (int) $data['stok'],
        ]);

        if (!$success) {
            throw new RuntimeException('Gagal menambahkan produk baru ke database.');
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Memperbarui data produk yang sudah ada.
     *
     * @param int $id ID produk yang akan diperbarui
     * @param array{nama_produk: string, kategori: string, harga: float|int, stok: int} $data
     * @return bool True jika berhasil diperbarui
     * @throws RuntimeException Jika pembaruan gagal
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE produk 
                SET nama_produk = :nama_produk, 
                    kategori = :kategori, 
                    harga = :harga, 
                    stok = :stok 
                WHERE id_produk = :id';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nama_produk' => trim($data['nama_produk']),
            ':kategori'    => trim($data['kategori']),
            ':harga'       => (float) $data['harga'],
            ':stok'        => (int) $data['stok'],
            ':id'          => $id,
        ]);
    }

    /**
     * Menghapus data produk berdasarkan ID.
     *
     * @param int $id ID produk yang akan dihapus
     * @return bool True jika berhasil
     * @throws RuntimeException Jika produk tidak dapat dihapus karena berelasi dengan transaksi
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM produk WHERE id_produk = :id');
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            // Kode SQLSTATE 23000 menandakan foreign key constraint restriction
            if ($e->getCode() === '23000') {
                throw new RuntimeException(
                    'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi penjualan. Ubah stok menjadi 0 jika ingin menonaktifkannya.'
                );
            }
            throw new RuntimeException('Gagal menghapus produk: ' . $e->getMessage());
        }
    }

    /**
     * Menghitung total jumlah seluruh produk di database (untuk ringkasan dashboard).
     *
     * @return int Jumlah produk
     */
    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM produk');
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }
}
