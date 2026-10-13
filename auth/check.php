<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['user'])) {
        header('Location: ../login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['user']['VaiTro'] ?? '') !== $role) {
        http_response_code(403);
        exit('Bạn không có quyền truy cập trang này.');
    }
}
