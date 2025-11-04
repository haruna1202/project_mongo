<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng Ký - Vô Ưu Quán</title>
  <link rel="stylesheet" href="../css/register.css" />
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
  <div class="left">
    <div class="container">
      <h2>Đăng Ký</h2>

      <form class="form">
        <input type="text" name="fullname" placeholder="Họ và tên" required class="input">
        <input type="email" name="email" placeholder="Email" required class="input">
        <input type="password" name="password" placeholder="Mật khẩu" required class="input">
        <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required class="input">
        <button type="submit" class="login-button">Tạo Tài Khoản</button>
      </form>

      <div class="footer-text">
        <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
      </div>

      <p class="footer-note">
        © 2025 Vô Ưu Quán 
      </p>
    </div>
  </div>

  <div class="right"></div>
</body>
</html>
