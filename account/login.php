<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng nhập | Vô Ưu Quán</title>
  <link rel="stylesheet" href="../css/login.css" />
</head>

<body>
  <div class="login-wrapper">
    <!-- Cột bên trái: Form -->
    <div class="form-section">
        
      <div class="container">
        <div class="heading">ĐĂNG NHẬP</div>
        
        <form class="form" action="../includes/login_handler.php" method="POST">
          <input placeholder="Email" id="email" name="email" type="email" class="input" required />
          <input placeholder="Mật khẩu" id="password" name="password" type="password" class="input" required />
          <span class="forgot-password"><a href="forgot_password.php">Quên mật khâu</a></p></span>
          <input value="Đăng Nhập" type="submit" class="login-button" />
        </form>
        <div class="footer-text">
          <p>Bạn chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
        </div>
        <p class="footer-note">© 2025 Vô Ưu Quán.</p>
      </div>
      
    </div>

    <!-- Cột bên phải: Ảnh nền -->
    <div class="image-section">

    </div>
  </div>
</body>
</html>
