-- ==========================================================
-- Database Seed Data: RestoKita (POS Kasir Restoran)
-- Akun Login:
-- 1. Admin -> username: admin, password: admin123
-- 2. Kasir -> username: kasir, password: kasir123
-- ==========================================================

USE `restokita`;

-- 1. SEED DATA PENGGUNA
INSERT INTO `pengguna` (`id_pengguna`, `nama`, `username`, `password`, `role`) VALUES
(1, 'Administrator Resto', 'admin', '$2y$10$aARSXMhRnrilASk/C2qmcuCZfdovCTR1b5I0jFrFy6Wla0IJwhNfy', 'admin'),
(2, 'Kasir RestoKita', 'kasir', '$2y$10$YeFPOURjPUXMBlhiRFWq5u7Pc/eNoMqbaJO8W/PSAOUWTCqqofsCa', 'kasir')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

-- 2. SEED DATA MENU PRODUK
INSERT INTO `produk` (`id_produk`, `nama_produk`, `kategori`, `harga`, `stok`) VALUES
(1, 'Nasi Goreng Spesial', 'Makanan', 25000.00, 50),
(2, 'Ayam Bakar Madu', 'Makanan', 28000.00, 35),
(3, 'Mie Goreng Jawa', 'Makanan', 22000.00, 40),
(4, 'Sate Ayam Madura (10 Tusuk)', 'Makanan', 26000.00, 25),
(5, 'Soto Ayam Lamongan', 'Makanan', 24000.00, 30),
(6, 'Es Teh Manis', 'Minuman', 5000.00, 100),
(7, 'Es Kopi Susu Gula Aren', 'Minuman', 18000.00, 60),
(8, 'Matcha Latte Ice', 'Minuman', 20000.00, 45),
(9, 'Jus Alpukat Keruk', 'Minuman', 16000.00, 35),
(10, 'Kentang Goreng Crispy', 'Snack', 15000.00, 40),
(11, 'Pisang Bakar Cokelat Keju', 'Snack', 14000.00, 30),
(12, 'Cireng Bumbu Rujak', 'Snack', 12000.00, 50)
ON DUPLICATE KEY UPDATE 
    `nama_produk` = VALUES(`nama_produk`),
    `kategori` = VALUES(`kategori`),
    `harga` = VALUES(`harga`),
    `stok` = VALUES(`stok`);

-- 3. SEED CONTOH TRANSAKSI AWAL (UNTUK DEMO DASHBOARD & RIWAYAT)
INSERT INTO `transaksi` (`id_transaksi`, `kode_transaksi`, `tanggal`, `total`, `diskon`, `pajak`, `total_bayar`, `id_pengguna`) VALUES
(1, 'TRX-20261109-0001', NOW(), 58000.00, 5800.00, 5220.00, 57420.00, 2)
ON DUPLICATE KEY UPDATE `kode_transaksi` = VALUES(`kode_transaksi`);

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `qty`, `harga_satuan`, `subtotal`) VALUES
(1, 1, 1, 2, 25000.00, 50000.00), -- 2x Nasi Goreng Spesial = 50.000
(2, 1, 6, 1, 5000.00, 5000.00),   -- 1x Es Teh Manis = 5.000
(3, 1, 12, 1, 3000.00, 3000.00)   -- Tambahan item kecil = 3.000 (total 58.000)
ON DUPLICATE KEY UPDATE `id_detail` = VALUES(`id_detail`);
