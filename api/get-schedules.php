
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
    "SELECT MaLich,
            TIME_FORMAT(GioBatDau, '%H:%i') AS GioBatDau,
            TIME_FORMAT(GioKetThuc, '%H:%i') AS GioKetThuc
     FROM LichKham
     WHERE MaBS = ?
       AND NgayKham = ?
       AND NgayKham >= CURDATE()
       AND TrangThai = 'Mo'
       AND SoLuongDaDat < SoLuongToiDa
     ORDER BY GioBatDau"
);

$stmt->execute([$maBS, $ngay]);

echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
