<?php
include '../conn.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit();
}

$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($cart_id <= 0) {
    header('Location: cart.php');
    exit();
}

// If quantity less than 1, remove the item.
if ($quantity < 1) {
    $del = mysqli_query($conn, "DELETE FROM `cart` WHERE id = $cart_id AND user_id = $user_id");
    header('Location: cart.php');
    exit();
}

// Ensure the cart item belongs to the current user
$check = mysqli_query($conn, "SELECT user_id FROM `cart` WHERE id = $cart_id");
if (! $check || mysqli_num_rows($check) == 0) {
    header('Location: cart.php');
    exit();
}

$row = mysqli_fetch_assoc($check);
if ($row['user_id'] != $user_id) {
    header('Location: cart.php');
    exit();
}

$quantity = max(1, $quantity);
$q = mysqli_query($conn, "UPDATE `cart` SET quantity = $quantity WHERE id = $cart_id AND user_id = $user_id");

if ($q) {
    header('Location: cart.php');
} else {
    echo "<script>alert('Gagal mengupdate keranjang: " . mysqli_error($conn) . "'); window.location='cart.php';</script>";
}
exit();

?>
