<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('Admin');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/portal-layout.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['MaPK'] ?? 0);
    try {
        if ($action === 'save') {
            $ten = trim($_POST['TenPhong'] ?? '');
            $vitri = trim($_POST['ViTri'] ?? '');
            if ($ten === '') throw new Exception('Tên phòng không được để trống.');
            if ($id) {
                $pdo->prepare('UPDATE PhongKham SET TenPhong=?,ViTri=? WHERE MaPK=?')->execute([$ten, $vitri ?: null, $id]);
            } else {
                $pdo->prepare('INSERT INTO PhongKham(TenPhong,ViTri) VALUES(?,?)')->execute([$ten, $vitri ?: null]);
            }
        } elseif ($action === 'toggle_status' && $id) {
            $pdo->prepare('UPDATE PhongKham SET TrangThai = 1 - TrangThai WHERE MaPK=?')->execute([$id]);
        } elseif ($action === 'delete' && $id) {
            $s = $pdo->prepare('SELECT COUNT(*) FROM LichKham WHERE MaPK=?');
            $s->execute([$id]);

            if ($s->fetchColumn() > 0) {
                throw new Exception('Không thể xóa phòng khám vì phòng này đã được sử dụng trong lịch khám.');
            }

            $pdo->prepare('DELETE FROM PhongKham WHERE MaPK=?')->execute([$id]);
        }
        header('Location: rooms.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM PhongKham WHERE MaPK=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
$rows = $pdo->query('SELECT pk.*,COUNT(lk.MaLich) SoLich FROM PhongKham pk LEFT JOIN LichKham lk ON lk.MaPK=pk.MaPK GROUP BY pk.MaPK ORDER BY pk.MaPK DESC')->fetchAll();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Phòng khám</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/apple-touch-icon.png">
</head>
<body class="clinic-dashboard-page">
<?php clinicPortalShellStart('Admin', 'Quản lý phòng khám', 'rooms'); ?>
<div class="container py-4">
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post" class="card card-body mb-4">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="MaPK" value="<?= (int)($edit['MaPK'] ?? 0) ?>">
    <div class="row g-2">
        <div class="col-md-5">
            <input class="form-control" name="TenPhong" required placeholder="Tên phòng" value="<?= htmlspecialchars($edit['TenPhong'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-5">
            <input class="form-control" name="ViTri" placeholder="Vị trí (VD: Tầng 2)" value="<?= htmlspecialchars($edit['ViTri'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100"><?= $edit ? 'Cập nhật' : 'Thêm' ?></button>
        </div>
    </div>
</form>
<div class="table-responsive"><table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>Mã</th>
            <th>Tên phòng</th>
            <th>Vị trí</th>
            <th>Số lịch</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= (int)$r['MaPK'] ?></td>
                <td><?= htmlspecialchars($r['TenPhong'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($r['ViTri'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)$r['SoLich'] ?></td>
                <td><?= $r['TrangThai'] ? 'Hoạt động' : 'Đã khóa' ?></td>
                <td>
                    <a class="btn btn-sm btn-warning" href="?edit=<?= (int)$r['MaPK'] ?>">Sửa</a>
                    <form class="d-inline" method="post">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="MaPK" value="<?= (int)$r['MaPK'] ?>">
                        <button class="btn btn-sm btn-outline-secondary"><?= $r['TrangThai'] ? 'Khóa' : 'Mở khóa' ?></button>
                    </form>
                    <form class="d-inline" method="post" onsubmit="return confirm('Bạn có chắc muốn xóa phòng khám này?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="MaPK" value="<?= (int)$r['MaPK'] ?>">
                        <button class="btn btn-sm btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table></div>
</div>
<?php clinicPortalShellEnd(); ?>
</body>
</html>