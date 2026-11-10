<?php

namespace RestoKita;

use Exception;
use PDO;
use RuntimeException;

/**
 * Class Transaction
 *
 * Mengelola alur transaksi kasir, penyimpanan data transaksi berelasi,
 * pemotongan stok otomatis, serta laporan/riwayat transaksi.
 * Seluruh proses checkout dijamin menggunakan Database Transaction (ACID).
 *
 * @package RestoKita
 */
class Transaction
{
    private PDO $db;
    private TransactionCalculator $calculator;

    /**
     * Inisialisasi model Transaction.
     *
     * @param PDO|null $db Objek PDO
     * @param TransactionCalculator|null $calculator Objek kalkulator perhitungan
     */
    public function __construct(?PDO $db = null, ?TransactionCalculator $calculator = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->calculator = $calculator ?? new TransactionCalculator();
    }

    /**
     * Memproses transaksi baru dari kasir:
     * 1. Memulai database transaction (beginTransaction)
     * 2. Mengunci baris produk (SELECT FOR UPDATE) dan memeriksa kecukupan stok
     * 3. Menghitung subtotal, diskon bertingkat, PPN 10%, dan total bayar
     * 4. Menyimpan data transaksi ke tabel 'transaksi'
     * 5. Menyimpan rincian item ke tabel 'detail_transaksi'
     * 6. Mengurangi stok produk di tabel 'produk'
     * 7. Menyelesaikan transaksi (commit) atau membatalkan (rollBack) jika terjadi kesalahan
     *
     * @param int $idPengguna ID kasir yang memproses
     * @param array<int, array{id_produk: int, qty: int}> $rawItems Daftar item belanja
     * @param float|null $customDiscount Nilai nominal diskon manual (opsional, jika diketik sendiri oleh kasir)
     * @return array<string, mixed> Data transaksi yang berhasil disimpan
     * @throws RuntimeException Jika stok tidak mencukupi atau proses database gagal
     */
    public function process(int $idPengguna, array $rawItems, ?float $customDiscount = null): array
    {
        if (empty($rawItems)) {
            throw new RuntimeException('Keranjang belanja kosong.');
        }

        $this->db->beginTransaction();

        try {
            $preparedItems = [];
            $calcItems     = [];

            // 1. Verifikasi ketersediaan dan stok tiap produk dengan row locking
            $stmtSelect = $this->db->prepare(
                'SELECT id_produk, nama_produk, harga, stok 
                 FROM produk 
                 WHERE id_produk = :id_produk 
                 FOR UPDATE'
            );

            foreach ($rawItems as $item) {
                $idProduk = (int) ($item['id_produk'] ?? 0);
                $qty      = (int) ($item['qty'] ?? 0);

                if ($idProduk <= 0 || $qty <= 0) {
                    throw new RuntimeException('Data item pesanan tidak valid.');
                }

                $stmtSelect->execute([':id_produk' => $idProduk]);
                $product = $stmtSelect->fetch();

                if (!$product) {
                    throw new RuntimeException("Produk dengan ID {$idProduk} tidak ditemukan.");
                }

                if ((int) $product['stok'] < $qty) {
                    throw new RuntimeException(
                        "Stok produk '{$product['nama_produk']}' tidak mencukupi! Sisa stok: {$product['stok']}, diminta: {$qty}."
                    );
                }

                $harga = (float) $product['harga'];
                $itemSubtotal = round($harga * $qty, 2);

                $preparedItems[] = [
                    'id_produk'    => $idProduk,
                    'nama_produk'  => $product['nama_produk'],
                    'qty'          => $qty,
                    'harga_satuan' => $harga,
                    'subtotal'     => $itemSubtotal,
                ];

                $calcItems[] = [
                    'harga' => $harga,
                    'qty'   => $qty,
                ];
            }

            // 2. Hitung subtotal, diskon (otomatis atau custom), pajak, dan total bayar dengan logika TransactionCalculator
            $calculation = $this->calculator->calculate($calcItems, $customDiscount);

            // 3. Buat nomor kode transaksi unik: TRX-YYYYMMDD-XXXX
            $kodeTransaksi = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            // 4. Simpan ke tabel 'transaksi' (header)
            $stmtInsertTrx = $this->db->prepare(
                'INSERT INTO transaksi (kode_transaksi, tanggal, total, diskon, pajak, total_bayar, id_pengguna) 
                 VALUES (:kode, NOW(), :total, :diskon, :pajak, :total_bayar, :id_pengguna)'
            );
            $stmtInsertTrx->execute([
                ':kode'        => $kodeTransaksi,
                ':total'       => $calculation['subtotal'],
                ':diskon'      => $calculation['discount'],
                ':pajak'       => $calculation['tax'],
                ':total_bayar' => $calculation['total_bayar'],
                ':id_pengguna' => $idPengguna,
            ]);

            $idTransaksi = (int) $this->db->lastInsertId();

            // 5. Simpan detail item dan kurangi stok produk
            $stmtInsertDetail = $this->db->prepare(
                'INSERT INTO detail_transaksi (id_transaksi, id_produk, qty, harga_satuan, subtotal) 
                 VALUES (:id_transaksi, :id_produk, :qty, :harga_satuan, :subtotal)'
            );

            $stmtUpdateStok = $this->db->prepare(
                'UPDATE produk SET stok = stok - :qty WHERE id_produk = :id_produk'
            );

            foreach ($preparedItems as $detail) {
                // Simpan rincian item transaksi
                $stmtInsertDetail->execute([
                    ':id_transaksi' => $idTransaksi,
                    ':id_produk'    => $detail['id_produk'],
                    ':qty'          => $detail['qty'],
                    ':harga_satuan' => $detail['harga_satuan'],
                    ':subtotal'     => $detail['subtotal'],
                ]);

                // Kurangi stok di tabel produk
                $stmtUpdateStok->execute([
                    ':qty'       => $detail['qty'],
                    ':id_produk' => $detail['id_produk'],
                ]);
            }

            // 6. Selesaikan transaksi jika semua tahap berhasil
            $this->db->commit();

            return [
                'id_transaksi'   => $idTransaksi,
                'kode_transaksi' => $kodeTransaksi,
                'subtotal'       => $calculation['subtotal'],
                'diskon'         => $calculation['discount'],
                'pajak'          => $calculation['tax'],
                'total_bayar'    => $calculation['total_bayar'],
                'items'          => $preparedItems,
            ];
        } catch (Exception $e) {
            // Batalkan seluruh perubahan jika terjadi error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Mengambil daftar transaksi dengan opsi filter tanggal dan pencarian kode.
     *
     * @param string|null $startDate Tanggal awal (format Y-m-d)
     * @param string|null $endDate Tanggal akhir (format Y-m-d)
     * @param string|null $search Pencarian kode transaksi atau nama kasir
     * @return array<int, array<string, mixed>>
     */
    public function getAll(?string $startDate = null, ?string $endDate = null, ?string $search = null): array
    {
        $sql = 'SELECT t.id_transaksi, t.kode_transaksi, t.tanggal, t.total, t.diskon, t.pajak, t.total_bayar,
                       p.nama AS nama_kasir, p.username AS username_kasir
                FROM transaksi t
                JOIN pengguna p ON t.id_pengguna = p.id_pengguna
                WHERE 1=1';
        $params = [];

        if (!empty($startDate)) {
            $sql .= ' AND DATE(t.tanggal) >= :start_date';
            $params[':start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= ' AND DATE(t.tanggal) <= :end_date';
            $params[':end_date'] = $endDate;
        }

        if (!empty($search)) {
            $sql .= ' AND (t.kode_transaksi LIKE :search OR p.nama LIKE :search)';
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= ' ORDER BY t.tanggal DESC, t.id_transaksi DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Mengambil satu data transaksi berdasarkan ID.
     *
     * @param int $idTransaksi ID transaksi
     * @return array<string, mixed>|null Data transaksi
     */
    public function getById(int $idTransaksi): ?array
    {
        $sql = 'SELECT t.id_transaksi, t.kode_transaksi, t.tanggal, t.total, t.diskon, t.pajak, t.total_bayar,
                       p.nama AS nama_kasir, p.username AS username_kasir
                FROM transaksi t
                JOIN pengguna p ON t.id_pengguna = p.id_pengguna
                WHERE t.id_transaksi = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idTransaksi]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Mengambil daftar rincian item (detail) dari suatu transaksi.
     *
     * @param int $idTransaksi ID transaksi
     * @return array<int, array<string, mixed>>
     */
    public function getDetails(int $idTransaksi): array
    {
        $sql = 'SELECT dt.id_detail, dt.id_produk, dt.qty, dt.harga_satuan, dt.subtotal,
                       pr.nama_produk, pr.kategori
                FROM detail_transaksi dt
                JOIN produk pr ON dt.id_produk = pr.id_produk
                WHERE dt.id_transaksi = :id
                ORDER BY dt.id_detail ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idTransaksi]);

        return $stmt->fetchAll();
    }

    /**
     * Mengambil ringkasan data transaksi hari ini untuk tampilan Dashboard:
     * - Total transaksi hari ini (count)
     * - Total pendapatan hari ini (sum total_bayar)
     *
     * @return array{total_transaksi: int, total_pendapatan: float}
     */
    public function getTodaySummary(): array
    {
        $sql = 'SELECT COUNT(*) AS total_transaksi, COALESCE(SUM(total_bayar), 0) AS total_pendapatan 
                FROM transaksi 
                WHERE DATE(tanggal) = CURDATE()';

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();

        return [
            'total_transaksi'  => (int) ($row['total_transaksi'] ?? 0),
            'total_pendapatan' => (float) ($row['total_pendapatan'] ?? 0.0),
        ];
    }

    /**
     * Mengambil daftar beberapa transaksi terbaru untuk tabel ringkasan dashboard.
     *
     * @param int $limit Jumlah data yang diambil
     * @return array<int, array<string, mixed>>
     */
    public function getRecent(int $limit = 5): array
    {
        $sql = 'SELECT t.id_transaksi, t.kode_transaksi, t.tanggal, t.total_bayar, p.nama AS nama_kasir
                FROM transaksi t
                JOIN pengguna p ON t.id_pengguna = p.id_pengguna
                ORDER BY t.tanggal DESC, t.id_transaksi DESC
                LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
