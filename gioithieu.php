<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
if (!defined('BASE_URL')) {
    define('BASE_URL', '/project-mongo');
}
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
?>
<!-- HTML GIỚI THIỆU -->
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Giới Thiệu - Vô Ưu Quán</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/gioithieu.css">
  <!-- Font + Icon -->
  <link rel="stylesheet" href="/project-mongo/assets/vendor/fontawesome/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

<header class="navbar">
  <div class="nav-content">
    <!-- Logo -->
    <div class="logo">
      <img src="<?= BASE_URL ?>/images/VoUuQuan.svg" alt="Vô Ưu Quán Logo">
    </div>
    <!-- Menu -->
    <nav class="menu">
      <a href="<?= BASE_URL ?>/trangchu.php">Trang Chủ</a>
      <a href="<?= BASE_URL ?>/gioithieu.php" class="active">Giới Thiệu</a>
      <a href="<?= BASE_URL ?>/sanpham.php">Sản Phẩm</a>
      <?php if (!$isAdmin): ?>
        <a href="<?= BASE_URL ?>/#checkout">Thanh Toán</a>
      <?php endif; ?>
    </nav>
    <!-- Tài khoản + Giỏ hàng -->
    <div class="account">
      <i class="fa-regular fa-user"></i>

      <?php if ($isLoggedIn): ?>
        <!-- ĐÃ ĐĂNG NHẬP: hiện vai trò + xin chào -->
        Vai trò:
        <strong><?= htmlspecialchars(ucfirst($roleLabel)) ?></strong>
        &nbsp;|&nbsp;
        Xin chào, <strong><?= htmlspecialchars($nameLabel) ?></strong>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>/logout.php">Đăng xuất</a>

        <?php if ($isAdmin): ?>
          &nbsp;|&nbsp;
          <a href="<?= BASE_URL ?>/admin/dashboard.php">Khu vực Admin</a>
        <?php endif; ?>

      <?php else: ?>
        <!-- CHƯA ĐĂNG NHẬP: chỉ hiện Đăng nhập / Đăng ký -->
        <a href="<?= BASE_URL ?>/account/login.php">Đăng nhập</a>
        &nbsp;|&nbsp;
        <a href="<?= BASE_URL ?>/account/register.php">Đăng ký</a>
      <?php endif; ?>

      &nbsp;|&nbsp;
      <span class="cart">
        <i class="fa-solid fa-cart-shopping"></i> 0
      </span>
    </div>
  </div>
</header>


<main class="page">
  <h1 class="section-title">Chào mừng bạn đến với Vô Ưu Quán</h1>
  <hr class="hr-soft">

  <!-- Ảnh ngay dưới tiêu đề -->
  <img class="hero-banner" src="<?= BASE_URL ?>/images/product/gioithieu.png" alt="Vật phẩm Phật giáo tại Vô Ưu Quán">

  <!-- Bố cục 2 cột: Trái (Giới thiệu + Liên hệ) | Phải (Sứ mệnh + Tầm nhìn) -->
  <section class="two-col container">

    <!-- LEFT -->
    <div>
      <h2 class="section-h2">Về Vô Ưu Quán – Vật Phẩm Phật Giáo</h2>
      <p>
        Vô Ưu Quán được hình thành với tâm nguyện lan tỏa năng lượng lành,
        đưa các vật phẩm chánh niệm và an tịnh đến với quý Phật tử, người hành trì
        và bất kỳ ai đang tìm lại sự bình an nơi nội tâm.
      </p>
      <ul class="list-clean">
        <li><strong>Chuỗi Vòng – Chuỗi hạt niệm Phật</strong>: bạn đồng hành trên con đường hành trì, gìn giữ niệm lành.</li>
        <li><strong>Tượng Phật &amp; tượng Phật mini</strong>: tôn nghiêm và gần gũi, thích hợp đặt tại bàn làm việc hay trong xe.</li>
        <li><strong>Lư xông trầm/nhang</strong>: lan tỏa hương thơm thanh tịnh, gột rửa muộn phiền.</li>
        <li><strong>Dây chuyền phong thủy</strong>: tinh tế, giàu ý nghĩa, dễ phối hợp nhiều phong cách.</li>
        <li><strong>Móc khóa phong thủy</strong>: nhỏ nhắn mà mang theo năng lượng hộ thân, an lành.</li>
      </ul>

      <h3 class="section-h3" style="margin-top:16px">Sản phẩm nổi bật</h3>
      <ul class="list-clean">
        <li>Chuỗi vòng tay đá quý &amp; ngọc trai: hộ thân, may mắn, thanh tịnh trong từng hạt.</li>
        <li>Móc khóa phong thủy hoàng kim: thu hút tài lộc, bình an mọi nơi bạn đến.</li>
        <li>Lư xông trầm hoa sen đồng: tạo không gian trang nghiêm, nuôi dưỡng tuệ giác.</li>
      </ul>

      <!-- Liên hệ -->
      <div class="contact contact-card">
        <p><i class="fa-solid fa-location-dot"></i> 256 Nguyễn Văn Cừ – Phường An Hoà – Quận Ninh Kiều – TPCT, Cần Thơ, Việt Nam</p>
        <p><i class="fa-solid fa-phone"></i> <a href="tel:0389883981">0389 883 981</a></p>
        <p><i class="fa-solid fa-envelope"></i> <a href="mailto:vouuquan@gmail.com">vouuquan@gmail.com</a></p>
      </div>
    </div>

    <!-- RIGHT -->
    <aside>
      <div class="side-section">
        <h3 class="section-h3">Sứ mệnh</h3>
        <ul class="list-clean">
          <li>Mang đến những vật phẩm <strong>đúng pháp – đúng chất</strong>, nguồn gốc rõ ràng, bền đẹp.</li>
          <li>Lan tỏa giá trị từ bi, chánh niệm và tĩnh thức qua từng sản phẩm.</li>
          <li>Đồng hành kiến tạo không gian sống <em>an tịnh – ấm áp</em> cho mọi gia đình.</li>
        </ul>
      </div>
      <div class="side-section">
        <h3 class="section-h3">Tầm nhìn</h3>
        <ul class="list-clean">
          <li>Trở thành địa chỉ tin cậy về vật phẩm Phật giáo uy tín tại Cần Thơ và khu vực ĐBSCL.</li>
          <li>Xây dựng cộng đồng người dùng nuôi dưỡng <strong>từ bi – trí tuệ – chánh niệm</strong>.</li>
          <li>Ứng dụng văn hoá Phật giáo trong đời sống hiện đại với trải nghiệm mua sắm ấm áp, thuận tiện.</li>
        </ul>
      </div>
    </aside>

  </section>
</main>
<footer class="site-footer">
  <div class="container footer-content" style="text-align:center;color:#fff;padding:20px 0">
    <p>&copy; <?= date('Y') ?> Vô Ưu Quán – Vật phẩm Phật giáo. Sản phẩm cam kết hoàn toàn từ tự nhiên.</p>
    <ul class="footer-list" style="list-style:none;margin:0;padding:0">
      <li><i class="fa-solid fa-location-dot fa-sm" aria-hidden="true"></i> 256 Nguyễn Văn Cừ - Phường An Hoà - Quận Ninh Kiều - TPCT, Cần Thơ, Việt Nam</li>
      <li><i class="fa-solid fa-phone fa-sm" aria-hidden="true"></i> Hotline: <a href="tel:0389883981" style="color:#fff">0389 883 981</a></li>
      <li><i class="fa-solid fa-envelope fa-sm" aria-hidden="true"></i> Email: <a href="mailto:vouuquan@gmail.com" style="color:#fff">vouuquan@gmail.com</a></li>
    </ul>
  </div>
</footer>
</body>
</html>
