<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#12345a">
    <title>Đăng ký tiêm ngừa | ClinicCare</title>
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
            <a href="testing.php"><i class="bi bi-eyedropper"></i><span>Xét nghiệm</span></a>
            <a class="active" href="vaccination.php"><i class="bi bi-shield-plus"></i><span>Tiêm ngừa</span></a>
            <a href="patient/lookup.php"><i class="bi bi-search"></i><span>Tra cứu lịch</span></a>
            <a href="login.php"><i class="bi bi-person-lock"></i><span>Đăng nhập</span></a>
        </nav>
    </div>
</header>
<main class="clinic-main">
    <section class="clinic-page-heading clinic-appointment-heading">
        <div>
            <div class="clinic-eyebrow"><i class="bi bi-shield-plus"></i> ĐỒNG HÀNH CÙNG SỨC KHỎE</div>
            <h1>Đăng ký <span>tiêm ngừa</span></h1>
            <p>Chọn loại vắc-xin và thời gian mong muốn. Nhân viên y tế sẽ tiếp nhận thông tin và tư vấn trước khi tiêm.</p>
        </div>
        <a class="clinic-heading-link" href="appointment.php"><i class="bi bi-calendar2-plus"></i> Đặt lịch khám</a>
    </section>
    <div class="clinic-booking-layout">
        <section class="clinic-form-card">
            <div class="clinic-form-card-heading">
                <div class="clinic-heading-icon"><i class="bi bi-shield-plus"></i></div>
                <div><h2>Thông tin đăng ký tiêm ngừa</h2><p>Vui lòng điền chính xác thông tin bên dưới</p></div>
                <span class="clinic-required-note">* Bắt buộc</span>
            </div>
            <form class="clinic-booking-form" method="post" action="#">

                <section class="clinic-form-section">
                    <div class="clinic-form-section-title"><span>01</span><div><h3>Thông tin cá nhân</h3><p>Thông tin của người đăng ký tiêm ngừa</p></div></div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="clinic-field"><label for="HoTen">Họ và tên <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-person"></i><input id="HoTen" name="HoTen" type="text" class="form-control" placeholder="Nhập họ và tên" autocomplete="name" required></div></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="SoDienThoai">Số điện thoại <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-telephone"></i><input id="SoDienThoai" name="SoDienThoai" type="tel" class="form-control" placeholder="Nhập số điện thoại" autocomplete="tel" required></div></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="Email">Email</label><div class="clinic-input-wrap"><i class="bi bi-envelope"></i><input id="Email" name="Email" type="email" class="form-control" placeholder="example@email.com" autocomplete="email"></div></div></div>
                        <div class="col-md-3"><div class="clinic-field"><label for="GioiTinh">Giới tính</label><select id="GioiTinh" name="GioiTinh" class="form-select"><option value="">-- Chọn --</option><option>Nam</option><option>Nữ</option><option>Khác</option></select></div></div>
                        <div class="col-md-3"><div class="clinic-field"><label for="NgaySinh">Ngày sinh <b>*</b></label><input id="NgaySinh" name="NgaySinh" type="date" class="form-control" required></div></div>
                    </div>
                </section>
                <section class="clinic-form-section">
                    <div class="clinic-form-section-title"><span>02</span><div><h3>Thông tin tiêm ngừa</h3><p>Lựa chọn loại vắc-xin và lịch tiêm mong muốn</p></div></div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="clinic-field"><label for="LoaiVacXin">Loại vắc-xin <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-shield-plus"></i><select id="LoaiVacXin" name="LoaiVacXin" class="form-select" required><option value="">-- Chọn loại vắc-xin --</option><option>Vắc-xin cúm mùa</option><option>Vắc-xin viêm gan B</option><option>Vắc-xin HPV</option><option>Vắc-xin uốn ván</option><option>Vắc-xin sởi - quai bị - rubella</option><option>Khác / Cần tư vấn</option></select></div></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="MuiTiem">Mũi tiêm</label><select id="MuiTiem" name="MuiTiem" class="form-select"><option value="">-- Chọn --</option><option>Mũi đầu tiên</option><option>Mũi tiếp theo</option><option>Mũi nhắc lại</option><option>Chưa rõ / Cần tư vấn</option></select></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="NgayTiem">Ngày tiêm mong muốn <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-calendar3"></i><input id="NgayTiem" name="NgayTiem" type="date" class="form-control" required></div></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="GioTiem">Khung giờ mong muốn <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-clock"></i><select id="GioTiem" name="GioTiem" class="form-select" required><option value="">-- Chọn khung giờ --</option><option>08:00 - 10:00</option><option>10:00 - 12:00</option><option>13:00 - 15:00</option><option>15:00 - 17:00</option></select></div></div></div>
                    </div>
                </section>
                <section class="clinic-form-section clinic-form-section-last">
                    <div class="clinic-form-section-title"><span>03</span><div><h3>Thông tin sức khỏe</h3><p>Một số thông tin cần thiết để nhân viên y tế tư vấn</p></div></div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="clinic-field"><label for="TienSuDiUng">Tiền sử dị ứng</label><select id="TienSuDiUng" name="TienSuDiUng" class="form-select"><option value="">-- Chọn --</option><option>Không ghi nhận</option><option>Có tiền sử dị ứng</option><option>Chưa rõ</option></select></div></div>
                        <div class="col-md-6"><div class="clinic-field"><label for="TinhTrang">Tình trạng sức khỏe hiện tại</label><select id="TinhTrang" name="TinhTrang" class="form-select"><option value="">-- Chọn --</option><option>Bình thường</option><option>Đang không khỏe</option><option>Muốn được tư vấn thêm</option></select></div></div>
                        <div class="col-12"><div class="clinic-field"><label for="GhiChu">Ghi chú thêm</label><textarea id="GhiChu" name="GhiChu" class="form-control" rows="4" placeholder="Thông tin về các mũi tiêm trước đây hoặc điều cần tư vấn (không bắt buộc)"></textarea></div></div>
                    </div>
                </section>
                <div class="clinic-form-actions">
                    <p><i class="bi bi-lock-fill"></i> Thông tin chỉ dùng để tiếp nhận yêu cầu đăng ký của bạn.</p>
                    <button class="clinic-primary-btn" type="button" onclick="alert('Đây là giao diện minh họa. Chức năng gửi và lưu thông tin sẽ được bổ sung bằng PHP/MySQL sau.')">Gửi yêu cầu tiêm ngừa <i class="bi bi-arrow-right"></i></button>
                </div>
            </form>
        </section>
        <aside class="clinic-booking-aside">
            <div class="clinic-aside-card clinic-aside-highlight">
                <div class="clinic-aside-icon"><i class="bi bi-shield-check"></i></div>
                <h3>Đăng ký tiêm ngừa</h3>
                <p>Thực hiện đăng ký theo các bước đơn giản dưới đây.</p>
                <div class="clinic-step-list">
                    <div><span>1</span><p><strong>Điền thông tin</strong><small>Cung cấp thông tin người tiêm</small></p></div>
                    <div><span>2</span><p><strong>Chọn vắc-xin</strong><small>Lựa chọn loại vắc-xin cần tiêm</small></p></div>
                    <div><span>3</span><p><strong>Chọn lịch dự kiến</strong><small>Chọn ngày và khung giờ mong muốn</small></p></div>
                    <div><span>4</span><p><strong>Chờ xác nhận</strong><small>Phòng khám liên hệ để tư vấn</small></p></div>
                </div>
            </div>
            <div class="clinic-aside-card clinic-aside-support">
                <div class="clinic-support-icon"><i class="bi bi-headset"></i></div>
                <div><strong>Cần hỗ trợ?</strong><p>Nếu cần tư vấn thêm, bạn có thể xem thông tin liên hệ của phòng khám.</p><a href="contact.html">Thông tin liên hệ <i class="bi bi-arrow-up-right"></i></a></div>
            </div>
        </aside>
    </div>
</main>
<footer class="clinic-footer"><span>© ClinicCare</span><span>Chăm sóc sức khỏe bằng sự tận tâm</span></footer>
</body>
</html>