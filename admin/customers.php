<?php
// /project-mongo/admin/customers.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== VỊ TRÍ ROOT & AUTOLOAD =====
$ROOT = realpath(__DIR__ . '/..');
$AUTO = $ROOT . '/vendor/autoload.php';

// ===== Kiểm tra autoload =====
if (!file_exists($AUTO)) {
  echo "<h3>Thiếu vendor/autoload.php</h3>
        <p>Chạy lệnh sau trong thư mục <code>project-mongo</code>:</p>
        <pre>cd C:\\xampp\\htdocs\\project-mongo
composer require mongodb/mongodb:^1.19</pre>";
  exit;
}
require_once $AUTO;

// ===== Kiểm tra extension mongodb =====
if (!extension_loaded('mongodb')) {
  echo "<h3>PHP extension 'mongodb' chưa bật</h3>
        <p>Mở <code>C:\\xampp\\php\\php.ini</code> và thêm:</p>
        <pre>extension=mongodb</pre>
        <p>Rồi <b>Restart Apache</b>.</p>";
  exit;
}

// ===== Chỉ admin mới vào =====
if (!isset($_SESSION['auth'])) { 
  header('Location: ../account/login.php'); 
  exit; 
}
if (($_SESSION['auth']['role'] ?? '') !== 'admin') { 
  http_response_code(403); 
  die('Forbidden'); 
}

// ===== Kết nối MongoDB =====
use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$client = new Client('mongodb://localhost:27017');
$db     = $client->selectDatabase('shop_phongthuy');

// Thử các tên collection có thể có
$colUsers = null;
$possibleCollections = ['nguoidung', 'NguoiDung', 'khachhang', 'KhachHang', 'users'];

foreach ($possibleCollections as $collName) {
  try {
    $testCol = $db->selectCollection($collName);
    if ($testCol->countDocuments([]) > 0) {
      $colUsers = $testCol;
      break;
    }
  } catch (Throwable $e) {
    continue;
  }
}

// Nếu không tìm thấy collection nào có dữ liệu, dùng 'nguoidung' làm mặc định
if (!$colUsers) {
  $colUsers = $db->selectCollection('nguoidung');
}

// ===== CONSTANTS =====
if (!defined('ADMIN_URL')) define('ADMIN_URL', '/project-mongo/admin');
if (!defined('BASE_URL'))  define('BASE_URL',  '/project-mongo');

// ===== Hàm helper =====
function h($s) { 
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
}

function getFieldValue($doc, array $possibleKeys, $default = '') {
  foreach ($possibleKeys as $key) {
    if (isset($doc[$key]) && $doc[$key] !== '' && $doc[$key] !== null) {
      return $doc[$key];
    }
  }
  return $default;
}

// ===== Actions (POST) =====
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['action'] ?? '';
  $id  = $_POST['id'] ?? '';
  try {
    if ($id) $oid = new ObjectId($id);

    if ($act === 'toggle') {
      $u   = $colUsers->findOne(['_id'=>$oid]);
      $cur = getFieldValue($u, ['TrangThai', 'trangthai', 'status'], 'active');
      $new = ($cur === 'active') ? 'locked' : 'active';
      $colUsers->updateOne(['_id'=>$oid], ['$set'=>[
        'TrangThai'=>$new,
        'trangthai'=>$new,
        'status'=>$new,
        'updatedAt'=>new UTCDateTime()
      ]]);
      $notice = "Đã đổi trạng thái: {$new}.";

    } elseif ($act === 'resetpw') {
      $temp = bin2hex(random_bytes(4));
      $hash = password_hash($temp, PASSWORD_BCRYPT);
      $colUsers->updateOne(['_id'=>$oid], ['$set'=>[
        'Matkhau' => $hash,
        'matkhau' => $hash,
        'password' => $hash,
        'mustChangePassword' => true,
        'updatedAt' => new UTCDateTime()
      ]]);
      $notice = "Mật khẩu tạm: {$temp}";

    } elseif ($act === 'delete') {
      $colUsers->updateOne(['_id'=>$oid], ['$set'=>[
        'TrangThai'=>'deleted',
        'trangthai'=>'deleted', 
        'status'=>'deleted',
        'deletedAt'=>new UTCDateTime(), 
        'updatedAt'=>new UTCDateTime()
      ]]);
      $notice = "Đã xóa mềm người dùng.";
    }
  } catch (Throwable $e) {
    $notice = 'Lỗi: '.$e->getMessage();
  }
}

// ===== Filters & paging =====
$q = trim($_GET['q'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$skip = ($page - 1) * $limit;

$filter = [];
$and = [];

// Tìm kiếm theo nhiều trường có thể
if ($q !== '') {
  $and[] = ['$or' => [
    ['Hoten' => ['$regex' => $q, '$options' => 'i']],
    ['hoten' => ['$regex' => $q, '$options' => 'i']],
    ['HoTen' => ['$regex' => $q, '$options' => 'i']],
    ['name' => ['$regex' => $q, '$options' => 'i']],
    ['Email' => ['$regex' => $q, '$options' => 'i']],
    ['email' => ['$regex' => $q, '$options' => 'i']],
    ['Sdt' => ['$regex' => $q, '$options' => 'i']],
    ['sdt' => ['$regex' => $q, '$options' => 'i']],
    ['phone' => ['$regex' => $q, '$options' => 'i']],
    ['Diachi' => ['$regex' => $q, '$options' => 'i']],
    ['diachi' => ['$regex' => $q, '$options' => 'i']],
    ['address' => ['$regex' => $q, '$options' => 'i']],
  ]];
}

if ($role !== '') {
  $and[] = ['$or' => [
    ['Role' => $role],
    ['role' => $role],
    ['VaiTro' => $role],
    ['vaitro' => $role],
  ]];
}

if ($status !== '') {
  $and[] = ['$or' => [
    ['TrangThai' => $status],
    ['trangthai' => $status],
    ['status' => $status],
  ]];
}

if ($and) {
  $filter = ['$and' => $and];
}

$total = $colUsers->countDocuments($filter);
$cursor = $colUsers->find($filter, ['sort' => ['_id' => -1], 'skip' => $skip, 'limit' => $limit]);
$pages = (int)ceil($total / $limit);

// Xác định tab đang chọn
$view = $_GET['view'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý khách hàng – Vô ưu Quán</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- STYLES -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/customers_style.css">
</head>

<body>

<?php include __DIR__ . '/partials/admin_topbar.php'; ?>

<div class="dvq-main">
  <div class="dvq-container">

    <!-- HEADER -->
    <h1 class="dvq-title">Quản lý khách hàng</h1>
    <p class="dvq-sub">Quản lý tài khoản khách hàng. Bạn có thể khóa/mở khóa, reset mật khẩu hoặc xóa mềm tài khoản.</p>

    <!-- TABS & ACTIONS -->
    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin:20px 0; flex-wrap:wrap;">
      <!-- TABS -->
      <div class="tab-pills">
        <a href="<?= ADMIN_URL ?>/customers.php?view=all" 
           class="tab-pill <?= $view === 'all' ? 'tab-active' : '' ?>">
          <i class="fa-solid fa-users"></i> Tất cả
        </a>
        <a href="<?= ADMIN_URL ?>/customers.php?view=user&role=user" 
           class="tab-pill <?= $view === 'user' ? 'tab-active' : '' ?>">
          <i class="fa-solid fa-user"></i> Khách
        </a>
        <a href="<?= ADMIN_URL ?>/customers.php?view=admin&role=admin" 
           class="tab-pill <?= $view === 'admin' ? 'tab-active' : '' ?>">
          <i class="fa-solid fa-user-shield"></i> Quản trị viên
        </a>
        <a href="<?= ADMIN_URL ?>/customers.php?view=locked&status=locked" 
           class="tab-pill <?= $view === 'locked' ? 'tab-active' : '' ?>">
          <i class="fa-solid fa-lock"></i> Bị khóa
        </a>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="dvq-actions">
        <a href="<?= ADMIN_URL ?>/customers.php" class="dvq-btn btn-icon">
          <i class="fa-solid fa-rotate-right"></i><span>Tải lại</span>
        </a>
      </div>
    </div>

    <!-- NOTICE -->
    <?php if ($notice): ?>
      <div class="notice-box">
        <i class="fa-solid fa-circle-check"></i> <?= h($notice) ?>
      </div>
    <?php endif; ?>

    <!-- FILTERS -->
    <div class="filter-card">
      <form method="get" class="filter-row">
        <input type="text" name="q" value="<?= h($q) ?>" 
               placeholder="🔍 Tìm tên, email, SĐT, địa chỉ…">
        
        <select name="role">
          <option value="">— Vai trò —</option>
          <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Khách hàng</option>
          <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        
        <select name="status">
          <option value="">— Trạng thái —</option>
          <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
          <option value="locked" <?= $status === 'locked' ? 'selected' : '' ?>>Bị khóa</option>
          <option value="deleted" <?= $status === 'deleted' ? 'selected' : '' ?>>Đã xóa</option>
        </select>
        
        <button type="submit" class="dvq-btn primary">
          <i class="fa-solid fa-filter"></i> Lọc
        </button>
        
        <a href="customers.php" class="dvq-btn">
          <i class="fa-solid fa-xmark"></i> Xóa lọc
        </a>
      </form>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Họ tên</th>
            <th>Email</th>
            <th>SĐT</th>
            <th>Địa chỉ</th>
            <th>Vai trò</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
            <th style="text-align:center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($total === 0): ?>
          <tr>
            <td colspan="8" class="empty-state">
              <i class="fa-solid fa-users-slash"></i>
              <p>Không tìm thấy khách hàng nào</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($cursor as $u):
            $id = (string)$u['_id'];
            $stat = getFieldValue($u, ['TrangThai', 'trangthai', 'status'], 'active');
            $createdAt = isset($u['_id']) ? (new DateTime('@' . hexdec(substr((string)$u['_id'], 0, 8))))->format('d/m/Y H:i') : '';
            
            $hoten = getFieldValue($u, ['Hoten', 'hoten', 'HoTen', 'name'], 'N/A');
            $email = getFieldValue($u, ['Email', 'email'], 'N/A');
            $sdt = getFieldValue($u, ['Sdt', 'sdt', 'phone'], 'N/A');
            $diachi = getFieldValue($u, ['Diachi', 'diachi', 'address'], 'N/A');
            $vaitro = getFieldValue($u, ['Role', 'role', 'VaiTro', 'vaitro'], 'user');
          ?>
          <tr>
            <td><?= h($hoten) ?></td>
            <td><?= h($email) ?></td>
            <td><?= h($sdt) ?></td>
            <td><?= h($diachi) ?></td>
            <td><?= h($vaitro) ?></td>
            <td><span class="badge <?= $stat ?>"><?= $stat ?></span></td>
            <td><?= $createdAt ?></td>
            <td class="actions">
              <form method="post" onsubmit="return confirm('Đổi trạng thái tài khoản này?')" style="display:inline">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="toggle">
                <button class="dvq-btn" title="Khóa/Mở khóa">
                  <i class="fa-solid fa-lock"></i>
                </button>
              </form>
              
              <form method="post" onsubmit="return confirm('Reset mật khẩu và cấp mật khẩu tạm?')" style="display:inline">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="resetpw">
                <button class="dvq-btn primary" title="Reset mật khẩu">
                  <i class="fa-solid fa-key"></i>
                </button>
              </form>
              
              <form method="post" onsubmit="return confirm('Xóa mềm người dùng này?')" style="display:inline">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="delete">
                <button class="dvq-btn" style="background:#b33;color:#fff;border-color:#b33" title="Xóa">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($pages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $pages; $i++):
        $qs = array_filter(['q' => $q, 'role' => $role, 'status' => $status, 'page' => $i]);
        $link = 'customers.php?' . http_build_query($qs);
        if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= $link ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <p style="margin-top:16px;color:var(--dvq-muted);font-size:13px">
      <i class="fa-solid fa-users"></i> Tổng: <strong><?= number_format($total) ?></strong> khách hàng
    </p>

  </div>
</div>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="container footer-content">
    <p>&copy; <?= date('Y') ?> Vô ưu Quán – Vật phẩm Phật giáo. Sản phẩm cam kết hoàn toàn từ tự nhiên.</p>
    <ul class="footer-list">
      <li><i class="fa-solid fa-map-marker-alt fa-sm"></i> 256 Nguyễn Văn Cừ - Phường An Hòa - Quận Ninh Kiều - TPCT</li>
      <li><i class="fa-solid fa-phone fa-sm"></i> Hotline: <a href="tel:0389883981" style="color:#fff">0389 883 981</a></li>
      <li><i class="fa-solid fa-envelope fa-sm"></i> Email: <a href="mailto:vouuquanvn@gmail.com" style="color:#fff">vouuquan@gmail.com</a></li>
    </ul>     
  </div>
</footer>

<style>
.footer-list {
  list-style: none;
  margin: 0;
}
</style>

</body>
</html>