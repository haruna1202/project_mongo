<?php
// /project-mongo/admin/customers.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== VỊ TRÍ ROOT & AUTOLOAD (vì file đang nằm trong /admin) =====
$ROOT = realpath(__DIR__ . '/..');                // C:\xampp\htdocs\project-mongo
$AUTO = $ROOT . '/vendor/autoload.php';
require_once $AUTO;

// === Back buttons links ===
$homeHref = '../';
if (file_exists($ROOT.'/index.php')) {
  $homeHref = '../index.php';
} elseif (file_exists($ROOT.'/VoUuQuan.php')) {
  $homeHref = '../VoUuQuan.php';
}
$dashHref = file_exists($ROOT.'/dashboard.php') ? '../dashboard.php' : $homeHref;

if (!file_exists($AUTO)) {
  // Hiển thị lỗi gọn gàng nếu chưa cài composer
  echo "<h3>Thiếu vendor/autoload.php</h3>
        <p>Chạy lệnh sau trong thư mục <code>project-mongo</code>:</p>
        <pre>cd C:\\xampp\\htdocs\\project-mongo
composer require mongodb/mongodb:^1.19</pre>";
  exit;
}
require_once $AUTO;

// ===== Kiểm tra extension mongodb (php.ini) =====
if (!extension_loaded('mongodb')) {
  echo "<h3>PHP extension 'mongodb' chưa bật</h3>
        <p>Mở <code>C:\\xampp\\php\\php.ini</code> và thêm:</p>
        <pre>extension=mongodb</pre>
        <p>Rồi <b>Restart Apache</b>.</p>";
  exit;
}

// ===== Chỉ admin mới vào =====
if (!isset($_SESSION['auth'])) { header('Location: ../account/login.php'); exit; }
if (($_SESSION['auth']['role'] ?? '') !== 'admin') { http_response_code(403); die('Forbidden'); }

// ===== Kết nối MongoDB =====
use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$client = new Client('mongodb://localhost:27017');
$db     = $client->selectDatabase('Shop_phongthuy');

// Ưu tiên 'nguoidung' theo chuẩn mới; nếu trống thì đọc tạm 'khachhang'
$colUsers = $db->selectCollection('nguoidung');
try {
  if ($colUsers->countDocuments([]) === 0) $colUsers = $db->selectCollection('khachhang');
} catch (Throwable $e) { $colUsers = $db->selectCollection('khachhang'); }

// ===== Actions (POST) =====
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['action'] ?? '';
  $id  = $_POST['id'] ?? '';
  try {
    if ($id) $oid = new ObjectId($id);

    if ($act === 'toggle') {
      $u   = $colUsers->findOne(['_id'=>$oid]);
      $cur = ($u['TrangThai'] ?? 'active');
      $new = ($cur === 'active') ? 'locked' : 'active';
      $colUsers->updateOne(['_id'=>$oid], ['$set'=>['TrangThai'=>$new,'updatedAt'=>new UTCDateTime()]]);
      $notice = "Đã đổi trạng thái: {$new}.";

    } elseif ($act === 'resetpw') {
      $temp = bin2hex(random_bytes(4));                     // mật khẩu tạm
      $hash = password_hash($temp, PASSWORD_BCRYPT);
      $colUsers->updateOne(['_id'=>$oid], ['$set'=>[
        'Matkhau' => $hash,                                  // đảm bảo code đăng nhập dùng password_verify
        'mustChangePassword' => true,
        'updatedAt' => new UTCDateTime()
      ]]);
      $notice = "Mật khẩu tạm: {$temp}";

    } elseif ($act === 'delete') {
      $colUsers->updateOne(['_id'=>$oid], ['$set'=>[
        'TrangThai'=>'deleted', 'deletedAt'=>new UTCDateTime(), 'updatedAt'=>new UTCDateTime()
      ]]);
      $notice = "Đã xóa mềm người dùng.";
    }
  } catch (Throwable $e) {
    $notice = 'Lỗi: '.$e->getMessage();
  }
}

// ===== Filters & paging =====
$q=trim($_GET['q'] ?? ''); $role=trim($_GET['role'] ?? ''); $status=trim($_GET['status'] ?? '');
$page=max(1,(int)($_GET['page'] ?? 1)); $limit=10; $skip=($page-1)*$limit;
$filter=[]; $and=[];
if($q!==''){ $and[]=['$or'=>[
  ['Hoten'=>['$regex'=>$q,'$options'=>'i']],
  ['Email'=>['$regex'=>$q,'$options'=>'i']],
  ['Sdt'=>['$regex'=>$q,'$options'=>'i']],
  ['Diachi'=>['$regex'=>$q,'$options'=>'i']],
]];}
if($role!=='')   $and[]=['Role'=>$role];
if($status!=='') $and[]=['TrangThai'=>$status];
if($and) $filter=['$and'=>$and];

$total =$colUsers->countDocuments($filter);
$cursor=$colUsers->find($filter,['sort'=>['_id'=>-1],'skip'=>$skip,'limit'=>$limit]);
$pages =(int)ceil($total/$limit);
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Quản lý khách hàng</title>
<link rel="stylesheet" href="../css/style.css">
<style>
  .page{width:90%;max-width:1200px;margin:24px auto}
  .toolbar{display:flex;gap:12px;align-items:center;justify-content:space-between;margin-bottom:16px}
  .filters{display:flex;gap:8px;align-items:center}
  .filters input,.filters select{padding:8px 10px;border-radius:8px;border:1px solid #ddd}
  .btn{padding:8px 12px;border:none;border-radius:8px;cursor:pointer}
  .btn-primary{background:#933838;color:#fff}
  .btn-ghost{background:#fff;border:1px solid #ddd}
  .btn-danger{background:#b33;color:#fff}
  .badge{padding:3px 8px;border-radius:999px;font-size:12px}
  .badge.active{background:#e7f7ee;color:#137a4b;border:1px solid #c7ead8}
  .badge.locked{background:#fdf1d6;color:#9a6b00;border:1px solid #f2d494}
  .badge.deleted{background:#fde5e5;color:#a11;border:1px solid #f2b7b7}
  table{width:100%;border-collapse:collapse;background:#fff}
  th,td{padding:10px 12px;border-bottom:1px solid #eee;text-align:left}
  th{background:#fff7ec;color:#933838}
  .actions{display:flex;gap:6px}
  .pagination{display:flex;gap:6px;margin-top:12px}
  .pagination a,.pagination span{padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333}
  .pagination .current{background:#933838;color:#fff;border-color:#933838}
  .notice{margin-bottom:10px;color:#0a7}
</style>
</head>
<body>
<div class="page">
  <div class="page-head">
  <h2>Quản lý khách hàng</h2>
  <div class="actions">
    <a href="/project-mongo/admin/dashboard.php" class="btn btn-ghost btn-icon">
      <i class="fa-solid fa-house"></i> Tổng quan
    </a>
    <a href="/project-mongo/admin/customers.php" class="btn btn-ghost btn-icon">
      <i class="fa-solid fa-rotate-right"></i> Tải lại
    </a>
  </div>
</div>
  <div class="page-body">
    <p>Quản lý tài khoản khách hàng. Bạn có thể khóa/mở khóa, reset mật khẩu hoặc xóa mềm tài khoản.</p>
</div>



  <?php if($notice):?><div class="notice"><?=htmlspecialchars($notice)?></div><?php endif;?>

  <div class="toolbar">
    <form class="filters" method="get">
      <input type="text" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Tìm tên, email, SĐT, địa chỉ…">
      <select name="role">
        <option value="">— Vai trò —</option>
        <option value="user"  <?=$role==='user'?'selected':''?>>user</option>
        <option value="admin" <?=$role==='admin'?'selected':''?>>admin</option>
      </select>
      <select name="status">
        <option value="">— Trạng thái —</option>
        <option value="active"  <?=$status==='active'?'selected':''?>>active</option>
        <option value="locked"  <?=$status==='locked'?'selected':''?>>locked</option>
        <option value="deleted" <?=$status==='deleted'?'selected':''?>>deleted</option>
      </select>
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-ghost" href="customers.php">Xóa lọc</a>
    </form>

    <a class="btn btn-ghost"
       href="customers.php?<?=http_build_query(array_filter(['q'=>$q,'role'=>$role,'status'=>$status]))?>&export=1">
       Xuất CSV
    </a>
  </div>

  <table>
    <thead><tr>
      <th>Họ tên</th><th>Email</th><th>SĐT</th><th>Địa chỉ</th>
      <th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th>
    </tr></thead>
    <tbody>
    <?php foreach($cursor as $u):
      $id=(string)$u['_id'];
      $stat=(string)($u['TrangThai'] ?? 'active');
      $badge='badge '.$stat;
      $createdAt = isset($u['_id']) ? (new DateTime('@'.hexdec(substr((string)$u['_id'],0,8))))->format('Y-m-d H:i') : '';
    ?>
      <tr>
        <td><?=htmlspecialchars((string)($u['Hoten'] ?? ''))?></td>
        <td><?=htmlspecialchars((string)($u['Email'] ?? ''))?></td>
        <td><?=htmlspecialchars((string)($u['Sdt'] ?? ''))?></td>
        <td><?=htmlspecialchars((string)($u['Diachi'] ?? ''))?></td>
        <td><?=htmlspecialchars((string)($u['Role'] ?? 'user'))?></td>
        <td><span class="<?=$badge?>"><?=$stat?></span></td>
        <td><?=$createdAt?></td>
        <td class="actions">
          <form method="post" onsubmit="return confirm('Đổi trạng thái tài khoản này?')">
            <input type="hidden" name="id" value="<?=$id?>">
            <input type="hidden" name="action" value="toggle">
            <button class="btn btn-ghost">Khóa/Mở</button>
          </form>
          <form method="post" onsubmit="return confirm('Reset mật khẩu và cấp mật khẩu tạm?')">
            <input type="hidden" name="id" value="<?=$id?>">
            <input type="hidden" name="action" value="resetpw">
            <button class="btn btn-primary">Reset</button>
          </form>
          <form method="post" onsubmit="return confirm('Xóa mềm người dùng này?')">
            <input type="hidden" name="id" value="<?=$id?>">
            <input type="hidden" name="action" value="delete">
            <button class="btn btn-danger">Xóa</button>
          </form>
        </td>
      </tr>
    <?php endforeach;?>
    </tbody>
  </table>

  <div class="pagination">
    <?php for($i=1;$i<=$pages;$i++):
      $qs=array_filter(['q'=>$q,'role'=>$role,'status'=>$status,'page'=>$i]);
      $link='customers.php?'.http_build_query($qs);
      echo $i==$page ? '<span class="current">'.$i.'</span>' : '<a href="'.$link.'">'.$i.'</a>';
    endfor;?>
  </div>
  <p style="margin-top:10px;color:#666">Tổng: <?= (int)$total ?> khách hàng</p>
</div>
</body>
</html>
