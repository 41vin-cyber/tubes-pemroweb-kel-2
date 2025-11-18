<?php
include '../conn.php';
session_start();

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_GET['id'];

// cek apakah produk sudah ada di keranjang user
$check = $conn->query("SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id");

if ($check->num_rows > 0) {
    // kalau sudah ada, tambahkan jumlahnya
    $conn->query("UPDATE cart SET quantity = quantity + 1 
                  WHERE user_id = $user_id AND product_id = $product_id");
} else {
    // kalau belum ada, tambahkan data baru
    $conn->query("INSERT INTO cart (user_id, product_id, quantity) 
                  VALUES ($user_id, $product_id, 1)");
}

// setelah berhasil → balik ke keranjang
header("Location: cart.php");
exit();
?>
