<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$auth = $_SESSION['auth'] ?? [];
$role = $auth['role'] ?? 'user';
$name = $auth['name'] ?? ($auth['email'] ?? 'Khách');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vô Ưu Quán - Vật phẩm Phật giáo</title>

  <!-- FONT + CSS -->
  <link rel="stylesheet" href="/project-mongo/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

  <!-- HEADER -->
  <header class="navbar">
    <div class="nav-content">
      <div class="logo">
        <img src="/project-mongo/images/VoUuQuan.svg" alt="Vô Ưu Quán Logo">
      </div>

      <nav class="menu">
  <a href="/project-mongo/trangchu.php">Trang Chủ</a>
  <a href="#about">Giới Thiệu</a>
  <a href="#products">Sản Phẩm</a>
  <a href="#checkout">Thanh Toán</a>
</nav>


      <div class="account">
  <i class="fa-regular fa-user"></i>
  Vai trò: <strong><?= htmlspecialchars($role) ?></strong>
  <?php if ($role === 'admin'): ?>
    &nbsp;|&nbsp;<a href="/project-mongo/admin/">Khu vực Admin</a>
  <?php endif; ?>
  &nbsp;|&nbsp;<a href="/project-mongo/logout.php">Đăng xuất</a>
  <span class="cart"><i class="fa-solid fa-cart-shopping"></i> 0</span>
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
    <h2>Sản Phẩm Tại Vô Ưu Quán</h2>
    <div class="grid">

      <div class="item">
        <img src="/project-mongo/images/categories/vong.png" alt="Vòng Tay Phong Thủy">
        <p>Vòng Tay Phong Thủy</p>
      </div>

      <div class="item">
        <img src="/project-mongo/images/categories/day_chuyen_aqua.png" alt="Dây Chuyền Đá Phong Thủy">
        <p>Dây Chuyền Đá Phong Thủy</p>
      </div>

      <div class="item">
        <img src="/project-mongo/images/categories/luhuong.png" alt="Lư Xông Trầm / Nhang">
        <p>Lư Xông Trầm / Nhang</p>
      </div>

      <div class="item">
        <img src="/project-mongo/images/categories/mockhoa.png" alt="Móc Khóa Phong Thủy">
        <p>Móc Khóa Phong Thủy</p>
      </div>

      <div class="item">
        <img src="/project-mongo/images/categories/tuong.png" alt="Tượng Phật Mini">
        <p>Tượng Phật Mini</p>
      </div>

    </div>
  </div>
</section>


  <!-- FOOTER -->
  <footer>
    <div class="container footer-content">
      <p>© 2025 Vô Ưu Quán – Vật phẩm Phật giáo. Sản phẩm cam kết hoàn toàn từ tự nhiên.</p>
    </div>
  </footer>
  


</body>

<script>
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
</html>
