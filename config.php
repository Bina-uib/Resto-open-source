<?php
$conn = new mysqli("localhost", "root", "", "warung_os");

if ($conn->connect_error) {
    die("Koneksi gagal");
}
?>