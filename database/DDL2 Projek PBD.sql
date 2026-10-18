USE uts_pbd_026;

-- 1. View tabel satuan
CREATE OR REPLACE VIEW v_satuan AS
SELECT idsatuan, nama_satuan, status
FROM satuan;

-- 2. View tabel vendor
CREATE OR REPLACE VIEW v_vendor AS
SELECT idvendor, nama_vendor, badan_hukum, status
FROM vendor;

-- 3. View tabel role
CREATE OR REPLACE VIEW v_role AS
SELECT idrole, nama_role
FROM role;

-- 4. View tabel user
CREATE OR REPLACE VIEW v_user AS
SELECT iduser, username, password, idrole
FROM user;

-- 5. View tabel barang
CREATE OR REPLACE VIEW v_barang AS
SELECT idbarang, jenis, nama, idsatuan, status, harga
FROM barang;

-- 6. View tabel margin_penjualan
CREATE OR REPLACE VIEW v_margin_penjualan AS
SELECT idmargin_penjualan, created_at, persen, status, iduser, updated_at
FROM margin_penjualan;
