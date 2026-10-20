<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
function guiEmailXacNhanLich(array $data): void
{
    $config = require __DIR__ . '/../config/mail.php';

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['port'];

    $mail->CharSet = 'UTF-8';

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($data['Email'], $data['HoTen']);

    $mail->isHTML(true);
    $mail->Subject = 'Xác nhận đặt lịch khám - ' . $data['MaTraCuu'];

    $lookupUrl = $config['base_url'] . '/patient/lookup.php?code=' . urlencode($data['MaTraCuu']);

    $mail->Body = '
        <h2>Đặt lịch khám thành công</h2>

        <p>Xin chào <strong>' . htmlspecialchars($data['HoTen']) . '</strong>,</p>

        <p>Lịch khám của bạn đã được ghi nhận.</p>

        <p><strong>Mã tra cứu:</strong> ' . htmlspecialchars($data['MaTraCuu']) . '</p>
        <p><strong>Chuyên khoa:</strong> ' . htmlspecialchars($data['TenChuyenKhoa']) . '</p>
        <p><strong>Bác sĩ:</strong> ' . htmlspecialchars($data['TenBacSi']) . '</p>
        <p><strong>Ngày khám:</strong> ' . date('d/m/Y', strtotime($data['NgayKham'])) . '</p>
        <p><strong>Thời gian:</strong> ' . substr($data['GioBatDau'], 0, 5) . ' - ' . substr($data['GioKetThuc'], 0, 5) . '</p>
        <p><strong>Phòng:</strong> ' . htmlspecialchars($data['TenPhong'] ?? 'Chưa xác định') . '</p>

        <p><strong>Trạng thái:</strong> Chờ xác nhận</p>

        <p>Bạn có thể tra cứu lịch tại:</p>

        <p><a href="' . htmlspecialchars($lookupUrl) . '">Tra cứu lịch khám</a></p>

        <p>Vui lòng đến trước giờ khám khoảng 15 phút.</p>
    ';

    $mail->AltBody =
        "Đặt lịch khám thành công\n" .
        "Mã tra cứu: {$data['MaTraCuu']}\n" .
        "Bác sĩ: {$data['TenBacSi']}\n" .
        "Ngày khám: {$data['NgayKham']}\n" .
        "Thời gian: {$data['GioBatDau']} - {$data['GioKetThuc']}";

    $mail->send();
}