-- =========================================================
-- DATABASE: QuanLyKhamBenh
-- Dùng cho project clinic-website (PHP + MySQL/XAMPP)
-- Phiên bản MaBS theo chuyên khoa: N001, NG001, RHM001, M001, TM001
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS quanlykhambenh;

CREATE DATABASE QuanLyKhamBenh
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE QuanLyKhamBenh;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 1. TÀI KHOẢN
-- Chỉ Admin và Bác sĩ cần tài khoản
-- =========================================================
CREATE TABLE TaiKhoan (
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
CREATE TABLE ChuyenKhoa (
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
CREATE TABLE BacSi (
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
CREATE TABLE BenhNhan (
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
CREATE TABLE PhongKham (
    MaPK INT AUTO_INCREMENT PRIMARY KEY,
    TenPhong VARCHAR(100) NOT NULL,
    ViTri VARCHAR(255),
    TrangThai TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- =========================================================
-- 6. LỊCH KHÁM / LỊCH LÀM VIỆC CỦA BÁC SĨ
-- =========================================================
CREATE TABLE LichKham (
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
CREATE TABLE DatLich (
    MaDatLich INT AUTO_INCREMENT PRIMARY KEY,
    MaBN INT NOT NULL,
    MaLich INT NOT NULL,
    NgayDat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    LyDoKham TEXT,
    TrieuChung TEXT,
    TrangThai ENUM(
        'ChoXacNhan',
        'DaXacNhan',
        'DaKham',
        'DaHuy',
        'VangMat'
    ) NOT NULL DEFAULT 'ChoXacNhan',
    GhiChu VARCHAR(500),

    CONSTRAINT FK_DatLich_BenhNhan
        FOREIGN KEY (MaBN)
        REFERENCES BenhNhan(MaBN)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT FK_DatLich_LichKham
        FOREIGN KEY (MaLich)
        REFERENCES LichKham(MaLich)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    UNIQUE KEY uq_benhnhan_lich (MaBN, MaLich),
    INDEX idx_datlich_trangthai (TrangThai),
    INDEX idx_datlich_ngaydat (NgayDat)
) ENGINE=InnoDB;

-- =========================================================
-- 8. THUỐC
-- =========================================================
CREATE TABLE Thuoc (
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
CREATE TABLE HoSoKham (
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
CREATE TABLE DonThuoc (
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
CREATE TABLE ChiTietDonThuoc (
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
-- DỮ LIỆU MẪU
-- =========================================================

-- Chuyên khoa + mã viết tắt dùng để sinh mã bác sĩ
INSERT INTO ChuyenKhoa
    (MaCK, MaVietTat, TenChuyenKhoa, MoTa, TrangThai)
VALUES
    (1, 'N',   'Khoa Nội',          'Khám và điều trị các bệnh nội khoa', 1),
    (2, 'NG',  'Khoa Ngoại',        'Khám và điều trị các bệnh ngoại khoa', 1),
    (3, 'RHM', 'Khoa Răng Hàm Mặt', 'Khám và điều trị các bệnh răng hàm mặt', 1),
    (4, 'M',   'Khoa Mắt',          'Khám và điều trị các bệnh về mắt', 1),
    (5, 'TM',  'Khoa Tim Mạch',     'Khám và điều trị các bệnh tim mạch', 1);

-- Phòng khám mẫu
INSERT INTO PhongKham
    (MaPK, TenPhong, ViTri, TrangThai)
VALUES
    (1, 'Phòng 101', 'Tầng 1', 1),
    (2, 'Phòng 201', 'Tầng 2', 1),
    (3, 'Phòng 301', 'Tầng 3', 1),
    (4, 'Phòng 401', 'Tầng 4', 1),
    (5, 'Phòng 501', 'Tầng 5', 1);

-- Password chung: doctor123
SET @doctor_password = '$2y$12$EPzq8Yw/KMCXY82TCVqUVurvCva705J6.IlmhfwdvxRond4k6NnAq';

-- KHOA NỘI
INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.nguyenminhanh', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('N001', @MaTK, 1, 'Nguyễn Minh Anh', 'Nam', '0901000001', 'minhanh@clinic.vn', 'Bác sĩ chuyên khoa I', 'Nội tổng quát', 8, 1);

INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.tranthuylinh', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('N002', @MaTK, 1, 'Trần Thùy Linh', 'Nữ', '0901000002', 'thuylinh@clinic.vn', 'Bác sĩ chuyên khoa I', 'Nội tổng quát', 6, 1);

-- KHOA NGOẠI
INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.lehoangnam', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('NG001', @MaTK, 2, 'Lê Hoàng Nam', 'Nam', '0902000001', 'hoangnam@clinic.vn', 'Bác sĩ chuyên khoa II', 'Ngoại tổng quát', 10, 1);

INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.phamquocbao', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('NG002', @MaTK, 2, 'Phạm Quốc Bảo', 'Nam', '0902000002', 'quocbao@clinic.vn', 'Bác sĩ chuyên khoa I', 'Ngoại tổng quát', 7, 1);

-- KHOA RĂNG HÀM MẶT
INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.nguyenngocmai', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('RHM001', @MaTK, 3, 'Nguyễn Ngọc Mai', 'Nữ', '0903000001', 'ngocmai@clinic.vn', 'Bác sĩ chuyên khoa I', 'Răng Hàm Mặt', 6, 1);

INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.tranminhkhoa', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('RHM002', @MaTK, 3, 'Trần Minh Khoa', 'Nam', '0903000002', 'minhkhoa@clinic.vn', 'Bác sĩ chuyên khoa I', 'Răng Hàm Mặt', 8, 1);

-- KHOA MẮT
INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.lethanhha', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('M001', @MaTK, 4, 'Lê Thanh Hà', 'Nữ', '0904000001', 'thanhha@clinic.vn', 'Bác sĩ chuyên khoa I', 'Nhãn khoa', 7, 1);

INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.voquanghuy', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('M002', @MaTK, 4, 'Võ Quang Huy', 'Nam', '0904000002', 'quanghuy@clinic.vn', 'Bác sĩ chuyên khoa I', 'Nhãn khoa', 9, 1);

-- KHOA TIM MẠCH
INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.phamgiahan', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('TM001', @MaTK, 5, 'Phạm Gia Hân', 'Nữ', '0905000001', 'giahan@clinic.vn', 'Bác sĩ chuyên khoa II', 'Tim mạch', 11, 1);

INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('bs.nguyenduykhang', @doctor_password, 'BacSi', 1);
SET @MaTK = LAST_INSERT_ID();

INSERT INTO BacSi (MaBS, MaTK, MaCK, HoTen, GioiTinh, SoDienThoai, Email, HocVi, ChuyenMon, KinhNghiem, TrangThai)
VALUES ('TM002', @MaTK, 5, 'Nguyễn Duy Khang', 'Nam', '0905000002', 'duykhang@clinic.vn', 'Bác sĩ chuyên khoa II', 'Tim mạch', 12, 1);

-- Thuốc mẫu
INSERT INTO Thuoc
    (MaThuoc, TenThuoc, DonVi, SoLuongTon, DonGia, HanSuDung, TrangThai)
VALUES
    (1, 'Paracetamol 500mg',  'Viên', 500, 1200, '2028-12-31', 1),
    (2, 'Amoxicillin 500mg',  'Viên', 300, 2500, '2028-06-30', 1),
    (3, 'Vitamin C 500mg',    'Viên', 400, 1000, '2028-10-31', 1),
    (4, 'Omeprazole 20mg',    'Viên', 200, 1800, '2028-09-30', 1),
    (5, 'Ibuprofen 400mg',    'Viên', 250, 1500, '2028-08-31', 1);

-- =========================================================
-- LỊCH MẪU ĐỂ TEST ĐẶT LỊCH
-- Có thể xóa block này nếu không muốn dữ liệu lịch mẫu.
-- Lịch được tạo cho ngày mai nên luôn dễ test sau khi import.
-- =========================================================
-- =========================================================
-- LỊCH KHÁM MẪU: HÔM NAY + 7 NGÀY TỚI
-- Mỗi bác sĩ có 4 ca/ngày
-- =========================================================

INSERT INTO LichKham
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
    CASE
        WHEN bs.MaBS LIKE 'RHM%' THEN 3
        WHEN bs.MaBS LIKE 'NG%'  THEN 2
        WHEN bs.MaBS LIKE 'TM%'  THEN 5
        WHEN bs.MaBS LIKE 'N%'   THEN 1
        WHEN bs.MaBS LIKE 'M%'   THEN 4
        ELSE 1
    END
    AS MaPK,

    CURDATE() + INTERVAL ngay.SoNgay DAY AS NgayKham,

    ca.GioBatDau,
    ca.GioKetThuc,

    1,
    0,
    'Mo'

FROM BacSi bs

CROSS JOIN
(
    SELECT 0 AS SoNgay
    UNION ALL SELECT 1
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
    UNION ALL SELECT 6
    UNION ALL SELECT 7
) ngay

CROSS JOIN
(
    SELECT
        CAST('07:00:00' AS TIME) AS GioBatDau,
        CAST('08:00:00' AS TIME) AS GioKetThuc

    UNION ALL

    SELECT
        '10:00:00',
        '11:00:00'

    UNION ALL

    SELECT
        '13:00:00',
        '14:00:00'

    UNION ALL

    SELECT
        '16:00:00',
        '17:00:00'
) ca

WHERE bs.TrangThai = 1;
-- =========================================================
-- KIỂM TRA NHANH SAU KHI IMPORT
-- =========================================================
SELECT 'ChuyenKhoa' AS Bang, COUNT(*) AS SoLuong FROM ChuyenKhoa
UNION ALL
SELECT 'BacSi', COUNT(*) FROM BacSi
UNION ALL
SELECT 'PhongKham', COUNT(*) FROM PhongKham
UNION ALL
SELECT 'Thuoc', COUNT(*) FROM Thuoc
UNION ALL
SELECT 'LichKham', COUNT(*) FROM LichKham;
