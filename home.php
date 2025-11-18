<?php
session_start();
include 'conn.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// prepare safe values
$searchTerm = '%' . $search . '%';
$searchSafe = mysqli_real_escape_string($conn, $searchTerm);
$categorySafe = mysqli_real_escape_string($conn, $category);

// build query with optional filters
$sql = "SELECT * FROM products WHERE 1";

if (!empty($search)) {
    $sql .= " AND (name LIKE '$searchSafe' OR description LIKE '$searchSafe')";
}

if (!empty($category)) {
    // assuming `category` column stores values like 'makeup', 'skincare', etc.
    $sql .= " AND category = '$categorySafe'";
}

$sql .= " ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query gagal: " . mysqli_error($conn));
}

// list of categories (ubah sesuai data di database jika perlu)
$categories = [
    'makeup' => 'Makeup',
    'skincare' => 'Skincare',
    'haircare' => 'Haircare',
    'bodycare' => 'Bodycare',
    'nailcare' => 'Nailcare',
    'fragrance' => 'Fragrance'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Produk</title>
    <style>
        /* Container utama */
        .product {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            margin: 20px;
        }

        /* Box produk */
        .product-card {
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            border: 1px solid #ccc;
            background: #fff;
            list-style: none;
        }

        /* Gambar produk */
        .product-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
        }

        /* Nama produk */
        .product-card h3 {
            font-size: 16px;
            margin: 10px 0 5px;
        }

        /* Harga */
        .product-card .price {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Tombol */
        .product-card .btn {
            display: inline-block;
            padding: 6px 10px;
            color: #222;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            border: 1px solid #222;
            background: #f8f8f8;
            cursor: pointer;
        }

        /* Kategori list */
        .categories {
            display:flex;
            gap:8px;
            list-style:none;
            padding:12px 20px;
            margin:0;
        }
        .categories li a {
            text-decoration: none;
        }
        .cat-btn {
            padding:6px 10px;
            border-radius:6px;
            border:1px solid #ccc;
            background:#fff;
            cursor:pointer;
            font-size:14px;
        }
        .cat-btn.active {
            background:#222;
            color:#fff;
            border-color:#222;
        }

        /* Navbar */
        nav ul {
            display:flex;
            gap:12px;
            list-style:none;
            padding:12px;
            margin:0;
        }
    </style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="auth/logout.php">Logout</a></li>
            <li><a href="cart/cart.php">Keranjang (<span id="cart-count">0</span>)</a></li>
            <li><a href="home.php">Home</a></li>
            <li><a href="order/order_history.php">Pesanan Saya</a></li>
        </ul>
    </nav>

    <div class="search-box" style="margin-bottom:16px;">
        <form method="GET" action="">
            <!-- preserve category when searching -->
            <?php if ($category !== ''): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES); ?>">
            <?php endif; ?>
            <input type="text" name="q" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
            <button type="submit">Cari</button>
        </form>
    </div>
    
    <!-- Kategori -->
    <ul class="categories">
        <!-- Tautan "Semua" yang membersihkan filter kategori, namun tetap menyertakan pencarian -->
        <?php
        // helper to build querystring preserving search
        function build_query($params) {
            return '?' . http_build_query($params);
        }

        // "Semua" link
        $allParams = [];
        if ($search !== '') $allParams['q'] = $search;
        $allHref = build_query($allParams);
        $isAllActive = ($category === '');
        ?>
        <li>
            <a href="<?php echo htmlspecialchars($allHref, ENT_QUOTES); ?>">
                <button class="cat-btn <?php echo $isAllActive ? 'active' : ''; ?>">Semua</button>
            </a>
        </li>

        <?php foreach ($categories as $key => $label): 
            $params = [];
            if ($search !== '') $params['q'] = $search;
            $params['category'] = $key;
            $href = build_query($params);
            $active = ($category === $key);
        ?>
        <li>
            <a href="<?php echo htmlspecialchars($href, ENT_QUOTES); ?>">
                <button class="cat-btn <?php echo $active ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($label, ENT_QUOTES); ?>
                </button>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div style="padding: 0 20px;">

        <div class="product">
            <?php
            // Jika tidak ada hasil, tampilkan pesan
            if ($result && $result->num_rows === 0) {
                echo '<p>Tidak ditemukan produk yang sesuai.</p>';
            } elseif ($result) {
                // Loop aman dengan $result
                while ($card = $result->fetch_assoc()) :
                    // Pastikan nama file gambar aman
                    $imageFile = !empty($card['image']) ? htmlspecialchars($card['image'], ENT_QUOTES) : 'placeholder.png';
                    // Ubah path jika gambar berada di folder uploads/ bukan assets/
                    $imagePath = 'assets/' . $imageFile;
            ?>

            <div class="product-card">

                <a href="detail_produk.php?id=<?php echo (int)$card['id']; ?>">
                    <img src="<?php echo $imagePath; ?>" 
                        alt="<?php echo htmlspecialchars($card['name'], ENT_QUOTES); ?>">
                    <h3><?php echo htmlspecialchars($card['name'], ENT_QUOTES); ?></h3>

                    <p class="price">
                        Rp <?php echo number_format($card['price'], 0, ',', '.'); ?>
                    </p>
                </a>
                
                <button>beli sekarang</button>
                <button class="btn add-to-cart" data-id="<?php echo (int)$card['id']; ?>">
                    🛒
                </button>


            </div>


            <?php
                endwhile;
            } else {
                echo '<p>Terjadi kesalahan saat mengambil produk.</p>';
            }

            // Bebaskan resource result jika ada
            if (isset($result) && $result instanceof mysqli_result) {
                $result->free();
            }
            ?>
        </div>
    </div>

    <script>
    function updateCartCount() {
        fetch("cart/cart_count.php")
            .then(response => response.text())
            .then(data => {
                const el = document.getElementById("cart-count");
                if (el) el.innerText = data;
            })
            .catch(err => {
                console.error('Gagal mengambil cart count:', err);
            });
    }

    updateCartCount();

    document.addEventListener('click', function(e) {
        if (e.target && e.target.matches('.add-to-cart')) {
            let productId = e.target.dataset.id;
            let yakin = confirm("Yakin ingin menambahkan produk ini ke keranjang?");
            if (!yakin) return;

            fetch("cart/cart_add.php?id=" + encodeURIComponent(productId))
                .then(response => response.text())
                .then(data => {
                    alert("Produk berhasil ditambahkan ke keranjang!");
                    updateCartCount();
                })
                .catch(err => {
                    console.error('Gagal menambah keranjang:', err);
                    alert('Terjadi kesalahan saat menambahkan ke keranjang.');
                });
        }
    });
    </script>
</body>
</html>
