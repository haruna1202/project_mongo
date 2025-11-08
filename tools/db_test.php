


<?php
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'])) { http_response_code(403); exit('Forbidden'); }
// DEV ONLY: Đừng deploy file này lên production
require_once __DIR__ . '/../includes/db_connect.php';

$email = strtolower(trim($_GET['email'] ?? ''));
header('Content-Type: text/plain; charset=utf-8');

if ($email === '') { echo "Dùng: check_user.php?email=..."; exit; }

$col  = col_users(); // phải trả về collection 'nguoidung'
$user = $col->findOne(['$or' => [['Email'=>$email], ['email'=>$email]]]);

if (!$user) { echo "Không thấy user với email: $email\n"; exit; }

$u = $user->getArrayCopy();
echo "=== USER DOC ===\n";
print_r([
  '_id'      => (string)($u['_id'] ?? ''),
  'Email'    => $u['Email'] ?? null,
  'email'    => $u['email'] ?? null,
  'Hoten'    => $u['Hoten'] ?? $u['Hovaten'] ?? null,
  'VaiTro'   => $u['VaiTro'] ?? $u['role'] ?? null,
  'Matkhau'  => $u['Matkhau'] ?? null,
  'password' => $u['password'] ?? null,
  'types'    => [
     'Matkhau'  => gettype($u['Matkhau'] ?? null),
     'password' => gettype($u['password'] ?? null),
  ],
]);

$pwd = $u['Matkhau'] ?? $u['password'] ?? '';
echo "\n=== PASSWORD INFO ===\n";
if (!is_string($pwd) || $pwd==='') { echo "dbPass không phải string/empty\n"; exit; }

$info = password_get_info($pwd);
print_r($info);
echo "\n";
$plain = 'mật_khẩu_bạn_vừa_đổi';        // thử giá trị bạn đặt khi reset
$hash  = '...dán đúng chuỗi $2y$10$... trong DB...';
var_dump(password_get_info($hash));     // sẽ trả về ['algoName' => 'bcrypt', ...]
var_dump(password_verify($plain, $hash)); // true nếu khớp