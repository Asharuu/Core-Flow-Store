# PEMROGRAMAN BASIS DATA PRAKTIKUM 2026/2027

NAMA  : Ahmad Shafy Al Rahman<br>
NIM   : 434251026<br>
Kelas : TI - B2<br>

# Core-Flow Store

Sistem Informasi Manajemen Ritel & Inventori Toko berbasis PHP Native dan MariaDB. Dikembangkan sebagai bagian dari praktikum mata kuliah Pemrograman Basis Data (PBD).

---

## 📦 Fitur Utama

- **Ringkasan Operasional**: Dashboard metrik stok barang, status antrean pemesanan, dan transaksi kasir.
- **Kasir Penjualan (POS)**: Layar transaksi kasir dengan kalkulasi dinamis tarif margin keuntungan dan PPN 11%.
- **Pengadaan Barang (Purchase Order)**: Penerbitan surat pesanan ke vendor mitra.
- **Penerimaan Gudang**: Verifikasi kedatangan barang dari vendor dan sinkronisasi stok fisik.
- **Buku Mutasi Kartu Stok**: Rekap audit mutasi barang masuk (*inflow*) dan keluar (*outflow*).
- **Master Data**: Pengelolaan data katalog produk, satuan, mitra vendor, dan kebijakan persentase margin keuntungan.
- **Simulasi Multi-Peran**: Beralih peran pengujian secara instan (Admin, Staff Pengadaan, Staff Gudang, Kasir, Supervisor).

---

## 🛠️ Struktur Direktori

```text
Core-Flow-Store/
├── config/             # Konfigurasi koneksi PDO MariaDB
├── database/           # Berkas DDL, DML, View, dan Diagram ERD
├── includes/           # Komponen layout (Header, Sidebar, Footer)
├── index.php           # Dashboard utama
├── pos.php             # Halaman kasir penjualan
├── pengadaan.php       # Modul purchase order ke vendor
├── penerimaan.php      # Modul verifikasi barang gudang
├── kartu-stok.php      # Audit buku mutasi stok
├── produk.php          # Manajemen data produk (mengonsumsi VIEW v_barang & v_satuan)
├── vendor.php          # Manajemen data vendor (mengonsumsi VIEW v_vendor)
├── margin-penjualan.php# Pengaturan margin keuntungan (mengonsumsi VIEW v_margin_penjualan)
├── login.php           # Autentikasi pengguna
├── tokens.css          # Desain sistem & variabel tema (Carnival-01)
└── styles.css          # Gaya visual antarmuka
```

---

## 🚀 Panduan Menjalankan di Lokal (Laragon / XAMPP)

1. Pastikan server Apache dan MySQL/MariaDB sudah aktif di Laragon atau XAMPP.
2. Buat database baru di MariaDB dengan nama:
   ```sql
   CREATE DATABASE uts_pbd_026;
   ```
3. Import berkas SQL yang berada di dalam folder `database/`:
   - `proyek_pbd_uts.sql` (Struktur tabel DDL dan data awal DML)
   - `DDL2 Projek PBD.sql` (Kumpulan Simple View tabel master)
4. Pastikan kredensial di `config/database.php` sudah sesuai dengan pengaturan server lokal Anda.
5. Buka peramban dan akses:
   ```text
   http://localhost/Core-Flow-Store
   ```
6. Masuk menggunakan salah satu akun uji coba (misal: `admin_utama` / `admin123`) atau gunakan tombol chip akun di halaman login.
