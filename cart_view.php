<?php
// cart_view.php – trả về giỏ hàng hiện tại cho mini cart
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$gio = $_SESSION['gio_hang']  ?? [];   // ['id' => qty]
$data = $_SESSION['cart_data'] ?? [];  // ['id' => ['ten','gia','hinh']]

$items    = [];
$subtotal = 0;

foreach ($gio as $id => $qty) {
    if (!isset($data[$id])) continue;
    $info = $data[$id];

    $gia  = (int)$info['gia'];
    $tt   = $gia * $qty;
    $subtotal += $tt;

    $items[] = [
        'id'           => $id,
        'ten'          => $info['ten'],
        'gia'          => $gia,
        'gia_format'   => number_format($gia, 0, ',', '.') . ' đ',
        'so_luong'     => $qty,
        'thanh_tien'   => $tt,
        'thanh_format' => number_format($tt, 0, ',', '.') . ' đ',
        'hinh'         => $info['hinh'] ?? ''
    ];
}

echo json_encode([
    'success'         => true,
    'items'           => $items,
    'subtotal'        => $subtotal,
    'subtotal_format' => number_format($subtotal, 0, ',', '.') . ' đ',
    'count'           => array_sum($gio)
]);
