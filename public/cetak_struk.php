<?php
/**
 * Cetak Struk Pembayaran Kasir (Thermal Paper Receipt Format) - RestoKita
 */

use RestoKita\Auth;
use RestoKita\Helper;
use RestoKita\Transaction;

require_once __DIR__ . '/../vendor/autoload.php';

Auth::requireLogin('login.php');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    die('ID Transaksi tidak valid.');
}

$transactionModel = new Transaction();
$transaksi = $transactionModel->getById($id);

if (!$transaksi) {
    die('Transaksi tidak ditemukan.');
}

$details = $transactionModel->getDetails($id);
$dpp = (float)$transaksi['total'] - (float)$transaksi['diskon'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - <?= Helper::e($transaksi['kode_transaksi']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        .struk-card {
            background: #fff;
            max-width: 340px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .border-dashed { border-bottom: 1px dashed #000; margin: 10px 0; }
        .d-flex { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .small { font-size: 12px; }
        .btn-print-wrapper {
            max-width: 340px;
            margin: 0 auto 15px auto;
            display: flex;
            gap: 10px;
        }
        .btn-act {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            cursor: pointer;
            font-family: sans-serif;
            font-weight: 600;
            background: #fff;
        }
        .btn-primary-act {
            background: #ff6b35;
            color: #fff;
            border-color: #ff6b35;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .struk-card { box-shadow: none; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="btn-print-wrapper no-print">
    <button onclick="window.print()" class="btn-act btn-primary-act">
        <i class="bi bi-printer"></i> Cetak Struk
    </button>
    <button onclick="window.close()" class="btn-act">
        Tutup
    </button>
</div>

<div class="struk-card">
    <div class="text-center">
        <h3 style="margin: 0; font-size: 20px;">RESTOKITA</h3>
        <p class="small" style="margin: 2px 0;">Jl. Keahlian Pemrograman No. 5</p>
        <p class="small" style="margin: 2px 0;">Telp: (021) 8899-7766</p>
    </div>

    <div class="border-dashed"></div>

    <div class="small">
        <div class="d-flex">
            <span>No. Nota:</span>
            <span class="fw-bold"><?= Helper::e($transaksi['kode_transaksi']) ?></span>
        </div>
        <div class="d-flex">
            <span>Tanggal:</span>
            <span><?= date('d/m/Y H:i', strtotime($transaksi['tanggal'])) ?></span>
        </div>
        <div class="d-flex">
            <span>Kasir:</span>
            <span><?= Helper::e($transaksi['nama_kasir']) ?></span>
        </div>
    </div>

    <div class="border-dashed"></div>

    <div class="small">
        <?php foreach ($details as $d): ?>
            <div style="margin-bottom: 6px;">
                <div class="fw-bold"><?= Helper::e($d['nama_produk']) ?></div>
                <div class="d-flex">
                    <span><?= (int)$d['qty'] ?> x <?= number_format((float)$d['harga_satuan'], 0, ',', '.') ?></span>
                    <span><?= number_format((float)$d['subtotal'], 0, ',', '.') ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="border-dashed"></div>

    <div class="small">
        <div class="d-flex">
            <span>Subtotal:</span>
            <span><?= number_format((float)$transaksi['total'], 0, ',', '.') ?></span>
        </div>
        <div class="d-flex">
            <span>Diskon:</span>
            <span>- <?= number_format((float)$transaksi['diskon'], 0, ',', '.') ?></span>
        </div>
        <div class="d-flex">
            <span>Dasar Pajak:</span>
            <span><?= number_format($dpp, 0, ',', '.') ?></span>
        </div>
        <div class="d-flex">
            <span>PPN (10%):</span>
            <span><?= number_format((float)$transaksi['pajak'], 0, ',', '.') ?></span>
        </div>
        <div class="border-dashed"></div>
        <div class="d-flex fw-bold" style="font-size: 14px;">
            <span>TOTAL BAYAR:</span>
            <span>Rp <?= number_format((float)$transaksi['total_bayar'], 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="border-dashed"></div>

    <div class="text-center small" style="margin-top: 15px;">
        <p style="margin: 2px 0;">Terima Kasih Atas Kunjungan Anda!</p>
        <p style="margin: 2px 0; color: #555;">Makanan Halal, Lezat & Berkualitas</p>
    </div>
</div>

</body>
</html>
