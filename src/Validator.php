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
     * Format ekstensi gambar yang didukung.
     */
    public const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Format MIME types gambar yang diperbolehkan.
     */
    public const ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Ukuran maksimum file gambar (dalam byte, default 2MB).
     */
    public const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

    /**
     * Validasi file unggahan gambar produk.
     *
     * @param array<string, mixed>|null $file Objek $_FILES['gambar']
     * @param bool $isRequired Apakah gambar wajib diunggah
     * @return array<string, string> Daftar pesan error
     */
    public function validateImage(?array $file, bool $isRequired = false): array
    {
        $errors = [];

        if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            if ($isRequired) {
                $errors['gambar'] = 'Gambar produk wajib diunggah.';
            }
            return $errors;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['gambar'] = 'Terjadi kesalahan saat mengunggah file gambar (Error code: ' . $file['error'] . ').';
            return $errors;
        }

        // Cek ukuran file
        if (($file['size'] ?? 0) > self::MAX_IMAGE_SIZE) {
            $errors['gambar'] = 'Ukuran gambar maksimal adalah 2 MB.';
            return $errors;
        }

        // Cek ekstensi file
        $fileName = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            $errors['gambar'] = 'Format file tidak didukung. Harap unggah file JPG, JPEG, PNG, atau WEBP.';
            return $errors;
        }

        // Cek MIME type dengan finfo jika tersedia
        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName !== '' && file_exists($tmpName)) {
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (!in_array($mime, self::ALLOWED_IMAGE_MIMES, true)) {
                    $errors['gambar'] = 'Tipe MIME file gambar tidak valid (' . $mime . ').';
                    return $errors;
                }
            } elseif (function_exists('getimagesize')) {
                $imageInfo = @getimagesize($tmpName);
                if ($imageInfo === false) {
                    $errors['gambar'] = 'File yang diunggah bukan file gambar yang valid.';
                    return $errors;
                }
            }
        }

        return $errors;
    }

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

    /**
     * Validasi nilai diskon (manual/custom) yang diinputkan oleh kasir.
     *
     * @param mixed $discount Nilai diskon
     * @param float|null $subtotal Nilai subtotal belanja untuk validasi batas maksimum
     * @return array<string, string> Daftar error validasi
     */
    public function validateDiscount(mixed $discount, ?float $subtotal = null): array
    {
        $errors = [];

        if ($discount === null || $discount === '') {
            return $errors;
        }

        if (!is_numeric($discount)) {
            $errors['discount'] = 'Nilai diskon harus berupa angka.';
            return $errors;
        }

        $discountVal = (float) $discount;

        if ($discountVal < 0) {
            $errors['discount'] = 'Diskon tidak boleh bernilai negatif.';
        } elseif ($subtotal !== null && $subtotal >= 0 && $discountVal > $subtotal) {
            $errors['discount'] = 'Diskon tidak boleh melebihi nilai subtotal belanja.';
        }

        return $errors;
    }
}
