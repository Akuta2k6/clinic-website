<?php
require_once __DIR__.'/../auth/check.php'; requireRole('Admin'); require_once __DIR__.'/../config/database.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??''; $id=(int)($_POST['MaCK']??0);
  try{
    if($action==='save'){
      $ten=trim($_POST['TenChuyenKhoa']??''); $mota=trim($_POST['MoTa']??'');
      if($ten==='') throw new Exception('Tên chuyên khoa không được để trống.');
      if($id){$s=$pdo->prepare('UPDATE ChuyenKhoa SET TenChuyenKhoa=?,MoTa=? WHERE MaCK=?');$s->execute([$ten,$mota?:null,$id]);}
      else {$s=$pdo->prepare('INSERT INTO ChuyenKhoa(TenChuyenKhoa,MoTa) VALUES(?,?)');$s->execute([$ten,$mota?:null]);}
    } 
    elseif ($action === 'delete' && $id) {

      $s = $pdo->prepare('SELECT COUNT(*) FROM BacSi WHERE MaCK=?');
      $s->execute([$id]);

      if ($s->fetchColumn() > 0) {
          throw new Exception(
              'Không thể xóa chuyên khoa vì vẫn còn bác sĩ thuộc chuyên khoa này.'
          );
      }

      $pdo->prepare('DELETE FROM ChuyenKhoa WHERE MaCK=?')
          ->execute([$id]);
    }
  header('Location: specialties.php'); exit;
  }catch(Throwable $e){$error=$e->getMessage();}
}
$edit=null; if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM ChuyenKhoa WHERE MaCK=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$rows=$pdo->query('SELECT ck.*,COUNT(bs.MaBS) SoBacSi FROM ChuyenKhoa ck LEFT JOIN BacSi bs ON bs.MaCK=ck.MaCK GROUP BY ck.MaCK ORDER BY ck.MaCK DESC')->fetchAll();
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Chuyên khoa</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body>
    <div class="container py-4">
    <div class="d-flex justify-content-between mb-3">
    <h2>Quản lý chuyên khoa</h2>
    <a class="btn btn-outline-secondary" href="dashboard.php">Dashboard</a>
  </div>
<?php if($error):?>
  <div class="alert alert-danger">
    <?=htmlspecialchars($error)?>
  </div>
<?php endif;?>
<form method="post" class="card card-body mb-4">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="MaCK" value="<?=$edit['MaCK']??''?>">
  <div class="row g-2">
    <div class="col-md-4">
      <input class="form-control" name="TenChuyenKhoa" placeholder="Tên chuyên khoa" required value="<?=htmlspecialchars($edit['TenChuyenKhoa']??'')?>">
    </div>
    <div class="col-md-6">
      <input class="form-control" name="MoTa" placeholder="Mô tả" value="<?=htmlspecialchars($edit['MoTa']??'')?>">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100"><?=$edit?'Cập nhật':'Thêm'?></button>
    </div>
  </div>
</form>
<table class="table table-bordered align-middle">
  <thead>
    <tr>
      <th>Mã</th>
      <th>Tên</th>
      <th>Mô tả</th>
      <th>Số bác sĩ</th>
      <th>Trạng thái</th>
      <th>Thao tác</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r):?>
      <tr>
        <td>
          <?=$r['MaCK']?>
        </td>
        <td>
          <?=htmlspecialchars($r['TenChuyenKhoa'])?>
        </td>
        <td>
          <?=htmlspecialchars($r['MoTa']??'')?>
        </td>
        <td>
          <?=$r['SoBacSi']?>
        </td>
        <td>
          <?=$r['TrangThai']?'Hoạt động':'Đã khóa'?>
        </td>
        <td>
          <a class="btn btn-sm btn-warning" href="?edit=<?=$r['MaCK']?>">Sửa</a> 
          <form class="d-inline" method="post"
                onsubmit="return confirm('Bạn có chắc muốn xóa chuyên khoa này?');">

              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="MaCK" value="<?=$r['MaCK']?>">

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
