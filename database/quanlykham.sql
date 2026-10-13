CREATE DATABASE IF NOT EXISTS QuanLyKhamBenh
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE QuanLyKhamBenh;

CREATE TABLE IF NOT EXISTS TaiKhoan (
    MaTK INT AUTO_INCREMENT PRIMARY KEY,
    TenDangNhap VARCHAR(100) NOT NULL UNIQUE,
    MatKhau VARCHAR(255) NOT NULL,
    VaiTro ENUM('Admin','BacSi') NOT NULL,
    TrangThai TINYINT(1) NOT NULL DEFAULT 1,
    NgayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ChuyenKhoa (
    MaCK INT AUTO_INCREMENT PRIMARY KEY,
    TenChuyenKhoa VARCHAR(150) NOT NULL UNIQUE,
    MoTa TEXT,
    TrangThai TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS BacSi (
    MaBS INT AUTO_INCREMENT PRIMARY KEY,
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
    CONSTRAINT FK_BacSi_TaiKhoan FOREIGN KEY (MaTK) REFERENCES TaiKhoan(MaTK),
    CONSTRAINT FK_BacSi_ChuyenKhoa FOREIGN KEY (MaCK) REFERENCES ChuyenKhoa(MaCK)
);

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
    INDEX idx_benhnhan_sdt (SoDienThoai)
);

CREATE TABLE IF NOT EXISTS PhongKham (
    MaPK INT AUTO_INCREMENT PRIMARY KEY,
    TenPhong VARCHAR(100) NOT NULL,
    ViTri VARCHAR(255),
    TrangThai TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS LichKham (
    MaLich INT AUTO_INCREMENT PRIMARY KEY,
    MaBS INT NOT NULL,
    MaPK INT NULL,
    NgayKham DATE NOT NULL,
    GioBatDau TIME NOT NULL,
    GioKetThuc TIME NOT NULL,
    SoLuongToiDa INT NOT NULL DEFAULT 1,
    SoLuongDaDat INT NOT NULL DEFAULT 0,
    TrangThai ENUM('Mo','Day','Huy') NOT NULL DEFAULT 'Mo',
    GhiChu VARCHAR(500),
    CONSTRAINT FK_LichKham_BacSi FOREIGN KEY (MaBS) REFERENCES BacSi(MaBS),
    CONSTRAINT FK_LichKham_PhongKham FOREIGN KEY (MaPK) REFERENCES PhongKham(MaPK),
    UNIQUE KEY uq_lich_bacsi (MaBS, NgayKham, GioBatDau)
);

CREATE TABLE IF NOT EXISTS DatLich (
    MaDatLich INT AUTO_INCREMENT PRIMARY KEY,
    MaBN INT NOT NULL,
    MaLich INT NOT NULL,
    NgayDat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    LyDoKham TEXT,
    TrieuChung TEXT,
    TrangThai ENUM('ChoXacNhan','DaXacNhan','DaKham','DaHuy','VangMat') NOT NULL DEFAULT 'ChoXacNhan',
    GhiChu VARCHAR(500),
    CONSTRAINT FK_DatLich_BenhNhan FOREIGN KEY (MaBN) REFERENCES BenhNhan(MaBN),
    CONSTRAINT FK_DatLich_LichKham FOREIGN KEY (MaLich) REFERENCES LichKham(MaLich),
    UNIQUE KEY uq_benhnhan_lich (MaBN, MaLich)
);

CREATE TABLE IF NOT EXISTS Thuoc (
    MaThuoc INT AUTO_INCREMENT PRIMARY KEY,
    TenThuoc VARCHAR(200) NOT NULL,
    DonVi VARCHAR(50),
    SoLuongTon INT NOT NULL DEFAULT 0,
    DonGia DECIMAL(12,2) NOT NULL DEFAULT 0,
    HanSuDung DATE,
    TrangThai TINYINT(1) NOT NULL DEFAULT 1
);

INSERT IGNORE INTO ChuyenKhoa (MaCK, TenChuyenKhoa, MoTa) VALUES
(1, 'Khoa Nội', 'Khám và điều trị các bệnh nội khoa'),
(2, 'Khoa Ngoại', 'Khám và điều trị các bệnh ngoại khoa'),
(3, 'Khoa Răng Hàm Mặt', 'Khám và điều trị răng hàm mặt'),
(4, 'Khoa Mắt', 'Khám và điều trị các bệnh về mắt'),
(5, 'Khoa Tim mạch', 'Khám và điều trị các bệnh tim mạch');

INSERT IGNORE INTO PhongKham (MaPK, TenPhong, ViTri) VALUES
(1, 'Phòng 101', 'Tầng 1'),
(2, 'Phòng 201', 'Tầng 2'),
(3, 'Phòng 301', 'Tầng 3');

INSERT IGNORE INTO TaiKhoan (MaTK, TenDangNhap, MatKhau, VaiTro) VALUES
(1, 'admin', '$2y$12$lGssCmiKtp2MD.xHgItwAe7PmgN4jHFVk0aP2dRzbAFAiL7ECxVKu', 'Admin'),
(2, 'bs.minhanh', '$2y$12$3LJ41YGHFc4C6FQWWolqAOUYHU6sGjwQT1kye5QTCUc88Enh2wsMe', 'BacSi'),
(3, 'bs.hoangnam', '$2y$12$3LJ41YGHFc4C6FQWWolqAOUYHU6sGjwQT1kye5QTCUc88Enh2wsMe', 'BacSi');

INSERT IGNORE INTO BacSi (MaBS, MaTK, MaCK, HoTen, Email, HocVi, KinhNghiem) VALUES
(1, 2, 1, 'Nguyễn Minh Anh', 'minhanh@clinic.vn', 'Bác sĩ chuyên khoa I', 8),
(2, 3, 2, 'Trần Hoàng Nam', 'hoangnam@clinic.vn', 'Bác sĩ chuyên khoa I', 10);

INSERT IGNORE INTO Thuoc (MaThuoc, TenThuoc, DonVi, SoLuongTon, DonGia, HanSuDung) VALUES
(1, 'Paracetamol 500mg', 'Viên', 500, 1200, '2028-12-31'),
(2, 'Amoxicillin 500mg', 'Viên', 300, 2500, '2028-06-30');

CREATE TABLE IF NOT EXISTS HoSoKham (
    MaHS INT AUTO_INCREMENT PRIMARY KEY,
    MaDatLich INT NOT NULL UNIQUE,
    ThoiGianKham DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ChanDoan TEXT,
    KetLuan TEXT,
    HuongDieuTri TEXT,
    GhiChu TEXT,
    CONSTRAINT FK_HoSoKham_DatLich FOREIGN KEY (MaDatLich) REFERENCES DatLich(MaDatLich)
);

CREATE TABLE IF NOT EXISTS DonThuoc (
    MaDon INT AUTO_INCREMENT PRIMARY KEY,
    MaHS INT NOT NULL UNIQUE,
    NgayKeDon DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    LoiDan TEXT,
    CONSTRAINT FK_DonThuoc_HoSoKham FOREIGN KEY (MaHS) REFERENCES HoSoKham(MaHS)
);

CREATE TABLE IF NOT EXISTS ChiTietDonThuoc (
    MaCT INT AUTO_INCREMENT PRIMARY KEY,
    MaDon INT NOT NULL,
    MaThuoc INT NOT NULL,
    SoLuong INT NOT NULL,
    LieuDung VARCHAR(255),
    CachDung VARCHAR(500),
    CONSTRAINT FK_ChiTietDonThuoc_DonThuoc FOREIGN KEY (MaDon) REFERENCES DonThuoc(MaDon),
    CONSTRAINT FK_ChiTietDonThuoc_Thuoc FOREIGN KEY (MaThuoc) REFERENCES Thuoc(MaThuoc)
);
