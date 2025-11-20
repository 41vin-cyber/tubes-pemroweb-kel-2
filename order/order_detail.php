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

// Ambil data transaction
$query = mysqli_query($conn, "SELECT id, total, metode_pembayaran, alamat, pengiriman, ongkir, admin_fee, status, created_at FROM transactions WHERE id = $order_id AND user_id = $user_id");
$order = mysqli_fetch_assoc($query);

if (!$order) {
    header("Location: order_history.php");
    exit();
}

// Ambil item yang dibeli
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
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #333;
            font-size: 24px;
        }
        
        .info-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .info-row label {
            font-weight: bold;
            color: #333;
        }
        
        .info-row span {
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-canceled {
            background: #f8d7da;
            color: #721c24;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
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
        
        .summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: right;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .summary-row.total {
            border-top: 2px solid #667eea;
            padding-top: 10px;
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #0275d8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #025aa5;
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
        <div>
            <label>Alamat Pengiriman:</label>
            <span><?= htmlspecialchars($order['alamat']) ?></span>
        </div>
    </div>

    <h2 style="margin-bottom: 15px;">📦 Detail Produk</h2>

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
                <?php
                if ($order['status'] === 'completed') {
                    // Check existing review for this user/product/transaction
                    $check = mysqli_query($conn, "SELECT rating FROM `product_reviews` WHERE user_id = $user_id AND product_id = $prod_id AND transaction_id = $order_id LIMIT 1");
                    if ($check && mysqli_num_rows($check) > 0) {
                        $rv = mysqli_fetch_assoc($check);
                        echo '<strong>Rating Anda:</strong> ' . str_repeat('★', (int)$rv['rating']) . str_repeat('☆', 5-(int)$rv['rating']);
                    } else {
                        // show rating form only
                        ?>
                        <form action="../review_add.php" method="POST" style="margin-top:8px;">
                            <input type="hidden" name="product_id" value="<?= $prod_id ?>">
                            <input type="hidden" name="transaction_id" value="<?= $order_id ?>">
                            <label>Rating: </label>
                            <select name="rating" required>
                                <option value="">Pilih Bintang</option>
                                <option value="5">★★★★★ (5 Bintang)</option>
                                <option value="4">★★★★☆ (4 Bintang)</option>
                                <option value="3">★★★☆☆ (3 Bintang)</option>
                                <option value="2">★★☆☆☆ (2 Bintang)</option>
                                <option value="1">★☆☆☆☆ (1 Bintang)</option>
                            </select>
                            <button type="submit" style="margin-left:8px;">Kirim Rating</button>
                        </form>
                        <?php
                    }
                }
                ?>
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

    <a href="order_history.php" class="btn">← Kembali ke Riwayat Transaksi</a>
</div>
</body>
</html>
