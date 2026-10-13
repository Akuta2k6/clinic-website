<?php

require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');

require_once __DIR__ . '/../config/database.php';

$maDatLich = filter_input(
    INPUT_POST,
    'MaDatLich',
    FILTER_VALIDATE_INT
);

$trangThai = $_POST['TrangThai'] ?? '';

$allowed = [
    'ChoXacNhan',
    'DaXacNhan',
    'DaKham',
    'DaHuy',
    'VangMat'
];

if (
    !$maDatLich ||
    !in_array($trangThai, $allowed, true)
) {
    header('Location: appointments.php');
    exit;
}


// Lấy bác sĩ đang đăng nhập
$stmt = $pdo->prepare(
    'SELECT MaBS
     FROM BacSi
     WHERE MaTK=?'
);

$stmt->execute([
    $_SESSION['user']['MaTK']
]);

$maBS = $stmt->fetchColumn();


// Kiểm tra lịch có thuộc bác sĩ không
$stmt = $pdo->prepare("SELECT
        dl.MaDatLich,
        dl.TrangThai,
        dl.MaLich
    FROM DatLich dl
    JOIN LichKham lk
        ON dl.MaLich = lk.MaLich
    WHERE dl.MaDatLich=?
      AND lk.MaBS=?
");

$stmt->execute([
    $maDatLich,
    $maBS
]);

$appointment = $stmt->fetch();

if (!$appointment) {
    header('Location: appointments.php');
    exit;
}


/*
 * Nếu chọn Đã khám:
 * Không đánh dấu ngay.
 * Chuyển bác sĩ sang hồ sơ khám.
 */
if ($trangThai === 'DaKham') {

    header(
        'Location: medical-record.php?appointment='
        . $maDatLich
    );

    exit;
}


// Nếu bệnh nhân hủy lịch
if ($trangThai === 'DaHuy') {

    $pdo->beginTransaction();

    try {

        $stmt = $pdo->prepare(
            "UPDATE DatLich
             SET TrangThai='DaHuy'
             WHERE MaDatLich=?"
        );

        $stmt->execute([
            $maDatLich
        ]);


        // Trả lại một chỗ trống cho lịch
        $stmt = $pdo->prepare(
            "UPDATE LichKham
            SET
                SoLuongDaDat =
                    GREATEST(SoLuongDaDat - 1, 0),

                TrangThai =
                    CASE
                        WHEN TrangThai = 'Day'
                        THEN 'Mo'
                        ELSE TrangThai
                    END

            WHERE MaLich=?
        ");

        $stmt->execute([
            $appointment['MaLich']
        ]);


        $pdo->commit();

    } catch (Throwable $e) {

        $pdo->rollBack();

        throw $e;
    }


    header('Location: appointments.php');
    exit;
}


// Các trạng thái còn lại
$stmt = $pdo->prepare(
    'UPDATE DatLich
     SET TrangThai=?
     WHERE MaDatLich=?'
);

$stmt->execute([
    $trangThai,
    $maDatLich
]);

header('Location: appointments.php');
exit;