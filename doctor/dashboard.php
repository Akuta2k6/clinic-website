<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->prepare('SELECT * FROM BacSi WHERE MaTK = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['MaTK']]);
$bacSi = $stmt->fetch();
if (!$bacSi) exit('Không tìm thấy hồ sơ bác sĩ.');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM DatLich dl JOIN LichKham lk ON dl.MaLich=lk.MaLich WHERE lk.MaBS=? AND lk.NgayKham=CURDATE() AND dl.TrangThai<>'DaHuy'");
$stmt->execute([$bacSi['MaBS']]);
$todayCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM DatLich dl JOIN LichKham lk ON dl.MaLich=lk.MaLich WHERE lk.MaBS=?');
$stmt->execute([$bacSi['MaBS']]);
$totalCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT
        dl.MaDatLich,
        bn.HoTen,
        bn.SoDienThoai,
        lk.NgayKham,
        lk.GioBatDau,
        dl.TrangThai

    FROM DatLich dl

    JOIN BenhNhan bn
        ON dl.MaBN = bn.MaBN

    JOIN LichKham lk
        ON dl.MaLich = lk.MaLich

    WHERE lk.MaBS = ?

      AND lk.NgayKham >= CURDATE()

      AND dl.TrangThai IN (
          'ChoXacNhan',
          'DaXacNhan'
      )

    ORDER BY
        lk.NgayKham ASC,
        lk.GioBatDau ASC

    LIMIT 10
");

$stmt->execute([
    $bacSi['MaBS']
]);

$appointments = $stmt->fetchAll();
?>
<!doctype html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Bác sĩ</title>
        <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container py-4">
            <div class="d-flex justify-content-between">
                <div>
                    <h2>Xin chào, BS. <?= htmlspecialchars($bacSi['HoTen']) ?></h2>
                    <div class="row g-3 mt-2"><div class="col-sm-6"><div class="card card-body"><h6 class="text-muted mb-1">Lịch hôm nay</h6><h2 class="mb-0"><?= $todayCount ?></h2></div></div><div class="col-sm-6"><div class="card card-body"><h6 class="text-muted mb-1">Tổng lịch hẹn</h6><h2 class="mb-0"><?= $totalCount ?></h2></div></div></div>
                </div>
                <a href="../logout.php" class="btn btn-outline-danger h-50">Đăng xuất</a>
            </div>
            <div class="d-flex gap-2 mb-3">

                <a href="appointments.php"
                class="btn btn-primary">

                    Quản lý lịch khám

                </a>

                <a href="schedules.php"
                class="btn btn-success">

                    Quản lý lịch làm việc

                </a>

                <a href="history.php"
                class="btn btn-secondary">

                    Lịch sử đã khám

                </a>

            </div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Bệnh nhân</th>
                        <th>SĐT</th>
                        <th>Ngày</th>
                        <th>Giờ</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($appointments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['HoTen']) ?></td>
                            <td><?= htmlspecialchars($a['SoDienThoai']) ?></td>
                            <td><?= $a['NgayKham'] ?></td><td><?= substr($a['GioBatDau'],0,5) ?></td>
                            <td><?= $a['TrangThai'] ?></td>
                        </tr>
                        
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </body>
</html>
