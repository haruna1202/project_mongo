<?php
// ---- SESSION (dùng 1 cookie chung cho toàn dự án) ----
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_name('vouu_sid'); // tên cookie thống nhất
  session_set_cookie_params([
    'path'     => '/project-mongo', // QUAN TRỌNG: phạm vi cookie cho cả /project-mongo và /project-mongo/admin
    'httponly' => true,
    'samesite' => 'Lax',
    // 'secure' => true, // bật nếu chạy HTTPS
  ]);
  session_start();
}

// ---- BASE URL (tự tính theo thư mục dự án) ----
define('PROJECT_ROOT', __DIR__);                                            // khu mà hệ thống sẽ nhận C:\xampp\htdocs\project-mongo
$docRoot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT']), '/');        // C:/xampp/htdocs
$baseUrl = str_replace($docRoot, '', str_replace('\\','/', PROJECT_ROOT));      // /project-mongo

// Tập hằng “chuẩn”
define('BASE_URL', $baseUrl ?: '/');           // /project-mongo
define('ADMIN_URL', BASE_URL . '/admin');      // /project-mongo/admin

// Tạo ALIAS tương thích với code cũ
if (!defined('BASE'))        define('BASE', BASE_URL);
if (!defined('ADMIN_BASE'))  define('ADMIN_BASE', ADMIN_URL);
if (!defined('ADMIN'))       define('ADMIN', ADMIN_URL);
// ... có thể thêm các hằng số khác nếu cần
