<?php
require_once __DIR__ . '/config/database.php';
$chuyenKhoas = $pdo->query('SELECT MaCK, TenChuyenKhoa FROM ChuyenKhoa WHERE TrangThai = 1 ORDER BY TenChuyenKhoa')->fetchAll();
$success = isset($_GET['success']);
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đặt lịch khám</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
<div class="container py-5" style="max-width:900px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Đặt lịch khám</h2>
    <a href="index.html" class="btn btn-outline-secondary">Trang chủ</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">Đặt lịch khám thành công.</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form action="actions/book-appointment.php" method="post" class="card card-body shadow-sm">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Họ và tên *</label>
        <input name="HoTen" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Số điện thoại *</label>
        <input name="SoDienThoai" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="Email" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Giới tính</label>
        <select name="GioiTinh" class="form-select">
          <option value="">-- Chọn --</option>
          <option>Nam</option><option>Nữ</option><option>Khác</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Ngày sinh</label>
        <input type="date" name="NgaySinh" class="form-control">
      </div>

      <div class="col-md-6">
        <label class="form-label">Chuyên khoa *</label>
        <select id="MaCK" class="form-select" required>
          <option value="">-- Chọn chuyên khoa --</option>
          <?php foreach ($chuyenKhoas as $ck): ?>
            <option value="<?= $ck['MaCK'] ?>"><?= htmlspecialchars($ck['TenChuyenKhoa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Bác sĩ *</label>
        <select id="MaBS" class="form-select" required disabled>
          <option value="">-- Chọn bác sĩ --</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Ngày khám *</label>
        <input type="date" id="NgayKham" class="form-control" min="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Khung giờ *</label>
        <select name="MaLich" id="MaLich" class="form-select" required disabled>
          <option value="">-- Chọn khung giờ --</option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Lý do khám / triệu chứng</label>
        <textarea name="LyDoKham" class="form-control" rows="4"></textarea>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">Xác nhận đặt lịch</button>
      </div>
    </div>
  </form>
</div>
<script>
const department = document.getElementById('MaCK');
const doctor = document.getElementById('MaBS');
const dateInput = document.getElementById('NgayKham');
const schedule = document.getElementById('MaLich');

department.addEventListener('change', async () => {
  doctor.innerHTML = '<option value="">-- Chọn bác sĩ --</option>';
  schedule.innerHTML = '<option value="">-- Chọn khung giờ --</option>';
  schedule.disabled = true;
  if (!department.value) { doctor.disabled = true; return; }
  const res = await fetch(`api/get-doctors.php?MaCK=${department.value}`);
  const data = await res.json();
  data.forEach(d => doctor.insertAdjacentHTML('beforeend', `<option value="${d.MaBS}">${d.HoTen}${d.HocVi ? ' - ' + d.HocVi : ''}</option>`));
  doctor.disabled = false;
});

async function loadSchedules() {
  schedule.innerHTML = '<option value="">-- Chọn khung giờ --</option>';
  if (!doctor.value || !dateInput.value) { schedule.disabled = true; return; }
  const res = await fetch(`api/get-schedules.php?MaBS=${doctor.value}&NgayKham=${dateInput.value}`);
  const data = await res.json();
  if (!data.length) {
    schedule.insertAdjacentHTML('beforeend', '<option value="" disabled>Không còn lịch trống</option>');
  } else {
    data.forEach(s => schedule.insertAdjacentHTML('beforeend', `<option value="${s.MaLich}">${s.GioBatDau} - ${s.GioKetThuc}</option>`));
  }
  schedule.disabled = false;
}

doctor.addEventListener('change', loadSchedules);
dateInput.addEventListener('change', loadSchedules);
</script>
</body>
</html>
