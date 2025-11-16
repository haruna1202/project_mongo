<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db_connect.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;

/* ==== THÔNG TIN USER ĐĂNG NHẬP ==== */
$user    = $_SESSION['auth'] ?? [];
$name    = $user['name'] ?: ($user['email'] ?? 'Người dùng');
$role    = $user['role'] ?? 'user';
$isAdmin = strtolower((string)($user['role'] ?? '')) === 'admin';

// Nếu không phải admin thì đá về dashboard (front)
if (!$isAdmin) {
  header('Location: /project-mongo/dashboard.php');
  exit;
}

/* ==== HÀM PHỤ TRỢ CƠ BẢN ==== */
/** Lấy giá trị từ mảng theo nhiều key, trả về giá trị đầu tiên tìm thấy hoặc mặc định */
function g($r, array $keys, $def = '') {
  foreach ($keys as $k) if (isset($r[$k])) return $r[$k];
  return $def;
}

/** Escape HTML để tránh XSS khi echo ra view */
function esc($s){
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * Lấy dữ liệu từ $_POST theo key và ép kiểu
 * type: 's' = string, 'i' = int, 'f' = float
 */
function _f(string $key, string $type='s'){
  if (!isset($_POST[$key])) return null;
  $v = $_POST[$key];
  if ($type === 's') return trim((string)$v);
  if ($type === 'i') return (int)$v;
  if ($type === 'f') return (float)$v;
  return $v;
}

/* ==== CHUẨN HÓA ĐƯỜNG DẪN ẢNH THEO PROJECT ==== */
function img_url($path){
  $p = trim((string)$path);
  if ($p === '') return '/project-mongo/images/no-image.png';

  $p = str_replace('\\', '/', $p);

  // Nếu là URL đầy đủ
  if (preg_match('#^https?://#i', $p)) return $p;

  // Nếu đã có /project-mongo/ ở đầu
  if (strpos($p, '/project-mongo/') === 0) return $p;

  // Nếu dạng images/...
  if (strpos($p, 'images/') === 0 || strpos($p, '/images/') === 0) {
    $p = ltrim($p, '/');
    return '/project-mongo/' . $p;
  }

  // Còn lại: chỉ tên file → mặc định trong categories
  return '/project-mongo/images/categories/' . basename($p);
}

/* ==== HỖ TRỢ UPLOAD ẢNH SẢN PHẨM ==== */

function slugify_filename(string $name): string {
  $name = trim($name);
  if ($name === '') return 'image';
  $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
  $name = preg_replace('/[^A-Za-z0-9\-]+/', '-', $name);
  $name = trim($name, '-');
  if ($name === '') return 'image';
  return strtolower($name);
}

function save_uploaded_image(string $field, string $basename_hint = ''): ?string {
  if (!isset($_FILES[$field])) return null;
  $f = $_FILES[$field];

  // Không chọn file
  if ($f['error'] === UPLOAD_ERR_NO_FILE) return null;

  if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('Tải ảnh lỗi mã: '.$f['error']);

  // Kiểm tra phần mở rộng
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
    throw new Exception('Định dạng ảnh không hợp lệ.');
  }

  // Giới hạn dung lượng 5MB
  if ($f['size'] > 5*1024*1024) throw new Exception('Dung lượng vượt 5MB.');

  // Thư mục lưu file
  $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
  $dir = $docroot.'/project-mongo/images/product';
  if (!is_dir($dir)) @mkdir($dir, 0777, true);

  // Tạo tên file mới
  $slug = slugify_filename($basename_hint ?: pathinfo($f['name'], PATHINFO_FILENAME));
  $new = $slug.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.'.$ext;

  // Di chuyển file upload vào thư mục đích
  if (!move_uploaded_file($f['tmp_name'], $dir.'/'.$new)) {
    throw new Exception('Không thể lưu ảnh.');
  }

  // Lưu trong DB chỉ dùng path tương đối
  return 'images/product/'.$new;
}

/**
 * Xoá file ảnh cũ trên server (nếu có và không phải link http)
 */
function remove_local_image_if_exists(?string $path): void {
  if (!$path) return;
  $p = trim($path);
  $p = str_replace('\\','/',$p);
  $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');

  // Nếu là URL ngoài thì bỏ qua
  if (preg_match('#^https?://#i', $p)) return;

  // Nếu path có chứa "project-mongo/" ở giữa thì chuẩn hóa lại
  if (($pos = stripos($p, 'project-mongo/')) !== false) {
    $p = substr($p, $pos + strlen('project-mongo'));
    if ($p === '' || $p[0] !== '/') $p = '/'.$p;
  }

  // Xác định đường dẫn vật lý
  if (strpos($p, '/images/product/') === 0) {
    $full = $docroot.'/project-mongo'.$p;
  } elseif (strpos($p, '/images/') === 0) {
    $full = $docroot.'/project-mongo'.$p;
  } elseif (preg_match('#^(images|uploads|assets)/#i', $p)) {
    $full = $docroot.'/project-mongo/'.$p;
  } else {
    $full = $docroot.'/project-mongo/images/product/'.basename($p);
  }
  // Xoá file nếu tồn tại
  if ($full && is_file($full)) {
    @unlink($full);
  }
}

/* ==== XỬ LÝ FORM POST (THÊM / SỬA / XOÁ SẢN PHẨM) ==== */

$msg = '';                                   // Thông báo hiển thị cho admin
$col = $db->selectCollection('sanpham');    // Collection sản phẩm

// Chỉ xử lý khi request là POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $action = $_POST['action'] ?? '';         // create / update / delete

  try {
    /* --- THÊM MỚI SẢN PHẨM --- */
    if ($action === 'create') {
      // Lấy dữ liệu từ form
      $ten     = _f('name','s') ?? '';
      $loai    = _f('category','s') ?? '';
      $gia     = _f('price','f') ?? 0.0;
      $tonkho  = _f('stock','i') ?? 0;
      $mota    = _f('desc','s') ?? '';

      // Validate đơn giản
      if ($ten === '')   throw new Exception('Tên sản phẩm không được để trống.');
      if ($gia < 0)      throw new Exception('Giá không hợp lệ.');
      if ($tonkho < 0)   throw new Exception('Tồn kho không hợp lệ.');

      // Upload ảnh (nếu có chọn)
      $imgPath = save_uploaded_image('image', $ten) ?? '';

      // Document chuẩn trong MongoDB
      $doc = [
        'Tensanpham' => $ten,
        'Loaihang'   => $loai,
        'Giaban'     => $gia,
        'Tonkho'     => $tonkho,
        'Mota'       => $mota,
        'hinhanh'    => $imgPath, // lưu path ảnh
        'created_at' => new UTCDateTime(),
        'updated_at' => new UTCDateTime(),
      ];

      // Ghi vào DB
      $col->insertOne($doc);
      $msg = 'Đã thêm sản phẩm mới.';

    /* --- CẬP NHẬT SẢN PHẨM --- */
    } elseif ($action === 'update') {
      $id = trim((string)($_POST['id'] ?? ''));
      if ($id === '') throw new Exception('Thiếu ID sản phẩm.');

      // Lấy dữ liệu form
      $ten     = _f('name','s') ?? '';
      $loai    = _f('category','s') ?? '';
      $gia     = _f('price','f') ?? 0.0;
      $tonkho  = _f('stock','i') ?? 0;
      $mota    = _f('desc','s') ?? '';
      $imgPath = null; // sẽ set nếu upload ảnh mới

      // Validate
      if ($ten === '')   throw new Exception('Tên sản phẩm không được để trống.');
      if ($gia < 0)      throw new Exception('Giá không hợp lệ.');
      if ($tonkho < 0)   throw new Exception('Tồn kho không hợp lệ.');

      // Lấy doc cũ để xoá ảnh nếu cần
      $old = $col->findOne(['_id'=> new ObjectId($id)]);
      if (!$old) throw new Exception('Không tìm thấy sản phẩm để sửa.');

      // Nếu có upload ảnh mới thì lưu, xoá ảnh cũ
      $newImg = save_uploaded_image('image', $ten);
      if ($newImg !== null && $newImg !== '') {
        $imgPath = $newImg;
         $oldImg  = g($old, ['hinhanh','Hinhanh','image','hinhAnh','HinhAnh'], '');
        remove_local_image_if_exists($oldImg);
      }

      // Các field cần update
      $update = [
        'Tensanpham' => $ten,
        'Loaihang'   => $loai,
        'Giaban'     => $gia,
        'Tonkho'     => $tonkho,
        'Mota'       => $mota,
        'updated_at' => new UTCDateTime(),
      ];
      if ($imgPath !== null) $update['hinhanh'] = $imgPath;

      // Thực hiện update
      $col->updateOne(['_id'=> new ObjectId($id)], ['$set'=>$update]);
      $msg = 'Đã cập nhật sản phẩm.';

    /* --- XOÁ SẢN PHẨM --- */
    } elseif ($action === 'delete') {
      $id = trim((string)($_POST['id'] ?? ''));
      if ($id === '') throw new Exception('Thiếu ID sản phẩm để xoá.');

      // Lấy doc cũ để xoá luôn file ảnh trên server
      $old = $col->findOne(['_id'=> new ObjectId($id)]);
      if ($old) {
         $oldImg  = g($old, ['hinhanh','Hinhanh','image','hinhAnh','HinhAnh'], '');
        remove_local_image_if_exists($oldImg);
      }

      // Xoá document
      $col->deleteOne(['_id'=> new ObjectId($id)]);
      $msg = 'Đã xoá sản phẩm.';
    } else {
      // Không có action hợp lệ -> bỏ qua
    }
  } catch (Throwable $e) {
    // Bắt mọi lỗi và hiển thị ra trên giao diện
    $msg = 'Lỗi: '.$e->getMessage();
  }
}

/* ==== TRUY VẤN DANH SÁCH SẢN PHẨM ĐỂ HIỂN THỊ ==== */

// Từ khóa tìm kiếm
$q = trim((string)($_GET['q'] ?? ''));

// Ngưỡng "sắp hết hàng" (mặc định 0 = không lọc)
$lowParam = $_GET['low'] ?? '';
$lowThres = is_numeric($lowParam) ? (int)$lowParam : ($lowParam !== '' ? 5 : 0);

$cond = [];

// Tìm kiếm theo tên / loại / mô tả (dùng Regex không phân biệt hoa thường)
if ($q !== '') {
  $regex = new Regex($q, 'i');
  $cond['$or'] = [
    ['Tensanpham' => $regex],
    ['TenSanPham' => $regex],
    ['name'       => $regex],
    ['Loaihang'   => $regex],
    ['category'   => $regex],
    ['Mota'       => $regex],
    ['desc'       => $regex],
  ];
}

// Lọc sản phẩm sắp hết hàng (Tonkho / SoLuong / stock < ngưỡng)
if ($lowThres > 0) {
  $cond['$or'][] = ['Tonkho' => ['$lt' => $lowThres]];
  $cond['$or'][] = ['SoLuong'=> ['$lt' => $lowThres]];
  $cond['$or'][] = ['stock'  => ['$lt' => $lowThres]];
}

// Sắp xếp theo tên sản phẩm (ưu tiên Tensanpham)
$opts = ['sort' => ['Tensanpham'=>1, 'TenSanPham'=>1, 'name'=>1]];

// Lấy danh sách sản phẩm từ MongoDB
$cursor   = $cond ? $col->find($cond, $opts) : $col->find([], $opts);
$products = iterator_to_array($cursor, false);

// Định nghĩa BASE_URL nếu chưa có
if (!defined('BASE_URL')) define('BASE_URL', '/project-mongo');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý sản phẩm - Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- CSS chung -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin-product.css">
</head>
<body>

<?php include __DIR__ . '/partials/admin_topbar.php'; ?>

<div class="dvq-main">
  <div class="page dvq-container">

    <div class="flex-spread" style="margin-bottom: 16px;">
      <div>
        <h1><i class="fa-solid fa-boxes-stacked"></i> Quản lý sản phẩm</h1>
        <p class="sub">Thêm, chỉnh sửa thông tin sản phẩm &amp; theo dõi tồn kho.</p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/admin/product.php" class="btn ghost">
          <i class="fa-solid fa-rotate-right"></i> Tải lại
        </a>
      </div>
    </div>

    <!-- Hiển thị thông báo (thành công / lỗi) -->
    <?php if ($msg !== ''): ?>
      <div class="msg">
        <i class="fa-regular fa-circle-check"></i>
        <span><?= esc($msg) ?></span>
      </div>
    <?php endif; ?>

    <!-- KHỐI DANH SÁCH SẢN PHẨM + THANH TÌM KIẾM / LỌC -->
    <div class="card">
      <div class="flex-spread">
        <!-- Form tìm kiếm & lọc sản phẩm -->
        <form class="toolbar-form" method="get" action="">
          <input type="text" name="q" placeholder="Tìm theo tên, loại, mô tả..." value="<?= esc($q) ?>">
          <input type="number" name="low" min="0" placeholder="Lọc sắp hết hàng (<=)" value="<?= esc($lowThres ?: '') ?>">
          <button class="btn secondary" type="submit">
            <i class="fa-solid fa-filter"></i> Lọc
          </button>
          <a href="<?= BASE_URL ?>/admin/product.php" class="btn ghost">
            <i class="fa-solid fa-rotate-right"></i> Xoá lọc
          </a>
        </form>

        <!-- Nút scroll xuống form "Thêm sản phẩm mới" -->
        <button class="btn" type="button"
                onclick="document.getElementById('new-product').scrollIntoView({behavior:'smooth'})">
          <i class="fa-solid fa-plus"></i> Thêm sản phẩm mới
        </button>
      </div>

      <!-- Bảng danh sách sản phẩm -->
      <div class="mt-2">
        <table>
          <thead>
            <tr>
              <th>Ảnh</th>
              <th>Sản phẩm</th>
              <th class="text-right">Giá</th>
              <th class="text-center">Tồn kho</th>
              <th class="text-right col-actions">Thao tác</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$products): ?>
            <tr>
              <td colspan="5" class="text-center" style="padding:16px;">Chưa có sản phẩm nào.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $p):
              // Chuẩn hóa dữ liệu từng sản phẩm
              $id     = (string)($p['_id'] ?? '');
              $ten    = g($p, ['Tensanpham','TenSanPham','name'], '(Không tên)');
              $loai   = g($p, ['Loaihang','category'], 'Chưa phân loại');
              $gia    = (float)g($p, ['Giaban','GiaBan','price'], 0);
              $tonkho = (int)g($p, ['Tonkho','SoLuong','stock'], 0);
              $mota   = g($p, ['Mota','desc'], '');

              $img    = g($p, ['hinhanh','Hinhanh','image','hinhAnh','HinhAnh'], '');
              $imgUrl = img_url($img);

              // Badge hiển thị trạng thái tồn kho
              $badgeClass = $tonkho <= 0 ? 'low' : ($tonkho <= 5 ? 'low' : 'ok');
              $badgeIcon  = $tonkho <= 0 ? 'fa-circle-xmark' : ($tonkho <= 5 ? 'fa-triangle-exclamation' : 'fa-circle-check');
              $badgeText  = $tonkho <= 0 ? 'Hết hàng' : ($tonkho <= 5 ? 'Sắp hết' : 'Đủ hàng');
            ?>
            <tr class="prod-row">
              <!-- Cột ảnh -->
              <td>
                <div class="thumb">
                  <img src="<?= esc($imgUrl) ?>" alt="">
                </div>
              </td>

              <!-- Cột thông tin sản phẩm -->
              <td>
                <div class="prod-name"><?= esc($ten) ?></div>
                <div class="prod-meta">
                  <span><i class="fa-solid fa-tag"></i> <?= esc($loai) ?></span>
                </div>
                <?php if ($mota !== ''): ?>
                  <div class="prod-meta mt-1"><?= esc($mota) ?></div>
                <?php endif; ?>
              </td>

              <!-- Cột giá -->
              <td class="text-right">
                <?= number_format($gia, 0, ',', '.') ?> đ
              </td>

              <!-- Cột tồn kho + badge -->
              <td class="text-center">
                <div><?= $tonkho ?></div>
                <div class="badge <?= $badgeClass ?> mt-1">
                  <i class="fa-solid <?= $badgeIcon ?>"></i>
                  <span><?= $badgeText ?></span>
                </div>
              </td>

              <!-- Cột thao tác -->
              <td class="text-right">
                <!-- Nút mở form sửa chi tiết -->
                <button class="btn ghost" type="button" data-edit="<?= esc($id) ?>">
                  <i class="fa-regular fa-pen-to-square"></i> Sửa / Xem chi tiết
                </button>

                <!-- Form xoá sản phẩm -->
                <form method="post" style="display:inline" onsubmit="return confirm('Xoá sản phẩm này?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= esc($id) ?>">
                  <button class="btn danger" type="submit">
                    <i class="fa-regular fa-trash-can"></i> Xoá
                  </button>
                </form>

                <!-- FORM SỬA (ẩn / hiện bằng JS) -->
                <div class="pm-edit-box" id="edit-<?= esc($id) ?>">
                  <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= esc($id) ?>">

                    <div class="pm-edit-grid">
                      <div class="pm-field">
                        <label>Tên sản phẩm</label>
                        <input type="text" name="name" value="<?= esc($ten) ?>" required>
                      </div>
                      <div class="pm-field">
                        <label>Loại hàng</label>
                        <input type="text" name="category" value="<?= esc($loai) ?>">
                      </div>
                      <div class="pm-field">
                        <label>Giá bán (đ)</label>
                        <input type="number" step="1000" name="price" value="<?= esc((string)$gia) ?>">
                      </div>
                      <div class="pm-field">
                        <label>Tồn kho</label>
                        <input type="number" name="stock" value="<?= esc((string)$tonkho) ?>">
                      </div>
                      <div class="pm-field">
                        <label>Ảnh hiện tại</label>
                        <div class="thumb thumb-lg">
                          <img src="<?= esc($imgUrl) ?>" alt="">
                        </div>
                        <small class="prod-meta">Chọn ảnh mới để thay đổi (tối đa 5MB).</small>
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.gif">
                      </div>
                      <div class="pm-field">
                        <label>Mô tả</label>
                        <textarea name="desc"><?= esc($mota) ?></textarea>
                      </div>
                    </div>

                    <div class="mt-2 edit-actions">
                      <button class="btn ghost" type="button" data-edit-close="<?= esc($id) ?>">Đóng</button>
                      <button class="btn" type="submit">Lưu thay đổi</button>
                    </div>
                  </form>
                </div>

              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- FORM THÊM SẢN PHẨM MỚI -->
    <div class="card mt-3" id="new-product">
      <h2 class="section-title">
        <i class="fa-solid fa-plus-circle"></i> Thêm sản phẩm mới
      </h2>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">
        <div class="pm-edit-grid">
          <div class="pm-field">
            <label>Tên sản phẩm</label>
            <input type="text" name="name" required>
          </div>
          <div class="pm-field">
            <label>Loại hàng</label>
            <input type="text" name="category">
          </div>
          <div class="pm-field">
            <label>Giá bán (đ)</label>
            <input type="number" step="1000" name="price">
          </div>
          <div class="pm-field">
            <label>Tồn kho ban đầu</label>
            <input type="number" name="stock">
          </div>
          <div class="pm-field">
            <label>Ảnh sản phẩm</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.gif">
            <small class="prod-meta">Chọn ảnh rõ nét, tệp dưới 5MB.</small>
          </div>
          <div class="pm-field">
            <label>Mô tả</label>
            <textarea name="desc"></textarea>
          </div>
        </div>
        <div class="mt-2 text-right">
          <button class="btn" type="submit">
            <i class="fa-solid fa-check"></i> Thêm sản phẩm
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="container footer-content">
    <p>&copy; <?= date('Y') ?> Vô Ưu Quán – Vật phẩm Phật giáo. Sản phẩm cam kết hoàn toàn từ tự nhiên.</p>
    <ul class="footer-list">
      <li><i class="fa-solid fa-map-marker-alt fa-sm" aria-hidden="true"></i> 256 Nguyễn Văn Cừ - Phường An Hoà - Quận Ninh Kiều - TPCT, Can Tho, Vietnam</li>
      <li><i class="fa-solid fa-phone fa-sm" aria-hidden="true"></i> Hotline: <a href="tel:0389883981" style="color:#fff"> 0389 883 981</a></li>
      <li><i class="fa-solid fa-envelope fa-fm"></i> Email:<a href="mailto:vouuquanvn@gmail.com" style="color:#fff"> vouuquan@gmail.com</a></li>
    </ul>     
  </div>
</footer>

<!-- JS: toggle form sửa từng sản phẩm -->
<script>
  // Mở / đóng box sửa chi tiết
  document.querySelectorAll('[data-edit]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-edit');
      const box = document.getElementById('edit-' + id);
      if (!box) return;
      box.style.display = (box.style.display === 'none' || !box.style.display) ? 'block' : 'none';
    });
  });

  document.querySelectorAll('[data-edit-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-edit-close');
      const box = document.getElementById('edit-' + id);
      if (box) box.style.display = 'none';
    });
  });
</script>

<style>
  .footer-list {
    list-style: none;
    margin: 0;
  }
</style>

</body>
</html>