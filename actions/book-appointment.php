<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../appointment.php');
    exit;
}

$hoTen = trim($_POST['HoTen'] ?? '');
$email = trim($_POST['Email'] ?? '');
$sdt = trim($_POST['SoDienThoai'] ?? '');
$gioiTinh = trim($_POST['GioiTinh'] ?? '');
$ngaySinh = $_POST['NgaySinh'] ?? null;
$maLich = filter_input(INPUT_POST, 'MaLich', FILTER_VALIDATE_INT);
$lyDo = trim($_POST['LyDoKham'] ?? '');

if ($hoTen === '' || $sdt === '' || !$maLich) {
    header('Location: ../appointment.php?error=' . urlencode('Vui lòng nhập đầy đủ thông tin bắt buộc.'));
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM LichKham WHERE MaLich = ? FOR UPDATE");
    $stmt->execute([$maLich]);
    $lich = $stmt->fetch();

    if (!$lich || $lich['TrangThai'] !== 'Mo' || (int)$lich['SoLuongDaDat'] >= (int)$lich['SoLuongToiDa']) {
        throw new RuntimeException('Khung giờ này đã hết chỗ hoặc không còn mở.');
    }

    $stmt = $pdo->prepare('SELECT MaBN FROM BenhNhan WHERE SoDienThoai = ? ORDER BY MaBN DESC LIMIT 1');
    $stmt->execute([$sdt]);
    $benhNhan = $stmt->fetch();

    if ($benhNhan) {
        $maBN = (int)$benhNhan['MaBN'];
        $stmt = $pdo->prepare('UPDATE BenhNhan SET HoTen=?, Email=?, GioiTinh=?, NgaySinh=? WHERE MaBN=?');
        $stmt->execute([$hoTen, $email ?: null, $gioiTinh ?: null, $ngaySinh ?: null, $maBN]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO BenhNhan (HoTen, GioiTinh, NgaySinh, SoDienThoai, Email) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$hoTen, $gioiTinh ?: null, $ngaySinh ?: null, $sdt, $email ?: null]);
        $maBN = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('INSERT INTO DatLich (MaBN, MaLich, LyDoKham) VALUES (?, ?, ?)');
    $stmt->execute([$maBN, $maLich, $lyDo ?: null]);

    $stmt = $pdo->prepare("UPDATE LichKham SET SoLuongDaDat = SoLuongDaDat + 1,
        TrangThai = CASE WHEN SoLuongDaDat + 1 >= SoLuongToiDa THEN 'Day' ELSE TrangThai END
        WHERE MaLich = ?");
    $stmt->execute([$maLich]);

    $pdo->commit();
    header('Location: ../appointment.php?success=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../appointment.php?error=' . urlencode($e->getMessage()));
    exit;
}
