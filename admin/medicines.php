<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('Admin');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/portal-layout.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['MaThuoc'] ?? 0);
    try {
        if ($action === 'save') {
            $ten = trim($_POST['TenThuoc'] ?? '');
            $dv = trim($_POST['DonVi'] ?? '');
            $sl = (int)($_POST['SoLuongTon'] ?? 0);
            $gia = (float)($_POST['DonGia'] ?? 0);
            $hsd = trim($_POST['HanSuDung'] ?? '');

            if ($ten === '') throw new Exception('Tên thuốc là bắt buộc.');
            if ($sl < 0 || $gia < 0) throw new Exception('Số lượng và đơn giá không được âm.');
            if ($hsd !== '') {
                $d = DateTime::createFromFormat('!Y-m-d', $hsd);
                if (!$d || $d->format('Y-m-d') !== $hsd) {
                    throw new Exception('Hạn sử dụng không hợp lệ.');
                }
            }

            if ($id) {
                $pdo->prepare('UPDATE Thuoc SET TenThuoc=?,DonVi=?,SoLuongTon=?,DonGia=?,HanSuDung=? WHERE MaThuoc=?')
                    ->execute([$ten, $dv ?: null, $sl, $gia, $hsd ?: null, $id]);
            } else {
                $pdo->prepare('INSERT INTO Thuoc(TenThuoc,DonVi,SoLuongTon,DonGia,HanSuDung) VALUES(?,?,?,?,?)')
                    ->execute([$ten, $dv ?: null, $sl, $gia, $hsd ?: null]);
            }
        } elseif ($action === 'toggle_status' && $id) {
            $pdo->prepare('UPDATE Thuoc SET TrangThai = 1 - TrangThai WHERE MaThuoc=?')->execute([$id]);
        } elseif ($action === 'delete' && $id) {
            $s = $pdo->prepare('SELECT COUNT(*) FROM ChiTietDonThuoc WHERE MaThuoc=?');
            $s->execute([$id]);

            if ($s->fetchColumn() > 0) {
                throw new Exception('Không thể xóa thuốc vì thuốc này đã xuất hiện trong đơn thuốc.');
            }

            $pdo->prepare('DELETE FROM Thuoc WHERE MaThuoc=?')->execute([$id]);
        }
        header('Location: medicines.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM Thuoc WHERE MaThuoc=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
$rows = $pdo->query('SELECT * FROM Thuoc ORDER BY MaThuoc DESC')->fetchAll();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Thuốc</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/apple-touch-icon.png">
</head>
<body class="clinic-dashboard-page">
<?php clinicPortalShellStart('Admin', 'Quản lý thuốc', 'medicines'); ?>
<div class="container py-4">
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post" class="card card-body mb-4">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="MaThuoc" value="<?= (int)($edit['MaThuoc'] ?? 0) ?>">
    <div class="row g-2">
        <div class="col-md-3">
            <input name="TenThuoc" class="form-control" placeholder="Tên thuốc" required value="<?= htmlspecialchars($edit['TenThuoc'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-2">
            <input name="DonVi" class="form-control" placeholder="Đơn vị" value="<?= htmlspecialchars($edit['DonVi'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-2">
            <input type="number" min="0" name="SoLuongTon" class="form-control" placeholder="Tồn kho" value="<?= (int)($edit['SoLuongTon'] ?? 0) ?>">
        </div>
        <div class="col-md-2">
            <input type="number" min="0" step="0.01" name="DonGia" class="form-control" placeholder="Đơn giá" value="<?= htmlspecialchars((string)($edit['DonGia'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="HanSuDung" class="form-control" value="<?= htmlspecialchars($edit['HanSuDung'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary w-100"><?= $edit ? 'Lưu' : 'Thêm' ?></button>
        </div>
    </div>
</form>
<div class="table-responsive"><table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>Mã</th>
            <th>Tên thuốc</th>
            <th>Đơn vị</th>
            <th>Tồn kho</th>
            <th>Đơn giá</th>
            <th>HSD</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= (int)$r['MaThuoc'] ?></td>
                <td><?= htmlspecialchars($r['TenThuoc'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($r['DonVi'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)$r['SoLuongTon'] ?></td>
                <td><?= number_format((float)$r['DonGia'], 0, ',', '.') ?></td>
                <td><?= htmlspecialchars($r['HanSuDung'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $r['TrangThai'] ? 'Đang dùng' : 'Đã khóa' ?></td>
                <td>
                    <a class="btn btn-sm btn-warning" href="?edit=<?= (int)$r['MaThuoc'] ?>">Sửa</a>
                    <form class="d-inline" method="post">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="MaThuoc" value="<?= (int)$r['MaThuoc'] ?>">
                        <button class="btn btn-sm btn-outline-secondary"><?= $r['TrangThai'] ? 'Khóa' : 'Mở khóa' ?></button>
                    </form>
                    <form class="d-inline" method="post" onsubmit="return confirm('Bạn có chắc muốn xóa thuốc này?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="MaThuoc" value="<?= (int)$r['MaThuoc'] ?>">
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