<?php
// ---- SESSION (dùng 1 cookie chung cho toàn dự án) ----
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_name('vouu_sid'); // tên cookie thống nhất
  session_set_cookie_params([
    'path'     => '/project-mongo', // phạm vi cookie
    'httponly' => true,
    'samesite' => 'Lax',
    // 'secure' => true, // bật nếu chạy HTTPS
  ]);
  session_start();
}

// ---- BASE URL (tự tính theo thư mục dự án) ----
if (!defined('PROJECT_ROOT')) {
  define('PROJECT_ROOT', __DIR__); // C:\xampp\htdocs\project-mongo
}

$docRoot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');      // C:/xampp/htdocs
$baseUrl = str_replace($docRoot, '', str_replace('\\','/', PROJECT_ROOT));          // /project-mongo

// Tập hằng “chuẩn” (chỉ định nghĩa nếu chưa có)
if (!defined('BASE_URL'))  define('BASE_URL', $baseUrl ?: '/');     // /project-mongo
if (!defined('ADMIN_URL')) define('ADMIN_URL', BASE_URL . '/admin'); // /project-mongo/admin

// ALIAS tương thích code cũ
if (!defined('BASE'))        define('BASE', BASE_URL);
if (!defined('ADMIN_BASE'))  define('ADMIN_BASE', ADMIN_URL);
if (!defined('ADMIN'))       define('ADMIN', ADMIN_URL);
