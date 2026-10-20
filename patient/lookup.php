<?php
require_once __DIR__ . '/../config/database.php';

$result = null;
$error = '';

$maTraCuu = trim($_POST['MaTraCuu'] ?? $_GET['code'] ?? '');
$sdt = trim($_POST['SoDienThoai'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($maTraCuu === '' || $sdt === '') {
        $error = 'Vui lòng nhập đầy đủ mã tra cứu và số điện thoại.';
    } else {
        $stmt = $pdo->prepare("
            SELECT dl.MaTraCuu, dl.NgayDat, dl.LyDoKham, dl.TrangThai,
                   bn.HoTen, bn.SoDienThoai, bn.Email,
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
            WHERE dl.MaTraCuu=? AND bn.SoDienThoai=?
            LIMIT 1
        ");
        $stmt->execute([$maTraCuu, $sdt]);
        $result = $stmt->fetch();

        if (!$result) {
            $error = 'Không tìm thấy lịch khám phù hợp.';
        }
    }
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
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="card card-body shadow-sm mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Mã tra cứu</label>
                <input name="MaTraCuu" class="form-control"
                       value="<?= htmlspecialchars($maTraCuu) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Số điện thoại</label>
                <input name="SoDienThoai" class="form-control" required>
            </div>

            <div class="col-12">
                <button class="btn btn-primary">Tra cứu</button>
            </div>
        </div>
    </form>

    <?php if ($result): ?>
        <div class="card shadow-sm">
            <div class="card-body">

                <h4 class="mb-3">Thông tin lịch khám</h4>

                <p><strong>Mã tra cứu:</strong> <?= htmlspecialchars($result['MaTraCuu']) ?></p>
                <p><strong>Bệnh nhân:</strong> <?= htmlspecialchars($result['HoTen']) ?></p>
                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($result['SoDienThoai']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($result['Email'] ?? '') ?></p>

                <hr>

                <p><strong>Chuyên khoa:</strong> <?= htmlspecialchars($result['TenChuyenKhoa']) ?></p>
                <p><strong>Bác sĩ:</strong> <?= htmlspecialchars($result['TenBacSi']) ?></p>

                <p>
                    <strong>Ngày khám:</strong>
                    <?= date('d/m/Y', strtotime($result['NgayKham'])) ?>
                </p>

                <p>
                    <strong>Thời gian:</strong>
                    <?= substr($result['GioBatDau'], 0, 5) ?>
                    -
                    <?= substr($result['GioKetThuc'], 0, 5) ?>
                </p>

                <p>
                    <strong>Phòng:</strong>
                    <?= htmlspecialchars($result['TenPhong'] ?? 'Chưa xác định') ?>
                    <?= $result['ViTri'] ? ' - ' . htmlspecialchars($result['ViTri']) : '' ?>
                </p>

                <p><strong>Lý do khám:</strong> <?= htmlspecialchars($result['LyDoKham'] ?? '') ?></p>

                <p>
                    <strong>Trạng thái:</strong>
                    <?= htmlspecialchars(hienTrangThai($result['TrangThai'])) ?>
                </p>

            </div>
        </div>
    <?php endif; ?>

</div>
</body>
</html>