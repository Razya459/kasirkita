<?php

namespace RestoKita;

/**
 * Class Validator
 *
 * Menyediakan fungsi-fungsi validasi input formulir pada aplikasi RestoKita:
 * - Validasi data produk (nama, kategori, harga, stok)
 * - Validasi autentikasi (login / user)
 * - Validasi item transaksi kasir
 *
 * @package RestoKita
 */
class Validator
{
    /**
     * Kategori produk yang diperbolehkan.
     */
    public const ALLOWED_CATEGORIES = ['Makanan', 'Minuman', 'Snack'];

    /**
     * Validasi data input form produk (tambah dan edit).
     *
     * @param array<string, mixed> $data Data input dari $_POST
     * @return array<string, string> Daftar pesan error validasi (kosong jika valid)
     */
    public function validateProduct(array $data): array
    {
        $errors = [];

        // Validasi Nama Produk
        $nama = trim((string) ($data['nama_produk'] ?? ''));
        if ($nama === '') {
            $errors['nama_produk'] = 'Nama produk wajib diisi.';
        } elseif (mb_strlen($nama) < 3) {
            $errors['nama_produk'] = 'Nama produk minimal 3 karakter.';
        } elseif (mb_strlen($nama) > 100) {
            $errors['nama_produk'] = 'Nama produk maksimal 100 karakter.';
        }

        // Validasi Kategori
        $kategori = trim((string) ($data['kategori'] ?? ''));
        if ($kategori === '') {
            $errors['kategori'] = 'Kategori produk wajib dipilih.';
        } elseif (!in_array($kategori, self::ALLOWED_CATEGORIES, true)) {
            $errors['kategori'] = 'Kategori produk harus salah satu dari: ' . implode(', ', self::ALLOWED_CATEGORIES) . '.';
        }

        // Validasi Harga
        $hargaRaw = $data['harga'] ?? null;
        if ($hargaRaw === null || $hargaRaw === '') {
            $errors['harga'] = 'Harga produk wajib diisi.';
        } elseif (!is_numeric($hargaRaw)) {
            $errors['harga'] = 'Harga produk harus berupa angka.';
        } else {
            $harga = (float) $hargaRaw;
            if ($harga <= 0) {
                $errors['harga'] = 'Harga produk harus lebih besar dari 0.';
            }
        }

        // Validasi Stok
        $stokRaw = $data['stok'] ?? null;
        if ($stokRaw === null || $stokRaw === '') {
            $errors['stok'] = 'Stok produk wajib diisi.';
        } elseif (!is_numeric($stokRaw) || (int) $stokRaw != $stokRaw) {
            $errors['stok'] = 'Stok produk harus berupa bilangan bulat.';
        } else {
            $stok = (int) $stokRaw;
            if ($stok < 0) {
                $errors['stok'] = 'Stok produk tidak boleh bernilai negatif.';
            }
        }

        return $errors;
    }

    /**
     * Validasi kredensial login pengguna.
     *
     * @param array<string, mixed> $data Data login (username & password)
     * @return array<string, string> Daftar error validasi
     */
    public function validateLogin(array $data): array
    {
        $errors = [];

        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '') {
            $errors['username'] = 'Username wajib diisi.';
        }

        if ($password === '') {
            $errors['password'] = 'Password wajib diisi.';
        }

        return $errors;
    }

    /**
     * Validasi data pesanan pada kasir sebelum diproses.
     *
     * @param array<int, array{id_produk?: int, qty?: int}> $cartItems Daftar item belanja
     * @return array<string, string> Daftar error validasi
     */
    public function validateCart(array $cartItems): array
    {
        $errors = [];

        if (empty($cartItems)) {
            $errors['cart'] = 'Keranjang belanja masih kosong. Silakan pilih menu terlebih dahulu.';
            return $errors;
        }

        foreach ($cartItems as $index => $item) {
            $idProduk = $item['id_produk'] ?? null;
            $qty      = $item['qty'] ?? null;

            if (empty($idProduk) || !is_numeric($idProduk) || (int) $idProduk <= 0) {
                $errors["item_{$index}_id"] = "ID Produk pada baris " . ($index + 1) . " tidak valid.";
            }

            if ($qty === null || !is_numeric($qty) || (int) $qty <= 0) {
                $errors["item_{$index}_qty"] = "Jumlah (qty) pada baris " . ($index + 1) . " harus berupa angka minimal 1.";
            }
        }

        return $errors;
    }
}
