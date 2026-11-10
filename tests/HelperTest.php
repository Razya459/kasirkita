<?php

namespace RestoKita\Tests;

use PHPUnit\Framework\TestCase;
use RestoKita\Helper;

/**
 * Class HelperTest
 *
 * Menguji fungsi pembantu (Helper) seperti format mata uang rupiah,
 * sanitasi string (anti-XSS), dan format tanggal.
 */
class HelperTest extends TestCase
{
    /**
     * Uji pemformatan nominal angka ke format mata uang Rupiah.
     */
    public function testFormatRupiah(): void
    {
        $this->assertEquals('Rp 25.000', Helper::formatRupiah(25000));
        $this->assertEquals('Rp 0', Helper::formatRupiah(0));
        $this->assertEquals('Rp 57.420', Helper::formatRupiah(57420));
        $this->assertEquals('Rp 57.420,50', Helper::formatRupiah(57420.50, true));
    }

    /**
     * Uji sanitasi teks terhadap karakter berbahaya XSS.
     */
    public function testSanitizeAntiXss(): void
    {
        $input = '<script>alert("XSS")</script>';
        $cleaned = Helper::e($input);

        $this->assertStringNotContainsString('<script>', $cleaned);
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $cleaned);
        $this->assertEquals('', Helper::e(null));
    }

    /**
     * Uji format tanggal ke format bahasa Indonesia.
     */
    public function testFormatTanggal(): void
    {
        $datetime = '2026-11-09 10:30:00';
        $formatted = Helper::formatTanggal($datetime);

        $this->assertStringContainsString('09 Nov 2026', $formatted);
        $this->assertStringContainsString('10:30', $formatted);
    }
}
