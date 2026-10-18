USE uts_pbd_026;

-- 1. Tabel satuan
CREATE TABLE satuan (
    idsatuan INT,
    nama_satuan VARCHAR(45),
    status TINYINT,
    PRIMARY KEY (idsatuan)
);

-- 2. Tabel vendor
CREATE TABLE vendor (
    idvendor INT,
    nama_vendor VARCHAR(100),
    badan_hukum CHAR(1),
    status CHAR(1),
    PRIMARY KEY (idvendor)
);

-- 3. Tabel role
CREATE TABLE role (
    idrole INT,
    nama_role VARCHAR(100),
    PRIMARY KEY (idrole)
);

-- 4. Tabel user
CREATE TABLE user (
    iduser INT,
    username VARCHAR(45),
    password VARCHAR(100),
    idrole INT,
    PRIMARY KEY (iduser),
    FOREIGN KEY (idrole) REFERENCES role (idrole)
);

-- 5. Tabel barang
CREATE TABLE barang (
    idbarang INT,
    jenis CHAR(1),
    nama VARCHAR(45),
    idsatuan INT,
    status TINYINT,
    harga INT,
    PRIMARY KEY (idbarang),
    FOREIGN KEY (idsatuan) REFERENCES satuan (idsatuan)
);

-- 6. Tabel kartu_stok
CREATE TABLE kartu_stok (
    idkartu_stok INT,
    jenis_transaksi CHAR(1),
    masuk INT,
    keluar INT,
    stock INT,
    created_at TIMESTAMP,
    idtransaksi INT,
    idbarang INT,
    PRIMARY KEY (idkartu_stok),
    FOREIGN KEY (idbarang) REFERENCES barang (idbarang)
);

ALTER TABLE kartu_stok
	MODIFY idkartu_stok INT;

-- 7. Tabel margin_penjualan
CREATE TABLE margin_penjualan (
    idmargin_penjualan INT,
    created_at TIMESTAMP,
    persen DOUBLE,
    status TINYINT,
    iduser INT,
    updated_at TIMESTAMP,
    PRIMARY KEY (idmargin_penjualan),
    FOREIGN KEY (iduser) REFERENCES user (iduser)
);

-- 8. Tabel pengadaan
CREATE TABLE pengadaan (
    idpengadaan BIGINT,
    timestamp TIMESTAMP,
    user_iduser INT,
    status CHAR(1),
    vendor_idvendor INT,
    subtotal_nilai INT,
    ppn INT,
    total_nilai INT,
    PRIMARY KEY (idpengadaan),
    FOREIGN KEY (user_iduser) REFERENCES user (iduser),
    FOREIGN KEY (vendor_idvendor) REFERENCES vendor (idvendor)
);

-- 9. Tabel detail_pengadaan
CREATE TABLE detail_pengadaan (
    iddetail_pengadaan BIGINT,
    harga_satuan INT,
    jumlah INT,
    sub_total INT,
    idbarang INT,
    idpengadaan BIGINT,
    PRIMARY KEY (iddetail_pengadaan),
    FOREIGN KEY (idbarang) REFERENCES barang (idbarang),
    FOREIGN KEY (idpengadaan) REFERENCES pengadaan (idpengadaan)
);

-- 10. Tabel penerimaan
CREATE TABLE penerimaan (
    idpenerimaan BIGINT,
    created_at TIMESTAMP,
    status CHAR(1),
    idpengadaan BIGINT,
    iduser INT,
    PRIMARY KEY (idpenerimaan),
    FOREIGN KEY (idpengadaan) REFERENCES pengadaan (idpengadaan),
    FOREIGN KEY (iduser) REFERENCES user (iduser)
);

-- 11. Tabel detail_penerimaan
CREATE TABLE detail_penerimaan (
    iddetail_penerimaan BIGINT,
    idpenerimaan BIGINT,
    barang_idbarang INT,
    jumlah_terima INT,
    harga_satuan_terima INT,
    sub_total_terima INT,
    PRIMARY KEY (iddetail_penerimaan),
    FOREIGN KEY (idpenerimaan) REFERENCES penerimaan (idpenerimaan),
    FOREIGN KEY (barang_idbarang) REFERENCES barang (idbarang)
);

-- 12. Tabel retur
CREATE TABLE retur (
    idretur BIGINT,
    created_at TIMESTAMP,
    idpenerimaan BIGINT,
    idpengadaan BIGINT,
    iduser INT,
    PRIMARY KEY (idretur),
    FOREIGN KEY (idpenerimaan) REFERENCES penerimaan (idpenerimaan),
    FOREIGN KEY (idpengadaan) REFERENCES pengadaan (idpengadaan),
    FOREIGN KEY (iduser) REFERENCES user (iduser)
);

-- 13. Tabel detail_retur
CREATE TABLE detail_retur (
    iddetail_retur INT,
    jumlah INT,
    alasan VARCHAR(200),
    idretur BIGINT,
    iddetail_penerimaan BIGINT,
    PRIMARY KEY (iddetail_retur),
    FOREIGN KEY (idretur) REFERENCES retur (idretur),
    FOREIGN KEY (iddetail_penerimaan) REFERENCES detail_penerimaan (iddetail_penerimaan)
);

-- 14. Tabel penjualan
CREATE TABLE penjualan (
    idpenjualan INT,
    created_at TIMESTAMP,
    subtotal_nilai INT,
    ppn INT,
    total_nilai INT,
    iduser INT,
    idmargin_penjualan INT,
    PRIMARY KEY (idpenjualan),
    FOREIGN KEY (iduser) REFERENCES user (iduser),
    FOREIGN KEY (idmargin_penjualan) REFERENCES margin_penjualan (idmargin_penjualan)
);

-- 15. Tabel detail_penjualan
CREATE TABLE detail_penjualan (
    iddetail_penjualan BIGINT,
    harga_satuan INT,
    jumlah INT,
    subtotal INT,
    penjualan_idpenjualan INT,
    idbarang INT,
    PRIMARY KEY (iddetail_penjualan),
    FOREIGN KEY (penjualan_idpenjualan) REFERENCES penjualan (idpenjualan),
    FOREIGN KEY (idbarang) REFERENCES barang (idbarang)
);