<?php
session_start();
include '../config/db.php';

if(isset($_POST['login'])){
 $user=$_POST['username'];
 $pass=$_POST['password'];

 $stmt=$conn->prepare("SELECT * FROM users WHERE username=?");
 $stmt->bind_param("s",$user);
 $stmt->execute();
 $result=$stmt->get_result();
 $data=$result->fetch_assoc();

 if($data && password_verify($pass,$data['password'])){
  $_SESSION['login']=true;
  $_SESSION['role']=$data['role'];
  header('Location: dashboard.php');
 }else{
  echo "Login gagal";
 }
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<form method="POST" class="container mt-5">
<input name="username" class="form-control mb-2" placeholder="Username">
<input type="password" name="password" class="form-control mb-2" placeholder="Password">
<button name="login" class="btn btn-primary">Login</button>
</form>