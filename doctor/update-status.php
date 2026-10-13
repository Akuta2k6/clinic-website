<?php
require_once __DIR__ . '/../auth/check.php'; requireRole('BacSi'); require_once __DIR__ . '/../config/database.php';
$maDatLich=filter_input(INPUT_POST,'MaDatLich',FILTER_VALIDATE_INT); $trangThai=$_POST['TrangThai']??'';
$allowed=['ChoXacNhan','DaXacNhan','DaKham','DaHuy','VangMat'];
if(!$maDatLich || !in_array($trangThai,$allowed,true)){header('Location: appointments.php');exit;}
$stmt=$pdo->prepare('SELECT MaBS FROM BacSi WHERE MaTK=?');$stmt->execute([$_SESSION['user']['MaTK']]);$maBS=$stmt->fetchColumn();
$stmt=$pdo->prepare('UPDATE DatLich dl JOIN LichKham lk ON dl.MaLich=lk.MaLich SET dl.TrangThai=? WHERE dl.MaDatLich=? AND lk.MaBS=?');$stmt->execute([$trangThai,$maDatLich,$maBS]);
header('Location: appointments.php');
