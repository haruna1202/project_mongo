<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../includes/db_connect.php';

$limit  = (int)($_GET['limit']  ?? 0);
$skip   = (int)($_GET['skip']   ?? 0);
$cat    = trim($_GET['category'] ?? '');
$search = trim($_GET['search']   ?? '');

$filter = [];
if ($cat   !== '') $filter['Loaihang']    = $cat;
if ($search!== '') $filter['Tensanpham']  = ['$regex'=>$search,'$options'=>'i'];

$opt = ['sort'=>['_id'=>-1]];
if ($limit>0) $opt['limit']=$limit;
if ($skip >0) $opt['skip']=$skip;

$docs = $db->sanpham->find($filter, $opt);

$out=[];
foreach($docs as $d){
  $img = $d['Hinhanh'] ?? ($d['hinhAnh'] ?? '');
  $img = str_replace('\\','/',$img);

  $out[] = [
    'id'       => (string)$d['_id'],
    'name'     => $d['Tensanpham'] ?? '',
    'price'    => (float)($d['Giaban'] ?? 0),
    'desc'     => $d['Mota'] ?? '',
    'stock'    => (int)($d['Tonkho'] ?? 0),
    'rating'   => (float)($d['Danhgia'] ?? 0),
    'category' => $d['Loaihang'] ?? '',
    'image'    => $img,
  ];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
