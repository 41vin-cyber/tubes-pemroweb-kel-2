<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_POST['selected'])) {
    echo "<script>alert('Tidak ada produk dipilih'); window.location='../cart/cart.php';</script>";
    exit();
}

include '../conn.php';

$selected = $_POST['selected'];
$ids = implode(",", $selected);

$query = mysqli_query($conn, "
    SELECT cart.id AS cart_id, cart.quantity, cart.product_id,
           products.name, products.price
    FROM cart
    JOIN products ON cart.product_id = products.id
    WHERE cart.id IN ($ids)
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 26px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 15px;
        }
        
        h3 {
            color: #333;
            margin: 20px 0 10px 0;
            font-size: 16px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
        }
        
        .summary-row.total {
            border-top: 2px solid #667eea;
            padding-top: 12px;
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #5568d3;
        }
        
        .btn-back {
            display: inline-block;
            margin-top: 10px;
            padding: 10px;
            text-align: center;
            background: #999;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            width: 100%;
        }
        
        .btn-back:hover {
            background: #777;
        }
    </style>
    <script>
        function updateTotal() {
            let subtotal = parseInt(document.getElementById('baseTotal').value);
            let adminFee = 5000;

            let shippingRaw = document.getElementById('pengiriman').value.split("|");
            let shippingCost = shippingRaw.length > 1 ? parseInt(shippingRaw[1]) : 0;

            let finalTotal = subtotal + shippingCost + adminFee;

            document.getElementById('subtotalDisplay').innerHTML = "Rp " + subtotal.toLocaleString('id-ID');
            document.getElementById('ongkirDisplay').innerHTML = "Rp " + shippingCost.toLocaleString('id-ID');
            document.getElementById('adminDisplay').innerHTML = "Rp " + adminFee.toLocaleString('id-ID');
            document.getElementById('finalTotal').innerHTML = "Rp " + finalTotal.toLocaleString('id-ID');

            document.getElementById('total_final_input').value = finalTotal;
        }
    </script>
</head>
<body>
<div class="container">
    <h2>🛒 Checkout</h2>

    <form action="checkout_process.php" method="POST">
        <input type="hidden" name="cart_ids" value="<?= implode(",", $selected); ?>">

        <h3>📋 Ringkasan Produk</h3>
        <table>
            <tr>
                <th>Produk</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>

            <?php
            $total_produk = 0;
            while ($row = mysqli_fetch_assoc($query)):
                $sub = $row['price'] * $row['quantity'];
                $total_produk += $sub;
            ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>Rp <?= number_format($row['price']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td>Rp <?= number_format($sub) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <div class="summary">
            <div class="summary-row">
                <span>Subtotal Produk:</span>
                <span>Rp <?= number_format($total_produk) ?></span>
            </div>
        </div>

        <input type="hidden" id="baseTotal" value="<?= $total_produk ?>">

        <h3>💳 Metode Pembayaran</h3>
        <div class="form-group">
            <select name="metode_pembayaran" required>
                <option value="">-- Pilih Metode --</option>
                <option value="Transfer Bank">Transfer Bank</option>
                <option value="E-Wallet">E-Wallet</option>
                <option value="COD">COD (Bayar Ditempat)</option>
            </select>
        </div>

        <h3>📍 Alamat Pengiriman</h3>
        <div class="form-group">
            <textarea name="alamat" placeholder="Masukkan alamat pengiriman lengkap Anda..." required></textarea>
        </div>

        <h3>🚚 Jenis Pengiriman</h3>
        <div class="form-group">
            <select name="pengiriman" id="pengiriman" onchange="updateTotal()" required>
                <option value="">-- Pilih Jenis Pengiriman --</option>
                <option value="Reguler|20000">Reguler (+ Rp 20.000) - 3-5 hari</option>
                <option value="Express|35000">Express (+ Rp 35.000) - 1-2 hari</option>
                <option value="Kargo|50000">Kargo (+ Rp 50.000) - Same Day</option>
            </select>
        </div>

        <div class="summary">
            <h3 style="margin-top: 0;">💰 Rincian Biaya:</h3>
            <div class="summary-row">
                <span>Subtotal Produk:</span>
                <span id="subtotalDisplay">Rp <?= number_format($total_produk) ?></span>
            </div>
            <div class="summary-row">
                <span>Ongkir:</span>
                <span id="ongkirDisplay">Rp 20.000</span>
            </div>
            <div class="summary-row">
                <span>Administrasi:</span>
                <span id="adminDisplay">Rp 5.000</span>
            </div>
            <div class="summary-row total">
                <span>Total Pembayaran:</span>
                <span id="finalTotal">Rp <?= number_format($total_produk + 20000 + 5000) ?></span>
            </div>
        </div>

        <input type="hidden" name="total_final" id="total_final_input" value="<?= $total_produk + 20000 + 5000 ?>">

        <button type="submit">💳 Bayar Sekarang</button>
        <a href="../cart/cart.php" class="btn-back">← Kembali ke Keranjang</a>
    </form>
</div>

<script>
    updateTotal();
</script>

</body>
</html>
