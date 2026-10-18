-- =====================================================================
-- PROYEK PEMROGRAMAN BASIS DATA (PRAKTIKUM) - LAPORAN UTS
-- Mahasiswa : Ahmad Shafy Al Rahman
-- NIM       : 434251026
-- Kelas     : TI - B2 / D4 Teknik Informatika
-- =====================================================================

-- 1. PEMBUATAN DATABASE
CREATE DATABASE IF NOT EXISTS db_pos_pbd;
USE db_pos_pbd;

-- =====================================================================
-- BAGIAN 1: DATA DEFINITION LANGUAGE (DDL)
-- Urutan eksekusi disesuaikan dengan dependensi Foreign Key
-- =====================================================================

-- 1. Tabel role (Master Role / Hak Akses)
CREATE TABLE IF NOT EXISTS role (
    idrole INT AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(100) NOT NULL
);

-- 2. Tabel satuan (Master Satuan Barang)
CREATE TABLE IF NOT EXISTS satuan (
    idsatuan INT AUTO_INCREMENT PRIMARY KEY,
    nama_satuan VARCHAR(45) NOT NULL,
    status TINYINT DEFAULT 1
);

-- 3. Tabel vendor (Master Supplier / Vendor)
CREATE TABLE IF NOT EXISTS vendor (
    idvendor INT AUTO_INCREMENT PRIMARY KEY,
    nama_vendor VARCHAR(100) NOT NULL,
    badan_hukum CHAR(1) NOT NULL,
    status CHAR(1) DEFAULT 'A'
);

-- 4. Tabel user (Master User Sistem)
CREATE TABLE IF NOT EXISTS user (
    iduser INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(45) NOT NULL,
    password VARCHAR(100) NOT NULL,
    idrole INT NOT NULL,
    CONSTRAINT fk_user_role FOREIGN KEY (idrole) REFERENCES role (idrole)
);

-- 5. Tabel barang (Master Data Barang)
CREATE TABLE IF NOT EXISTS barang (
    idbarang INT AUTO_INCREMENT PRIMARY KEY,
    jenis CHAR(1) NOT NULL,
    nama VARCHAR(45) NOT NULL,
    idsatuan INT NOT NULL,
    status TINYINT DEFAULT 1,
    harga INT NOT NULL,
    CONSTRAINT fk_barang_satuan FOREIGN KEY (idsatuan) REFERENCES satuan (idsatuan)
);

-- 6. Tabel margin_penjualan (Master Margin / Persentase Laba)
CREATE TABLE IF NOT EXISTS margin_penjualan (
    idmargin_penjualan INT AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    persen DOUBLE NOT NULL,
    status TINYINT DEFAULT 1,
    iduser INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_margin_user FOREIGN KEY (iduser) REFERENCES user (iduser)
);

-- 7. Tabel kartu_stok (Log Mutasi Stok Barang)
CREATE TABLE IF NOT EXISTS kartu_stok (
    idkartu_stok BIGINT AUTO_INCREMENT PRIMARY KEY,
    jenis_transaksi CHAR(1) NOT NULL,
    masuk INT DEFAULT 0,
    keluar INT DEFAULT 0,
    stock INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    idtransaksi INT NOT NULL,
    idbarang INT NOT NULL,
    CONSTRAINT fk_kartu_stok_barang FOREIGN KEY (idbarang) REFERENCES barang (idbarang)
);

-- 8. Tabel pengadaan (Header Pengadaan Barang ke Vendor)
CREATE TABLE IF NOT EXISTS pengadaan (
    idpengadaan BIGINT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_iduser INT NOT NULL,
    status CHAR(1) DEFAULT '0',
    vendor_idvendor INT NOT NULL,
    subtotal_nilai INT DEFAULT 0,
    ppn INT DEFAULT 0,
    total_nilai INT DEFAULT 0,
    CONSTRAINT fk_pengadaan_user FOREIGN KEY (user_iduser) REFERENCES user (iduser),
    CONSTRAINT fk_pengadaan_vendor FOREIGN KEY (vendor_idvendor) REFERENCES vendor (idvendor)
);

-- 9. Tabel detail_pengadaan (Detail Item Barang yang Dipesan)
CREATE TABLE IF NOT EXISTS detail_pengadaan (
    iddetail_pengadaan BIGINT AUTO_INCREMENT PRIMARY KEY,
    harga_satuan INT NOT NULL,
    jumlah INT NOT NULL,
    sub_total INT NOT NULL,
    idbarang INT NOT NULL,
    idpengadaan BIGINT NOT NULL,
    CONSTRAINT fk_detpengadaan_barang FOREIGN KEY (idbarang) REFERENCES barang (idbarang),
    CONSTRAINT fk_detpengadaan_pengadaan FOREIGN KEY (idpengadaan) REFERENCES pengadaan (idpengadaan)
);

-- 10. Tabel penerimaan (Header Penerimaan Barang dari Vendor)
CREATE TABLE IF NOT EXISTS penerimaan (
    idpenerimaan BIGINT AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status CHAR(1) DEFAULT '0',
    idpengadaan BIGINT NOT NULL,
    iduser INT NOT NULL,
    CONSTRAINT fk_penerimaan_pengadaan FOREIGN KEY (idpengadaan) REFERENCES pengadaan (idpengadaan),
    CONSTRAINT fk_penerimaan_user FOREIGN KEY (iduser) REFERENCES user (iduser)
);

-- 11. Tabel detail_penerimaan (Detail Item Barang yang Diterima)
CREATE TABLE IF NOT EXISTS detail_penerimaan (
    iddetail_penerimaan BIGINT AUTO_INCREMENT PRIMARY KEY,
    idpenerimaan BIGINT NOT NULL,
    barang_idbarang INT NOT NULL,
    jumlah_terima INT NOT NULL,
    harga_satuan_terima INT NOT NULL,
    sub_total_terima INT NOT NULL,
    CONSTRAINT fk_detterima_penerimaan FOREIGN KEY (idpenerimaan) REFERENCES penerimaan (idpenerimaan),
    CONSTRAINT fk_detterima_barang FOREIGN KEY (barang_idbarang) REFERENCES barang (idbarang)
);

-- 12. Tabel retur (Header Retur Barang Bermasalah ke Vendor)
CREATE TABLE IF NOT EXISTS retur (
    idretur BIGINT AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    idpenerimaan BIGINT NOT NULL,
    idpengadaan BIGINT NOT NULL,
    iduser INT NOT NULL,
    CONSTRAINT fk_retur_penerimaan FOREIGN KEY (idpenerimaan) REFERENCES penerimaan (idpenerimaan),
    CONSTRAINT fk_retur_pengadaan FOREIGN KEY (idpengadaan) REFERENCES pengadaan (idpengadaan),
    CONSTRAINT fk_retur_user FOREIGN KEY (iduser) REFERENCES user (iduser)
);

-- 13. Tabel detail_retur (Detail Item Barang yang Diretur)
CREATE TABLE IF NOT EXISTS detail_retur (
    iddetail_retur INT AUTO_INCREMENT PRIMARY KEY,
    jumlah INT NOT NULL,
    alasan VARCHAR(200) NOT NULL,
    idretur BIGINT NOT NULL,
    iddetail_penerimaan BIGINT NOT NULL,
    CONSTRAINT fk_detretur_retur FOREIGN KEY (idretur) REFERENCES retur (idretur),
    CONSTRAINT fk_detretur_detterima FOREIGN KEY (iddetail_penerimaan) REFERENCES detail_penerimaan (iddetail_penerimaan)
);

-- 14. Tabel penjualan (Header Transaksi Penjualan / Kasir)
CREATE TABLE IF NOT EXISTS penjualan (
    idpenjualan INT AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subtotal_nilai INT DEFAULT 0,
    ppn INT DEFAULT 0,
    total_nilai INT DEFAULT 0,
    iduser INT NOT NULL,
    idmargin_penjualan INT NOT NULL,
    CONSTRAINT fk_penjualan_user FOREIGN KEY (iduser) REFERENCES user (iduser),
    CONSTRAINT fk_penjualan_margin FOREIGN KEY (idmargin_penjualan) REFERENCES margin_penjualan (idmargin_penjualan)
);

-- 15. Tabel detail_penjualan (Detail Item Barang yang Dijual)
CREATE TABLE IF NOT EXISTS detail_penjualan (
    iddetail_penjualan BIGINT AUTO_INCREMENT PRIMARY KEY,
    harga_satuan INT NOT NULL,
    jumlah INT NOT NULL,
    subtotal INT NOT NULL,
    penjualan_idpenjualan INT NOT NULL,
    idbarang INT NOT NULL,
    CONSTRAINT fk_detjual_penjualan FOREIGN KEY (penjualan_idpenjualan) REFERENCES penjualan (idpenjualan),
    CONSTRAINT fk_detjual_barang FOREIGN KEY (idbarang) REFERENCES barang (idbarang)
);


-- =====================================================================
-- BAGIAN 2: DATA MANIPULATION LANGUAGE (DML)
-- Mengisi data awal (Dummy Data) untuk 6 Entitas Master (Non-Transaksi)
-- =====================================================================

-- 1. DML Tabel role
INSERT INTO role (idrole, nama_role) VALUES
(1, 'Administrator'),
(2, 'Manajer Pengadaan'),
(3, 'Staff Gudang'),
(4, 'Kasir');

-- 2. DML Tabel user
INSERT INTO user (iduser, username, password, idrole) VALUES
(1, 'admin', MD5('admin123'), 1),
(2, 'pengadaan01', MD5('pengadaan123'), 2),
(3, 'gudang01', MD5('gudang123'), 3),
(4, 'kasir01', MD5('kasir123'), 4);

-- 3. DML Tabel satuan
INSERT INTO satuan (idsatuan, nama_satuan, status) VALUES
(1, 'Pcs', 1),
(2, 'Dus', 1),
(3, 'Botol', 1),
(4, 'Pack', 1),
(5, 'Kg', 1);

-- 4. DML Tabel vendor
-- Keterangan badan_hukum: P = PT, C = CV, U = UD
-- Keterangan status: A = Aktif, N = Non-aktif
INSERT INTO vendor (idvendor, nama_vendor, badan_hukum, status) VALUES
(1, 'Indofood Sukses Makmur', 'P', 'A'),
(2, 'Mayora Indah', 'P', 'A'),
(3, 'Sumber Berkah Jaya', 'C', 'A'),
(4, 'Toko Grosir Mandiri', 'U', 'A');

-- 5. DML Tabel barang
-- Keterangan jenis: M = Makanan/Minuman, N = Non-makanan
INSERT INTO barang (idbarang, jenis, nama, idsatuan, status, harga) VALUES
(1, 'M', 'Indomie Goreng Spesial', 1, 1, 3100),
(2, 'M', 'Teh Pucuk Harum 350ml', 3, 1, 3500),
(3, 'M', 'Biskuit Roma Kelapa 300g', 4, 1, 9500),
(4, 'N', 'Sabun Lifebuoy Total 10 85g', 1, 1, 4000),
(5, 'N', 'Shampoo Pantene 160ml', 3, 1, 23000),
(6, 'M', 'Beras Ramos Super 5kg', 5, 1, 68000);

-- 6. DML Tabel margin_penjualan
INSERT INTO margin_penjualan (idmargin_penjualan, persen, status, iduser) VALUES
(1, 10.0, 1, 1),
(2, 12.5, 1, 1),
(3, 15.0, 1, 1);


-- =====================================================================
-- QUERY VERIFIKASI UNTUK SCREENSHOT LAPORAN
-- =====================================================================
-- Cek struktur tabel (DDL):
-- DESCRIBE role;
-- DESCRIBE user;
-- DESCRIBE satuan;
-- DESCRIBE vendor;
-- DESCRIBE barang;
-- DESCRIBE margin_penjualan;
-- DESCRIBE kartu_stok;
-- DESCRIBE pengadaan;
-- DESCRIBE detail_pengadaan;
-- DESCRIBE penerimaan;
-- DESCRIBE detail_penerimaan;
-- DESCRIBE retur;
-- DESCRIBE detail_retur;
-- DESCRIBE penjualan;
-- DESCRIBE detail_penjualan;

-- Cek data master (DML):
-- SELECT * FROM role;
-- SELECT * FROM user;
-- SELECT * FROM satuan;
-- SELECT * FROM vendor;
-- SELECT * FROM barang;
-- SELECT * FROM margin_penjualan;
