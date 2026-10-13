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
  <title>Đăng nhập</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
<div class="container py-5" style="max-width:520px">
  <h2 class="mb-4 text-center">Đăng nhập hệ thống</h2>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form action="actions/login-process.php" method="post" class="card card-body shadow-sm">
    <div class="mb-3">
      <label class="form-label">Tên đăng nhập</label>
      <input name="TenDangNhap" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Mật khẩu</label>
      <input type="password" name="MatKhau" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100">Đăng nhập</button>
    <a href="index.html" class="btn btn-link mt-2">Về trang chủ</a>
  </form>
</div>
</body>
</html>
