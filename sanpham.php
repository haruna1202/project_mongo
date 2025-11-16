<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';       
require_once __DIR__ . '/includes/db_connect.php'; 
start_session_once();

$auth       = $_SESSION['auth'] ?? null;
$isLoggedIn = !empty($auth);

$isAdmin   = false;
$roleLabel = 'khách';
$nameLabel = 'Khách';

if ($isLoggedIn) {
    $rawRole   = strtolower((string)($auth['role'] ?? 'user'));
    $isAdmin   = ($rawRole === 'admin');
    $roleLabel = $isAdmin ? 'admin' : 'user';
    $nameLabel = $auth['name'] ?? ($auth['email'] ?? 'Người dùng');
}


$boSanPham = $db->selectCollection('sanpham');


function chuyen_html($chuoi): string {
    return htmlspecialchars((string)$chuoi, ENT_QUOTES, 'UTF-8');
}

function dinh_dang_vnd($soTien): string {
    if ($soTien === '' || $soTien === null) {
        $soTien = 0;
    }
    if (is_string($soTien)) {
        $soTien = preg_replace('/[^\d.]/', '', $soTien);
        $soTien = $soTien === '' ? 0 : (float)$soTien;
    }
    return number_format((float)$soTien, 0, ',', '.') . ' đ';
}

// Xử lý đường dẫn hình lấy từ Mongo
function duong_dan_anh($giaTri): string {
    $path = (string)$giaTri;
    $path = trim($path);
    $path = trim($path, "\"'");
    $path = str_replace('\\', '/', $path);

    if ($path === '') {
        return '/project-mongo/images/VoUuQuan.svg';
    }

    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    if (strpos($path, '/project-mongo/') === 0) {
        return $path;
    }

    if (strpos($path, 'images/') === 0) {
        return '/project-mongo/' . $path;
    }

    if (strpos($path, '/') === false) {
        return '/project-mongo/images/categories/' . $path;
    }

    return '/project-mongo/' . ltrim($path, '/');
}

/* ==== LẤY DANH MỤC TỪ MONGODB ==== */
$dsLoaiDb = $boSanPham->distinct('Loaihang');
if (!is_array($dsLoaiDb)) {
    $dsLoaiDb = iterator_to_array($dsLoaiDb, false);
}
sort($dsLoaiDb);

// Danh mục đang chọn trên URL (?loai=...)
$loaiChon     = $_GET['loai'] ?? 'all';
$filter       = [];
$tieuDeTrang  = 'Tất cả sản phẩm phong thủy';

if ($loaiChon !== 'all') {
    $filter      = ['Loaihang' => $loaiChon];
    $tieuDeTrang = $loaiChon;
}

$cursorSanPham = $boSanPham->find($filter);
$dsSanPham     = iterator_to_array($cursorSanPham, false);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= chuyen_html($tieuDeTrang) ?> | Vô Ưu Quán</title>

  <!-- CSS CHUNG + ICON -->
  <link rel="stylesheet" href="/project-mongo/css/style.css">
  <link rel="stylesheet" href="/project-mongo/css/sanpham.css">
  <link rel="stylesheet" href="/project-mongo/assets/vendor/fontawesome/css/all.min.css">
</head>

<body>
  <!-- HEADER (GIỐNG TRANGCHU, CHỈ SỬA LINK SẢN PHẨM / THANH TOÁN) -->
  <header class="navbar">
    <div class="nav-content">
      <div class="logo">
        <img src="/project-mongo/images/VoUuQuan.svg" alt="Vô Ưu Quán Logo">
      </div>

      <nav class="menu">
        <a href="/project-mongo/trangchu.php">Trang Chủ</a>
        <a href="/project-mongo/gioithieu.php">Giới Thiệu</a>
        <a href="/project-mongo/sanpham.php?loai=all" class="active">Sản Phẩm</a>
        <?php if (!$isAdmin): ?>
          <a href="/project-mongo/checkout.php">Thanh Toán</a>
        <?php endif; ?>
      </nav>

      <div class="account">
        <i class="fa-regular fa-user"></i>

        <?php if ($isLoggedIn): ?>
          Vai trò:
          <strong><?= htmlspecialchars(ucfirst($roleLabel)) ?></strong>
          &nbsp;|&nbsp;
          Xin chào, <strong><?= htmlspecialchars($nameLabel) ?></strong>
          <?php if ($isAdmin): ?>
            &nbsp;|&nbsp;
            <a href="/project-mongo/admin/dashboard.php">Khu vực Admin</a>
          <?php endif; ?>
          &nbsp;|&nbsp;
          <a href="/project-mongo/logout.php">Đăng xuất</a>
        <?php else: ?>
          <a href="/project-mongo/account/login.php">Đăng nhập</a>
          &nbsp;|&nbsp;
          <a href="/project-mongo/account/register.php">Đăng ký</a>
        <?php endif; ?>

        <?php if (!$isAdmin): ?>
          &nbsp;|&nbsp;
          <span class="cart">
            <i class="fa-solid fa-cart-shopping"></i> 0
          </span>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- NỘI DUNG TRANG DANH SÁCH SẢN PHẨM -->
  <main class="sanpham-page">
    <div style="margin-bottom: 10px;">

      </a>
    </div>

    <div class="sanpham-layout">
      <!-- SIDEBAR DANH MỤC -->
      <aside class="box-sidebar">
        <h2>Danh mục sản phẩm</h2>
        <p class="mota">
          Lọc theo loại sản phẩm bạn quan tâm: Vòng tay, Dây chuyền, Tượng, Lư hương, Móc khóa...
        </p>
        <ul class="menu-loai">
          <li>
            <a href="/project-mongo/sanpham.php?loai=all"
               class="<?= ($loaiChon === 'all') ? 'active' : '' ?>">
              Tất cả sản phẩm
            </a>
          </li>

          <?php foreach ($dsLoaiDb as $tenLoai): ?>
            <?php
              $tenLoaiStr = (string)$tenLoai;
              $active     = ($loaiChon === $tenLoaiStr) ? 'active' : '';
            ?>
            <li>
              <a href="/project-mongo/sanpham.php?loai=<?= urlencode($tenLoaiStr) ?>"
                 class="<?= $active ?>">
                <?= chuyen_html($tenLoaiStr) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>

      <!-- KHU VỰC SẢN PHẨM -->
      <section>
        <div class="sanpham-main-header">
          <h1 class="sanpham-title"><?= chuyen_html($tieuDeTrang) ?></h1>
          <p class="sanpham-subtitle">
            Các sản phẩm phong thủy, vật phẩm Phật giáo được chọn lọc kỹ lưỡng tại Vô Ưu Quán.
          </p>
        </div>

        <?php if (empty($dsSanPham)): ?>
          <p class="khong-co-sp">Hiện chưa có sản phẩm nào thuộc danh mục này.</p>
        <?php else: ?>
          <div class="sanpham-grid">
            <?php foreach ($dsSanPham as $sp): ?>
              <?php
                $ten  = $sp['Tensanpham'] ?? 'Sản phẩm không tên';
                $gia  = $sp['Giaban'] ?? 0;
                // ƯU TIÊN LẤY "hinhanh" (chữ thường) TỪ DB
                $hinh = $sp['hinhanh'] ?? ($sp['Hinhanh'] ?? '');
                $id   = $sp['_id'] ?? null;
                $idStr = ($id !== null) ? (string)$id : '';
              ?>
              <article class="sp-card">
                <img src="<?= chuyen_html(duong_dan_anh($hinh)) ?>"
                     alt="<?= chuyen_html($ten) ?>">
                <h3 class="sp-ten"><?= chuyen_html($ten) ?></h3>
                <div class="sp-gia"><?= dinh_dang_vnd($gia) ?></div>
                <?php if ($idStr !== ''): ?>
                  <a class="nut-xem" href="/project-mongo/chitietsanpham.php?id=<?= urlencode($idStr) ?>">
                    <i class="fa-solid fa-eye"></i>
                    Xem chi tiết
                  </a>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container footer-content">
      <p>&copy; <?= date('Y') ?> Vô Ưu Quán – Vật phẩm Phật giáo. Sản phẩm cam kết hoàn toàn từ tự nhiên.</p>
      <ul class="footer-list">
        <li>
          <i class="fa-solid fa-map-marker-alt fa-sm" aria-hidden="true"></i>
          Địa chỉ: 256 Nguyễn Văn Cừ - Phường An Hoà - Quận Ninh Kiều - TPCT, Cần Thơ, Vietnam
        </li>
        <li>
          <i class="fa-solid fa-phone fa-sm" aria-hidden="true"></i>
          Hotline:
          <a href="tel:0389883981">0389 883 981</a>
        </li>
        <li>
          <i class="fa-solid fa-envelope fa-sm" aria-hidden="true"></i>
          Email:
          <a href="mailto:vouuquanvn@gmail.com">vouuquan@gmail.com</a>
        </li>
      </ul>
    </div>
  </footer>
</body>
</html>
<style>
.footer-list{
  list-style: none;   /* bỏ bullet */
  margin: 0;          /* gọn khoảng cách */
}
</style>