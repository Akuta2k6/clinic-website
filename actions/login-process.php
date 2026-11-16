<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$username = trim($_POST['TenDangNhap'] ?? '');
$password = $_POST['MatKhau'] ?? '';

if ($username === '' || $password === '') {
    header('Location: ../login.php?error=' . urlencode('Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM TaiKhoan WHERE TenDangNhap = ? AND TrangThai = 1 LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['MatKhau'])) {
    header('Location: ../login.php?error=' . urlencode('Sai tên đăng nhập hoặc mật khẩu.'));
    exit;
}

session_regenerate_id(true);

$_SESSION['user'] = [
    'MaTK' => (int)$user['MaTK'],
    'TenDangNhap' => $user['TenDangNhap'],
    'VaiTro' => $user['VaiTro']
];

if ($user['VaiTro'] === 'Admin') {
    header('Location: ../admin/dashboard.php');
} elseif ($user['VaiTro'] === 'BacSi') {
    header('Location: ../doctor/dashboard.php');
} else {
    session_unset();
    session_destroy();
    header('Location: ../login.php?error=' . urlencode('Vai trò tài khoản không hợp lệ.'));
}
exit;