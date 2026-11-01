USE uts_pbd_026;

DELIMITER $$
CREATE TRIGGER trg_kartu_stok_penerimaan
AFTER INSERT ON detail_penerimaan
FOR EACH ROW
BEGIN
	DECLARE no_urut INT;
	DECLARE stok_lama INT;
	DECLARE stok_baru INT;

	SELECT IFNULL(MAX(idkartu_stok), 0) + 1 INTO no_urut FROM kartu_stok;
	SET stok_lama = fn_cek_stok(NEW.barang_idbarang);
	SET stok_baru = stok_lama + NEW.jumlah_terima;

	INSERT INTO kartu_stok (idkartu_stok, jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, idbarang)
	VALUES (no_urut, 'M', NEW.jumlah_terima, 0, stok_baru, NOW(), NEW.idpenerimaan, NEW.barang_idbarang);
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_kartu_stok_penjualan
AFTER INSERT ON detail_penjualan
FOR EACH ROW
BEGIN
	DECLARE no_urut INT;
	DECLARE stok_lama INT;
	DECLARE stok_baru INT;

	SELECT IFNULL(MAX(idkartu_stok), 0) + 1 INTO no_urut FROM kartu_stok;
	SET stok_lama = fn_cek_stok(NEW.idbarang);
	SET stok_baru = stok_lama - NEW.jumlah;

	INSERT INTO kartu_stok (idkartu_stok, jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, idbarang)
	VALUES (no_urut, 'K', 0, NEW.jumlah, stok_baru, NOW(), NEW.penjualan_idpenjualan, NEW.idbarang);
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_kartu_stok_retur
AFTER INSERT ON detail_retur
FOR EACH ROW
BEGIN
	DECLARE no_urut INT;
	DECLARE id_brg INT;
	DECLARE stok_lama INT;
	DECLARE stok_baru INT;

	SELECT IFNULL(MAX(idkartu_stok), 0) + 1 INTO no_urut FROM kartu_stok;
	SELECT barang_idbarang INTO id_brg FROM detail_penerimaan WHERE iddetail_penerimaan = NEW.iddetail_penerimaan;
	SET stok_lama = fn_cek_stok(id_brg);
	SET stok_baru = stok_lama - NEW.jumlah;

	INSERT INTO kartu_stok (idkartu_stok, jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, idbarang)
	VALUES (no_urut, 'R', 0, NEW.jumlah, stok_baru, NOW(), NEW.idretur, id_brg);
END$$
DELIMITER ;
