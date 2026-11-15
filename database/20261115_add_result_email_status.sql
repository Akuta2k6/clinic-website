ALTER TABLE DatLich
    ADD COLUMN TrangThaiGuiKetQua ENUM('ChuaGui', 'DangGui', 'DaGui', 'ThatBai') NOT NULL DEFAULT 'ChuaGui' AFTER GhiChu,
    ADD COLUMN ThoiGianThuGuiKetQua DATETIME NULL AFTER TrangThaiGuiKetQua,
    ADD COLUMN ThoiGianGuiKetQua DATETIME NULL AFTER ThoiGianThuGuiKetQua,
    ADD COLUMN LoiGuiKetQua VARCHAR(500) NULL AFTER ThoiGianGuiKetQua;
