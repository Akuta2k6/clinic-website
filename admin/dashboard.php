<?php
require_once __DIR__ . '/../auth/check.php'; requireRole('Admin'); require_once __DIR__ . '/../config/database.php';
$stats=[
 'bacsi'=>(int)$pdo->query('SELECT COUNT(*) FROM BacSi WHERE TrangThai=1')->fetchColumn(),
 'benhnhan'=>(int)$pdo->query('SELECT COUNT(*) FROM BenhNhan')->fetchColumn(),
 'lichhomnay'=>(int)$pdo->query("SELECT COUNT(*) FROM DatLich dl JOIN LichKham lk ON dl.MaLich=lk.MaLich WHERE lk.NgayKham=CURDATE() AND dl.TrangThai<>'DaHuy'")->fetchColumn(),
 'thuoc'=>(int)$pdo->query('SELECT COUNT(*) FROM Thuoc WHERE TrangThai=1')->fetchColumn()
];
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin</title><link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet"></head><body><div class="container py-4">
<div class="d-flex justify-content-between mb-4"><h2>Admin Dashboard</h2><a href="../logout.php" class="btn btn-outline-danger">Đăng xuất</a></div>
<div class="row g-3 mb-4"><div class="col-md-3"><div class="card card-body"><h5>Bác sĩ</h5><h2><?= $stats['bacsi'] ?></h2></div></div><div class="col-md-3"><div class="card card-body"><h5>Bệnh nhân</h5><h2><?= $stats['benhnhan'] ?></h2></div></div><div class="col-md-3"><div class="card card-body"><h5>Lịch hôm nay</h5><h2><?= $stats['lichhomnay'] ?></h2></div></div><div class="col-md-3"><div class="card card-body"><h5>Loại thuốc</h5><h2><?= $stats['thuoc'] ?></h2></div></div></div>
<div class="d-flex gap-2 flex-wrap"><a class="btn btn-primary" href="doctors.php">Quản lý bác sĩ</a><a class="btn btn-primary" href="specialties.php">Quản lý chuyên khoa</a><a class="btn btn-primary" href="rooms.php">Quản lý phòng khám</a><a class="btn btn-primary" href="appointments.php">Quản lý lịch hẹn</a><a class="btn btn-primary" href="medicines.php">Quản lý thuốc</a></div>
</div></body></html>
