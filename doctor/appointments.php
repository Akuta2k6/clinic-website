<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');
require_once __DIR__ . '/../config/database.php';
$stmt=$pdo->prepare('SELECT MaBS,HoTen FROM BacSi WHERE MaTK=?'); $stmt->execute([$_SESSION['user']['MaTK']]); $bs=$stmt->fetch();
$stmt=$pdo->prepare("SELECT dl.MaDatLich,bn.HoTen,bn.SoDienThoai,bn.Email,lk.NgayKham,lk.GioBatDau,lk.GioKetThuc,dl.LyDoKham,dl.TrangThai
FROM DatLich dl JOIN BenhNhan bn ON dl.MaBN=bn.MaBN JOIN LichKham lk ON dl.MaLich=lk.MaLich WHERE lk.MaBS=? ORDER BY lk.NgayKham DESC,lk.GioBatDau");
$stmt->execute([$bs['MaBS']]); $rows=$stmt->fetchAll();
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Lịch khám</title><link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet"></head><body><div class="container py-4">
<div class="d-flex justify-content-between mb-3"><h2>Lịch khám - <?= htmlspecialchars($bs['HoTen']) ?></h2><a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a></div>
<table class="table table-striped table-bordered align-middle"><thead><tr><th>Bệnh nhân</th><th>Liên hệ</th><th>Ngày giờ</th><th>Lý do</th><th>Trạng thái</th><th>Hồ sơ</th><th>Cập nhật</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= htmlspecialchars($r['HoTen']) ?></td><td><?= htmlspecialchars($r['SoDienThoai']) ?><br><?= htmlspecialchars($r['Email'] ?? '') ?></td><td><?= $r['NgayKham'] ?><br><?= substr($r['GioBatDau'],0,5) ?> - <?= substr($r['GioKetThuc'],0,5) ?></td><td><?= nl2br(htmlspecialchars($r['LyDoKham'] ?? '')) ?></td><td><?= $r['TrangThai'] ?></td><td><a class="btn btn-sm btn-success" href="medical-record.php?appointment=<?= $r['MaDatLich'] ?>">Khám / Hồ sơ</a></td><td>
<form action="update-status.php" method="post" class="d-flex gap-2"><input type="hidden" name="MaDatLich" value="<?= $r['MaDatLich'] ?>"><select name="TrangThai" class="form-select form-select-sm"><option>ChoXacNhan</option><option>DaXacNhan</option><option>DaKham</option><option>DaHuy</option><option>VangMat</option></select><button class="btn btn-sm btn-primary">Lưu</button></form>
</td></tr><?php endforeach; ?>
</tbody></table></div></body></html>
