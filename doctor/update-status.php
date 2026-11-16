<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: appointments.php');
    exit;
}

$maDatLich = filter_input(INPUT_POST, 'MaDatLich', FILTER_VALIDATE_INT);
$trangThai = $_POST['TrangThai'] ?? '';

if (!$maDatLich || !in_array($trangThai, ['DaXacNhan', 'DaHuy'], true)) {
    header('Location: appointments.php');
    exit;
}

$stmt = $pdo->prepare('SELECT MaBS FROM BacSi WHERE MaTK=?');
$stmt->execute([$_SESSION['user']['MaTK']]);
$maBS = $stmt->fetchColumn();

if (!$maBS) {
    header('Location: appointments.php?status_error=1');
    exit;
}

if ($trangThai === 'DaXacNhan') {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "SELECT dl.MaDatLich, dl.TrangThai
             FROM DatLich dl
             JOIN LichKham lk ON dl.MaLich = lk.MaLich
             WHERE dl.MaDatLich = ? AND lk.MaBS = ?
             FOR UPDATE"
        );
        $stmt->execute([$maDatLich, $maBS]);
        $appointment = $stmt->fetch();

        if (!$appointment || $appointment['TrangThai'] !== 'ChoXacNhan') {
            throw new RuntimeException('Lịch khám không ở trạng thái chờ xác nhận.');
        }

        $stmt = $pdo->prepare(
            "UPDATE DatLich
             SET TrangThai = 'DaXacNhan'
             WHERE MaDatLich = ? AND TrangThai = 'ChoXacNhan'"
        );
        $stmt->execute([$maDatLich]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Không thể xác nhận lịch khám.');
        }

        $pdo->commit();
        header('Location: appointments.php?confirmed=1');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[Clinic] Xác nhận lịch khám thất bại: ' . $e->getMessage());
        header('Location: appointments.php?status_error=1');
        exit;
    }
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT
            dl.MaDatLich,
            dl.MaTraCuu,
            dl.MaLich,
            dl.TrangThai,
            bn.HoTen,
            bn.Email,
            lk.NgayKham,
            lk.GioBatDau,
            lk.GioKetThuc,
            bs.HoTen AS TenBacSi,
            ck.TenChuyenKhoa,
            pk.TenPhong
        FROM DatLich dl
        JOIN BenhNhan bn ON dl.MaBN = bn.MaBN
        JOIN LichKham lk ON dl.MaLich = lk.MaLich
        JOIN BacSi bs ON lk.MaBS = bs.MaBS
        JOIN ChuyenKhoa ck ON bs.MaCK = ck.MaCK
        LEFT JOIN PhongKham pk ON lk.MaPK = pk.MaPK
        WHERE dl.MaDatLich = ?
          AND lk.MaBS = ?
        FOR UPDATE"
    );
    $stmt->execute([$maDatLich, $maBS]);
    $appointment = $stmt->fetch();

    if (
        !$appointment ||
        !in_array($appointment['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true)
    ) {
        throw new RuntimeException('Lịch khám không còn ở trạng thái có thể hủy.');
    }

    $stmt = $pdo->prepare(
        "UPDATE DatLich
         SET TrangThai = 'DaHuy'
         WHERE MaDatLich = ?
           AND TrangThai IN ('ChoXacNhan', 'DaXacNhan')"
    );
    $stmt->execute([$maDatLich]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Không thể cập nhật trạng thái lịch khám.');
    }

    $stmt = $pdo->prepare(
        "UPDATE LichKham
         SET
            SoLuongDaDat = GREATEST(SoLuongDaDat - 1, 0),
            TrangThai = CASE
                WHEN TrangThai = 'Day' THEN 'Mo'
                ELSE TrangThai
            END
         WHERE MaLich = ?"
    );
    $stmt->execute([$appointment['MaLich']]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[Clinic] Hủy lịch khám thất bại: ' . $e->getMessage());
    header('Location: appointments.php?cancel_error=1');
    exit;
}

$mailSent = 0;
$mailFailed = 0;
$mailSkipped = 0;

if (empty($appointment['Email']) || !filter_var($appointment['Email'], FILTER_VALIDATE_EMAIL)) {
    $mailSkipped = 1;
} else {
    try {
        require_once __DIR__ . '/../helpers/send-appointment-mail.php';
        guiEmailHuyLichKham($appointment);
        $mailSent = 1;
    } catch (Throwable $mailError) {
        $mailFailed = 1;
        error_log('[Clinic] Email hủy lịch khám thất bại: ' . $mailError->getMessage());
    }
}

header('Location: appointments.php?' . http_build_query([
    'cancelled' => 1,
    'sent' => $mailSent,
    'failed' => $mailFailed,
    'skipped' => $mailSkipped
]));
exit;