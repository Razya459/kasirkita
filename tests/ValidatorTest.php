<?php

namespace RestoKita\Tests;

use PHPUnit\Framework\TestCase;
use RestoKita\Validator;

/**
 * Class ValidatorTest
 *
 * Menguji fungsionalitas validasi input data produk, login, dan keranjang kasir.
 */
class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    /**
     * Uji data produk valid tidak menghasilkan error.
     */
    public function testValidateProductSuccess(): void
    {
        $validData = [
            'nama_produk' => 'Nasi Goreng Spesial',
            'kategori'    => 'Makanan',
            'harga'       => '25000',
            'stok'        => '50',
        ];

        $errors = $this->validator->validateProduct($validData);
        $this->assertEmpty($errors, 'Produk valid seharusnya tidak menghasilkan error.');
    }

    /**
     * Uji nama produk kosong atau kurang dari 3 karakter.
     */
    public function testValidateProductNameTooShortOrEmpty(): void
    {
        $emptyName = [
            'nama_produk' => '',
            'kategori'    => 'Makanan',
            'harga'       => '25000',
            'stok'        => '10',
        ];
        $errorsEmpty = $this->validator->validateProduct($emptyName);
        $this->assertArrayHasKey('nama_produk', $errorsEmpty);

        $shortName = [
            'nama_produk' => 'AB',
            'kategori'    => 'Makanan',
            'harga'       => '25000',
            'stok'        => '10',
        ];
        $errorsShort = $this->validator->validateProduct($shortName);
        $this->assertArrayHasKey('nama_produk', $errorsShort);
    }

    /**
     * Uji kategori produk tidak valid.
     */
    public function testValidateProductInvalidCategory(): void
    {
        $invalidCat = [
            'nama_produk' => 'Es Teh Manis',
            'kategori'    => 'Pakaian', // Kategori tidak diperbolehkan
            'harga'       => '5000',
            'stok'        => '20',
        ];

        $errors = $this->validator->validateProduct($invalidCat);
        $this->assertArrayHasKey('kategori', $errors);
    }

    /**
     * Uji harga produk tidak valid (kosong, bukan angka, atau <= 0).
     */
    public function testValidateProductInvalidPrice(): void
    {
        $zeroPrice = [
            'nama_produk' => 'Sate Ayam',
            'kategori'    => 'Makanan',
            'harga'       => '0',
            'stok'        => '10',
        ];
        $errors = $this->validator->validateProduct($zeroPrice);
        $this->assertArrayHasKey('harga', $errors);

        $negativePrice = [
            'nama_produk' => 'Sate Ayam',
            'kategori'    => 'Makanan',
            'harga'       => '-5000',
            'stok'        => '10',
        ];
        $errors = $this->validator->validateProduct($negativePrice);
        $this->assertArrayHasKey('harga', $errors);

        $stringPrice = [
            'nama_produk' => 'Sate Ayam',
            'kategori'    => 'Makanan',
            'harga'       => 'gratis',
            'stok'        => '10',
        ];
        $errors = $this->validator->validateProduct($stringPrice);
        $this->assertArrayHasKey('harga', $errors);
    }

    /**
     * Uji stok produk bernilai negatif.
     */
    public function testValidateProductNegativeStock(): void
    {
        $data = [
            'nama_produk' => 'Kentang Goreng',
            'kategori'    => 'Snack',
            'harga'       => '15000',
            'stok'        => '-5',
        ];

        $errors = $this->validator->validateProduct($data);
        $this->assertArrayHasKey('stok', $errors);
    }

    /**
     * Uji validasi login sukses dan gagal.
     */
    public function testValidateLogin(): void
    {
        $empty = $this->validator->validateLogin(['username' => '', 'password' => '']);
        $this->assertArrayHasKey('username', $empty);
        $this->assertArrayHasKey('password', $empty);

        $valid = $this->validator->validateLogin(['username' => 'admin', 'password' => 'admin123']);
        $this->assertEmpty($valid);
    }

    /**
     * Uji validasi keranjang belanja kasir.
     */
    public function testValidateCart(): void
    {
        $emptyCart = $this->validator->validateCart([]);
        $this->assertArrayHasKey('cart', $emptyCart);

        $invalidItem = $this->validator->validateCart([
            ['id_produk' => 1, 'qty' => 0]
        ]);
        $this->assertArrayHasKey('item_0_qty', $invalidItem);

        $validCart = $this->validator->validateCart([
            ['id_produk' => 1, 'qty' => 2],
            ['id_produk' => 3, 'qty' => 1],
        ]);
        $this->assertEmpty($validCart);
    }

    /**
     * Uji validasi input diskon manual.
     */
    public function testValidateDiscount(): void
    {
        // Valid diskon normal
        $this->assertEmpty($this->validator->validateDiscount(5000, 50000));
        $this->assertEmpty($this->validator->validateDiscount(0, 50000));
        $this->assertEmpty($this->validator->validateDiscount(null, 50000));
        $this->assertEmpty($this->validator->validateDiscount('', 50000));

        // Diskon bukan angka
        $errorsNotNum = $this->validator->validateDiscount('diskon-spesial', 50000);
        $this->assertArrayHasKey('discount', $errorsNotNum);

        // Diskon negatif
        $errorsNeg = $this->validator->validateDiscount(-500, 50000);
        $this->assertArrayHasKey('discount', $errorsNeg);

        // Diskon melebihi subtotal
        $errorsExceed = $this->validator->validateDiscount(60000, 50000);
        $this->assertArrayHasKey('discount', $errorsExceed);
    }

    /**
     * Uji validasi file unggahan gambar produk.
     */
    public function testValidateImage(): void
    {
        // 1. Gambar tidak wajib dan tidak diunggah -> lolos
        $this->assertEmpty($this->validator->validateImage(null, false));
        $this->assertEmpty($this->validator->validateImage(['error' => UPLOAD_ERR_NO_FILE], false));

        // 2. Gambar wajib tapi tidak diunggah -> error
        $errorsRequired = $this->validator->validateImage(null, true);
        $this->assertArrayHasKey('gambar', $errorsRequired);

        // 3. File upload error -> error
        $errorsUpload = $this->validator->validateImage(['error' => UPLOAD_ERR_INI_SIZE], false);
        $this->assertArrayHasKey('gambar', $errorsUpload);

        // 4. File ukuran melebihi 2MB -> error
        $oversized = [
            'error' => UPLOAD_ERR_OK,
            'name'  => 'makanan.jpg',
            'size'  => 3 * 1024 * 1024,
        ];
        $errorsSize = $this->validator->validateImage($oversized, false);
        $this->assertArrayHasKey('gambar', $errorsSize);

        // 5. Ekstensi tidak didukung (contoh .pdf atau .exe) -> error
        $invalidExt = [
            'error' => UPLOAD_ERR_OK,
            'name'  => 'script.exe',
            'size'  => 1024,
        ];
        $errorsExt = $this->validator->validateImage($invalidExt, false);
        $this->assertArrayHasKey('gambar', $errorsExt);

        // 6. File valid dummy
        $validFile = [
            'error' => UPLOAD_ERR_OK,
            'name'  => 'nasi_goreng.jpg',
            'size'  => 500 * 1024,
        ];
        $this->assertEmpty($this->validator->validateImage($validFile, false));
    }
}
