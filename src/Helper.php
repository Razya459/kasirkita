<?php

namespace RestoKita;

/**
 * Class Helper
 *
 * Kumpulan fungsi bantuan (utility functions) umum:
 * - Format mata uang Rupiah
 * - Sanitasi output anti-XSS
 * - Flash message antar request HTTP
 * - Format tanggal bahasa Indonesia
 *
 * @package RestoKita
 */
class Helper
{
    /**
     * Memformat angka desimal/integer menjadi format Rupiah Indonesia.
     * Contoh: 25000 -> "Rp 25.000"
     *
     * @param float|int $angka Nilai angka yang akan diformat
     * @param bool $withDecimals Tampilkan sen desimal (,00) jika true
     * @return string Format Rupiah
     */
    public static function formatRupiah(float|int $angka, bool $withDecimals = false): string
    {
        $decimals = $withDecimals ? 2 : 0;
        return 'Rp ' . number_format((float) $angka, $decimals, ',', '.');
    }

    /**
     * Membersihkan teks string untuk mencegah serangan XSS (Cross-Site Scripting).
     *
     * @param string|null $string Teks mentah
     * @return string Teks yang sudah di-escape
     */
    public static function e(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Memulai session jika belum aktif.
     *
     * @return void
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Menyimpan flash message ke dalam session.
     *
     * @param string $type Tipe pesan ('success', 'danger', 'warning', 'info')
     * @param string $message Isi pesan notifikasi
     * @return void
     */
    public static function setFlash(string $type, string $message): void
    {
        self::startSession();
        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    /**
     * Mengambil dan menghapus flash message dari session (one-time read).
     *
     * @return array{type: string, message: string}|null Data flash message atau null
     */
    public static function getFlash(): ?array
    {
        self::startSession();
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    /**
     * Mengalihkan halaman (redirect) ke URL tujuan lalu menghentikan skrip.
     *
     * @param string $url URL atau path tujuan redirect
     * @return never
     */
    public static function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Memformat tanggal MySQL DATETIME menjadi format Indonesia yang ramah dibaca.
     * Contoh: "2026-11-09 14:30:00" -> "09 Nov 2026, 14:30"
     *
     * @param string $datetime Tanggal/waktu dalam format Y-m-d H:i:s
     * @return string Format tanggal lokal
     */
    public static function formatTanggal(string $datetime): string
    {
        $bulanIndo = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return $datetime;
        }

        $tgl   = date('d', $timestamp);
        $bln   = (int) date('m', $timestamp);
        $thn   = date('Y', $timestamp);
        $waktu = date('H:i', $timestamp);

        return "{$tgl} " . ($bulanIndo[$bln] ?? '') . " {$thn}, {$waktu}";
    }
}
