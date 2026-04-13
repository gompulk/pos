let cart = [];

function tambah(id,nama,harga){
 cart.push({id,nama,harga}); render();
}

function render(){
 let html=''; let total=0;
 cart.forEach((i,index)=>{
  html+=`<li>${i.nama} - ${i.harga} <button onclick="hapus(${index})">x</button></li>`;
  total+=i.harga;
 });
 document.getElementById('cart').innerHTML=html;
 document.getElementById('total').innerText=total;
}

function hapus(i){
 cart.splice(i,1); render();
}

function bayar(){
 let total=parseInt(document.getElementById('total').innerText);
 let bayar=parseInt(document.getElementById('bayar').value);
 let kembali = bayar-total;
 document.getElementById('kembalian').innerText='Kembalian: '+kembali;

 fetch('simpan_transaksi.php',{
  method:'POST',
  headers:{'Content-Type':'application/json'},
  body:JSON.stringify({total:total,items:cart})
 }).then(r=>r.json()).then(res=>{
  alert('Transaksi berhasil');
  window.print();
  location.reload();
 });
}
