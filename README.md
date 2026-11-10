# RestoKita - POS Kasir Restoran (Uji Kompetensi Keahlian SMK)

Aplikasi web **Point of Sales (POS) Kasir Restoran** yang dirancang khusus untuk keperluan **Uji Kompetensi Keahlian (UKK) SMK Jurusan Pengembangan Perangkat Lunak dan Gim (PPLG)**, okupasi **Pemrogram Junior (Junior Coder)**.

Kode ditulis dengan standar **PHP Native 8+ Terstruktur**, bersih (clean code), mengikuti PSR-12, menggunakan **PDO Prepared Statements** (anti-SQL Injection), sanitasi anti-XSS, dan dilengkapi dengan **Unit Testing PHPUnit** untuk memastikan akurasi perhitungan MUK (Materi Uji Kompetensi).

---

## 1. Tech Stack
- **Backend**: PHP 8.2+ Native (Terstruktur, tanpa framework besar)
- **Database**: MySQL / MariaDB (PDO Prepared Statements + Database Transaction ACID)
- **Frontend**: HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, Vanilla JavaScript
- **Unit Testing**: PHPUnit 11 (Autoloading PSR-4 via Composer)
- **Lingkungan**: XAMPP / Laragon / PHP Built-in Server

---

## 2. Struktur Folder
```text
Kasir 2/
├── config/
│   └── database.php                # Pengaturan koneksi database MySQL
├── database/
│   ├── schema.sql                  # Struktur DDL tabel & foreign key
│   └── seed.sql                    # Data awal (Admin, Kasir, 12 Menu)
├── public/
│   ├── assets/
│   │   ├── css/style.css           # Styling khusus RestoKita (Dark & Orange)
│   │   └── js/kasir.js             # Interaksi keranjang belanja & hitung otomatis
│   ├── produk/
│   │   ├── index.php               # CRUD: Daftar menu & pencarian/filter
│   │   ├── tambah.php              # CRUD: Form tambah produk baru
│   │   ├── edit.php                # CRUD: Form edit data produk
│   │   └── hapus.php               # CRUD: Hapus produk & proteksi relasi
│   ├── index.php                   # Pengarah utama (redirect)
│   ├── login.php                   # Login multi-role (Admin & Kasir)
│   ├── logout.php                  # Mengakhiri sesi pengguna
│   ├── dashboard.php               # Ringkasan statistik produk & omzet
│   ├── kasir.php                   # Antarmuka utama Kasir POS 2 Kolom
│   ├── riwayat.php                 # Riwayat transaksi & filter tanggal
│   ├── detail_transaksi.php        # Rincian item per transaksi
│   └── cetak_struk.php             # Format cetak struk thermal kasir
├── src/
│   ├── Auth.php                    # Pengelolaan session, hash password, & middleware
│   ├── Database.php                # Koneksi PDO (Singleton pattern)
│   ├── Helper.php                  # Format Rupiah, Anti-XSS, flash message
│   ├── Product.php                 # Model data produk (CRUD)
│   ├── Transaction.php             # Model transaksi & pemotongan stok (ACID)
│   ├── TransactionCalculator.php   # Logika murni perhitungan diskon & PPN
│   └── Validator.php               # Validasi input server-side
├── templates/
│   ├── header.php                  # Template header & navigasi atas
│   ├── sidebar.php                 # Sidebar navigasi gelap (Dark Sidebar)
│   └── footer.php                  # Footer & pemuatan script JS
├── tests/
│   ├── HelperTest.php              # Unit test fungsi helper
│   ├── TransactionCalculatorTest.php # Unit test logika perhitungan MUK
│   └── ValidatorTest.php           # Unit test validasi form
├── composer.json                   # Konfigurasi dependensi & PSR-4 autoload
├── phpunit.xml                     # Konfigurasi pengujian PHPUnit
├── setup_db.php                    # Skrip instalasi database 1-klik
└── index.php                       # Pengarah root ke public/index.php
```

---

## 3. Akun Login untuk Pengujian
Aplikasi memiliki 2 role pengguna dengan password yang di-hash menggunakan algoritma `PASSWORD_BCRYPT`:

| Role | Username | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Akses penuh: Dashboard, POS Kasir, CRUD Menu Produk, Riwayat Transaksi |
| **Kasir** | `kasir` | `kasir123` | Akses operasional: Dashboard, POS Kasir, Lihat Menu, Riwayat Transaksi |

> **Catatan Pengujian Cepat**: Pada halaman login tersedia tombol bantu **"Admin"** dan **"Kasir"** untuk mengisi username dan password secara otomatis dalam 1 klik.

---

## 4. Cara Instalasi & Menjalankan Aplikasi

### Opsi A: Menjalankan di XAMPP / Laragon
1. Pastikan folder project berada di dalam direktori web server:
   - XAMPP: `C:\xampp\htdocs\Kasir 2` (atau folder `htdocs` tempat Anda menaruh project)
   - Laragon: `C:\laragon\www\Kasir 2`
2. Buka Control Panel XAMPP / Laragon, lalu **START** modul **Apache** dan **MySQL**.
3. **Impor Database** (Pilih salah satu cara berikut):
   - **Cara 1 (Otomatis / Paling Mudah)**: Buka browser dan akses URL:  
     `http://localhost/Kasir 2/setup_db.php`  
     Skrip akan membuat database `restokita` dan mengisikan data awal secara otomatis.
   - **Cara 2 (phpMyAdmin)**: Buka `http://localhost/phpmyadmin`, buat database bernama `restokita`, lalu import file `database/schema.sql` dilanjutkan dengan `database/seed.sql`.
4. Buka aplikasi di browser:
   ```text
   http://localhost/Kasir 2/
   ```

### Opsi B: Menjalankan dengan PHP Built-in Server
Jika tidak menggunakan Apache:
1. Pastikan MySQL sudah aktif di port `3306`.
2. Jalankan setup database:
   ```bash
   php setup_db.php
   ```
3. Jalankan web server bawaan PHP:
   ```bash
   php -S localhost:8000 -t public
   ```
4. Buka browser pada alamat `http://localhost:8000`.

---

## 5. Cara Menjalankan Unit Testing (PHPUnit)
Unit testing digunakan untuk memverifikasi logika matematika diskon bertingkat, PPN 10%, penanganan input negatif, dan validasi data.

Jalankan perintah berikut di terminal project:
```bash
.\vendor\bin\phpunit
```
atau via Composer:
```bash
composer test
```

### Hasil Pengujian:
```text
Runtime:       PHP 8.2.12
Configuration: phpunit.xml

..........................                                        26 / 26 (100%)

Time: 00:00.069, Memory: 8.00 MB

OK (26 tests, 60 assertions)
```
Semua 26 pengujian dinyatakan **100% PASSED**.

---

## 6. Aturan Perhitungan Bisnis (TransactionCalculator)
Seluruh logika kalkulasi diisolasi pada file `src/TransactionCalculator.php` tanpa dependensi ke database.

### Rumus Perhitungan:
1. **Subtotal**: Jumlah seluruh $(Harga \times Qty)$ tiap menu.
2. **Diskon Bertingkat**:
   - Subtotal $<$ Rp 50.000 $\rightarrow$ **0%**
   - Rp 50.000 s.d. Rp 99.999 $\rightarrow$ **10%**
   - Rp 100.000 s.d. Rp 199.999 $\rightarrow$ **15%**
   - $\ge$ Rp 200.000 $\rightarrow$ **20%**
3. **Dasar Pengenaan Pajak (DPP)**:
   $$\text{Dasar Pajak} = \text{Subtotal} - \text{Diskon}$$
4. **Pajak Restoran (PPN 10%)**:
   $$\text{Pajak} = \text{Dasar Pajak} \times 10\%$$
5. **Total Bayar Akhir**:
   $$\text{Total Bayar} = \text{Dasar Pajak} + \text{Pajak}$$

### Contoh Kasus Uji MUK:
- Pembelian:
  - 2x Nasi Goreng Spesial (@ Rp 25.000) = Rp 50.000
  - 1x Es Teh Manis (@ Rp 5.000) = Rp 5.000
  - 1x Item Tambahan (@ Rp 3.000) = Rp 3.000
- **Subtotal** = **Rp 58.000**
- Subtotal berada pada rentang Rp 50.000 s.d. Rp 99.999 $\rightarrow$ Diskon **10%**
- **Diskon** = $58.000 \times 10\% =$ **Rp 5.800**
- **Dasar Pajak** = $58.000 - 5.800 =$ **Rp 52.200**
- **PPN (10%)** = $52.200 \times 10\% =$ **Rp 5.220**
- **Total Bayar Akhir** = $52.200 + 5.220 =$ **Rp 57.420** *(Sesuai persis dengan MUK)*.
