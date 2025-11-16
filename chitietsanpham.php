<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
// chỉ mở session, KHÔNG ép đăng nhập
start_session_once();

// Lấy thông tin user đăng nhập (nếu có)
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

// Đếm số lượng sản phẩm trong giỏ
$cart_count = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['qty'];
    }
}


// Kết nối MongoDB
require __DIR__ . '/includes/db_connect.php';
use MongoDB\BSON\ObjectId;
$boSanPham = $db->selectCollection('sanpham');

function chuyen_html($chuoi): string {
    return htmlspecialchars((string)$chuoi, ENT_QUOTES, 'UTF-8');
}

function dinh_dang_vnd($soTien): string {
    if ($soTien === '' || $soTien === null) $soTien = 0;
    if (is_string($soTien)) {
        $soTien = preg_replace('/[^\d.]/', '', $soTien);
        $soTien = $soTien === '' ? 0 : (float)$soTien;
    }
    return number_format((float)$soTien, 0, ',', '.') . ' đ';
}

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

$id = $_GET['id'] ?? '';
$sp = null;

if ($id !== '' && preg_match('/^[0-9a-f]{24}$/i', $id)) {
    $sp = $boSanPham->findOne(['_id' => new ObjectId($id)]);
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>
        <?php
        if ($sp) echo chuyen_html($sp['Tensanpham'] ?? 'Chi tiết sản phẩm');
        else echo 'Không tìm thấy sản phẩm';
        ?>
    </title>

    <link rel="stylesheet" href="/project-mongo/css/style.css">
    <link rel="stylesheet" href="/project-mongo/css/chitietsanpham.css">
    <link rel="stylesheet" href="/project-mongo/assets/vendor/fontawesome/css/all.min.css">
</head>
<body>

<header class="navbar">
    <div class="nav-content">
        <div class="logo">
            <img src="/project-mongo/images/VoUuQuan.svg" alt="Vô Ưu Quán Logo">
        </div>

        <nav class="menu">
            <a href="/project-mongo/trangchu.php">Trang Chủ</a>
            <a href="/project-mongo/gioithieu.php">Giới Thiệu</a>
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
    <a href="/project-mongo/cart.php" class="cart" id="header-cart-link">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="cart-count"><?= $cart_count ?></span>
    </a>
    <?php endif; ?>
</div>
    </div>
</header>

<div class="khung-trang">
    <a href="/project-mongo/sanpham.php?loai=all" class="link-ve">&laquo; Quay về danh mục sản phẩm</a>

    <?php if (!$sp): ?>
        <p>Không tìm thấy sản phẩm bạn chọn.</p>
    <?php else: ?>
        <?php
        $ten  = $sp['Tensanpham'] ?? '';
        $gia  = $sp['Giaban'] ?? 0;
        $motaRaw = $sp['Mota'] ?? '';
        $hinh = $sp['hinhanh'] ?? ($sp['Hinhanh'] ?? '');
        $mota = trim((string)$motaRaw);
        $loai = $sp['Loaihang'] ?? '';

        if (
            $mota === '' ||
            $mota === $hinh ||
            preg_match('~\.(jpe?g|png|gif|webp|bmp)$~i', $mota) ||
            preg_match('~^images[\\/]+~i', $mota)
        ) {
            $mota = '';
        }
        ?>

        <div class="chi-tiet">
            <div class="ct-anh">
                <img src="<?= chuyen_html(duong_dan_anh($hinh)); ?>"
                     alt="<?= chuyen_html($ten); ?>">
            </div>

            <div>
                <div class="ct-ten"><?= chuyen_html($ten); ?></div>
                <?php if ($loai !== ''): ?>
                    <div class="ct-cat">Danh mục: <?= chuyen_html($loai); ?></div>
                <?php endif; ?>
                <div class="ct-gia"><?= dinh_dang_vnd($gia); ?></div>

                <?php if ($mota !== ''): ?>
                    <div class="ct-mo-ta-ngan">
                        <?= nl2br(chuyen_html($mota)); ?>
                    </div>
                <?php endif; ?>

                <form class="hang-mua" onsubmit="return false;">
                    <div class="so-luong">
                        <button type="button" onclick="
                            var i=this.parentNode.querySelector('input[name=so_luong]');
                            i.value = Math.max(1, parseInt(i.value||'1')-1);
                        ">-</button>

                        <input type="text" name="so_luong" value="1">

                        <button type="button" onclick="
                            var i=this.parentNode.querySelector('input[name=so_luong]');
                            i.value = Math.max(1, parseInt(i.value||'1')+1);
                        ">+</button>
                    </div>

                    <input type="hidden" name="id_san_pham" value="<?= chuyen_html($id); ?>">
                    <input type="hidden" name="ten_san_pham" value="<?= chuyen_html($ten); ?>">
                    <input type="hidden" name="gia_san_pham" value="<?= (int)$gia; ?>">
                    <input type="hidden" name="hinh_san_pham" value="<?= chuyen_html(duong_dan_anh($hinh)); ?>">

                    <button type="button" id="btn-add-cart" class="nut-chinh">
                        <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
                    </button>

                    <button type="button" id="btn-buy-now" class="nut-outline">
                        Mua ngay
                    </button>
                </form>

                <div class="ct-chinh-sach">
                    <strong>Chính sách tại Vô Ưu Quán:</strong>
                    <ul>
                        <li>Miễn phí giao hàng cho đơn từ 500.000 đ.</li>
                        <li>Hỗ trợ đổi size vòng trong 7 ngày.</li>
                        <li>Sản phẩm được khai quang – trì chú trước khi giao đến khách.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mo-ta-chi-tiet">
            <h2>Mô tả chi tiết sản phẩm</h2>
            <p>
                <?php
                if ($mota === '') {
                    echo 'Chưa có mô tả cho sản phẩm này.';
                } else {
                    echo nl2br(chuyen_html($mota));
                }
                ?>
            </p>

            <h3>Ý nghĩa phong thủy của sản phẩm</h3>
            <ul>
                <li>Giúp tâm an, ngủ ngon, giảm bớt lo âu căng thẳng.</li>
                <li>Thu hút may mắn, tài lộc, thuận lợi trong công việc và kinh doanh.</li>
                <li>Cân bằng năng lượng, tăng sự tập trung và bình tĩnh nội tâm.</li>
                <li>Phù hợp làm quà tặng cho người thân, bạn bè.</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<footer class="site-footer">
    <div class="container footer-content">
        <p>&copy; <?= date('Y') ?> Vô Ưu Quán – Vật phẩm Phật giáo. Sản phẩm cam kết hoàn toàn từ tự nhiên.</p>
        <ul class="footer-list">
            <li>
                <i class="fa-solid fa-map-marker-alt fa-sm"></i>
                256 Nguyễn Văn Cừ - Phường An Hoà - Quận Ninh Kiều - TPCT, Cần Thơ, Vietnam
            </li>
            <li>
                <i class="fa-solid fa-phone fa-sm"></i>
                Hotline: <a href="tel:0389883981">0389 883 981</a>
            </li>
            <li>
                <i class="fa-solid fa-envelope fa-sm"></i>
                Email: <a href="mailto:vouuquanvn@gmail.com">vouuquan@gmail.com</a>
            </li>
        </ul>
    </div>
</footer>
<style>
.footer-list{
  list-style: none;   /* bỏ bullet */
  margin: 0;          /* gọn khoảng cách */
}
</style>
<div id="mini-cart-overlay" class="mini-cart-overlay">
    <div class="mini-cart">
        <div class="mini-cart-header">
            <span>Giỏ hàng</span>
            <button type="button" class="mini-cart-close">Đóng ×</button>
        </div>

        <div class="mini-cart-body">
            <div class="mini-cart-item">
                <img id="mini-cart-img" src="/project-mongo/images/placeholder.png" alt="" class="mini-cart-thumb">
                <div class="mini-cart-info">
                    <div id="mini-cart-title" class="mini-cart-name">Tên sản phẩm</div>
                    <div id="mini-cart-qty" class="mini-cart-qty-price">1 × 0 đ</div>
                </div>
            </div>
        </div>

        <div class="mini-cart-footer">
            <div class="mini-cart-sub">
                <span>Tổng số phụ:</span>
                <span id="mini-cart-subtotal">0 đ</span>
            </div>
            <a href="/project-mongo/cart.php" class="btn-mini full">Xem giỏ hàng</a>
            <a href="/project-mongo/checkout.php" class="btn-mini outline">Thanh toán</a>
        </div>
    </div>
</div>

<script>
  // Truyền trạng thái đăng nhập từ PHP sang JS
  const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
  const LOGIN_URL    = '/project-mongo/account/login.php';

  document.addEventListener('DOMContentLoaded', function() {
    const form    = document.querySelector('form.hang-mua');
    const btnAdd  = document.getElementById('btn-add-cart');
    const btnBuy  = document.getElementById('btn-buy-now');
    const overlay = document.getElementById('mini-cart-overlay');
    const btnClose = document.querySelector('.mini-cart-close');
    const headerCartLink = document.getElementById('header-cart-link');


    if (!form) {
      console.warn('Không tìm thấy form.hang-mua');
      return;
    }

    // Hàm chuyển sang trang đăng nhập
    function redirectToLogin() {
      // Để sau login quay lại đúng trang này
      const nextUrl = encodeURIComponent(window.location.href);
      window.location.href = LOGIN_URL + '?next=' + nextUrl;
    }

    async function goCart(redirectToCheckout) {
      // Nếu CHƯA đăng nhập -> không gọi AJAX, chuyển login luôn
      if (!IS_LOGGED_IN) {
        redirectToLogin();
        return;
      }

      const fd = new FormData(form);
      if (redirectToCheckout) {
        fd.append('hanh_dong', 'mua-ngay');
      }

      try {
        const res = await fetch('/project-mongo/cart_action.php', {
          method: 'POST',
          body: fd
        });

        const raw = await res.text();
        console.log('cart_action.php trả về:', raw);

        if (!res.ok) {
          alert('Lỗi HTTP ' + res.status);
          return;
        }

        let data;
        try {
          data = JSON.parse(raw);
        } catch (e) {
          alert('Dữ liệu trả về không phải JSON hợp lệ.');
          return;
        }

        if (data.error) {
          alert(data.error);
          return;
        }

        // Cập nhật mini-cart
        if (data.item) {
          document.getElementById('mini-cart-title').textContent = data.item.ten;
          document.getElementById('mini-cart-qty').textContent =
            data.item.so_luong + ' × ' + data.item.gia_format;
          document.getElementById('mini-cart-subtotal').textContent =
            data.subtotal_format;

          if (data.item.hinh) {
            document.getElementById('mini-cart-img').src = data.item.hinh;
          }
        }

        if (typeof data.cart_count !== 'undefined') {
          const cartCount = document.querySelector('.cart-count');
          if (cartCount) {
            cartCount.textContent = data.cart_count;
          }
        }

        if (redirectToCheckout) {
          window.location.href = '/project-mongo/checkout.php';
        } else {
          if (overlay) overlay.classList.add('show');
        }

      } catch (err) {
        console.error('Fetch lỗi:', err);
        alert('Không thể gửi yêu cầu tới server: ' + err);
      }
    }

    if (btnAdd) {
      btnAdd.addEventListener('click', function(e) {
        e.preventDefault();
        goCart(false);
      });
    }

    if (btnBuy) {
      btnBuy.addEventListener('click', function(e) {
        e.preventDefault();
        goCart(true);
      });
    }

    if (btnClose && overlay) {
      btnClose.addEventListener('click', function() {
        overlay.classList.remove('show');
      });

      overlay.addEventListener('click', function(e) {
        if (e.target.id === 'mini-cart-overlay') {
          overlay.classList.remove('show');
        }
      });
    }
  });
</script>
</body>
</html>