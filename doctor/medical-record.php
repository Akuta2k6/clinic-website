<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/portal-layout.php';

function guiKetQuaKham(PDO $pdo, int $maDatLich, string $maBS, bool $guiLai = false): void
{
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            "SELECT
                dl.TrangThai,
                dl.TrangThaiGuiKetQua,
                dl.ThoiGianThuGuiKetQua,
                dl.MaTraCuu,
                bn.HoTen,
                bn.Email
            FROM DatLich dl
            JOIN BenhNhan bn ON dl.MaBN = bn.MaBN
            JOIN LichKham lk ON dl.MaLich = lk.MaLich
            WHERE dl.MaDatLich = ? AND lk.MaBS = ?
            FOR UPDATE"
        );
        $stmt->execute([$maDatLich, $maBS]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            throw new RuntimeException('Không tìm thấy lịch khám thuộc bác sĩ này.');
        }

        if ($guiLai) {
            if ($appointment['TrangThai'] !== 'DaKham') {
                throw new RuntimeException('Chỉ có thể gửi lại kết quả sau khi đã hoàn tất khám.');
            }

            if ($appointment['TrangThaiGuiKetQua'] === 'DaGui') {
                throw new RuntimeException('Email kết quả đã được gửi thành công, không cần gửi lại.');
            }

            if ($appointment['TrangThaiGuiKetQua'] === 'DangGui' && !empty($appointment['ThoiGianThuGuiKetQua'])) {
                $lastAttempt = strtotime($appointment['ThoiGianThuGuiKetQua']);
                if ($lastAttempt !== false && time() - $lastAttempt < 600) {
                    throw new RuntimeException('Hệ thống đang xử lý gửi email. Vui lòng chờ ít nhất 10 phút trước khi thử lại.');
                }
            }
        } else {
            if ($appointment['TrangThaiGuiKetQua'] === 'DaGui') {
                throw new RuntimeException('Email kết quả đã được gửi thành công, không cần gửi lại.');
            }

            if (!in_array($appointment['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true)) {
                throw new RuntimeException('Lịch khám không còn ở trạng thái có thể hoàn tất.');
            }

            $stmt = $pdo->prepare('SELECT MaHS FROM HoSoKham WHERE MaDatLich = ? FOR UPDATE');
            $stmt->execute([$maDatLich]);
            $maHS = (int)$stmt->fetchColumn();

            if (!$maHS) {
                throw new RuntimeException('Hãy lưu hồ sơ khám trước khi hoàn tất.');
            }

            $stmt = $pdo->prepare('SELECT MaDon FROM DonThuoc WHERE MaHS = ? FOR UPDATE');
            $stmt->execute([$maHS]);
            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('Hãy lưu đơn thuốc hoặc xác nhận không kê thuốc trước khi hoàn tất.');
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE DatLich
             SET TrangThai = 'DaKham',
                 TrangThaiGuiKetQua = 'DangGui',
                 ThoiGianThuGuiKetQua = NOW(),
                 LoiGuiKetQua = NULL
             WHERE MaDatLich = ?"
        );
        $stmt->execute([$maDatLich]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    try {
        require_once __DIR__ . '/../helpers/medical-result-pdf.php';
        require_once __DIR__ . '/../helpers/send-appointment-mail.php';

        if (empty($appointment['Email']) || !filter_var($appointment['Email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Bệnh nhân chưa có địa chỉ email hợp lệ. Hãy cập nhật email trước khi gửi kết quả.');
        }

        $pdfContent = taoPdfKetQuaKham($pdo, $maDatLich, $maBS);
        $fileCode = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$appointment['MaTraCuu']);
        $fileName = 'Ket-qua-kham-' . $fileCode . '.pdf';

        guiEmailKetQuaKham($appointment, $pdfContent, $fileName);

        $stmt = $pdo->prepare(
            "UPDATE DatLich
             SET TrangThaiGuiKetQua = 'DaGui',
                 ThoiGianGuiKetQua = NOW(),
                 LoiGuiKetQua = NULL
             WHERE MaDatLich = ? AND TrangThaiGuiKetQua = 'DangGui'"
        );
        $stmt->execute([$maDatLich]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Email đã được gửi nhưng không thể cập nhật trạng thái gửi trong cơ sở dữ liệu.');
        }
    } catch (Throwable $e) {
        error_log('[Clinic] Gửi kết quả khám thất bại: ' . $e->getMessage());

        $message = $e instanceof InvalidArgumentException
            ? $e->getMessage()
            : 'Không tạo hoặc gửi được email kết quả. Vui lòng kiểm tra thư viện PDF, cấu hình SMTP và kết nối.';

        $stmt = $pdo->prepare(
            "UPDATE DatLich
             SET TrangThaiGuiKetQua = 'ThatBai',
                 LoiGuiKetQua = ?
             WHERE MaDatLich = ? AND TrangThaiGuiKetQua = 'DangGui'"
        );
        $storedMessage = function_exists('mb_substr')
            ? mb_substr($message, 0, 500, 'UTF-8')
            : (function_exists('iconv_substr') ? iconv_substr($message, 0, 500, 'UTF-8') : substr($message, 0, 500));
        $stmt->execute([$storedMessage, $maDatLich]);

        throw new RuntimeException($message);
    }
}

$stmt = $pdo->prepare('SELECT MaBS, HoTen FROM BacSi WHERE MaTK = ?');
$stmt->execute([$_SESSION['user']['MaTK']]);
$bs = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bs) {
    exit('Không tìm thấy bác sĩ.');
}

$maDatLich = (int)($_GET['appointment'] ?? $_POST['MaDatLich'] ?? 0);
if (!$maDatLich) {
    exit('Thiếu mã lịch hẹn.');
}

$stmt = $pdo->prepare(
    "SELECT
        dl.*,
        bn.HoTen AS TenBN,
        bn.GioiTinh,
        bn.NgaySinh,
        bn.SoDienThoai,
        bn.Email,
        bn.DiaChi,
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
    WHERE dl.MaDatLich = ? AND lk.MaBS = ?"
);
$stmt->execute([$maDatLich, $bs['MaBS']]);
$a = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$a) {
    http_response_code(403);
    exit('Lịch hẹn không thuộc bác sĩ này.');
}

$error = '';
$ok = '';
$emailStatus = $a['TrangThaiGuiKetQua'] ?? 'ChuaGui';
$isLocked = $a['TrangThai'] === 'DaKham' && in_array($emailStatus, ['DangGui', 'DaGui'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($isLocked && in_array($action, ['save_record', 'save_prescription', 'add_medicine', 'remove_medicine'], true)) {
            throw new RuntimeException('Hồ sơ đang được gửi hoặc đã gửi cho bệnh nhân nên tạm thời không thể chỉnh sửa.');
        }

        if ($action === 'save_record') {
            $chanDoan = trim($_POST['ChanDoan'] ?? '');
            $ketLuan = trim($_POST['KetLuan'] ?? '');
            $huongDieuTri = trim($_POST['HuongDieuTri'] ?? '');
            $ghiChu = trim($_POST['GhiChu'] ?? '');

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT MaHS FROM HoSoKham WHERE MaDatLich = ? FOR UPDATE');
            $stmt->execute([$maDatLich]);
            $maHS = (int)$stmt->fetchColumn();

            if ($maHS) {
                $stmt = $pdo->prepare(
                    'UPDATE HoSoKham
                     SET ChanDoan = ?, KetLuan = ?, HuongDieuTri = ?, GhiChu = ?, ThoiGianKham = NOW()
                     WHERE MaHS = ?'
                );
                $stmt->execute([$chanDoan ?: null, $ketLuan ?: null, $huongDieuTri ?: null, $ghiChu ?: null, $maHS]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO HoSoKham(MaDatLich, ChanDoan, KetLuan, HuongDieuTri, GhiChu)
                     VALUES(?, ?, ?, ?, ?)'
                );
                $stmt->execute([$maDatLich, $chanDoan ?: null, $ketLuan ?: null, $huongDieuTri ?: null, $ghiChu ?: null]);
            }

            $pdo->commit();
            $ok = 'Đã lưu hồ sơ khám. Hãy hoàn tất đơn thuốc trước khi kết thúc lượt khám.';
        } elseif ($action === 'save_prescription') {
            $loiDan = trim($_POST['LoiDan'] ?? '');
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT MaHS FROM HoSoKham WHERE MaDatLich = ? FOR UPDATE');
            $stmt->execute([$maDatLich]);
            $maHS = (int)$stmt->fetchColumn();
            if (!$maHS) {
                throw new RuntimeException('Hãy lưu hồ sơ khám trước khi kê đơn.');
            }

            $stmt = $pdo->prepare('SELECT MaDon FROM DonThuoc WHERE MaHS = ? FOR UPDATE');
            $stmt->execute([$maHS]);
            $maDon = (int)$stmt->fetchColumn();

            if ($maDon) {
                $stmt = $pdo->prepare('UPDATE DonThuoc SET LoiDan = ?, NgayKeDon = NOW() WHERE MaDon = ?');
                $stmt->execute([$loiDan ?: null, $maDon]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO DonThuoc(MaHS, LoiDan) VALUES(?, ?)');
                $stmt->execute([$maHS, $loiDan ?: null]);
            }

            $pdo->commit();
            $ok = 'Đã lưu đơn thuốc hoặc xác nhận không kê thuốc.';
        } elseif ($action === 'add_medicine') {
            $mathuoc = (int)($_POST['MaThuoc'] ?? 0);
            $soLuong = max(1, (int)($_POST['SoLuong'] ?? 1));
            $lieuDung = trim($_POST['LieuDung'] ?? '');
            $cachDung = trim($_POST['CachDung'] ?? '');

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT MaHS FROM HoSoKham WHERE MaDatLich = ? FOR UPDATE');
            $stmt->execute([$maDatLich]);
            $maHS = (int)$stmt->fetchColumn();
            if (!$maHS) {
                throw new RuntimeException('Hãy lưu hồ sơ khám trước.');
            }

            $stmt = $pdo->prepare('SELECT MaDon FROM DonThuoc WHERE MaHS = ? FOR UPDATE');
            $stmt->execute([$maHS]);
            $maDon = (int)$stmt->fetchColumn();
            if (!$maDon) {
                $stmt = $pdo->prepare('INSERT INTO DonThuoc(MaHS) VALUES(?)');
                $stmt->execute([$maHS]);
                $maDon = (int)$pdo->lastInsertId();
            }

            $stmt = $pdo->prepare('SELECT SoLuongTon, TrangThai FROM Thuoc WHERE MaThuoc = ? FOR UPDATE');
            $stmt->execute([$mathuoc]);
            $thuocInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$thuocInfo || !$thuocInfo['TrangThai']) {
                throw new RuntimeException('Thuốc không khả dụng.');
            }
            if ((int)$thuocInfo['SoLuongTon'] < $soLuong) {
                throw new RuntimeException('Không đủ thuốc trong kho.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO ChiTietDonThuoc(MaDon, MaThuoc, SoLuong, LieuDung, CachDung)
                 VALUES(?, ?, ?, ?, ?)'
            );
            $stmt->execute([$maDon, $mathuoc, $soLuong, $lieuDung ?: null, $cachDung ?: null]);

            $stmt = $pdo->prepare('UPDATE Thuoc SET SoLuongTon = SoLuongTon - ? WHERE MaThuoc = ?');
            $stmt->execute([$soLuong, $mathuoc]);

            $pdo->commit();
            $ok = 'Đã thêm thuốc vào đơn.';
        } elseif ($action === 'remove_medicine') {
            $maCT = (int)($_POST['MaCT'] ?? 0);
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT ct.MaThuoc, ct.SoLuong
                 FROM ChiTietDonThuoc ct
                 JOIN DonThuoc dt ON ct.MaDon = dt.MaDon
                 JOIN HoSoKham hs ON dt.MaHS = hs.MaHS
                 WHERE ct.MaCT = ? AND hs.MaDatLich = ?
                 FOR UPDATE'
            );
            $stmt->execute([$maCT, $maDatLich]);
            $chiTiet = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($chiTiet) {
                $stmt = $pdo->prepare('DELETE FROM ChiTietDonThuoc WHERE MaCT = ?');
                $stmt->execute([$maCT]);
                $stmt = $pdo->prepare('UPDATE Thuoc SET SoLuongTon = SoLuongTon + ? WHERE MaThuoc = ?');
                $stmt->execute([$chiTiet['SoLuong'], $chiTiet['MaThuoc']]);
            }

            $pdo->commit();
            $ok = 'Đã xóa thuốc khỏi đơn.';
        } elseif ($action === 'complete_exam') {
            guiKetQuaKham($pdo, $maDatLich, $bs['MaBS']);
            header('Location: medical-record.php?appointment=' . $maDatLich . '&sent=1');
            exit;
        } elseif ($action === 'resend_result') {
            guiKetQuaKham($pdo, $maDatLich, $bs['MaBS'], true);
            header('Location: medical-record.php?appointment=' . $maDatLich . '&sent=1&resent=1');
            exit;
        } else {
            throw new RuntimeException('Thao tác không hợp lệ.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

$stmt = $pdo->prepare(
    'SELECT dl.*, bn.HoTen AS TenBN, bn.GioiTinh, bn.NgaySinh, bn.SoDienThoai, bn.Email, bn.DiaChi,
            lk.NgayKham, lk.GioBatDau, lk.GioKetThuc
     FROM DatLich dl
     JOIN BenhNhan bn ON dl.MaBN = bn.MaBN
     JOIN LichKham lk ON dl.MaLich = lk.MaLich
     WHERE dl.MaDatLich = ? AND lk.MaBS = ?'
);
$stmt->execute([$maDatLich, $bs['MaBS']]);
$a = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT * FROM HoSoKham WHERE MaDatLich = ?');
$stmt->execute([$maDatLich]);
$hs = $stmt->fetch(PDO::FETCH_ASSOC);

$don = null;
$details = [];
if ($hs) {
    $stmt = $pdo->prepare('SELECT * FROM DonThuoc WHERE MaHS = ?');
    $stmt->execute([$hs['MaHS']]);
    $don = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($don) {
        $stmt = $pdo->prepare(
            'SELECT ct.*, t.TenThuoc, t.DonVi
             FROM ChiTietDonThuoc ct
             JOIN Thuoc t ON ct.MaThuoc = t.MaThuoc
             WHERE ct.MaDon = ?
             ORDER BY ct.MaCT'
        );
        $stmt->execute([$don['MaDon']]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$thuoc = $pdo->query(
    'SELECT MaThuoc, TenThuoc, DonVi, SoLuongTon
     FROM Thuoc
     WHERE TrangThai = 1 AND SoLuongTon > 0
     ORDER BY TenThuoc'
)->fetchAll(PDO::FETCH_ASSOC);

$emailStatus = $a['TrangThaiGuiKetQua'] ?? 'ChuaGui';
$isLocked = $a['TrangThai'] === 'DaKham' && in_array($emailStatus, ['DangGui', 'DaGui'], true);
$lastAttempt = !empty($a['ThoiGianThuGuiKetQua']) ? strtotime($a['ThoiGianThuGuiKetQua']) : false;
$staleSending = $emailStatus === 'DangGui' && $lastAttempt !== false && time() - $lastAttempt >= 600;
$canRetry = $a['TrangThai'] === 'DaKham' && in_array($emailStatus, ['ChuaGui', 'ThatBai'], true) || ($a['TrangThai'] === 'DaKham' && $staleSending);
$canComplete = in_array($a['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true) && $hs && $don;
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hồ sơ khám</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="clinic-dashboard-page">
<?php clinicPortalShellStart('BacSi', 'Hồ sơ khám bệnh', 'appointments'); ?>
<div class="container py-4">
<?php if (isset($_GET['sent'])): ?>
        <div class="alert alert-success">
            <?= isset($_GET['resent']) ? 'Đã gửi lại' : 'Đã gửi' ?> email kết quả khám kèm file PDF cho bệnh nhân thành công.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
        <div class="alert alert-success"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="card card-body my-3">
        <strong><?= htmlspecialchars($a['TenBN'], ENT_QUOTES, 'UTF-8') ?></strong>
        <span>SĐT: <?= htmlspecialchars($a['SoDienThoai'] ?? '', ENT_QUOTES, 'UTF-8') ?> | Email: <?= htmlspecialchars($a['Email'] ?? 'Chưa có', ENT_QUOTES, 'UTF-8') ?></span>
        <span>Ngày khám: <?= htmlspecialchars($a['NgayKham'], ENT_QUOTES, 'UTF-8') ?> <?= substr($a['GioBatDau'], 0, 5) ?> - <?= substr($a['GioKetThuc'], 0, 5) ?></span>
        <span>Lý do: <?= htmlspecialchars($a['LyDoKham'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <span>Trạng thái lịch khám: <strong><?= htmlspecialchars($a['TrangThai'], ENT_QUOTES, 'UTF-8') ?></strong></span>
    </div>

    <?php if ($emailStatus === 'DaGui'): ?>
        <div class="alert alert-success">
            Đã gửi email kết quả khám đến <?= htmlspecialchars($a['Email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($a['ThoiGianGuiKetQua'])): ?>
                lúc <?= date('H:i d/m/Y', strtotime($a['ThoiGianGuiKetQua'])) ?>.
            <?php endif; ?>
        </div>
    <?php elseif ($emailStatus === 'ThatBai'): ?>
        <div class="alert alert-warning">
            Lượt khám đã hoàn tất nhưng email kết quả chưa gửi thành công.
            <?= htmlspecialchars($a['LoiGuiKetQua'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php elseif ($emailStatus === 'DangGui' && !$staleSending): ?>
        <div class="alert alert-info">Hệ thống đang xử lý gửi email kết quả. Vui lòng không nhấn gửi lại liên tục.</div>
    <?php elseif ($staleSending): ?>
        <div class="alert alert-warning">Lần gửi trước có thể đã bị gián đoạn. Bác sĩ có thể thử gửi lại kết quả.</div>
    <?php endif; ?>

    <form method="post" class="card card-body mb-4">
        <input type="hidden" name="action" value="save_record">
        <input type="hidden" name="MaDatLich" value="<?= $maDatLich ?>">
        <h5>Kết quả khám</h5>
        <div class="row g-2">
            <div class="col-md-6">
                <textarea name="ChanDoan" class="form-control" rows="3" placeholder="Chẩn đoán" <?= $isLocked ? 'disabled' : '' ?>><?= htmlspecialchars($hs['ChanDoan'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="col-md-6">
                <textarea name="KetLuan" class="form-control" rows="3" placeholder="Kết luận" <?= $isLocked ? 'disabled' : '' ?>><?= htmlspecialchars($hs['KetLuan'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="col-md-6">
                <textarea name="HuongDieuTri" class="form-control" rows="3" placeholder="Hướng điều trị" <?= $isLocked ? 'disabled' : '' ?>><?= htmlspecialchars($hs['HuongDieuTri'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="col-md-6">
                <textarea name="GhiChu" class="form-control" rows="3" placeholder="Ghi chú" <?= $isLocked ? 'disabled' : '' ?>><?= htmlspecialchars($hs['GhiChu'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" <?= $isLocked ? 'disabled' : '' ?>>Lưu hồ sơ khám</button>
            </div>
        </div>
    </form>

    <?php if ($hs): ?>
        <form method="post" class="card card-body mb-3">
            <input type="hidden" name="action" value="save_prescription">
            <input type="hidden" name="MaDatLich" value="<?= $maDatLich ?>">
            <h5>Đơn thuốc</h5>
            <div class="d-flex gap-2">
                <input name="LoiDan" class="form-control" placeholder="Lời dặn của bác sĩ" value="<?= htmlspecialchars($don['LoiDan'] ?? '', ENT_QUOTES, 'UTF-8') ?>" <?= $isLocked ? 'disabled' : '' ?>>
                <button class="btn btn-secondary text-nowrap" <?= $isLocked ? 'disabled' : '' ?>>Lưu đơn thuốc</button>
            </div>
            <small class="text-muted mt-2">Nếu bệnh nhân không cần dùng thuốc, hãy lưu đơn thuốc trống để xác nhận không kê thuốc.</small>
        </form>

        <form method="post" class="card card-body mb-3">
            <input type="hidden" name="action" value="add_medicine">
            <input type="hidden" name="MaDatLich" value="<?= $maDatLich ?>">
            <div class="row g-2">
                <div class="col-md-4">
                    <select name="MaThuoc" class="form-select" required <?= $isLocked ? 'disabled' : '' ?>>
                        <option value="">Chọn thuốc</option>
                        <?php foreach ($thuoc as $t): ?>
                            <option value="<?= (int)$t['MaThuoc'] ?>"><?= htmlspecialchars($t['TenThuoc'], ENT_QUOTES, 'UTF-8') ?> (còn <?= (int)$t['SoLuongTon'] ?> <?= htmlspecialchars($t['DonVi'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="number" min="1" name="SoLuong" class="form-control" placeholder="Số lượng" required <?= $isLocked ? 'disabled' : '' ?>></div>
                <div class="col-md-2"><input name="LieuDung" class="form-control" placeholder="Liều dùng" <?= $isLocked ? 'disabled' : '' ?>></div>
                <div class="col-md-3"><input name="CachDung" class="form-control" placeholder="Cách dùng" <?= $isLocked ? 'disabled' : '' ?>></div>
                <div class="col-md-1"><button class="btn btn-success w-100" <?= $isLocked ? 'disabled' : '' ?>>Thêm</button></div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead><tr><th>Thuốc</th><th>Số lượng</th><th>Liều dùng</th><th>Cách dùng</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($details as $detail): ?>
                    <tr>
                        <td><?= htmlspecialchars($detail['TenThuoc'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$detail['SoLuong'] ?> <?= htmlspecialchars($detail['DonVi'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($detail['LieuDung'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($detail['CachDung'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="action" value="remove_medicine">
                                <input type="hidden" name="MaDatLich" value="<?= $maDatLich ?>">
                                <input type="hidden" name="MaCT" value="<?= (int)$detail['MaCT'] ?>">
                                <button class="btn btn-sm btn-outline-danger" <?= $isLocked ? 'disabled' : '' ?>>Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$details): ?>
                    <tr><td colspan="5" class="text-center text-muted">Chưa có thuốc trong đơn.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (in_array($a['TrangThai'], ['ChoXacNhan', 'DaXacNhan'], true)): ?>
        <div class="card card-body border-success mt-4">
            <h5>Hoàn tất lượt khám</h5>
            <p class="mb-2">Sau khi hoàn tất, hệ thống sẽ chuyển lịch sang trạng thái đã khám, tạo một file PDF tổng hợp hồ sơ khám và đơn thuốc, sau đó tự động gửi đến email bệnh nhân.</p>
            <?php if (!$hs): ?>
                <div class="text-muted">Vui lòng lưu hồ sơ khám trước.</div>
            <?php elseif (!$don): ?>
                <div class="text-muted">Vui lòng lưu đơn thuốc hoặc xác nhận không kê thuốc trước.</div>
            <?php else: ?>
                <form method="post" onsubmit="return confirm('Hoàn tất lượt khám và tự động gửi hồ sơ PDF đến email bệnh nhân?');">
                    <input type="hidden" name="action" value="complete_exam">
                    <input type="hidden" name="MaDatLich" value="<?= $maDatLich ?>">
                    <button class="btn btn-success">Hoàn tất khám và gửi email PDF</button>
                </form>
            <?php endif; ?>
        </div>
    <?php elseif ($canRetry): ?>
        <form method="post" class="mt-3" onsubmit="return confirm('Gửi lại email kết quả khám kèm file PDF cho bệnh nhân?');">
            <input type="hidden" name="action" value="resend_result">
            <input type="hidden" name="MaDatLich" value="<?= $maDatLich ?>">
            <button class="btn btn-warning">Gửi lại email kết quả</button>
        </form>
    <?php endif; ?>
</div>
<?php clinicPortalShellEnd(); ?>
</body>
</html>
