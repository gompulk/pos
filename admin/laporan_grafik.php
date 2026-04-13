<?php
include '../config/db.php';
$data = $conn->query("SELECT DATE(created_at) tgl, SUM(total) total FROM transaksi GROUP BY tgl");
$labels=[]; $totals=[];
while($d=$data->fetch_assoc()){
 $labels[]=$d['tgl'];
 $totals[]=$d['total'];
}
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<canvas id="chart"></canvas>
<script>
new Chart(document.getElementById('chart'),{
 type:'line',
 data:{labels:<?= json_encode($labels) ?>,
 datasets:[{label:'Penjualan',data:<?= json_encode($totals) ?>}]}
});
</script>