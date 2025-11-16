<?php
// cart_action.php – API thêm vào giỏ cho AJAX
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Hàm format tiền VND
function format_vnd($n) {
    return number_format((float)$n, 0, ',', '.') . ' đ';
}

// Lấy dữ liệu từ AJAX
$id   = $_POST['id_san_pham']  ?? '';
$ten  = $_POST['ten_san_pham'] ?? '';
$gia  = isset($_POST['gia_san_pham']) ? (int)$_POST['gia_san_pham'] : 0;
$hinh = $_POST['hinh_san_pham'] ?? '';
$qty  = isset($_POST['so_luong']) ? max(1, (int)$_POST['so_luong']) : 1;

// Check dữ liệu
if ($id === '' || $ten === '' || $gia <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu sản phẩm không hợp lệ.'
    ]);
    exit;
}

// Khởi tạo giỏ
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart =& $_SESSION['cart'];

// Nếu đã có trong giỏ thì + số lượng, không thì tạo mới
if (isset($cart[$id])) {
    $cart[$id]['qty'] += $qty;
} else {
    $cart[$id] = [
        'id'   => $id,
        'ten'  => $ten,
        'gia'  => $gia,   // đơn giá
        'hinh' => $hinh,
        'qty'  => $qty,   // số lượng
    ];
}

// Tính tổng số lượng để hiện lên icon giỏ hàng
$cart_count = 0;
$subtotal   = 0;

foreach ($cart as $item) {
    $cart_count += $item['qty'];
    $subtotal   += $item['gia'] * $item['qty'];
}

// Lấy item vừa thêm (hoặc cập nhật)
$currentItem = $cart[$id];

echo json_encode([
    'success'    => true,
    'message'    => 'Đã thêm sản phẩm vào giỏ hàng.',
    'cart_count' => $cart_count,

    // Thông tin item để mini-cart bên product_detail.php dùng
    'item' => [
        'id'         => $currentItem['id'],
        'ten'        => $currentItem['ten'],
        'so_luong'   => $currentItem['qty'],
        'gia'        => $currentItem['gia'],
        'gia_format' => format_vnd($currentItem['gia']),
        'hinh'       => $currentItem['hinh'],
    ],

    // Tổng tiền toàn giỏ
    'subtotal'        => $subtotal,
    'subtotal_format' => format_vnd($subtotal),
]);
