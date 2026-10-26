USE uts_pbd_026;

CREATE OR REPLACE VIEW v_satuan
AS SELECT idsatuan AS id, nama_satuan AS nama, status
FROM satuan;

CREATE OR REPLACE VIEW v_vendor AS
SELECT idvendor AS id, nama_vendor AS nama, badan_hukum, status
FROM vendor;

CREATE OR REPLACE VIEW v_role AS
SELECT idrole AS id, nama_role AS nama
FROM role;

CREATE OR REPLACE VIEW v_user AS
SELECT iduser AS id, username AS 'nama user', password, idrole AS 'id role'
FROM user;

CREATE OR REPLACE VIEW v_barang AS
SELECT idbarang AS id, jenis, nama, idsatuan 'id satuan', status, harga
FROM barang;

CREATE OR REPLACE VIEW v_margin_penjualan AS
SELECT idmargin_penjualan AS id, created_at AS 'Dibuat pada', persen, status, iduser 'id user', updated_at AS 'Diperbarui pada'
FROM margin_penjualan;

SELECT * FROM v_satuan;