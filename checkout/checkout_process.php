<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$cart_ids_arr = [];
if (isset($_POST['selected']) && is_array($_POST['selected'])) {
    $cart_ids_arr = array_map('intval', $_POST['selected']);
} elseif (isset($_POST['cart_ids'])) {
    if (is_array($_POST['cart_ids'])) {
        $cart_ids_arr = array_map('intval', $_POST['cart_ids']);
    } else {
        $cart_ids_arr = array_map('intval', array_filter(array_map('trim', explode(',', $_POST['cart_ids']))));
    }
}

if (empty($cart_ids_arr)) {
    echo "<script>alert('Tidak ada produk dipilih'); window.location='../cart/cart.php';</script>";
    exit();
}

$metode = isset($_POST['metode_pembayaran']) ? mysqli_real_escape_string($conn, $_POST['metode_pembayaran']) : '';
$alamat = isset($_POST['alamat']) ? mysqli_real_escape_string($conn, $_POST['alamat']) : '';

$pengiriman = '';
$ongkir = 0;
if (isset($_POST['pengiriman']) && strpos($_POST['pengiriman'], '|') !== false) {
    list($pengiriman_part, $ongkir_part) = explode('|', $_POST['pengiriman'], 2);
    $pengiriman = mysqli_real_escape_string($conn, $pengiriman_part);
    $ongkir = (int)$ongkir_part;
} elseif (isset($_POST['pengiriman'])) {
    $pengiriman = mysqli_real_escape_string($conn, $_POST['pengiriman']);
}

$admin_fee = 5000;

$ids_str = implode(',', array_map('intval', $cart_ids_arr));

$sql = "SELECT c.id AS cart_id, c.quantity, c.product_id, p.name, p.price
        FROM `cart` c
        JOIN `products` p ON c.product_id = p.id
        WHERE c.id IN ($ids_str) AND c.user_id = $user_id";

$query = mysqli_query($conn, $sql);
if (! $query || mysqli_num_rows($query) == 0) {
    echo "<script>alert('Produk di keranjang tidak ditemukan atau bukan milik Anda'); window.location='../cart/cart.php';</script>";
    exit();
}

// Hitung total
$total = 0;
$items = [];
while ($row = mysqli_fetch_assoc($query)) {
    $sub = $row['price'] * $row['quantity'];
    $total += $sub;
    $items[] = $row;
}

$grand_total = $total + $ongkir + $admin_fee;

// Simpan ke table `transactions` (PERBAIKAN: sebelumnya `transaction`)
$metode_e = mysqli_real_escape_string($conn, $metode);
$alamat_e = mysqli_real_escape_string($conn, $alamat);
$pengiriman_e = mysqli_real_escape_string($conn, $pengiriman);

$ins_sql = "INSERT INTO `transactions` (user_id, total, metode_pembayaran, alamat, pengiriman, ongkir, admin_fee, status, created_at)
            VALUES ($user_id, $grand_total, '$metode_e', '$alamat_e', '$pengiriman_e', $ongkir, $admin_fee, 'pending', NOW())";

$ins = mysqli_query($conn, $ins_sql);
if (! $ins) {
    echo "<script>alert('Gagal menyimpan transaksi.'); window.location='../cart/cart.php';</script>";
    exit();
}

$transaksi_id = mysqli_insert_id($conn);

// Simpan produk ke transaction_items
foreach ($items as $item) {
    $produk_id = (int)$item['product_id'];
    $nama = mysqli_real_escape_string($conn, $item['name']);
    $harga = (float)$item['price'];
    $qty = (int)$item['quantity'];
    $subtotal = $harga * $qty;

    $item_sql = "INSERT INTO `transaction_items` (transaction_id, product_id, product_name, price, quantity, subtotal)
                 VALUES ($transaksi_id, $produk_id, '$nama', $harga, $qty, $subtotal)";
    $it_res = mysqli_query($conn, $item_sql);
    if (! $it_res) {
        // Jika gagal menyimpan item, log/beri tahu dan hentikan
        echo "<script>alert('Gagal menyimpan item transaksi.'); window.location='../cart/cart.php';</script>";
        exit();
    }
}

// Hapus dari cart (hanya milik user)
$del_sql = "DELETE FROM `cart` WHERE id IN ($ids_str) AND user_id = $user_id";
mysqli_query($conn, $del_sql);

echo "<script>alert('Transaksi berhasil!'); window.location='../order/order_history.php';</script>";
exit();
