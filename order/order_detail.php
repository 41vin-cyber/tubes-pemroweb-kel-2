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
$user_id = intval($_SESSION['user_id']);

// Ambil data transaction (sanitasi minimal — kamu bisa ganti ke prepared statement jika mau)
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
        :root{
            --max-w:1180px;
            --bg: #ffe6f2;
            --accent: #ff4d94;
            --accent-2: #ff77b7;
            --pink-light: #ffb3d9;
            --pink-mid: #ff66b3;
            --pink-dark: #d63384;
            --muted: #6b6b6b;
            --card-shadow: 0 6px 20px rgba(255,105,180,0.12);
            --radius: 12px;
            --gap: 16px;
        }

        *{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%;font-family:"Poppins", Arial, sans-serif;background:var(--bg);color:#222;}
        a{color:inherit;text-decoration:none}
        .container{
            max-width:var(--max-w);
            margin:20px auto;
            background:#fff;
            padding:26px;
            border-radius:var(--radius);
            box-shadow:var(--card-shadow);
        }

        /* header */
        .header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            margin-bottom:22px;
            padding-bottom:18px;
            border-bottom:3px solid var(--pink-light);
        }
        .header h1{font-size:22px;color:var(--pink-dark);font-weight:800}
        .status-badge{
            padding:8px 14px;
            border-radius:999px;
            font-weight:800;
            font-size:13px;
        }
        .status-pending{background:#fff3cd;color:#856404}
        .status-completed{background:#d4edda;color:#155724}
        .status-canceled{background:#f8d7da;color:#721c24}

        .nav a{
            display:inline-block;
            padding:8px 12px;
            border-radius:10px;
            border:2px solid var(--pink-dark);
            color:var(--pink-dark);
            font-weight:700;
            transition:transform .18s ease, background .18s ease, color .18s ease;
            background:#fff;
        }
        .nav a:hover{ background:var(--pink-dark); color:#fff; transform:translateY(-3px); }

        /* info box */
        .info-box{
            background:linear-gradient(180deg,#fff7fa 0%, #fff0f5 100%);
            padding:18px;
            border-radius:12px;
            margin-bottom:18px;
            border-left:6px solid var(--pink-mid);
        }
        .info-row{ display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px; }
        .info-row label{display:block; font-weight:700; color:var(--pink-dark); margin-bottom:6px;}
        .info-row span{display:block; color:var(--muted)}

        /* table */
        table{ width:100%; border-collapse:collapse; margin-top:8px; }
        th{
            background:var(--pink-light);
            color:var(--pink-dark);
            text-align:left;
            padding:14px;
            font-weight:800;
            font-size:14px;
        }
        td{ padding:12px 14px; border-bottom:1px solid #ffdfe8; vertical-align:middle; color:#222; }
        tr:hover{ background:#fff0f6; }

        /* rating form small */
        select{ padding:6px 8px; border-radius:8px; border:1px solid #ffdfe8; font-weight:700 }
        form.inline { margin-top:8px; display:inline-flex; gap:8px; align-items:center; }

        /* summary */
        .summary{
            background:#fff;
            padding:16px;
            border-radius:12px;
            margin-top:18px;
            box-shadow:0 4px 14px rgba(0,0,0,0.04);
            text-align:right;
        }
        .summary-row{ display:flex; justify-content:space-between; gap:12px; margin-bottom:8px; color:#333; font-weight:700; }
        .summary-row.total{ border-top:3px solid var(--pink-light); padding-top:12px; font-size:18px; color:var(--pink-dark) }

        .btn{
            display:inline-block;
            padding:10px 16px;
            border-radius:12px;
            background:var(--pink-mid);
            color:#fff;
            font-weight:800;
            text-decoration:none;
            transition:transform .14s ease, background .14s ease;
        }
        .btn:hover{ transform:translateY(-3px); background:var(--pink-dark); color:#fff }

        /* responsive */
        @media (max-width:768px){
            .info-row{ grid-template-columns:1fr; }
            table, thead, tbody, th, td, tr{ display:block; }
            th{ display:none; }
            tr{ background:#fff; margin-bottom:14px; padding:12px; border-radius:10px; box-shadow:var(--card-shadow); }
            td{ border:none; padding:8px 0; display:flex; justify-content:space-between; }
            td:before{ content:attr(data-label); font-weight:800; color:var(--pink-dark); margin-right:8px; }
            .summary{text-align:left}
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Detail Transaksi #<?= htmlspecialchars($order['id']) ?></h1>
        <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
            <?= ucfirst(htmlspecialchars($order['status'])) ?>
        </span>
    </div>

    <div class="info-box" role="region" aria-label="Informasi transaksi">
        <div class="info-row">
            <div>
                <label>Tanggal Transaksi</label>
                <span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at']))) ?></span>
            </div>
            <div>
                <label>Metode Pembayaran</label>
                <span><?= htmlspecialchars($order['metode_pembayaran']) ?></span>
            </div>
        </div>

        <div class="info-row">
            <div>
                <label>Jenis Pengiriman</label>
                <span><?= htmlspecialchars($order['pengiriman']) ?></span>
            </div>
            <div>
                <label>Ongkir</label>
                <span>Rp <?= number_format((float)$order['ongkir'], 0, ',', '.') ?></span>
            </div>
        </div>

        <div>
            <label>Alamat Pengiriman</label>
            <span><?= nl2br(htmlspecialchars($order['alamat'])) ?></span>
        </div>
    </div>

    <h2 style="margin:0 0 12px 0; color:var(--pink-dark); font-size:18px;">📦 Detail Produk</h2>

    <table role="table" aria-label="Daftar produk">
        <tr>
            <th>Nama Produk</th>
            <th>Harga</th>
            <th>Jumlah</th>
            <th>Subtotal</th>
        </tr>

        <?php
        $subtotal_produk = 0;
        while ($row = mysqli_fetch_assoc($items)) :
            $subtotal_produk += (float)$row['subtotal'];
            $prod_id = intval($row['product_id']);
        ?>
        <tr>
            <td data-label="Nama Produk"><?= htmlspecialchars($row['product_name']) ?></td>
            <td data-label="Harga">Rp <?= number_format((float)$row['price'], 0, ',', '.') ?></td>
            <td data-label="Jumlah"><?= intval($row['quantity']) ?></td>
            <td data-label="Subtotal">Rp <?= number_format((float)$row['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td colspan="4" style="background:transparent; border-bottom:none; padding-top:6px;">
                <?php
                if ($order['status'] === 'completed') {
                    // Check existing review for this user/product/transaction
                    $check = mysqli_query($conn, "SELECT rating FROM product_reviews WHERE user_id = $user_id AND product_id = $prod_id AND transaction_id = $order_id LIMIT 1");
                    if ($check && mysqli_num_rows($check) > 0) {
                        $rv = mysqli_fetch_assoc($check);
                        $rating = intval($rv['rating']);
                        echo '<strong>Rating Anda:</strong> ' . str_repeat('★', $rating) . str_repeat('☆', 5-$rating);
                    } else {
                        // show rating form only
                        ?>
                        <form class="inline" action="../review_add.php" method="POST" style="margin-top:8px;">
                            <input type="hidden" name="product_id" value="<?= $prod_id ?>">
                            <input type="hidden" name="transaction_id" value="<?= $order_id ?>">
                            <label for="rating-<?= $prod_id ?>" style="color:var(--pink-dark); font-weight:700;">Rating:</label>
                            <select id="rating-<?= $prod_id ?>" name="rating" required>
                                <option value="">Pilih Bintang</option>
                                <option value="5">★★★★★ (5)</option>
                                <option value="4">★★★★☆ (4)</option>
                                <option value="3">★★★☆☆ (3)</option>
                                <option value="2">★★☆☆☆ (2)</option>
                                <option value="1">★☆☆☆☆ (1)</option>
                            </select>
                            <button type="submit" class="btn" style="padding:8px 12px; font-size:14px;">Kirim Rating</button>
                        </form>
                        <?php
                    }
                }
                ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="summary" aria-label="Ringkasan pembayaran">
        <div class="summary-row"><span>Subtotal Produk:</span><span>Rp <?= number_format($subtotal_produk, 0, ',', '.') ?></span></div>
        <div class="summary-row"><span>Ongkir:</span><span>Rp <?= number_format((float)$order['ongkir'], 0, ',', '.') ?></span></div>
        <div class="summary-row"><span>Admin Fee:</span><span>Rp <?= number_format((float)$order['admin_fee'], 0, ',', '.') ?></span></div>
        <div class="summary-row total"><span>Total Pembayaran:</span><span>Rp <?= number_format((float)$order['total'], 0, ',', '.') ?></span></div>
    </div>

    <div style="margin-top:18px;">
        <a href="order_history.php" class="btn">← Kembali ke Riwayat Transaksi</a>
    </div>
</div>
</body>
</html>
