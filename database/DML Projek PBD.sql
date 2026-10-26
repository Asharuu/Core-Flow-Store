USE uts_pbd_026;
INSERT INTO satuan (idsatuan, nama_satuan, status)
VALUES (1, 'Pcs', 1),
		 (2, 'Dus', 1),
	 	 (3, 'Botol', 1),
		 (4, 'Pack', 1),
		 (5, 'Kg', 1);

SELECT * FROM satuan

INSERT INTO role (idrole, nama_role)
VALUES (1, 'Admin'),
		 (2, 'Staff Pengadaan'),
		 (3, 'Staff Gudang'),
 		 (4, 'Kasir'),
		 (5, 'Supervisor');

SELECT * FROM role

INSERT INTO user (iduser, username, password, idrole)
VALUES (1, 'admin_utama', 'admin123', 1),
       (2, 'ashar_pengadaan', 'ashar123', 2),
       (3, 'altaf_gudang', 'altaf123', 3),
       (4, 'rahel_kasir', 'rahel123', 4),
       (5, 'hikam_supervisor', 'hikam123', 5);

SELECT * FROM user

INSERT INTO vendor (idvendor, nama_vendor, badan_hukum, status)
VALUES (1, 'PT Snack Jaya', 'P', 'A'),
       (2, 'PT Grosir Indah', 'P', 'A'),
       (3, 'PT Perbotolan', 'P', 'A'),
       (4, 'PT Pokemon', 'P', 'A'),
       (5, 'PT Sembako', 'P', 'A');
       
SELECT * FROM vendor

INSERT INTO barang (idbarang, jenis, nama, idsatuan, status, harga)
VALUES (1, 'M', 'Chiki Ball', 1, 1, 3500),
       (2, 'M', 'Indomie 1 Dus', 2, 1, 120000),
       (3, 'M', 'Teh Botol 450ml', 3, 1, 5000),
       (4, 'N', 'Kartu Pokemon', 4, 1, 100000),
       (5, 'M', 'Beras Rojo Lele 5kg', 5, 1, 70000);

SELECT * FROM barang

INSERT INTO margin_penjualan (idmargin_penjualan, created_at, persen, status, iduser, updated_at)
VALUES (1, '2026-09-01 08:00:00', 10.0, 1, 1, '2026-10-01 08:00:00'),
       (2, '2026-09-01 08:30:00', 12.5, 1, 1, '2026-10-01 08:30:00'),
       (3, '2026-09-02 09:00:00', 15.0, 1, 5, '2026-10-02 09:00:00'),
       (4, '2026-09-02 09:30:00', 17.5, 1, 5, '2026-10-02 09:30:00'),
       (5, '2026-09-03 10:00:00', 20.0, 0, 1, '2026-10-03 10:00:00');
       
SELECT * FROM margin_penjualan