<?php
// checkout.php – Trang thanh toán riêng cho Vô Ưu Quán

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Chỉ mở session, KHÔNG bắt đăng nhập
start_session_once();

$auth       = $_SESSION['auth'] ?? null;
$isLoggedIn = !empty($auth);

$isAdmin   = false;
$roleLabel = 'khách';
$nameLabel = 'Khách';

if ($isLoggedIn) {
    $rawRole  = strtolower((string)($auth['role'] ?? 'user'));
    $isAdmin  = ($rawRole === 'admin');
    $roleLabel = $isAdmin ? 'admin' : 'user';
    $nameLabel = $auth['name'] ?? ($auth['email'] ?? 'Người dùng');
}
/* ==== HÀM FORMAT VND ==== */
function format_vnd($n) {
    return number_format((float)$n, 0, ',', '.') . ' đ';
}

/* ==== LẤY GIỎ HÀNG TỪ SESSION ==== */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart =& $_SESSION['cart'];

$cart_count = 0;
$subtotal   = 0;
foreach ($cart as $item) {
    $cart_count += $item['qty'];
    $subtotal   += $item['gia'] * $item['qty'];
}

/* ==== XỬ LÝ FORM ĐẶT HÀNG ==== */
$orderSuccess = false;
$errors       = [];
$orderData    = [
    'ho_ten'     => '',
    'dien_thoai' => '',
    'dia_chi'    => '',
    'ghi_chu'    => ''
];
$orderItems   = [];
$orderTotal   = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderData['ho_ten']     = trim($_POST['ho_ten']     ?? '');
    $orderData['dien_thoai'] = trim($_POST['dien_thoai'] ?? '');
    $orderData['dia_chi']    = trim($_POST['dia_chi']    ?? '');
    $orderData['ghi_chu']    = trim($_POST['ghi_chu']    ?? '');

    if ($orderData['ho_ten'] === '') {
        $errors['ho_ten'] = 'Vui lòng nhập họ tên.';
    }
    if ($orderData['dien_thoai'] === '') {
        $errors['dien_thoai'] = 'Vui lòng nhập số điện thoại.';
    }
    if ($orderData['dia_chi'] === '') {
        $errors['dia_chi'] = 'Vui lòng nhập địa chỉ giao hàng.';
    }
    if ($cart_count === 0) {
        $errors['cart'] = 'Giỏ hàng đang trống, không thể thanh toán.';
    }

    // Nếu không có lỗi ⇒ đặt hàng thành công
    if (empty($errors)) {
        $orderSuccess = true;
        $orderItems   = $cart;
        $orderTotal   = $subtotal;

        // TODO: nếu muốn lưu đơn hàng vào MongoDB thì insert ở đây

        // Xóa giỏ hàng sau khi đặt
        $_SESSION['cart'] = [];
        $cart =& $_SESSION['cart'];
        $cart_count = 0;
        $subtotal   = 0;
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Thanh toán - Vô Ưu Quán</title>

  <link rel="stylesheet" href="/project-mongo/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body { background:#FDF3E2; margin:0; }
    .checkout-page {
      max-width:1000px;
      margin:30px auto 40px;
      padding:0 16px;
    }
    .checkout-grid {
      display:grid;
      grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);
      gap:20px;
    }
    .ck-box {
      background:#fff;
      border-radius:16px;
      box-shadow:0 4px 12px rgba(0,0,0,0.1);
      padding:18px 20px 22px;
    }
    .ck-title {
      font-size:22px;
      font-weight:700;
      color:#933838;
      margin-bottom:12px;
    }
    .ck-field { margin-bottom:10px; }
    .ck-field label {
      display:block;
      font-size:14px;
      margin-bottom:4px;
      color:#7B3E19;
    }
    .ck-field input,
    .ck-field textarea {
      width:100%;
      border-radius:10px;
      border:1px solid #e0d5c8;
      padding:8px 10px;
      font-size:14px;
      outline:none;
      font-family:inherit;
    }
    .ck-field textarea {
      resize:vertical;
      min-height:70px;
    }
    .ck-error {
      font-size:12px;
      color:#c0392b;
      margin-top:2px;
    }
    .ck-summary-item {
      display:flex;
      justify-content:space-between;
      font-size:14px;
      margin-bottom:6px;
    }
    .ck-summary-total {
      border-top:1px dashed #e0d5c8;
      margin-top:8px;
      padding-top:8px;
      font-weight:700;
      color:#933838;
      display:flex;
      justify-content:space-between;
    }
    .btn-primary,
    .btn-outline {
      border-radius:999px;
      padding:10px 22px;
      font-size:14px;
      font-weight:600;
      border:none;
      cursor:pointer;
      text-decoration:none;
      display:inline-block;
    }
    .btn-primary { background:#933838; color:#fff; }
    .btn-primary:hover { background:#7B3E19; }
    .btn-outline {
      background:#fff;
      color:#933838;
      border:1px solid #933838;
      margin-right:10px;
    }
    .success-box { text-align:center; }
    .success-icon {
      font-size:50px;
      color:#2ecc71;
      margin-bottom:10px;
    }
    .success-title {
      font-size:24px;
      font-weight:700;
      color:#933838;
      margin-bottom:8px;
      text-transform:uppercase;
    }
    .success-text {
      font-size:14px;
      color:#444;
      margin-bottom:16px;
    }
    .order-summary-table {
      width:100%;
      border-collapse:collapse;
      margin-top:10px;
      font-size:13px;
    }
    .order-summary-table th,
    .order-summary-table td {
      padding:6px 4px;
      border-bottom:1px solid #f1e3d6;
      text-align:left;
    }
    .order-summary-table th {
      background:#fef5ea;
      color:#7B3E19;
    }
    @media (max-width:768px){
      .checkout-grid{ grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

  <!-- HEADER giống các trang khác -->
  <header class="navbar">
    <div class="nav-content">
      <div class="logo">
        <img src="/project-mongo/images/VoUuQuan.svg" alt="Vô Ưu Quán Logo">
      </div>

      <nav class="menu">
        <a href="/project-mongo/trangchu.php">Trang Chủ</a>
        <a href="/project-mongo/trangchu.php#about">Giới Thiệu</a>
          <a href="/project-mongo/sanpham.php?loai=all">Sản Phẩm</a>
        <?php if (!$isAdmin): ?>
          <a href="/project-mongo/checkout.php">Thanh Toán</a>
        <?php endif; ?>
      </nav>

      <div class="account">
  <i class="fa-regular fa-user"></i>

  <?php if ($isLoggedIn): ?>
    <!-- ĐÃ ĐĂNG NHẬP -->
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
    <!-- CHƯA ĐĂNG NHẬP -->
    <a href="/project-mongo/account/login.php">Đăng nhập</a>
    &nbsp;|&nbsp;
    <a href="/project-mongo/account/register.php">Đăng ký</a>
  <?php endif; ?>

  <?php if (!$isAdmin): ?>
    &nbsp;|&nbsp;
    <a href="/project-mongo/cart.php" class="cart">
      <i class="fa-solid fa-cart-shopping"></i>
      <span class="cart-count"><?= $cart_count ?></span>
    </a>
  <?php endif; ?>
</div>
    </div>
  </header>

  <main class="page-main">
    <div class="checkout-page">

      <?php if ($orderSuccess): ?>

        <!-- ĐẶT HÀNG THÀNH CÔNG -->
        <div class="ck-box success-box">
          <div class="success-icon">
            <i class="fa-regular fa-circle-check"></i>
          </div>
          <div class="success-title">ĐẶT HÀNG THÀNH CÔNG</div>
          <div class="success-text">
            Cảm ơn <?= htmlspecialchars($orderData['ho_ten'], ENT_QUOTES, 'UTF-8') ?> đã tin tưởng Vô Ưu Quán.<br>
            Chúng tôi sẽ liên hệ xác nhận đơn hàng và giao đến địa chỉ
            <strong><?= htmlspecialchars($orderData['dia_chi'], ENT_QUOTES, 'UTF-8') ?></strong><br>
            SĐT: <strong><?= htmlspecialchars($orderData['dien_thoai'], ENT_QUOTES, 'UTF-8') ?></strong>.
          </div>

          <?php if (!empty($orderItems)): ?>
            <h3 style="margin-top:10px;color:#7B3E19;">Tóm tắt đơn hàng</h3>
            <table class="order-summary-table">
              <thead>
                <tr>
                  <th>Sản phẩm</th>
                  <th>SL</th>
                  <th>Đơn giá</th>
                  <th>Thành tiền</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($orderItems as $it): 
                    $lineTotal = $it['gia'] * $it['qty'];
              ?>
                <tr>
                  <td><?= htmlspecialchars($it['ten'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= (int)$it['qty'] ?></td>
                  <td><?= format_vnd($it['gia']) ?></td>
                  <td><?= format_vnd($lineTotal) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <p style="margin-top:8px;font-weight:600;">
              Tổng tiền: <?= format_vnd($orderTotal) ?>
            </p>
          <?php endif; ?>

          <div style="margin-top:18px;">
            <a href="/project-mongo/sanpham.php?loai=all" class="btn-primary">Tiếp tục mua sắm</a>
          </div>
        </div>

      <?php else: ?>

        <?php if ($cart_count === 0): ?>
          <!-- GIỎ TRỐNG -->
          <div class="ck-box">
            <p>Giỏ hàng đang trống, không thể thanh toán.</p>
            <a href="/project-mongo/sanpham.php?loai=all" class="btn-primary">Quay lại mua sắm</a>
          </div>
        <?php else: ?>

          <!-- FORM + TÓM TẮT ĐƠN HÀNG -->
          <div class="checkout-grid">
            <!-- CỘT TRÁI: FORM -->
            <div class="ck-box">
              <div class="ck-title">Thông tin thanh toán</div>

              <form method="post" action="checkout.php">
                <div class="ck-field">
                  <label for="ho_ten">Họ và tên *</label>
                  <input type="text" id="ho_ten" name="ho_ten"
                         value="<?= htmlspecialchars($orderData['ho_ten'], ENT_QUOTES, 'UTF-8') ?>">
                  <?php if (isset($errors['ho_ten'])): ?>
                    <div class="ck-error"><?= htmlspecialchars($errors['ho_ten']) ?></div>
                  <?php endif; ?>
                </div>

                <div class="ck-field">
                  <label for="dien_thoai">Số điện thoại *</label>
                  <input type="text" id="dien_thoai" name="dien_thoai"
                         value="<?= htmlspecialchars($orderData['dien_thoai'], ENT_QUOTES, 'UTF-8') ?>">
                  <?php if (isset($errors['dien_thoai'])): ?>
                    <div class="ck-error"><?= htmlspecialchars($errors['dien_thoai']) ?></div>
                  <?php endif; ?>
                </div>

                <div class="ck-field">
                  <label for="dia_chi">Địa chỉ giao hàng *</label>
                  <input type="text" id="dia_chi" name="dia_chi"
                         value="<?= htmlspecialchars($orderData['dia_chi'], ENT_QUOTES, 'UTF-8') ?>">
                  <?php if (isset($errors['dia_chi'])): ?>
                    <div class="ck-error"><?= htmlspecialchars($errors['dia_chi']) ?></div>
                  <?php endif; ?>
                </div>

                <div class="ck-field">
                  <label for="ghi_chu">Ghi chú thêm (nếu có)</label>
                  <textarea id="ghi_chu" name="ghi_chu"><?= htmlspecialchars($orderData['ghi_chu'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div style="margin-top:14px;">
                  <a href="cart.php" class="btn-outline">Quay lại giỏ hàng</a>
                  <button type="submit" class="btn-primary">Đặt hàng</button>
                </div>
              </form>
            </div>

            <!-- CỘT PHẢI: TÓM TẮT GIỎ HÀNG -->
            <div class="ck-box">
              <div class="ck-title">Đơn hàng của bạn</div>
              <?php foreach ($cart as $it): 
                    $lineTotal = $it['gia'] * $it['qty'];
              ?>
                <div class="ck-summary-item">
                  <span>
                    <?= htmlspecialchars($it['ten'], ENT_QUOTES, 'UTF-8') ?>
                    × <?= (int)$it['qty'] ?>
                  </span>
                  <span><?= format_vnd($lineTotal) ?></span>
                </div>
              <?php endforeach; ?>

              <div class="ck-summary-total">
                <span>Tổng thanh toán</span>
                <span><?= format_vnd($subtotal) ?></span>
              </div>
            </div>
          </div>

        <?php endif; ?>

      <?php endif; ?>

    </div>
  </main>

   <footer class ="site-footer">
    <div class="container footer-content">
      <p>&copy; <?= date('Y') ?> Vô Ưu Quán – Vật phẩm Phật giáo. Sản phẩm cam kết hoàn toàn từ tự nhiên.</p>
    <ul class = "footer-list">
      
      <li><i class="fa-solid fa-map-marker-alt fa-sm" aria-hidden="true"></i>  256 Nguyễn Văn Cừ - Phường An Hoà - Quận Ninh Kiều - TPCT, Can Tho, Vietnam</li>
      <li><i class="fa-solid fa-phone fa-sm" aria-hidden="true"></i> Hotline: <a href="tel:0389883981" style="color:#fff"> 0389 883 981</a></li>
      <li><i class="fa-solid fa-envelope fa-fm"></i> Email:<a href ="mailto:vouuquanvn@gmail.com" style="color:#fff"> vouuquan@gmail.com</a></li>
  </ul>     
    </div>
  </footer>
<style>
.footer-list{
  list-style: none;   /* bỏ bullet */
  margin: 0;          /* gọn khoảng cách */
}
</style>
</body>
</html>
