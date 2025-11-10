<?php
// BẮT BUỘC: nạp config để có BASE_URL/ADMIN_URL + session chung
require_once dirname(__DIR__, 2) . '/config.php';

// Nạp guard hiện có của bạn (đang nằm cùng thư mục partials)
require_once __DIR__ . '/guard.php';

$ADMIN_NAME = $_SESSION['auth']['username'] ?? 'Admin';
?>
<header class="navbar adminbar">
  <div class="nav-content">

    <!-- LOGO -->
    <a class="logo" href="<?= ADMIN_URL ?>/dashboard.php" aria-label="Trang Quản Trị">
      <img src="<?= BASE_URL ?>/images/VoUuQuan.svg" alt="Vô Ưu Quán">
    </a>

    <!-- MENU -->
    <nav class="menu">
      <a href="<?= ADMIN_URL ?>/dashboard.php">Tổng quan</a>
      <a href="<?= ADMIN_URL ?>/orders.php">Quản lý Đơn hàng</a>
      <a href="<?= ADMIN_URL ?>/products.php">Quản lý Sản phẩm</a>
      <a href="<?= ADMIN_URL ?>/customers.php">Quản lý Khách hàng</a>
      <a href="<?= ADMIN_URL ?>/content.php">Quản lý Nội dung</a>
    </nav>

    <!-- GÓC PHẢI -->
    <div class="account">
      <a href="<?= BASE_URL ?>/trangchu.php">Xem trang web</a>
      <span> | Xin chào, <b><?= htmlspecialchars($ADMIN_NAME) ?></b> | </span>
      <a href="<?= BASE_URL ?>/account/logout.php">Đăng xuất</a>
    </div>

  </div>
</header>
