<?php
declare(strict_types=1);


require __DIR__ . '/partials/guard.php';            // session + check role=admin
require __DIR__ . '/../includes/db_connect.php';    // kết nối MongoDB -> $db


// ==== THỐNG KÊ BỔ SUNG ====
// KPI (nếu bạn đã có rồi thì bỏ block này)
$stats = [
  'sanpham'   => 0,
  'loaihang'  => 0,
  'donhang'   => 0,
  'nguoidung' => 0,
];
try { $stats['sanpham']   = $db->selectCollection('sanpham')->countDocuments(); }   catch(Throwable $e){}
try {
  $sp = $db->selectCollection('sanpham');
  $vals = array_merge(
    $sp->distinct('loai'), $sp->distinct('Loai'),
    $sp->distinct('category'), $sp->distinct('loaiId')
  );
  $vals = array_values(array_unique(array_filter($vals, fn($v)=>$v!==null && $v!=='')));
  $stats['loaihang'] = count($vals);
} catch (Throwable $e) { $stats['loaihang'] = 0; }

try { $stats['donhang']   = $db->selectCollection('donhang')->countDocuments(); }   catch(Throwable $e){}
try { $stats['nguoidung'] = $db->selectCollection('nguoidung')->countDocuments(); } catch(Throwable $e){}

$orders   = $db->selectCollection('donhang');
$products = $db->selectCollection('sanpham');

// Đơn chờ xử lý
$pendingCond = ['$or' => [['trangthai'=>'Chờ xử lý'], ['TrangThai'=>'Chờ xử lý']]];
try { $pending = $orders->countDocuments($pendingCond); } catch(Throwable $e){ $pending = 0; }

// 5 đơn gần đây
$recent = [];
try {
  $cur = $orders->find([], ['sort'=>['_id'=>-1], 'limit'=>5]);
  foreach($cur as $d) $recent[] = $d;
} catch(Throwable $e){}

// Sản phẩm sắp hết hàng (< 5)
$lowStock = [];
try {
  $cond = ['$or'=>[
    ['tonkho'  => ['$lt'=>5]],
    ['soLuong' => ['$lt'=>5]],
  ]];
  $cur2 = $products->find($cond, ['limit'=>5]);
  foreach($cur2 as $p) $lowStock[] = $p;
} catch(Throwable $e){}

// Doanh thu 7 ngày (tuỳ chọn)
$rev7 = [];
try {
  $since = new MongoDB\BSON\UTCDateTime((time()-6*86400)*1000);
  $pipeline = [
    ['$match'=>[
      '$or'=>[
        ['created_at'=>['$gte'=>$since]],
        ['ngay'      =>['$gte'=>$since]],
        ['NgayDat'   =>['$gte'=>$since]],
      ],
      '$nor'=>[['trangthai'=>'Hủy'],['TrangThai'=>'Hủy']]
    ]],
    ['$addFields'=>[
      'day'=>['$dateToString'=>[
        'format'=>'%Y-%m-%d',
        'date'=>['$ifNull'=>[
          '$created_at',
          ['$ifNull'=>['$ngay','$NgayDat']]
        ]]
      ]]
    ]],
    ['$group'=>[
      '_id'=>'$day',
      'sum'=>['$sum'=>['$ifNull'=>['$tongtien','$TongTien']]]
    ]],
    ['$sort'=>['_id'=>1]]
  ];
  foreach ($orders->aggregate($pipeline) as $r) $rev7[] = $r;
} catch(Throwable $e){}

function h($x){ return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8'); }
function money_vn($n){ $n=is_numeric($n)?(float)$n:0; return number_format($n,0,',','.').'₫'; }
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tổng quan - Vô Ưu Quán (Admin)</title>
  <link rel="stylesheet" href="/project-mongo/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <?php require __DIR__ . '/partials/admin_topbar.php'; ?>

  <main class="admin-content container">
    <h1 style="margin:6px 0 14px">Tổng quan</h1>

    <div class="cards-4">
      <div class="card kpi">
        <h3>Tổng sản phẩm</h3>
        <p class="big"><?= number_format($stats['sanpham']) ?></p>
      </div>
      <div class="card kpi">
        <h3>Loại hàng</h3>
        <p class="big"><?= number_format($stats['loaihang']) ?></p>
      </div>
      <div class="card kpi">
        <h3>Đơn hàng</h3>
        <p class="big"><?= number_format($stats['donhang']) ?></p>
      </div>
      <div class="card kpi">
        <h3>Người dùng</h3>
        <p class="big"><?= number_format($stats['nguoidung']) ?></p>
      </div>
    </div>

    <!-- Chỗ trống để sau này hiển thị biểu đồ / danh sách đơn gần đây -->
    <div class="card" style="margin-top:20px">
      <h3>Đơn gần đây</h3>
      <p style="opacity:.8">Sẽ hiển thị khi kết nối dữ liệu MongoDB cho orders.</p>
    </div>
  </main>
</body>
</html>
