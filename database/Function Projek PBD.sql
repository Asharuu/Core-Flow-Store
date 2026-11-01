USE uts_pbd_026;

DELIMITER $$
CREATE FUNCTION fn_cek_stok(p_idbarang int)
RETURNS int
DETERMINISTIC
BEGIN
	DECLARE hasil int;
	SELECT stock
	INTO hasil
	FROM kartu_stok
	WHERE idbarang = p_idbarang
	ORDER BY idkartu_stok DESC
	LIMIT 1;
	RETURN IFNULL(hasil, 0);
END$$
DELIMITER ;

SELECT idbarang, nama, fn_cek_stok(idbarang) AS sisa_stok
FROM barang;

DELIMITER $$
CREATE FUNCTION fn_hitung_ppn(p_subtotal int)
RETURNS int
DETERMINISTIC
BEGIN
	DECLARE hasil int;
	SET hasil = ROUND(p_subtotal * 11 / 100);
	RETURN hasil;
END$$
DELIMITER ;

SELECT fn_hitung_ppn(100000) AS nilai_ppn;
