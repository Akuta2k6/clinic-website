<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

function clinicMailLoadConfig(): array
{
    $path = __DIR__ . '/../config/mail.php';
    if (!is_file($path)) {
        throw new RuntimeException('Chưa cấu hình file config/mail.php.');
    }
    return require $path;
}

function clinicMailEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function clinicMailDate($value): string
{
    $timestamp = strtotime((string)$value);
    return $timestamp === false ? (string)$value : date('d/m/Y', $timestamp);
}

function clinicMailTimeRange(array $data): string
{
    $start = substr((string)($data['GioBatDau'] ?? ''), 0, 5);
    $end = substr((string)($data['GioKetThuc'] ?? ''), 0, 5);
    return trim($start . ' - ' . $end, ' -');
}

function clinicMailCreateMailer(array $config, array $data): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['port'];
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($data['Email'], $data['HoTen']);
    $mail->isHTML(true);

    return $mail;
}

function clinicMailDetailRow(string $label, $value): string
{
    return '<tr>' .
        '<td width="38%" style="padding:12px 14px;border-bottom:1px solid #e8eef5;color:#64748b;font-size:13px;line-height:20px;vertical-align:top;">' . clinicMailEscape($label) . '</td>' .
        '<td style="padding:12px 14px;border-bottom:1px solid #e8eef5;color:#17324d;font-size:14px;font-weight:600;line-height:21px;vertical-align:top;">' . clinicMailEscape($value) . '</td>' .
        '</tr>';
}

function clinicMailDetailCard(array $rows): string
{
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border:1px solid #e4ebf3;border-radius:12px;border-collapse:separate;border-spacing:0;background:#ffffff;overflow:hidden;">';

    foreach ($rows as $row) {
        $html .= clinicMailDetailRow($row[0], $row[1]);
    }

    return $html . '</table>';
}

function clinicMailStatus(string $label, string $background, string $color): string
{
    return '<span style="display:inline-block;padding:7px 12px;border-radius:30px;background:' . clinicMailEscape($background) . ';color:' . clinicMailEscape($color) . ';font-size:12px;font-weight:700;letter-spacing:.3px;">' . clinicMailEscape($label) . '</span>';
}

function clinicMailButton(string $url, string $label): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 8px;"><tr><td align="center" bgcolor="#1769aa" style="border-radius:8px;">' .
        '<a href="' . clinicMailEscape($url) . '" target="_blank" style="display:inline-block;padding:13px 22px;border:1px solid #1769aa;border-radius:8px;color:#ffffff;font-size:14px;font-weight:700;line-height:20px;text-decoration:none;">' . clinicMailEscape($label) . '</a>' .
        '</td></tr></table>';
}

function clinicMailNotice(string $message, string $background = '#eff6ff', string $color = '#24527a', string $border = '#bfdbfe'): string
{
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-top:18px;"><tr><td style="padding:14px 16px;border:1px solid ' . clinicMailEscape($border) . ';border-radius:10px;background:' . clinicMailEscape($background) . ';color:' . clinicMailEscape($color) . ';font-size:13px;line-height:21px;">' . clinicMailEscape($message) . '</td></tr></table>';
}

function clinicMailLayout(
    array $config,
    string $preheader,
    string $eyebrow,
    string $title,
    string $intro,
    string $content,
    string $accent = '#1769aa'
): string {
    $brand = clinicMailEscape($config['from_name'] ?? 'Phòng khám');
    $safeAccent = clinicMailEscape($accent);

    return '<!doctype html>' .
        '<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><meta name="x-apple-disable-message-reformatting"><title>' . clinicMailEscape($title) . '</title></head>' .
        '<body style="margin:0;padding:0;background:#f2f6fb;font-family:Arial,Helvetica,sans-serif;color:#24364b;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">' .
        '<div style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;mso-hide:all;">' . clinicMailEscape($preheader) . '</div>' .
        '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f2f6fb" style="width:100%;background:#f2f6fb;"><tr><td align="center" style="padding:30px 12px;">' .
        '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;border-collapse:separate;border-spacing:0;">' .
        '<tr><td bgcolor="#123451" style="padding:24px 28px;background:#123451;border-radius:14px 14px 0 0;border-bottom:4px solid ' . $safeAccent . ';">' .
        '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>' .
        '<td width="48" valign="middle"><div style="width:42px;height:42px;border-radius:12px;background:#ffffff;color:#1769aa;text-align:center;font-size:29px;font-weight:700;line-height:42px;">+</div></td>' .
        '<td valign="middle" style="padding-left:12px;"><div style="color:#ffffff;font-size:17px;font-weight:700;letter-spacing:1.1px;line-height:22px;">' . $brand . '</div><div style="padding-top:3px;color:#c7d8e8;font-size:11px;letter-spacing:1.2px;line-height:16px;">CHĂM SÓC SỨC KHỎE CỦA BẠN</div></td>' .
        '</tr></table></td></tr>' .
        '<tr><td bgcolor="#ffffff" style="padding:30px 30px 26px;background:#ffffff;">' .
        '<div style="margin-bottom:10px;color:' . $safeAccent . ';font-size:11px;font-weight:700;letter-spacing:1.4px;line-height:17px;text-transform:uppercase;">' . clinicMailEscape($eyebrow) . '</div>' .
        '<h1 style="margin:0 0 14px;color:#17324d;font-size:25px;font-weight:700;line-height:33px;">' . clinicMailEscape($title) . '</h1>' .
        '<p style="margin:0 0 22px;color:#536579;font-size:14px;line-height:24px;">' . $intro . '</p>' .
        $content .
        '<p style="margin:26px 0 0;color:#536579;font-size:13px;line-height:22px;">Trân trọng,<br><strong style="color:#17324d;">' . $brand . '</strong></p>' .
        '</td></tr>' .
        '<tr><td bgcolor="#eaf0f6" style="padding:20px 26px;background:#eaf0f6;border-radius:0 0 14px 14px;text-align:center;">' .
        '<p style="margin:0 0 6px;color:#526579;font-size:12px;font-weight:700;line-height:18px;">' . $brand . '</p>' .
        '<p style="margin:0;color:#7b8b9b;font-size:11px;line-height:18px;">Thông báo tự động từ hệ thống đặt lịch trực tuyến. Nếu cần hỗ trợ, vui lòng liên hệ phòng khám qua kênh liên hệ chính thức.</p>' .
        '</td></tr></table>' .
        '<p style="margin:16px 0 0;color:#94a3b3;font-size:10px;line-height:16px;text-align:center;">© ' . date('Y') . ' ' . $brand . '</p>' .
        '</td></tr></table></body></html>';
}

function guiEmailXacNhanLich(array $data): void
{
    $config = clinicMailLoadConfig();
    $mail = clinicMailCreateMailer($config, $data);
    $mail->Subject = 'Đặt lịch khám thành công - ' . $data['MaTraCuu'];

    $lookupUrl = $config['base_url'] . '/patient/lookup.php?code=' . urlencode($data['MaTraCuu']);
    $rows = [
        ['Mã tra cứu', $data['MaTraCuu']],
        ['Chuyên khoa', $data['TenChuyenKhoa']],
        ['Bác sĩ', $data['TenBacSi']],
        ['Ngày khám', clinicMailDate($data['NgayKham'])],
        ['Thời gian', clinicMailTimeRange($data)],
        ['Phòng khám', $data['TenPhong'] ?? 'Chưa xác định']
    ];

    $content = '<p style="margin:0 0 14px;color:#24364b;font-size:14px;line-height:23px;">Xin chào <strong>' . clinicMailEscape($data['HoTen']) . '</strong>,</p>' .
        '<p style="margin:0 0 16px;color:#536579;font-size:14px;line-height:23px;">Phòng khám đã tiếp nhận yêu cầu đặt lịch của bạn. Vui lòng theo dõi trạng thái lịch hẹn trước ngày khám.</p>' .
        '<div style="margin-bottom:14px;">' . clinicMailStatus('CHỜ XÁC NHẬN', '#fff7e6', '#a16207') . '</div>' .
        clinicMailDetailCard($rows) .
        clinicMailButton($lookupUrl, 'Tra cứu lịch khám') .
        clinicMailNotice('Vui lòng đến trước giờ khám khoảng 15 phút. Hãy lưu lại mã tra cứu để thuận tiện kiểm tra thông tin lịch hẹn.');

    $mail->Body = clinicMailLayout(
        $config,
        'Yêu cầu đặt lịch của bạn đã được ghi nhận. Mã tra cứu: ' . $data['MaTraCuu'],
        'Thông tin đặt lịch',
        'Đặt lịch khám thành công!',
        'Cảm ơn bạn đã tin tưởng lựa chọn dịch vụ của chúng tôi.',
        $content,
        '#1769aa'
    );

    $mail->AltBody =
        "ĐẶT LỊCH KHÁM THÀNH CÔNG\n\n" .
        "Xin chào {$data['HoTen']},\n" .
        "Lịch khám của bạn đã được ghi nhận và đang chờ xác nhận.\n\n" .
        "Mã tra cứu: {$data['MaTraCuu']}\n" .
        "Chuyên khoa: {$data['TenChuyenKhoa']}\n" .
        "Bác sĩ: {$data['TenBacSi']}\n" .
        'Ngày khám: ' . clinicMailDate($data['NgayKham']) . "\n" .
        'Thời gian: ' . clinicMailTimeRange($data) . "\n" .
        'Phòng khám: ' . ($data['TenPhong'] ?? 'Chưa xác định') . "\n\n" .
        "Tra cứu lịch khám: {$lookupUrl}\n" .
        "Vui lòng đến trước giờ khám khoảng 15 phút.";

    $mail->send();
}

function guiEmailHuyLichKham(array $data): void
{
    $config = clinicMailLoadConfig();
    $mail = clinicMailCreateMailer($config, $data);
    $mail->Subject = 'Thông báo hủy lịch khám - ' . $data['MaTraCuu'];

    $ngayKham = clinicMailDate($data['NgayKham']);
    $gioKham = clinicMailTimeRange($data);
    $lyDoHuy = trim((string)($data['LyDoHuy'] ?? 'Lịch khám đã được bác sĩ hủy.'));
    $lookupUrl = $config['base_url'] . '/patient/lookup.php?code=' . urlencode($data['MaTraCuu']);

    $rows = [
        ['Mã tra cứu', $data['MaTraCuu']],
        ['Chuyên khoa', $data['TenChuyenKhoa']],
        ['Bác sĩ', $data['TenBacSi']],
        ['Ngày khám', $ngayKham],
        ['Thời gian', $gioKham],
        ['Phòng khám', $data['TenPhong'] ?? 'Chưa xác định']
    ];

    $content = '<p style="margin:0 0 14px;color:#24364b;font-size:14px;line-height:23px;">Xin chào <strong>' . clinicMailEscape($data['HoTen']) . '</strong>,</p>' .
        '<p style="margin:0 0 16px;color:#536579;font-size:14px;line-height:23px;">Chúng tôi rất tiếc phải thông báo lịch khám dưới đây đã được bác sĩ hủy.</p>' .
        '<div style="margin-bottom:14px;">' . clinicMailStatus('LỊCH ĐÃ HỦY', '#fff0f0', '#b42318') . '</div>' .
        clinicMailDetailCard($rows) .
        '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-top:18px;"><tr><td style="padding:15px 16px;border-left:4px solid #d94841;border-radius:7px;background:#fff5f4;">' .
        '<div style="margin-bottom:6px;color:#9f2d27;font-size:12px;font-weight:700;letter-spacing:.5px;">LÝ DO HỦY LỊCH</div>' .
        '<div style="color:#633b39;font-size:14px;line-height:22px;">' . nl2br(clinicMailEscape($lyDoHuy)) . '</div>' .
        '</td></tr></table>' .
        clinicMailButton($lookupUrl, 'Tra cứu lịch khám') .
        clinicMailNotice('Bạn có thể liên hệ phòng khám hoặc đặt một lịch khám khác. Chúng tôi chân thành xin lỗi vì sự bất tiện này.', '#fff7ed', '#9a3412', '#fed7aa');

    $mail->Body = clinicMailLayout(
        $config,
        'Lịch khám ' . $data['MaTraCuu'] . ' đã được bác sĩ hủy.',
        'Thông báo lịch hẹn',
        'Lịch khám đã được hủy',
        'Thông tin lịch hẹn và lý do hủy được gửi bên dưới để bạn tiện theo dõi.',
        $content,
        '#d94841'
    );

    $mail->AltBody =
        "THÔNG BÁO HỦY LỊCH KHÁM\n\n" .
        "Xin chào {$data['HoTen']},\n" .
        "Lịch khám của bạn đã được bác sĩ hủy.\n\n" .
        "Mã tra cứu: {$data['MaTraCuu']}\n" .
        "Chuyên khoa: {$data['TenChuyenKhoa']}\n" .
        "Bác sĩ: {$data['TenBacSi']}\n" .
        "Ngày khám: {$ngayKham}\n" .
        "Thời gian: {$gioKham}\n" .
        'Phòng khám: ' . ($data['TenPhong'] ?? 'Chưa xác định') . "\n" .
        "Lý do hủy: {$lyDoHuy}\n\n" .
        "Tra cứu lịch khám: {$lookupUrl}\n" .
        "Vui lòng liên hệ phòng khám hoặc đặt một lịch khám khác.";

    $mail->send();
}

function guiEmailOTPHuyLich(array $data, string $otp): void
{
    $config = clinicMailLoadConfig();
    $mail = clinicMailCreateMailer($config, $data);
    $mail->Subject = 'Mã xác minh hủy lịch khám - ' . $data['MaTraCuu'];

    $content = '<p style="margin:0 0 14px;color:#24364b;font-size:14px;line-height:23px;">Xin chào <strong>' . clinicMailEscape($data['HoTen']) . '</strong>,</p>' .
        '<p style="margin:0 0 18px;color:#536579;font-size:14px;line-height:23px;">Chúng tôi nhận được yêu cầu hủy lịch khám của bạn. Hãy sử dụng mã bên dưới để xác minh yêu cầu.</p>' .
        '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 18px;"><tr><td align="center" style="padding:22px 16px;border:1px dashed #8bb8dc;border-radius:12px;background:#f1f8ff;">' .
        '<div style="margin-bottom:9px;color:#53708c;font-size:11px;font-weight:700;letter-spacing:1.3px;">MÃ XÁC MINH CỦA BẠN</div>' .
        '<div style="color:#145b94;font-family:Arial,Helvetica,sans-serif;font-size:34px;font-weight:700;letter-spacing:9px;line-height:42px;">' . clinicMailEscape($otp) . '</div>' .
        '</td></tr></table>' .
        clinicMailNotice('Mã có hiệu lực trong 10 phút và chỉ sử dụng được một lần. Không chia sẻ mã này cho bất kỳ ai.') .
        clinicMailDetailCard([
            ['Mã tra cứu', $data['MaTraCuu']],
            ['Bác sĩ', $data['TenBacSi']],
            ['Ngày khám', clinicMailDate($data['NgayKham'])],
            ['Thời gian', clinicMailTimeRange($data)]
        ]) .
        clinicMailNotice('Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email và không cung cấp mã xác minh cho người khác.', '#fff7ed', '#9a3412', '#fed7aa');

    $mail->Body = clinicMailLayout(
        $config,
        'Mã xác minh hủy lịch của bạn có hiệu lực trong 10 phút.',
        'Bảo mật tài khoản lịch hẹn',
        'Xác minh yêu cầu hủy lịch',
        'Đây là bước xác minh để đảm bảo yêu cầu hủy lịch được thực hiện bởi chính bạn.',
        $content,
        '#1769aa'
    );

    $mail->AltBody =
        "XÁC MINH YÊU CẦU HỦY LỊCH\n\n" .
        "Xin chào {$data['HoTen']},\n" .
        "Mã xác minh của bạn: {$otp}\n" .
        "Mã có hiệu lực trong 10 phút và chỉ sử dụng được một lần.\n\n" .
        "Mã tra cứu: {$data['MaTraCuu']}\n" .
        "Bác sĩ: {$data['TenBacSi']}\n" .
        'Ngày khám: ' . clinicMailDate($data['NgayKham']) . "\n" .
        'Thời gian: ' . clinicMailTimeRange($data) . "\n\n" .
        "Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.";

    $mail->send();
}

function guiEmailBenhNhanHuyLichThanhCong(array $data): void
{
    $config = clinicMailLoadConfig();
    $mail = clinicMailCreateMailer($config, $data);
    $mail->Subject = 'Xác nhận hủy lịch khám thành công - ' . $data['MaTraCuu'];

    $ngayKham = clinicMailDate($data['NgayKham']);
    $gioKham = clinicMailTimeRange($data);
    $lookupUrl = $config['base_url'] . '/patient/lookup.php?code=' . urlencode($data['MaTraCuu']);
    $rows = [
        ['Mã tra cứu', $data['MaTraCuu']],
        ['Chuyên khoa', $data['TenChuyenKhoa']],
        ['Bác sĩ', $data['TenBacSi']],
        ['Ngày khám', $ngayKham],
        ['Thời gian', $gioKham]
    ];

    $content = '<p style="margin:0 0 14px;color:#24364b;font-size:14px;line-height:23px;">Xin chào <strong>' . clinicMailEscape($data['HoTen']) . '</strong>,</p>' .
        '<p style="margin:0 0 16px;color:#536579;font-size:14px;line-height:23px;">Yêu cầu hủy lịch khám của bạn đã được xác minh bằng mã OTP và xử lý thành công.</p>' .
        '<div style="margin-bottom:14px;">' . clinicMailStatus('ĐÃ HỦY THÀNH CÔNG', '#eaf8ef', '#18723b') . '</div>' .
        clinicMailDetailCard($rows) .
        clinicMailButton($lookupUrl, 'Tra cứu lịch khám') .
        clinicMailNotice('Chỗ khám đã được giải phóng cho bệnh nhân khác. Cảm ơn bạn đã chủ động thông báo cho phòng khám.', '#effaf3', '#24613b', '#b7e4c7');

    $mail->Body = clinicMailLayout(
        $config,
        'Yêu cầu hủy lịch khám ' . $data['MaTraCuu'] . ' đã được xử lý thành công.',
        'Xác nhận thao tác',
        'Hủy lịch khám thành công',
        'Yêu cầu của bạn đã được xử lý. Dưới đây là thông tin lịch hẹn đã hủy.',
        $content,
        '#23945b'
    );

    $mail->AltBody =
        "HỦY LỊCH KHÁM THÀNH CÔNG\n\n" .
        "Xin chào {$data['HoTen']},\n" .
        "Yêu cầu hủy lịch khám đã được xác minh bằng OTP và xử lý thành công.\n\n" .
        "Mã tra cứu: {$data['MaTraCuu']}\n" .
        "Chuyên khoa: {$data['TenChuyenKhoa']}\n" .
        "Bác sĩ: {$data['TenBacSi']}\n" .
        "Ngày khám: {$ngayKham}\n" .
        "Thời gian: {$gioKham}\n" .
        "Trạng thái: Đã hủy. Chỗ khám đã được giải phóng.\n\n" .
        "Tra cứu lịch khám: {$lookupUrl}";

    $mail->send();
}

function guiEmailKetQuaKham(array $data, string $pdfContent, string $fileName): void
{
    if (empty($data['Email']) || !filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Bệnh nhân chưa có địa chỉ email hợp lệ.');
    }

    if ($pdfContent === '') {
        throw new RuntimeException('Không tạo được file PDF kết quả khám.');
    }

    $config = clinicMailLoadConfig();
    $mail = clinicMailCreateMailer($config, $data);
    $mail->Subject = 'Kết quả khám bệnh và đơn thuốc - ' . $data['MaTraCuu'];

    $lookupUrl = $config['base_url'] . '/patient/lookup.php?code=' . urlencode($data['MaTraCuu']);
    $content = '<p style="margin:0 0 14px;color:#24364b;font-size:14px;line-height:23px;">Xin chào <strong>' . clinicMailEscape($data['HoTen']) . '</strong>,</p>' .
        '<p style="margin:0 0 18px;color:#536579;font-size:14px;line-height:23px;">Bác sĩ đã hoàn tất quá trình khám và kê đơn cho bạn. Hồ sơ khám bệnh cùng đơn thuốc được đính kèm trong email này.</p>' .
        '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 18px;"><tr><td style="padding:17px 18px;border:1px solid #dce9f5;border-radius:11px;background:#f5f9fd;">' .
        '<div style="color:#1769aa;font-size:12px;font-weight:700;letter-spacing:.7px;">TÀI LIỆU ĐÍNH KÈM</div>' .
        '<div style="padding-top:7px;color:#17324d;font-size:14px;font-weight:700;line-height:21px;">' . clinicMailEscape($fileName) . '</div>' .
        '<div style="padding-top:4px;color:#64748b;font-size:12px;line-height:18px;">Định dạng PDF · Hồ sơ khám bệnh và đơn thuốc</div>' .
        '</td></tr></table>' .
        clinicMailDetailCard([
            ['Mã tra cứu lịch khám', $data['MaTraCuu']]
        ]) .
        clinicMailButton($lookupUrl, 'Tra cứu lịch khám') .
        clinicMailNotice('Vui lòng tải xuống và lưu lại file PDF để tiện theo dõi sức khỏe hoặc sử dụng khi cần tái khám. Nội dung chi tiết được trình bày trong tài liệu đính kèm.');

    $mail->Body = clinicMailLayout(
        $config,
        'Hồ sơ khám bệnh và đơn thuốc của bạn đã có trong file PDF đính kèm.',
        'Thông tin sau buổi khám',
        'Hồ sơ khám bệnh đã hoàn thành',
        'Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của phòng khám.',
        $content,
        '#1769aa'
    );

    $mail->AltBody =
        "KẾT QUẢ KHÁM BỆNH\n\n" .
        "Xin chào {$data['HoTen']},\n" .
        "Bác sĩ đã hoàn tất quá trình khám và kê đơn cho bạn.\n" .
        "Hồ sơ khám bệnh và đơn thuốc được đính kèm trong file PDF.\n\n" .
        "Mã tra cứu: {$data['MaTraCuu']}\n" .
        "Tệp đính kèm: {$fileName}\n" .
        "Tra cứu lịch khám: {$lookupUrl}\n\n" .
        "Vui lòng lưu lại tài liệu để tiện theo dõi và sử dụng khi cần tái khám.\n" .
        "Trân trọng, {$config['from_name']}";

    $mail->addStringAttachment(
        $pdfContent,
        $fileName,
        PHPMailer::ENCODING_BASE64,
        'application/pdf'
    );

    $mail->send();
}