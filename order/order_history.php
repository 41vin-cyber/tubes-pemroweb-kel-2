<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil semua transaksi user
$orders = $conn->query("
    SELECT id, total, metode_pembayaran, status, created_at FROM transactions
    WHERE user_id = $user_id 
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi</title>
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
        
        .nav a {
            color: #667eea;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav a:hover {
            background: #667eea;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
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
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #5cb85c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #4cae4c;
        }
        
        .empty {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📋 Riwayat Transaksi</h1>
        <div class="nav">
            <a href="../home.php">← Kembali ke Home</a>
        </div>
    </div>

    <table>
        <tr>
            <th>ID Transaksi</th>
            <th>Total Pembayaran</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
        </tr>

        <?php 
        $no_orders = true;
        while($row = $orders->fetch_assoc()) : 
            $no_orders = false;
        ?>
        <tr>
            <td>#<?= $row['id'] ?></td>
            <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
            <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
            <td>
                <span class="status-badge status-<?= $row['status'] ?>">
                    <?= ucfirst($row['status']) ?>
                </span>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
            <td>
                <a class="btn" href="order_detail.php?id=<?= $row['id'] ?>">
                    Lihat Detail
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <?php if($no_orders): ?>
    <div class="empty">
        <p style="font-size: 18px; color: #666;">Belum ada transaksi</p>
        <a href="../home.php" class="btn" style="margin-top: 15px;">Mulai Belanja</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
