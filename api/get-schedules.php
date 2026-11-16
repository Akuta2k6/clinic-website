<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$maBS = trim($_GET['MaBS'] ?? '');
$ngay = trim($_GET['NgayKham'] ?? '');

if ($maBS === '' || $ngay === '') {
    echo json_encode([]);
    exit;
}

$date = DateTime::createFromFormat('!Y-m-d', $ngay);

if (!$date || $date->format('Y-m-d') !== $ngay) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT lk.MaLich,
            TIME_FORMAT(lk.GioBatDau, '%H:%i') AS GioBatDau,
            TIME_FORMAT(lk.GioKetThuc, '%H:%i') AS GioKetThuc
     FROM LichKham lk
     INNER JOIN BacSi bs ON lk.MaBS = bs.MaBS
     INNER JOIN ChuyenKhoa ck ON bs.MaCK = ck.MaCK
     WHERE lk.MaBS = ?
       AND lk.NgayKham = ?
       AND (lk.NgayKham > CURDATE() OR (lk.NgayKham = CURDATE() AND lk.GioBatDau > CURTIME()))
       AND lk.TrangThai = 'Mo'
       AND lk.SoLuongDaDat < lk.SoLuongToiDa
       AND bs.TrangThai = 1
       AND ck.TrangThai = 1
     ORDER BY lk.GioBatDau"
);

$stmt->execute([$maBS, $ngay]);

echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);