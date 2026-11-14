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

function guiEmailHuyLichLamViec(array $data): void
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
    $mail->Subject = 'Thông báo hủy lịch khám - ' . $data['MaTraCuu'];

    $ngayKham = date('d/m/Y', strtotime($data['NgayKham']));
    $gioKham = substr($data['GioBatDau'], 0, 5) . ' - ' . substr($data['GioKetThuc'], 0, 5);
    $lyDoHuy = trim($data['LyDoHuy'] ?? '');
    $lyDoHtml = $lyDoHuy !== '' ? '<p><strong>Lý do:</strong> ' . nl2br(htmlspecialchars($lyDoHuy)) . '</p>' : '';

    $mail->Body = '
        <h2>Thông báo hủy lịch khám</h2>
        <p>Xin chào <strong>' . htmlspecialchars($data['HoTen']) . '</strong>,</p>
        <p>Chúng tôi rất tiếc phải thông báo lịch khám của bạn đã bị hủy do bác sĩ không thể thực hiện lịch làm việc này.</p>
        <p><strong>Mã tra cứu:</strong> ' . htmlspecialchars($data['MaTraCuu']) . '</p>
        <p><strong>Chuyên khoa:</strong> ' . htmlspecialchars($data['TenChuyenKhoa']) . '</p>
        <p><strong>Bác sĩ:</strong> ' . htmlspecialchars($data['TenBacSi']) . '</p>
        <p><strong>Ngày khám:</strong> ' . $ngayKham . '</p>
        <p><strong>Thời gian:</strong> ' . $gioKham . '</p>
        <p><strong>Phòng:</strong> ' . htmlspecialchars($data['TenPhong'] ?? 'Chưa xác định') . '</p>
        ' . $lyDoHtml . '
        <p>Vui lòng liên hệ phòng khám hoặc đặt một lịch khám khác. Chúng tôi chân thành xin lỗi vì sự bất tiện này.</p>
    ';

    $mail->AltBody = "Thông báo hủy lịch khám\n" .
        "Mã tra cứu: {$data['MaTraCuu']}\n" .
        "Bác sĩ: {$data['TenBacSi']}\n" .
        "Ngày khám: {$ngayKham}\n" .
        "Thời gian: {$gioKham}\n" .
        ($lyDoHuy !== '' ? "Lý do: {$lyDoHuy}\n" : '') .
        "Vui lòng liên hệ phòng khám hoặc đặt một lịch khám khác.";

    $mail->send();
}

function guiEmailHuyLichKham(array $data): void
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
    $mail->Subject = 'Thông báo hủy lịch khám - ' . $data['MaTraCuu'];

    $ngayKham = date('d/m/Y', strtotime($data['NgayKham']));
    $gioKham = substr($data['GioBatDau'], 0, 5) . ' - ' . substr($data['GioKetThuc'], 0, 5);
    $lyDoHuy = trim($data['LyDoHuy'] ?? 'Lịch khám đã được bác sĩ hủy.');
    $lookupUrl = $config['base_url'] . '/patient/lookup.php?code=' . urlencode($data['MaTraCuu']);

    $mail->Body = '
        <h2>Thông báo hủy lịch khám</h2>
        <p>Xin chào <strong>' . htmlspecialchars($data['HoTen']) . '</strong>,</p>
        <p>Chúng tôi rất tiếc phải thông báo lịch khám của bạn đã được bác sĩ hủy.</p>
        <p><strong>Mã tra cứu:</strong> ' . htmlspecialchars($data['MaTraCuu']) . '</p>
        <p><strong>Chuyên khoa:</strong> ' . htmlspecialchars($data['TenChuyenKhoa']) . '</p>
        <p><strong>Bác sĩ:</strong> ' . htmlspecialchars($data['TenBacSi']) . '</p>
        <p><strong>Ngày khám:</strong> ' . $ngayKham . '</p>
        <p><strong>Thời gian:</strong> ' . $gioKham . '</p>
        <p><strong>Phòng:</strong> ' . htmlspecialchars($data['TenPhong'] ?? 'Chưa xác định') . '</p>
        <p><strong>Lý do:</strong> ' . nl2br(htmlspecialchars($lyDoHuy)) . '</p>
        <p>Vui lòng liên hệ phòng khám hoặc đặt một lịch khám khác.</p>
        <p><a href="' . htmlspecialchars($lookupUrl) . '">Tra cứu lịch khám</a></p>
        <p>Chúng tôi chân thành xin lỗi vì sự bất tiện này.</p>
    ';

    $mail->AltBody =
        "Thông báo hủy lịch khám\n" .
        "Mã tra cứu: {$data['MaTraCuu']}\n" .
        "Bác sĩ: {$data['TenBacSi']}\n" .
        "Ngày khám: {$ngayKham}\n" .
        "Thời gian: {$gioKham}\n" .
        "Lý do: {$lyDoHuy}\n" .
        "Vui lòng liên hệ phòng khám hoặc đặt một lịch khám khác.";

    $mail->send();
}

