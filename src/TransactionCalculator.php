<?php

namespace RestoKita;

use InvalidArgumentException;

/**
 * Class TransactionCalculator
 *
 * Mengelola logika murni kalkulasi transaksi kasir restoran:
 * - Subtotal belanja
 * - Diskon bertingkat berdasarkan nominal subtotal
 * - Dasar Pengenaan Pajak (DPP)
 * - Pajak Pertambahan Nilai (PPN 10%)
 * - Total akhir yang harus dibayarkan pelanggan
 *
 * Class ini tidak memiliki dependensi database sehingga sangat mudah di-unit test.
 *
 * @package RestoKita
 */
class TransactionCalculator
{
    /**
     * Tarif Pajak Pertambahan Nilai (PPN) = 10%.
     */
    public const TAX_RATE = 0.10;

    /**
     * Konfigurasi batas diskon bertingkat:
     * - Subtotal < 50.000       => Diskon 0%
     * - 50.000 s.d. 99.999      => Diskon 10%
     * - 100.000 s.d. 199.999    => Diskon 15%
     * - >= 200.000              => Diskon 20%
     */
    public const DISCOUNT_TIERS = [
        [
            'min'  => 200000.0,
            'max'  => null,
            'rate' => 0.20,
        ],
        [
            'min'  => 100000.0,
            'max'  => 199999.99,
            'rate' => 0.15,
        ],
        [
            'min'  => 50000.0,
            'max'  => 99999.99,
            'rate' => 0.10,
        ],
        [
            'min'  => 0.0,
            'max'  => 49999.99,
            'rate' => 0.00,
        ],
    ];

    /**
     * Menghitung subtotal dari daftar item pesanan.
     *
     * @param array<int, array{harga: float|int, qty: int}> $items Daftar item pesanan
     * @return float Total subtotal (harga * qty)
     * @throws InvalidArgumentException Jika keranjang kosong, qty <= 0, atau harga < 0
     */
    public function calculateSubtotal(array $items): float
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Keranjang belanja tidak boleh kosong.');
        }

        $subtotal = 0.0;

        foreach ($items as $index => $item) {
            if (!isset($item['harga']) || !isset($item['qty'])) {
                throw new InvalidArgumentException("Item pada index {$index} wajib memiliki 'harga' dan 'qty'.");
            }

            $harga = (float) $item['harga'];
            $qty   = (int) $item['qty'];

            if ($qty <= 0) {
                throw new InvalidArgumentException("Jumlah qty pada index {$index} harus lebih besar dari 0.");
            }

            if ($harga < 0) {
                throw new InvalidArgumentException("Harga produk pada index {$index} tidak boleh bernilai negatif.");
            }

            $subtotal += ($harga * $qty);
        }

        return round($subtotal, 2);
    }

    /**
     * Mendapatkan persentase diskon bertingkat berdasarkan nominal subtotal.
     *
     * Aturan:
     * - Subtotal < 50.000       => 0% (0.00)
     * - 50.000 s.d. 99.999      => 10% (0.10)
     * - 100.000 s.d. 199.999    => 15% (0.15)
     * - >= 200.000              => 20% (0.20)
     *
     * @param float $subtotal Nilai subtotal belanja
     * @return float Persentase diskon dalam desimal (contoh: 0.10 untuk 10%)
     * @throws InvalidArgumentException Jika subtotal bernilai negatif
     */
    public function getDiscountRate(float $subtotal): float
    {
        if ($subtotal < 0) {
            throw new InvalidArgumentException('Subtotal tidak boleh bernilai negatif.');
        }

        // Cek dari tier diskon tertinggi ke terendah
        foreach (self::DISCOUNT_TIERS as $tier) {
            $isAboveMin = $subtotal >= $tier['min'];
            $isBelowMax = ($tier['max'] === null) || ($subtotal <= $tier['max']);

            if ($isAboveMin && $isBelowMax) {
                return (float) $tier['rate'];
            }
        }

        return 0.00;
    }

    /**
     * Menghitung besaran nilai diskon dalam rupiah (subtotal × diskon_rate).
     *
     * @param float $subtotal Nilai subtotal belanja
     * @return float Nilai nominal diskon (Rp)
     * @throws InvalidArgumentException Jika subtotal negatif
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < 0) {
            throw new InvalidArgumentException('Subtotal tidak boleh bernilai negatif.');
        }

        $rate = $this->getDiscountRate($subtotal);
        return round($subtotal * $rate, 2);
    }

    /**
     * Menghitung Dasar Pengenaan Pajak (DPP) yaitu subtotal dikurangi diskon.
     *
     * @param float $subtotal Nilai subtotal belanja
     * @param float $discount Nilai diskon belanja
     * @return float Dasar pengenaan pajak (subtotal - diskon)
     * @throws InvalidArgumentException Jika subtotal negatif atau diskon melebihi subtotal
     */
    public function calculateTaxBase(float $subtotal, float $discount): float
    {
        if ($subtotal < 0) {
            throw new InvalidArgumentException('Subtotal tidak boleh bernilai negatif.');
        }

        if ($discount < 0) {
            throw new InvalidArgumentException('Diskon tidak boleh bernilai negatif.');
        }

        if ($discount > $subtotal) {
            throw new InvalidArgumentException('Diskon tidak boleh melebihi nilai subtotal.');
        }

        return round($subtotal - $discount, 2);
    }

    /**
     * Menghitung nilai pajak PPN (dasar_pajak × 10%).
     *
     * @param float $subtotal Nilai subtotal belanja
     * @param float $discount Nilai diskon belanja
     * @return float Nilai pajak (10% dari dasar pajak)
     * @throws InvalidArgumentException Jika nilai input tidak valid
     */
    public function calculateTax(float $subtotal, float $discount): float
    {
        $taxBase = $this->calculateTaxBase($subtotal, $discount);
        return round($taxBase * self::TAX_RATE, 2);
    }

    /**
     * Menghitung total akhir pembayaran (dasar_pajak + pajak).
     *
     * @param float $subtotal Nilai subtotal belanja
     * @param float $discount Nilai diskon belanja
     * @param float $tax Nilai pajak
     * @return float Total akhir yang wajib dibayar
     * @throws InvalidArgumentException Jika ada komponen bernilai negatif
     */
    public function calculateTotal(float $subtotal, float $discount, float $tax): float
    {
        $taxBase = $this->calculateTaxBase($subtotal, $discount);

        if ($tax < 0) {
            throw new InvalidArgumentException('Pajak tidak boleh bernilai negatif.');
        }

        return round($taxBase + $tax, 2);
    }

    /**
     * Melakukan kalkulasi menyeluruh sekaligus dari daftar item pesanan.
     *
     * @param array<int, array{harga: float|int, qty: int}> $items Daftar item
     * @return array{
     *     subtotal: float,
     *     discount_rate: float,
     *     discount_percent: int,
     *     discount: float,
     *     tax_base: float,
     *     tax: float,
     *     total_bayar: float
     * } Hasil perhitungan lengkap
     * @throws InvalidArgumentException Jika item tidak valid
     */
    public function calculate(array $items): array
    {
        $subtotal        = $this->calculateSubtotal($items);
        $discountRate    = $this->getDiscountRate($subtotal);
        $discount        = $this->calculateDiscount($subtotal);
        $taxBase         = $this->calculateTaxBase($subtotal, $discount);
        $tax             = $this->calculateTax($subtotal, $discount);
        $totalBayar      = $this->calculateTotal($subtotal, $discount, $tax);

        return [
            'subtotal'         => $subtotal,
            'discount_rate'    => $discountRate,
            'discount_percent' => (int) round($discountRate * 100),
            'discount'         => $discount,
            'tax_base'         => $taxBase,
            'tax'              => $tax,
            'total_bayar'      => $totalBayar,
        ];
    }
}
