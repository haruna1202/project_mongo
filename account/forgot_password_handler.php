<?php
declare(strict_types=1);
error_reporting(E_ALL); ini_set('display_errors','1');

require __DIR__ . '/../includes/db_connect.php';   // đã có col_users()

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /project-mongo/account/forgot_password.php'); exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
if ($email === '') {
  header('Location: /project-mongo/account/forgot_password.php?err=missing'); exit;
}

$users = col_users();

// Tìm theo cả 'Email' và 'email' để tương thích dữ liệu cũ
$user = $users->findOne(['$or' => [['Email'=>$email], ['email'=>$email]]]);
if (!$user) {
  // Không lộ thông tin tài khoản — trả về trang cũ
  header('Location: /project-mongo/account/forgot_password.php?sent=1'); exit;
}

// Tạo token đặt lại mật khẩu (hết hạn 30 phút)
$token  = bin2hex(random_bytes(16));
$expire = new MongoDB\BSON\UTCDateTime((time()+1800)*1000);

// Lưu token vào các trường RIÊNG, KHÔNG đụng tới Matkhau/password
$users->updateOne(
  ['_id' => $user['_id']],
  ['$set' => [
    'resetToken'     => $token,
    'resetTokenExp'  => $expire,
    'resetRequested' => new MongoDB\BSON\UTCDateTime(time()*1000),
  ]]
);

// Vì bạn đang dev local, chuyển thẳng sang trang đặt mật khẩu:
header('Location: /project-mongo/account/reset_password.php?token='.$token);
exit;
