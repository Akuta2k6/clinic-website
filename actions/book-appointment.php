<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../appointment.php');
    exit;
}

function taoMaTraCuu(PDO $pdo): string
{
    do {
        $ma = 'DL' . date('Ymd') . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM DatLich WHERE MaTraCuu=?');
        $stmt->execute([$ma]);
    } while ($stmt->fetchColumn() > 0);

    return $ma;
}

$hoTen = trim($_POST['HoTen'] ?? '');
$email = trim($_POST['Email'] ?? '');
$sdt = trim($_POST['SoDienThoai'] ?? '');
$gioiTinh = trim($_POST['GioiTinh'] ?? '');
$ngaySinh = $_POST['NgaySinh'] ?? null;
$maLich = filter_input(INPUT_POST, 'MaLich', FILTER_VALIDATE_INT);
$lyDo = trim($_POST['LyDoKham'] ?? '');

if ($hoTen === '' || $sdt === '' || $email === '' || !$maLich) {
    header('Location: ../appointment.php?error=' . urlencode('Vui lòng nhập đầy đủ họ tên, số điện thoại, email và lịch khám.'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../appointment.php?error=' . urlencode('Email không hợp lệ.'));
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM LichKham WHERE MaLich=? FOR UPDATE");
    $stmt->execute([$maLich]);
    $lich = $stmt->fetch();

    if (!$lich || $lich['TrangThai'] !== 'Mo' || (int)$lich['SoLuongDaDat'] >= (int)$lich['SoLuongToiDa']) {
        throw new RuntimeException('Khung giờ này đã hết chỗ hoặc không còn mở.');
    }

    $stmt = $pdo->prepare('SELECT MaBN FROM BenhNhan WHERE SoDienThoai=? ORDER BY MaBN DESC LIMIT 1');
    $stmt->execute([$sdt]);
    $benhNhan = $stmt->fetch();

    if ($benhNhan) {
        $maBN = (int)$benhNhan['MaBN'];
        $stmt = $pdo->prepare('UPDATE BenhNhan SET HoTen=?, Email=?, GioiTinh=?, NgaySinh=? WHERE MaBN=?');
        $stmt->execute([$hoTen, $email, $gioiTinh ?: null, $ngaySinh ?: null, $maBN]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO BenhNhan (HoTen, GioiTinh, NgaySinh, SoDienThoai, Email) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$hoTen, $gioiTinh ?: null, $ngaySinh ?: null, $sdt, $email]);
        $maBN = (int)$pdo->lastInsertId();
    }

    $maTraCuu = taoMaTraCuu($pdo);

    $stmt = $pdo->prepare('INSERT INTO DatLich (MaTraCuu, MaBN, MaLich, LyDoKham) VALUES (?, ?, ?, ?)');
    $stmt->execute([$maTraCuu, $maBN, $maLich, $lyDo ?: null]);

    $maDatLich = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("UPDATE LichKham
        SET SoLuongDaDat = SoLuongDaDat + 1,
        TrangThai = CASE WHEN SoLuongDaDat + 1 >= SoLuongToiDa THEN 'Day' ELSE TrangThai END
        WHERE MaLich=?");
    $stmt->execute([$maLich]);

    $pdo->commit();

    $mailStatus = 0;

    try {
        require_once __DIR__ . '/../helpers/send-appointment-mail.php';

        $stmt = $pdo->prepare("
            SELECT dl.MaTraCuu, dl.LyDoKham, dl.TrangThai,
                   bn.HoTen, bn.Email, bn.SoDienThoai,
                   lk.NgayKham, lk.GioBatDau, lk.GioKetThuc,
                   bs.HoTen AS TenBacSi,
                   ck.TenChuyenKhoa,
                   pk.TenPhong, pk.ViTri
            FROM DatLich dl
            JOIN BenhNhan bn ON dl.MaBN=bn.MaBN
            JOIN LichKham lk ON dl.MaLich=lk.MaLich
            JOIN BacSi bs ON lk.MaBS=bs.MaBS
            JOIN ChuyenKhoa ck ON bs.MaCK=ck.MaCK
            LEFT JOIN PhongKham pk ON lk.MaPK=pk.MaPK
            WHERE dl.MaDatLich=?
        ");
        $stmt->execute([$maDatLich]);
        $thongTin = $stmt->fetch();

        if ($thongTin) {
            guiEmailXacNhanLich($thongTin);
            $mailStatus = 1;
        }
     } catch (Throwable $mailError) {
        $mailStatus = 0;
    }

    header('Location: ../appointment.php?success=1&code=' . urlencode($maTraCuu) . '&mail=' . $mailStatus);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ../appointment.php?error=' . urlencode($e->getMessage()));
    exit;
}