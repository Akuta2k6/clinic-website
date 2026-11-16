<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/portal-layout.php';

$stmt = $pdo->prepare('SELECT MaBS, HoTen FROM BacSi WHERE MaTK=?');
$stmt->execute([$_SESSION['user']['MaTK']]);
$bs = $stmt->fetch();

if (!$bs) {
    exit('Không tìm thấy hồ sơ bác sĩ.');
}

$stmt = $pdo->prepare(
    "SELECT
        dl.MaDatLich,
        bn.HoTen,
        bn.SoDienThoai,
        bn.Email,
        lk.NgayKham,
        lk.GioBatDau,
        lk.GioKetThuc,
        dl.LyDoKham,
        dl.TrangThai
    FROM DatLich dl
    JOIN BenhNhan bn ON dl.MaBN = bn.MaBN
    JOIN LichKham lk ON dl.MaLich = lk.MaLich
    WHERE lk.MaBS = ?
      AND dl.TrangThai IN ('ChoXacNhan', 'DaXacNhan')
    ORDER BY lk.NgayKham ASC, lk.GioBatDau ASC"
);
$stmt->execute([$bs['MaBS']]);
$rows = $stmt->fetchAll();

$confirmed = isset($_GET['confirmed']);
$cancelled = isset($_GET['cancelled']);
$cancelError = isset($_GET['cancel_error']);
$statusError = isset($_GET['status_error']);
$sent = (int)($_GET['sent'] ?? 0);
$failed = (int)($_GET['failed'] ?? 0);
$skipped = (int)($_GET['skipped'] ?? 0);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Quản lý lịch khám</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="clinic-dashboard-page">
<?php clinicPortalShellStart('BacSi', 'Quản lý lịch khám', 'appointments'); ?>
    <div class="container py-4">
        <?php if ($confirmed): ?>
            <div class="alert alert-success">
                Đã xác nhận lịch khám thành công.
            </div>
        <?php endif; ?>

        <?php if ($cancelled): ?>
            <div class="alert alert-success">
                Đã hủy lịch khám thành công.
                Email gửi thành công: <?= $sent ?>;
                gửi thất bại: <?= $failed ?>;
                không có email hợp lệ: <?= $skipped ?>.
            </div>
        <?php endif; ?>

        <?php if ($cancelError || $statusError): ?>
            <div class="alert alert-danger">
                Không thể cập nhật trạng thái lịch khám. Lịch có thể đã được xử lý trước đó hoặc dữ liệu vừa thay đổi. Vui lòng tải lại trang và kiểm tra trạng thái.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Bệnh nhân</th>
                        <th>Liên hệ</th>
                        <th>Ngày giờ</th>
                        <th>Lý do khám</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['HoTen'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars($r['SoDienThoai'], ENT_QUOTES, 'UTF-8') ?><br>
                                <?= htmlspecialchars($r['Email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(date('d/m/Y', strtotime($r['NgayKham'])), ENT_QUOTES, 'UTF-8') ?><br>
                                <?= substr($r['GioBatDau'], 0, 5) ?> - <?= substr($r['GioKetThuc'], 0, 5) ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($r['LyDoKham'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>
                            <td>
                                <?php if ($r['TrangThai'] === 'ChoXacNhan'): ?>
                                    <span class="badge bg-warning text-dark">Chờ xác nhận</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark">Đã xác nhận</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ($r['TrangThai'] === 'ChoXacNhan'): ?>
                                        <form action="update-status.php" method="post">
                                            <input type="hidden" name="MaDatLich" value="<?= (int)$r['MaDatLich'] ?>">
                                            <input type="hidden" name="TrangThai" value="DaXacNhan">
                                            <button type="submit" class="btn btn-sm btn-primary">Xác nhận</button>
                                        </form>
                                    <?php endif; ?>
                                    <a class="btn btn-sm btn-success" href="medical-record.php?appointment=<?= (int)$r['MaDatLich'] ?>">Khám</a>
                                    <form action="update-status.php" method="post" onsubmit="return confirm('Bạn có chắc chắn muốn hủy lịch khám này không? Hệ thống sẽ cập nhật trạng thái và gửi email thông báo cho bệnh nhân.');">
                                        <input type="hidden" name="MaDatLich" value="<?= (int)$r['MaDatLich'] ?>">
                                        <input type="hidden" name="TrangThai" value="DaHuy">
                                        <button type="submit" class="btn btn-sm btn-danger">Hủy lịch</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Hiện không có lịch khám đang chờ xử lý.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php clinicPortalShellEnd(); ?>
</body>
</html>