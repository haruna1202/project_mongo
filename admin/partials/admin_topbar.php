<?php
// admin/partials/admin_topbar.php  — dùng chung layout với trang chủ
require_once __DIR__.'/guard.php';
$BASE = '/project-mongo';
?>
<header class="navbar adminbar">
  <div class="nav-content">
    <!-- LOGO: dùng cùng class .logo như trang chủ -->
    <a class="logo" href="<?=$BASE?>/admin/dashboard.php" aria-label="Trang Quản Trị">
      <img src="<?=$BASE?>/images/VoUuQuan.svg" alt="Vô Ưu Quán">
    </a>

    <!-- MENU: giữ class .menu, chỉ đổi item sang Admin -->
    <nav class="menu">
      <a href="<?=$BASE?>/admin/dashboard.php">Tổng quan</a>
      <a href="<?=$BASE?>/admin/orders.php">Quản lý Đơn hàng</a>
      <a href="<?=$BASE?>/admin/products.php">Quản lý Sản phẩm</a>
      <a href="<?=$BASE?>/admin/customers.php">Quản lý Khách hàng</a>
      <a href="<?=$BASE?>/admin/content.php">Quản lý Nội dung</a>
    </nav>

    <!-- GÓC PHẢI: giữ class .account -->
    <div class="account">
      <a href="<?=$BASE?>/VoUuQuan.php">Xem trang web</a>
      <span> | Xin chào, <b><?=htmlspecialchars($ADMIN_NAME)?></b> | </span>
      <a href="<?=$BASE?>/account/logout.php">Đăng xuất</a>
    </div>
  </div>
</header>
