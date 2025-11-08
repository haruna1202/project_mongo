<?php
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'])) { http_response_code(403); exit('Forbidden'); }

// tools/seed.php — nạp dữ liệu JSON vào MongoDB (fix $oid, $date, ...)

require __DIR__ . '/../includes/db_connect.php'; // có $db

/** Chuyển Extended JSON -> BSON PHP */
function convertExtended(&$value) {
  // Nếu là mảng kiểu {"$oid": "..."}
  if (is_array($value)) {
    if (isset($value['$oid'])) {
      $value = new MongoDB\BSON\ObjectId((string)$value['$oid']);
      return;
    }
    if (isset($value['$date'])) {
      // cố gắng parse mọi chuỗi date -> UTCDateTime (ms)
      $ts = strtotime((string)$value['$date']);
      if ($ts !== false) $value = new MongoDB\BSON\UTCDateTime($ts * 1000);
      return;
    }
    if (isset($value['$numberLong'])) {
      $value = (int)$value['$numberLong'];
      return;
    }
    if (isset($value['$numberDecimal'])) {
      $value = new MongoDB\BSON\Decimal128((string)$value['$numberDecimal']);
      return;
    }
    // duyệt sâu
    foreach ($value as $k => &$v) convertExtended($v);
    unset($v);
  }
}

function normalizeDoc(array $doc): array {
  // Chuẩn hóa ảnh: \ -> /
  foreach (['Hinhanh','hinhAnh','image'] as $k) {
    if (isset($doc[$k])) $doc[$k] = str_replace('\\','/',$doc[$k]);
  }

  // Convert Extended JSON (bao gồm _id.$oid, $date, ...)
  foreach ($doc as $k => &$v) convertExtended($v);
  unset($v);

  // Nếu _id là chuỗi rỗng/null thì bỏ để Mongo tự tạo
  if (array_key_exists('_id', $doc) && ( $doc['_id'] === '' || $doc['_id'] === null )) {
    unset($doc['_id']);
  }

  return $doc;
}

function importJson($db, $collName, $file){
  echo "==> $collName: ";
  if (!file_exists($file)) { echo "không thấy file $file\n"; return; }

  $json = json_decode(file_get_contents($file), true);
  if (!is_array($json)) { echo "file không phải JSON array\n"; return; }

  $coll = $db->$collName;
  $count = $coll->countDocuments();
  if ($count > 0) { echo "đã có $count docs, bỏ qua\n"; return; }

  $docs = [];
  foreach ($json as $doc) {
    $docs[] = normalizeDoc($doc);
  }

  $res = $coll->insertMany($docs);
  echo "đã insert " . $res->getInsertedCount() . " docs\n";
}

$base = realpath(__DIR__ . '/../data');
importJson($db, 'sanpham',   $base . '/Shop_phongthuy.sanpham.json');
importJson($db, 'loaihang',  $base . '/Shop_phongthuy.loaihang.json');
importJson($db, 'khachhang', $base . '/Shop_phongthuy.khachhang.json');
importJson($db, 'giohang',   $base . '/Shop_phongthuy.giohang.json');
importJson($db, 'donhang',   $base . '/Shop_phongthuy.donhang.json');

echo "xong.\n";
