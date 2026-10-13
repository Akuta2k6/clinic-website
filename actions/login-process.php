<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$username = trim($_POST['TenDangNhap'] ?? '');
$password = $_POST['MatKhau'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM TaiKhoan WHERE TenDangNhap = ? AND TrangThai = 1 LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['MatKhau'])) {
    header('Location: ../login.php?error=' . urlencode('Sai tên đăng nhập hoặc mật khẩu.'));
    exit;
}

$_SESSION['user'] = [
    'MaTK' => (int)$user['MaTK'],
    'TenDangNhap' => $user['TenDangNhap'],
    'VaiTro' => $user['VaiTro']
];

if ($user['VaiTro'] === 'Admin') {
    header('Location: ../admin/dashboard.php');
} else {
    header('Location: ../doctor/dashboard.php');
}
exit;
