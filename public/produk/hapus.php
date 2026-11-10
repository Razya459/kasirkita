<?php
/**
 * Hapus Menu Produk - RestoKita
 */

use RestoKita\Auth;
use RestoKita\Helper;
use RestoKita\Product;

require_once __DIR__ . '/../../vendor/autoload.php';

Auth::requireAdmin('index.php');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    Helper::setFlash('danger', 'ID Produk tidak valid.');
    Helper::redirect('index.php');
}

try {
    $productModel = new Product();
    $product = $productModel->getById($id);

    if (!$product) {
        Helper::setFlash('danger', 'Produk tidak ditemukan.');
        Helper::redirect('index.php');
    }

    $productModel->delete($id);
    Helper::deleteImage($product['gambar'] ?? null);
    Helper::setFlash('success', "Menu '{$product['nama_produk']}' berhasil dihapus dari sistem.");
} catch (\Throwable $e) {
    Helper::setFlash('danger', $e->getMessage());
}

Helper::redirect('index.php');
