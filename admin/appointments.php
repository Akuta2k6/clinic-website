<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('Admin');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/portal-layout.php';
$selectedSpecialty = filter_var($_GET['specialty'] ?? 0, FILTER_VALIDATE_INT);
$selectedSpecialty = $selectedSpecialty !== false && $selectedSpecialty > 0 ? $selectedSpecialty : 0;
$specialtyFilters = $pdo->query(
    'SELECT ck.MaCK, ck.TenChuyenKhoa, COUNT(dl.MaDatLich) AS SoLich
     FROM ChuyenKhoa ck
     LEFT JOIN BacSi bs ON bs.MaCK = ck.MaCK
     LEFT JOIN LichKham lk ON lk.MaBS = bs.MaBS
     LEFT JOIN DatLich dl ON dl.MaLich = lk.MaLich
     GROUP BY ck.MaCK, ck.TenChuyenKhoa
     ORDER BY ck.TenChuyenKhoa'
)->fetchAll();
$totalAppointments = (int)$pdo->query('SELECT COUNT(*) FROM DatLich')->fetchColumn();
$validSpecialtyIds = array_map('intval', array_column($specialtyFilters, 'MaCK'));
if ($selectedSpecialty > 0 && !in_array($selectedSpecialty, $validSpecialtyIds, true)) {
    $selectedSpecialty = 0;
}
$appointmentSql = "SELECT dl.MaDatLich,bn.HoTen AS BenhNhan,bs.HoTen AS BacSi,ck.TenChuyenKhoa,lk.NgayKham,lk.GioBatDau,dl.TrangThai
                   FROM DatLich dl
                   JOIN BenhNhan bn ON dl.MaBN=bn.MaBN
                   JOIN LichKham lk ON dl.MaLich=lk.MaLich
                   JOIN BacSi bs ON lk.MaBS=bs.MaBS
                   JOIN ChuyenKhoa ck ON bs.MaCK=ck.MaCK";
if ($selectedSpecialty > 0) {
    $appointmentSql .= ' WHERE bs.MaCK = ?';
    $appointmentSql .= ' ORDER BY lk.NgayKham DESC,lk.GioBatDau';
    $appointmentStmt = $pdo->prepare($appointmentSql);
    $appointmentStmt->execute([$selectedSpecialty]);
    $rows = $appointmentStmt->fetchAll();
} else {
    $rows = $pdo->query($appointmentSql . ' ORDER BY lk.NgayKham DESC,lk.GioBatDau')->fetchAll();
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Quản lý lịch hẹn</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="clinic-dashboard-page">
<?php clinicPortalShellStart('Admin', 'Quản lý lịch hẹn', 'appointments'); ?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2>Danh sách lịch hẹn</h2>
            <p class="text-muted small mb-0 mt-1">Theo dõi lịch đặt khám của bệnh nhân trên toàn hệ thống.</p>
        </div>
        <span class="badge text-bg-primary"><?= count($rows) ?> / <?= $totalAppointments ?> lịch hẹn</span>
    </div>
    <div class="specialty-filter-panel">
        <div class="specialty-filter-heading">
            <div>
                <h5>Lọc theo chuyên khoa</h5>
                <p>Chọn chuyên khoa để xem các lịch hẹn tương ứng</p>
            </div>
        </div>
        <div class="specialty-filter-list">
            <a class="specialty-filter-chip <?= $selectedSpecialty === 0 ? 'active' : '' ?>" href="appointments.php" <?= $selectedSpecialty === 0 ? 'aria-current="page"' : '' ?>>
                <span>Tất cả</span><span class="specialty-filter-count"><?= $totalAppointments ?></span>
            </a>
            <?php foreach ($specialtyFilters as $specialty): ?>
                <?php $specialtyId = (int)$specialty['MaCK']; ?>
                <a class="specialty-filter-chip <?= $selectedSpecialty === $specialtyId ? 'active' : '' ?>" href="?specialty=<?= $specialtyId ?>" <?= $selectedSpecialty === $specialtyId ? 'aria-current="page"' : '' ?>>
                    <span><?= htmlspecialchars($specialty['TenChuyenKhoa'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="specialty-filter-count"><?= (int)$specialty['SoLich'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Bệnh nhân</th>
                    <th>Bác sĩ</th>
                    <th>Chuyên khoa</th>
                    <th>Ngày</th>
                    <th>Giờ</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['MaDatLich'] ?></td>
                        <td><?= htmlspecialchars($r['BenhNhan'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['BacSi'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['TenChuyenKhoa'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['NgayKham'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(substr($r['GioBatDau'], 0, 5), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $statusMap = [
                                'ChoXacNhan' => ['Chờ xác nhận', 'status-pending'],
                                'DaXacNhan' => ['Đã xác nhận', 'status-confirmed'],
                                'DaKham' => ['Đã khám', 'status-completed'],
                                'DaHuy' => ['Đã hủy', 'status-cancelled'],
                                'VangMat' => ['Vắng mặt', 'status-missed']
                            ];
                            [$statusLabel, $statusClass] = $statusMap[$r['TrangThai']] ?? [$r['TrangThai'], 'status-neutral'];
                            ?>
                            <span class="status-pill <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có lịch hẹn nào trong hệ thống.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php clinicPortalShellEnd(); ?>
</body>
</html>
