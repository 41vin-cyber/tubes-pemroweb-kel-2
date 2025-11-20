<?php
include 'conn.php';
session_start();

if (!isset($_GET['id'])) {
  echo "Produk tidak ditemukan.";
  exit();
}

$id = (int)$_GET['id'];

$query = $conn->query("SELECT * FROM products WHERE id = $id");

if ($query->num_rows == 0) {
  echo "Produk tidak ditemukan.";
  exit();
}

$product = $query->fetch_assoc();

$rating_q = mysqli_query($conn, "SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM `product_reviews` WHERE product_id = " . (int)$product['id']);
$rating_data = mysqli_fetch_assoc($rating_q);
$avg_rating = $rating_data && $rating_data['avg_rating'] ? round($rating_data['avg_rating'],1) : 0;
$rating_count = $rating_data ? (int)$rating_data['cnt'] : 0;

$stock = isset($product['stock']) ? (int)$product['stock'] : 0;

function renderStars($rating) {
    $fullStar = '<svg xmlns="http://www.w3.org/2000/svg" fill="#ffd700" viewBox="0 0 24 24" width="24" height="24"><path d="M12 17.27l6.18 3.73-1.64-7.19 5.46-4.73-7.28-.62L12 2 10.27 8.46l-7.28.62 5.46 4.73-1.64 7.19z"/></svg>';
    $halfStar = '<svg xmlns="http://www.w3.org/2000/svg" fill="url(#half-grad)" viewBox="0 0 24 24" width="24" height="24"><defs><linearGradient id="half-grad"><stop offset="50%" stop-color="#ffd700"/><stop offset="50%" stop-color="#ddd"/></linearGradient></defs><path d="M12 17.27l6.18 3.73-1.64-7.19 5.46-4.73-7.28-.62L12 2z"/></svg>';
    $emptyStar = '<svg xmlns="http://www.w3.org/2000/svg" fill="#ddd" viewBox="0 0 24 24" width="24" height="24"><path d="M12 17.27l6.18 3.73-1.64-7.19 5.46-4.73-7.28-.62L12 2 10.27 8.46l-7.28.62 5.46 4.73-1.64 7.19z"/></svg>';

    $starsHTML = '';
    for ($i=1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $starsHTML .= $fullStar;
        } elseif ($rating > $i - 1 && $rating < $i) {
            $starsHTML .= $halfStar;
        } else {
            $starsHTML .= $emptyStar;
        }
    }
    return $starsHTML;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Detail Produk - <?= htmlspecialchars($product['name'], ENT_QUOTES); ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
    body {
      margin: 0; 
      font-family: 'Poppins', sans-serif;
      background: #fcfcfc;
      color: #333;
      padding: 40px 20px;
      display: flex;
      justify-content: center;
      min-height: 100vh;
    }
    .wrapper {
      max-width: 1000px;
      width: 100%;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 12px 28px rgba(244, 143, 177, 0.15);
      padding: 30px 40px;
      box-sizing: border-box;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 25px;
      text-decoration: none;
      color: #f48fb1;
      font-weight: 600;
      border: 2px solid #f48fb1;
      padding: 8px 16px;
      border-radius: 30px;
      transition: all 0.3s ease;
    }
    .back-link:hover {
      background: #f48fb1;
      color: white;
    }
    .container {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .top {
      display: flex;
      gap: 40px;
      align-items: flex-start;
    }
    .image-box {
      flex: 0 0 350px;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 24px rgba(244, 143, 177, 0.25);
      background: linear-gradient(135deg, #fdeef4, #fbe6ec);
    }
    .image-box img {
      display: block;
      width: 100%;
      border-radius: 16px;
      object-fit: cover;
      height: 350px;
    }
    .desc-label {
      font-size: 1.5rem;
      font-weight: 850;
      margin-bottom: 5px;
      background: linear-gradient(90deg, #b74168ff, #bc477b);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .desc {
      font-size: 1rem;
      line-height: 1.5;
      color: #474747ff;
      white-space: pre-line;
      margin-top: 0;
    }
    .info {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
    }
    .info h1 {
      font-size: 2.2rem;
      margin: 0 0 12px 0;
      font-weight: 700;
      color: #bc477b;
    }
    .price {
      font-size: 1.8rem;
      font-weight: 700;
      margin: 8px 0 12px;
      background: linear-gradient(90deg, #f586abff, #bc477b);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .stock {
      font-weight: 600;
      color: #444;
      margin-bottom: 5px;
    }
    .stock.out {
      color: #e57373;
      font-weight: 700;
      font-size: 1.1rem;
    }
    .rating {
      display: flex;
      align-items: center;
      margin-bottom: 18px;
      gap: 12px;
    }
    .rating-stars svg {
      width: 26px;
      height: 26px;
    }
    .rating-text {
      font-size: 1rem;
      color: #957299;
      font-weight: 600;
    }
    .qty-cart {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 8px;
    }
    .qty-label {
      font-weight: 600;
      color: #444;
      user-select: none;
      min-width: 40px;
    }
    .qty-box {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border: 1.6px solid #e0a9bb;
      border-radius: 12px;
      overflow: hidden;
      background: #fce8f1;
      user-select: none;
    }
    .qty-btn {
      background: transparent;
      border: none;
      font-weight: 900;
      font-size: 1.4rem;
      line-height: 1;
      padding: 6px 12px;
      color: #bc477b;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .qty-btn:hover:not(:disabled) {
      background: #f48fb1;
      color: white;
    }
    .qty-btn:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }
    input[type="number"] {
      width: 70px;
      border: none;
      outline: none;
      font-size: 1.1rem;
      font-weight: 600;
      text-align: center;
      background: transparent;
      color: #bc477b;
      font-family: 'Poppins', sans-serif;
      padding: 8px 0;
      -moz-appearance: textfield;
    }
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
      -webkit-appearance: none;
      margin: 0; 
    }
    .btn {
      background: #bc477b;
      color: white;
      border: none;
      padding: 12px 26px;
      font-size: 1.1rem;
      font-weight: 700;
      border-radius: 30px;
      cursor: pointer;
      transition: background 0.3s ease, box-shadow 0.3s ease;
      box-shadow: 0 6px 18px rgba(188, 71, 123, 0.45);
      flex-shrink: 0;
      white-space: nowrap;
    }
    .btn:hover:not(:disabled) {
      background: #f48fb1;
      box-shadow: 0 10px 28px rgba(244, 143, 177, 0.6);
    }
    .btn:disabled {
      background: #f7d3df;
      color: #9e748e;
      cursor: not-allowed;
      box-shadow: none;
    }
    @media (max-width: 720px) {
      .container {
        gap: 20px;
      }
      .top {
        flex-direction: column;
        align-items: center;
        gap: 20px;
      }
      .image-box {
        width: 100%;
        max-width: 350px;
        height: auto;
      }
      .image-box img {
        height: auto;
      }
      .qty-cart {
        flex-wrap: wrap;
      }
      .qty-label {
        min-width: auto;
      }
      .btn {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>
<body>

<div class="wrapper">
  <a href="home.php" class="back-link" aria-label="Kembali ke Daftar Produk">← Kembali ke Daftar Produk</a>

  <div class="container">
    <div class="top">
      <div class="image-box">
        <img src="assets/<?= htmlspecialchars($product['image'], ENT_QUOTES); ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES); ?>">
      </div>

      <div class="info">
        <h1><?= htmlspecialchars($product['name'], ENT_QUOTES); ?></h1>

        <div class="rating" aria-label="Rating produk">
          <div class="rating-stars" aria-hidden="true">
            <?= $rating_count > 0 ? renderStars($avg_rating) : '' ?>
          </div>
          <div class="rating-text">
            <?php if ($rating_count > 0): ?>
              <?= number_format($avg_rating,1) ?> / 5 (<?= $rating_count ?> ulasan)
            <?php else: ?>
              Belum ada rating
            <?php endif; ?>
          </div>
        </div>

        <div class="price" aria-label="Harga produk">Rp <?= number_format($product['price'], 0, ',', '.'); ?></div>

        <?php if ($stock > 0): ?>
        <div class="stock" aria-label="Stok tersedia">Stok: <strong><?= $stock; ?></strong></div>
        <?php else: ?>
        <div class="stock out" aria-label="Stok habis">Stok Habis</div>
        <?php endif; ?>

        <div class="qty-cart" aria-label="Jumlah pembelian produk">
          <div class="qty-label">QTY:</div>
          <div class="qty-box" id="qty-box">
            <button type="button" class="qty-btn" id="qty-decrease" aria-label="Kurangi jumlah">−</button>
            <input type="number" id="qty-input" name="qty" value="1" min="1" step="1" aria-live="polite" aria-atomic="true" aria-label="Jumlah produk">
            <button type="button" class="qty-btn" id="qty-increase" aria-label="Tambah jumlah">+</button>
          </div>

          <button class="btn add-to-cart" id="add-to-cart-btn" data-id="<?= (int)$product['id']; ?>" <?= $stock === 0 ? 'disabled' : '' ?>>
            Tambahkan ke Keranjang
          </button>
        </div>
      </div>
    </div>

    <div>
      <h2 class="desc-label">Deskripsi</h2>
      <div class="desc"><?= nl2br(htmlspecialchars($product['description'], ENT_QUOTES)); ?></div>
    </div>
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

  (function(){
      const stock = <?= json_encode($stock); ?>;
      const qtyInput = document.getElementById('qty-input');
      const btnInc = document.getElementById('qty-increase');
      const btnDec = document.getElementById('qty-decrease');
      const addBtn = document.getElementById('add-to-cart-btn');

      if (qtyInput) {
          qtyInput.setAttribute('max', stock > 0 ? stock : 1);
          if (stock === 0) qtyInput.value = 0;
          else if (parseInt(qtyInput.value) < 1) qtyInput.value = 1;
          else if (parseInt(qtyInput.value) > stock) qtyInput.value = stock;
      }

      if (stock === 0) {
          addBtn.disabled = true;
          addBtn.style.opacity = 0.7;
      }

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

      qtyInput && qtyInput.addEventListener('input', function(){
          let v = qtyInput.value.replace(/[^\d]/g,'');
          qtyInput.value = v;
      });

      qtyInput && qtyInput.addEventListener('change', function(){
          let v = clampQty(qtyInput.value);
          qtyInput.value = v;
      });

      document.addEventListener('click', function(e) {
          if (e.target && e.target.matches('.add-to-cart')) {
              e.stopPropagation();

              const productId = e.target.dataset.id;
              let qty = parseInt(qtyInput.value) || 1;

              if (stock === 0) {
                  alert('Stok habis.');
                  return;
              }
              if (qty < 1) qty = 1;
              if (qty > stock) qty = stock;

              let yakin = confirm("Yakin ingin menambahkan " + qty + " item ke keranjang?");
              if (!yakin) return;

              e.target.disabled = true;

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
