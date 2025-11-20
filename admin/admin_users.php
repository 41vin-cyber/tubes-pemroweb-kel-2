<?php
include 'auth_admin.php';

// HAPUS USER
if (isset($_POST['hapus_user'])) {
    $id = intval($_POST['id']);
    if ($id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_users.php");
        exit();
    }
}

// UPDATE USER
if (isset($_POST['update_user'])) {
    $id = intval($_POST['id']);
    $username = htmlspecialchars($_POST['username']);
    $role = htmlspecialchars($_POST['role']);
    
    if ($username && $role) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $role, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_users.php");
        exit();
    }
}

// LOAD USER
$users = $conn->query("SELECT id, username, email, role FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User</title>
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
        
        .form-inline {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        input[type="text"], select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .btn-update {
            background: #5cb85c;
            color: white;
        }
        
        .btn-update:hover {
            background: #4cae4c;
        }
        
        .btn-delete {
            background: #d9534f;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c9302c;
        }
        
        .action-cell {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>👥 Kelola User</h1>
        <div class="nav">
            <a href="admin_products.php">Kelola Produk</a>
            <a href="admin_transactions.php">Kelola Transaksi</a>
            <a href="../logout.php" style="background: #d9534f; color: white;">Logout</a>
        </div>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Aksi</th>
        </tr>

        <?php while($u = $users->fetch_assoc()) { ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['role']) ?></td>
            <td>
                <div class="action-cell">
                    <!-- UPDATE -->
                    <form method="post" class="form-inline" style="flex-wrap: wrap;">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input type="text" name="username" value="<?= htmlspecialchars($u['username']) ?>" required style="flex: 1; min-width: 100px;">
                        <select name="role" required>
                            <option value="user" <?= $u['role']=="user"?"selected":"" ?>>user</option>
                            <option value="admin" <?= $u['role']=="admin"?"selected":"" ?>>admin</option>
                        </select>
                        <button class="btn-update" name="update_user">Update</button>
                    </form>

                    <!-- HAPUS -->
                    <?php if ($u['id'] != $_SESSION['user_id']) { ?>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Yakin hapus user ini?')">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button class="btn-delete" name="hapus_user">Hapus</button>
                    </form>
                    <?php } ?>
                </div>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>
</body>
</html>
