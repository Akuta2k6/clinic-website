<?php
require_once __DIR__ . '/config/database.php';

$chuyenKhoas = $pdo->query('SELECT MaCK, TenChuyenKhoa FROM ChuyenKhoa WHERE TrangThai=1 ORDER BY TenChuyenKhoa')->fetchAll();

$success = isset($_GET['success']);
$error = trim((string)($_GET['error'] ?? ''));
$code = trim((string)($_GET['code'] ?? ''));
$mailStatus = $_GET['mail'] ?? null;

// Chỉ nhận mã bác sĩ và chuyên khoa hợp lệ đang hoạt động.
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
  <title>Đặt lịch khám</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
<div class="container py-5" style="max-width:900px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Đặt lịch khám</h2>
    <div class="d-flex gap-2">
        <a href="patient/lookup.php" class="btn btn-outline-primary">Tra cứu lịch</a>
        <a href="index.html" class="btn btn-outline-secondary">Trang chủ</a>
    </div>
  </div>

  <?php if ($error !== ''): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success">
        <strong>Đặt lịch khám thành công.</strong>

        <?php if ($code !== ''): ?>
            <div class="mt-2">
                Mã tra cứu của bạn:
                <strong><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        <?php endif; ?>
        <?php if ($mailStatus === '1'): ?>
            <div class="mt-2">Email xác nhận đã được gửi. Bạn hãy kiểm tra hộp thư đến và thư rác.</div>
        <?php elseif ($mailStatus === '0'): ?>
            <div class="mt-2">Lịch khám đã được lưu nhưng email xác nhận chưa gửi được. Vui lòng lưu mã tra cứu ở trên.</div>
        <?php endif; ?>
    </div>
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
        <label class="form-label">Email *</label>
        <input type="email" name="Email" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Giới tính</label>
        <select name="GioiTinh" class="form-select">
          <option value="">-- Chọn --</option>
          <option value="Nam">Nam</option>
          <option value="Nữ">Nữ</option>
          <option value="Khác">Khác</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Ngày sinh</label>
        <input type="date" name="NgaySinh" max="<?= date('Y-m-d') ?>" class="form-control">
      </div>

      <div class="col-md-6">
        <label class="form-label">Chuyên khoa *</label>
        <select id="MaCK" class="form-select" required>
          <option value="">-- Chọn chuyên khoa --</option>
          <?php foreach ($chuyenKhoas as $ck): ?>
            <option value="<?= (int)$ck['MaCK'] ?>"><?= htmlspecialchars($ck['TenChuyenKhoa'], ENT_QUOTES, 'UTF-8') ?></option>
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
const selectedDoctor = <?= json_encode($bacSiChonSan['MaBS'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const selectedDepartment = <?= (int)($bacSiChonSan['MaCK'] ?? 0) ?>;
let doctorsRequestId = 0;
let schedulesRequestId = 0;

async function loadDoctors(preselectedDoctor = '') {
  const requestId = ++doctorsRequestId;
  ++schedulesRequestId;
  doctor.innerHTML = '<option value="">-- Chọn bác sĩ --</option>';
  doctor.disabled = true;
  schedule.innerHTML = '<option value="">-- Chọn khung giờ --</option>';
  schedule.disabled = true;
  if (!department.value) return;
  try {
    const res = await fetch(`api/get-doctors.php?MaCK=${encodeURIComponent(department.value)}`);
    if (!res.ok) throw new Error('Không tải được danh sách bác sĩ');
    const data = await res.json();
    if (requestId !== doctorsRequestId) return;
    data.forEach(d => {
      const option = document.createElement('option');
      option.value = d.MaBS;
      option.textContent = d.HoTen + (d.HocVi ? ' - ' + d.HocVi : '');
      doctor.appendChild(option);
    });
    doctor.disabled = false;
    if (preselectedDoctor && data.some(d => d.MaBS === preselectedDoctor)) {
      doctor.value = preselectedDoctor;
      if (dateInput.value) await loadSchedules();
    }
  } catch (err) {
    if (requestId === doctorsRequestId) {
      doctor.innerHTML = '<option value="">Không tải được bác sĩ, vui lòng thử lại</option>';
    }
    console.error(err);
  }
}

department.addEventListener('change', () => loadDoctors());

async function loadSchedules() {
  const requestId = ++schedulesRequestId;
  schedule.innerHTML = '<option value="">-- Chọn khung giờ --</option>';
  schedule.disabled = true;
  if (!doctor.value || !dateInput.value) return;
  try {
    const res = await fetch(
      `api/get-schedules.php?MaBS=${encodeURIComponent(doctor.value)}&NgayKham=${encodeURIComponent(dateInput.value)}`
    );
    if (!res.ok) throw new Error('Không tải được khung giờ khám');
    const data = await res.json();
    if (requestId !== schedulesRequestId) return;
    if (!data.length) {
      const option = document.createElement('option');
      option.value = '';
      option.disabled = true;
      option.textContent = 'Không còn lịch trống';
      schedule.appendChild(option);
    } else {
      data.forEach(s => {
        const option = document.createElement('option');
        option.value = s.MaLich;
        option.textContent = `${s.GioBatDau} - ${s.GioKetThuc}`;
        schedule.appendChild(option);
      });
    }
    schedule.disabled = false;
  } catch (err) {
    if (requestId === schedulesRequestId) {
      schedule.innerHTML = '<option value="">Không tải được khung giờ, vui lòng thử lại</option>';
    }
    console.error(err);
  }
}

doctor.addEventListener('change', loadSchedules);
dateInput.addEventListener('change', loadSchedules);
if (selectedDoctor && selectedDepartment) {
  department.value = String(selectedDepartment);
  loadDoctors(selectedDoctor);
}
</script>
</body>
</html>