<?php
// /project-mongo/admin/content.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ===== ROOT & AUTOLOAD ===== */
$ROOT = realpath(__DIR__ . '/..');      // C:\xampp\htdocs\project-mongo
$AUTO = $ROOT . '/vendor/autoload.php';
if (!file_exists($AUTO)) {
  echo "<h3>Thiếu vendor/autoload.php</h3>
        <pre>cd C:\\xampp\\htdocs\\project-mongo
composer require mongodb/mongodb:^1.19</pre>";
  exit;
}
require_once $AUTO;
if (!extension_loaded('mongodb')) {
  echo "<h3>PHP extension 'mongodb' chưa bật</h3>
        <pre>; trong C:\\xampp\\php\\php.ini
extension=mongodb</pre>
        <p>Restart Apache rồi mở lại trang.</p>";
  exit;
}

/* ===== AUTH: chỉ admin ===== */
if (!isset($_SESSION['auth'])) { header('Location: ../account/login.php'); exit; }
if (($_SESSION['auth']['role'] ?? '') !== 'admin') { http_response_code(403); die('Forbidden'); }

/* ===== DB & Collections ===== */
use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$client = new Client('mongodb://localhost:27017');
$db     = $client->selectDatabase('Shop_phongthuy');

$colContent = $db->selectCollection('noidung'); // mới: lưu banner & trang tĩnh
$colCats    = $db->selectCollection('loaihang');
$colProds   = $db->selectCollection('sanpham');

/* ===== Helpers ===== */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function bval($v){ return (is_bool($v)?$v: (strtolower((string)$v)==='true' || (int)$v===1)); }
function readBool($doc, $keys=['NoiBat','noiBat','noibat']) {
  foreach($keys as $k) if (isset($doc[$k])) return bval($doc[$k]);
  return false;
}
function setBoolUpdate($field, $val){
  return ['$set'=>[$field=>$val, 'updatedAt'=>new UTCDateTime()]];
}

/* ===== Tab & Actions ===== */
$tab = $_GET['tab'] ?? 'banners'; // banners | categories | products | pages

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['action'] ?? '';
  try {
    /* ---- BANNERS ---- */
    if ($act === 'banner_add') {
      $title = trim($_POST['title'] ?? '');
      $desc  = trim($_POST['desc'] ?? '');
      $img   = trim($_POST['img'] ?? '');
      $ord   = (int)($_POST['ord'] ?? 0);
      $show  = isset($_POST['show']) ? true : false;
      $colContent->insertOne([
        'Loai'     => 'banner',
        'TieuDe'   => $title,
        'MoTa'     => $desc,
        'Anh'      => $img,
        'ThuTu'    => $ord,
        'HienThi'  => $show,
        'TrangThai'=> 'active',
        'createdAt'=> new UTCDateTime(),
        'updatedAt'=> new UTCDateTime(),
      ]);
      $notice = 'Đã thêm banner.';
      $tab = 'banners';

    } elseif ($act === 'banner_update') {
      $id   = new ObjectId($_POST['id']);
      $title= trim($_POST['title'] ?? '');
      $desc = trim($_POST['desc'] ?? '');
      $img  = trim($_POST['img'] ?? '');
      $ord  = (int)($_POST['ord'] ?? 0);
      $show = isset($_POST['show']) ? true : false;
      $colContent->updateOne(['_id'=>$id], ['$set'=>[
        'TieuDe'=>$title,'MoTa'=>$desc,'Anh'=>$img,'ThuTu'=>$ord,'HienThi'=>$show,'updatedAt'=>new UTCDateTime()
      ]]);
      $notice = 'Đã cập nhật banner.';
      $tab = 'banners';

    } elseif ($act === 'banner_toggle') {
      $id = new ObjectId($_POST['id']);
      $b  = $colContent->findOne(['_id'=>$id]);
      $cur= bval($b['HienThi'] ?? true);
      $colContent->updateOne(['_id'=>$id], ['$set'=>['HienThi'=>!$cur,'updatedAt'=>new UTCDateTime()]]);
      $notice = 'Đã đổi hiển thị banner.';
      $tab = 'banners';

    } elseif ($act === 'banner_delete') {
      $id = new ObjectId($_POST['id']);
      $colContent->updateOne(['_id'=>$id], ['$set'=>['TrangThai'=>'deleted','updatedAt'=>new UTCDateTime()]]);
      $notice = 'Đã xóa mềm banner.';
      $tab = 'banners';

    /* ---- CATEGORIES FEATURE ---- */
    } elseif ($act === 'cat_toggle_feature') {
      $id = new ObjectId($_POST['id']);
      $c  = $colCats->findOne(['_id'=>$id]);
      $cur= readBool($c);
      $colCats->updateOne(['_id'=>$id], setBoolUpdate('NoiBat', !$cur));
      $notice = 'Đã đổi trạng thái nổi bật cho danh mục.';
      $tab = 'categories';

    /* ---- PRODUCTS FEATURE ---- */
    } elseif ($act === 'prod_toggle_feature') {
      $id = new ObjectId($_POST['id']);
      $p  = $colProds->findOne(['_id'=>$id]);
      $cur= readBool($p);
      $colProds->updateOne(['_id'=>$id], setBoolUpdate('NoiBat', !$cur));
      $notice = 'Đã đổi trạng thái nổi bật cho sản phẩm.';
      $tab = 'products';

    /* ---- PAGES (ABOUT/FOOTER) ---- */
    } elseif ($act === 'page_save') {
      $key   = trim($_POST['key'] ?? 'about');
      $title = trim($_POST['title'] ?? '');
      $body  = trim($_POST['body'] ?? '');
      $colContent->updateOne(
        ['Loai'=>'page','Key'=>$key],
        ['$set'=>[
          'TieuDe'=>$title,
          'NoiDung'=>$body,
          'TrangThai'=>'active',
          'updatedAt'=>new UTCDateTime()
        ], '$setOnInsert'=>[
          'createdAt'=>new UTCDateTime()
        ]],
        ['upsert'=>true]
      );
      $notice = 'Đã lưu nội dung trang.';
      $tab = 'pages';
    }
  } catch (Throwable $e) {
    $notice = 'Lỗi: '.$e->getMessage();
  }
}

/* ===== READ DATA THEO TAB ===== */
$banners = [];
$cats    = [];
$prods   = [];
$about   = $colContent->findOne(['Loai'=>'page','Key'=>'about']);
$footer  = $colContent->findOne(['Loai'=>'page','Key'=>'footer']);

if ($tab === 'banners') {
  $banners = $colContent->find(
    ['Loai'=>'banner','TrangThai'=>['$ne'=>'deleted']],
    ['sort'=>['ThuTu'=>1,'_id'=>-1]]
  );
}
if ($tab === 'categories') {
  $cats = $colCats->find([], ['sort'=>['_id'=>-1]]);
}
if ($tab === 'products') {
  $q = trim($_GET['q'] ?? '');
  $filter = [];
  if ($q!=='') {
    $filter = ['$or'=>[
      ['Ten'   => ['$regex'=>$q,'$options'=>'i']],
      ['ten'   => ['$regex'=>$q,'$options'=>'i']],
      ['TenSP' => ['$regex'=>$q,'$options'=>'i']],
      ['tensp' => ['$regex'=>$q,'$options'=>'i']],
    ]];
  }
  $prods = $colProds->find($filter, ['limit'=>30, 'sort'=>['_id'=>-1]]);
}

/* ===== VIEW ===== */
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Quản lý nội dung</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer"/>
<style>
  .page{width:90%;max-width:1200px;margin:24px auto}
  .page-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
  .page-head h2{margin:0;color:#933838}
  .actions{display:flex;gap:8px;flex-wrap:wrap}
  .btn{padding:8px 12px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
  .btn-ghost{background:#fff;border:1px solid #ddd;color:#333}
  .btn-primary{background:#933838;color:#fff}
  .btn-danger{background:#b33;color:#fff}
  .tabs{display:flex;gap:8px;margin:8px 0 16px}
  .tab{padding:8px 12px;border:1px solid #ddd;border-radius:999px;text-decoration:none;color:#333;background:#fff}
  .tab.active{background:#933838;color:#fff;border-color:#933838}
  table{width:100%;border-collapse:collapse;background:#fff;margin-top:8px}
  th,td{padding:10px 12px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
  th{background:#fff7ec;color:#933838}
  .badge{padding:3px 8px;border-radius:999px;font-size:12px}
  .badge.on{background:#e7f7ee;color:#137a4b;border:1px solid #c7ead8}
  .badge.off{background:#fde5e5;color:#a11;border:1px solid #f2b7b7}
  .notice{margin:8px 0;color:#0a7}
  .grid{display:grid;gap:12px}
  .grid-2{grid-template-columns:1fr 1fr}
  .grid-3{grid-template-columns:1fr 1fr 1fr}
  .field{display:flex;flex-direction:column;gap:6px}
  .field input,.field textarea{padding:8px 10px;border:1px solid #ddd;border-radius:8px}
  .muted{color:#777;font-size:12px}
</style>
</head>
<body>
<div class="page">

  <div class="page-head">
    <h2>Quản lý nội dung</h2>
    <div class="actions">
      <a href="/project-mongo/admin/dashboard.php" class="btn btn-ghost">
        <i class="fa-solid fa-house"></i> Tổng quan
      </a>
      <a href="/project-mongo/admin/content.php?tab=<?=h($tab)?>" class="btn btn-ghost">
        <i class="fa-solid fa-rotate-right"></i> Tải lại
      </a>
    </div>
  </div>

  <div class="tabs">
    <a class="tab <?= $tab==='banners'?'active':''?>"    href="?tab=banners"><i class="fa-solid fa-image"></i> Banners</a>
    <a class="tab <?= $tab==='categories'?'active':''?>" href="?tab=categories"><i class="fa-solid fa-layer-group"></i> Danh mục nổi bật</a>
    <a class="tab <?= $tab==='products'?'active':''?>"   href="?tab=products"><i class="fa-solid fa-fire"></i> Sản phẩm nổi bật</a>
    <a class="tab <?= $tab==='pages'?'active':''?>"      href="?tab=pages"><i class="fa-regular fa-file-lines"></i> Trang tĩnh</a>
  </div>

  <?php if ($notice): ?><div class="notice"><?=h($notice)?></div><?php endif; ?>

  <?php if ($tab==='banners'): ?>
    <!-- ===== TAB: BANNERS ===== -->
    <form method="post" class="grid grid-3" style="margin-top:8px">
      <input type="hidden" name="action" value="banner_add">
      <div class="field">
        <label>Tiêu đề</label>
        <input name="title" placeholder="Ví dụ: Ưu đãi tháng 11">
      </div>
      <div class="field">
        <label>Mô tả</label>
        <input name="desc" placeholder="Mô tả ngắn…">
      </div>
      <div class="field">
        <label>Ảnh (URL hoặc /project-mongo/assets/...)</label>
        <input name="img" placeholder="/project-mongo/assets/banner1.jpg">
      </div>
      <div class="field">
        <label>Thứ tự</label>
        <input type="number" name="ord" value="0">
      </div>
      <div class="field">
        <label>Hiển thị</label>
        <label style="display:flex;align-items:center;gap:6px">
          <input type="checkbox" name="show" checked> Bật
        </label>
      </div>
      <div class="field" style="align-self:end">
        <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm banner</button>
      </div>
    </form>

    <table>
      <thead><tr>
        <th>Thứ tự</th><th>Tiêu đề</th><th>Mô tả</th><th>Ảnh</th><th>Hiển thị</th><th>Thao tác</th>
      </tr></thead>
      <tbody>
      <?php foreach($banners as $b): $id=(string)$b['_id']; $show=bval($b['HienThi'] ?? true); ?>
        <tr>
          <td style="width:80px"><?= (int)($b['ThuTu'] ?? 0) ?></td>
          <td><?= h($b['TieuDe'] ?? '') ?></td>
          <td><?= h($b['MoTa'] ?? '') ?></td>
          <td>
            <?php $img = (string)($b['Anh'] ?? ''); if($img): ?>
              <div class="muted"><?= h($img) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $show?'on':'off' ?>"><?= $show?'Đang hiển thị':'Tắt' ?></span>
          </td>
          <td style="white-space:nowrap">
            <form method="post" style="display:inline" onsubmit="return confirm('Đổi hiển thị banner?')">
              <input type="hidden" name="action" value="banner_toggle">
              <input type="hidden" name="id" value="<?= $id ?>">
              <button class="btn btn-ghost"><i class="fa-solid fa-eye"></i> Toggle</button>
            </form>
            <button class="btn btn-ghost" onclick="openEdit('<?= $id ?>','<?= h($b['TieuDe']??'') ?>','<?= h($b['MoTa']??'') ?>','<?= h($b['Anh']??'') ?>','<?= (int)($b['ThuTu']??0) ?>',<?= $show?'true':'false' ?>)">
              <i class="fa-solid fa-pen"></i> Sửa
            </button>
            <form method="post" style="display:inline" onsubmit="return confirm('Xóa mềm banner này?')">
              <input type="hidden" name="action" value="banner_delete">
              <input type="hidden" name="id" value="<?= $id ?>">
              <button class="btn btn-danger"><i class="fa-solid fa-trash"></i> Xóa</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Modal sửa đơn giản -->
    <div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);align-items:center;justify-content:center">
      <div style="background:#fff;border-radius:12px;min-width:520px;padding:16px">
        <h3 style="margin:0 0 12px">Sửa banner</h3>
        <form method="post" class="grid grid-2">
          <input type="hidden" name="action" value="banner_update">
          <input type="hidden" name="id" id="e_id">
          <div class="field"><label>Tiêu đề</label><input id="e_title" name="title"></div>
          <div class="field"><label>Mô tả</label><input id="e_desc" name="desc"></div>
          <div class="field" style="grid-column:1 / -1"><label>Ảnh (URL hoặc path)</label><input id="e_img" name="img"></div>
          <div class="field"><label>Thứ tự</label><input id="e_ord" name="ord" type="number"></div>
          <div class="field"><label>Hiển thị</label>
            <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" id="e_show" name="show"> Bật</label>
          </div>
          <div style="grid-column:1 / -1;display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
            <button type="button" class="btn btn-ghost" onclick="closeEdit()">Đóng</button>
            <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
          </div>
        </form>
      </div>
    </div>
    <script>
      function openEdit(id,t,d,i,o,s){ 
        document.getElementById('e_id').value=id;
        document.getElementById('e_title').value=t;
        document.getElementById('e_desc').value=d;
        document.getElementById('e_img').value=i;
        document.getElementById('e_ord').value=o;
        document.getElementById('e_show').checked=!!s;
        document.getElementById('editModal').style.display='flex';
      }
      function closeEdit(){ document.getElementById('editModal').style.display='none'; }
    </script>

  <?php elseif ($tab==='categories'): ?>
    <!-- ===== TAB: CATEGORIES ===== -->
    <table>
      <thead><tr><th>Tên danh mục</th><th>Mô tả</th><th>Nổi bật</th><th>Thao tác</th></tr></thead>
      <tbody>
      <?php foreach($cats as $c): $id=(string)$c['_id']; $hot=readBool($c); ?>
        <tr>
          <td><?= h($c['TenLoai'] ?? $c['ten'] ?? $c['name'] ?? '') ?></td>
          <td><?= h($c['MoTa'] ?? $c['mota'] ?? '') ?></td>
          <td><span class="badge <?= $hot?'on':'off' ?>"><?= $hot?'Bật':'Tắt' ?></span></td>
          <td>
            <form method="post" onsubmit="return confirm('Đổi nổi bật danh mục này?')" style="display:inline">
              <input type="hidden" name="action" value="cat_toggle_feature">
              <input type="hidden" name="id" value="<?= $id ?>">
              <button class="btn btn-ghost"><i class="fa-solid fa-star"></i> Toggle</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

  <?php elseif ($tab==='products'): ?>
    <!-- ===== TAB: PRODUCTS ===== -->
    <form method="get" class="actions" style="margin:8px 0">
      <input type="hidden" name="tab" value="products">
      <input name="q" placeholder="Tìm tên sản phẩm…" value="<?=h($_GET['q'] ?? '')?>" style="padding:8px 10px;border:1px solid #ddd;border-radius:8px;min-width:260px">
      <button class="btn btn-ghost"><i class="fa-solid fa-magnifying-glass"></i> Tìm</button>
      <a class="btn btn-ghost" href="?tab=products"><i class="fa-solid fa-rotate-right"></i> Xóa lọc</a>
    </form>

    <table>
      <thead><tr><th>Tên</th><th>Giá</th><th>Ảnh</th><th>Nổi bật</th><th>Thao tác</th></tr></thead>
      <tbody>
      <?php foreach($prods as $p): $id=(string)$p['_id']; $hot=readBool($p); ?>
        <tr>
          <td><?= h($p['Ten'] ?? $p['ten'] ?? $p['TenSP'] ?? $p['tensp'] ?? '') ?></td>
          <td><?= h($p['Gia'] ?? $p['gia'] ?? $p['DonGia'] ?? '') ?></td>
          <td class="muted"><?= h($p['Hinh'] ?? $p['hinh'] ?? $p['Anh'] ?? $p['anh'] ?? '') ?></td>
          <td><span class="badge <?= $hot?'on':'off' ?>"><?= $hot?'Bật':'Tắt' ?></span></td>
          <td>
            <form method="post" onsubmit="return confirm('Đổi nổi bật sản phẩm này?')" style="display:inline">
              <input type="hidden" name="action" value="prod_toggle_feature">
              <input type="hidden" name="id" value="<?= $id ?>">
              <button class="btn btn-ghost"><i class="fa-solid fa-fire"></i> Toggle</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

  <?php elseif ($tab==='pages'): ?>
    <!-- ===== TAB: PAGES (ABOUT & FOOTER) ===== -->
    <div class="grid grid-2" style="margin-top:8px">
      <form method="post" class="grid">
        <input type="hidden" name="action" value="page_save">
        <input type="hidden" name="key" value="about">
        <div class="field"><label>Tiêu đề (About)</label>
          <input name="title" value="<?=h($about['TieuDe'] ?? 'Giới thiệu Vô Ưu Quán')?>"></div>
        <div class="field"><label>Nội dung</label>
          <textarea name="body" rows="8"><?=h($about['NoiDung'] ?? '...')?></textarea></div>
        <div style="display:flex;justify-content:flex-end;gap:8px">
          <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu About</button>
        </div>
      </form>

      <form method="post" class="grid">
        <input type="hidden" name="action" value="page_save">
        <input type="hidden" name="key" value="footer">
        <div class="field"><label>Tiêu đề (Footer)</label>
          <input name="title" value="<?=h($footer['TieuDe'] ?? 'Liên hệ & Chính sách')?>"></div>
        <div class="field"><label>Nội dung</label>
          <textarea name="body" rows="8"><?=h($footer['NoiDung'] ?? '...')?></textarea></div>
        <div style="display:flex;justify-content:flex-end;gap:8px">
          <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu Footer</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
