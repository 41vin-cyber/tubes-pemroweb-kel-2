<?php
include '../conn.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = mysqli_query($conn, "
    SELECT cart.id AS cart_id, cart.quantity,
    products.name, products.price, products.image
    FROM cart
    JOIN products ON cart.product_id = products.id
    WHERE cart.user_id = $user_id
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Keranjang</title>
</head>
<body>

<h2>Keranjang Belanja</h2>
<a href="../home.php">home</a>

<?php if (mysqli_num_rows($query) == 0): ?>
    <p>Keranjang kosong.</p>
<?php else: ?>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>Pilih</th>
        <th>Gambar</th>
        <th>Nama Produk</th>
        <th>Harga</th>
        <th>Jumlah</th>
        <th>Total</th>
        <th>Aksi</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($query)) : ?>
    <tr>
        <td>

            <input type="checkbox" name="selected[]" value="<?= $row['cart_id'] ?>" form="checkoutForm">
        </td>

        <td>
            <?php if (!empty($row['image'])): ?>
                <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" style="width:80px; height:auto;">
            <?php else: ?>
                -
            <?php endif; ?>
        </td>

        <td><?= htmlspecialchars($row['name']) ?></td>
        <td>Rp <?= number_format($row['price']) ?></td>
        <td>

            <form action="edit_cart.php" method="POST" style="display:inline-block; margin:0;">
                <input type="hidden" name="cart_id" value="<?= $row['cart_id'] ?>">
                <input type="number" name="quantity" value="<?= $row['quantity'] ?>" min="1" style="width:70px;">
                <button type="submit">Update</button>
            </form>
        </td>
        <td>Rp <?= number_format($row['price'] * $row['quantity']) ?></td>
        <td>

            <form action="delete_cart.php" method="POST" onsubmit="return confirm('Hapus item dari keranjang?');" style="display:inline-block; margin:0;">
                <input type="hidden" name="cart_id" value="<?= $row['cart_id'] ?>">
                <button type="submit">Hapus</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<br>


<form id="checkoutForm" action="../checkout/checkout_fixed.php" method="POST">
    <button type="submit">Checkout</button>
</form>

<?php endif; ?>

</body>
</html>
