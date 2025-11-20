<?php
include 'auth_admin.php';

// UPDATE STATUS
if (isset($_POST['set_status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    
    $valid_status = ['pending', 'completed', 'canceled'];
    if (in_array($status, $valid_status)) {
        mysqli_query($conn, "UPDATE transactions SET status = '$status' WHERE id = $id");
    }
    header("Location: admin_transactions.php");
    exit();
}

// LOAD TRANSAKSI
$tx = mysqli_query($conn, "
    SELECT t.id, t.user_id, t.total, t.status, t.created_at, u.username, u.email
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Transaksi</title>
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
            max-width: 1200px;
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
        
        .nav {
            display: flex;
            gap: 15px;
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
        
        select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        button {
            padding: 8px 15px;
            background: #5cb85c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #4cae4c;
        }
        
        .form-inline {
            display: flex;
            gap: 10px;
            align-items: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>💰 Kelola Transaksi</h1>
        <div class="nav">
            <a href="admin_users.php">Kelola User</a>
            <a href="admin_products.php">Kelola Produk</a>
            <a href="../logout.php" style="background: #d9534f; color: white;">Logout</a>
        </div>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Total</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th>
        </tr>

        <?php while($t = mysqli_fetch_assoc($tx)) { ?>
        <tr>
            <td><?= $t['id'] ?></td>
            <td><?= isset($t['username']) ? htmlspecialchars($t['username']) : '-' ?></td>
            <td><?= isset($t['email']) ? htmlspecialchars($t['email']) : '-' ?></td>
            <td>Rp <?= number_format($t['total'], 0, ',', '.') ?></td>
            <td>
                <span class="status-badge status-<?= $t['status'] ?>">
                    <?= ucfirst($t['status']) ?>
                </span>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
            <td>
                <form method="post" class="form-inline">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <select name="status">
                        <option value="pending" <?= $t['status']=="pending"?"selected":"" ?>>Pending</option>
                        <option value="completed" <?= $t['status']=="completed"?"selected":"" ?>>Completed</option>
                        <option value="canceled" <?= $t['status']=="canceled"?"selected":"" ?>>Canceled</option>
                    </select>
                    <button name="set_status">Set</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>
</body>
</html>
