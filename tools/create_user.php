<?php
// /project-mongo/tools/create_user.php
declare(strict_types=1);
require __DIR__ . '/../includes/db_connect.php';
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'])) { http_response_code(403); exit('Forbidden'); }

$users = col_users();

// Thông tin mẫu
$email = 'admin@vouuquan.vn';
$name  = 'Quản trị viên';
$pass  = '123456'; // mật khẩu đăng nhập
$hash  = password_hash($pass, PASSWORD_DEFAULT);

$doc = [
  'Email'     => strtolower($email),
  'Hoten'     => $name,
  'Matkhau'   => $hash,        // băm an toàn
  'Role'      => 'admin',
  'createdAt' => new MongoDB\BSON\UTCDateTime()
];

$exists = $users->findOne(['$or' => [['Email'=>strtolower($email)], ['email'=>strtolower($email)]]]);
if ($exists) {
  echo 'Email đã tồn tại.';
} else {
  $users->insertOne($doc);
  echo 'OK: ' . $email . ' / ' . $pass;
}
