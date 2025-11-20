<?php
session_start();
include '../conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$orders = $conn->query("
    SELECT id, total, metode_pembayaran, status, created_at 
    FROM transactions
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
        :root{
            --pink-bg: #ffe6f2;
            --pink-light: #ffb3d9;
            --pink-mid: #ff66b3;
            --pink-dark: #d63384;
            --text-dark: #660033;
            --box-shadow: 0 6px 20px rgba(255, 105, 180, 0.12);
        }

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family: "Poppins", Arial, sans-serif;
            background: var(--pink-bg);
            padding:20px;
        }

        .container{
            max-width:1000px;
            margin:0 auto;
            background:#fff;
            padding:30px;
            border-radius:14px;
            box-shadow: var(--box-shadow);
        }

        /* HEADER */
        .header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:30px;
            padding-bottom:20px;
            border-bottom:3px solid var(--pink-light);
        }

        .header h1{
            font-size:26px;
            color:var(--pink-dark);
            font-weight:800;
        }

        .nav a{
            background:#fff;
            border:2px solid var(--pink-dark);
            padding:8px 14px;
            border-radius:8px;
            color:var(--pink-dark);
            text-decoration:none;
            font-weight:600;
            transition:0.25s;
        }

        .nav a:hover{
            background:var(--pink-dark);
            color:#fff;
            transform:translateY(-3px);
        }

        /* TABLE */
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
            overflow:hidden;
        }

        th{
            background:var(--pink-light);
            color:var(--text-dark);
            padding:14px;
            font-size:15px;
            text-align:left;
            font-weight:700;
        }

        td{
            padding:14px;
            border-bottom:1px solid #ffd6ea;
            color:#333;
            font-size:14px;
        }

        tr:hover{
            background:#ffe6f7;
        }

        /* BADGE STATUS */
        .status-badge{
            padding:6px 12px;
            border-radius:999px;
            font-weight:700;
            font-size:12px;
        }

        .status-pending{
            background:#fff3cd;
            color:#856404;
        }

        .status-completed{
            background:#d4edda;
            color:#155724;
        }

        .status-canceled{
            background:#f8d7da;
            color:#721c24;
        }

        /* BUTTON */
        .btn{
            display:inline-block;
            padding:8px 14px;
            background:var(--pink-mid);
            color:#fff;
            border-radius:8px;
            text-decoration:none;
            font-weight:700;
            transition:0.25s;
        }

        .btn:hover{
            background:var(--pink-dark);
            transform:translateY(-3px);
        }

        .empty{
            text-align:center;
            padding:40px 10px;
            color:#777;
            font-size:16px;
        }

        /* RESPONSIVE */
        @media(max-width:768px){
            table, thead, tbody, th, td, tr{
                display:block;
            }

            th{ display:none; }

            tr{
                background:#fff;
                margin-bottom:14px;
                padding:12px;
                border-radius:12px;
                box-shadow: var(--box-shadow);
            }

            td{
                border:none;
                padding:8px 0;
                display:flex;
                justify-content:space-between;
            }

            td:before{
                content: attr(data-label);
                font-weight:700;
                color:var(--pink-dark);
            }
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
        while($row = $orders->fetch_assoc()):
            $no_orders = false;
        ?>
        <tr>
            <td data-label="ID">#<?= $row['id'] ?></td>
            <td data-label="Total">Rp <?= number_format($row['total'],0,',','.') ?></td>
            <td data-label="Metode"><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
            <td data-label="Status">
                <span class="status-badge status-<?= $row['status'] ?>">
                    <?= ucfirst($row['status']) ?>
                </span>
            </td>
            <td data-label="Tanggal"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
            <td data-label="Aksi">
                <a class="btn" href="order_detail.php?id=<?= $row['id'] ?>">Lihat Detail</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <?php if($no_orders): ?>
        <div class="empty">
            Belum ada transaksi<br>
            <a href="../home.php" class="btn" style="margin-top:15px;">Mulai Belanja</a>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
