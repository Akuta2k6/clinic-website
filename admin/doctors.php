<?php
require_once __DIR__.'/../auth/check.php'; requireRole('Admin'); require_once __DIR__.'/../config/database.php';
$error='';
$avatarMoi=null;
function taoMaBacSi(PDO $pdo, int $maCK): string
{
    // Lấy chữ viết tắt của chuyên khoa
    $stmt = $pdo->prepare(
        'SELECT MaVietTat
         FROM ChuyenKhoa
         WHERE MaCK = ?'
    );

    $stmt->execute([$maCK]);

    $prefix = $stmt->fetchColumn();

    if (!$prefix) {
        throw new Exception('Không tìm thấy mã viết tắt của chuyên khoa.');
    }

    // Lấy tất cả mã bác sĩ của chuyên khoa đó
    $stmt = $pdo->prepare(
        'SELECT MaBS
         FROM BacSi
         WHERE MaBS LIKE ?
         ORDER BY MaBS'
    );

    $stmt->execute([$prefix . '%']);

    $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $usedNumbers = [];

    foreach ($codes as $code) {

        $number = substr($code, strlen($prefix));

        if (ctype_digit($number)) {
            $usedNumbers[] = (int)$number;
        }
    }

    $number = 1;

    while (in_array($number, $usedNumbers, true)) {
        $number++;
    }

    return $prefix . str_pad(
        (string)$number,
        3,
        '0',
        STR_PAD_LEFT
    );
}

function luuAvatarBacSi(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? -1) !== UPLOAD_ERR_OK) throw new RuntimeException('Không thể tải ảnh lên. Vui lòng thử lại.');
    if (!is_uploaded_file($file['tmp_name']) || $file['size'] > 2 * 1024 * 1024 || $file['size'] <= 0) {
        throw new RuntimeException('Ảnh không hợp lệ hoặc vượt quá 2 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'][$mime] ?? null;
    if (!$ext || @getimagesize($file['tmp_name']) === false) throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WebP hợp lệ.');
    $dir = __DIR__ . '/../assets/uploads/doctors';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Không tạo được thư mục ảnh bác sĩ.');
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Không lưu được ảnh bác sĩ.');
    return 'assets/uploads/doctors/' . $name;
}
function xoaAvatarDuocQuanLy(?string $path): void
{
    if (!$path || !preg_match('~^assets/uploads/doctors/[a-f0-9]{32}\.(jpg|png|webp)$~', $path)) return;
    $file = __DIR__ . '/../' . $path;
    if (is_file($file)) @unlink($file);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action = $_POST['action'] ?? '';
  $id = trim($_POST['MaBS'] ?? ''); try{
  if($action==='save'){
    $hoten=trim($_POST['HoTen']??'');$mack=(int)($_POST['MaCK']??0);$email=trim($_POST['Email']??'');$sdt=trim($_POST['SoDienThoai']??'');$hocvi=trim($_POST['HocVi']??'');$chuyenmon=trim($_POST['ChuyenMon']??'');$kn=(int)($_POST['KinhNghiem']??0);
    if($hoten===''||!$mack) throw new Exception('Họ tên và chuyên khoa là bắt buộc.');
    $avatarCu=null;
    if($id){$s=$pdo->prepare('SELECT AnhDaiDien FROM BacSi WHERE MaBS=?');$s->execute([$id]);$avatarCu=$s->fetchColumn();if($avatarCu===false) throw new Exception('Không tìm thấy bác sĩ.');}
    if(!$id){$username=trim($_POST['TenDangNhap']??'');$password=$_POST['MatKhau']??'';if($username===''||strlen($password)<6) throw new Exception('Tên đăng nhập và mật khẩu ít nhất 6 ký tự là bắt buộc khi thêm bác sĩ.');}
    $avatarMoi=luuAvatarBacSi($_FILES['AnhDaiDien'] ?? []);
    $avatar=$avatarMoi??$avatarCu;
    if($id){
      $pdo->prepare('UPDATE BacSi SET MaCK=?,HoTen=?,Email=?,SoDienThoai=?,HocVi=?,ChuyenMon=?,KinhNghiem=?,AnhDaiDien=? WHERE MaBS=?')->execute([$mack,$hoten,$email?:null,$sdt?:null,$hocvi?:null,$chuyenmon?:null,$kn,$avatar,$id]);
      if($avatarMoi) xoaAvatarDuocQuanLy($avatarCu);
    }else{
      $username=trim($_POST['TenDangNhap']??'');$password=$_POST['MatKhau']??'';
      if($username===''||strlen($password)<6) throw new Exception('Tên đăng nhập và mật khẩu ít nhất 6 ký tự là bắt buộc khi thêm bác sĩ.');
      $pdo->beginTransaction();
      $pdo->prepare("INSERT INTO TaiKhoan(TenDangNhap,MatKhau,VaiTro) VALUES(?,?,'BacSi')")->execute([$username,password_hash($password,PASSWORD_DEFAULT)]);
      $matk=(int)$pdo->lastInsertId();
      $maBS = taoMaBacSi($pdo, $mack);

      $pdo->prepare(
          'INSERT INTO BacSi(
              MaBS,
              MaTK,
              MaCK,
              HoTen,
              Email,
              SoDienThoai,
              HocVi,
              ChuyenMon,
              KinhNghiem,AnhDaiDien
          )
          VALUES(?,?,?,?,?,?,?,?,?,?)'
      )->execute([
          $maBS,
          $matk,
          $mack,
          $hoten,
          $email ?: null,
          $sdt ?: null,
          $hocvi ?: null,
          $chuyenmon ?: null,
          $kn, $avatar
      ]);      
      $pdo->commit();
    }
  } elseif($action === 'delete' && $id) {

    $s = $pdo->prepare('SELECT MaTK,AnhDaiDien FROM BacSi WHERE MaBS=?');
    $s->execute([$id]);
    $bacSiCanXoa = $s->fetch(PDO::FETCH_ASSOC);
    $matk = $bacSiCanXoa['MaTK'] ?? null;

    if (!$matk) {
        throw new Exception('Không tìm thấy bác sĩ.');
    }

    // Không cho xóa nếu bác sĩ đã có lịch làm việc
    $s = $pdo->prepare('SELECT COUNT(*) FROM LichKham WHERE MaBS=?');
    $s->execute([$id]);

    if ($s->fetchColumn() > 0) {
        throw new Exception(
            'Không thể xóa bác sĩ vì bác sĩ này đã có lịch khám hoặc lịch sử khám.'
        );
    }

    $pdo->beginTransaction();

    $pdo->prepare('DELETE FROM BacSi WHERE MaBS=?')
        ->execute([$id]);

    $pdo->prepare('DELETE FROM TaiKhoan WHERE MaTK=?')
        ->execute([$matk]);

    $pdo->commit();
    xoaAvatarDuocQuanLy($bacSiCanXoa['AnhDaiDien'] ?? null);
  } elseif($action==='reset_password'&&$id){
    $pwd=$_POST['NewPassword']??'';if(strlen($pwd)<6)throw new Exception('Mật khẩu mới phải có ít nhất 6 ký tự.');$s=$pdo->prepare('SELECT MaTK FROM BacSi WHERE MaBS=?');$s->execute([$id]);$matk=$s->fetchColumn();if($matk)$pdo->prepare('UPDATE TaiKhoan SET MatKhau=? WHERE MaTK=?')->execute([password_hash($pwd,PASSWORD_DEFAULT),$matk]);
  }
  header('Location: doctors.php');exit;
 }catch(Throwable $e){
  if($pdo->inTransaction())$pdo->rollBack();
  if($avatarMoi) xoaAvatarDuocQuanLy($avatarMoi);
  $error=$e->getMessage();
  }
}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT bs.*,tk.TenDangNhap FROM BacSi bs JOIN TaiKhoan tk ON bs.MaTK=tk.MaTK WHERE bs.MaBS=?');
$s->execute([trim($_GET['edit'])]);
$edit=$s->fetch();}
$cks=$pdo->query('SELECT MaCK,TenChuyenKhoa FROM ChuyenKhoa WHERE TrangThai=1 ORDER BY TenChuyenKhoa')->fetchAll();
$rows=$pdo->query('SELECT bs.MaBS,bs.HoTen,bs.Email,bs.SoDienThoai,bs.HocVi,bs.AnhDaiDien,ck.TenChuyenKhoa,bs.TrangThai,tk.TenDangNhap FROM BacSi bs JOIN ChuyenKhoa ck ON bs.MaCK=ck.MaCK JOIN TaiKhoan tk ON bs.MaTK=tk.MaTK ORDER BY bs.MaBS DESC')->fetchAll();
?>
<!doctype html><html lang="vi">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Bác sĩ</title><link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body>
    <div class="container py-4">
      <div class="d-flex justify-content-between mb-3">
        <h2>Quản lý bác sĩ</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
      </div>
<?php if($error):?>
  <div class="alert alert-danger">
    <?=htmlspecialchars($error)?>
  </div><?php endif;?>
<form method="post" enctype="multipart/form-data" class="card card-body mb-4">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="MaBS" value="<?=$edit['MaBS']??''?>">
  <div class="row g-2">
<div class="col-md-4">
  <input name="HoTen" class="form-control" placeholder="Họ tên" required value="<?=htmlspecialchars($edit['HoTen']??'')?>">
</div>
<div class="col-md-4">
  <select name="MaCK" class="form-select" required>
    <option value="">Chọn chuyên khoa</option>
    <?php foreach($cks as $c):?>
      <option value="<?=$c['MaCK']?>" <?=($edit['MaCK']??0)==$c['MaCK']?'selected':''?>>
        <?=htmlspecialchars($c['TenChuyenKhoa'])?>
      </option>
    <?php endforeach;?>
  </select>
</div>
<div class="col-md-4">
  <input name="Email" type="email" class="form-control" placeholder="Email" value="<?=htmlspecialchars($edit['Email']??'')?>">
</div>
<div class="col-md-3">
  <input name="SoDienThoai" class="form-control" placeholder="Số điện thoại" value="<?=htmlspecialchars($edit['SoDienThoai']??'')?>">
</div>
<div class="col-md-3">
  <input name="HocVi" class="form-control" placeholder="Học vị" value="<?=htmlspecialchars($edit['HocVi']??'')?>">
</div>
<div class="col-md-3">
  <input name="ChuyenMon" class="form-control" placeholder="Chuyên môn" value="<?=htmlspecialchars($edit['ChuyenMon']??'')?>">
</div>
<div class="col-md-3">
  <input name="KinhNghiem" type="number" min="0" class="form-control" placeholder="Kinh nghiệm (năm)" value="<?=$edit['KinhNghiem']??0?>">
</div>
<div class="col-md-12">
  <label class="form-label">Ảnh đại diện bác sĩ (JPG, PNG, WebP; tối đa 2 MB)</label>
  <?php if(!empty($edit['AnhDaiDien'])): ?>
    <div class="mb-2"><img src="../<?=htmlspecialchars($edit['AnhDaiDien'],ENT_QUOTES,'UTF-8')?>" alt="Ảnh bác sĩ" width="90" height="90" style="object-fit:cover;border-radius:8px"></div>
  <?php endif; ?>
  <input type="file" name="AnhDaiDien" class="form-control" accept="image/jpeg,image/png,image/webp" id="doctorAvatarInput">
  <img id="doctorAvatarPreview" alt="Xem trước ảnh" style="display:none;width:90px;height:90px;object-fit:cover;border-radius:8px;margin-top:8px">
  <div class="form-text">Không chọn ảnh mới sẽ giữ nguyên ảnh hiện tại.</div>
</div>
<?php if(!$edit):?>
  <div class="col-md-6">
    <input name="TenDangNhap" class="form-control" placeholder="Tên đăng nhập" required>
  </div>
  <div class="col-md-4">
    <input name="MatKhau" type="password" class="form-control" placeholder="Mật khẩu (>= 6 ký tự)" required>
  </div>
<?php else:?>
  <div class="col-md-10">
    <input class="form-control" value="Tài khoản: <?=htmlspecialchars($edit['TenDangNhap'])?>" disabled>
  </div>
<?php endif;?>
  <div class="col-md-2">
    <button class="btn btn-primary w-100"><?=$edit?'Cập nhật':'Thêm bác sĩ'?></button>
  </div>
</div>
</form>
<table class="table table-bordered align-middle">
  <thead>
    <tr>
      <th>Ảnh</th>
      <th>Mã</th>
      <th>Bác sĩ</th>
      <th>Tài khoản</th>
      <th>Chuyên khoa</th>
      <th>Liên hệ</th>
      <th>Trạng thái</th>
      <th>Thao tác</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r):?>
      <tr>
        <td><?php if(!empty($r['AnhDaiDien'])): ?><img src="../<?=htmlspecialchars($r['AnhDaiDien'],ENT_QUOTES,'UTF-8')?>" alt="Avatar" width="56" height="56" style="object-fit:cover;border-radius:50%"><?php else: ?><span class="text-muted">Chưa có ảnh</span><?php endif; ?></td>
        <td>
          <?=$r['MaBS']?>
        </td>
        <td>
          <?=htmlspecialchars($r['HoTen'])?>
          <br>
          <small>
            <?=htmlspecialchars($r['HocVi']??'')?>
          </small>
        </td>
        <td>
          <?=htmlspecialchars($r['TenDangNhap'])?>
        </td>
        <td>
          <?=htmlspecialchars($r['TenChuyenKhoa'])?>
        </td>
        <td>
          <?=htmlspecialchars($r['Email']??'')?>
          <br>
          <?=htmlspecialchars($r['SoDienThoai']??'')?>
        </td>
        <td>
          <?=$r['TrangThai']?'Hoạt động':'Đã khóa'?>
        </td>
        <td>
          <a class="btn btn-sm btn-warning" href="?edit=<?=$r['MaBS']?>">Sửa</a>
          <form class="d-inline" method="post"
                onsubmit="return confirm('Bạn có chắc muốn xóa bác sĩ và tài khoản này?');">

              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="MaBS" value="<?=$r['MaBS']?>">

              <button class="btn btn-sm btn-danger">
                  Xóa
              </button>
          </form>
          <form method="post" class="mt-2 d-flex gap-1">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="MaBS" value="<?=$r['MaBS']?>">
            <input name="NewPassword" class="form-control form-control-sm" placeholder="Mật khẩu mới">
            <button class="btn btn-sm btn-secondary">Đặt lại</button>
          </form></td>
        </tr>
    <?php endforeach;?>
  </tbody>
</table>
</div>
<script>
document.getElementById('doctorAvatarInput')?.addEventListener('change', function () {
  const preview=document.getElementById('doctorAvatarPreview');
  if(preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
  if(!this.files.length){preview.style.display='none';return;}
  const url=URL.createObjectURL(this.files[0]);preview.src=url;preview.dataset.url=url;preview.style.display='block';
});
</script>
</body>
</html>
