<?php

namespace RestoKita\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RestoKita\TransactionCalculator;

/**
 * Class TransactionCalculatorTest
 *
 * Menguji seluruh aturan bisnis pada TransactionCalculator:
 * - Kalkulasi subtotal normal dan keranjang kosong
 * - Uji batas (boundary test) tiap tingkatan diskon
 * - Kalkulasi PPN 10% dan total bayar
 * - Verifikasi contoh kasus MUK UKK (58.000 -> 57.420)
 * - Penanganan input tidak valid (qty <= 0, harga negatif)
 */
class TransactionCalculatorTest extends TestCase
{
    private TransactionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TransactionCalculator();
    }

    /**
     * Uji kalkulasi subtotal dengan beberapa item normal.
     */
    public function testCalculateSubtotalNormal(): void
    {
        $items = [
            ['harga' => 25000, 'qty' => 2], // 50.000
            ['harga' => 5000,  'qty' => 1], // 5.000
            ['harga' => 3000,  'qty' => 1], // 3.000
        ];

        $subtotal = $this->calculator->calculateSubtotal($items);
        $this->assertEquals(58000.0, $subtotal);
    }

    /**
     * Uji keranjang kosong harus melempar InvalidArgumentException.
     */
    public function testCalculateSubtotalEmptyCartThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Keranjang belanja tidak boleh kosong.');

        $this->calculator->calculateSubtotal([]);
    }

    /**
     * Uji qty nol atau negatif harus melempar InvalidArgumentException.
     */
    public function testCalculateSubtotalZeroOrNegativeQtyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Jumlah qty pada index 0 harus lebih besar dari 0.');

        $this->calculator->calculateSubtotal([
            ['harga' => 10000, 'qty' => 0]
        ]);
    }

    /**
     * Uji harga bernilai negatif harus melempar InvalidArgumentException.
     */
    public function testCalculateSubtotalNegativePriceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Harga produk pada index 0 tidak boleh bernilai negatif.');

        $this->calculator->calculateSubtotal([
            ['harga' => -5000, 'qty' => 2]
        ]);
    }

    /**
     * Uji struktur item tanpa field 'harga' atau 'qty'.
     */
    public function testCalculateSubtotalMissingKeysThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Item pada index 0 wajib memiliki 'harga' dan 'qty'.");

        /** @phpstan-ignore-next-line */
        $this->calculator->calculateSubtotal([
            ['nama' => 'Nasi Goreng']
        ]);
    }

    /**
     * Uji tingkat diskon untuk subtotal < 50.000 (0%).
     */
    public function testDiscountRateBelow50k(): void
    {
        $this->assertEquals(0.00, $this->calculator->getDiscountRate(0.0));
        $this->assertEquals(0.00, $this->calculator->getDiscountRate(25000.0));
        $this->assertEquals(0.00, $this->calculator->getDiscountRate(49999.0));
    }

    /**
     * Uji nilai batas diskon 10% (50.000 s.d. 99.999).
     */
    public function testDiscountRateBetween50kAnd100k(): void
    {
        $this->assertEquals(0.10, $this->calculator->getDiscountRate(50000.0));
        $this->assertEquals(0.10, $this->calculator->getDiscountRate(58000.0));
        $this->assertEquals(0.10, $this->calculator->getDiscountRate(99999.0));
    }

    /**
     * Uji nilai batas diskon 15% (100.000 s.d. 199.999).
     */
    public function testDiscountRateBetween100kAnd200k(): void
    {
        $this->assertEquals(0.15, $this->calculator->getDiscountRate(100000.0));
        $this->assertEquals(0.15, $this->calculator->getDiscountRate(150000.0));
        $this->assertEquals(0.15, $this->calculator->getDiscountRate(199999.0));
    }

    /**
     * Uji nilai batas diskon 20% (>= 200.000).
     */
    public function testDiscountRate200kAndAbove(): void
    {
        $this->assertEquals(0.20, $this->calculator->getDiscountRate(200000.0));
        $this->assertEquals(0.20, $this->calculator->getDiscountRate(350000.0));
        $this->assertEquals(0.20, $this->calculator->getDiscountRate(1000000.0));
    }

    /**
     * Uji subtotal negatif pada getDiscountRate melempar exception.
     */
    public function testGetDiscountRateNegativeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->getDiscountRate(-1000.0);
    }

    /**
     * Uji perhitungan nominal diskon dalam rupiah.
     */
    public function testCalculateDiscountNominal(): void
    {
        $this->assertEquals(0.0, $this->calculator->calculateDiscount(40000.0));
        $this->assertEquals(5800.0, $this->calculator->calculateDiscount(58000.0));
        $this->assertEquals(15000.0, $this->calculator->calculateDiscount(100000.0));
        $this->assertEquals(40000.0, $this->calculator->calculateDiscount(200000.0));
    }

    /**
     * Uji Dasar Pengenaan Pajak (DPP = subtotal - diskon).
     */
    public function testCalculateTaxBase(): void
    {
        $subtotal = 58000.0;
        $discount = 5800.0;
        $taxBase  = $this->calculator->calculateTaxBase($subtotal, $discount);

        $this->assertEquals(52200.0, $taxBase);
    }

    /**
     * Uji perhitungan PPN 10% dari dasar pajak.
     */
    public function testCalculateTaxTenPercent(): void
    {
        $subtotal = 58000.0;
        $discount = 5800.0;
        $tax      = $this->calculator->calculateTax($subtotal, $discount);

        $this->assertEquals(5220.0, $tax);
    }

    /**
     * Uji perhitungan total bayar akhir (dasar_pajak + pajak).
     */
    public function testCalculateTotalBayar(): void
    {
        $subtotal = 58000.0;
        $discount = 5800.0;
        $tax      = 5220.0;
        $total    = $this->calculator->calculateTotal($subtotal, $discount, $tax);

        $this->assertEquals(57420.0, $total);
    }

    /**
     * UJI KASUS WAJIB MUK UKK:
     * Subtotal: 58.000
     * Diskon 10%: 5.800
     * Dasar Pajak: 52.200
     * Pajak 10%: 5.220
     * Total Bayar: 57.420
     */
    public function testMukUkkScenarioCase(): void
    {
        $items = [
            ['harga' => 25000, 'qty' => 2], // 50.000
            ['harga' => 5000,  'qty' => 1], // 5.000
            ['harga' => 3000,  'qty' => 1], // 3.000
        ];

        $result = $this->calculator->calculate($items);

        $this->assertEquals(58000.0, $result['subtotal'], 'Subtotal harus 58.000');
        $this->assertEquals(0.10, $result['discount_rate'], 'Rate diskon harus 10%');
        $this->assertEquals(10, $result['discount_percent'], 'Persen diskon harus 10');
        $this->assertEquals(5800.0, $result['discount'], 'Nominal diskon harus 5.800');
        $this->assertEquals(52200.0, $result['tax_base'], 'Dasar pengenaan pajak harus 52.200');
        $this->assertEquals(5220.0, $result['tax'], 'Pajak PPN harus 5.220');
        $this->assertEquals(57420.0, $result['total_bayar'], 'Total bayar akhir harus 57.420');
    }

    /**
     * Uji diskon melebihi subtotal melempar exception.
     */
    public function testTaxBaseDiscountExceedsSubtotalThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->calculateTaxBase(10000.0, 15000.0);
    }

    /**
     * Uji kalkulasi dengan diskon manual (custom discount) nominal.
     */
    public function testCalculateWithCustomDiscountNominal(): void
    {
        $items = [
            ['harga' => 20000, 'qty' => 2], // Subtotal 40.000
        ];

        // Diskon manual Rp 5.000
        $result = $this->calculator->calculate($items, 5000.0);

        $this->assertEquals(40000.0, $result['subtotal']);
        $this->assertEquals(5000.0, $result['discount']);
        $this->assertEquals(35000.0, $result['tax_base']); // 40.000 - 5.000
        $this->assertEquals(3500.0, $result['tax']);       // 10% dari 35.000
        $this->assertEquals(38500.0, $result['total_bayar']); // 35.000 + 3.500
    }

    /**
     * Uji kalkulasi dengan diskon custom 0 (tanpa diskon).
     */
    public function testCalculateWithCustomDiscountZero(): void
    {
        $items = [
            ['harga' => 60000, 'qty' => 1], // Subtotal 60.000 (normalnya dapat diskon 10%)
        ];

        // Paksa diskon 0 (override tier)
        $result = $this->calculator->calculate($items, 0.0);

        $this->assertEquals(60000.0, $result['subtotal']);
        $this->assertEquals(0.0, $result['discount']);
        $this->assertEquals(60000.0, $result['tax_base']);
        $this->assertEquals(6000.0, $result['tax']);
        $this->assertEquals(66000.0, $result['total_bayar']);
    }

    /**
     * Uji kalkulasi dengan diskon custom melebihi subtotal melempar exception.
     */
    public function testCalculateWithCustomDiscountExceedsSubtotalThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Diskon tidak boleh melebihi nilai subtotal.');

        $items = [
            ['harga' => 10000, 'qty' => 1],
        ];

        $this->calculator->calculate($items, 15000.0);
    }

    /**
     * Uji kalkulasi dengan diskon custom bernilai negatif melempar exception.
     */
    public function testCalculateWithCustomDiscountNegativeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Diskon tidak boleh bernilai negatif.');

        $items = [
            ['harga' => 10000, 'qty' => 1],
        ];

        $this->calculator->calculate($items, -500.0);
    }

    /**
     * Uji helper kalkulasi diskon berdasarkan persen.
     */
    public function testCalculateDiscountByPercent(): void
    {
        $subtotal = 50000.0;
        $this->assertEquals(5000.0, $this->calculator->calculateDiscountByPercent($subtotal, 10.0));
        $this->assertEquals(12500.0, $this->calculator->calculateDiscountByPercent($subtotal, 25.0));
        $this->assertEquals(0.0, $this->calculator->calculateDiscountByPercent($subtotal, 0.0));
        $this->assertEquals(50000.0, $this->calculator->calculateDiscountByPercent($subtotal, 100.0));
    }
}
