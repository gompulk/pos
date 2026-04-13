<?php
include '../config/db.php';
$data = $conn->query("SELECT * FROM produk");
$out=[];
while($d=$data->fetch_assoc()){
 $out[]=$d;
}
echo json_encode($out);