<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('Admin');
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

function adminDashboardEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function adminDashboardStatus(string $status): array
{
    $map = [
        'ChoXacNhan' => ['Chờ xác nhận', 'status-pending'],
        'DaXacNhan' => ['Đã xác nhận', 'status-confirmed'],
        'DaKham' => ['Đã khám', 'status-completed'],
        'DaHuy' => ['Đã hủy', 'status-cancelled'],
        'VangMat' => ['Vắng mặt', 'status-missed']
    ];
    return $map[$status] ?? [$status, 'status-neutral'];
}

$stats = [];
$stats['doctors'] = (int)$pdo->query('SELECT COUNT(*) FROM BacSi WHERE TrangThai = 1')->fetchColumn();
$stats['patients'] = (int)$pdo->query('SELECT COUNT(*) FROM BenhNhan')->fetchColumn();
$stats['today'] = (int)$pdo->query(
    "SELECT COUNT(*)
     FROM DatLich dl
     JOIN LichKham lk ON lk.MaLich = dl.MaLich
     WHERE lk.NgayKham = CURDATE()
       AND dl.TrangThai <> 'DaHuy'"
)->fetchColumn();
$stats['pending'] = (int)$pdo->query(
    "SELECT COUNT(*)
     FROM DatLich dl
     JOIN LichKham lk ON lk.MaLich = dl.MaLich
     WHERE lk.NgayKham >= CURDATE()
       AND dl.TrangThai = 'ChoXacNhan'"
)->fetchColumn();
$stats['completedMonth'] = (int)$pdo->query(
    "SELECT COUNT(*)
     FROM DatLich dl
     JOIN LichKham lk ON lk.MaLich = dl.MaLich
     WHERE lk.NgayKham >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
       AND lk.NgayKham < DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)
       AND dl.TrangThai = 'DaKham'"
)->fetchColumn();
$stats['medicines'] = (int)$pdo->query('SELECT COUNT(*) FROM Thuoc WHERE TrangThai = 1')->fetchColumn();

$stmt = $pdo->query(
    "SELECT DATE(dl.NgayDat) AS booking_date, COUNT(*) AS total
     FROM DatLich dl
     WHERE dl.NgayDat >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
       AND dl.NgayDat < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
     GROUP BY DATE(dl.NgayDat)
     ORDER BY booking_date"
);
$trendRows = $stmt->fetchAll();
$trendMap = [];
foreach ($trendRows as $row) {
    $trendMap[$row['booking_date']] = (int)$row['total'];
}
$bookingTrend = [];
$trendMax = 1;
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime('-' . $i . ' days'));
    $count = $trendMap[$date] ?? 0;
    $bookingTrend[] = ['date' => $date, 'count' => $count];
    $trendMax = max($trendMax, $count);
}

$stmt = $pdo->query(
    "SELECT dl.TrangThai, COUNT(*) AS total
     FROM DatLich dl
     JOIN LichKham lk ON lk.MaLich = dl.MaLich
     WHERE lk.NgayKham >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
       AND lk.NgayKham <= CURDATE()
     GROUP BY dl.TrangThai"
);
$statusRows = $stmt->fetchAll();
$statusTotals = ['DaKham' => 0, 'DaXacNhan' => 0, 'ChoXacNhan' => 0, 'DaHuy' => 0, 'VangMat' => 0];
foreach ($statusRows as $row) {
    if (array_key_exists($row['TrangThai'], $statusTotals)) {
        $statusTotals[$row['TrangThai']] = (int)$row['total'];
    }
}
$statusTotal = array_sum($statusTotals);
$statusSegments = [];
$statusColors = ['DaKham' => '#16a477', 'DaXacNhan' => '#2785e8', 'ChoXacNhan' => '#f2a541', 'DaHuy' => '#e56b75', 'VangMat' => '#8b78d6'];
$statusLabels = ['DaKham' => 'Đã khám', 'DaXacNhan' => 'Đã xác nhận', 'ChoXacNhan' => 'Chờ xác nhận', 'DaHuy' => 'Đã hủy', 'VangMat' => 'Vắng mặt'];
$gradientParts = [];
$gradientPosition = 0;
foreach ($statusTotals as $key => $value) {
    if ($value <= 0 || $statusTotal <= 0) {
        continue;
    }
    $nextPosition = $gradientPosition + ($value / $statusTotal * 100);
    $gradientParts[] = $statusColors[$key] . ' ' . round($gradientPosition, 2) . '% ' . round($nextPosition, 2) . '%';
    $gradientPosition = $nextPosition;
    $statusSegments[] = ['key' => $key, 'label' => $statusLabels[$key], 'value' => $value, 'percent' => round($value / $statusTotal * 100, 1), 'color' => $statusColors[$key]];
}
$donutStyle = $gradientParts ? 'conic-gradient(' . implode(', ', $gradientParts) . ')' : 'conic-gradient(#e8edf4 0% 100%)';

$stmt = $pdo->query(
    "SELECT
        dl.MaDatLich,
        bn.HoTen AS BenhNhan,
        bn.SoDienThoai,
        bs.HoTen AS BacSi,
        ck.TenChuyenKhoa,
        lk.GioBatDau,
        lk.GioKetThuc,
        dl.TrangThai
     FROM DatLich dl
     JOIN BenhNhan bn ON bn.MaBN = dl.MaBN
     JOIN LichKham lk ON lk.MaLich = dl.MaLich
     JOIN BacSi bs ON bs.MaBS = lk.MaBS
     LEFT JOIN ChuyenKhoa ck ON ck.MaCK = bs.MaCK
     WHERE lk.NgayKham = CURDATE()
     ORDER BY lk.GioBatDau ASC
     LIMIT 8"
);
$todayAppointments = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT
        bn.HoTen AS BenhNhan,
        bs.HoTen AS BacSi,
        ck.TenChuyenKhoa,
        lk.NgayKham,
        lk.GioBatDau,
        dl.NgayDat,
        dl.TrangThai
     FROM DatLich dl
     JOIN BenhNhan bn ON bn.MaBN = dl.MaBN
     JOIN LichKham lk ON lk.MaLich = dl.MaLich
     JOIN BacSi bs ON bs.MaBS = lk.MaBS
     LEFT JOIN ChuyenKhoa ck ON ck.MaCK = bs.MaCK
     ORDER BY dl.NgayDat DESC
     LIMIT 5"
);
$recentBookings = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT ck.TenChuyenKhoa, COUNT(dl.MaDatLich) AS total
     FROM ChuyenKhoa ck
     LEFT JOIN BacSi bs ON bs.MaCK = ck.MaCK
     LEFT JOIN LichKham lk
        ON lk.MaBS = bs.MaBS
       AND lk.NgayKham >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
       AND lk.NgayKham < DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)
     LEFT JOIN DatLich dl
        ON dl.MaLich = lk.MaLich
       AND dl.TrangThai <> 'DaHuy'
     WHERE ck.TrangThai = 1
     GROUP BY ck.MaCK, ck.TenChuyenKhoa
     ORDER BY total DESC, ck.TenChuyenKhoa ASC
     LIMIT 5"
);
$specialtyStats = $stmt->fetchAll();
$specialtyMax = 1;
foreach ($specialtyStats as $specialty) {
    $specialtyMax = max($specialtyMax, (int)$specialty['total']);
}

$alerts = [];
$alerts['pending'] = $stats['pending'];
$alerts['fullSchedules'] = (int)$pdo->query(
    "SELECT COUNT(*) FROM LichKham
     WHERE NgayKham >= CURDATE()
       AND TrangThai = 'Day'"
)->fetchColumn();
$alerts['lowStock'] = (int)$pdo->query(
    'SELECT COUNT(*) FROM Thuoc WHERE TrangThai = 1 AND SoLuongTon <= 5'
)->fetchColumn();
$alerts['expired'] = (int)$pdo->query(
    'SELECT COUNT(*) FROM Thuoc WHERE TrangThai = 1 AND HanSuDung IS NOT NULL AND HanSuDung < CURDATE()'
)->fetchColumn();

$adminName = $_SESSION['user']['TenDangNhap'] ?? 'Quản trị viên';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard quản trị | Phòng khám</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="clinic-dashboard-page">
<div class="dashboard-shell">
    <aside class="dashboard-sidebar" id="dashboardSidebar">
        <a class="sidebar-brand" href="dashboard.php"><span class="brand-mark"><i class="bi bi-heart-pulse-fill"></i></span><span><strong>ClinicCare</strong><small>Hệ thống quản lý phòng khám</small></span></a>
        <div class="sidebar-caption">QUẢN TRỊ HỆ THỐNG</div>
        <nav class="sidebar-nav">
            <a class="sidebar-link active" href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i><span>Tổng quan</span></a>
            <a class="sidebar-link" href="doctors.php"><i class="bi bi-person-badge"></i><span>Quản lý bác sĩ</span></a>
            <a class="sidebar-link" href="specialties.php"><i class="bi bi-hospital"></i><span>Chuyên khoa</span></a>
            <a class="sidebar-link" href="rooms.php"><i class="bi bi-door-open"></i><span>Phòng khám</span></a>
            <a class="sidebar-link" href="appointments.php"><i class="bi bi-calendar2-check"></i><span>Lịch hẹn</span><span class="sidebar-count"><?= $stats['pending'] ?></span></a>
            <a class="sidebar-link" href="medicines.php"><i class="bi bi-capsule"></i><span>Quản lý thuốc</span></a>
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><i class="bi bi-shield-check"></i><div><strong>Quản trị hệ thống</strong><span>Kiểm soát hoạt động phòng khám</span></div></div>
            <a class="sidebar-link sidebar-logout" href="../logout.php"><i class="bi bi-box-arrow-left"></i><span>Đăng xuất</span></a>
        </div>
    </aside>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <button class="sidebar-toggle" type="button" aria-label="Mở menu" data-sidebar-toggle><i class="bi bi-list"></i></button>
            <div class="topbar-heading"><span class="eyebrow">TRUNG TÂM ĐIỀU HÀNH</span><h1>Dashboard Admin</h1></div>
            <div class="topbar-actions"><div class="topbar-date"><i class="bi bi-calendar3"></i><span><?= date('d/m/Y') ?></span></div><div class="topbar-user admin-user"><span class="admin-avatar"><i class="bi bi-person-fill"></i></span><div><strong><?= adminDashboardEscape($adminName) ?></strong><small>Quản trị viên</small></div></div></div>
        </header>

        <div class="dashboard-content">
            <section class="welcome-row"><div><p class="welcome-kicker">TỔNG QUAN VẬN HÀNH</p><h2>Xin chào, <?= adminDashboardEscape($adminName) ?></h2><p>Theo dõi tình hình hoạt động và các công việc cần chú ý của phòng khám.</p></div><a class="btn btn-primary dashboard-primary-btn" href="appointments.php"><i class="bi bi-calendar2-check me-2"></i>Quản lý lịch hẹn</a></section>

            <section class="stats-grid stats-grid-admin">
                <article class="stat-card stat-blue"><div class="stat-icon"><i class="bi bi-person-heart"></i></div><div class="stat-copy"><span>Tổng bệnh nhân</span><strong><?= number_format($stats['patients']) ?></strong><small>Hồ sơ bệnh nhân trong hệ thống</small></div></article>
                <article class="stat-card stat-green"><div class="stat-icon"><i class="bi bi-person-badge"></i></div><div class="stat-copy"><span>Bác sĩ hoạt động</span><strong><?= number_format($stats['doctors']) ?></strong><small>Tài khoản bác sĩ đang hoạt động</small></div></article>
                <article class="stat-card stat-purple"><div class="stat-icon"><i class="bi bi-calendar2-week"></i></div><div class="stat-copy"><span>Lịch khám hôm nay</span><strong><?= number_format($stats['today']) ?></strong><small>Không tính lượt đã hủy</small></div></article>
                <article class="stat-card stat-orange"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div class="stat-copy"><span>Chờ xác nhận</span><strong><?= number_format($stats['pending']) ?></strong><small>Các lịch từ hôm nay trở đi</small></div></article>
                <article class="stat-card stat-teal"><div class="stat-icon"><i class="bi bi-clipboard2-check"></i></div><div class="stat-copy"><span>Đã khám tháng này</span><strong><?= number_format($stats['completedMonth']) ?></strong><small>Lượt khám đã hoàn tất</small></div></article>
                <article class="stat-card stat-pink"><div class="stat-icon"><i class="bi bi-capsule"></i></div><div class="stat-copy"><span>Loại thuốc hoạt động</span><strong><?= number_format($stats['medicines']) ?></strong><small>Danh mục thuốc đang sử dụng</small></div></article>
            </section>

            <section class="dashboard-grid dashboard-grid-admin-charts">
                <article class="dashboard-panel panel-chart">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-graph-up-arrow"></i></span><div><h3>Xu hướng đặt lịch</h3><p>Số lượt đặt mới trong 7 ngày gần nhất</p></div></div><span class="panel-tag">7 ngày</span></div>
                    <div class="weekly-chart" role="img" aria-label="Biểu đồ số lượt đặt lịch trong 7 ngày gần nhất">
                        <?php foreach ($bookingTrend as $day): ?>
                            <div class="weekly-bar-item"><div class="weekly-bar-track"><div class="weekly-bar-fill" style="height: <?= max(3, (int)round(($day['count'] / $trendMax) * 100)) ?>%"><span><?= (int)$day['count'] ?></span></div></div><small><?= adminDashboardEscape(date('d/m', strtotime($day['date']))) ?></small></div>
                        <?php endforeach; ?>
                    </div>
                </article>
                <article class="dashboard-panel panel-status-chart">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-pie-chart"></i></span><div><h3>Trạng thái lịch khám</h3><p>Trong 30 ngày gần nhất</p></div></div></div>
                    <div class="status-chart-layout"><div class="status-donut" style="background: <?= adminDashboardEscape($donutStyle) ?>"><div><strong><?= number_format($statusTotal) ?></strong><span>Tổng lượt</span></div></div><div class="status-legend">
                        <?php foreach ($statusSegments as $segment): ?><div><span class="legend-label"><i style="background: <?= adminDashboardEscape($segment['color']) ?>"></i><?= adminDashboardEscape($segment['label']) ?></span><strong><?= number_format($segment['value']) ?> <small>(<?= adminDashboardEscape($segment['percent']) ?>%)</small></strong></div><?php endforeach; ?>
                        <?php if (!$statusSegments): ?><span class="text-muted small">Chưa có dữ liệu trong khoảng thời gian này.</span><?php endif; ?>
                    </div></div>
                </article>
            </section>

            <section class="dashboard-grid dashboard-grid-admin-lower">
                <article class="dashboard-panel panel-table">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-calendar-day"></i></span><div><h3>Lịch khám hôm nay</h3><p>Tổng hợp lịch hẹn theo thời gian</p></div></div><a class="panel-link" href="appointments.php">Xem tất cả <i class="bi bi-arrow-right"></i></a></div>
                    <div class="table-responsive dashboard-table-wrap"><table class="table dashboard-table align-middle"><thead><tr><th>Thời gian</th><th>Bệnh nhân</th><th>Bác sĩ</th><th>Chuyên khoa</th><th>Trạng thái</th></tr></thead><tbody>
                        <?php foreach ($todayAppointments as $appointment): ?>
                            <?php [$statusLabel, $statusClass] = adminDashboardStatus($appointment['TrangThai']); ?>
                            <tr><td><strong><?= adminDashboardEscape(substr($appointment['GioBatDau'], 0, 5)) ?></strong><small class="table-subtext">đến <?= adminDashboardEscape(substr($appointment['GioKetThuc'], 0, 5)) ?></small></td><td><strong><?= adminDashboardEscape($appointment['BenhNhan']) ?></strong><small class="table-subtext"><?= adminDashboardEscape($appointment['SoDienThoai']) ?></small></td><td><?= adminDashboardEscape($appointment['BacSi']) ?></td><td><?= adminDashboardEscape($appointment['TenChuyenKhoa'] ?? 'Chưa phân loại') ?></td><td><span class="status-pill <?= adminDashboardEscape($statusClass) ?>"><?= adminDashboardEscape($statusLabel) ?></span></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$todayAppointments): ?><tr><td colspan="5"><div class="empty-state"><i class="bi bi-calendar2-x"></i><strong>Chưa có lịch khám hôm nay</strong><span>Lịch hẹn mới sẽ hiển thị tại đây.</span></div></td></tr><?php endif; ?>
                    </tbody></table></div>
                </article>
                <article class="dashboard-panel panel-alerts">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-bell"></i></span><div><h3>Cảnh báo & nhắc nhở</h3><p>Các mục cần quản trị viên lưu ý</p></div></div></div>
                    <div class="alert-list">
                        <a class="alert-item alert-warning" href="appointments.php"><span class="alert-icon"><i class="bi bi-hourglass-split"></i></span><span><strong><?= number_format($alerts['pending']) ?> lịch chờ xác nhận</strong><small>Các lịch khám chưa được tiếp nhận</small></span><i class="bi bi-chevron-right"></i></a>
                        <a class="alert-item alert-info" href="appointments.php"><span class="alert-icon"><i class="bi bi-calendar2-x"></i></span><span><strong><?= number_format($alerts['fullSchedules']) ?> khung giờ đã đủ chỗ</strong><small>Khung lịch đang ở trạng thái đầy</small></span><i class="bi bi-chevron-right"></i></a>
                        <a class="alert-item alert-warning" href="medicines.php"><span class="alert-icon"><i class="bi bi-capsule"></i></span><span><strong><?= number_format($alerts['lowStock']) ?> loại thuốc tồn kho thấp</strong><small>Ngưỡng cảnh báo: còn không quá 5 đơn vị</small></span><i class="bi bi-chevron-right"></i></a>
                        <a class="alert-item alert-danger" href="medicines.php"><span class="alert-icon"><i class="bi bi-exclamation-triangle"></i></span><span><strong><?= number_format($alerts['expired']) ?> loại thuốc đã hết hạn</strong><small>Kiểm tra và cập nhật danh mục thuốc</small></span><i class="bi bi-chevron-right"></i></a>
                    </div>
                </article>
            </section>

            <section class="dashboard-grid dashboard-grid-admin-bottom">
                <article class="dashboard-panel panel-activity">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-activity"></i></span><div><h3>Hoạt động đặt lịch gần đây</h3><p>Các lượt đặt mới nhất trên hệ thống</p></div></div><a class="panel-link" href="appointments.php">Quản lý <i class="bi bi-arrow-right"></i></a></div>
                    <div class="activity-list">
                        <?php foreach ($recentBookings as $booking): ?>
                            <?php [$statusLabel, $statusClass] = adminDashboardStatus($booking['TrangThai']); ?>
                            <div class="activity-row"><span class="activity-dot"></span><div class="activity-content"><strong><?= adminDashboardEscape($booking['BenhNhan']) ?></strong><span>Đặt lịch với <?= adminDashboardEscape($booking['BacSi']) ?> · <?= adminDashboardEscape($booking['TenChuyenKhoa'] ?? 'Chưa phân loại') ?></span></div><span class="activity-time"><?= adminDashboardEscape(date('d/m H:i', strtotime($booking['NgayDat']))) ?></span><span class="status-pill <?= adminDashboardEscape($statusClass) ?>"><?= adminDashboardEscape($statusLabel) ?></span></div>
                        <?php endforeach; ?>
                        <?php if (!$recentBookings): ?><div class="empty-state compact-empty"><i class="bi bi-inbox"></i><strong>Chưa có hoạt động đặt lịch</strong><span>Các lượt đặt mới sẽ xuất hiện tại đây.</span></div><?php endif; ?>
                    </div>
                </article>
                <article class="dashboard-panel panel-specialties">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-hospital"></i></span><div><h3>Lượt khám theo chuyên khoa</h3><p>Thống kê trong tháng hiện tại</p></div></div><a class="panel-link" href="specialties.php">Chi tiết <i class="bi bi-arrow-right"></i></a></div>
                    <div class="specialty-list">
                        <?php foreach ($specialtyStats as $specialty): $specialtyCount = (int)$specialty['total']; ?>
                            <div class="specialty-row"><div><strong><?= adminDashboardEscape($specialty['TenChuyenKhoa']) ?></strong><span><?= number_format($specialtyCount) ?> lượt</span></div><div class="specialty-track"><span style="width: <?= (int)round(($specialtyCount / $specialtyMax) * 100) ?>%"></span></div></div>
                        <?php endforeach; ?>
                        <?php if (!$specialtyStats): ?><div class="empty-state compact-empty"><i class="bi bi-bar-chart"></i><strong>Chưa có dữ liệu</strong><span>Thống kê sẽ xuất hiện khi có lượt khám.</span></div><?php endif; ?>
                    </div>
                </article>
            </section>
            <footer class="dashboard-footer">© <?= date('Y') ?> ClinicCare · Hệ thống quản lý phòng khám <span>Cập nhật lúc <?= date('H:i') ?></span></footer>
        </div>
    </main>
</div>
<div class="sidebar-backdrop" data-sidebar-close></div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>
