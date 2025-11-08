<?php
declare(strict_types=1);
error_reporting(E_ALL); ini_set('display_errors','1');

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /project-mongo/account/login.php'); exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$pass  = (string)($_POST['password'] ?? '');

if ($email === '' || $pass === '') {
  header('Location: /project-mongo/account/login.php?err=missing'); exit;
}

$users = col_users(); // <- đảm bảo hàm này trả về collection 'nguoidung'
$user  = $users->findOne(['$or' => [['Email'=>$email], ['email'=>$email]]]);

if (!$user) {
  header('Location: /project-mongo/account/login.php?err=notfound'); exit;
}

$dbPass = $user['Matkhau'] ?? $user['password'] ?? '';
$ok = false;
if (is_string($dbPass) && $dbPass !== '') {
  $info = password_get_info($dbPass);
  $ok = (($info['algo'] ?? 0) !== 0) ? password_verify($pass, $dbPass)
                                     : hash_equals($dbPass, $pass);
}
if (!$ok) {
  header('Location: /project-mongo/account/login.php?err=wrongpwd'); exit;
}

/* ---- Chuẩn hoá dữ liệu trước khi nhét vào session ---- */
$data = $user->getArrayCopy();
$data['email'] = strtolower($data['Email'] ?? $data['email'] ?? $email);
$data['name']  = $data['Hoten'] ?? $data['Hovaten'] ?? $data['name'] ?? ($data['username'] ?? 'Người dùng');
$data['role']  = strtolower($data['VaiTro'] ?? $data['role'] ?? 'user');

do_login($data);                  // <- auth.php sẽ regenerate id & set $_SESSION['auth']

header('Location: /project-mongo/trangchu.php');  // luôn về trang chủ
exit;
