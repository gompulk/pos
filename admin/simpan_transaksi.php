<?php
include '../config/db.php';
$data = json_decode(file_get_contents('php://input'), true);
$total = $data['total'];

$conn->query("INSERT INTO transaksi (total) VALUES ('$total')");
$id = $conn->insert_id;

foreach($data['items'] as $item){
 $conn->query("INSERT INTO detail (transaksi_id, produk_id, harga) VALUES ('$id','$item[id]','$item[harga]')");
}

echo json_encode(['status'=>'ok']);