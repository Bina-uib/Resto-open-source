<?php
session_start();
include "config.php";

if(!isset($_POST['qty'])){
    echo "Tidak ada pesanan!";
    exit;
}

$qtys = $_POST['qty'];
$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Konfirmasi Pesanan</title>

    <style>

    body{
        background: #ddb999;
        font-family: monospace;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }

    .confirm-box{
        background: #f8f1eb;
        width: 550px;
        padding: 35px;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    h1{
        text-align: center;
        color: #4e2d1f;
        margin-bottom: 30px;
    }

    .item{
        display: flex;
        justify-content: space-between;
        padding: 15px 0;
        border-bottom: 1px solid #ddd;
        color: #4e2d1f;
        font-size: 18px;
    }

    .total{
        text-align: right;
        margin-top: 25px;
        font-size: 30px;
        font-weight: bold;
        color: #4e2d1f;
    }

    .btn{
        width: 100%;
        margin-top: 30px;
        padding: 16px;
        border: none;
        border-radius: 12px;
        background: #a34700;
        color: white;
        font-size: 18px;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn:hover{
        background: #7a3300;
        transform: scale(1.02);
    }

    .back{
        display: inline-block;
        margin-bottom: 20px;
        text-decoration: none;
        color: #4e2d1f;
        font-weight: bold;
    }

    </style>
</head>
<body>

<div class="confirm-box">

<a href="customer.php" class="back">
← Kembali
</a>

<h1>Konfirmasi Pesanan</h1>

<form method="POST" action="save_order.php">

<?php

foreach($qtys as $id => $qty){

    if($qty > 0){

        $result = $conn->query("SELECT * FROM products WHERE id=$id");
        $row = $result->fetch_assoc();

        $subtotal = $row['price'] * $qty;
        $total += $subtotal;

?>

<div class="item">

<div>
    <b><?= $row['name'] ?></b><br>
    <?= $qty ?> x Rp <?= number_format($row['price']) ?>
</div>

<div>
    Rp <?= number_format($subtotal) ?>
</div>

</div>

<input type="hidden" name="menu[<?= $id ?>]" value="<?= $qty ?>">

<?php
    }
}
?>

<div class="total">
Total: Rp <?= number_format($total) ?>
</div>

<button type="submit" class="btn">
    Konfirmasi Pesanan
</button>

</form>

</div>

</body>
</html>