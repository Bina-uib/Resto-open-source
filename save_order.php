<?php
session_start();
include "config.php";

if(!isset($_POST['menu'])){
    die("Tidak ada pesanan");
}

$customer_id = $_SESSION['customer_id'];

$menus = $_POST['menu'];

$total = 0;

/* =========================
   HITUNG TOTAL
========================= */

foreach($menus as $product_id => $qty){

    $result = $conn->query("SELECT * FROM products WHERE id=$product_id");
    $product = $result->fetch_assoc();

    $subtotal = $product['price'] * $qty;

    $total += $subtotal;
}

/* =========================
   SIMPAN KE ORDERS
========================= */

$stmt = $conn->prepare("
INSERT INTO orders (customer_id, total)
VALUES (?, ?)
");

$stmt->bind_param("ii", $customer_id, $total);

$stmt->execute();

$order_id = $stmt->insert_id;

/* =========================
   SIMPAN ORDER ITEMS
========================= */

foreach($menus as $product_id => $qty){

    $result = $conn->query("SELECT * FROM products WHERE id=$product_id");
    $product = $result->fetch_assoc();

    $subtotal = $product['price'] * $qty;

    $stmt2 = $conn->prepare("
    INSERT INTO order_items
    (order_id, product_id, qty, price)
    VALUES (?, ?, ?, ?)
    ");

    $stmt2->bind_param(
        "iiii",
        $order_id,
        $product_id,
        $qty,
        $subtotal
    );

    $stmt2->execute();
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Pesanan Berhasil</title>

<style>

body{
    background:#ddb999;
    font-family:monospace;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.box{
    background:#fff;
    padding:40px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 10px 20px rgba(0,0,0,0.2);
}

h1{
    color:#4e2d1f;
}

p{
    margin-top:15px;
    color:#555;
}

a{
    display:inline-block;
    margin-top:25px;
    padding:12px 25px;
    background:#a34700;
    color:white;
    text-decoration:none;
    border-radius:10px;
}

</style>

</head>
<body>

<div class="box">

<h1>Pesanan Berhasil!</h1>

<p>
Pesanan Anda sedang diproses oleh dapur.
</p>

<a href="customer.php">
Kembali
</a>

</div>

</body>
</html>