<?php
session_start();
include "config.php";

if(isset($_POST['submit'])){
    $phone = $_POST['phone'];
    $table_id = $_POST['table'];

    if(!preg_match('/^08[0-9]{8,11}$/', $phone)){
        echo "<script>alert('No HP harus angka semua & diawali 08!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (phone, table_id) VALUES (?, ?)");
        $stmt->bind_param("si", $phone, $table_id);
        $stmt->execute();

        $_SESSION['customer_id'] = $stmt->insert_id;
        $_SESSION['table_id'] = $table_id;

        header("Location: customer.php");
        exit;
    }
}

$hasLogin = isset($_SESSION['customer_id']);
?>

<!DOCTYPE html>
<html>
<head>
<title>Customer</title>
<link rel="stylesheet" href="style.css">

<style>
/* 🔥 FIX GRID */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    width: 100%;
}

/* container jangan sempit */
.container {
    max-width: 1000px;
    margin: auto;
}

/* card */
.card {
    background: white;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.card input {
    width: 70px;
    padding: 5px;
    text-align: center;
}

/* responsive */
@media (max-width: 768px){
    .menu-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px){
    .menu-grid {
        grid-template-columns: 1fr;
    }
}
</style>

</head>
<body>

<?php if(!$hasLogin): ?>

<div class="container">
    <h1>Masuk Customer</h1>

    <form method="POST">
        <input type="text" name="phone" placeholder="No HP"
               pattern="08[0-9]{8,11}" required><br><br>

        <select name="table" required>
            <option value="">Pilih No Meja</option>
            <?php
            $tables = $conn->query("SELECT * FROM tables");
            while($t = $tables->fetch_assoc()){
                echo "<option value='{$t['id']}'>Meja {$t['table_number']}</option>";
            }
            ?>
        </select>

        <br><br>
        <button type="submit" name="submit">Masuk</button>
    </form>
</div>

<?php else: ?>

<a href="logout_customer.php">← Kembali</a>

<div class="container">
    <h1>Halaman Customer</h1>
    <p>Meja: <?= $_SESSION['table_id'] ?></p>

<form method="POST" action="confirm_order.php">

<h3>Makanan</h3>
<div class="menu-grid">
<?php
$makanan = $conn->query("SELECT * FROM products WHERE category='makanan'");
while($m = $makanan->fetch_assoc()){
?>
    <div class="card">
        <h4><?= $m['name'] ?></h4>
        <img src="<?= $m['image'] ?>" alt="<?= $m['name'] ?>" style="width:100%;height:150px;object-fit:cover;border-radius:10px;">
        <p>Rp <?= number_format($m['price']) ?></p>
        <input type="number" name="qty[<?= $m['id'] ?>]" min="0" value="0">
    </div>
<?php } ?>
</div>

<h3>Minuman</h3>
<div class="menu-grid">
<?php
$minuman = $conn->query("SELECT * FROM products WHERE category='minuman'");
while($d = $minuman->fetch_assoc()){
?>
    <div class="card">
        <h4><?= $d['name'] ?></h4>
        <img src="<?= $d['image'] ?>" alt="<?= $d['name'] ?>" style="width:100%;height:150px;object-fit:cover;border-radius:10px;">
        <p>Rp <?= number_format($d['price']) ?></p>
        <input type="number" name="qty[<?= $d['id'] ?>]" min="0" value="0">
    </div>
<?php } ?>
</div>

<br>
<button type="submit">Lanjut Pesan</button>

</form>
</div>

<button onclick="alert('Pelayan dipanggil!')" style="position:fixed;bottom:20px;left:20px;background:red;color:white;padding:15px;border:none;border-radius:10px;">
    Panggil Pelayan
</button>

<?php endif; ?>

</body>
</html>