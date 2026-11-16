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
$sdtRaw = trim($_POST['SoDienThoai'] ?? '');
$sdt = preg_replace('/[\s.\-()]+/', '', $sdtRaw);
$gioiTinh = trim($_POST['GioiTinh'] ?? '');
$ngaySinh = trim($_POST['NgaySinh'] ?? '');
$maLich = filter_input(INPUT_POST, 'MaLich', FILTER_VALIDATE_INT);
$lyDo = trim($_POST['LyDoKham'] ?? '');

if ($hoTen === '' || $sdt === '' || $email === '' || !$maLich) {
    header('Location: ../appointment.php?error=' . urlencode('Vui lòng nhập đầy đủ họ tên, số điện thoại, email và lịch khám.'));
    exit;
}

if (!preg_match('/^(0|\+84)[0-9]{8,10}$/', $sdt)) {
    header('Location: ../appointment.php?error=' . urlencode('Số điện thoại không hợp lệ.'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../appointment.php?error=' . urlencode('Email không hợp lệ.'));
    exit;
}

if ($gioiTinh !== '' && !in_array($gioiTinh, ['Nam', 'Nữ', 'Khác'], true)) {
    header('Location: ../appointment.php?error=' . urlencode('Giới tính không hợp lệ.'));
    exit;
}

if ($ngaySinh !== '') {
    $dob = DateTime::createFromFormat('!Y-m-d', $ngaySinh);
    if (!$dob || $dob->format('Y-m-d') !== $ngaySinh || $ngaySinh > date('Y-m-d')) {
        header('Location: ../appointment.php?error=' . urlencode('Ngày sinh không hợp lệ.'));
        exit;
    }
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT lk.*,
               (lk.NgayKham < CURDATE() OR (lk.NgayKham = CURDATE() AND lk.GioBatDau <= CURTIME())) AS DaQuaThoiGian,
               bs.TrangThai AS TrangThaiBacSi,
               ck.TrangThai AS TrangThaiChuyenKhoa
        FROM LichKham lk
        JOIN BacSi bs ON lk.MaBS = bs.MaBS
        JOIN ChuyenKhoa ck ON bs.MaCK = ck.MaCK
        WHERE lk.MaLich = ?
        FOR UPDATE
    ");
    $stmt->execute([$maLich]);
    $lich = $stmt->fetch();

    if (!$lich) {
        throw new RuntimeException('Không tìm thấy lịch khám.');
    }

    if ((int)$lich['TrangThaiBacSi'] !== 1 || (int)$lich['TrangThaiChuyenKhoa'] !== 1) {
        throw new RuntimeException('Bác sĩ hoặc chuyên khoa này hiện tạm ngưng nhận lịch.');
    }

    if ((int)$lich['DaQuaThoiGian'] === 1) {
        throw new RuntimeException('Khung giờ khám đã qua. Vui lòng chọn khung giờ hoặc ngày khác.');
    }

    if ($lich['TrangThai'] !== 'Mo' || (int)$lich['SoLuongDaDat'] >= (int)$lich['SoLuongToiDa']) {
        throw new RuntimeException('Khung giờ này đã hết chỗ hoặc không còn mở.');
    }

    $stmt = $pdo->prepare('SELECT MaBN FROM BenhNhan WHERE SoDienThoai=? ORDER BY MaBN DESC LIMIT 1 FOR UPDATE');
    $stmt->execute([$sdt]);
    $benhNhan = $stmt->fetch();

    if ($benhNhan) {
        $maBN = (int)$benhNhan['MaBN'];
        $stmt = $pdo->prepare('UPDATE BenhNhan SET HoTen=?, Email=?, GioiTinh=?, NgaySinh=? WHERE MaBN=?');
        $stmt->execute([$hoTen, $email, $gioiTinh !== '' ? $gioiTinh : null, $ngaySinh !== '' ? $ngaySinh : null, $maBN]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO BenhNhan (HoTen, GioiTinh, NgaySinh, SoDienThoai, Email) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$hoTen, $gioiTinh !== '' ? $gioiTinh : null, $ngaySinh !== '' ? $ngaySinh : null, $sdt, $email]);
        $maBN = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('SELECT MaDatLich, MaTraCuu, TrangThai FROM DatLich WHERE MaBN = ? AND MaLich = ? LIMIT 1 FOR UPDATE');
    $stmt->execute([$maBN, $maLich]);
    $existingBooking = $stmt->fetch();

    $maTraCuu = taoMaTraCuu($pdo);

    if ($existingBooking) {
        if ($existingBooking['TrangThai'] !== 'DaHuy') {
            throw new RuntimeException('Bạn đã đặt khung giờ khám này rồi (Mã tra cứu: ' . $existingBooking['MaTraCuu'] . ').');
        }

        $maDatLich = (int)$existingBooking['MaDatLich'];
        $stmt = $pdo->prepare(
            "UPDATE DatLich
             SET MaTraCuu = ?,
                 NgayDat = NOW(),
                 LyDoKham = ?,
                 TrangThai = 'ChoXacNhan',
                 GhiChu = NULL,
                 TrangThaiGuiKetQua = 'ChuaGui',
                 ThoiGianThuGuiKetQua = NULL,
                 ThoiGianGuiKetQua = NULL,
                 LoiGuiKetQua = NULL
             WHERE MaDatLich = ?"
        );
        $stmt->execute([$maTraCuu, $lyDo !== '' ? $lyDo : null, $maDatLich]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO DatLich (MaTraCuu, MaBN, MaLich, LyDoKham) VALUES (?, ?, ?, ?)');
        $stmt->execute([$maTraCuu, $maBN, $maLich, $lyDo !== '' ? $lyDo : null]);
        $maDatLich = (int)$pdo->lastInsertId();
    }

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
        error_log('[Clinic] Email xác nhận đặt lịch thất bại. Mã tra cứu: ' . $maTraCuu . '. Lỗi: ' . $mailError->getMessage());
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