<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/portal-layout.php';

$stmt = $pdo->prepare('SELECT MaBS, HoTen FROM BacSi WHERE MaTK=?');
$stmt->execute([$_SESSION['user']['MaTK']]);
$bs = $stmt->fetch();

if (!$bs) {
    exit('Không tìm thấy hồ sơ bác sĩ.');
}

$error = '';
$successMessage = '';
if (isset($_GET['created'])) {
    $successMessage = 'Đã tạo lịch làm việc thành công.';
} elseif (isset($_GET['updated'])) {
    $successMessage = 'Đã cập nhật lịch làm việc thành công.';
} elseif (isset($_GET['deleted'])) {
    $successMessage = 'Đã xóa lịch làm việc thành công.';
}

$editSchedule = null;
$formData = [
    'NgayKham' => '',
    'GioBatDau' => '',
    'GioKetThuc' => '',
    'MaPK' => '',
    'SoLuongToiDa' => 1,
    'GhiChu' => ''
];

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];

    if ($editId > 0) {
        $stmt = $pdo->prepare(
            "SELECT lk.*,
                (SELECT COUNT(*) FROM DatLich dl WHERE dl.MaLich = lk.MaLich) AS TongLuotDat
             FROM LichKham lk
             WHERE lk.MaLich = ? AND lk.MaBS = ?"
        );
        $stmt->execute([$editId, $bs['MaBS']]);
        $editSchedule = $stmt->fetch();

        if (!$editSchedule) {
            $error = 'Không tìm thấy lịch làm việc hoặc bạn không có quyền chỉnh sửa lịch này.';
        } elseif ((int)$editSchedule['TongLuotDat'] > 0) {
            $error = 'Không thể sửa lịch đã có lượt đặt. Hãy xử lý các lượt đặt trong mục Quản lý lịch khám.';
            $editSchedule = null;
        } elseif ($editSchedule['TrangThai'] === 'Huy') {
            $error = 'Không thể sửa lịch làm việc đã bị hủy.';
            $editSchedule = null;
        } elseif ($editSchedule['NgayKham'] < date('Y-m-d')) {
            $error = 'Không thể chỉnh sửa lịch làm việc trong quá khứ.';
            $editSchedule = null;
        } else {
            $formData = [
                'NgayKham' => $editSchedule['NgayKham'],
                'GioBatDau' => substr($editSchedule['GioBatDau'], 0, 5),
                'GioKetThuc' => substr($editSchedule['GioKetThuc'], 0, 5),
                'MaPK' => $editSchedule['MaPK'],
                'SoLuongToiDa' => $editSchedule['SoLuongToiDa'],
                'GhiChu' => $editSchedule['GhiChu'] ?? ''
            ];
        }
    }
}

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

        if ($action === 'update') {
            $id = (int)($_POST['MaLich'] ?? 0);
            $ngay = $_POST['NgayKham'] ?? '';
            $bd = $_POST['GioBatDau'] ?? '';
            $kt = $_POST['GioKetThuc'] ?? '';
            $mapk = (int)($_POST['MaPK'] ?? 0);
            $max = max(1, (int)($_POST['SoLuongToiDa'] ?? 1));
            $ghichu = trim($_POST['GhiChu'] ?? '');

            if (!$id || !$ngay || !$bd || !$kt || !$mapk) {
                throw new Exception('Vui lòng nhập đủ ngày, giờ và phòng khám.');
            }

            if ($kt <= $bd) {
                throw new Exception('Giờ kết thúc phải sau giờ bắt đầu.');
            }

            if ($ngay < date('Y-m-d')) {
                throw new Exception('Không thể cập nhật lịch trong quá khứ.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT MaLich, TrangThai
                 FROM LichKham
                 WHERE MaLich = ? AND MaBS = ?
                 FOR UPDATE'
            );
            $stmt->execute([$id, $bs['MaBS']]);
            $currentSchedule = $stmt->fetch();

            if (!$currentSchedule) {
                throw new Exception('Không tìm thấy lịch làm việc hoặc bạn không có quyền chỉnh sửa lịch này.');
            }

            if ($currentSchedule['TrangThai'] === 'Huy') {
                throw new Exception('Không thể chỉnh sửa lịch làm việc đã bị hủy.');
            }

            $stmt = $pdo->prepare(
                'SELECT MaDatLich
                 FROM DatLich
                 WHERE MaLich = ?
                 FOR UPDATE'
            );
            $stmt->execute([$id]);

            if ($stmt->fetch()) {
                throw new Exception('Không thể sửa lịch đã có lượt đặt. Hãy xử lý các lượt đặt trong mục Quản lý lịch khám.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM PhongKham WHERE MaPK = ? AND TrangThai = 1');
            $stmt->execute([$mapk]);

            if ((int)$stmt->fetchColumn() === 0) {
                throw new Exception('Phòng khám không tồn tại hoặc đang bị khóa.');
            }

            $stmt = $pdo->prepare(
                "SELECT MaLich
                 FROM LichKham
                 WHERE MaBS = ?
                   AND NgayKham = ?
                   AND MaLich <> ?
                   AND TrangThai <> 'Huy'
                   AND NOT (GioKetThuc <= ? OR GioBatDau >= ?)
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([$bs['MaBS'], $ngay, $id, $bd, $kt]);

            if ($stmt->fetch()) {
                throw new Exception('Khung giờ này bị trùng với lịch làm việc khác của bạn.');
            }

            $stmt = $pdo->prepare(
                "SELECT MaLich
                 FROM LichKham
                 WHERE MaPK = ?
                   AND NgayKham = ?
                   AND MaLich <> ?
                   AND TrangThai <> 'Huy'
                   AND NOT (GioKetThuc <= ? OR GioBatDau >= ?)
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([$mapk, $ngay, $id, $bd, $kt]);

            if ($stmt->fetch()) {
                throw new Exception('Phòng khám đã có lịch trong khung giờ này. Vui lòng chọn phòng hoặc thời gian khác.');
            }

            $stmt = $pdo->prepare(
                "UPDATE LichKham
                 SET MaPK = ?,
                     NgayKham = ?,
                     GioBatDau = ?,
                     GioKetThuc = ?,
                     SoLuongToiDa = ?,
                     GhiChu = ?,
                     TrangThai = 'Mo'
                 WHERE MaLich = ? AND MaBS = ?"
            );
            $stmt->execute([
                $mapk,
                $ngay,
                $bd,
                $kt,
                $max,
                $ghichu ?: null,
                $id,
                $bs['MaBS']
            ]);

            $pdo->commit();

            header('Location: schedules.php?updated=1');
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

            header('Location: schedules.php?deleted=1');
            exit;
        }

        throw new Exception('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();
        if (in_array($action, ['save', 'update'], true)) {
            $formData = [
                'NgayKham' => $_POST['NgayKham'] ?? '',
                'GioBatDau' => $_POST['GioBatDau'] ?? '',
                'GioKetThuc' => $_POST['GioKetThuc'] ?? '',
                'MaPK' => $_POST['MaPK'] ?? '',
                'SoLuongToiDa' => $_POST['SoLuongToiDa'] ?? 1,
                'GhiChu' => $_POST['GhiChu'] ?? ''
            ];
            if ($action === 'update') {
                $editId = (int)($_POST['MaLich'] ?? 0);
                $stmt = $pdo->prepare(
                    "SELECT lk.*,
                        (SELECT COUNT(*) FROM DatLich dl WHERE dl.MaLich = lk.MaLich) AS TongLuotDat
                     FROM LichKham lk
                     WHERE lk.MaLich = ? AND lk.MaBS = ?"
                );
                $stmt->execute([$editId, $bs['MaBS']]);
                $editSchedule = $stmt->fetch();
            }
        }
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
    <style>
        .schedule-actions {
            width: 1%;
            white-space: nowrap;
        }
        .schedule-notes {
            min-width: 260px;
        }
        .schedule-actions .action-note {
            display: inline-block;
            max-width: 125px;
            white-space: normal;
        }
    </style>
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="clinic-dashboard-page">
<?php clinicPortalShellStart('BacSi', 'Quản lý lịch làm việc', 'schedules'); ?>
    <div class="container py-4">
<?php if ($successMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="card card-body mb-4">
            <input type="hidden" name="action" value="<?= $editSchedule ? 'update' : 'save' ?>">
            <?php if ($editSchedule): ?>
                <input type="hidden" name="MaLich" value="<?= (int)$editSchedule['MaLich'] ?>">
            <?php endif; ?>
            <h5 class="mb-3"><?= $editSchedule ? 'Chỉnh sửa lịch làm việc' : 'Tạo lịch làm việc mới' ?></h5>
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Ngày khám</label>
                    <input type="date" min="<?= date('Y-m-d') ?>" name="NgayKham" class="form-control" value="<?= htmlspecialchars((string)$formData['NgayKham']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Giờ bắt đầu</label>
                    <input type="time" name="GioBatDau" class="form-control" value="<?= htmlspecialchars((string)$formData['GioBatDau']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Giờ kết thúc</label>
                    <input type="time" name="GioKetThuc" class="form-control" value="<?= htmlspecialchars((string)$formData['GioKetThuc']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Phòng khám</label>
                    <select name="MaPK" class="form-select" required>
                        <option value="">Chọn phòng</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= (int)$room['MaPK'] ?>" <?= (string)$formData['MaPK'] === (string)$room['MaPK'] ? 'selected' : '' ?>><?= htmlspecialchars($room['TenPhong']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Số lượng tối đa</label>
                    <input type="number" min="1" name="SoLuongToiDa" class="form-control" value="<?= (int)$formData['SoLuongToiDa'] ?>" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editSchedule ? 'Cập nhật lịch' : 'Tạo lịch' ?></button>
                </div>
                <div class="col-12">
                    <label class="form-label">Ghi chú</label>
                    <input name="GhiChu" class="form-control" value="<?= htmlspecialchars((string)$formData['GhiChu']) ?>" placeholder="Ghi chú (không bắt buộc)">
                </div>
                <?php if ($editSchedule): ?>
                    <div class="col-12 d-flex justify-content-end">
                        <a href="schedules.php" class="btn btn-outline-secondary">Hủy chỉnh sửa</a>
                    </div>
                <?php endif; ?>
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
                        <th class="schedule-notes">Ghi chú</th>
                        <th class="schedule-actions">Thao tác</th>
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
                            <td class="schedule-notes"><?= htmlspecialchars($row['GhiChu'] ?? '') ?></td>
                            <td class="schedule-actions">
                                <?php if ((int)$row['TongLuotDat'] === 0): ?>
                                    <div class="d-flex flex-nowrap gap-1">
                                        <?php if ($row['TrangThai'] !== 'Huy' && $row['NgayKham'] >= date('Y-m-d')): ?>
                                            <a href="schedules.php?edit=<?= (int)$row['MaLich'] ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                                        <?php endif; ?>
                                        <form method="post" class="m-0" onsubmit="return confirm('Bạn có chắc muốn xóa lịch làm việc chưa có bệnh nhân đặt này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="MaLich" value="<?= (int)$row['MaLich'] ?>">
                                            <button class="btn btn-sm btn-outline-danger">Xóa lịch</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small action-note">Đã có lượt đặt. Xử lý tại Quản lý lịch khám.</span>
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
<?php clinicPortalShellEnd(); ?>
</body>
</html>
