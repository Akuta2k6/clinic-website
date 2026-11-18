-- =========================================================
-- QUAN LY KHAM BENH - DATABASE TONG HOP THEO PHIEN BAN HIEN TAI (13 BANG)
-- Tuong thich voi MariaDB 10.4.x (XAMPP).
-- Import mot lan de tao cau truc va du lieu khoi tao.
-- Khong DROP DATABASE va khong xoa cac bang/ban ghi dang co.
-- Ngoai tru cot TrieuChung cu (neu ton tai), vi phien ban hien tai da bo cot nay.
-- =========================================================
-- Luu y: neu DatLich dang co cot TrieuChung, cot nay se bi xoa
-- de dong bo voi phien ban hien tai. Hay sao luu truoc khi import.
-- =========================================================

-- =========================================================
-- DATABASE: QuanLyKhamBenh
-- Dùng cho project clinic-website (PHP + MySQL/XAMPP)
-- Phiên bản MaBS theo chuyên khoa: N001, NG001, RHM001, M001, TM001
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


CREATE DATABASE IF NOT EXISTS QuanLyKhamBenh
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE QuanLyKhamBenh;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 1. TÀI KHOẢN
-- Chỉ Admin và Bác sĩ cần tài khoản
-- =========================================================
CREATE TABLE IF NOT EXISTS TaiKhoan (
    MaTK INT AUTO_INCREMENT PRIMARY KEY,
    TenDangNhap VARCHAR(100) NOT NULL UNIQUE,
    MatKhau VARCHAR(255) NOT NULL,
    VaiTro ENUM('Admin', 'BacSi') NOT NULL,
    TrangThai TINYINT(1) NOT NULL DEFAULT 1,
    NgayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 2. CHUYÊN KHOA
-- MaVietTat dùng để tự sinh MaBS
-- =========================================================
CREATE TABLE IF NOT EXISTS ChuyenKhoa (
    MaCK INT AUTO_INCREMENT PRIMARY KEY,
    MaVietTat VARCHAR(10) NOT NULL UNIQUE,
    TenChuyenKhoa VARCHAR(150) NOT NULL UNIQUE,
    MoTa TEXT,
    TrangThai TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- =========================================================
-- 3. BÁC SĨ
-- MaBS không AUTO_INCREMENT nữa.
-- Ví dụ: N001, NG001, RHM001, M001, TM001
-- =========================================================
CREATE TABLE IF NOT EXISTS BacSi (
    MaBS VARCHAR(10) PRIMARY KEY,
    MaTK INT NOT NULL UNIQUE,
    MaCK INT NOT NULL,
    HoTen VARCHAR(150) NOT NULL,
    GioiTinh VARCHAR(20),
    NgaySinh DATE,
    SoDienThoai VARCHAR(20),
    Email VARCHAR(150),
    HocVi VARCHAR(150),
    ChuyenMon VARCHAR(255),
    KinhNghiem INT NOT NULL DEFAULT 0,
    AnhDaiDien VARCHAR(255),
    TrangThai TINYINT(1) NOT NULL DEFAULT 1,

    CONSTRAINT FK_BacSi_TaiKhoan
        FOREIGN KEY (MaTK)
        REFERENCES TaiKhoan(MaTK)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT FK_BacSi_ChuyenKhoa
        FOREIGN KEY (MaCK)
        REFERENCES ChuyenKhoa(MaCK)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_bacsi_mack (MaCK),
    INDEX idx_bacsi_hoten (HoTen)
) ENGINE=InnoDB;

-- =========================================================
-- 4. BỆNH NHÂN
-- Bệnh nhân không cần tài khoản
-- =========================================================
CREATE TABLE IF NOT EXISTS BenhNhan (
    MaBN INT AUTO_INCREMENT PRIMARY KEY,
    HoTen VARCHAR(150) NOT NULL,
    GioiTinh VARCHAR(20),
    NgaySinh DATE,
    SoDienThoai VARCHAR(20) NOT NULL,
    Email VARCHAR(150),
    CCCD VARCHAR(20),
    DiaChi VARCHAR(255),
    NgayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_benhnhan_sdt (SoDienThoai),
    INDEX idx_benhnhan_hoten (HoTen)
) ENGINE=InnoDB;

-- =========================================================
-- 5. PHÒNG KHÁM
-- =========================================================
CREATE TABLE IF NOT EXISTS PhongKham (
    MaPK INT AUTO_INCREMENT PRIMARY KEY,
    TenPhong VARCHAR(100) NOT NULL,
    ViTri VARCHAR(255),
    TrangThai TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- =========================================================
-- 6. LỊCH KHÁM / LỊCH LÀM VIỆC CỦA BÁC SĨ
-- =========================================================
CREATE TABLE IF NOT EXISTS LichKham (
    MaLich INT AUTO_INCREMENT PRIMARY KEY,
    MaBS VARCHAR(10) NOT NULL,
    MaPK INT NULL,
    NgayKham DATE NOT NULL,
    GioBatDau TIME NOT NULL,
    GioKetThuc TIME NOT NULL,
    SoLuongToiDa INT NOT NULL DEFAULT 1,
    SoLuongDaDat INT NOT NULL DEFAULT 0,
    TrangThai ENUM('Mo', 'Day', 'Huy') NOT NULL DEFAULT 'Mo',
    GhiChu VARCHAR(500),

    CONSTRAINT FK_LichKham_BacSi
        FOREIGN KEY (MaBS)
        REFERENCES BacSi(MaBS)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT FK_LichKham_PhongKham
        FOREIGN KEY (MaPK)
        REFERENCES PhongKham(MaPK)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT CK_LichKham_ThoiGian
        CHECK (GioKetThuc > GioBatDau),

    CONSTRAINT CK_LichKham_SoLuong
        CHECK (SoLuongToiDa > 0 AND SoLuongDaDat >= 0),

    UNIQUE KEY uq_lich_bacsi (MaBS, NgayKham, GioBatDau),
    INDEX idx_lich_ngay (NgayKham),
    INDEX idx_lich_phong (MaPK)
) ENGINE=InnoDB;

-- =========================================================
-- 7. ĐẶT LỊCH
-- =========================================================
CREATE TABLE IF NOT EXISTS DatLich (
    MaDatLich INT AUTO_INCREMENT PRIMARY KEY,
    MaTraCuu VARCHAR(20) NOT NULL UNIQUE,
    MaBN INT NOT NULL,
    MaLich INT NOT NULL,
    NgayDat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    LyDoKham TEXT,
    TrangThai ENUM('ChoXacNhan','DaXacNhan','DaKham','DaHuy','VangMat') NOT NULL DEFAULT 'ChoXacNhan',
    GhiChu VARCHAR(500),
    TrangThaiGuiKetQua ENUM('ChuaGui', 'DangGui', 'DaGui', 'ThatBai') NOT NULL DEFAULT 'ChuaGui',
    ThoiGianThuGuiKetQua DATETIME NULL,
    ThoiGianGuiKetQua DATETIME NULL,
    LoiGuiKetQua VARCHAR(500) NULL,

    CONSTRAINT FK_DatLich_BenhNhan FOREIGN KEY (MaBN) REFERENCES BenhNhan(MaBN) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_DatLich_LichKham FOREIGN KEY (MaLich) REFERENCES LichKham(MaLich) ON UPDATE CASCADE ON DELETE RESTRICT,

    UNIQUE KEY uq_benhnhan_lich (MaBN, MaLich),
    INDEX idx_datlich_trangthai (TrangThai),
    INDEX idx_datlich_ngaydat (NgayDat)
) ENGINE=InnoDB;


-- =========================================================
-- DANH GIA CUA KHACH HANG
-- =========================================================
CREATE TABLE IF NOT EXISTS DanhGia (
    MaDanhGia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    TenNguoiDanhGia VARCHAR(100) NULL,
    AnDanh TINYINT(1) NOT NULL DEFAULT 0,
    SoSao TINYINT UNSIGNED NOT NULL,
    NoiDung VARCHAR(1000) NOT NULL,
    NgayDanhGia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    TrangThai ENUM('HienThi', 'An') NOT NULL DEFAULT 'HienThi',
    CONSTRAINT CK_DanhGia_SoSao CHECK (SoSao BETWEEN 1 AND 5),
    INDEX idx_danhgia_trangthai_ngay (TrangThai, NgayDanhGia),
    INDEX idx_danhgia_ngay (NgayDanhGia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS XacMinhHuyLich (
    MaDatLich INT PRIMARY KEY,
    MaOTPHash VARCHAR(255) NOT NULL,
    ThoiGianTao DATETIME NOT NULL,
    ThoiGianHetHan DATETIME NOT NULL,
    SoLanNhapSai TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT FK_XacMinhHuyLich_DatLich
        FOREIGN KEY (MaDatLich)
        REFERENCES DatLich(MaDatLich)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_xacminhhuylich_hethan (ThoiGianHetHan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 8. THUỐC
-- =========================================================
CREATE TABLE IF NOT EXISTS Thuoc (
    MaThuoc INT AUTO_INCREMENT PRIMARY KEY,
    TenThuoc VARCHAR(200) NOT NULL,
    DonVi VARCHAR(50),
    SoLuongTon INT NOT NULL DEFAULT 0,
    DonGia DECIMAL(12,2) NOT NULL DEFAULT 0,
    HanSuDung DATE,
    TrangThai TINYINT(1) NOT NULL DEFAULT 1,

    CONSTRAINT CK_Thuoc_SoLuongTon CHECK (SoLuongTon >= 0),
    CONSTRAINT CK_Thuoc_DonGia CHECK (DonGia >= 0),
    INDEX idx_thuoc_ten (TenThuoc)
) ENGINE=InnoDB;

-- =========================================================
-- 9. HỒ SƠ KHÁM
-- Mỗi lịch hẹn hoàn thành có tối đa 1 hồ sơ khám
-- =========================================================
CREATE TABLE IF NOT EXISTS HoSoKham (
    MaHS INT AUTO_INCREMENT PRIMARY KEY,
    MaDatLich INT NOT NULL UNIQUE,
    ThoiGianKham DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ChanDoan TEXT,
    KetLuan TEXT,
    HuongDieuTri TEXT,
    GhiChu TEXT,

    CONSTRAINT FK_HoSoKham_DatLich
        FOREIGN KEY (MaDatLich)
        REFERENCES DatLich(MaDatLich)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
-- 10. ĐƠN THUỐC
-- Mỗi hồ sơ khám có tối đa 1 đơn thuốc
-- =========================================================
CREATE TABLE IF NOT EXISTS DonThuoc (
    MaDon INT AUTO_INCREMENT PRIMARY KEY,
    MaHS INT NOT NULL UNIQUE,
    NgayKeDon DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    LoiDan TEXT,

    CONSTRAINT FK_DonThuoc_HoSoKham
        FOREIGN KEY (MaHS)
        REFERENCES HoSoKham(MaHS)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
-- 11. CHI TIẾT ĐƠN THUỐC
-- =========================================================
CREATE TABLE IF NOT EXISTS ChiTietDonThuoc (
    MaCT INT AUTO_INCREMENT PRIMARY KEY,
    MaDon INT NOT NULL,
    MaThuoc INT NOT NULL,
    SoLuong INT NOT NULL,
    LieuDung VARCHAR(255),
    CachDung VARCHAR(500),

    CONSTRAINT FK_ChiTietDonThuoc_DonThuoc
        FOREIGN KEY (MaDon)
        REFERENCES DonThuoc(MaDon)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT FK_ChiTietDonThuoc_Thuoc
        FOREIGN KEY (MaThuoc)
        REFERENCES Thuoc(MaThuoc)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT CK_ChiTietDonThuoc_SoLuong CHECK (SoLuong > 0),
    INDEX idx_ctdt_madon (MaDon),
    INDEX idx_ctdt_mathuoc (MaThuoc)
) ENGINE=InnoDB;

-- =========================================================
-- BO SUNG TUONG THICH VOI CAC BAN DATABASE CU
-- =========================================================

-- Them cac cot theo doi gui ket qua neu database da ton tai tu phien ban cu.
ALTER TABLE DatLich
    ADD COLUMN IF NOT EXISTS TrangThaiGuiKetQua ENUM('ChuaGui', 'DangGui', 'DaGui', 'ThatBai') NOT NULL DEFAULT 'ChuaGui' AFTER GhiChu,
    ADD COLUMN IF NOT EXISTS ThoiGianThuGuiKetQua DATETIME NULL AFTER TrangThaiGuiKetQua,
    ADD COLUMN IF NOT EXISTS ThoiGianGuiKetQua DATETIME NULL AFTER ThoiGianThuGuiKetQua,
    ADD COLUMN IF NOT EXISTS LoiGuiKetQua VARCHAR(500) NULL AFTER ThoiGianGuiKetQua;

-- Cot TrieuChung khong con duoc su dung trong phien ban hien tai.
SET @has_trieu_chung = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'DatLich'
      AND COLUMN_NAME = 'TrieuChung'
);
SET @drop_trieu_chung_sql = IF(
    @has_trieu_chung > 0,
    'ALTER TABLE DatLich DROP COLUMN TrieuChung',
    'SELECT 1'
);
PREPARE drop_trieu_chung_stmt FROM @drop_trieu_chung_sql;
EXECUTE drop_trieu_chung_stmt;
DEALLOCATE PREPARE drop_trieu_chung_stmt;

-- =========================================================
-- DU LIEU KHOI TAO: CHUYEN KHOA, PHONG, TAI KHOAN, BAC SI, THUOC
-- INSERT IGNORE giup khong tao trung du lieu khi import lai.
-- =========================================================

INSERT IGNORE INTO ChuyenKhoa
    (MaCK, MaVietTat, TenChuyenKhoa, MoTa, TrangThai)
VALUES
    (1, 'N',   'Khoa Nội',          'Khám và điều trị các bệnh nội khoa', 1),
    (2, 'NG',  'Khoa Ngoại',        'Khám và điều trị các bệnh ngoại khoa', 1),
    (3, 'RHM', 'Khoa Răng Hàm Mặt', 'Khám và điều trị các bệnh răng hàm mặt', 1),
    (4, 'M',   'Khoa Mắt',          'Khám và điều trị các bệnh về mắt', 1),
    (5, 'TM',  'Khoa Tim Mạch',     'Khám và điều trị các bệnh tim mạch', 1);

INSERT IGNORE INTO PhongKham
    (MaPK, TenPhong, ViTri, TrangThai)
VALUES
    (1, 'Phòng 101 - Khoa Nội', 'Tầng 1 - Khu Nội khoa', 1),
    (2, 'Phòng 102 - Khoa Nội', 'Tầng 1 - Khu Nội khoa', 1),
    (3, 'Phòng 201 - Khoa Ngoại', 'Tầng 2 - Khu Ngoại khoa', 1),
    (4, 'Phòng 202 - Khoa Ngoại', 'Tầng 2 - Khu Ngoại khoa', 1),
    (5, 'Phòng 301 - Răng Hàm Mặt', 'Tầng 3 - Khu Răng Hàm Mặt', 1),
    (6, 'Phòng 302 - Răng Hàm Mặt', 'Tầng 3 - Khu Răng Hàm Mặt', 1),
    (7, 'Phòng 401 - Khoa Mắt', 'Tầng 4 - Khu Mắt', 1),
    (8, 'Phòng 402 - Khoa Mắt', 'Tầng 4 - Khu Mắt', 1),
    (9, 'Phòng 501 - Khoa Tim Mạch', 'Tầng 5 - Khu Tim Mạch', 1),
    (10, 'Phòng 502 - Khoa Tim Mạch', 'Tầng 5 - Khu Tim Mạch', 1);

-- Mat khau mac dinh: admin / admin123; cac bac si / doctor123.
SET @admin_password = '$2y$12$lGssCmiKtp2MD.xHgItwAe7PmgN4jHFVk0aP2dRzbAFAiL7ECxVKu';
SET @doctor_password = '$2y$12$EPzq8Yw/KMCXY82TCVqUVurvCva705J6.IlmhfwdvxRond4k6NnAq';

INSERT IGNORE INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES
    ('admin', @admin_password, 'Admin', 1),
    ('bs.nguyenminhanh', @doctor_password, 'BacSi', 1),
    ('bs.tranthuylinh', @doctor_password, 'BacSi', 1),
    ('bs.lehoangnam', @doctor_password, 'BacSi', 1),
    ('bs.phamquocbao', @doctor_password, 'BacSi', 1),
    ('bs.nguyenngocmai', @doctor_password, 'BacSi', 1),
    ('bs.tranminhkhoa', @doctor_password, 'BacSi', 1),
    ('bs.lethanhha', @doctor_password, 'BacSi', 1),
    ('bs.voquanghuy', @doctor_password, 'BacSi', 1),
    ('bs.phamgiahan', @doctor_password, 'BacSi', 1),
    ('bs.nguyenduykhang', @doctor_password, 'BacSi', 1);

INSERT IGNORE INTO BacSi
    (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
SELECT 'N001', tk.MaTK, ck.MaCK, 'Nguyễn Minh Anh', 'Nam', '0901000001', 'minhanh@clinic.vn', 'Bác sĩ chuyên khoa I', 'Nội tổng quát', 8, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'N'
WHERE tk.TenDangNhap = 'bs.nguyenminhanh'
UNION ALL
SELECT 'N002', tk.MaTK, ck.MaCK, 'Trần Thùy Linh', 'Nữ', '0901000002', 'thuylinh@clinic.vn', 'Bác sĩ chuyên khoa I', 'Nội tổng quát', 6, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'N'
WHERE tk.TenDangNhap = 'bs.tranthuylinh'
UNION ALL
SELECT 'NG001', tk.MaTK, ck.MaCK, 'Lê Hoàng Nam', 'Nam', '0902000001', 'hoangnam@clinic.vn', 'Bác sĩ chuyên khoa II', 'Ngoại tổng quát', 10, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'NG'
WHERE tk.TenDangNhap = 'bs.lehoangnam'
UNION ALL
SELECT 'NG002', tk.MaTK, ck.MaCK, 'Phạm Quốc Bảo', 'Nam', '0902000002', 'quocbao@clinic.vn', 'Bác sĩ chuyên khoa I', 'Ngoại tổng quát', 7, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'NG'
WHERE tk.TenDangNhap = 'bs.phamquocbao'
UNION ALL
SELECT 'RHM001', tk.MaTK, ck.MaCK, 'Nguyễn Ngọc Mai', 'Nữ', '0903000001', 'ngocmai@clinic.vn', 'Bác sĩ chuyên khoa I', 'Răng Hàm Mặt', 6, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'RHM'
WHERE tk.TenDangNhap = 'bs.nguyenngocmai'
UNION ALL
SELECT 'RHM002', tk.MaTK, ck.MaCK, 'Trần Minh Khoa', 'Nam', '0903000002', 'minhkhoa@clinic.vn', 'Bác sĩ chuyên khoa I', 'Răng Hàm Mặt', 8, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'RHM'
WHERE tk.TenDangNhap = 'bs.tranminhkhoa'
UNION ALL
SELECT 'M001', tk.MaTK, ck.MaCK, 'Lê Thanh Hà', 'Nữ', '0904000001', 'thanhha@clinic.vn', 'Bác sĩ chuyên khoa I', 'Nhãn khoa', 7, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'M'
WHERE tk.TenDangNhap = 'bs.lethanhha'
UNION ALL
SELECT 'M002', tk.MaTK, ck.MaCK, 'Võ Quang Huy', 'Nam', '0904000002', 'quanghuy@clinic.vn', 'Bác sĩ chuyên khoa I', 'Nhãn khoa', 9, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'M'
WHERE tk.TenDangNhap = 'bs.voquanghuy'
UNION ALL
SELECT 'TM001', tk.MaTK, ck.MaCK, 'Phạm Gia Hân', 'Nữ', '0905000001', 'giahan@clinic.vn', 'Bác sĩ chuyên khoa II', 'Tim mạch', 11, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'TM'
WHERE tk.TenDangNhap = 'bs.phamgiahan'
UNION ALL
SELECT 'TM002', tk.MaTK, ck.MaCK, 'Nguyễn Duy Khang', 'Nam', '0905000002', 'duykhang@clinic.vn', 'Bác sĩ chuyên khoa II', 'Tim mạch', 12, 1
FROM TaiKhoan tk JOIN ChuyenKhoa ck ON ck.MaVietTat = 'TM'
WHERE tk.TenDangNhap = 'bs.nguyenduykhang';

INSERT IGNORE INTO Thuoc
    (MaThuoc, TenThuoc, DonVi, SoLuongTon, DonGia, HanSuDung, TrangThai)
VALUES
    (1, 'Paracetamol 500mg', 'Viên', 500, 1200, '2028-12-31', 1),
    (2, 'Amoxicillin 500mg', 'Viên', 300, 2500, '2028-06-30', 1),
    (3, 'Vitamin C 500mg', 'Viên', 400, 1000, '2028-10-31', 1),
    (4, 'Omeprazole 20mg', 'Viên', 200, 1800, '2028-09-30', 1),
    (5, 'Ibuprofen 400mg', 'Viên', 250, 1500, '2028-08-31', 1);

-- =========================================================
-- LICH KHAM MAU: 7 NGAY LIEN TIEP TU NGAY MAI
-- 10 bac si x 7 ngay x 8 ca = 560 lich trong database moi.
-- =========================================================

INSERT IGNORE INTO LichKham
(
    MaBS,
    MaPK,
    NgayKham,
    GioBatDau,
    GioKetThuc,
    SoLuongToiDa,
    SoLuongDaDat,
    TrangThai
)
SELECT
    bs.MaBS,
    pk.MaPK,
    ngay.NgayKham,
    ca.GioBatDau,
    ca.GioKetThuc,
    1,
    0,
    'Mo'
FROM BacSi bs
JOIN PhongKham pk
    ON pk.MaPK = CASE bs.MaBS
        WHEN 'N001' THEN 1
        WHEN 'N002' THEN 2
        WHEN 'NG001' THEN 3
        WHEN 'NG002' THEN 4
        WHEN 'RHM001' THEN 5
        WHEN 'RHM002' THEN 6
        WHEN 'M001' THEN 7
        WHEN 'M002' THEN 8
        WHEN 'TM001' THEN 9
        WHEN 'TM002' THEN 10
        ELSE NULL
    END
CROSS JOIN
(
    SELECT CURDATE() + INTERVAL 1 DAY AS NgayKham
    UNION ALL SELECT CURDATE() + INTERVAL 2 DAY
    UNION ALL SELECT CURDATE() + INTERVAL 3 DAY
    UNION ALL SELECT CURDATE() + INTERVAL 4 DAY
    UNION ALL SELECT CURDATE() + INTERVAL 5 DAY
    UNION ALL SELECT CURDATE() + INTERVAL 6 DAY
    UNION ALL SELECT CURDATE() + INTERVAL 7 DAY
) ngay
CROSS JOIN
(
    SELECT CAST('07:00:00' AS TIME) AS GioBatDau, CAST('08:00:00' AS TIME) AS GioKetThuc
    UNION ALL SELECT '08:00:00', '09:00:00'
    UNION ALL SELECT '09:00:00', '10:00:00'
    UNION ALL SELECT '10:00:00', '11:00:00'
    UNION ALL SELECT '13:00:00', '14:00:00'
    UNION ALL SELECT '14:00:00', '15:00:00'
    UNION ALL SELECT '15:00:00', '16:00:00'
    UNION ALL SELECT '16:00:00', '17:00:00'
) ca
WHERE bs.TrangThai = 1;

-- =========================================================
-- KIEM TRA SAU KHI IMPORT
-- =========================================================
SELECT 'TaiKhoan' AS Bang, COUNT(*) AS SoLuong FROM TaiKhoan
UNION ALL SELECT 'ChuyenKhoa', COUNT(*) FROM ChuyenKhoa
UNION ALL SELECT 'BacSi', COUNT(*) FROM BacSi
UNION ALL SELECT 'BenhNhan', COUNT(*) FROM BenhNhan
UNION ALL SELECT 'PhongKham', COUNT(*) FROM PhongKham
UNION ALL SELECT 'LichKham', COUNT(*) FROM LichKham
UNION ALL SELECT 'DatLich', COUNT(*) FROM DatLich
UNION ALL SELECT 'XacMinhHuyLich', COUNT(*) FROM XacMinhHuyLich
UNION ALL SELECT 'Thuoc', COUNT(*) FROM Thuoc
UNION ALL SELECT 'HoSoKham', COUNT(*) FROM HoSoKham
UNION ALL SELECT 'DonThuoc', COUNT(*) FROM DonThuoc
UNION ALL SELECT 'ChiTietDonThuoc', COUNT(*) FROM ChiTietDonThuoc;
