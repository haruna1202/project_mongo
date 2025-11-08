<?php
declare(strict_types=1);
error_reporting(E_ALL); ini_set('display_errors','1');

require __DIR__ . '/../includes/db_connect.php';
require __DIR__ . '/../includes/auth.php'; // dùng do_login()

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /project-mongo/account/register.php'); exit;
}

$hoten = trim((string)($_POST['hoten'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$pass  = (string)($_POST['password'] ?? '');
$cf    = (string)($_POST['confirm']  ?? '');

// ===== Validate =====
if ($hoten === '' || $email === '' || $pass === '' || $cf === '') {
  header('Location: /project-mongo/account/register.php?err=missing'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: /project-mongo/account/register.php?err=bademail'); exit;
}
if ($pass !== $cf) {
  header('Location: /project-mongo/account/register.php?err=confirm'); exit;
}
if (strlen($pass) < 6) {
  header('Location: /project-mongo/account/register.php?err=weak'); exit;
}

// ===== Collection người dùng =====
$users = col_users(); // <- db_connect.php của bạn đã có hàm này

// Email đã tồn tại?
$exist  = $users->findOne(['$or' => [['Email'=>$email], ['email'=>$email]]]);
if ($exist) {
  header('Location: /project-mongo/account/register.php?err=exists'); exit;
}

// Hash & Insert
$hash = password_hash($pass, PASSWORD_BCRYPT);

$doc = [
  'Hoten'     => $hoten,
  'Email'     => $email,   // đồng bộ cả 2 key theo dữ liệu cũ
  'email'     => $email,
  'VaiTro'    => 'user',
  'Matkhau'   => $hash,
  'password'  => $hash,     // để login_handler hiện tại đọc được
  'createdAt' => new MongoDB\BSON\UTCDateTime()
];

$ins = $users->insertOne($doc);
$doc['_id'] = $ins->getInsertedId();

// Auto login -> trang chủ
do_login($doc);
header('Location: /project-mongo/trangchu.php'); exit;
