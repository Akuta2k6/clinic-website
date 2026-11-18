<?php
session_start();
if (!empty($_SESSION['user'])) {
    $target = $_SESSION['user']['VaiTro'] === 'Admin' ? 'admin/dashboard.php' : 'doctor/dashboard.php';
    header('Location: ' . $target);
    exit;
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#12345a">
    <title>Đăng nhập | ClinicCare</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/public-pages.css" rel="stylesheet">
</head>
<body class="clinic-public-body">
<header class="clinic-header">
    <div class="clinic-header-inner">
        <a class="clinic-brand" href="index.html">
            <span class="clinic-brand-mark"><i class="bi bi-heart-pulse-fill"></i></span>
            <span class="clinic-brand-copy"><strong>ClinicCare</strong><small>Hệ thống quản lý phòng khám</small></span>
        </a>
        <nav class="clinic-nav" aria-label="Điều hướng chính">
            <a href="index.html"><i class="bi bi-house-door"></i><span>Trang chủ</span></a>
            <a href="appointment.php"><i class="bi bi-calendar2-plus"></i><span>Đặt lịch khám</span></a>
            <a href="patient/lookup.php"><i class="bi bi-search"></i><span>Tra cứu lịch</span></a>
        </nav>
    </div>
</header>

<main class="clinic-main clinic-login-main">
    <div class="clinic-login-layout">
        <section class="clinic-login-intro">
            <div class="clinic-intro-kicker"><span></span> CỔNG THÔNG TIN NỘI BỘ</div>
            <div class="clinic-login-illustration">
                <div class="clinic-illustration-orbit clinic-orbit-one"></div>
                <div class="clinic-illustration-orbit clinic-orbit-two"></div>
                <div class="clinic-illustration-cross"><i class="bi bi-heart-pulse-fill"></i></div>
                <span class="clinic-float-icon clinic-float-calendar"><i class="bi bi-calendar2-check"></i></span>
                <span class="clinic-float-icon clinic-float-clipboard"><i class="bi bi-clipboard2-pulse"></i></span>
                <span class="clinic-float-icon clinic-float-shield"><i class="bi bi-shield-check"></i></span>
            </div>
            <h1>Chăm sóc tận tâm.<br><span>Quản lý chuyên nghiệp.</span></h1>
            <p>Không gian làm việc dành cho đội ngũ bác sĩ và quản trị viên, hỗ trợ quản lý lịch khám và thông tin điều trị trên cùng một hệ thống.</p>
            <div class="clinic-login-feature-list">
                <div><i class="bi bi-check-circle-fill"></i><span>Quản lý lịch hẹn và lịch làm việc</span></div>
                <div><i class="bi bi-check-circle-fill"></i><span>Theo dõi hồ sơ khám và đơn thuốc</span></div>
                <div><i class="bi bi-check-circle-fill"></i><span>Cập nhật hoạt động phòng khám</span></div>
            </div>
        </section>

        <section class="clinic-login-card-wrap">
            <div class="clinic-login-card">
                <div class="clinic-login-card-icon"><i class="bi bi-person-lock"></i></div>
                <div class="clinic-eyebrow">LƯU Ý ĐÂY LÀ CỔNG THÔNG TIN NỘI BỘ CỦA PHÒNG KHÁM</div>
                <h2>Đăng nhập hệ thống</h2>
                <p class="clinic-card-subtitle">Vui lòng nhập thông tin tài khoản của bạn để tiếp tục.</p>

                <?php if ($error): ?>
                    <div class="clinic-alert clinic-alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <form action="actions/login-process.php" method="post" class="clinic-form-stack">
                    <div class="clinic-field">
                        <label for="TenDangNhap">Tên đăng nhập</label>
                        <div class="clinic-input-wrap">
                            <i class="bi bi-person"></i>
                            <input id="TenDangNhap" name="TenDangNhap" type="text" class="form-control" placeholder="Nhập tên đăng nhập" autocomplete="username" required autofocus>
                        </div>
                    </div>
                    <div class="clinic-field">
                        <label for="MatKhau">Mật khẩu</label>
                        <div class="clinic-input-wrap clinic-password-wrap">
                            <i class="bi bi-lock"></i>
                            <input id="MatKhau" name="MatKhau" type="password" class="form-control" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                            <button class="clinic-password-toggle" type="button" data-password-toggle aria-label="Hiển thị mật khẩu"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <button class="clinic-primary-btn clinic-login-submit" type="submit">Đăng nhập <i class="bi bi-arrow-right"></i></button>
                </form>
                <div class="clinic-login-divider"><span></span><i class="bi bi-shield-lock"></i><span></span></div>
                <p class="clinic-login-security">Thông tin đăng nhập chỉ dành cho nhân viên được cấp tài khoản.</p>
                <a class="clinic-back-link" href="index.html"><i class="bi bi-arrow-left"></i> Quay về trang chủ</a>
            </div>
            <div class="clinic-login-help"><i class="bi bi-info-circle"></i> Nếu không thể đăng nhập, vui lòng liên hệ quản trị viên.</div>
        </section>
    </div>
</main>

<footer class="clinic-footer"><span>© <?= date('Y') ?> ClinicCare</span><span>Chăm sóc sức khỏe bằng sự tận tâm</span></footer>
<script>
const passwordInput = document.getElementById('MatKhau');
const passwordToggle = document.querySelector('[data-password-toggle]');
passwordToggle.addEventListener('click', () => {
    const visible = passwordInput.type === 'text';
    passwordInput.type = visible ? 'password' : 'text';
    passwordToggle.innerHTML = visible ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    passwordToggle.setAttribute('aria-label', visible ? 'Hiển thị mật khẩu' : 'Ẩn mật khẩu');
});
</script>
</body>
</html>
