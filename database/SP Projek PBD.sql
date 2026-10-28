USE uts_pbd_026;

-- 1. Stored Procedure menampilkan pengadaan berdasarkan status
DELIMITER $$
CREATE PROCEDURE sp_pengadaan_status(p_status CHAR(1))
BEGIN
	SELECT
		p.idpengadaan,
		p.timestamp,
		p.status,
		p.subtotal_nilai,
		p.ppn,
		p.total_nilai,
		v.nama_vendor,
		u.username,
		d.jumlah,
		d.harga_satuan,
		b.nama AS nama_barang,
		s.nama_satuan
	FROM pengadaan p
	LEFT JOIN vendor v ON p.vendor_idvendor = v.idvendor
	LEFT JOIN user u ON p.user_iduser = u.iduser
	LEFT JOIN detail_pengadaan d ON p.idpengadaan = d.idpengadaan
	LEFT JOIN barang b ON d.idbarang = b.idbarang
	LEFT JOIN satuan s ON b.idsatuan = s.idsatuan
	WHERE p.status = p_status
	ORDER BY p.idpengadaan DESC;
END$$
DELIMITER ;

CALL sp_pengadaan_status('P');


-- 2. Stored Procedure menampilkan kartu stok berdasarkan barang
DELIMITER $$
CREATE PROCEDURE sp_kartu_stok_barang(p_idbarang int)
BEGIN
	SELECT
		k.idkartu_stok,
		k.created_at,
		b.nama AS nama_barang,
		s.nama_satuan,
		k.jenis_transaksi,
		k.masuk,
		k.keluar,
		k.stock,
		k.idtransaksi,
		k.idbarang
	FROM kartu_stok k
	LEFT JOIN barang b ON k.idbarang = b.idbarang
	LEFT JOIN satuan s ON b.idsatuan = s.idsatuan
	WHERE k.idbarang = p_idbarang
	ORDER BY k.idkartu_stok DESC;
END$$
DELIMITER ;

CALL sp_kartu_stok_barang(1);
