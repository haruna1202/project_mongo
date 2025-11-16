<?php
declare(strict_types=1);
use MongoDB\BSON\UTCDateTime; 
/* ==== BẮT BUỘC ĐĂNG NHẬP + KẾT NỐI DB ==== */
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db_connect.php';



/* Chống back-cache */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* ==== HẰNG SỐ ĐƯỜNG DẪN ==== */
if (!defined('ADMIN_URL')) define('ADMIN_URL', '/project-mongo/admin');
if (!defined('BASE_URL'))  define('BASE_URL',  '/project-mongo');

/* ==== HELPER CƠ BẢN ==== */
if (!function_exists('esc')) {
  function esc($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('g')) {
  function g($arr, array $keys, $default = '') {
    if ($arr instanceof \MongoDB\Model\BSONDocument || $arr instanceof \MongoDB\Model\BSONArray) {
      $arr = $arr->getArrayCopy();
    }
    if (!is_array($arr)) return $default;
    foreach ($keys as $k) {
      if (isset($arr[$k]) && $arr[$k] !== '' && $arr[$k] !== null) return $arr[$k];
    }
    return $default;
  }
}

/* Lấy URL ảnh đầu tiên của sản phẩm (images, Hinhanh, image, anh, ...) */

if (!function_exists('firstImageUrl')) {
  function firstImageUrl($doc): string {
    if ($doc instanceof \MongoDB\Model\BSONDocument || $doc instanceof \MongoDB\Model\BSONArray) {
      $doc = $doc->getArrayCopy();
    }
    if (!is_array($doc)) return '';

    // Các field có thể chứa ảnh
    $keys = ['Hinhanh','hinhanh','HinhAnh','hinhAnh','image','images','Anh','anh','hinh','thumbnail'];

    foreach ($keys as $k) {
      if (!array_key_exists($k, $doc)) continue;

      $val = $doc[$k];
      if ($val instanceof \MongoDB\Model\BSONDocument || $val instanceof \MongoDB\Model\BSONArray) {
        $val = $val->getArrayCopy();
      }

      $img = '';
      if (is_array($val)) {
        // VD: images: ["vongtay1.jpg", "vongtay2.jpg"]
        $first = $val[0] ?? $val;
        if ($first instanceof \MongoDB\Model\BSONDocument || $first instanceof \MongoDB\Model\BSONArray) {
          $first = $first->getArrayCopy();
        }
        if (is_array($first)) {
          $img = $first['url'] ?? $first['path'] ?? $first['src'] ?? '';
        } else {
          $img = (string)$first;
        }
      } else {
        $img = (string)$val;
      }

      $img = trim($img);
      if ($img === '') continue;

      // 1) URL tuyệt đối
      if (preg_match('~^https?://~i', $img)) return $img;

      // 2) Nếu trong chuỗi đã có "project-mongo" thì coi như full path
      if (strpos($img, 'project-mongo') !== false) {
        return $img;
      }

      // 3) Bắt đầu bằng "/images/..." => thêm BASE_URL vào trước
      if (strpos($img, '/images/') === 0) {
        return BASE_URL . $img;
      }

      // 4) Bắt đầu bằng "images/..." => BASE_URL + "/images/..."
      if (strpos($img, 'images/') === 0) {
        return BASE_URL . '/' . $img;
      }

      // 5) Nếu không có dấu "/" => chỉ là tên file => /images/product/tenfile
      if (strpos($img, '/') === false) {
        return BASE_URL . '/images/product/' . $img;
      }

      // 6) Còn lại: path tương đối khác => gắn BASE_URL phía trước
      return BASE_URL . '/' . ltrim($img, '/');
    }

    return '';
  }
}

/* ==== LẤY COLLECTION ==== */
$colProducts = $db->selectCollection('sanpham');
$colOrders   = $db->selectCollection('donhang');
$colUsers    = $db->selectCollection('nguoidung');

/* ==== THỐNG KÊ NHANH ==== */
try { $countProducts = $colProducts->countDocuments(); } catch (Throwable $e) { $countProducts = 0; }
try { $countOrders   = $colOrders->countDocuments(); }   catch (Throwable $e) { $countOrders = 0; }
try { $countUsers    = $colUsers->countDocuments(); }    catch (Throwable $e) { $countUsers = 0; }

try {
  // ước lượng loại hàng: distinct theo Loaihang/category
  $cats = $colProducts->distinct('Loaihang');
  if (!$cats) $cats = $colProducts->distinct('category');
  $countCategories = is_array($cats) ? count($cats) : 0;
} catch (Throwable $e) { $countCategories = 0; }

$pendingCond = [
  '$or' => [
    ['trangthai' => 'Chờ xử lý'],
    ['TrangThai' => 'Chờ xử lý'],
    ['status'    => 'pending'],
  ]
];
try { $countPending = $colOrders->countDocuments($pendingCond); }
catch (Throwable $e) { $countPending = 0; }

/* ==== DOANH THU 30 NGÀY & 7 NGÀY (SỬA LẠI CHO ĐÚNG FIELD NGAYDAT) ==== */
$labels30 = $data30 = $labels7 = $data7 = [];
$total30 = $total7 = 0;
$orderCount30 = $orderCount7 = 0;

try {
  $tz    = new DateTimeZone('Asia/Ho_Chi_Minh');
  $today = new DateTime('today', $tz);
  $end   = (clone $today)->setTime(23, 59, 59);
  $start30 = (clone $end)->modify('-29 days');

  // Tạo mảng ngày rỗng trong 30 ngày gần nhất
  $revByDate = [];
  $dateKeys  = [];
  $tmp = clone $start30;
  while ($tmp <= $end) {
    $key = $tmp->format('Y-m-d');
    $revByDate[$key] = 0.0;
    $dateKeys[] = $key;
    $tmp->modify('+1 day');
  }

  // Lấy TẤT CẢ đơn Hoàn tất, không filter ngày trong Mongo nữa
  $statusCond = [
    '$or' => [
      ['trangthai' => 'Hoàn tất'],
      ['TrangThai' => 'Hoàn tất'],
      ['status'    => 'completed'],
    ]
  ];
  $cursor = $colOrders->find($statusCond);

  foreach ($cursor as $o) {
    // Lấy ngày đặt đơn theo đủ kiểu field
    $dt = null;

    if (isset($o['created_at']) && $o['created_at'] instanceof UTCDateTime) {
      $dt = $o['created_at']->toDateTime()->setTimezone($tz);
    } elseif (isset($o['ngaydat']) && $o['ngaydat'] instanceof UTCDateTime) {
      $dt = $o['ngaydat']->toDateTime()->setTimezone($tz);
    } elseif (isset($o['NgayDat']) && $o['NgayDat'] instanceof UTCDateTime) {
      $dt = $o['NgayDat']->toDateTime()->setTimezone($tz);
    } elseif (isset($o['NgayDat']) && is_string($o['NgayDat'])) {
      // trường hợp lưu dạng chuỗi "2025-11-15" hoặc "15/11/2025"
      try {
        $dt = new DateTime($o['NgayDat'], $tz);
      } catch (Throwable $e) {
        $dt = null;
      }
    }

    if (!$dt) continue;
    // chỉ lấy đơn trong khoảng 30 ngày gần nhất
    if ($dt < $start30 || $dt > $end) continue;

    $dayKey = $dt->format('Y-m-d');
    if (!array_key_exists($dayKey, $revByDate)) continue;

    $amount = (float)g($o, ['TongTien','Tongtien','tong','total','tongtien'], 0);
    $revByDate[$dayKey] += $amount;
  }

  // Build dữ liệu 30 ngày
  foreach ($dateKeys as $k) {
    $labels30[] = DateTime::createFromFormat('Y-m-d', $k, $tz)->format('d/m');
    $v = $revByDate[$k];
    $data30[] = $v;
    $total30 += $v;
    if ($v > 0) $orderCount30++;
  }

  // 7 ngày gần nhất: lấy 7 ngày cuối cùng từ mảng 30 ngày
  $last7Keys = array_slice($dateKeys, -7);
  foreach ($last7Keys as $k) {
    $labels7[] = DateTime::createFromFormat('Y-m-d', $k, $tz)->format('d/m');
    $v = $revByDate[$k];
    $data7[] = $v;
    $total7 += $v;
    if ($v > 0) $orderCount7++;
  }

} catch (Throwable $e) {
  // có lỗi thì để 0, không làm bể trang
}

/* ==== ĐƠN GẦN ĐÂY ==== */
$recentOrders = [];
try {
  $cursor = $colOrders->find([], ['sort' => ['_id' => -1], 'limit' => 5]);
  foreach ($cursor as $o) $recentOrders[] = $o;
} catch (Throwable $e) {}

/* ==== HÀNG TỒN KHO THẤP (SỬA: LẤY <=5 & NHIỀU FIELD, + ẢNH) ==== */
$lowProducts = [];
try {
  $condLow = [
    '$or' => [
      ['Tonkho'  => ['$lte' => 5]],
      ['tonkho'  => ['$lte' => 5]],
      ['SoLuong' => ['$lte' => 5]],
      ['soLuong' => ['$lte' => 5]],
      ['stock'   => ['$lte' => 5]],
    ]
  ];
  $optsLow = ['limit' => 12];
  $cursorLow = $colProducts->find($condLow, $optsLow);
  foreach ($cursorLow as $p) $lowProducts[] = $p;
} catch (Throwable $e) {}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin — Vô Ưu Quán</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/dashboard.css">
</head>

<body>

<?php include __DIR__ . '/partials/admin_topbar.php'; ?>

<div class="dvq-main">
  <div class="dvq-container">

    <!-- TỔNG QUAN NHANH -->
    <h1 class="dvq-title">Tổng quan nhanh</h1>
    <section class="dvq-grid">
      <div class="dvq-card">
        <h3>Sản phẩm</h3>
        <div class="big"><?= $countProducts ?></div>
      </div>
      <div class="dvq-card">
        <h3>Loại hàng (ước lượng)</h3>
        <div class="big"><?= $countCategories ?></div>
      </div>
      <div class="dvq-card">
        <h3>Đơn hàng</h3>
        <div class="big"><?= $countOrders ?></div>
      </div>
      <div class="dvq-card">
        <h3>Người dùng</h3>
        <div class="big"><?= $countUsers ?></div>
      </div>
      <div class="dvq-card">
        <h3>Đơn chờ xử lý</h3>
        <div class="big"><?= $countPending ?></div>
      </div>
    </section>

    <!-- DOANH THU 30 NGÀY -->
    <div class="dvq-grid-2">
      <section class="dvq-card">
        <h3>Doanh thu 30 ngày gần nhất</h3>
        <p>Tổng doanh thu</p>
        <div class="big"><?= number_format($total30, 0, ',', '.') ?> đ</div>
        <p>Số ngày có đơn: <?= $orderCount30 ?></p>
        <p>Giá trị TB/đơn: 
          <?= $orderCount30 ? number_format($total30 / max($orderCount30,1), 0, ',', '.') . ' đ' : '0 đ' ?>
        </p>
      </section>
      <section class="dvq-card chart-box">
        <h3>Biểu đồ 30 ngày</h3>
        <canvas id="chart30"></canvas>
      </section>
    </div>

    <!-- DOANH THU 7 NGÀY -->
    <div class="dvq-grid-2">
      <section class="dvq-card">
        <h3>Doanh thu 7 ngày gần nhất</h3>
        <p>Bình quân/ngày</p>
        <div class="big"><?= $total7 ? number_format($total7 / 7, 0, ',', '.') . ' đ' : '0 đ' ?></div>
        <p>Tổng 7 ngày: <?= number_format($total7, 0, ',', '.') ?> đ</p>
      </section>
      <section class="dvq-card chart-box">
        <h3>Biểu đồ 7 ngày</h3>
        <canvas id="chart7"></canvas>
      </section>
    </div>

    <!-- ĐƠN GẦN ĐÂY -->
    <h2 class="dvq-title" style="font-size:22px;margin-top:18px;">Đơn gần đây</h2>
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Mã đơn</th>
            <th>Ngày đặt</th>
            <th>Khách hàng</th>
            <th>Trạng thái</th>
            <th class="text-right">Tổng</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$recentOrders): ?>
          <tr><td colspan="5">Chưa có đơn hàng nào.</td></tr>
        <?php else: ?>
          <?php foreach ($recentOrders as $o):
            $id   = (string)($o['_id'] ?? '');
            $code = g($o, ['Madon','MaDon','code'], $id);
            $kh   = g($o, ['TenKhach','TenKhachHang','khach','customer'], 'N/A');
            $st   = g($o, ['trangthai','TrangThai','status'], '');
            $tong = (float)g($o, ['TongTien','Tongtien','tong','total'], 0);
            $ngay = '';
            if (isset($o['ngaydat']) && $o['ngaydat'] instanceof UTCDateTime) {
              $ngay = $o['ngaydat']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y');
            } elseif (isset($o['NgayDat']) && $o['NgayDat'] instanceof UTCDateTime) {
              $ngay = $o['NgayDat']->toDateTime()->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('d/m/Y');
            } elseif (isset($o['NgayDat']) && is_string($o['NgayDat'])) {
              $ngay = (string)$o['NgayDat'];
            }
          ?>
          <tr>
            <td><?= esc($code) ?></td>
            <td><?= esc($ngay) ?></td>
            <td><?= esc($kh) ?></td>
            <td><?= esc($st) ?></td>
            <td style="text-align:right;"><?= number_format($tong, 0, ',', '.') ?> đ</td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- HÀNG TỒN KHO THẤP CẦN BỔ SUNG -->
    <h2 class="dvq-title" style="font-size:22px;margin-top:24px;">Hàng tồn kho thấp cần bổ sung</h2>
    <div class="dvq-low">
      <?php if (!$lowProducts): ?>
        <p>Hiện chưa có sản phẩm nào sắp hết hàng.</p>
      <?php else: ?>
        <?php foreach ($lowProducts as $p):
          $ten  = g($p, ['Tensanpham','TenSanPham','Ten','ten','name'], '(Không tên)');
          $ton  = (int)g($p, ['Tonkho','tonkho','SoLuong','soLuong','stock'], 0);
          $img  = firstImageUrl($p);
        ?>
        <div class="low-card">
          <div class="low-body">
            <div class="thumb">
              <?php if ($img): ?>
                <img src="<?= esc($img) ?>" alt="<?= esc($ten) ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                <div class="thumb-empty">Không ảnh</div>
              <?php endif; ?>
            </div>
            <div class="txt">
              <div class="prod-name"><?= esc($ten) ?></div>
              <div class="stock">Còn: <?= $ton ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>
<!-- FOOTER -->
  <footer class ="site-footer">
    <div class="container footer-content">
      <p>&copy; <?= date('Y') ?> Vô Ưu Quán – Vật phẩm Phật giáo. Sản phẩm cam kết hoàn toàn từ tự nhiên.</p>
    <ul class = "footer-list">
      
      <li><i class="fa-solid fa-map-marker-alt fa-sm" aria-hidden="true"></i>  256 Nguyễn Văn Cừ - Phường An Hoà - Quận Ninh Kiều - TPCT, Can Tho, Vietnam</li>
      <li><i class="fa-solid fa-phone fa-sm" aria-hidden="true"></i> Hotline: <a href="tel:0389883981" style="color:#fff"> 0389 883 981</a></li>
      <li><i class="fa-solid fa-envelope fa-fm"></i> Email:<a href ="mailto:vouuquanvn@gmail.com" style="color:#fff"> vouuquan@gmail.com</a></li>
  </ul>     
    </div>
  </footer>
  <style>
.footer-list{
  list-style: none;   /* bỏ bullet */
  margin: 0;          /* gọn khoảng cách */
}
</style>
<!-- CHART.JS -->
<script src="<?= BASE_URL ?>/assets/vendor/chartjs/chart.umd.min.js"></script>
<script>
const labels30 = <?= json_encode($labels30, JSON_UNESCAPED_UNICODE) ?>;
const data30   = <?= json_encode($data30) ?>;
const labels7  = <?= json_encode($labels7, JSON_UNESCAPED_UNICODE) ?>;
const data7    = <?= json_encode($data7) ?>;

if (document.getElementById('chart30')) {
  new Chart(document.getElementById('chart30'), {
    type: 'line',
    data: { labels: labels30, datasets: [{ label: 'Doanh thu (đ)', data: data30, tension: 0.3, borderWidth: 2, pointRadius: 2 }]},
    options: { responsive: true, maintainAspectRatio: false, plugins:{legend:{display:true}} }
  });
}
if (document.getElementById('chart7')) {
  new Chart(document.getElementById('chart7'), {
    type: 'line',
    data: { labels: labels7, datasets: [{ label: 'Doanh thu (đ)', data: data7, tension: 0.3, borderWidth: 2, pointRadius: 2 }]},
    options: { responsive: true, maintainAspectRatio: false, plugins:{legend:{display:true}} }
  });
}
</script>

</body> 
</html>
