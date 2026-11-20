<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#12345a">
    <title>Đăng ký xét nghiệm</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/public-pages.css" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">
</head>
<body class="clinic-public-body">
<header class="clinic-header">
    <div class="clinic-header-inner">
        <a class="clinic-brand" href="index.php">
            <span class="clinic-brand-mark"><i class="bi bi-heart-pulse-fill"></i></span>
            <span class="clinic-brand-copy"><strong>TDTU Clinic</strong><small>Hệ thống quản lý phòng khám</small></span>
        </a>
        <nav class="clinic-nav" aria-label="Điều hướng chính">
            <a href="index.php"><i class="bi bi-house-door"></i><span>Trang chủ</span></a>
            <a href="appointment.php"><i class="bi bi-calendar2-plus"></i><span>Đặt lịch khám</span></a>
            <a class="active" href="testing.php"><i class="bi bi-eyedropper"></i><span>Xét nghiệm</span></a>
            <a href="vaccination.php"><i class="bi bi-shield-plus"></i><span>Tiêm ngừa</span></a>
            <a href="patient/lookup.php"><i class="bi bi-search"></i><span>Tra cứu lịch</span></a>
            <a href="login.php"><i class="bi bi-person-lock"></i><span>Đăng nhập</span></a>
        </nav>
    </div>
</header>
<main class="clinic-main">
    <section class="clinic-page-heading clinic-appointment-heading">
        <div>
            <div class="clinic-eyebrow"><i class="bi bi-eyedropper"></i> ĐỒNG HÀNH CÙNG SỨC KHỎE</div>
            <h1>Đăng ký <span>xét nghiệm</span></h1>
            <p>Lựa chọn dịch vụ xét nghiệm và thời gian mong muốn. Phòng khám sẽ tiếp nhận yêu cầu và hướng dẫn các bước tiếp theo.</p>
        </div>
        <a class="clinic-heading-link" href="appointment.php"><i class="bi bi-calendar2-plus"></i> Đặt lịch khám</a>
    </section>
    <div class="clinic-booking-layout">
        <section class="clinic-form-card">
            <div class="clinic-form-card-heading">
                <div class="clinic-heading-icon"><i class="bi bi-eyedropper"></i></div>
                <div><h2>Thông tin đăng ký xét nghiệm</h2><p>Vui lòng điền chính xác thông tin bên dưới</p></div>
                <span class="clinic-required-note">* Bắt buộc</span>
            </div>
            <form class="clinic-booking-form" method="post" action="#">

                <section class="clinic-form-section">
                    <div class="clinic-form-section-title"><span>01</span><div><h3>Thông tin cá nhân</h3><p>Thông tin của người đăng ký xét nghiệm</p></div></div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="clinic-field"><label for="HoTen">Họ và tên <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-person"></i><input id="HoTen" name="HoTen" type="text" class="form-control" placeholder="Nhập họ và tên" autocomplete="name" required></div></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="SoDienThoai">Số điện thoại <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-telephone"></i><input id="SoDienThoai" name="SoDienThoai" type="tel" class="form-control" placeholder="Nhập số điện thoại" autocomplete="tel" required></div></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="Email">Email</label><div class="clinic-input-wrap"><i class="bi bi-envelope"></i><input id="Email" name="Email" type="email" class="form-control" placeholder="example@email.com" autocomplete="email"></div></div></div>
                        <div class="col-md-3"><div class="clinic-field"><label for="GioiTinh">Giới tính</label><select id="GioiTinh" name="GioiTinh" class="form-select"><option value="">-- Chọn --</option><option>Nam</option><option>Nữ</option><option>Khác</option></select></div></div>
                        <div class="col-md-3"><div class="clinic-field"><label for="NgaySinh">Ngày sinh <b>*</b></label><input id="NgaySinh" name="NgaySinh" type="date" class="form-control" required></div></div>
                    </div>
                </section>
                <section class="clinic-form-section">
                    <div class="clinic-form-section-title"><span>02</span><div><h3>Thông tin xét nghiệm</h3><p>Lựa chọn dịch vụ xét nghiệm cần thực hiện</p></div></div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="clinic-field"><label for="LoaiXetNghiem">Loại xét nghiệm <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-eyedropper"></i><select id="LoaiXetNghiem" name="LoaiXetNghiem" class="form-select" required><option value="">-- Chọn loại xét nghiệm --</option><option>Xét nghiệm công thức máu</option><option>Xét nghiệm đường huyết</option><option>Xét nghiệm mỡ máu</option><option>Xét nghiệm chức năng gan</option><option>Xét nghiệm chức năng thận</option><option>Tổng phân tích nước tiểu</option><option>Gói xét nghiệm tổng quát</option><option>Khác / Cần tư vấn</option></select></div></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="HinhThuc">Hình thức thực hiện</label><select id="HinhThuc" name="HinhThuc" class="form-select"><option value="">-- Chọn --</option><option>Đến phòng khám</option><option>Cần tư vấn thêm</option></select></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="NgayXetNghiem">Ngày mong muốn <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-calendar3"></i><input id="NgayXetNghiem" name="NgayXetNghiem" type="date" class="form-control" required></div></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="GioXetNghiem">Khung giờ mong muốn <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-clock"></i><select id="GioXetNghiem" name="GioXetNghiem" class="form-select" required><option value="">-- Chọn khung giờ --</option><option>07:00 - 09:00</option><option>09:00 - 11:00</option><option>13:00 - 15:00</option><option>15:00 - 17:00</option></select></div></div></div>
                    </div>
                </section>
                <section class="clinic-form-section clinic-form-section-last">
                    <div class="clinic-form-section-title"><span>03</span><div><h3>Thông tin bổ sung</h3><p>Cho phòng khám biết thêm nhu cầu của bạn</p></div></div>
                    <div class="clinic-field"><label for="GhiChu">Ghi chú / yêu cầu thêm</label><textarea id="GhiChu" name="GhiChu" class="form-control" rows="4" placeholder="Ví dụ: chỉ định của bác sĩ, nhu cầu tư vấn hoặc lưu ý khác (không bắt buộc)"></textarea></div>
                    <div class="clinic-field mt-3"><label for="ChiDinh">Bạn đã có chỉ định xét nghiệm chưa?</label><select id="ChiDinh" name="ChiDinh" class="form-select"><option value="">-- Chọn --</option><option>Đã có chỉ định của bác sĩ</option><option>Chưa có, muốn được tư vấn</option></select></div>
                </section>
                <div class="clinic-form-actions">
                    <p><i class="bi bi-lock-fill"></i> Thông tin chỉ dùng để tiếp nhận yêu cầu đăng ký của bạn.</p>
                    <button class="clinic-primary-btn" type="button" onclick="alert('Đây là giao diện minh họa. Chức năng gửi và lưu thông tin sẽ được bổ sung bằng PHP/MySQL sau.')">Gửi yêu cầu xét nghiệm <i class="bi bi-arrow-right"></i></button>
                </div>
            </form>
        </section>
        <aside class="clinic-booking-aside">
            <div class="clinic-aside-card clinic-aside-highlight">
                <div class="clinic-aside-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                <h3>Đăng ký xét nghiệm</h3>
                <p>Hoàn tất thông tin đăng ký theo các bước sau.</p>
                <div class="clinic-step-list">
                    <div><span>1</span><p><strong>Điền thông tin</strong><small>Cung cấp thông tin người xét nghiệm</small></p></div>
                    <div><span>2</span><p><strong>Chọn dịch vụ</strong><small>Lựa chọn loại xét nghiệm phù hợp</small></p></div>
                    <div><span>3</span><p><strong>Chọn lịch dự kiến</strong><small>Chọn ngày và khung giờ mong muốn</small></p></div>
                    <div><span>4</span><p><strong>Chờ xác nhận</strong><small>Phòng khám hướng dẫn tiếp theo</small></p></div>
                </div>
            </div>
            <div class="clinic-aside-card clinic-aside-support">
                <div class="clinic-support-icon"><i class="bi bi-headset"></i></div>
                <div><strong>Cần hỗ trợ?</strong><p>Nếu cần tư vấn thêm, bạn có thể xem thông tin liên hệ của phòng khám.</p><a href="contact.html">Thông tin liên hệ <i class="bi bi-arrow-up-right"></i></a></div>
            </div>
        </aside>
    </div>
</main>
<footer class="clinic-footer"><span>© TDTU Clinic</span><span>Chăm sóc sức khỏe bằng sự tận tâm</span></footer>
</body>
</html>
