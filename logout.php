<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';

// Định nghĩa BASE_URL + ADMIN_URL nếu chưa có
if (!defined('BASE_URL')) {
    define('BASE_URL', '/project-mongo');
}
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', BASE_URL . '/admin');
}

// Gọi hàm logout() bên auth.php để huỷ session
logout();

// Sau khi logout xong → quay về trang đăng nhập ADMIN
header('Location: ' . BASE_URL . '/account/login.php');
exit;
