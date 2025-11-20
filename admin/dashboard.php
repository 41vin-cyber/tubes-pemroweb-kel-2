<?php 
include 'auth_admin.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
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
            font-size: 28px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info p {
            color: #666;
            font-weight: bold;
        }
        
        .logout-btn {
            background: #d9534f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c9302c;
        }
        
        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .menu-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.6);
        }
        
        .menu-card h2 {
            font-size: 22px;
            margin-bottom: 10px;
        }
        
        .menu-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Dashboard Admin</h1>
                <p style="color: #999; font-size: 14px; margin-top: 5px;">Kelola sistem toko online Anda</p>
            </div>
            <div class="user-info">
                <p>👤 <?= htmlspecialchars($_SESSION['username']) ?></p>
                <a href="../auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="menu">
            <a href="admin_users.php" class="menu-card">
                <h2>👥 Kelola Users</h2>
                <p>Tambah, edit, atau hapus akun pengguna</p>
            </a>
            
            <a href="admin_products.php" class="menu-card">
                <h2>📦 Kelola Produk</h2>
                <p>Kelola daftar produk toko Anda</p>
            </a>
            
            <a href="admin_transactions.php" class="menu-card">
                <h2>💰 Kelola Transaksi</h2>
                <p>Lihat dan ubah status transaksi</p>
            </a>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 Admin System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
