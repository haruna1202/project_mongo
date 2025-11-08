<?php
// Hiển thị thông báo (nếu có) khi gửi form
$info = $_GET['info'] ?? '';  // info=sent | notfound | error
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quên Mật Khẩu | Vô Ưu Quán</title>

  <!-- DÙNG ĐƯỜNG DẪN TUYỆT ĐỐI ĐỂ KHỎI LỆCH -->
  <link rel="stylesheet" href="/project-mongo/css/login.css?v=3" />
  <link rel="stylesheet" href="/project-mongo/css/forgot_password.css?v=3" />

  <!-- alert nhỏ (nếu bạn chưa có trong CSS) -->
  <style>
    .msg{padding:10px 12px;border-radius:8px;margin:8px 0 12px;font-weight:600;line-height:1.4}
    .msg.success{background:#e7f6ed;color:#166534;border:1px solid #86efac}
    .msg.error{background:#fde8e8;color:#991b1b;border:1px solid #fecaca}
    .msg.info{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}
  </style>
</head>
<body>
  <div class="login-wrapper">
    <!-- CỘT TRÁI: FORM -->
    <div class="form-section">
      <div class="container">
        <h1 class="heading">Quên Mật Khẩu</h1>

        <?php if ($info === 'sent'): ?>
          <div class="msg success">Đã gửi liên kết đặt lại mật khẩu (demo: tạo token nội bộ). Vui lòng kiểm tra và tiếp tục.</div>
        <?php elseif ($info === 'notfound'): ?>
          <div class="msg error">Email không tồn tại trong hệ thống.</div>
        <?php elseif ($info === 'error'): ?>
          <div class="msg error">Có lỗi xảy ra. Vui lòng thử lại.</div>
        <?php endif; ?>

        <form class="form" method="post" action="forgot_password_handler.php">
          <input
            type="email"
            name="email"
            class="input"
            placeholder="Nhập email đã đăng ký"
            required
          />
          <input type="submit" class="login-button" value="Gửi liên kết đặt lại" />
        </form>

        <div class="footer-text" style="margin-top:8px">
          <a href="login.php">Đăng nhập</a>
        </div>
      </div>
    </div>

    <!-- CỘT PHẢI: ẢNH -->
    <div class="image-section"></div>
  </div>
</body>
</html>
