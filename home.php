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
// NOTE: exclude products with category = 'fragrance' entirely
$sql = "SELECT * FROM products WHERE category <> 'fragrance'";

if (!empty($search)) {
    $sql .= " AND (name LIKE '$searchSafe' OR description LIKE '$searchSafe')";
}

if (!empty($category)) {
    // still allow filtering by other categories
    $sql .= " AND category = '$categorySafe'";
}

$sql .= " ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query gagal: " . mysqli_error($conn));
}

// categories list: fragrance removed
$categories = [
    'makeup' => 'Makeup',
    'skincare' => 'Skincare',
    'haircare' => 'Haircare',
    'bodycare' => 'Bodycare',
    'nailcare' => 'Nailcare'
];

// path to placeholder image (make sure this file exists: assets/placeholder.png)
$placeholder = 'assets/placeholder.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Daftar Produk - Beauty Shop</title>

    <style>
    /* ====== VARIABLES & RESET ====== */
    :root{
        --max-w: 1180px;
        --bg: #f8f8fa;
        --accent: #ff4d94;
        --accent-2: #ff77b7;
        --muted: #6b6b6b;
        --card-shadow: 0 6px 18px rgba(255,105,180,0.08);
        --radius: 12px;
        --gap: 18px;
        --nav-height: 64px; /* lowered for cleaner layout */
        --glass: rgba(255,255,255,0.9);
        --toast-bg: rgba(0,0,0,0.8);
        --transition-fast: 140ms;
        --transition-medium: 220ms;
    }

    *, *::before, *::after { box-sizing: border-box; }
    html,body{
        height:100%;
        margin:0;
        font-family:"Poppins", Arial, sans-serif;
        background:var(--bg);
        color:#222;
        line-height:1.35;
        -webkit-font-smoothing:antialiased;
        -moz-osx-font-smoothing:grayscale;
    }
    a { color: inherit; text-decoration: none; }
    button { font-family: inherit; cursor: pointer; }

    /* ===== NAVBAR - OPTION A (CLEAN LIGHT) ===== */
    .site-header{
        position:sticky; top:0; z-index:90;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(18,18,18,0.04);
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }
    .nav-inner{
        max-width:var(--max-w); margin:0 auto; padding:8px 18px; height:var(--nav-height);
        display:flex; align-items:center; justify-content:space-between; gap:16px;
    }
    .nav-left{ display:flex; align-items:center; gap:12px; flex:1; min-width:0; }
    .brand{ display:flex; align-items:center; gap:10px; flex-shrink:0; }
    .brand-logo{ width:52px; height:52px; border-radius:10px; overflow:hidden; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,var(--accent-2),var(--accent)); box-shadow:0 6px 18px rgba(255,105,180,0.08); }
    .brand-logo img{ width:100%; height:100%; object-fit:cover; display:block; }
    .brand-title{ font-weight:800; font-size:18px; color:var(--accent); line-height:1; }
    .brand-sub{ font-size:12px; color:#8a8a8a; margin-top:2px; }

    .nav-links{ display:flex; gap:8px; align-items:center; margin-left:6px; flex-wrap:wrap; overflow:hidden; }
    /* subtle text links for clean look */
    .nav-links a{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        padding:8px 12px;
        border-radius:10px;
        background:transparent;
        border:none;
        color:#444;
        font-weight:600;
        font-size:14px;
        white-space:nowrap;
        transition: color .15s ease, background .15s ease, transform .12s ease;
    }
    .nav-links a:focus { outline:3px solid rgba(255,77,148,0.10); outline-offset:2px; border-radius:8px; }
    .nav-links a:hover{
        color: var(--accent);
        background: rgba(255,77,148,0.06);
        transform: translateY(-2px);
    }

    .nav-actions{ display:flex; gap:10px; align-items:center; justify-content:flex-end; min-width:0; }

    /* cart as gentle pill (ghost) */
    .cart-btn{
        position:relative;
        background: transparent;
        border:2px solid var(--accent);
        color:var(--accent);
        font-weight:700;
        font-size:14px;
        padding:8px 12px;
        border-radius:999px;
        display:flex;
        align-items:center;
        gap:8px;
        cursor:pointer;
        transition: background .15s ease, color .15s ease, transform .12s ease;
    }
    .cart-btn:hover{
        background: var(--accent);
        color: #fff;
        transform: translateY(-2px);
    }
    .cart-btn:focus{ outline:3px solid rgba(255,77,148,0.10); outline-offset:2px; }

    .cart-count{
        position:absolute; top:-6px; right:-6px; background:var(--accent); color:#fff; font-size:12px; padding:4px 7px; border-radius:999px; box-shadow:0 6px 14px rgba(255,77,148,0.12);
        line-height:1;
    }

    .mobile-menu{ display:none; width:100%; background:var(--glass); padding:12px 18px; border-top:1px solid rgba(0,0,0,0.03) }

    /* ===== NAV SEARCH ===== */
    .nav-search {
      display:flex;
      align-items:center;
      gap:8px;
      position:relative;
      margin-left:6px;
      flex-shrink:0;
    }

    .nav-search form{
      display:flex;
      align-items:center;
      gap:8px;
    }

    .search-input {
      width:180px;
      max-width: calc(100vw - 340px);
      padding:8px 12px;
      border-radius:10px;
      border:1px solid #ececec;
      background:#fff;
      box-shadow: 0 4px 10px rgba(14,14,14,0.03);
      transition: width .24s ease, box-shadow .18s ease, border-color .18s ease;
      font-size:14px;
      outline: none;
    }

    .search-input:focus,
    .nav-search.expanded .search-input {
      width:320px;
      box-shadow: 0 12px 28px rgba(18,18,18,0.06);
      border-color: rgba(255,77,148,0.12);
    }

    /* search button uses accent solid for visibility */
    .nav-search button[type="submit"]{
      padding:8px 12px;
      border-radius:10px;
      border: none;
      background:var(--accent);
      color:#fff;
      font-weight:700;
      cursor:pointer;
      box-shadow:0 8px 20px rgba(255,77,148,0.06);
      transition: transform .12s ease, opacity .12s ease;
    }
    .nav-search button[type="submit"]:hover{ transform: translateY(-2px); }

    /* ===== NAV-LOGOUT (ICON) ===== */
    .nav-logout{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:42px;
        height:42px;
        border-radius:50%;
        background:#fff;
        border:2px solid var(--accent);
        color:var(--accent);
        font-size:18px;
        text-decoration:none;
        transition: transform var(--transition-fast), background var(--transition-fast), color var(--transition-fast), box-shadow var(--transition-fast);
        box-shadow: 0 6px 18px rgba(255,105,180,0.06);
    }
    .nav-logout:hover,
    .nav-logout:focus {
        background: var(--accent);
        color: #fff;
        transform: translateY(-3px);
        outline: none;
        box-shadow: 0 18px 40px rgba(255,77,148,0.12);
    }
    .nav-logout:focus {
        box-shadow: 0 0 0 4px rgba(255,77,148,0.08);
    }

    /* ===== MAIN ===== */
    main.container{ max-width:var(--max-w); margin:18px auto; padding:8px 18px 60px; }

    .categories{ display:flex; flex-wrap:wrap; gap:10px; margin:8px 0 18px; padding:0; list-style:none; }
    .cat-btn{ padding:8px 14px; border-radius:999px; border:1px solid #ffdfe8; background:#fff; color:var(--accent); cursor:pointer; font-weight:600; font-size:14px; }
    .cat-btn.active{ background:var(--accent); color:#fff; border-color:var(--accent) }

    /* ===== PRODUCT GRID & CARD ===== */
    .product { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; align-items:stretch; }

    .product-card{
        background:#fff; border-radius:14px; box-shadow:var(--card-shadow); overflow:hidden;
        display:flex; flex-direction:column; transition: transform .18s ease, box-shadow .18s ease; min-height:420px;
    }
    .product-card:hover{ transform:translateY(-6px); box-shadow:0 20px 40px rgba(255,105,180,0.08) }

    .img-wrap{ position:relative; aspect-ratio:4/3; overflow:hidden; background:linear-gradient(180deg,#fff7fa 0%, #fff0f5 100%); display:flex; align-items:center; justify-content:center; }
    .img-wrap img{ width:100%; height:100%; object-fit:cover; display:block; transition: transform .45s ease; }
    .product-card:hover .img-wrap img{ transform:scale(1.05); }

    /* Reduced gap and tighter spacing between title and description */
    .card-body{ padding:16px; display:flex; flex-direction:column; gap:6px; flex:1 1 auto; }
    .card-title{ font-size:16px; font-weight:800; color:#222; line-height:1.2; min-height:34px; margin:0; }
    .card-desc{ font-size:13px; color:var(--muted); min-height:30px; margin:0; overflow:hidden; margin-top:4px; }

    .price-row{ display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:6px; }
    .price{ font-size:17px; font-weight:900; color:var(--accent); white-space:nowrap; }
    .old-price{ font-size:13px; color:#b4b4b4; text-decoration:line-through; }

    .card-actions{ display:flex; gap:10px; margin-top:auto; padding-top:6px; align-items:center; }
    .btn{ flex:1; padding:10px 12px; border-radius:12px; border:none; cursor:pointer; font-weight:800; font-size:14px; }
    .btn-primary{ background:var(--accent); color:#fff; box-shadow:0 8px 20px rgba(255,77,148,0.06); }
    .btn-ghost{ background:#fff; border:1px solid #ffdfe8; color:var(--accent); min-width:52px; height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; }
    .icon-btn{ width:48px; min-width:48px; height:44px; border-radius:12px; font-size:18px; }

    /* removed .cta-note usage - kept CSS removed to avoid accidental display */

    /* ===== TOAST ===== */
    .toast { position: fixed; right: 20px; bottom: 20px; background: var(--toast-bg); color: #fff; padding: 10px 14px; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.3); z-index: 9999; opacity: 0; transform: translateY(8px); transition: opacity .22s ease, transform .22s ease; pointer-events: none; font-weight:700; }
    .toast.show { opacity: 1; transform: translateY(0); pointer-events: auto; }

    /* ===== RESPONSIVE ===== */
    @media (max-width:900px){
        .nav-links{ display:none; }
        .nav-search { flex:1; justify-content:flex-end; margin-left:0; }
        .search-input { width:140px; }
        .search-input:focus,
        .nav-search.expanded .search-input { width:100%; max-width: 480px; }
    }
    @media (max-width:700px){
        .product{ grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width:440px){
        .product{ grid-template-columns: repeat(1, 1fr); }
        .img-wrap{ aspect-ratio:16/9; }
        .brand-sub{ display:none; }
        .nav-inner { flex-wrap:wrap; align-items:center; gap:8px; }
        .nav-left { order:1; width:100%; }
        .nav-actions { order:2; width:100%; justify-content:space-between; }
        .nav-search { order:2; width:100%; margin:8px 0; }
        .nav-links { order:3; width:100%; display:flex; justify-content:flex-start; gap:8px; flex-wrap:wrap; }
    }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <header class="site-header" role="banner">
        <div class="nav-inner">
            <div class="nav-left">
                <div class="brand" aria-hidden="false">
                    <div class="brand-logo" aria-hidden="true">
                        <!-- Pastikan file ini ada: assets/logo.png -->
                        <img src="assets/logo.png" alt="Beauty Shop">
                    </div>
                    <div>
                        <div class="brand-title">Beauty Shop</div>
                        <div class="brand-sub">Online Cosmetics</div>
                    </div>
                </div>

                <nav class="nav-links" role="navigation" aria-label="Menu utama">
                    <a href="home.php" role="link">Home</a>
                    <a href="order/order_history.php" role="link">Pesanan Saya</a>
                </nav>
            </div>

            <div class="nav-actions" role="group" aria-label="Aksi">
                <!-- NAV SEARCH -->
                <div class="nav-search" id="navSearch" aria-label="Pencarian produk">
                    <form method="GET" action="" role="search" aria-label="Form pencarian">
                        <?php if ($category !== ''): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES); ?>">
                        <?php endif; ?>
                        <label for="searchInput" class="sr-only" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">Cari produk</label>
                        <input
                            id="searchInput"
                            type="text"
                            name="q"
                            class="search-input"
                            placeholder="Cari produk..."
                            value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>"
                            aria-label="Cari produk">
                        <button type="submit" aria-label="Cari">Cari</button>
                    </form>
                </div>

                <button class="cart-btn" id="cartBtn" onclick="location.href='cart/cart.php'" aria-label="Buka keranjang">
                    🛒 <span style="margin-left:6px">Keranjang</span>
                    <span id="cart-count" class="cart-count" aria-live="polite">0</span>
                </button>

                <!-- NAV-LOGOUT: icon profile (acts as logout) -->
                <a href="auth/logout.php"
                   class="nav-logout"
                   role="button"
                   aria-label="Logout"
                   title="Keluar">
                    <!-- emoji; bisa diganti dengan SVG jika mau -->
                    👤
                </a>
            </div>
        </div>

        <!-- Optional mobile menu (simple list) -->
        <div id="mobileMenu" class="mobile-menu" role="region" aria-hidden="true">
            <div style="display:flex;flex-direction:column;gap:8px">
                <a href="home.php" style="padding:10px;border-radius:8px;">Home</a>
                <a href="order/order_history.php" style="padding:10px;border-radius:8px;">Pesanan Saya</a>
                <a href="cart/cart.php" style="padding:10px;border-radius:8px;">Keranjang</a>
                <a href="auth/logout.php" style="padding:10px;border-radius:8px;">Logout</a>
            </div>
        </div>
    </header>

    <main class="container" role="main">
        <!-- categories -->
        <ul class="categories" role="list">
            <?php
            function build_query($params){ return '?' . http_build_query($params); }
            $allParams = [];
            if ($search !== '') $allParams['q'] = $search;
            $allHref = build_query($allParams);
            $isAllActive = ($category === '');
            ?>
            <li style="list-style:none; display:inline-block;"><a href="<?php echo htmlspecialchars($allHref, ENT_QUOTES); ?>"><button class="cat-btn <?php echo $isAllActive ? 'active' : ''; ?>">Semua</button></a></li>

            <?php foreach ($categories as $key => $label):
                $params = [];
                if ($search !== '') $params['q'] = $search;
                $params['category'] = $key;
                $href = build_query($params);
                $active = ($category === $key);
            ?>
                <li style="list-style:none; display:inline-block; margin-left:6px;">
                    <a href="<?php echo htmlspecialchars($href, ENT_QUOTES); ?>">
                        <button class="cat-btn <?php echo $active ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($label, ENT_QUOTES); ?>
                        </button>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="product" aria-live="polite">
            <?php
            if ($result && $result->num_rows === 0) {
                echo '<p>Tidak ditemukan produk yang sesuai.</p>';
            } elseif ($result) {
                while ($card = $result->fetch_assoc()):
                    $imageFile = !empty($card['image']) ? htmlspecialchars($card['image'], ENT_QUOTES) : '';
                    $imagePath = $imageFile ? 'assets/' . $imageFile : $placeholder;
                    $shortDesc = strip_tags($card['description'] ?? '');
                    if (mb_strlen($shortDesc) > 110) $shortDesc = mb_substr($shortDesc, 0, 107) . '...';
                    $catLabel = isset($categories[$card['category']]) ? $categories[$card['category']] : ucfirst($card['category']);
                    $price = number_format($card['price'], 0, ',', '.');
                    $stock = isset($card['stock']) ? (int)$card['stock'] : null;
                    ?>
                    <article class="product-card" aria-labelledby="p-<?php echo (int)$card['id']; ?>">
                        <div class="img-wrap" role="img" aria-label="<?php echo htmlspecialchars($card['name'], ENT_QUOTES); ?>">
                            <a href="detail_produk.php?id=<?php echo (int)$card['id']; ?>" style="display:block;width:100%;height:100%;">
                                <img src="<?php echo $imagePath; ?>?"
                                     alt="<?php echo htmlspecialchars($card['name'], ENT_QUOTES); ?>"
                                     loading="lazy"
                                     onerror="this.onerror=null;this.src='<?php echo $placeholder; ?>';">
                            </a>
                        </div>

                        <div class="card-body">
                            <a href="detail_produk.php?id=<?php echo (int)$card['id']; ?>" style="text-decoration:none;color:inherit">
                                <h3 id="p-<?php echo (int)$card['id']; ?>" class="card-title"><?php echo htmlspecialchars($card['name'], ENT_QUOTES); ?></h3>
                            </a>

                            <p class="card-desc"><?php echo htmlspecialchars($shortDesc, ENT_QUOTES); ?></p>

                            <div class="price-row">
                                <div class="price">Rp <?php echo $price; ?></div>
                                <?php if (!empty($card['old_price']) && $card['old_price'] > $card['price']): ?>
                                    <div class="old-price">Rp <?php echo number_format($card['old_price'],0,',','.'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="card-actions">
                                <button class="btn btn-primary" onclick="location.href='detail_produk.php?id=<?php echo (int)$card['id']; ?>'">Beli sekarang</button>

                                <button class="btn btn-ghost icon-btn add-to-cart" data-id="<?php echo (int)$card['id']; ?>" aria-label="Tambah ke keranjang">
                                    🛒
                                </button>
                            </div>

                            <!-- "Gratis ongkir..." removed as requested -->
                        </div>
                    </article>
                <?php
                endwhile;
            } else {
                echo '<p>Terjadi kesalahan saat mengambil produk.</p>';
            }

            if (isset($result) && $result instanceof mysqli_result) {
                $result->free();
            }
            ?>
        </div>
    </main>

    <!-- TOAST -->
    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <!-- SCRIPTS -->
    <script>
    // toast helper
    function showToast(message, timeout = 2000) {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = message;
        t.classList.add('show');
        clearTimeout(t._hideTimer);
        t._hideTimer = setTimeout(() => t.classList.remove('show'), timeout);
    }

    // update cart count (calls server endpoint that returns a number)
    function updateCartCount() {
        fetch("cart/cart_count.php", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.text())
            .then(data => {
                const el = document.getElementById("cart-count");
                if (el) el.innerText = data;
            })
            .catch(err => console.error('Gagal mengambil cart count:', err));
    }
    // initial load
    updateCartCount();

    // add-to-cart (direct, no confirm)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.add-to-cart');
        if (!btn) return;
        const productId = btn.dataset.id;
        if (!productId) return;

        // visual feedback
        btn.disabled = true;
        btn.style.opacity = '0.6';

        fetch("cart/cart_add.php?id=" + encodeURIComponent(productId), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(resp => resp.text())
        .then(text => {
            showToast('Produk ditambahkan ke keranjang');
            updateCartCount();
        })
        .catch(err => {
            console.error('Gagal menambah keranjang:', err);
            showToast('Gagal menambahkan ke keranjang');
        })
        .finally(() => {
            setTimeout(() => { btn.disabled = false; btn.style.opacity = ''; }, 600);
        });
    });

    // expand/contract visual for navbar search (CSS handles animation; JS toggles class)
    (function(){
        const navSearchWrap = document.getElementById('navSearch');
        const searchInput = navSearchWrap ? navSearchWrap.querySelector('.search-input') : null;

        if (searchInput && navSearchWrap) {
            // add class on focus to trigger CSS expansion
            searchInput.addEventListener('focus', () => navSearchWrap.classList.add('expanded'));
            searchInput.addEventListener('blur', () => {
                // sedikit delay agar klik tombol submit masih bisa terjadi
                setTimeout(() => navSearchWrap.classList.remove('expanded'), 120);
            });

            // clicking outside collapses (for better UX)
            document.addEventListener('click', (e) => {
                if (!navSearchWrap.contains(e.target)) {
                    navSearchWrap.classList.remove('expanded');
                }
            });
        }
    })();

    // Accessibility: allow Enter key on product cards to open detail (for keyboard users)
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            const active = document.activeElement;
            if (active && active.closest && active.closest('.product-card')) {
                const link = active.closest('.product-card').querySelector('a[href^="detail_produk.php"]');
                if (link) link.click();
            }
        }
    });
    </script>
</body>
</html>
