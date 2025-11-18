<?php
include 'conn.php';
session_start();

// pastikan ada parameter id
if (!isset($_GET['id'])) {
  echo "Produk tidak ditemukan.";
  exit();
}

$id = (int)$_GET['id'];

// ambil data produk berdasarkan ID (cast int untuk keamanan)
$query = $conn->query("SELECT * FROM products WHERE id = $id");

if ($query->num_rows == 0) {
  echo "Produk tidak ditemukan.";
  exit();
}

$product = $query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Produk - <?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?></title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 30px;
      background: #f4f4f4;
    }
    .container {
      display: flex;
      gap: 40px;
      background: white;
      padding: 20px;
      border-radius: 10px;
      max-width: 900px;
      margin: auto;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    .image-box img {
      width: 350px;
      border-radius: 10px;
    }
    .info {
      flex: 1;
    }
    .info h1 {
      margin: 0;
      font-size: 26px;
    }
    .price {
      font-size: 24px;
      font-weight: bold;
      margin: 15px 0;
      color: #d9534f;
    }
    .desc {
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 20px;
    }
    .btn {
      display: inline-block;
      padding: 10px 18px;
      background: #0275d8;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-size: 16px;
      border: none;
      cursor: pointer;
    }
    .btn:hover {
      background: #025aa5;
    }
    .back {
      text-decoration: none;
      display: inline-block;
      margin-top: 20px;
      color: #555;
      border: 1px solid black;
      padding: 6px 10px;
      border-radius: 6px;
      background: #fff;
    }

    /* quantity input */
    .qty-box {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-right: 12px;
    }
    .qty-box input[type="number"] {
      width: 70px;
      padding: 8px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ccc;
      text-align: center;
    }
    .qty-btn {
      padding: 6px 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      background: #f0f0f0;
      cursor: pointer;
    }
    .qty-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .stock {
      margin-top: 6px;
      font-size: 16px;
      color: #333;
    }

    .small { font-size: 13px; color:#666; }
  </style>
</head>
<body>

<a class="back" href="home.php">← Kembali ke Daftar Produk</a> 

<div class="container"> 
  
  <!-- gambar -->
  <div class="image-box">
    <img src="assets/<?php echo htmlspecialchars($product['image'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>">
  </div>

  <!-- detail produk -->
  <div class="info">
    <h1><?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?></h1>

    <?php
    // Load average rating and count
    $rating_q = mysqli_query($conn, "SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM `product_reviews` WHERE product_id = " . (int)$product['id']);
    $rating_data = mysqli_fetch_assoc($rating_q);
    $avg_rating = $rating_data && $rating_data['avg_rating'] ? round($rating_data['avg_rating'],1) : 0;
    $rating_count = $rating_data ? (int)$rating_data['cnt'] : 0;
    ?>

    <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
    <?php if ($rating_count > 0): ?>
      <br><small class="small">Rating: <?= number_format($avg_rating,1) ?> / 5 (<?= $rating_count ?>)</small>
    <?php else: ?>
      <br><small class="small">Belum ada rating</small>
    <?php endif; ?>
    </p>

    <?php $stock = isset($product['stock']) ? (int)$product['stock'] : 0; ?>

    <p class="stock">
      <?php if ($stock > 0): ?>
        Stok: <strong><?php echo $stock; ?></strong>
      <?php else: ?>
        <span style="color:red; font-weight:bold;">Stok Habis</span>
      <?php endif; ?>
    </p>

    <p class="desc"><?php echo nl2br(htmlspecialchars($product['description'], ENT_QUOTES)); ?></p>

    <!-- Quantity + Add to cart -->
    <div style="margin-top:12px;">
      <div class="qty-box" id="qty-box">
        <button type="button" class="qty-btn" id="qty-decrease" aria-label="Kurangi">-</button>
        <input type="number" id="qty-input" name="qty" value="1" min="1" step="1" />
        <button type="button" class="qty-btn" id="qty-increase" aria-label="Tambah">+</button>
      </div>

      <button class="btn add-to-cart" id="add-to-cart-btn" data-id="<?php echo (int)$product['id']; ?>">
        Tambahkan ke Keranjang
      </button>
    </div>

    <br><br>

  </div>

</div>

<script>
    // updateCartCount same as before
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

    // Quantity logic
    (function(){
        const stock = <?php echo json_encode($stock); ?>;
        const qtyInput = document.getElementById('qty-input');
        const btnInc = document.getElementById('qty-increase');
        const btnDec = document.getElementById('qty-decrease');
        const addBtn = document.getElementById('add-to-cart-btn');

        // set max to stock and initial value
        if (qtyInput) {
            qtyInput.setAttribute('max', stock > 0 ? stock : 1);
            if (stock === 0) qtyInput.value = 0;
            else if (parseInt(qtyInput.value) < 1) qtyInput.value = 1;
            else if (parseInt(qtyInput.value) > stock) qtyInput.value = stock;
        }

        // disable add button if no stock
        if (stock === 0) {
            addBtn.disabled = true;
            addBtn.style.opacity = 0.7;
            // optional: hide qty controls
            // document.getElementById('qty-box').style.display = 'none';
        }

        // helpers
        function clampQty(val) {
            val = parseInt(val) || 0;
            if (val < 1) val = 1;
            if (stock > 0 && val > stock) val = stock;
            return val;
        }

        btnInc && btnInc.addEventListener('click', function(){
            let v = clampQty(qtyInput.value) + 1;
            if (stock > 0 && v > stock) v = stock;
            qtyInput.value = v;
        });

        btnDec && btnDec.addEventListener('click', function(){
            let v = clampQty(qtyInput.value) - 1;
            if (v < 1) v = 1;
            qtyInput.value = v;
        });

        // validate manual input
        qtyInput && qtyInput.addEventListener('input', function(){
            let v = qtyInput.value.replace(/[^\d]/g,'');
            qtyInput.value = v;
        });

        qtyInput && qtyInput.addEventListener('change', function(){
            let v = clampQty(qtyInput.value);
            qtyInput.value = v;
        });

        // Add to cart click
        document.addEventListener('click', function(e) {
            if (e.target && e.target.matches('.add-to-cart')) {
                e.stopPropagation();

                const productId = e.target.dataset.id;
                let qty = parseInt(qtyInput.value) || 1;

                // validate before sending
                if (stock === 0) {
                    alert('Stok habis.');
                    return;
                }
                if (qty < 1) qty = 1;
                if (qty > stock) qty = stock;

                let yakin = confirm("Yakin ingin menambahkan " + qty + " item ke keranjang?");
                if (!yakin) return;

                // disable button while request running
                e.target.disabled = true;

                // Kirim ke cart_add.php dengan parameter qty
                fetch("cart/cart_add.php?id=" + encodeURIComponent(productId) + "&qty=" + encodeURIComponent(qty))
                    .then(response => response.text())
                    .then(data => {
                        alert("Produk berhasil ditambahkan ke keranjang!");
                        updateCartCount();
                    })
                    .catch(err => {
                        console.error('Gagal menambah keranjang:', err);
                        alert('Terjadi kesalahan saat menambahkan ke keranjang.');
                    })
                    .finally(() => {
                        if (!e.target.hasAttribute('data-perm')) e.target.disabled = false;
                    });
            }
        });
    })();
</script>

</body>
</html>
