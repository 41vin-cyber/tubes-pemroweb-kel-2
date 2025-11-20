<?php
include 'auth_admin.php';

// daftar kategori (sinkron dengan home.php)
$categories = [
    'makeup' => 'Makeup',
    'skincare' => 'Skincare',
    'haircare' => 'Haircare',
    'bodycare' => 'Bodycare',
    'nailcare' => 'Nailcare',
    'fragrance' => 'Fragrance'
];

// TAMBAH PRODUK
if (isset($_POST['tambah_produk'])) {
    $name = htmlspecialchars($_POST['name']);
    $price = intval($_POST['price']);
    $description = htmlspecialchars($_POST['description']);
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    // validate category, fallback to empty string if invalid
    if (!array_key_exists($category, $categories)) $category = '';
    $image = "produk.jpg"; // Default image
    
    // Handle file upload
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed) && $file['size'] <= 2000000) {
            $image = time() . "_" . $file['name'];
            move_uploaded_file($file['tmp_name'], '../assets/' . $image);
        }
    }
    
        if ($name && $price > 0) {
        $stmt = $conn->prepare("INSERT INTO products (name, price, description, image, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $name, $price, $description, $image, $category);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_products.php");
        exit();
    }
}

// UPDATE PRODUK
if (isset($_POST['update_produk'])) {
    $id = intval($_POST['id']);
    $name = htmlspecialchars($_POST['name']);
    $price = intval($_POST['price']);
    $description = htmlspecialchars($_POST['description']);
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    if (!array_key_exists($category, $categories)) $category = '';
    
    if ($name && $price > 0) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, category = ? WHERE id = ?");
        $stmt->bind_param("sissi", $name, $price, $description, $category, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_products.php");
        exit();
    }
}

// HAPUS PRODUK
if (isset($_POST['hapus_produk'])) {
    $id = intval($_POST['id']);
    
    $result = $conn->query("SELECT image FROM products WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        $file = '../assets/' . $row['image'];
        if (file_exists($file)) unlink($file);
    }
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_products.php");
    exit();
}

// LOAD PRODUK (tambahkan category)
$products = $conn->query("SELECT id, name, price, description, image, category FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk</title>
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
        
        .section-title {
            font-size: 18px;
            margin: 30px 0 15px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .form-group {
            display: grid;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        input, textarea, select {
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
        
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-update {
            background: #5cb85c;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .btn-update:hover {
            background: #4cae4c;
        }
        
        .btn-delete {
            background: #d9534f;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .btn-delete:hover {
            background: #c9302c;
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
        
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .action-cell {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📦 Kelola Produk</h1>
        <div class="nav">
            <a href="admin_users.php">Kelola User</a>
            <a href="admin_transactions.php">Kelola Transaksi</a>
            <a href="../auth/logout.php" style="background: #d9534f; color: white;">Logout</a>
        </div>
    </div>

    <h2 class="section-title">➕ Tambah Produk Baru</h2>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <div class="form-row">
                <input type="text" name="name" placeholder="Nama produk" required>
                <input type="number" name="price" placeholder="Harga (Rp)" min="1" required>
            </div>
            <textarea name="description" placeholder="Deskripsi produk"></textarea>
            <div class="form-row">
                <select name="category" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="file" name="image" accept="image/*">
                <button class="btn-primary" name="tambah_produk">Tambah Produk</button>
            </div>
        </div>
    </form>

    <h2 class="section-title">📋 Daftar Produk</h2>
    <table>
        <tr>
            <th>Gambar</th>
            <th>Nama</th>
            <th>Harga</th>
            <th>Deskripsi</th>
            <th>Kategori</th>
            <th>Aksi</th>
        </tr>

        <?php while($p = $products->fetch_assoc()) { ?>
        <tr>
            <td>
                <img src="../assets/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="product-img">
            </td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td>Rp <?= number_format($p['price'], 0, ',', '.') ?></td>
            <td><?= substr(htmlspecialchars($p['description']), 0, 50) ?>...</td>
            <td><?= htmlspecialchars(isset($categories[$p['category']]) ? $categories[$p['category']] : $p['category']) ?></td>
            <td>
                <div class="action-cell">
                    <!-- UPDATE -->
                    <form method="post" style="display:contents;">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required style="min-width: 100px;">
                        <input type="number" name="price" value="<?= $p['price'] ?>" required style="min-width: 80px;">
                        <textarea name="description" style="min-width: 100px; min-height: 40px;"><?= htmlspecialchars($p['description']) ?></textarea>
                        <select name="category" style="min-width: 150px;">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $k => $label): ?>
                                <option value="<?php echo htmlspecialchars($k); ?>" <?php if ($p['category'] === $k) echo 'selected'; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn-update" name="update_produk">Update</button>
                    </form>

                    <!-- HAPUS -->
                    <form method="post" style="display:inline;" onsubmit="return confirm('Yakin hapus produk ini?')">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn-delete" name="hapus_produk">Hapus</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>
</body>
</html>
