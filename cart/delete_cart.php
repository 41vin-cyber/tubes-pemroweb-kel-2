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
if ($cart_id <= 0) {
    header('Location: cart.php');
    exit();
}

// Ensure the cart item belongs to the current user, then delete
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

$del = mysqli_query($conn, "DELETE FROM `cart` WHERE id = $cart_id AND user_id = $user_id");

if ($del) {
    header('Location: cart.php');
} else {
    echo "<script>alert('Gagal menghapus item: " . mysqli_error($conn) . "'); window.location='cart.php';</script>";
}
exit();

?>
