<?php
require_once __DIR__ . '/config/database.php';

$chuyenKhoas = $pdo->query('SELECT MaCK, TenChuyenKhoa FROM ChuyenKhoa WHERE TrangThai=1 ORDER BY TenChuyenKhoa')->fetchAll();

$success = isset($_GET['success']);
$error = $_GET['error'] ?? '';
$code = $_GET['code'] ?? '';
$mailStatus = $_GET['mail'] ?? null;

$maBSYeuCau = trim((string)($_GET['MaBS'] ?? ''));
$bacSiChonSan = null;
if ($maBSYeuCau !== '') {
    $stmt = $pdo->prepare('SELECT bs.MaBS, bs.MaCK FROM BacSi bs INNER JOIN ChuyenKhoa ck ON bs.MaCK=ck.MaCK WHERE bs.MaBS=? AND bs.TrangThai=1 AND ck.TrangThai=1 LIMIT 1');
    $stmt->execute([$maBSYeuCau]);
    $bacSiChonSan = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#12345a">
    <title>Đặt lịch khám</title>
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
            <a class="active" href="appointment.php"><i class="bi bi-calendar2-plus"></i><span>Đặt lịch khám</span></a>
            <a href="patient/lookup.php"><i class="bi bi-search"></i><span>Tra cứu lịch</span></a>
            <a href="login.php"><i class="bi bi-person-lock"></i><span>Đăng nhập</span></a>
        </nav>
    </div>
</header>

<main class="clinic-main">
    <section class="clinic-page-heading clinic-appointment-heading">
        <div>
            <div class="clinic-eyebrow"><i class="bi bi-heart-pulse-fill"></i> ĐỒNG HÀNH CÙNG SỨC KHỎE</div>
            <h1>Đặt lịch khám <span>trực tuyến</span></h1>
            <p>Chọn chuyên khoa, bác sĩ và khung giờ phù hợp. Phòng khám sẽ tiếp nhận thông tin và gửi xác nhận đến email của bạn.</p>
        </div>
        <a class="clinic-heading-link" href="patient/lookup.php"><i class="bi bi-search"></i> Đã có lịch? Tra cứu ngay</a>
    </section>

    <?php if ($success): ?>
        <section class="clinic-booking-success" role="status">
            <div class="clinic-success-icon"><i class="bi bi-check-lg"></i></div>
            <div class="clinic-success-content">
                <strong>Yêu cầu đặt lịch đã được ghi nhận!</strong>
                <p>Hãy lưu mã tra cứu bên dưới để kiểm tra trạng thái lịch hẹn hoặc thực hiện yêu cầu hủy lịch khi cần.</p>
                <?php if ($code): ?>
                    <div class="clinic-code-row">
                        <div><small>MÃ TRA CỨU CỦA BẠN</small><strong id="bookingCode"><?= htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <button type="button" class="clinic-copy-btn" data-copy-code><i class="bi bi-copy"></i> Sao chép</button>
                    </div>
                <?php endif; ?>
                <?php if ($mailStatus === '1'): ?>
                    <div class="clinic-mail-note"><i class="bi bi-envelope-check"></i> Email xác nhận đã được gửi. Bạn hãy kiểm tra hộp thư đến và thư rác.</div>
                <?php elseif ($mailStatus === '0'): ?>
                    <div class="clinic-mail-note clinic-mail-warning"><i class="bi bi-exclamation-triangle"></i> Lịch khám đã được lưu nhưng email xác nhận chưa gửi được. Vui lòng lưu mã tra cứu ở trên.</div>
                <?php endif; ?>
                <a class="clinic-inline-link" href="patient/lookup.php<?= $code ? '?code=' . urlencode((string)$code) : '' ?>">Tra cứu lịch khám <i class="bi bi-arrow-right"></i></a>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="clinic-alert clinic-alert-danger clinic-page-alert" role="alert"><i class="bi bi-exclamation-circle-fill"></i><span><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></span></div>
    <?php endif; ?>

    <div class="clinic-booking-layout">
        <section class="clinic-form-card">
            <div class="clinic-form-card-heading">
                <div class="clinic-heading-icon"><i class="bi bi-calendar2-check"></i></div>
                <div><h2>Thông tin đặt lịch</h2><p>Vui lòng điền chính xác thông tin bên dưới</p></div>
                <span class="clinic-required-note">* Bắt buộc</span>
            </div>

            <form action="actions/book-appointment.php" method="post" class="clinic-booking-form">
                <section class="clinic-form-section">
                    <div class="clinic-form-section-title"><span>01</span><div><h3>Thông tin cá nhân</h3><p>Thông tin liên hệ của người đến khám</p></div></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="clinic-field"><label for="HoTen">Họ và tên <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-person"></i><input id="HoTen" name="HoTen" type="text" class="form-control" placeholder="Nhập họ và tên" autocomplete="name" required></div></div>
                        </div>
                        <div class="col-md-6">
                            <div class="clinic-field"><label for="SoDienThoai">Số điện thoại <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-telephone"></i><input id="SoDienThoai" name="SoDienThoai" type="tel" class="form-control" placeholder="Nhập số điện thoại" autocomplete="tel" required></div></div>
                        </div>
                        <div class="col-md-6">
                            <div class="clinic-field"><label for="Email">Email <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-envelope"></i><input id="Email" type="email" name="Email" class="form-control" placeholder="example@email.com" autocomplete="email" required></div><small>Email dùng để nhận thông báo và mã xác minh khi cần hủy lịch.</small></div>
                        </div>
                        <div class="col-md-3">
                            <div class="clinic-field"><label for="GioiTinh">Giới tính</label><select id="GioiTinh" name="GioiTinh" class="form-select"><option value="">-- Chọn --</option><option>Nam</option><option>Nữ</option><option>Khác</option></select></div>
                        </div>
                        <div class="col-md-3">
                            <div class="clinic-field"><label for="NgaySinh">Ngày sinh</label><input id="NgaySinh" type="date" name="NgaySinh" class="form-control"></div>
                        </div>
                    </div>
                </section>

                <section class="clinic-form-section">
                    <div class="clinic-form-section-title"><span>02</span><div><h3>Chọn lịch khám</h3><p>Chọn chuyên khoa, bác sĩ và thời gian còn trống</p></div></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="clinic-field"><label for="MaCK">Chuyên khoa <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-hospital"></i><select id="MaCK" class="form-select" required><option value="">-- Chọn chuyên khoa --</option><?php foreach ($chuyenKhoas as $ck): ?><option value="<?= (int)$ck['MaCK'] ?>"><?= htmlspecialchars($ck['TenChuyenKhoa'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div></div>
                        </div>
                        <div class="col-md-6">
                            <div class="clinic-field"><label for="MaBS">Bác sĩ <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-person-badge"></i><select id="MaBS" class="form-select" required disabled><option value="">Vui lòng chọn chuyên khoa trước</option></select></div><small id="doctorHelp" aria-live="polite">Danh sách bác sĩ sẽ hiển thị sau khi chọn chuyên khoa.</small></div>
                        </div>
                        <div class="col-md-6">
                            <div class="clinic-field"><label for="NgayKham">Ngày khám <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-calendar3"></i><input type="date" id="NgayKham" class="form-control" min="<?= date('Y-m-d') ?>" required></div></div>
                        </div>
                        <div class="col-md-6">
                            <div class="clinic-field"><label for="MaLich">Khung giờ <b>*</b></label><div class="clinic-input-wrap"><i class="bi bi-clock"></i><select name="MaLich" id="MaLich" class="form-select" required disabled><option value="">Vui lòng chọn bác sĩ và ngày khám</option></select></div><small id="scheduleHelp" aria-live="polite">Khung giờ sẽ hiển thị sau khi chọn bác sĩ và ngày khám.</small></div>
                        </div>
                    </div>
                </section>

                <section class="clinic-form-section clinic-form-section-last">
                    <div class="clinic-form-section-title"><span>03</span><div><h3>Nội dung khám</h3><p>Giúp bác sĩ hiểu thêm nhu cầu thăm khám của bạn</p></div></div>
                    <div class="clinic-field"><label for="LyDoKham">Lý do khám</label><textarea id="LyDoKham" name="LyDoKham" class="form-control" rows="4" placeholder="Mô tả ngắn gọn lý do bạn muốn thăm khám (không bắt buộc)"></textarea></div>
                </section>

                <div class="clinic-form-actions">
                    <p><i class="bi bi-lock-fill"></i> Thông tin được sử dụng để tiếp nhận và xử lý lịch hẹn của bạn.</p>
                    <button class="clinic-primary-btn" type="submit">Xác nhận đặt lịch <i class="bi bi-arrow-right"></i></button>
                </div>
            </form>
        </section>

        <aside class="clinic-booking-aside">
            <div class="clinic-aside-card clinic-aside-highlight">
                <div class="clinic-aside-icon"><i class="bi bi-calendar2-heart"></i></div>
                <h3>Đặt lịch dễ dàng</h3>
                <p>Hoàn tất thông tin và lựa chọn khung giờ phù hợp chỉ với vài bước.</p>
                <div class="clinic-step-list">
                    <div><span>1</span><p><strong>Điền thông tin</strong><small>Cung cấp thông tin liên hệ</small></p></div>
                    <div><span>2</span><p><strong>Chọn bác sĩ</strong><small>Lựa chọn chuyên khoa phù hợp</small></p></div>
                    <div><span>3</span><p><strong>Chọn thời gian</strong><small>Chọn khung giờ còn trống</small></p></div>
                    <div><span>4</span><p><strong>Lưu mã tra cứu</strong><small>Dùng mã để kiểm tra lịch hẹn</small></p></div>
                </div>
            </div>
            <div class="clinic-aside-card clinic-aside-support">
                <div class="clinic-support-icon"><i class="bi bi-headset"></i></div>
                <div><strong>Cần hỗ trợ?</strong><p>Nếu gặp khó khăn khi đặt lịch, bạn có thể quay lại trang chủ để xem thông tin liên hệ của phòng khám.</p><a href="contact.php">Thông tin liên hệ <i class="bi bi-arrow-up-right"></i></a></div>
            </div>
        </aside>
    </div>
</main>

<footer class="clinic-footer"><span>© <?= date('Y') ?> TDTU Clinic</span><span>Chăm sóc sức khỏe bằng sự tận tâm</span></footer>

<script>
const department = document.getElementById('MaCK');
const doctor = document.getElementById('MaBS');
const dateInput = document.getElementById('NgayKham');
const schedule = document.getElementById('MaLich');
const doctorHelp = document.getElementById('doctorHelp');
const scheduleHelp = document.getElementById('scheduleHelp');
const selectedDoctor = <?= json_encode($bacSiChonSan['MaBS'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const selectedDepartment = <?= (int)($bacSiChonSan['MaCK'] ?? 0) ?>;
let doctorsRequestId = 0;
let schedulesRequestId = 0;

async function loadDoctors(preselectedDoctor = '') {
    const requestId = ++doctorsRequestId;
    doctor.replaceChildren(new Option('Đang tải danh sách bác sĩ...', ''));
    doctor.disabled = true;
    schedule.replaceChildren(new Option('Vui lòng chọn bác sĩ và ngày khám', ''));
    schedule.disabled = true;
    scheduleHelp.textContent = 'Khung giờ sẽ hiển thị sau khi chọn bác sĩ và ngày khám.';
    if (!department.value) {
        doctor.replaceChildren(new Option('Vui lòng chọn chuyên khoa trước', ''));
        doctorHelp.textContent = 'Danh sách bác sĩ sẽ hiển thị sau khi chọn chuyên khoa.';
        return;
    }
    doctorHelp.textContent = 'Đang tải danh sách bác sĩ...';
    try {
        const response = await fetch(`api/get-doctors.php?MaCK=${encodeURIComponent(department.value)}`);
        if (!response.ok) throw new Error('Không tải được danh sách bác sĩ');
        const data = await response.json();
        if (requestId !== doctorsRequestId) return;
        doctor.replaceChildren(new Option(data.length ? '-- Chọn bác sĩ --' : 'Chưa có bác sĩ thuộc chuyên khoa này', ''));
        data.forEach(item => doctor.add(new Option(item.HoTen + (item.HocVi ? ' - ' + item.HocVi : ''), item.MaBS)));
        doctor.disabled = data.length === 0;
        doctorHelp.textContent = data.length ? `Có ${data.length} bác sĩ đang hoạt động trong chuyên khoa này.` : 'Vui lòng chọn chuyên khoa khác hoặc liên hệ phòng khám.';
        if (preselectedDoctor && data.some(item => item.MaBS === preselectedDoctor)) {
            doctor.value = preselectedDoctor;
            if (dateInput.value) await loadSchedules();
        }
    } catch (error) {
        if (requestId === doctorsRequestId) {
            doctor.replaceChildren(new Option('Không tải được danh sách bác sĩ', ''));
            doctorHelp.textContent = 'Vui lòng thử chọn lại chuyên khoa.';
        }
        console.error(error);
    }
}

async function loadSchedules() {
    const requestId = ++schedulesRequestId;
    schedule.replaceChildren(new Option('Đang tìm khung giờ trống...', ''));
    schedule.disabled = true;
    if (!doctor.value || !dateInput.value) {
        schedule.replaceChildren(new Option('Vui lòng chọn bác sĩ và ngày khám', ''));
        scheduleHelp.textContent = 'Khung giờ sẽ hiển thị sau khi chọn bác sĩ và ngày khám.';
        return;
    }
    scheduleHelp.textContent = 'Đang kiểm tra lịch làm việc...';
    try {
        const query = new URLSearchParams({MaBS: doctor.value, NgayKham: dateInput.value});
        const response = await fetch(`api/get-schedules.php?${query.toString()}`);
        if (!response.ok) throw new Error('Không tải được khung giờ');
        const data = await response.json();
        if (requestId !== schedulesRequestId) return;
        schedule.replaceChildren(new Option(data.length ? '-- Chọn khung giờ --' : 'Không còn lịch trống trong ngày này', ''));
        data.forEach(item => schedule.add(new Option(`${item.GioBatDau} - ${item.GioKetThuc}`, String(item.MaLich))));
        schedule.disabled = data.length === 0;
        scheduleHelp.textContent = data.length ? `Tìm thấy ${data.length} khung giờ còn chỗ.` : 'Bạn có thể chọn ngày khác để xem lịch còn trống.';
    } catch (error) {
        if (requestId === schedulesRequestId) {
            schedule.replaceChildren(new Option('Không tải được khung giờ', ''));
            scheduleHelp.textContent = 'Vui lòng thử lại sau.';
        }
        console.error(error);
    }
}

department.addEventListener('change', () => loadDoctors());
doctor.addEventListener('change', loadSchedules);
dateInput.addEventListener('change', loadSchedules);

if (selectedDoctor && selectedDepartment) {
    department.value = String(selectedDepartment);
    loadDoctors(selectedDoctor);
}

const copyButton = document.querySelector('[data-copy-code]');
if (copyButton) {
    copyButton.addEventListener('click', async () => {
        const value = document.getElementById('bookingCode').textContent.trim();
        try {
            await navigator.clipboard.writeText(value);
            copyButton.innerHTML = '<i class="bi bi-check2"></i> Đã sao chép';
        } catch (error) {
            const range = document.createRange();
            range.selectNodeContents(document.getElementById('bookingCode'));
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            copyButton.innerHTML = '<i class="bi bi-check2"></i> Đã chọn mã';
        }
    });
}
</script>
</body>
</html>
