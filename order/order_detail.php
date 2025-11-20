<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: order_history.php");
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Ambil data transaksi
$query = mysqli_query($conn, "
    SELECT id, total, metode_pembayaran, alamat, pengiriman, ongkir, admin_fee, status, created_at 
    FROM transactions 
    WHERE id = $order_id AND user_id = $user_id
");
$order = mysqli_fetch_assoc($query);

if (!$order) {
    header("Location: order_history.php");
    exit();
}

// Ambil item transaksi
$items = mysqli_query($conn, "
    SELECT product_id, product_name, price, quantity, subtotal
    FROM transaction_items
    WHERE transaction_id = $order_id
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #ffe6f2;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(255, 128, 170, 0.25);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #ffb3d9;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #660033;
            font-size: 24px;
            font-weight: bold;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }

        .status-pending {
            background: #ffe099;
            color: #7a5600;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-canceled {
            background: #f8d7da;
            color: #721c24;
        }

        .info-box {
            background: #fff0f7;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #ffb3d9;
            margin-bottom: 20px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding-bottom: 12px;
        }

        .info-row label {
            font-weight: bold;
            color: #660033;
        }

        .info-row span {
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        th {
            background: #ffb3d9;
            color: #660033;
            padding: 14px;
            text-align: left;
            font-size: 15px;
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid #f3cce0;
        }

        tr:hover {
            background: #fff5fa;
        }

        .summary {
            margin-top: 20px;
            background: #fff0f7;
            padding: 20px;
            border-radius: 8px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: bold;
            border-top: 2px solid #ffb3d9;
            padding-top: 10px;
            color: #d63384;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            background: #ffb3d9;
            color: #660033;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin-top: 25px;
            transition: 0.2s;
        }

        .btn:hover {
            background: #ff99cc;
        }

        select, button {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ff99cc;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Detail Transaksi #<?= $order['id'] ?></h1>
        <span class="status-badge status-<?= $order['status'] ?>">
            <?= ucfirst($order['status']) ?>
        </span>
    </div>

    <div class="info-box">
        <div class="info-row">
            <div>
                <label>Tanggal Transaksi:</label>
                <span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div>
                <label>Metode Pembayaran:</label>
                <span><?= htmlspecialchars($order['metode_pembayaran']) ?></span>
            </div>
        </div>

        <div class="info-row">
            <div>
                <label>Jenis Pengiriman:</label>
                <span><?= htmlspecialchars($order['pengiriman']) ?></span>
            </div>
        </div>

        <label>Alamat Pengiriman:</label><br>
        <span><?= htmlspecialchars($order['alamat']) ?></span>
    </div>

    <h2 style="color:#660033; margin-bottom:10px;">📦 Detail Produk</h2>

    <table>
        <tr>
            <th>Nama Produk</th>
            <th>Harga</th>
            <th>Jumlah</th>
            <th>Subtotal</th>
        </tr>

        <?php
        $subtotal_produk = 0;
        while ($row = mysqli_fetch_assoc($items)) :
            $subtotal_produk += $row['subtotal'];
            $prod_id = (int)$row['product_id'];
        ?>

        <tr>
            <td><?= htmlspecialchars($row['product_name']) ?></td>
            <td>Rp <?= number_format($row['price']) ?></td>
            <td><?= $row['quantity'] ?></td>
            <td>Rp <?= number_format($row['subtotal']) ?></td>
        </tr>

        <tr>
            <td colspan="4">

            <?php if ($order['status'] === 'completed') : ?>

                <?php
                $check = mysqli_query($conn, "
                    SELECT rating 
                    FROM product_reviews 
                    WHERE user_id = $user_id 
                    AND product_id = $prod_id 
                    AND transaction_id = $order_id 
                    LIMIT 1
                ");

                if ($check && mysqli_num_rows($check) > 0) :
                    $rv = mysqli_fetch_assoc($check);
                    echo "<strong>Rating Anda: </strong>" .
                        str_repeat('★', $rv['rating']) .
                        str_repeat('☆', 5 - $rv['rating']);
                else :
                ?>

                    <form action="../review_add.php" method="POST" style="margin-top:8px;">
                        <input type="hidden" name="product_id" value="<?= $prod_id ?>">
                        <input type="hidden" name="transaction_id" value="<?= $order_id ?>">

                        <label>Rating: </label>
                        <select name="rating" required>
                            <option value="">Pilih Rating</option>
                            <option value="5">★★★★★ (5)</option>
                            <option value="4">★★★★☆ (4)</option>
                            <option value="3">★★★☆☆ (3)</option>
                            <option value="2">★★☆☆☆ (2)</option>
                            <option value="1">★☆☆☆☆ (1)</option>
                        </select>

                        <button type="submit">Kirim</button>
                    </form>

                <?php endif; endif; ?>

            </td>
        </tr>

        <?php endwhile; ?>
    </table>

    <div class="summary">
        <div class="summary-row">
            <span>Subtotal Produk:</span>
            <span>Rp <?= number_format($subtotal_produk) ?></span>
        </div>

        <div class="summary-row">
            <span>Ongkir:</span>
            <span>Rp <?= number_format($order['ongkir']) ?></span>
        </div>

        <div class="summary-row">
            <span>Admin Fee:</span>
            <span>Rp <?= number_format($order['admin_fee']) ?></span>
        </div>

        <div class="summary-row total">
            <span>Total Pembayaran:</span>
            <span>Rp <?= number_format($order['total']) ?></span>
        </div>
    </div>

    <a href="order_history.php" class="btn">← Kembali ke Riwayat</a>

</div>

</body>
</html>
