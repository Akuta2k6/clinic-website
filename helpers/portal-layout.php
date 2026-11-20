<?php
function clinicPortalEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function clinicPortalShellStart(string $role, string $pageTitle, string $activePage): void
{
    global $pdo;

    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $isDoctor = $role === 'BacSi';
    $name = 'Quản trị viên';
    $subtitle = 'Quản trị hệ thống';
    $avatarUrl = '';
    $pendingCount = 0;

    if ($isDoctor) {
        $stmt = $pdo->prepare(
            'SELECT bs.HoTen, bs.AnhDaiDien, ck.TenChuyenKhoa
             FROM BacSi bs
             LEFT JOIN ChuyenKhoa ck ON ck.MaCK = bs.MaCK
             WHERE bs.MaTK = ?
             LIMIT 1'
        );
        $stmt->execute([$_SESSION['user']['MaTK']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $name = $profile['HoTen'] ?? ($_SESSION['user']['TenDangNhap'] ?? 'Bác sĩ');
        $subtitle = $profile['TenChuyenKhoa'] ?? 'Bác sĩ';
        $avatar = trim((string)($profile['AnhDaiDien'] ?? ''));
        $avatarUrl = $avatar !== '' ? '../' . ltrim($avatar, '/') : '../assets/img/health/doctor.jpg';

        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM DatLich dl
             JOIN LichKham lk ON lk.MaLich = dl.MaLich
             JOIN BacSi bs ON bs.MaBS = lk.MaBS
             WHERE bs.MaTK = ?
               AND lk.NgayKham >= CURDATE()
               AND dl.TrangThai = 'ChoXacNhan'"
        );
        $stmt->execute([$_SESSION['user']['MaTK']]);
        $pendingCount = (int)$stmt->fetchColumn();

        $nav = [
            ['key' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'bi-grid-1x2-fill', 'label' => 'Tổng quan'],
            ['key' => 'appointments', 'href' => 'appointments.php', 'icon' => 'bi-calendar2-check', 'label' => 'Quản lý lịch khám', 'count' => $pendingCount],
            ['key' => 'schedules', 'href' => 'schedules.php', 'icon' => 'bi-calendar-week', 'label' => 'Lịch làm việc'],
            ['key' => 'history', 'href' => 'history.php', 'icon' => 'bi-clipboard2-pulse', 'label' => 'Lịch sử khám']
        ];
        $sectionLabel = 'CÔNG VIỆC';
        $eyebrow = 'TRANG QUẢN LÝ BÁC SĨ';
        $userLabel = 'Bác sĩ';
        $brandHref = 'dashboard.php';
        $helpTitle = 'Hỗ trợ hệ thống';
        $helpText = 'Liên hệ quản trị viên';
    } else {
        $name = $_SESSION['user']['TenDangNhap'] ?? 'Quản trị viên';
        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM DatLich dl
             JOIN LichKham lk ON lk.MaLich = dl.MaLich
             WHERE lk.NgayKham >= CURDATE()
               AND dl.TrangThai = 'ChoXacNhan'"
        );
        $pendingCount = (int)$stmt->fetchColumn();
        $nav = [
            ['key' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'bi-grid-1x2-fill', 'label' => 'Tổng quan'],
            ['key' => 'doctors', 'href' => 'doctors.php', 'icon' => 'bi-person-badge', 'label' => 'Quản lý bác sĩ'],
            ['key' => 'specialties', 'href' => 'specialties.php', 'icon' => 'bi-hospital', 'label' => 'Chuyên khoa'],
            ['key' => 'rooms', 'href' => 'rooms.php', 'icon' => 'bi-door-open', 'label' => 'Phòng khám'],
            ['key' => 'appointments', 'href' => 'appointments.php', 'icon' => 'bi-calendar2-check', 'label' => 'Lịch hẹn', 'count' => $pendingCount],
            ['key' => 'medicines', 'href' => 'medicines.php', 'icon' => 'bi-capsule', 'label' => 'Quản lý thuốc']
        ];
        $sectionLabel = 'QUẢN TRỊ HỆ THỐNG';
        $eyebrow = 'TRUNG TÂM ĐIỀU HÀNH';
        $userLabel = 'Quản trị viên';
        $brandHref = 'dashboard.php';
        $helpTitle = 'Quản trị hệ thống';
        $helpText = 'Kiểm soát hoạt động phòng khám';
    }

    echo '<div class="dashboard-shell">';
    echo '<aside class="dashboard-sidebar" id="dashboardSidebar">';
    echo '<a class="sidebar-brand" href="' . $brandHref . '"><span class="brand-mark"><i class="bi bi-heart-pulse-fill"></i></span><span><strong>TDTU Clinic</strong><small>Hệ thống quản lý phòng khám</small></span></a>';
    echo '<div class="sidebar-caption">' . clinicPortalEscape($sectionLabel) . '</div><nav class="sidebar-nav">';

    foreach ($nav as $item) {
        $activeClass = $activePage === $item['key'] ? ' active' : '';
        echo '<a class="sidebar-link' . $activeClass . '" href="' . clinicPortalEscape($item['href']) . '"><i class="bi ' . clinicPortalEscape($item['icon']) . '"></i><span>' . clinicPortalEscape($item['label']) . '</span>';
        if (isset($item['count']) && $item['count'] > 0) {
            echo '<span class="sidebar-count">' . (int)$item['count'] . '</span>';
        }
        echo '</a>';
    }

    echo '</nav><div class="sidebar-bottom"><div class="sidebar-help"><i class="bi ' . ($isDoctor ? 'bi-headset' : 'bi-shield-check') . '"></i><div><strong>' . clinicPortalEscape($helpTitle) . '</strong><span>' . clinicPortalEscape($helpText) . '</span></div></div>';
    echo '<a class="sidebar-link sidebar-logout" href="../logout.php"><i class="bi bi-box-arrow-left"></i><span>Đăng xuất</span></a></div></aside>';
    echo '<main class="dashboard-main"><header class="dashboard-topbar">';
    echo '<button class="sidebar-toggle" type="button" aria-label="Mở menu" data-sidebar-toggle><i class="bi bi-list"></i></button>';
    echo '<div class="topbar-heading"><span class="eyebrow">' . clinicPortalEscape($eyebrow) . '</span><h1>' . clinicPortalEscape($pageTitle) . '</h1></div>';
    echo '<div class="topbar-actions"><div class="topbar-date"><i class="bi bi-calendar3"></i><span>' . date('d/m/Y') . '</span></div><div class="topbar-user">';

    if ($isDoctor) {
        echo '<img src="' . clinicPortalEscape($avatarUrl) . '" alt="Ảnh bác sĩ" onerror="this.src=\'../assets/img/health/doctor.jpg\'"><div><strong>' . clinicPortalEscape($name) . '</strong><small>' . clinicPortalEscape($subtitle) . '</small></div>';
    } else {
        echo '<span class="admin-avatar"><i class="bi bi-person-fill"></i></span><div><strong>' . clinicPortalEscape($name) . '</strong><small>' . clinicPortalEscape($userLabel) . '</small></div>';
    }

    echo '</div></div></header><div class="dashboard-content portal-content">';
}

function clinicPortalShellEnd(): void
{
    echo '</div></main></div><div class="sidebar-backdrop" data-sidebar-close></div><script src="../assets/js/dashboard.js"></script>';
}
