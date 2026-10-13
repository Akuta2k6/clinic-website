<?php
require_once __DIR__.'/../auth/check.php'; requireRole('Admin'); require_once __DIR__.'/../config/database.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=$_POST['action']??'';$id=(int)($_POST['MaPK']??0);
 try{
  if($action==='save'){$ten=trim($_POST['TenPhong']??'');$vitri=trim($_POST['ViTri']??'');if($ten==='')throw new Exception('Tên phòng không được để trống.');if($id){$pdo->prepare('UPDATE PhongKham SET TenPhong=?,ViTri=? WHERE MaPK=?')->execute([$ten,$vitri?:null,$id]);}else{$pdo->prepare('INSERT INTO PhongKham(TenPhong,ViTri) VALUES(?,?)')->execute([$ten,$vitri?:null]);}}
  elseif($action==='toggle'&&$id){$pdo->prepare('UPDATE PhongKham SET TrangThai=1-TrangThai WHERE MaPK=?')->execute([$id]);}
  header('Location: rooms.php');exit;
 }catch(Throwable $e){$error=$e->getMessage();}
}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM PhongKham WHERE MaPK=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$rows=$pdo->query('SELECT pk.*,COUNT(lk.MaLich) SoLich FROM PhongKham pk LEFT JOIN LichKham lk ON lk.MaPK=pk.MaPK GROUP BY pk.MaPK ORDER BY pk.MaPK DESC')->fetchAll();
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Phòng khám</title><link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet"></head><body><div class="container py-4"><div class="d-flex justify-content-between mb-3"><h2>Quản lý phòng khám</h2><a class="btn btn-outline-secondary" href="dashboard.php">Dashboard</a></div>
<?php if($error):?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" class="card card-body mb-4"><input type="hidden" name="action" value="save"><input type="hidden" name="MaPK" value="<?=$edit['MaPK']??''?>"><div class="row g-2"><div class="col-md-5"><input class="form-control" name="TenPhong" required placeholder="Tên phòng" value="<?=htmlspecialchars($edit['TenPhong']??'')?>"></div><div class="col-md-5"><input class="form-control" name="ViTri" placeholder="Vị trí (VD: Tầng 2)" value="<?=htmlspecialchars($edit['ViTri']??'')?>"></div><div class="col-md-2"><button class="btn btn-primary w-100"><?=$edit?'Cập nhật':'Thêm'?></button></div></div></form>
<table class="table table-bordered"><thead><tr><th>Mã</th><th>Tên phòng</th><th>Vị trí</th><th>Số lịch</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=$r['MaPK']?></td><td><?=htmlspecialchars($r['TenPhong'])?></td><td><?=htmlspecialchars($r['ViTri']??'')?></td><td><?=$r['SoLich']?></td><td><?=$r['TrangThai']?'Hoạt động':'Đã khóa'?></td><td><a class="btn btn-sm btn-warning" href="?edit=<?=$r['MaPK']?>">Sửa</a> <form class="d-inline" method="post"><input type="hidden" name="action" value="toggle"><input type="hidden" name="MaPK" value="<?=$r['MaPK']?>"><button class="btn btn-sm <?=$r['TrangThai']?'btn-outline-danger':'btn-outline-success'?>"><?=$r['TrangThai']?'Khóa':'Mở'?></button></form></td></tr><?php endforeach;?></tbody></table></div></body></html>
