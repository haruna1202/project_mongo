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

      <form class="form" action="register_handler.php" method="POST">
        <input name="hoten"    placeholder="Họ và tên" type="text"     class="input" required />
        <input name="email"    placeholder="Email"     type="email"    class="input" required />
        <input name="password" placeholder="Mật khẩu"  type="password" class="input" required minlength="6" />
        <input name="confirm"  placeholder="Nhập lại mật khẩu" type="password" class="input" required minlength="6" />
        <input value="Tạo Tài Khoản" type="submit" class="login-button" />
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
