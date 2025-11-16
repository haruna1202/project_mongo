<?php
// cart.php – Trang giỏ hàng Vô Ưu Quán

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== BIẾN HEADER MẶC ĐỊNH (giống product_detail.php) ===== */
if (!isset($isAdmin)) $isAdmin = false;
if (!isset($role))    $role    = 'guest';
if (!isset($name))    $name    = '';

/* ===== HÀM DÙNG CHUNG ===== */
function format_vnd($n) {
    return number_format((float)$n, 0, ',', '.') . ' đ';
}

/* ===== LẤY GIỎ HÀNG TỪ SESSION ===== */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart =& $_SESSION['cart'];

/* ===== XỬ LÝ CÁC HÀNH ĐỘNG: inc, dec, remove, clear, update ===== */
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id     = $_GET['id']     ?? $_POST['id']     ?? '';

switch ($action) {
    case 'inc':   // cộng 1
        if ($id !== '' && isset($cart[$id])) {
            $cart[$id]['qty']++;
        }
        break;

    case 'dec':   // trừ 1 (<=0 thì xóa luôn)
        if ($id !== '' && isset($cart[$id])) {
            $cart[$id]['qty']--;
            if ($cart[$id]['qty'] <= 0) {
                unset($cart[$id]);
            }
        }
        break;

    case 'remove':    // xóa 1 sản phẩm
        if ($id !== '' && isset($cart[$id])) {
            unset($cart[$id]);
        }
        break;

    case 'clear':     // xóa sạch giỏ
        $cart = [];
        break;

    case 'update':    // cập nhật số lượng từ input
        if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
            foreach ($_POST['qty'] as $pid => $q) {
                $q = (int)$q;
                if ($q <= 0) {
                    unset($cart[$pid]);
                } else {
                    if (isset($cart[$pid])) {
                        $cart[$pid]['qty'] = $q;
                    }
                }
            }
        }
        break;
}

/* ===== TÍNH TỔNG SỐ LƯỢNG VÀ TỔNG TIỀN ===== */
$cart_count = 0;
$subtotal   = 0;

foreach ($cart as $item) {
    $cart_count += $item['qty'];
    $subtotal   += $item['gia'] * $item['qty'];
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Giỏ hàng - Vô Ưu Quán</title>

  <!-- CSS CHUNG -->
  <link rel="stylesheet" href="/project-mongo/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- CSS RIÊNG CHO TRANG GIỎ HÀNG -->
  <style>
    body {
      background: #FDF3E2;
      margin: 0;
    }
    .cart-page {
      max-width: 1000px;
      margin: 30px auto 40px;
      padding: 0 16px;
    }
    .cart-box {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    .cart-header {
      padding: 16px 20px;
      border-bottom: 1px solid #eee0d2;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .cart-header h1 {
      margin: 0;
      font-size: 22px;
      color: #933838;
    }
    .cart-clear {
      color: #c0392b;
      text-decoration: none;
      font-size: 14px;
    }
    .cart-clear:hover {
      text-decoration: underline;
    }
    table.cart-table {
      width: 100%;
      border-collapse: collapse;
    }
    table.cart-table th,
    table.cart-table td {
      padding: 12px 10px;
      border-bottom: 1px solid #f1e3d6;
      font-size: 14px;
      text-align: left;
      vertical-align: middle;
    }
    table.cart-table th {
      background: #fef5ea;
      color: #7B3E19;
      font-weight: 600;
    }
    .cart-thumb {
      width: 70px;
      border-radius: 8px;
      object-fit: cover;
    }
    .qty-box {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      border: 1px solid #e0d5c8;
      padding: 3px 8px;
      background: #fff;
      gap: 4px;
    }
    .qty-box a {
      text-decoration: none;
      font-size: 16px;
      padding: 0 6px;
      color: #933838;
    }
    .qty-box input {
      width: 40px;
      border: none;
      text-align: center;
      font-size: 14px;
      outline: none;
    }
    .cart-remove {
      color: #c0392b;
      text-decoration: none;
      font-size: 14px;
    }
    .cart-remove:hover {
      text-decoration: underline;
    }
    .cart-footer {
      padding: 14px 20px 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .cart-total {
      font-size: 16px;
      font-weight: 600;
      color: #7B3E19;
    }
    .btn-primary,
    .btn-outline {
      border-radius: 999px;
      padding: 9px 20px;
      font-size: 14px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }
    .btn-primary {
      background: #933838;
      color: #fff;
    }
    .btn-primary:hover {
      background: #7B3E19;
    }
    .btn-outline {
      background: #fff;
      color: #933838;
      border: 1px solid #933838;
      margin-right: 10px;
    }
    .empty-cart {
      padding: 20px;
      font-size: 14px;
    }
  </style>
</head>
<body>

  <!-- HEADER GIỐNG product_detail.php -->
  <header class="navbar">
    <div class="nav-content">
      <div class="logo">
        <img src="/project-mongo/images/VoUuQuan.svg" alt="Vô Ưu Quán Logo">
      </div>

      <nav class="menu">
        <a href="/project-mongo/trangchu.php">Trang Chủ</a>
        <a href="/project-mongo/trangchu.php#about">Giới Thiệu</a>
        <a href="/project-mongo/products.php?loai=all">Sản Phẩm</a>
        <?php if (!$isAdmin): ?>
          <a href="/project-mongo/trangchu.php#checkout">Thanh Toán</a>
        <?php endif; ?>
      </nav>

      <div class="account">
        <i class="fa-regular fa-user"></i>
        Vai trò: <strong><?= htmlspecialchars($role) ?></strong>

        <?php if ($isAdmin): ?>
          &nbsp;|&nbsp;<a href="/project-mongo/admin/dashboard.php">Khu vực Admin</a>
        <?php endif; ?>

        <?php if ($role === 'guest'): ?>
          &nbsp;|&nbsp;<a href="/project-mongo/account/login.php">Đăng nhập</a>
        <?php else: ?>
          &nbsp;|&nbsp;Xin chào, <strong><?= htmlspecialchars($name) ?></strong>
          &nbsp;|&nbsp;<a href="/project-mongo/logout.php">Đăng xuất</a>
        <?php endif; ?>

        <?php if (!$isAdmin): ?>
          <span class="cart">
            <i class="fa-solid fa-cart-shopping"></i> <?= $cart_count ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="page-main">
    <div class="cart-page">
      <div class="cart-box">
        <div class="cart-header">
          <h1>Giỏ hàng của bạn (<?= $cart_count ?>)</h1>
          <?php if ($cart_count > 0): ?>
            <a href="cart.php?action=clear"
               class="cart-clear"
               onclick="return confirm('Xóa toàn bộ giỏ hàng?');">
              <i class="fa-regular fa-trash-can"></i> Xóa giỏ hàng
            </a>
          <?php endif; ?>
        </div>

        <?php if ($cart_count == 0): ?>
          <div class="empty-cart">
            Giỏ hàng đang trống. <a href="sanpham.php">Tiếp tục mua sắm</a>.
          </div>
        <?php else: ?>

        <!-- form để cập nhật số lượng từ input -->
        <form method="post" action="cart.php">
          <input type="hidden" name="action" value="update">

          <table class="cart-table">
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cart as $pid => $item):
                    $lineTotal = $item['gia'] * $item['qty'];
              ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <?php if (!empty($item['hinh'])): ?>
                      <img src="<?= htmlspecialchars($item['hinh'], ENT_QUOTES, 'UTF-8') ?>"
                           class="cart-thumb" alt="">
                    <?php endif; ?>
                    <span><?= htmlspecialchars($item['ten'], ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                </td>
                <td><?= format_vnd($item['gia']); ?></td>
                <td>
                  <!-- CỘNG / TRỪ TỪNG SẢN PHẨM -->
                  <div class="qty-box">
                    <a href="cart.php?action=dec&id=<?= urlencode($pid) ?>">-</a>
                    <input type="text"
                           name="qty[<?= htmlspecialchars($pid, ENT_QUOTES, 'UTF-8') ?>]"
                           value="<?= (int)$item['qty'] ?>">
                    <a href="cart.php?action=inc&id=<?= urlencode($pid) ?>">+</a>
                  </div>
                </td>
                <td><?= format_vnd($lineTotal); ?></td>
                <td>
                  <a href="cart.php?action=remove&id=<?= urlencode($pid) ?>"
                     class="cart-remove"
                     onclick="return confirm('Xóa sản phẩm này khỏi giỏ?');">
                    Xóa
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="cart-footer">
            <div class="cart-total">
              Tổng cộng: <?= format_vnd($subtotal); ?>
            </div>
            <div>
              <button type="submit" class="btn-outline">Cập nhật giỏ hàng</button>
              <a href="checkout.php" class="btn-primary">Tiến hành thanh toán</a>
            </div>
          </div>
        </form>

        <?php endif; ?>
      </div>
    </div>
  </main>

  <footer class="footer">
    <div class="footer-inner">
      © 2025 Vô Ưu Quán – Vật phẩm Phật giáo. Sản phẩm cam kết hoàn toàn từ tự nhiên.
      <br>256 Nguyễn Văn Cừ - Phường An Hoà - Quận Ninh Kiều - TPCT, Cần Thơ, Vietnam
      <br>Hotline: 0389 883 981
      <br>Email: vouuquan@gmail.com
    </div>
  </footer>

</body>
</html>
