<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$maCK = filter_input(INPUT_GET, 'MaCK', FILTER_VALIDATE_INT);
if (!$maCK) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare('SELECT MaBS, HoTen, HocVi FROM BacSi WHERE MaCK = ? AND TrangThai = 1 ORDER BY HoTen');
$stmt->execute([$maCK]);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
