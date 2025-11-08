<?php

declare(strict_types=1);

function start_session_once(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    // tránh BOM/echo trước session_start
    ini_set('session.use_strict_mode', '1');
    session_start();
  }
}
// === RESET-PASSWORD HELPERS (thêm mới) ===
if (!defined('APP_KEY')) {
  // Đổi chuỗi này trước khi lên server thật
  define('APP_KEY', 'vo-uu-quan-change-this-please');
}
function token_hash(string $t): string {
  // Lưu token dưới dạng hash để an toàn
  return hash_hmac('sha256', $t, APP_KEY);
}

function do_login(array $u): void {
  start_session_once();
  session_regenerate_id(true);
  $_SESSION['auth'] = [
    'id'       => (string)($u['_id'] ?? ''),
    'email'    => strtolower($u['email'] ?? ''),
    'name'     => $u['name'] ?? 'Người dùng',
    'role'     => strtolower($u['role'] ?? 'user'),
    'username' => $u['username'] ?? null,
  ];
}

function require_login(): void {
  start_session_once();
  if (!isset($_SESSION['auth'])) {
    header('Location: /project-mongo/account/login.php');
    exit;
  }
}

function logout(): void {
  start_session_once();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}
