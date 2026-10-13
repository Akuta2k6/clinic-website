<?php

require_once __DIR__ . '/../auth/check.php';
requireRole('BacSi');

require_once __DIR__ . '/../config/database.php';


$stmt = $pdo->prepare(
    'SELECT MaBS, HoTen
     FROM BacSi
     WHERE MaTK=?'
);

$stmt->execute([
    $_SESSION['user']['MaTK']
]);

$bs = $stmt->fetch();

if (!$bs) {
    exit('Không tìm thấy bác sĩ.');
}


$stmt = $pdo->prepare(
    "SELECT
        dl.MaDatLich,
        bn.HoTen,
        bn.SoDienThoai,
        bn.Email,

        lk.NgayKham,
        lk.GioBatDau,
        lk.GioKetThuc,

        dl.LyDoKham,
        dl.TrangThai,

        hs.MaHS

    FROM DatLich dl

    JOIN BenhNhan bn
        ON dl.MaBN = bn.MaBN

    JOIN LichKham lk
        ON dl.MaLich = lk.MaLich

    LEFT JOIN HoSoKham hs
        ON hs.MaDatLich = dl.MaDatLich

    WHERE lk.MaBS = ?

      AND dl.TrangThai IN (
          'DaKham',
          'DaHuy',
          'VangMat'
      )

    ORDER BY
        lk.NgayKham DESC,
        lk.GioBatDau DESC
");

$stmt->execute([
    $bs['MaBS']
]);

$rows = $stmt->fetchAll();

?>

<!doctype html>

<html lang="vi">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Lịch sử khám</title>

    <link
        href="../assets/vendor/bootstrap/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body>

<div class="container py-4">

    <div class="d-flex justify-content-between mb-3">

        <div>

            <h2>Lịch sử khám</h2>

            <div>
                BS. <?= htmlspecialchars($bs['HoTen']) ?>
            </div>

        </div>

        <a href="dashboard.php"
           class="btn btn-outline-secondary h-50">

            Dashboard

        </a>

    </div>


    <table class="table table-bordered table-striped align-middle">

        <thead>

        <tr>

            <th>Bệnh nhân</th>

            <th>Liên hệ</th>

            <th>Ngày khám</th>

            <th>Lý do</th>

            <th>Trạng thái</th>

            <th>Hồ sơ</th>

        </tr>

        </thead>

        <tbody>

        <?php foreach ($rows as $r): ?>

            <tr>

                <td>

                    <?= htmlspecialchars($r['HoTen']) ?>

                </td>

                <td>

                    <?= htmlspecialchars($r['SoDienThoai']) ?>

                    <br>

                    <?= htmlspecialchars($r['Email'] ?? '') ?>

                </td>

                <td>

                    <?= $r['NgayKham'] ?>

                    <br>

                    <?= substr($r['GioBatDau'], 0, 5) ?>

                    -

                    <?= substr($r['GioKetThuc'], 0, 5) ?>

                </td>

                <td>

                    <?= nl2br(
                        htmlspecialchars(
                            $r['LyDoKham'] ?? ''
                        )
                    ) ?>

                </td>

                <td>

                    <?php

                    if ($r['TrangThai'] === 'DaKham') {

                        echo '<span class="badge bg-success">Đã khám</span>';

                    } elseif ($r['TrangThai'] === 'DaHuy') {

                        echo '<span class="badge bg-danger">Đã hủy</span>';

                    } else {

                        echo '<span class="badge bg-secondary">Vắng mặt</span>';
                    }

                    ?>

                </td>

                <td>

                    <?php if (
                        $r['TrangThai'] === 'DaKham'
                        && $r['MaHS']
                    ): ?>

                        <a
                            class="btn btn-sm btn-primary"
                            href="medical-record.php?appointment=<?= $r['MaDatLich'] ?>">

                            Xem hồ sơ

                        </a>

                    <?php else: ?>

                        <span class="text-muted">
                            Không có
                        </span>

                    <?php endif; ?>

                </td>

            </tr>

        <?php endforeach; ?>

        <?php if (!$rows): ?>

            <tr>

                <td colspan="6"
                    class="text-center text-muted">

                    Chưa có lịch sử khám.

                </td>

            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</div>

</body>

</html>