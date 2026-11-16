<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['patient_lookup_csrf'])) {
    $_SESSION['patient_lookup_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['patient_lookup_csrf'];
$result = null;
$error = '';
$notice = '';
$success = '';
$showOtpForm = false;
$cancelled = false;
$cancelMailFailed = false;
$maTraCuu = strtoupper(trim((string)($_POST['MaTraCuu'] ?? $_GET['code'] ?? '')));
$sdtRaw = trim((string)($_POST['SoDienThoai'] ?? ''));
$sdt = preg_replace('/[\s.\-()]+/', '', $sdtRaw);
$action = $_POST['action'] ?? 'lookup';

class PatientCancellationException extends RuntimeException
{
}

function layLichTheoTraCuu(PDO $pdo, string $maTraCuu, string $sdt, bool $forUpdate = false): ?array
{
    $sql = "
        SELECT dl.MaDatLich, dl.MaTraCuu, dl.MaLich, dl.NgayDat, dl.LyDoKham, dl.TrangThai,
               bn.HoTen, bn.SoDienThoai, bn.Email,
               lk.NgayKham, lk.GioBatDau, lk.GioKetThuc,
               TIMESTAMP(lk.NgayKham, lk.GioBatDau) > NOW() AS ConHuyDuoc,
               bs.HoTen AS TenBacSi,
               ck.TenChuyenKhoa,
               pk.TenPhong, pk.ViTri
        FROM DatLich dl
        JOIN BenhNhan bn ON dl.MaBN = bn.MaBN
        JOIN LichKham lk ON dl.MaLich = lk.MaLich
        JOIN BacSi bs ON lk.MaBS = bs.MaBS
        JOIN ChuyenKhoa ck ON bs.MaCK = ck.MaCK
        LEFT JOIN PhongKham pk ON lk.MaPK = pk.MaPK
        WHERE dl.MaTraCuu = ? AND bn.SoDienThoai = ?
        LIMIT 1";

    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$maTraCuu, $sdt]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function hienTrangThai(string $status): string
{
    return match ($status) {
        'ChoXacNhan' => 'Chờ xác nhận',
        'DaXacNhan' => 'Đã xác nhận',
        'DaKham' => 'Đã khám',
        'DaHuy' => 'Đã hủy',
        'VangMat' => 'Vắng mặt',
        default => $status
    };
}

function cheEmail(string $email): string
{
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) {
        return $email;
    }

    $name = $parts[0];
    $visible = substr($name, 0, 1);
    return $visible . str_repeat('*', max(3, strlen($name) - 1)) . '@' . $parts[1];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array($action, ['request_cancel_otp', 'confirm_cancel'], true)) {
        $submittedToken = $_POST['csrf_token'] ?? '';
        if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
            $error = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.';
        }
    }

    if ($error === '' && $action === 'lookup') {
        if ($maTraCuu === '' || $sdt === '') {
            $error = 'Vui lòng nhập đầy đủ mã tra cứu và số điện thoại.';
        } else {
            $result = layLichTheoTraCuu($pdo, $maTraCuu, $sdt);
            if (!$result) {
                $error = 'Không tìm thấy lịch khám phù hợp.';
            }
        }
    } elseif ($error === '' && $action === 'request_cancel_otp') {
        if ($maTraCuu === '' || $sdt === '') {
            $error = 'Thiếu thông tin tra cứu lịch khám. Vui lòng tra cứu lại.';
        } else {
            $appointment = layLichTheoTraCuu($pdo, $maTraCuu, $sdt);

            if (!$appointment) {
                $error = 'Không tìm thấy lịch khám phù hợp.';
            } elseif (!in_array($appointment['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true)) {
                $error = 'Lịch khám này không còn ở trạng thái có thể hủy.';
            } elseif ((int)$appointment['ConHuyDuoc'] !== 1) {
                $error = 'Không thể hủy vì lịch khám đã đến giờ bắt đầu hoặc đã qua.';
            } elseif (empty($appointment['Email']) || !filter_var($appointment['Email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Lịch khám chưa có địa chỉ email hợp lệ để xác minh.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $lockBooking = $pdo->prepare("SELECT dl.TrangThai, (TIMESTAMP(lk.NgayKham, lk.GioBatDau) > NOW()) AS ConHuyDuoc FROM DatLich dl JOIN LichKham lk ON dl.MaLich = lk.MaLich WHERE dl.MaDatLich = ? FOR UPDATE");
                    $lockBooking->execute([$appointment['MaDatLich']]);
                    $lockedBooking = $lockBooking->fetch();
                    if (!$lockedBooking || !in_array($lockedBooking['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true) || (int)$lockedBooking['ConHuyDuoc'] !== 1) {
                        throw new PatientCancellationException('Lịch khám không còn ở trạng thái có thể hủy. Vui lòng tra cứu lại.');
                    }

                    $otpStmt = $pdo->prepare('SELECT MaOTPHash, ThoiGianTao, TIMESTAMPDIFF(SECOND, ThoiGianTao, NOW()) AS SoGiayDaQua FROM XacMinhHuyLich WHERE MaDatLich = ? FOR UPDATE');
                    $otpStmt->execute([$appointment['MaDatLich']]);
                    $otpRecord = $otpStmt->fetch();

                    if ($otpRecord && (int)$otpRecord['SoGiayDaQua'] < 60) {
                        $waitSeconds = max(1, 60 - (int)$otpRecord['SoGiayDaQua']);
                        $pdo->commit();
                        $result = $appointment;
                        $showOtpForm = true;
                        $notice = 'Mã xác minh vừa được gửi. Vui lòng kiểm tra email hoặc thử gửi mã mới sau ' . $waitSeconds . ' giây.';
                    } else {
                        $otp = (string)random_int(100000, 999999);
                        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

                        if ($otpRecord) {
                            $saveOtp = $pdo->prepare("UPDATE XacMinhHuyLich SET MaOTPHash = ?, ThoiGianTao = NOW(), ThoiGianHetHan = DATE_ADD(NOW(), INTERVAL 10 MINUTE), SoLanNhapSai = 0 WHERE MaDatLich = ?");
                            $saveOtp->execute([$otpHash, $appointment['MaDatLich']]);
                        } else {
                            $saveOtp = $pdo->prepare("INSERT INTO XacMinhHuyLich (MaDatLich, MaOTPHash, ThoiGianTao, ThoiGianHetHan, SoLanNhapSai) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)");
                            $saveOtp->execute([$appointment['MaDatLich'], $otpHash]);
                        }

                        $pdo->commit();

                        try {
                            require_once __DIR__ . '/../helpers/send-appointment-mail.php';
                            guiEmailOTPHuyLich($appointment, $otp);
                            $result = $appointment;
                            $showOtpForm = true;
                            $notice = 'Mã xác minh đã được gửi đến ' . cheEmail($appointment['Email']) . '. Mã có hiệu lực trong 10 phút.';
                        } catch (Throwable $mailError) {
                            $deleteOtp = $pdo->prepare('DELETE FROM XacMinhHuyLich WHERE MaDatLich = ? AND MaOTPHash = ?');
                            $deleteOtp->execute([$appointment['MaDatLich'], $otpHash]);
                            error_log('[Clinic] Email OTP hủy lịch thất bại: ' . $mailError->getMessage());
                            $result = $appointment;
                            $error = 'Không gửi được mã xác minh đến email. Vui lòng kiểm tra kết nối email hoặc thử lại sau.';
                        }
                    }
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if ($e instanceof PatientCancellationException) {
                        $error = $e->getMessage();
                    } else {
                        error_log('[Clinic] Tạo OTP hủy lịch thất bại: ' . $e->getMessage());
                        $error = 'Không thể tạo mã xác minh. Vui lòng thử lại sau.';
                    }
                    $result = layLichTheoTraCuu($pdo, $maTraCuu, $sdt);
                }
            }
        }
    } elseif ($error === '' && $action === 'confirm_cancel') {
        $otp = trim($_POST['MaOTP'] ?? '');

        if ($maTraCuu === '' || $sdt === '') {
            $error = 'Thiếu thông tin tra cứu lịch khám. Vui lòng tra cứu lại.';
        } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {
            $error = 'Vui lòng nhập chính xác mã xác minh.';
            $result = layLichTheoTraCuu($pdo, $maTraCuu, $sdt);
            $showOtpForm = $result && in_array($result['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true) && (int)$result['ConHuyDuoc'] === 1;
        } else {
            try {
                $pdo->beginTransaction();
                $appointment = layLichTheoTraCuu($pdo, $maTraCuu, $sdt, true);

                if (!$appointment) {
                    throw new PatientCancellationException('Không tìm thấy lịch khám phù hợp.');
                }

                if (!in_array($appointment['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true)) {
                    throw new PatientCancellationException('Lịch khám này không còn ở trạng thái có thể hủy.');
                }

                if ((int)$appointment['ConHuyDuoc'] !== 1) {
                    throw new PatientCancellationException('Không thể hủy vì lịch khám đã đến giờ bắt đầu hoặc đã qua.');
                }

                $slotLock = $pdo->prepare('SELECT MaLich FROM LichKham WHERE MaLich = ? FOR UPDATE');
                $slotLock->execute([$appointment['MaLich']]);
                if (!$slotLock->fetchColumn()) {
                    throw new PatientCancellationException('Không tìm thấy khung giờ khám.');
                }

                $otpStmt = $pdo->prepare('SELECT MaOTPHash, ThoiGianHetHan, SoLanNhapSai, (ThoiGianHetHan > NOW()) AS ConHan FROM XacMinhHuyLich WHERE MaDatLich = ? FOR UPDATE');
                $otpStmt->execute([$appointment['MaDatLich']]);
                $otpRecord = $otpStmt->fetch();

                if (!$otpRecord) {
                    throw new PatientCancellationException('Không tìm thấy mã xác minh. Vui lòng yêu cầu gửi mã mới.');
                }

                if ((int)$otpRecord['ConHan'] !== 1) {
                    $deleteOtp = $pdo->prepare('DELETE FROM XacMinhHuyLich WHERE MaDatLich = ?');
                    $deleteOtp->execute([$appointment['MaDatLich']]);
                    $pdo->commit();
                    $result = $appointment;
                    $error = 'Mã xác minh đã hết hạn. Vui lòng yêu cầu gửi mã mới.';
                } elseif ((int)$otpRecord['SoLanNhapSai'] >= 5) {
                    $deleteOtp = $pdo->prepare('DELETE FROM XacMinhHuyLich WHERE MaDatLich = ?');
                    $deleteOtp->execute([$appointment['MaDatLich']]);
                    $pdo->commit();
                    $result = $appointment;
                    $error = 'Bạn đã nhập sai mã quá 5 lần. Vui lòng yêu cầu mã xác minh mới.';
                } elseif (!password_verify($otp, $otpRecord['MaOTPHash'])) {
                    $remaining = max(0, 4 - (int)$otpRecord['SoLanNhapSai']);
                    if ($remaining === 0) {
                        $deleteOtp = $pdo->prepare('DELETE FROM XacMinhHuyLich WHERE MaDatLich = ?');
                        $deleteOtp->execute([$appointment['MaDatLich']]);
                    } else {
                        $updateAttempts = $pdo->prepare('UPDATE XacMinhHuyLich SET SoLanNhapSai = SoLanNhapSai + 1 WHERE MaDatLich = ?');
                        $updateAttempts->execute([$appointment['MaDatLich']]);
                    }
                    $pdo->commit();
                    $result = $appointment;
                    $showOtpForm = $remaining > 0;
                    $error = $remaining > 0
                        ? 'Mã xác minh không chính xác. Bạn còn ' . $remaining . ' lần nhập.'
                        : 'Bạn đã nhập sai mã 5 lần. Vui lòng yêu cầu mã xác minh mới.';
                } else {
                    $cancelBooking = $pdo->prepare("UPDATE DatLich SET TrangThai = 'DaHuy' WHERE MaDatLich = ? AND TrangThai IN ('ChoXacNhan', 'DaXacNhan')");
                    $cancelBooking->execute([$appointment['MaDatLich']]);

                    if ($cancelBooking->rowCount() !== 1) {
                        throw new PatientCancellationException('Lịch khám vừa được xử lý bởi một thao tác khác.');
                    }

                    $releaseSlot = $pdo->prepare("UPDATE LichKham SET SoLuongDaDat = GREATEST(SoLuongDaDat - 1, 0), TrangThai = CASE WHEN TrangThai = 'Day' THEN 'Mo' ELSE TrangThai END WHERE MaLich = ?");
                    $releaseSlot->execute([$appointment['MaLich']]);

                    $deleteOtp = $pdo->prepare('DELETE FROM XacMinhHuyLich WHERE MaDatLich = ?');
                    $deleteOtp->execute([$appointment['MaDatLich']]);

                    $pdo->commit();
                    $cancelled = true;
                    $success = 'Bạn đã hủy lịch khám thành công.';

                    try {
                        require_once __DIR__ . '/../helpers/send-appointment-mail.php';
                        guiEmailBenhNhanHuyLichThanhCong($appointment);
                    } catch (Throwable $mailError) {
                        $cancelMailFailed = true;
                        error_log('[Clinic] Email xác nhận bệnh nhân hủy lịch thất bại: ' . $mailError->getMessage());
                    }

                    $result = layLichTheoTraCuu($pdo, $maTraCuu, $sdt);
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e instanceof PatientCancellationException ? $e->getMessage() : 'Không thể xác minh và hủy lịch. Vui lòng thử lại sau.';
                if (!($e instanceof PatientCancellationException)) {
                    error_log('[Clinic] Xác minh hủy lịch thất bại: ' . $e->getMessage());
                }
                $result = layLichTheoTraCuu($pdo, $maTraCuu, $sdt);
                $showOtpForm = $result && in_array($result['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true) && (int)$result['ConHuyDuoc'] === 1;
            }
        }
    }
}
?>

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tra cứu lịch khám</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="container py-5" style="max-width:800px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Tra cứu lịch khám</h2>
        <a href="../index.html" class="btn btn-outline-secondary">Trang chủ</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($notice): ?>
        <div class="alert alert-info"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($cancelMailFailed): ?>
        <div class="alert alert-warning">Lịch khám đã được hủy thành công nhưng email xác nhận chưa gửi được. Vui lòng liên hệ phòng khám nếu bạn cần xác nhận lại.</div>
    <?php elseif ($cancelled): ?>
        <div class="alert alert-success">Email xác nhận hủy lịch đã được gửi đến địa chỉ email của bạn.</div>
    <?php endif; ?>

    <form method="post" class="card card-body shadow-sm mb-4">
        <input type="hidden" name="action" value="lookup">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Mã tra cứu</label>
                <input name="MaTraCuu" class="form-control" value="<?= htmlspecialchars($maTraCuu, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Số điện thoại</label>
                <input name="SoDienThoai" class="form-control" value="<?= htmlspecialchars($sdtRaw, ENT_QUOTES, 'UTF-8') ?>" <?= ($maTraCuu !== '' && $sdtRaw === '') ? 'autofocus' : '' ?> required>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Tra cứu</button>
            </div>
        </div>
    </form>

    <?php if ($result): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h4 class="mb-3">Thông tin lịch khám</h4>
                <p><strong>Mã tra cứu:</strong> <?= htmlspecialchars($result['MaTraCuu'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Bệnh nhân:</strong> <?= htmlspecialchars($result['HoTen'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($result['SoDienThoai'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($result['Email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <hr>
                <p><strong>Chuyên khoa:</strong> <?= htmlspecialchars($result['TenChuyenKhoa'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Bác sĩ:</strong> <?= htmlspecialchars($result['TenBacSi'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Ngày khám:</strong> <?= date('d/m/Y', strtotime($result['NgayKham'])) ?></p>
                <p><strong>Thời gian:</strong> <?= substr($result['GioBatDau'], 0, 5) ?> - <?= substr($result['GioKetThuc'], 0, 5) ?></p>
                <p><strong>Phòng:</strong> <?= htmlspecialchars($result['TenPhong'] ?? 'Chưa xác định', ENT_QUOTES, 'UTF-8') ?><?= $result['ViTri'] ? ' - ' . htmlspecialchars($result['ViTri'], ENT_QUOTES, 'UTF-8') : '' ?></p>
                <p><strong>Lý do khám:</strong> <?= htmlspecialchars($result['LyDoKham'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-0"><strong>Trạng thái:</strong> <?= htmlspecialchars(hienTrangThai($result['TrangThai']), ENT_QUOTES, 'UTF-8') ?></p>

                <?php if (!$cancelled && in_array($result['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true) && (int)$result['ConHuyDuoc'] === 1): ?>
                    <hr>
                    <?php if ($showOtpForm): ?>
                        <h5 class="mb-2">Xác minh hủy lịch</h5>
                        <p class="text-muted">Nhập mã OTP đã được gửi đến email của bạn. Mã có hiệu lực trong 10 phút.</p>
                        <form method="post" class="row g-2">
                            <input type="hidden" name="action" value="confirm_cancel">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="MaTraCuu" value="<?= htmlspecialchars($maTraCuu, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="SoDienThoai" value="<?= htmlspecialchars($sdtRaw, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="col-md-6">
                                <input type="text" name="MaOTP" class="form-control" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" minlength="6" placeholder="Nhập mã OTP" required>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-danger">Xác nhận hủy lịch</button>
                            </div>
                        </form>
                        <form method="post" class="mt-2">
                            <input type="hidden" name="action" value="request_cancel_otp">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="MaTraCuu" value="<?= htmlspecialchars($maTraCuu, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="SoDienThoai" value="<?= htmlspecialchars($sdtRaw, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-link btn-sm p-0">Gửi lại mã xác minh</button>
                        </form>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="action" value="request_cancel_otp">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="MaTraCuu" value="<?= htmlspecialchars($maTraCuu, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="SoDienThoai" value="<?= htmlspecialchars($sdtRaw, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-outline-danger">Hủy lịch khám</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>