<?php
declare(strict_types=1);
error_reporting(E_ALL); ini_set('display_errors','1');

require __DIR__ . '/../includes/db_connect.php';

$token = trim($_GET['token'] ?? '');
$users = col_users();

if ($token === '') { header('Location: /project-mongo/account/forgot_password.php'); exit; }

// Lấy user có token hợp lệ
$user = $users->findOne([
  'resetToken' => $token,
  'resetTokenExp' => ['$gt' => new MongoDB\BSON\UTCDateTime(time()*1000)]
]);

if (!$user) {
  echo '<p>Liên kết không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.</p>';
  echo '<p><a href="/project-mongo/account/forgot_password.php">Quên mật khẩu</a></p>';
  exit;
}

// Xử lý submit đặt mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pass = (string)($_POST['password'] ?? '');
  $re   = (string)($_POST['password_confirm'] ?? '');
  if ($pass === '' || $pass !== $re) {
    $err = 'Mật khẩu không khớp hoặc trống.';
  } else {
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    // Cập nhật cả 2 key để tương thích dữ liệu cũ: Matkhau / password
    $users->updateOne(
      ['_id' => $user['_id']],
      ['$set' => [
          'Matkhau'  => $hash,
          'password' => $hash,
          'updatedAt'=> new MongoDB\BSON\UTCDateTime(time()*1000),
        ],
        '$unset' => [
          'resetToken'    => '',
          'resetTokenExp' => '',
          'resetRequested'=> ''
        ]
      ]
    );

    // Về login + hiển thị flash “đặt lại OK”
    header('Location: /project-mongo/account/login.php?msg=resetok');
    exit;
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Đặt lại mật khẩu</title>
  <link rel="stylesheet" href="/project-mongo/css/login.css">
  <style>.msg{padding:10px 12px;border-radius:8px;margin:8px 0 12px;font-weight:600}
  .msg.error{background:#fde8e8;color:#991b1b;border:1px solid #fecaca}</style>
</head>
<body>
  <div class="login-wrapper">
    <div class="form-section">
      <div class="container">
        <div class="heading">ĐẶT LẠI MẬT KHẨU</div>
        <?php if (!empty($err)): ?>
          <div class="msg error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <form class="form" method="post">
          <input class="input" type="password" name="password" placeholder="Mật khẩu mới" required>
          <input class="input" type="password" name="password_confirm" placeholder="Nhập lại mật khẩu" required>
          <button class="login-button" type="submit">Cập nhật</button>
        </form>
      </div>
    </div>
    <div class="image-section"></div>
  </div>
</body>
</html>
