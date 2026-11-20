<?php

function taoPdfKetQuaKham(PDO $pdo, int $maDatLich, string $maBS): string
{
    $autoload = __DIR__ . '/../vendor/autoload.php';

    if (!is_file($autoload)) {
        throw new RuntimeException('Chưa cài thư viện tạo PDF.');
    }

    require_once $autoload;

    $stmt = $pdo->prepare(
        "SELECT
            dl.MaDatLich,
            dl.MaTraCuu,
            dl.LyDoKham,
            bn.HoTen AS TenBenhNhan,
            bn.GioiTinh,
            bn.NgaySinh,
            bn.SoDienThoai,
            bn.Email,
            bn.DiaChi,
            lk.NgayKham,
            lk.GioBatDau,
            lk.GioKetThuc,
            bs.HoTen AS TenBacSi,
            bs.HocVi,
            ck.TenChuyenKhoa,
            pk.TenPhong,
            hs.ThoiGianKham,
            hs.ChanDoan,
            hs.KetLuan,
            hs.HuongDieuTri,
            hs.GhiChu,
            dt.NgayKeDon,
            dt.LoiDan
        FROM DatLich dl
        JOIN BenhNhan bn ON dl.MaBN = bn.MaBN
        JOIN LichKham lk ON dl.MaLich = lk.MaLich
        JOIN BacSi bs ON lk.MaBS = bs.MaBS
        JOIN ChuyenKhoa ck ON bs.MaCK = ck.MaCK
        LEFT JOIN PhongKham pk ON lk.MaPK = pk.MaPK
        JOIN HoSoKham hs ON hs.MaDatLich = dl.MaDatLich
        LEFT JOIN DonThuoc dt ON dt.MaHS = hs.MaHS
        WHERE dl.MaDatLich = ? AND lk.MaBS = ?
        LIMIT 1"
    );
    $stmt->execute([$maDatLich, $maBS]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new RuntimeException('Không tìm thấy dữ liệu hồ sơ khám để tạo PDF.');
    }

    $stmt = $pdo->prepare(
        "SELECT
            t.TenThuoc,
            t.DonVi,
            ct.SoLuong,
            ct.LieuDung,
            ct.CachDung
        FROM DonThuoc dt
        JOIN ChiTietDonThuoc ct ON ct.MaDon = dt.MaDon
        JOIN Thuoc t ON t.MaThuoc = ct.MaThuoc
        WHERE dt.MaHS = (
            SELECT MaHS FROM HoSoKham WHERE MaDatLich = ? LIMIT 1
        )
        ORDER BY ct.MaCT"
    );
    $stmt->execute([$maDatLich]);
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $e = static fn($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $multiline = static fn($value): string => trim((string)($value ?? '')) !== '' ? nl2br($e($value)) : '<span class="muted">Không có thông tin</span>';
    $date = static fn($value): string => !empty($value) ? date('d/m/Y', strtotime($value)) : '—';
    $time = substr((string)$record['GioBatDau'], 0, 5) . ' - ' . substr((string)$record['GioKetThuc'], 0, 5);
    $doctorTitle = trim((string)($record['HocVi'] ?? ''));
    $doctorName = trim($doctorTitle . ' ' . (string)$record['TenBacSi']);

    $medicineRows = '';
    foreach ($medicines as $index => $medicine) {
        $medicineRows .= '<tr>
            <td class="center">' . ($index + 1) . '</td>
            <td><strong>' . $e($medicine['TenThuoc']) . '</strong></td>
            <td class="center">' . $e($medicine['SoLuong']) . ' ' . $e($medicine['DonVi']) . '</td>
            <td>' . $multiline($medicine['LieuDung']) . '</td>
            <td>' . $multiline($medicine['CachDung']) . '</td>
        </tr>';
    }

    if ($medicineRows === '') {
        $medicineRows = '<tr><td colspan="5" class="center muted">Không kê thuốc trong lần khám này.</td></tr>';
    }

    $html = '<!doctype html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: dejavusans, sans-serif; color: #243247; font-size: 10pt; }
            h1 { color: #145b83; font-size: 19pt; text-align: center; margin: 0 0 4px; }
            h2 { color: #145b83; font-size: 12pt; border-bottom: 1px solid #b8cbd8; padding-bottom: 5px; margin: 17px 0 8px; }
            .subtitle { text-align: center; color: #5d6b78; font-size: 9pt; margin-bottom: 18px; }
            .meta { width: 100%; border-collapse: collapse; }
            .meta td { padding: 5px 7px; border: 1px solid #d8e0e7; vertical-align: top; }
            .label { color: #5b6875; width: 23%; }
            .section { margin-top: 12px; }
            .content { border: 1px solid #d8e0e7; padding: 8px; min-height: 25px; }
            table.medicines { width: 100%; border-collapse: collapse; }
            table.medicines th { background: #eaf2f7; color: #21445c; font-weight: bold; }
            table.medicines th, table.medicines td { border: 1px solid #cbd6df; padding: 6px; vertical-align: top; }
            .center { text-align: center; }
            .muted { color: #7b8792; }
            .signature { width: 100%; margin-top: 28px; }
            .signature td { width: 50%; text-align: center; vertical-align: top; }
            .signature-space { height: 55px; }
            .footer { margin-top: 20px; color: #6d7882; font-size: 8pt; text-align: center; }
        </style>
    </head>
    <body>
        <h1>BỆNH VIỆN ĐA KHOA TDTU</h1>
        <div class="subtitle">HỒ SƠ KHÁM BỆNH VÀ ĐƠN THUỐC</div>
        <table class="meta">
            <tr><td class="label">Mã tra cứu</td><td><strong>' . $e($record['MaTraCuu']) . '</strong></td><td class="label">Ngày khám</td><td>' . $date($record['NgayKham']) . '</td></tr>
            <tr><td class="label">Bệnh nhân</td><td colspan="3"><strong>' . $e($record['TenBenhNhan']) . '</strong></td></tr>
            <tr><td class="label">Giới tính</td><td>' . $e($record['GioiTinh'] ?: '—') . '</td><td class="label">Ngày sinh</td><td>' . $date($record['NgaySinh']) . '</td></tr>
            <tr><td class="label">Số điện thoại</td><td>' . $e($record['SoDienThoai']) . '</td><td class="label">Email</td><td>' . $e($record['Email']) . '</td></tr>
            <tr><td class="label">Địa chỉ</td><td colspan="3">' . $e($record['DiaChi'] ?: '—') . '</td></tr>
            <tr><td class="label">Bác sĩ</td><td>' . $e($doctorName) . '</td><td class="label">Chuyên khoa</td><td>' . $e($record['TenChuyenKhoa']) . '</td></tr>
            <tr><td class="label">Thời gian</td><td>' . $e($time) . '</td><td class="label">Phòng khám</td><td>' . $e($record['TenPhong'] ?: '—') . '</td></tr>
        </table>
        <h2>1. THÔNG TIN KHÁM BỆNH</h2>
        <div class="section"><strong>Lý do khám</strong><div class="content">' . $multiline($record['LyDoKham']) . '</div></div>
        <div class="section"><strong>Chẩn đoán</strong><div class="content">' . $multiline($record['ChanDoan']) . '</div></div>
        <div class="section"><strong>Kết luận</strong><div class="content">' . $multiline($record['KetLuan']) . '</div></div>
        <div class="section"><strong>Hướng điều trị</strong><div class="content">' . $multiline($record['HuongDieuTri']) . '</div></div>
        <div class="section"><strong>Ghi chú</strong><div class="content">' . $multiline($record['GhiChu']) . '</div></div>
        <h2>2. ĐƠN THUỐC</h2>
        <table class="medicines">
            <thead><tr><th style="width:7%">STT</th><th style="width:27%">Tên thuốc</th><th style="width:15%">Số lượng</th><th style="width:23%">Liều dùng</th><th style="width:28%">Cách dùng</th></tr></thead>
            <tbody>' . $medicineRows . '</tbody>
        </table>
        <div class="section"><strong>Lời dặn của bác sĩ</strong><div class="content">' . $multiline($record['LoiDan']) . '</div></div>
        <table class="signature">
            <tr><td><strong>BỆNH NHÂN</strong><div class="signature-space"></div><span class="muted">(Ký và ghi rõ họ tên)</span></td><td><strong>BÁC SĨ KHÁM</strong><div class="signature-space"></div><strong>' . $e($doctorName) . '</strong></td></tr>
        </table>
        <div class="footer">Tài liệu được tạo tự động từ hệ thống quản lý bệnh viện.</div>
    </body>
    </html>';

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 14,
        'margin_right' => 14,
        'margin_top' => 14,
        'margin_bottom' => 14,
        'default_font' => 'dejavusans',
        'tempDir' => sys_get_temp_dir()
    ]);
    $mpdf->SetTitle('Ket qua kham - ' . $record['MaTraCuu']);
    $mpdf->WriteHTML($html);

    return $mpdf->Output('', 'S');
}
