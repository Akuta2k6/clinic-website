<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

function clinicContactEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function clinicContactMailSettings(): array
{
    $mailConfigPath = __DIR__ . '/../config/mail.php';
    $contactConfigPath = __DIR__ . '/../config/contact.php';

    if (!is_file($mailConfigPath) || !is_file($contactConfigPath)) {
        throw new RuntimeException('Chưa cấu hình thông tin gửi email liên hệ.');
    }

    $mailConfig = require $mailConfigPath;
    $contactConfig = require $contactConfigPath;

    foreach (['host', 'port', 'username', 'password', 'from_email'] as $key) {
        if (empty($mailConfig[$key])) {
            throw new RuntimeException('Thiếu cấu hình SMTP: ' . $key);
        }
    }

    if (empty($contactConfig['receiving_email']) || !filter_var($contactConfig['receiving_email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Email tiếp nhận liên hệ chưa hợp lệ.');
    }

    return [$mailConfig, $contactConfig];
}

function clinicContactCreateMailer(array $mailConfig, array $contactConfig): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $mailConfig['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $mailConfig['username'];
    $mail->Password = $mailConfig['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)$mailConfig['port'];
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Timeout = 15;
    $mail->setFrom($mailConfig['from_email'], $contactConfig['hospital_name']);
    $mail->isHTML(true);

    return $mail;
}

function clinicContactMailLayout(string $hospitalName, string $title, string $intro, string $content): string
{
    $brand = clinicContactEscape($hospitalName);

    return '<!doctype html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>' . clinicContactEscape($title) . '</title></head>' .
        '<body style="margin:0;padding:24px;background:#f2f6fb;font-family:Arial,Helvetica,sans-serif;color:#24364b;">' .
        '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;">' .
        '<tr><td style="padding:22px 28px;background:#123451;color:#ffffff;border-bottom:4px solid #2785c7;"><strong style="font-size:18px;">' . $brand . '</strong><div style="margin-top:5px;color:#c7d8e8;font-size:11px;letter-spacing:1px;">BỘ PHẬN TIẾP NHẬN THÔNG TIN</div></td></tr>' .
        '<tr><td style="padding:28px;"><h1 style="margin:0 0 14px;color:#17324d;font-size:23px;line-height:1.35;">' . clinicContactEscape($title) . '</h1>' .
        '<p style="margin:0 0 20px;color:#536579;font-size:14px;line-height:1.7;">' . $intro . '</p>' . $content .
        '<p style="margin:26px 0 0;color:#536579;font-size:13px;line-height:1.7;">Trân trọng,<br><strong style="color:#17324d;">' . $brand . '</strong></p></td></tr>' .
        '<tr><td style="padding:16px 24px;background:#eaf0f6;color:#7b8b9b;font-size:11px;line-height:1.6;text-align:center;">Email được gửi tự động từ hệ thống liên hệ của bệnh viện. Vui lòng không gửi thông tin bệnh án qua email này.</td></tr>' .
        '</table></body></html>';
}

function clinicContactSendNotification(array $contact): void
{
    [$mailConfig, $contactConfig] = clinicContactMailSettings();
    $mail = clinicContactCreateMailer($mailConfig, $contactConfig);
    $mail->addAddress($contactConfig['receiving_email'], $contactConfig['hospital_name']);
    $mail->addReplyTo($contact['Email'], $contact['HoTen']);
    $mail->Subject = '[Liên hệ #' . (int)$contact['MaLienHe'] . '] ' . $contact['ChuDe'];

    $rows = [
        ['Mã liên hệ', '#' . (int)$contact['MaLienHe']],
        ['Họ và tên', $contact['HoTen']],
        ['Email', $contact['Email']],
        ['Số điện thoại', $contact['SoDienThoai'] !== '' ? $contact['SoDienThoai'] : 'Không cung cấp'],
        ['Chủ đề', $contact['ChuDe']]
    ];
    $table = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';
    foreach ($rows as [$label, $value]) {
        $table .= '<tr><td style="padding:10px;border-bottom:1px solid #e8eef5;color:#64748b;font-size:13px;">' . clinicContactEscape($label) . '</td><td style="padding:10px;border-bottom:1px solid #e8eef5;color:#17324d;font-size:14px;font-weight:600;">' . clinicContactEscape($value) . '</td></tr>';
    }
    $table .= '</table><h3 style="margin:22px 0 8px;color:#17324d;font-size:15px;">Nội dung liên hệ</h3><div style="padding:15px;border-radius:10px;background:#f7f9fc;color:#34445c;font-size:14px;line-height:1.7;white-space:pre-wrap;">' . nl2br(clinicContactEscape($contact['NoiDung'])) . '</div>';

    $mail->Body = clinicContactMailLayout(
        $contactConfig['hospital_name'],
        'Có yêu cầu liên hệ mới',
        'Hệ thống vừa ghi nhận một yêu cầu liên hệ từ khách hàng.',
        $table
    );
    $mail->AltBody = "LIÊN HỆ MỚI #" . (int)$contact['MaLienHe'] . "\n" .
        "Họ tên: {$contact['HoTen']}\nEmail: {$contact['Email']}\nSố điện thoại: {$contact['SoDienThoai']}\n" .
        "Chủ đề: {$contact['ChuDe']}\nNội dung: {$contact['NoiDung']}";
    $mail->send();
}

function clinicContactSendConfirmation(array $contact): void
{
    [$mailConfig, $contactConfig] = clinicContactMailSettings();
    $mail = clinicContactCreateMailer($mailConfig, $contactConfig);
    $mail->addAddress($contact['Email'], $contact['HoTen']);
    $mail->Subject = 'Đã tiếp nhận yêu cầu liên hệ #' . (int)$contact['MaLienHe'];

    $content = '<p style="margin:0 0 14px;color:#24364b;font-size:14px;line-height:1.7;">Xin chào <strong>' . clinicContactEscape($contact['HoTen']) . '</strong>,</p>' .
        '<p style="margin:0 0 16px;color:#536579;font-size:14px;line-height:1.7;">Bệnh viện đã tiếp nhận yêu cầu liên hệ của bạn. Bộ phận phụ trách sẽ xem xét và phản hồi trong thời gian sớm nhất.</p>' .
        '<div style="padding:15px;border-radius:10px;background:#f2f7fc;color:#34445c;font-size:13px;line-height:1.8;">' .
        '<strong>Mã liên hệ:</strong> #' . (int)$contact['MaLienHe'] . '<br>' .
        '<strong>Chủ đề:</strong> ' . clinicContactEscape($contact['ChuDe']) . '<br>' .
        '<strong>Nội dung:</strong><br>' . nl2br(clinicContactEscape($contact['NoiDung'])) . '</div>' .
        '<p style="margin:18px 0 0;color:#536579;font-size:13px;line-height:1.7;">Nếu cần hỗ trợ khẩn cấp, vui lòng gọi số cấp cứu 115. Không trả lời email này để gửi thông tin bệnh án.</p>';

    $mail->Body = clinicContactMailLayout(
        $contactConfig['hospital_name'],
        'Yêu cầu của bạn đã được ghi nhận',
        'Cảm ơn bạn đã liên hệ với bệnh viện.',
        $content
    );
    $mail->AltBody = "Xin chào {$contact['HoTen']},\nBệnh viện đã tiếp nhận yêu cầu liên hệ #" . (int)$contact['MaLienHe'] . ".\nChủ đề: {$contact['ChuDe']}\nChúng tôi sẽ phản hồi trong thời gian sớm nhất.";
    $mail->send();
}

function clinicContactSendReply(array $contact, string $reply): void
{
    [$mailConfig, $contactConfig] = clinicContactMailSettings();
    $mail = clinicContactCreateMailer($mailConfig, $contactConfig);
    $mail->addAddress($contact['Email'], $contact['HoTen']);
    $mail->addReplyTo($contactConfig['receiving_email'], $contactConfig['hospital_name']);
    $mail->Subject = 'Phản hồi yêu cầu liên hệ #' . (int)$contact['MaLienHe'] . ' - ' . $contact['ChuDe'];

    $content = '<p style="margin:0 0 14px;color:#24364b;font-size:14px;line-height:1.7;">Xin chào <strong>' . clinicContactEscape($contact['HoTen']) . '</strong>,</p>' .
        '<p style="margin:0 0 16px;color:#536579;font-size:14px;line-height:1.7;">Bệnh viện gửi bạn phản hồi cho yêu cầu liên hệ #' . (int)$contact['MaLienHe'] . '.</p>' .
        '<div style="padding:16px;border-left:4px solid #2785c7;border-radius:8px;background:#f2f7fc;color:#34445c;font-size:14px;line-height:1.8;white-space:pre-wrap;">' . nl2br(clinicContactEscape($reply)) . '</div>' .
        '<p style="margin:18px 0 0;color:#536579;font-size:13px;line-height:1.7;">Nếu cần trao đổi thêm, bạn có thể phản hồi email này hoặc liên hệ qua các kênh chính thức của bệnh viện.</p>';

    $mail->Body = clinicContactMailLayout(
        $contactConfig['hospital_name'],
        'Phản hồi từ bệnh viện',
        'Bệnh viện đã phản hồi yêu cầu của bạn.',
        $content
    );
    $mail->AltBody = "Xin chào {$contact['HoTen']},\nPhản hồi cho liên hệ #" . (int)$contact['MaLienHe'] . ":\n\n{$reply}\n\n" . $contactConfig['hospital_name'];
    $mail->send();
}
