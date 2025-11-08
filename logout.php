<?php
require __DIR__ . '/includes/auth.php';
logout();
header('Location: /project-mongo/account/login.php');  // <-- về trang đăng nhập
exit;
