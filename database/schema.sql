-- ==========================================================
-- Database Schema: RestoKita (POS Kasir Restoran)
-- Keperluan: Uji Kompetensi Keahlian (UKK) SMK - Pemrogram Junior
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `restokita` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `restokita`;

-- Matikan foreign key check sementara agar drop table bersih
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `detail_transaksi`;
DROP TABLE IF EXISTS `transaksi`;
DROP TABLE IF EXISTS `produk`;
DROP TABLE IF EXISTS `pengguna`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. TABEL PENGGUNA (Users & Roles)
CREATE TABLE `pengguna` (
    `id_pengguna` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL PRODUK (Menu Restoran)
CREATE TABLE `produk` (
    `id_produk` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_produk` VARCHAR(100) NOT NULL,
    `kategori` ENUM('Makanan', 'Minuman', 'Snack') NOT NULL,
    `harga` DECIMAL(10, 2) NOT NULL,
    `stok` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_kategori` (`kategori`),
    INDEX `idx_nama_produk` (`nama_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL TRANSAKSI (Header Transaksi Kasir)
CREATE TABLE `transaksi` (
    `id_transaksi` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_transaksi` VARCHAR(30) NOT NULL UNIQUE,
    `tanggal` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `total` DECIMAL(10, 2) NOT NULL COMMENT 'Subtotal sebelum diskon dan pajak',
    `diskon` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `pajak` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'PPN 10% dari (total - diskon)',
    `total_bayar` DECIMAL(10, 2) NOT NULL COMMENT 'Total akhir yang wajib dibayar',
    `id_pengguna` INT NOT NULL,
    INDEX `idx_tanggal` (`tanggal`),
    INDEX `idx_id_pengguna` (`id_pengguna`),
    CONSTRAINT `fk_transaksi_pengguna` 
        FOREIGN KEY (`id_pengguna`) 
        REFERENCES `pengguna` (`id_pengguna`) 
        ON UPDATE CASCADE 
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL DETAIL TRANSAKSI (Item Per Transaksi)
CREATE TABLE `detail_transaksi` (
    `id_detail` INT AUTO_INCREMENT PRIMARY KEY,
    `id_transaksi` INT NOT NULL,
    `id_produk` INT NOT NULL,
    `qty` INT NOT NULL,
    `harga_satuan` DECIMAL(10, 2) NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    INDEX `idx_detail_transaksi` (`id_transaksi`),
    INDEX `idx_detail_produk` (`id_produk`),
    CONSTRAINT `fk_detail_transaksi` 
        FOREIGN KEY (`id_transaksi`) 
        REFERENCES `transaksi` (`id_transaksi`) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE,
    CONSTRAINT `fk_detail_produk` 
        FOREIGN KEY (`id_produk`) 
        REFERENCES `produk` (`id_produk`) 
        ON UPDATE CASCADE 
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
