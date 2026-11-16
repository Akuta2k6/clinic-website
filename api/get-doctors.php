<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$maCK = filter_input(INPUT_GET, 'MaCK', FILTER_VALIDATE_INT);
if (!$maCK) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT bs.MaBS, bs.HoTen, bs.HocVi
     FROM BacSi bs
     INNER JOIN ChuyenKhoa ck ON bs.MaCK = ck.MaCK
     WHERE bs.MaCK = ? AND bs.TrangThai = 1 AND ck.TrangThai = 1
     ORDER BY bs.HoTen'
);
$stmt->execute([$maCK]);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);