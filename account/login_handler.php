<?php
declare(strict_types=1);
error_reporting(E_ALL); ini_set('display_errors','1');

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /project-mongo/account/login.php'); exit;
}

// LẤY THÊM THÔNG TIN NEXT (URL CẦN QUAY LẠI)
$nextRaw = trim($_POST['next'] ?? '');

$email = strtolower(trim($_POST['email'] ?? ''));
$pass  = (string)($_POST['password'] ?? '');

if ($email === '' || $pass === '') {
  $q = '/project-mongo/account/login.php?err=missing';
  if ($nextRaw !== '') {
    $q .= '&next=' . urlencode($nextRaw);
  }
  header('Location: ' . $q); exit;
}

$users = col_users(); // <- đảm bảo hàm này trả về collection 'nguoidung'
$user  = $users->findOne(['$or' => [['Email'=>$email], ['email'=>$email]]]);

if (!$user) {
  $q = '/project-mongo/account/login.php?err=notfound';
  if ($nextRaw !== '') {
    $q .= '&next=' . urlencode($nextRaw);
  }
  header('Location: ' . $q); exit;
}

$dbPass = $user['Matkhau'] ?? $user['password'] ?? '';
$ok = false;
if (is_string($dbPass) && $dbPass !== '') {
  $info = password_get_info($dbPass);
  $ok = (($info['algo'] ?? 0) !== 0) ? password_verify($pass, $dbPass)
                                     : hash_equals($dbPass, $pass);
}
if (!$ok) {
  $q = '/project-mongo/account/login.php?err=wrongpwd';
  if ($nextRaw !== '') {
    $q .= '&next=' . urlencode($nextRaw);
  }
  header('Location: ' . $q); exit;
}

/* ---- Chuẩn hoá dữ liệu trước khi nhét vào session ---- */
$data = $user->getArrayCopy();
$data['email'] = strtolower($data['Email'] ?? $data['email'] ?? $email);
$data['name']  = $data['Hoten'] ?? $data['Hovaten'] ?? $data['name'] ?? ($data['username'] ?? 'Người dùng');
$data['role']  = strtolower($data['VaiTro'] ?? $data['role'] ?? 'user');

start_session_once();         
$_SESSION['auth'] = $data;

$role = $data['role'] ?? 'user';

if ($role === 'admin') {
    $redirect = '/project-mongo/admin/dashboard.php';

} else {
  $redirect = '/project-mongo/trangchu.php';
   if ($nextRaw !== '') {
        $redirect = $nextRaw;
    if (preg_match('~^https?://~i', $nextRaw)) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if (stripos($nextRaw, $host) === false) {
                $redirect = '/project-mongo/trangchu.php';
            }
        }
    }
}
header('Location: ' . $redirect);
exit;
