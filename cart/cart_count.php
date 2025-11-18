<?php
session_start();
include '../conn.php';

// If user not logged in, show 0
if (!isset($_SESSION['user_id'])) {
	echo 0;
	exit();
}

$user_id = (int)$_SESSION['user_id'];

$q = mysqli_query($conn, "SELECT SUM(quantity) AS total FROM `cart` WHERE user_id=$user_id");
$data = mysqli_fetch_assoc($q);

echo $data['total'] ? $data['total'] : 0;
?>