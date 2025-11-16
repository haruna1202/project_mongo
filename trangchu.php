<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

// Chỉ mở session, KHÔNG bắt đăng nhập
start_session_once();

$auth = $_SESSION['auth'] ?? null;
$isLoggedIn = !empty($auth);


$isAdmin    = false;
$roleLabel  = 'khách';
$nameLabel  = 'Khách';


if ($isLoggedIn) {
    $rawRole = strtolower((string)($auth['role'] ?? 'user'));
    $isAdmin = ($rawRole === 'admin');


    $roleLabel = $isAdmin ? 'admin' : 'user';
    $nameLabel = $auth['name'] ?? ($auth['email'] ?? 'Người dùng');
}
?>
<!-- HTML TRANG CHỦ -->
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vô Ưu Quán - Vật phẩm Phật giáo</title>
  <link rel="stylesheet" href="/project-mongo/css/style.css">
  <link rel="stylesheet" href="/project-mongo/assets/vendor/fontawesome/css/all.min.css">
</head>
  <!-- HEADER -->
<body>
  <header class="navbar">
  <div class="nav-content">
    <div class="logo">
      <img src="/project-mongo/images/VoUuQuan.svg" alt="Vô Ưu Quán Logo">
    </div>

    <nav class="menu">
      <a href="/project-mongo/trangchu.php">Trang Chủ</a>
      <a href="/project-mongo/gioithieu.php">Giới Thiệu</a>
      <a href="/project-mongo/sanpham.php">Sản Phẩm</a>
     <?php if (!$isAdmin): ?>
    <?php if ($isLoggedIn): ?>
     
      <a href="/project-mongo/checkout.php">Thanh Toán</a>
    <?php else: ?>
      <a href="/project-mongo/account/login.php?next=/project-mongo/checkout.php">
        Thanh Toán
      </a>
    <?php endif; ?>
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
        <span class="cart">
          <i class="fa-solid fa-cart-shopping"></i> 0
        </span>
      <?php endif; ?>
    </div>
  </div>
</header>
<!--BANNER ZONE -->
<section class="banner">
  <div class="banner-container">
    <!-- ẢNH TRÁI LỚN -->
    <div class="banner-left">
      <img id="banner-left-slide" src="/project-mongo/images/product/TRANG_BIA.png" alt="Miễn phí vận chuyển">
      <div class="banner-dots-left">
        <span class="dot-left active" data-index="0"></span>
        <span class="dot-left" data-index="1"></span>
      </div>
    </div>

    <!-- CỘT PHẢI: trên khuyến mãi, dưới chính sách -->
    <div class="banner-right">
      <div class="banner-item">
        <img id="banner-right-top" src="/project-mongo/images/product/GARNET_LUU_DO.png" alt="Khuyến mãi 10%">
      </div>
      <div class="banner-item">
        <img src="/project-mongo/images/product/chinh_sach.png" alt="Chính sách vận chuyển">
      </div>
    </div>
  </div>
</section>

  <!-- ABOUT -->
  <section class="about" id="about">
    <div class="container about-content">
      <div class="about-text">
        <h2>Cửa hàng Vật Phẩm Phật Giáo - Vô Ưu Quán</h2>
        <p>Cửa hàng Vật Phẩm Phật Giáo - Vô Ưu Quán Vô Ưu Quán là website chuyên sâu về vật phẩm Phật Giáo, 
          được tạo nên với tâm nguyện mang đến cho quý Phật tử, người hành trì và người đang tìm về nội tâm, những vật phẩm mang năng lượng lành,
          chánh niệm và tĩnh tại.</p>
        <ul>
          <li>Tôn Tượng Di Lặc Gỗ Tự Nhiên: Biểu tượng của niềm vui, sự sung túc và hỷ xả, được chế tác tinh xảo, mang tính thiêng liêng và tôn kính.</li>
          <li>Vòng Tay Đá Quý Đỏ & Ngọc Trai: Chuỗi hạt - vòng tay hộ thân, với sắc đỏ may mắn và charm ngọc trai thanh lịch, gắn liền với hành trì niệm Phật, thiền định.</li>
          <li>Móc Khóa Phong Thủy Hoàng Kim: Pháp khí chiêu tài lộc và bình an, với các charm mang ý nghĩa may mắn, là vật phẩm hộ thân tiện lợi.</li>
          <li>Lư Xông Trầm Hoa Sen Đồng: Giúp không gian trở nên trang nghiêm và thanh tịnh, hỗ trợ việc xông trầm và nuôi dưỡng tuệ giác mỗi ngày.</li>
        </ul>
      </div>

        <div class="intro-image">
         <img src="./images/product/vat_pham.png" alt="Tượng Phật Di Lặc bằng gỗ và chuỗi hạt đá phong thủy được bày trang nghiêm trên nền vải trơn, tạo không gian thanh tịnh và an lành" class="intro-img">
      </div>
    </div>
  </section>

  <!-- PRODUCT GRID -->
<section class="categories" id="products">
  <div class="container">
    <h2>Sản Phẩm Tại Vô ưu Quán</h2>
    <div class="grid">

      <!-- Vòng Tay Phong Thủy -->
      <a class="item" href="/project-mongo/sanpham.php?loai=<?= urlencode('Vòng Tay Phong Thủy') ?>">
        <img src="/project-mongo/images/categories/vong.png" alt="Vòng Tay Phong Thủy">
        <p>Vòng Tay Phong Thủy</p>
      </a>

      <!-- Dây Chuyền Đá Phong Thủy -->
      <a class="item" href="/project-mongo/sanpham.php?loai=<?= urlencode('Dây Chuyền Đá Phong Thủy') ?>">
        <img src="/project-mongo/images/categories/day_chuyen_aqua.png" alt="Dây Chuyền Đá Phong Thủy">
        <p>Dây Chuyền Đá Phong Thủy</p>
      </a>

      <!-- Lư Xông Trầm / Nhang -->
      <a class="item" href="/project-mongo/sanpham.php?loai=<?= urlencode('Lư Xông Trầm/Nhang') ?>">
        <img src="/project-mongo/images/categories/luhuong.png" alt="Lư Xông Trầm / Nhang">
        <p>Lư Xông Trầm / Nhang</p>
      </a>

      <!-- Móc Khóa Phong Thủy -->
      <a class="item" href="/project-mongo/sanpham.php?loai=<?= urlencode('Móc Khóa Phong Thủy') ?>">
        <img src="/project-mongo/images/categories/mockhoa.png" alt="Móc Khóa Phong Thủy">
        <p>Móc Khóa Phong Thủy</p>
      </a>

      <!-- Tượng Phật Mini -->
      <a class="item" href="/project-mongo/sanpham.php?loai=<?= urlencode('Tượng Phật Mini') ?>">
        <img src="/project-mongo/images/categories/tuong.png" alt="Tượng Phật Mini">
        <p>Tượng Phật Mini</p>
      </a>

    </div>
  </div>
</section>



  <!-- FOOTER -->
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
<script>
// Hiệu ứng chuyển banner phải
// 🌿 Slide bên trái (2 ảnh + dot chọn)
const leftImages = [
  "/project-mongo/images/product/TRANG_BIA.png",
  "/project-mongo/images/product/free_delivery.png"
];
let leftIndex = 0;
const leftSlide = document.getElementById("banner-left-slide");
const leftDots = document.querySelectorAll(".dot-left");

function changeLeftSlide(index) {
  leftSlide.classList.add("fade-out");
  setTimeout(() => {
    leftIndex = index;
    leftSlide.src = leftImages[leftIndex];
    leftSlide.classList.remove("fade-out");
    updateLeftDots();
  }, 600);
}

function updateLeftDots() {
  leftDots.forEach((dot, i) => {
    dot.classList.toggle("active", i === leftIndex);
  });
}

// Tự động chuyển ảnh
let autoLeft = setInterval(() => {
  let next = (leftIndex + 1) % leftImages.length;
  changeLeftSlide(next);
}, 4000);

// Click vào chấm để đổi ảnh thủ công
leftDots.forEach(dot => {
  dot.addEventListener("click", () => {
    clearInterval(autoLeft);
    changeLeftSlide(Number(dot.dataset.index));
    autoLeft = setInterval(() => {
      let next = (leftIndex + 1) % leftImages.length;
      changeLeftSlide(next);
    }, 4000);
  });
});
</script>
</body>
</html>
<?php
// End of trangchu.php
?>
