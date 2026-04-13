<?php include 'layout.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/app.js"></script>
<style>@media print{body *{visibility:hidden}#struk,#struk *{visibility:visible}}</style>

<div id="struk">
<h3>POS STRUK</h3>
<ul id="cart"></ul>
<p>Total: <span id="total">0</span></p>
</div>

<input id="bayar" placeholder="Bayar">
<button onclick="bayar()">Bayar</button>
<p id="kembalian"></p>

<script>
function bayar(){
 Swal.fire({title:'Processing...',didOpen:()=>Swal.showLoading()});
 setTimeout(()=>{
  Swal.fire('Berhasil','Transaksi sukses','success');
  window.print();
 },1000);
}
</script>

<?php include 'layout_footer.php'; ?>