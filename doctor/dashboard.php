<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

function doctorDashboardEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function doctorDashboardStatus(string $status): array
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

$stmt = $pdo->prepare(
    'SELECT bs.*, ck.TenChuyenKhoa
     FROM BacSi bs
     LEFT JOIN ChuyenKhoa ck ON ck.MaCK = bs.MaCK
     WHERE bs.MaTK = ?
     LIMIT 1'
);
$stmt->execute([$_SESSION['user']['MaTK']]);
$bacSi = $stmt->fetch();

if (!$bacSi) {
    exit('Không tìm thấy hồ sơ bác sĩ.');
}

$maBS = $bacSi['MaBS'];

$stmt = $pdo->prepare(
    "SELECT
        COUNT(dl.MaDatLich) AS total,
        COALESCE(SUM(dl.TrangThai = 'ChoXacNhan'), 0) AS pending,
        COALESCE(SUM(dl.TrangThai = 'DaXacNhan'), 0) AS confirmed,
        COALESCE(SUM(dl.TrangThai = 'DaKham'), 0) AS completed,
        COALESCE(SUM(dl.TrangThai = 'VangMat'), 0) AS missed
     FROM LichKham lk
     LEFT JOIN DatLich dl
        ON dl.MaLich = lk.MaLich
       AND dl.TrangThai <> 'DaHuy'
     WHERE lk.MaBS = ?
       AND lk.NgayKham = CURDATE()"
);
$stmt->execute([$maBS]);
$todayStats = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM DatLich dl
     JOIN LichKham lk ON lk.MaLich = dl.MaLich
     WHERE lk.MaBS = ?
       AND lk.NgayKham >= CURDATE()
       AND dl.TrangThai = 'ChoXacNhan'"
);
$stmt->execute([$maBS]);
$pendingUpcoming = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT
        lk.NgayKham,
        COUNT(dl.MaDatLich) AS total
     FROM LichKham lk
     LEFT JOIN DatLich dl
        ON dl.MaLich = lk.MaLich
       AND dl.TrangThai <> 'DaHuy'
     WHERE lk.MaBS = ?
       AND lk.NgayKham BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
     GROUP BY lk.NgayKham
     ORDER BY lk.NgayKham"
);
$stmt->execute([$maBS]);
$weeklyRows = $stmt->fetchAll();
$weeklyMap = [];
foreach ($weeklyRows as $row) {
    $weeklyMap[$row['NgayKham']] = (int)$row['total'];
}
$weeklyStats = [];
$weeklyMax = 1;
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime('-' . $i . ' days'));
    $count = $weeklyMap[$date] ?? 0;
    $weeklyStats[] = ['date' => $date, 'count' => $count];
    $weeklyMax = max($weeklyMax, $count);
}

$stmt = $pdo->prepare(
    "SELECT
        dl.MaDatLich,
        bn.HoTen,
        bn.SoDienThoai,
        lk.NgayKham,
        lk.GioBatDau,
        lk.GioKetThuc,
        dl.TrangThai,
        dl.LyDoKham
     FROM DatLich dl
     JOIN BenhNhan bn ON bn.MaBN = dl.MaBN
     JOIN LichKham lk ON lk.MaLich = dl.MaLich
     WHERE lk.MaBS = ?
       AND lk.NgayKham = CURDATE()
       AND dl.TrangThai <> 'DaHuy'
     ORDER BY lk.GioBatDau ASC
     LIMIT 8"
);
$stmt->execute([$maBS]);
$todayAppointments = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT
        lk.NgayKham,
        lk.GioBatDau,
        lk.GioKetThuc,
        lk.SoLuongDaDat,
        lk.SoLuongToiDa,
        lk.TrangThai,
        pk.TenPhong
     FROM LichKham lk
     LEFT JOIN PhongKham pk ON pk.MaPK = lk.MaPK
     WHERE lk.MaBS = ?
       AND lk.NgayKham >= CURDATE()
       AND lk.TrangThai <> 'Huy'
     ORDER BY lk.NgayKham ASC, lk.GioBatDau ASC
     LIMIT 5"
);
$stmt->execute([$maBS]);
$upcomingSchedules = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT
        bn.HoTen,
        lk.NgayKham,
        lk.GioBatDau,
        dl.NgayDat,
        dl.TrangThai
     FROM DatLich dl
     JOIN BenhNhan bn ON bn.MaBN = dl.MaBN
     JOIN LichKham lk ON lk.MaLich = dl.MaLich
     WHERE lk.MaBS = ?
     ORDER BY dl.NgayDat DESC
     LIMIT 5"
);
$stmt->execute([$maBS]);
$recentBookings = $stmt->fetchAll();

$avatar = trim((string)($bacSi['AnhDaiDien'] ?? ''));
$avatarUrl = $avatar !== '' ? '../' . ltrim($avatar, '/') : '../assets/img/health/doctor.jpg';
$todayLabel = date('d/m/Y');
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard bác sĩ | Phòng khám</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/apple-touch-icon.png">
</head>
<body class="clinic-dashboard-page">
<div class="dashboard-shell">
    <aside class="dashboard-sidebar" id="dashboardSidebar">
        <a class="sidebar-brand" href="dashboard.php">
            <span class="brand-mark"><i class="bi bi-heart-pulse-fill"></i></span>
            <span><strong>TDTU Clinic</strong><small>Hệ thống quản lý phòng khám</small></span>
        </a>
        <div class="sidebar-caption">CÔNG VIỆC</div>
        <nav class="sidebar-nav">
            <a class="sidebar-link active" href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i><span>Tổng quan</span></a>
            <a class="sidebar-link" href="appointments.php"><i class="bi bi-calendar2-check"></i><span>Quản lý lịch khám</span><span class="sidebar-count"><?= (int)$pendingUpcoming ?></span></a>
            <a class="sidebar-link" href="schedules.php"><i class="bi bi-calendar-week"></i><span>Lịch làm việc</span></a>
            <a class="sidebar-link" href="history.php"><i class="bi bi-clipboard2-pulse"></i><span>Lịch sử khám</span></a>
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><i class="bi bi-headset"></i><div><strong>Hỗ trợ hệ thống</strong><span>Liên hệ quản trị viên</span></div></div>
            <a class="sidebar-link sidebar-logout" href="../logout.php"><i class="bi bi-box-arrow-left"></i><span>Đăng xuất</span></a>
        </div>
    </aside>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <button class="sidebar-toggle" type="button" aria-label="Mở menu" data-sidebar-toggle><i class="bi bi-list"></i></button>
            <div class="topbar-heading"><span class="eyebrow">TRANG QUẢN LÝ</span><h1>Tổng quan bác sĩ</h1></div>
            <div class="topbar-actions">
                <div class="topbar-date"><i class="bi bi-calendar3"></i><span><?= doctorDashboardEscape($todayLabel) ?></span></div>
                <div class="topbar-user">
                    <img src="<?= doctorDashboardEscape($avatarUrl) ?>" alt="Ảnh bác sĩ" onerror="this.src='../assets/img/health/doctor.jpg'">
                    <div><strong><?= doctorDashboardEscape($bacSi['HoTen']) ?></strong><small><?= doctorDashboardEscape($bacSi['TenChuyenKhoa'] ?? 'Bác sĩ') ?></small></div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <section class="welcome-row">
                <div><p class="welcome-kicker">CHÚC BẠN MỘT NGÀY LÀM VIỆC HIỆU QUẢ</p><h2>Xin chào, BS. <?= doctorDashboardEscape($bacSi['HoTen']) ?></h2><p>Theo dõi lịch khám và các công việc cần xử lý trong ngày.</p></div>
                <a class="btn btn-primary dashboard-primary-btn" href="appointments.php"><i class="bi bi-calendar-check me-2"></i>Mở lịch khám</a>
            </section>

            <section class="stats-grid stats-grid-doctor">
                <article class="stat-card stat-blue"><div class="stat-icon"><i class="bi bi-calendar2-week"></i></div><div class="stat-copy"><span>Lịch khám hôm nay</span><strong><?= (int)$todayStats['total'] ?></strong><small>Lượt đặt không bị hủy</small></div></article>
                <article class="stat-card stat-orange"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div class="stat-copy"><span>Chờ xác nhận</span><strong><?= (int)$todayStats['pending'] ?></strong><small>Trong ngày hôm nay</small></div></article>
                <article class="stat-card stat-green"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div class="stat-copy"><span>Đã khám</span><strong><?= (int)$todayStats['completed'] ?></strong><small>Hoàn tất trong hôm nay</small></div></article>
                <article class="stat-card stat-purple"><div class="stat-icon"><i class="bi bi-calendar2-plus"></i></div><div class="stat-copy"><span>Lịch cần xác nhận</span><strong><?= $pendingUpcoming ?></strong><small>Tất cả lịch từ hôm nay</small></div></article>
            </section>

            <section class="dashboard-grid dashboard-grid-main">
                <article class="dashboard-panel panel-chart">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-bar-chart-line"></i></span><div><h3>Lượt khám trong 7 ngày</h3><p>Số lượt đặt theo ngày, không tính lịch đã hủy</p></div></div><span class="panel-tag">7 ngày gần nhất</span></div>
                    <div class="weekly-chart" role="img" aria-label="Biểu đồ lượt khám trong 7 ngày gần nhất">
                        <?php foreach ($weeklyStats as $day): ?>
                            <div class="weekly-bar-item"><div class="weekly-bar-track"><div class="weekly-bar-fill" style="height: <?= max(3, (int)round(($day['count'] / $weeklyMax) * 100)) ?>%"><span><?= (int)$day['count'] ?></span></div></div><small><?= doctorDashboardEscape(date('d/m', strtotime($day['date']))) ?></small></div>
                        <?php endforeach; ?>
                    </div>
                </article>
                <article class="dashboard-panel panel-quick-actions">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-lightning-charge"></i></span><div><h3>Thao tác nhanh</h3><p>Truy cập các chức năng thường dùng</p></div></div></div>
                    <div class="quick-action-list">
                        <a href="appointments.php"><span class="quick-action-icon quick-blue"><i class="bi bi-calendar2-check"></i></span><span><strong>Quản lý lịch khám</strong><small>Xác nhận và xử lý lịch hẹn</small></span><i class="bi bi-chevron-right"></i></a>
                        <a href="schedules.php"><span class="quick-action-icon quick-green"><i class="bi bi-calendar-plus"></i></span><span><strong>Lịch làm việc</strong><small>Tạo và điều chỉnh khung giờ</small></span><i class="bi bi-chevron-right"></i></a>
                        <a href="history.php"><span class="quick-action-icon quick-purple"><i class="bi bi-journal-medical"></i></span><span><strong>Lịch sử khám</strong><small>Tra cứu các lượt khám đã hoàn tất</small></span><i class="bi bi-chevron-right"></i></a>
                    </div>
                </article>
            </section>

            <section class="dashboard-grid dashboard-grid-lower">
                <article class="dashboard-panel panel-table">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-calendar-day"></i></span><div><h3>Lịch khám hôm nay</h3><p>Danh sách bệnh nhân theo khung giờ</p></div></div><a class="panel-link" href="appointments.php">Xem tất cả <i class="bi bi-arrow-right"></i></a></div>
                    <div class="table-responsive dashboard-table-wrap">
                        <table class="table dashboard-table align-middle">
                            <thead><tr><th>Thời gian</th><th>Bệnh nhân</th><th>Số điện thoại</th><th>Trạng thái</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($todayAppointments as $appointment): ?>
                                <?php [$statusLabel, $statusClass] = doctorDashboardStatus($appointment['TrangThai']); ?>
                                <tr>
                                    <td><strong><?= doctorDashboardEscape(substr($appointment['GioBatDau'], 0, 5)) ?></strong><small class="table-subtext">đến <?= doctorDashboardEscape(substr($appointment['GioKetThuc'], 0, 5)) ?></small></td>
                                    <td><strong><?= doctorDashboardEscape($appointment['HoTen']) ?></strong><small class="table-subtext">Mã lịch #<?= (int)$appointment['MaDatLich'] ?></small></td>
                                    <td><?= doctorDashboardEscape($appointment['SoDienThoai']) ?></td>
                                    <td><span class="status-pill <?= doctorDashboardEscape($statusClass) ?>"><?= doctorDashboardEscape($statusLabel) ?></span></td>
                                    <td><a class="table-action" href="appointments.php">Chi tiết</a></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$todayAppointments): ?><tr><td colspan="5"><div class="empty-state"><i class="bi bi-calendar2-x"></i><strong>Chưa có lịch khám hôm nay</strong><span>Các lịch hẹn mới sẽ xuất hiện tại đây.</span></div></td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="dashboard-panel panel-schedule-list">
                    <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-calendar-week"></i></span><div><h3>Lịch làm việc sắp tới</h3><p>Các khung giờ đã mở</p></div></div><a class="panel-link" href="schedules.php">Quản lý <i class="bi bi-arrow-right"></i></a></div>
                    <div class="schedule-list">
                        <?php foreach ($upcomingSchedules as $schedule): ?>
                            <div class="schedule-item"><div class="schedule-date"><strong><?= doctorDashboardEscape(date('d', strtotime($schedule['NgayKham']))) ?></strong><small>Th<?= doctorDashboardEscape(date('m', strtotime($schedule['NgayKham']))) ?></small></div><div class="schedule-info"><strong><?= doctorDashboardEscape(substr($schedule['GioBatDau'], 0, 5)) ?> – <?= doctorDashboardEscape(substr($schedule['GioKetThuc'], 0, 5)) ?></strong><small><?= doctorDashboardEscape($schedule['TenPhong'] ?: 'Chưa phân phòng') ?></small></div><span class="capacity-pill"><?= (int)$schedule['SoLuongDaDat'] ?>/<?= (int)$schedule['SoLuongToiDa'] ?></span></div>
                        <?php endforeach; ?>
                        <?php if (!$upcomingSchedules): ?><div class="empty-state compact-empty"><i class="bi bi-calendar-plus"></i><strong>Chưa có lịch làm việc</strong><span>Tạo lịch để bệnh nhân có thể đặt hẹn.</span></div><?php endif; ?>
                    </div>
                </article>
            </section>

            <section class="dashboard-panel panel-activity">
                <div class="panel-heading"><div><span class="panel-icon"><i class="bi bi-activity"></i></span><div><h3>Đặt lịch gần đây</h3><p>Các lượt đặt mới nhất thuộc lịch của bạn</p></div></div><a class="panel-link" href="history.php">Quản lý lịch <i class="bi bi-arrow-right"></i></a></div>
                <div class="activity-list">
                    <?php foreach ($recentBookings as $booking): ?>
                        <?php [$statusLabel, $statusClass] = doctorDashboardStatus($booking['TrangThai']); ?>
                        <div class="activity-row"><span class="activity-dot"></span><div class="activity-content"><strong><?= doctorDashboardEscape($booking['HoTen']) ?></strong><span>Đặt lịch ngày <?= doctorDashboardEscape(date('d/m/Y', strtotime($booking['NgayKham']))) ?> lúc <?= doctorDashboardEscape(substr($booking['GioBatDau'], 0, 5)) ?></span></div><span class="activity-time"><?= doctorDashboardEscape(date('d/m H:i', strtotime($booking['NgayDat']))) ?></span><span class="status-pill <?= doctorDashboardEscape($statusClass) ?>"><?= doctorDashboardEscape($statusLabel) ?></span></div>
                    <?php endforeach; ?>
                    <?php if (!$recentBookings): ?><div class="empty-state compact-empty"><i class="bi bi-inbox"></i><strong>Chưa có hoạt động</strong><span>Các lượt đặt lịch mới sẽ được cập nhật tại đây.</span></div><?php endif; ?>
                </div>
            </section>
            <footer class="dashboard-footer">© <?= date('Y') ?> TDTU Clinic · Hệ thống quản lý phòng khám <span>Cập nhật lúc <?= date('H:i') ?></span></footer>
        </div>
    </main>
</div>
<div class="sidebar-backdrop" data-sidebar-close></div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>
