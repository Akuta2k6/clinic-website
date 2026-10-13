<?php
require_once __DIR__.'/../auth/check.php';requireRole('BacSi');require_once __DIR__.'/../config/database.php';
$s=$pdo->prepare('SELECT MaBS,HoTen FROM BacSi WHERE MaTK=?');$s->execute([$_SESSION['user']['MaTK']]);$bs=$s->fetch();if(!$bs)exit('Không tìm thấy hồ sơ bác sĩ.');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=$_POST['action']??'';$id=(int)($_POST['MaLich']??0);
 try{
  if($action==='save'){
   $ngay=$_POST['NgayKham']??'';$bd=$_POST['GioBatDau']??'';$kt=$_POST['GioKetThuc']??'';$mapk=(int)($_POST['MaPK']??0);$max=max(1,(int)($_POST['SoLuongToiDa']??1));$ghichu=trim($_POST['GhiChu']??'');
   if(!$ngay||!$bd||!$kt||!$mapk)throw new Exception('Vui lòng nhập đủ ngày, giờ và phòng khám.');if($kt<=$bd)throw new Exception('Giờ kết thúc phải sau giờ bắt đầu.');if($ngay<date('Y-m-d'))throw new Exception('Không thể tạo lịch trong quá khứ.');
   $q='SELECT COUNT(*) FROM LichKham WHERE MaBS=? AND NgayKham=? AND TrangThai<>\'Huy\' AND NOT (GioKetThuc<=? OR GioBatDau>=?)'.($id?' AND MaLich<>?':'');$args=[$bs['MaBS'],$ngay,$bd,$kt];if($id)$args[]=$id;$st=$pdo->prepare($q);$st->execute($args);if($st->fetchColumn()>0)throw new Exception('Khung giờ này bị trùng với lịch khác của bạn.');
   if($id){$st=$pdo->prepare('SELECT SoLuongDaDat FROM LichKham WHERE MaLich=? AND MaBS=?');$st->execute([$id,$bs['MaBS']]);$booked=(int)$st->fetchColumn();if($max<$booked)throw new Exception('Số lượng tối đa không thể nhỏ hơn số đã đặt.');$pdo->prepare('UPDATE LichKham SET MaPK=?,NgayKham=?,GioBatDau=?,GioKetThuc=?,SoLuongToiDa=?,GhiChu=?,TrangThai=IF(SoLuongDaDat>=?,\'Day\',\'Mo\') WHERE MaLich=? AND MaBS=?')->execute([$mapk,$ngay,$bd,$kt,$max,$ghichu?:null,$max,$id,$bs['MaBS']]);}
   else{$pdo->prepare('INSERT INTO LichKham(MaBS,MaPK,NgayKham,GioBatDau,GioKetThuc,SoLuongToiDa,GhiChu) VALUES(?,?,?,?,?,?,?)')->execute([$bs['MaBS'],$mapk,$ngay,$bd,$kt,$max,$ghichu?:null]);}
  }
    elseif ($action === 'delete' && $id) {

        $s = $pdo->prepare(
            'SELECT COUNT(*)
            FROM DatLich dl
            JOIN LichKham lk ON dl.MaLich = lk.MaLich
            WHERE lk.MaLich=? AND lk.MaBS=?'
        );

        $s->execute([$id, $bs['MaBS']]);

        if ($s->fetchColumn() > 0) {
            throw new Exception(
                'Không thể xóa lịch này vì đã có bệnh nhân đặt lịch.'
            );
        }

        $pdo->prepare(
            'DELETE FROM LichKham
            WHERE MaLich=? AND MaBS=?'
        )->execute([$id, $bs['MaBS']]);
    }
  header('Location: schedules.php');
  exit;
 }catch(Throwable $e){$error=$e->getMessage();}
}
$edit=null;if(isset($_GET['edit'])){$st=$pdo->prepare('SELECT * FROM LichKham WHERE MaLich=? AND MaBS=?');$st->execute([(int)$_GET['edit'],$bs['MaBS']]);$edit=$st->fetch();}
$rooms=$pdo->query('SELECT * FROM PhongKham WHERE TrangThai=1 ORDER BY TenPhong')->fetchAll();$st=$pdo->prepare('SELECT lk.*,pk.TenPhong,pk.ViTri FROM LichKham lk LEFT JOIN PhongKham pk ON lk.MaPK=pk.MaPK WHERE lk.MaBS=? ORDER BY lk.NgayKham DESC,lk.GioBatDau DESC');$st->execute([$bs['MaBS']]);$rows=$st->fetchAll();
?>
<!doctype html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Lịch làm việc</title>
        <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container py-4">
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <h2>Lịch làm việc</h2>
                    <div>BS. <?=htmlspecialchars($bs['HoTen'])?>
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary h-50">Dashboard</a>
        </div>
        <?php if($error):?>
            <div class="alert alert-danger">
                <?=htmlspecialchars($error)?>
            </div>
        <?php endif;?>
        <form method="post" class="card card-body mb-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="MaLich" value="<?=$edit['MaLich']??''?>">
            <div class="row g-2"><div class="col-md-2">
                <input type="date" min="<?=date('Y-m-d')?>" name="NgayKham" class="form-control" required value="<?=$edit['NgayKham']??''?>">
            </div>
            <div class="col-md-2">
                <input type="time" name="GioBatDau" class="form-control" required value="<?=isset($edit['GioBatDau'])?substr($edit['GioBatDau'],0,5):''?>">
            </div>
            <div class="col-md-2">
                <input type="time" name="GioKetThuc" class="form-control" required value="<?=isset($edit['GioKetThuc'])?substr($edit['GioKetThuc'],0,5):''?>">
            </div>
            <div class="col-md-2">
                <select name="MaPK" class="form-select" required>
                    <option value="">Phòng khám</option>
                    <?php foreach($rooms as $r):?>
                        <option value="<?=$r['MaPK']?>" <?=($edit['MaPK']??0)==$r['MaPK']?'selected':''?>><?=htmlspecialchars($r['TenPhong'])?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" min="1" name="SoLuongToiDa" class="form-control" value="<?=$edit['SoLuongToiDa']??1?>" placeholder="SL tối đa">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><?=$edit?'Cập nhật':'Tạo lịch'?></button>
            </div>
            <div class="col-12">
                <input name="GhiChu" class="form-control" placeholder="Ghi chú" value="<?=htmlspecialchars($edit['GhiChu']??'')?>">
            </div>
            </div>
        </form>
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
                    <?php foreach($rows as $r):?>
                        <tr>
                            <td>
                                <?=$r['NgayKham']?>
                            </td>
                            <td>
                                <?=substr($r['GioBatDau'],0,5)?> - <?=substr($r['GioKetThuc'],0,5)?>
                            </td>
                            <td>
                                <?=htmlspecialchars(($r['TenPhong']??'').' '.($r['ViTri']??''))?>
                            </td>
                            <td>
                                <?=$r['SoLuongDaDat']?> / <?=$r['SoLuongToiDa']?>
                            </td>
                            <td>
                                <?=$r['TrangThai']?>
                            </td>
                            <td>
                                <?=htmlspecialchars($r['GhiChu']??'')?>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-warning"
                                href="?edit=<?=$r['MaLich']?>">
                                    Sửa
                                </a>

                                <form class="d-inline"
                                    method="post"
                                    onsubmit="return confirm('Bạn có chắc muốn xóa lịch làm việc này?');">

                                    <input type="hidden"
                                        name="MaLich"
                                        value="<?=$r['MaLich']?>">

                                    <input type="hidden"
                                        name="action"
                                        value="delete">

                                    <button class="btn btn-sm btn-danger">
                                        Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </body>
</html>
