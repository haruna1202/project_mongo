<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['auth'])) { header('Location: /project-mongo/account/login.php'); exit; }
if (($_SESSION['auth']['role'] ?? '') !== 'admin') { header('Location: /project-mongo/VoUuQuan.php'); exit; }
$ADMIN_NAME = $_SESSION['auth']['username'] ?? 'Admin';
