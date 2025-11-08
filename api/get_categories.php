<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../includes/db_connect.php';

$docs = $db->loaihang->find([], ['sort'=>['_id'=>1]]);
$out=[];
foreach($docs as $d){
  $out[] = [
    'id'   => (string)$d['_id'],
    'name' => $d['Tenloai'] ?? '',
    'desc' => $d['Mota'] ?? ''
  ];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
