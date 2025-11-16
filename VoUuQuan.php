<?php
require __DIR__ . '/includes/auth.php';
start_session_once();
// Nếu đã đăng nhập → vào trang sau đăng nhập (trangchu.php)
// Chưa đăng nhập → vào trang login
$cta = '/project-mongo/trangchu.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chào mừng đến Vô Ưu Quán</title>
  <link rel="stylesheet" href="css/welcome.css">
</head>

<body>
  <div class="popup">
    <h1>🌸 CHÀO MỪNG BẠN ĐẾN VỚI VÔ ƯU QUÁN 🌸</h1>

    <p>
  <strong>Nơi gửi gắm <em>tâm an</em> và <em>phúc lành</em></strong><br>
  Từng vật phẩm Phật giáo mang ý nghĩa thiền định.<br>
  Khám phá bộ sưu tập độc đáo gồm <em>trang sức bình an</em>,<br>
 <strong>Vô Ưu Quán</strong> đồng hành cùng bạn trên hành trình tìm kiếm <em>an lạc</em>.
</p>

   <!-- Nút hiệu ứng mũi tên -->
<!-- From Uiverse.io by Li-Deheng --> 
<button class="button" onclick="window.location.href='trangchu.php'">
  <span>KHÁM PHÁ NGAY</span>
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 66 43">
    <polygon
      points="39.58,4.46 44.11,0 66,21.5 44.11,43 39.58,38.54 56.94,21.5"
    ></polygon>
    <polygon
      points="19.79,4.46 24.32,0 46.21,21.5 24.32,43 19.79,38.54 37.15,21.5"
    ></polygon>
    <polygon
      points="0,4.46 4.53,0 26.42,21.5 4.53,43 0,38.54 17.36,21.5"
    ></polygon>
  </svg>
</button>

  </div>
</body>
</html>
