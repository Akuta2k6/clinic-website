<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->prepare('SELECT MaBS, HoTen FROM BacSi WHERE MaTK=?');
$stmt->execute([$_SESSION['user']['MaTK']]);
$bs = $stmt->fetch();

if (!$bs) {
    exit('Không tìm thấy hồ sơ bác sĩ.');
}

$error = '';
$successMessage = isset($_GET['created']) ? 'Đã tạo lịch làm việc thành công.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $ngay = $_POST['NgayKham'] ?? '';
            $bd = $_POST['GioBatDau'] ?? '';
            $kt = $_POST['GioKetThuc'] ?? '';
            $mapk = (int)($_POST['MaPK'] ?? 0);
            $max = max(1, (int)($_POST['SoLuongToiDa'] ?? 1));
            $ghichu = trim($_POST['GhiChu'] ?? '');

            if (!$ngay || !$bd || !$kt || !$mapk) {
                throw new Exception('Vui lòng nhập đủ ngày, giờ và phòng khám.');
            }

            if ($kt <= $bd) {
                throw new Exception('Giờ kết thúc phải sau giờ bắt đầu.');
            }

            if ($ngay < date('Y-m-d')) {
                throw new Exception('Không thể tạo lịch trong quá khứ.');
            }

            $stmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM LichKham
                 WHERE MaBS = ?
                   AND NgayKham = ?
                   AND TrangThai <> 'Huy'
                   AND NOT (GioKetThuc <= ? OR GioBatDau >= ?)"
            );
            $stmt->execute([$bs['MaBS'], $ngay, $bd, $kt]);

            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception('Khung giờ này bị trùng với lịch khác của bạn.');
            }

            $stmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM LichKham
                 WHERE MaPK = ?
                   AND NgayKham = ?
                   AND TrangThai <> 'Huy'
                   AND NOT (GioKetThuc <= ? OR GioBatDau >= ?)"
            );
            $stmt->execute([$mapk, $ngay, $bd, $kt]);

            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception('Phòng khám đã có lịch trong khung giờ này. Vui lòng chọn phòng hoặc thời gian khác.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO LichKham
                    (MaBS, MaPK, NgayKham, GioBatDau, GioKetThuc, SoLuongToiDa, GhiChu)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $bs['MaBS'],
                $mapk,
                $ngay,
                $bd,
                $kt,
                $max,
                $ghichu ?: null
            ]);

            header('Location: schedules.php?created=1');
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['MaLich'] ?? 0);

            if (!$id) {
                throw new Exception('Mã lịch làm việc không hợp lệ.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT MaLich
                 FROM LichKham
                 WHERE MaLich = ?
                   AND MaBS = ?
                 FOR UPDATE'
            );
            $stmt->execute([$id, $bs['MaBS']]);

            if (!$stmt->fetchColumn()) {
                throw new Exception('Không tìm thấy lịch làm việc hoặc bạn không có quyền xóa lịch này.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM DatLich WHERE MaLich = ?');
            $stmt->execute([$id]);

            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception('Không thể xóa lịch đã có lượt đặt. Hãy xử lý các lượt đặt trong mục Quản lý lịch khám.');
            }

            $stmt = $pdo->prepare(
                'DELETE FROM LichKham
                 WHERE MaLich = ?
                   AND MaBS = ?'
            );
            $stmt->execute([$id, $bs['MaBS']]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('Không thể xóa lịch làm việc.');
            }

            $pdo->commit();

            header('Location: schedules.php');
            exit;
        }

        throw new Exception('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();
    }
}

$rooms = $pdo->query('SELECT * FROM PhongKham WHERE TrangThai=1 ORDER BY TenPhong')->fetchAll();

$stmt = $pdo->prepare(
    "SELECT
        lk.*,
        pk.TenPhong,
        pk.ViTri,
        (SELECT COUNT(*) FROM DatLich dl WHERE dl.MaLich = lk.MaLich) AS TongLuotDat
    FROM LichKham lk
    LEFT JOIN PhongKham pk ON lk.MaPK = pk.MaPK
    WHERE lk.MaBS = ?
    ORDER BY lk.NgayKham DESC, lk.GioBatDau DESC"
);
$stmt->execute([$bs['MaBS']]);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Quản lý lịch làm việc</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2>Quản lý lịch làm việc</h2>
                <div>BS. <?= htmlspecialchars($bs['HoTen']) ?></div>
                <small class="text-muted">Tạo lịch mới hoặc xóa lịch chưa có bệnh nhân đặt.</small>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="card card-body mb-4">
            <input type="hidden" name="action" value="save">
            <h5 class="mb-3">Tạo lịch làm việc mới</h5>
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Ngày khám</label>
                    <input type="date" min="<?= date('Y-m-d') ?>" name="NgayKham" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Giờ bắt đầu</label>
                    <input type="time" name="GioBatDau" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Giờ kết thúc</label>
                    <input type="time" name="GioKetThuc" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Phòng khám</label>
                    <select name="MaPK" class="form-select" required>
                        <option value="">Chọn phòng</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= (int)$room['MaPK'] ?>"><?= htmlspecialchars($room['TenPhong']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Số lượng tối đa</label>
                    <input type="number" min="1" name="SoLuongToiDa" class="form-control" value="1" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Tạo lịch</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Ghi chú</label>
                    <input name="GhiChu" class="form-control" placeholder="Ghi chú (không bắt buộc)">
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Giờ</th>
                        <th>Phòng</th>
                        <th>Đã đặt/Tối đa</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['NgayKham']) ?></td>
                            <td><?= substr($row['GioBatDau'], 0, 5) ?> - <?= substr($row['GioKetThuc'], 0, 5) ?></td>
                            <td><?= htmlspecialchars(($row['TenPhong'] ?? '') . ' ' . ($row['ViTri'] ?? '')) ?></td>
                            <td><?= (int)$row['SoLuongDaDat'] ?> / <?= (int)$row['SoLuongToiDa'] ?></td>
                            <td>
                                <?php if ($row['TrangThai'] === 'Huy'): ?>
                                    <span class="badge bg-danger">Đã hủy</span>
                                <?php elseif ($row['TrangThai'] === 'Day'): ?>
                                    <span class="badge bg-warning text-dark">Đã đầy</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Đang mở</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['GhiChu'] ?? '') ?></td>
                            <td>
                                <?php if ((int)$row['TongLuotDat'] === 0): ?>
                                    <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa lịch làm việc chưa có bệnh nhân đặt này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="MaLich" value="<?= (int)$row['MaLich'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Xóa lịch</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Đã có lượt đặt. Xử lý tại Quản lý lịch khám.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">Chưa có lịch làm việc.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
