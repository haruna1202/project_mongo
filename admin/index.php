<?php
require_once dirname(__DIR__) . '/config.php'; // <-- QUAN TRỌNG

if (($_SESSION['auth']['role'] ?? '') !== 'admin') {
  header('Location: ' . BASE_URL . '/account/login.php?next=' . urlencode(ADMIN_URL . '/dashboard.php'));
  exit;
}
header('Location: ' . ADMIN_URL . '/dashboard.php');
exit;
