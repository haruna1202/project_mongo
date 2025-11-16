<?php
// === BOOTSTRAP SESSION + BASE_URL ===
if (!defined('BASE_URL')) define('BASE_URL', '/project-mongo');

if (session_status() === PHP_SESSION_NONE) {
  session_name('vouu_sess'); // tên session cố định cho cả site
  session_set_cookie_params([
    'lifetime' => 0,                 
    'path'     => '/project-mongo',  
    'secure'   => false,             
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}
// === XỬ LÝ LOGIC ĐĂNG NHẬP ===
$next = $_GET['next'] ?? $_GET['redirect'] ?? (BASE_URL . '/trangchu.php');

// Giữ lại email nếu từ login_handler redirect về
$prefillEmail = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng nhập | Vô Ưu Quán</title>

  <link rel="stylesheet" href="../css/login.css" />

  <!-- Khung message -->
  <style>
    .msg{
      padding:10px 12px;
      border-radius:8px;
      margin:8px 0 12px;
      font-weight:600;
      line-height:1.4
    }
    .msg.success{
      background:#e7f6ed;
      color:#166534;
      border:1px solid #86efac
    }
    .msg.error{
      background:#fde8e8;
      color:#991b1b;
      border:1px solid #fecaca
    }
  </style>
</head>

<body>
  <div class="login-wrapper">
    <!-- Cột bên trái: Form -->
    <div class="form-section">
      <div class="container">
        <div class="heading">ĐĂNG NHẬP</div>

        <!-- Thông báo reset mật khẩu thành công -->
        <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'resetok'): ?>
          <div class="msg success">Đặt lại mật khẩu thành công! Vui lòng đăng nhập.</div>
        <?php endif; ?>

        <!-- Hiển thị lỗi đăng nhập -->
        <?php
          if (isset($_GET['err'])):
            $map = [
              'missing'  => 'Vui lòng nhập đầy đủ email và mật khẩu.',
              'notfound' => 'Tài khoản không tồn tại.',
              'wrongpwd' => 'Mật khẩu không chính xác.',
            ];
            $msg = $map[$_GET['err']] ?? 'Đăng nhập thất bại.';
        ?>
          <div class="msg error"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- Form đăng nhập -->
        <form class="form" action="login_handler.php" method="POST" novalidate>
          <input
            placeholder="Email"
            id="email"
            name="email"
            type="email"
            class="input"
            required
            autofocus
            autocomplete="email"
            value="<?= htmlspecialchars($prefillEmail, ENT_QUOTES, 'UTF-8') ?>"
          />

          <input
            placeholder="Mật khẩu"
            id="password"
            name="password"
            type="password"
            class="input"
            required
            autocomplete="current-password"
          />

          <!-- Gửi kèm URL cần quay lại sau đăng nhập -->
          <input type="hidden" name="next"
                 value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">

          <div class="forgot-password"><a href="forgot_password.php">Quên mật khẩu?</a></div>

          <input value="Đăng Nhập" type="submit" class="login-button" />
        </form>

        <div class="footer-text">
          <p>Bạn chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
        </div>
        <p class="footer-note">© 2025 Vô Ưu Quán.</p>
      </div>
    </div>

    <!-- Cột bên phải: Ảnh nền -->
    <div class="image-section"></div>
  </div>
</body>
</html>
