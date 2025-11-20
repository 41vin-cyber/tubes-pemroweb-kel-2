<?php
include 'conn.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    // require login
    header('Location: auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$transaction_id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;

if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    echo "<script>alert('Data rating tidak valid'); window.history.back();</script>";
    exit();
}

// Verify that the user purchased this product in this transaction (if transaction_id provided)
if ($transaction_id > 0) {
    $check = mysqli_query($conn, "SELECT t.status FROM `transactions` t WHERE t.id = $transaction_id AND t.user_id = $user_id LIMIT 1");
    if (! $check || mysqli_num_rows($check) == 0) {
        echo "<script>alert('Transaksi tidak ditemukan'); window.history.back();</script>";
        exit();
    }
    $t = mysqli_fetch_assoc($check);
    if ($t['status'] !== 'completed') {
        echo "<script>alert('Hanya transaksi yang selesai (completed) yang dapat diberi review'); window.history.back();</script>";
        exit();
    }

    // Ensure the product is part of the transaction items
    $chk2 = mysqli_query($conn, "SELECT 1 FROM `transaction_items` WHERE transaction_id = $transaction_id AND product_id = $product_id LIMIT 1");
    if (! $chk2 || mysqli_num_rows($chk2) == 0) {
        echo "<script>alert('Produk tidak ada pada transaksi ini'); window.history.back();</script>";
        exit();
    }
} else {
    // If no transaction specified, ensure the user has at least one completed transaction for this product
    $chk = mysqli_query($conn, "SELECT 1 FROM `transactions` t JOIN `transaction_items` ti ON t.id = ti.transaction_id WHERE t.user_id = $user_id AND t.status='completed' AND ti.product_id = $product_id LIMIT 1");
    if (! $chk || mysqli_num_rows($chk) == 0) {
        echo "<script>alert('Anda hanya dapat memberi review untuk produk yang sudah Anda beli dan statusnya completed'); window.history.back();</script>";
        exit();
    }
}

// Prevent duplicate review for same user+product+transaction
$dup_sql = "SELECT 1 FROM `product_reviews` WHERE user_id = $user_id AND product_id = $product_id";
if ($transaction_id > 0) $dup_sql .= " AND transaction_id = $transaction_id";
$dup_sql .= " LIMIT 1";
$dup = mysqli_query($conn, $dup_sql);
if ($dup && mysqli_num_rows($dup) > 0) {
    echo "<script>alert('Anda sudah memberi rating untuk produk ini'); window.history.back();</script>";
    exit();
}

// Insert review (rating only)
$ins = mysqli_query($conn, "INSERT INTO `product_reviews` (user_id, product_id, transaction_id, rating, created_at) VALUES ($user_id, $product_id, " . ($transaction_id>0?$transaction_id:"NULL") . ", $rating, NOW())");

if ($ins) {
    // Redirect back to product detail
    header('Location: detail_produk.php?id=' . $product_id);
    exit();
} else {
    echo "<script>alert('Gagal menyimpan review'); window.history.back();</script>";
    exit();
}

?>