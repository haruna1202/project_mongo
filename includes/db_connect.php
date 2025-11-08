<?php
// /project-mongo/includes/db_connect.php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

const MONGO_URI = 'mongodb://127.0.0.1:27017';
const DB_NAME   = 'shop_phongthuy';   // <-- chữ thường

$client = new MongoDB\Client(MONGO_URI);
$db     = $client->selectDatabase(DB_NAME);

/** Lấy collection người dùng (ưu tiên 'nguoidung', fallback 'khachhang') */
function col_users(): MongoDB\Collection {
  global $db;
  $names = iterator_to_array($db->listCollectionNames());
  if (in_array('nguoidung', $names, true)) return $db->selectCollection('nguoidung');
  return $db->selectCollection('khachhang');
}

