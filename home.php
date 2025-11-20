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
        --nav-height: 64px;
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
    button { font-family: inherit; cursor: pointer; border: none; background: none; }

    /* ===== NAVBAR ===== */
    .site-header{
        position:sticky; top:0; z-index:90;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(18,18,18,0.04);
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }
    .nav-inner{
        max-width:var(--max-w); margin:0 auto; padding:8px 12px; height:var(--nav-height);
        display:flex; align-items:center; justify-content:space-between; gap:16px;
        flex-wrap:wrap;
    }
    /* left area containing only brand now */
    .nav-left{ display:flex; align-items:center; gap:12px; flex:0 1 auto; min-width:0; }
    .brand{ display:flex; align-items:center; gap:10px; flex-shrink:0; }
    .brand-logo{ width:52px; height:52px; border-radius:10px; overflow:hidden; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,var(--accent-2),var(--accent)); box-shadow:0 6px 18px rgba(255,105,180,0.08); }
    .brand-logo img{ width:100%; height:100%; object-fit:cover; display:block; }
    .brand-title{ font-weight:800; font-size:18px; color:var(--accent); line-height:1; }
    .brand-sub{ font-size:12px; color:#8a8a8a; margin-top:2px; }

    /* removed nav-links visually; keep class if present elsewhere */
    .nav-links{ display:none; }

    /* RIGHT ACTIONS (search + cart + profile) - search moved into actions so it sits beside cart */
    .nav-actions{ display:flex; gap:10px; align-items:center; justify-content:flex-end; margin-left:auto; margin-right:0; padding-right:4px; flex-shrink:0; }

    /* cart: icon only (use svg if available) */
    .cart-btn{
        position:relative;
        width:44px;
        height:44px;
        border-radius:50%;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        background:#fff;
        border:2px solid var(--accent);
        color:var(--accent);
        font-size:18px;
        cursor:pointer;
        transition: transform .12s ease, background .12s ease, color .12s ease;
        box-shadow: 0 6px 18px rgba(255,105,180,0.06);
    }
    .cart-btn img{ width:20px; height:20px; display:block; }
    .cart-btn:hover, .cart-btn:focus {
        background:var(--accent);
        color:#fff;
        transform:translateY(-3px);
        outline:none;
    }
    .cart-count{
        position:absolute; top:-6px; right:-6px; background:var(--accent); color:#fff; font-size:12px; padding:4px 7px; border-radius:999px; box-shadow:0 6px 14px rgba(255,77,148,0.12);
        line-height:1;
    }

    /* profile icon button */
    .profile-btn{ width:44px; height:44px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; background:#fff; border:2px solid var(--accent); color:var(--accent); font-size:18px; cursor:pointer; transition: transform .12s ease, background .12s ease, color .12s ease; box-shadow: 0 6px 18px rgba(255,105,180,0.06); position:relative; }
    .profile-btn img{ width:20px; height:20px; border-radius:50%; display:block; object-fit:cover; }
    .profile-btn:hover, .profile-btn:focus { background:var(--accent); color:#fff; transform:translateY(-3px); outline:none; }

    /* dropdown */
    .profile-dropdown{ position:absolute; top:calc(var(--nav-height) + 8px); right:6px; min-width:180px; background:#fff; border-radius:12px; box-shadow:0 14px 40px rgba(18,18,18,0.12); border:1px solid rgba(0,0,0,0.04); padding:6px; display:none; z-index:120; }
    .profile-dropdown.show{ display:block; }
    .profile-dropdown a { display:flex; gap:10px; padding:10px 12px; align-items:center; border-radius:8px; color:#222; font-weight:600; text-decoration:none; }
    .profile-dropdown a:hover, .profile-dropdown a:focus { background: rgba(255,77,148,0.06); color:var(--accent); outline:none; }

    /* mobile menu */
    .mobile-menu{ display:none; width:100%; background:var(--glass); padding:12px 18px; border-top:1px solid rgba(0,0,0,0.03) }

    /* SEARCH now designed to sit inside .nav-actions */
    .nav-search { display:flex; align-items:center; gap:8px; position:relative; margin-left:0; }
    .search-input { width:220px; max-width: calc(100vw - 260px); padding:8px 12px; border-radius:10px; border:1px solid #ececec; background:#fff; box-shadow: 0 4px 10px rgba(14,14,14,0.03); transition: width .24s ease, box-shadow .18s ease, border-color .18s ease; font-size:14px; outline:none; }
    .search-input:focus, .nav-search.expanded .search-input { width:320px; box-shadow: 0 12px 28px rgba(18,18,18,0.06); border-color: rgba(255,77,148,0.12); }
    .nav-search button[type="submit"]{ padding:8px 12px; border-radius:10px; background:var(--accent); color:#fff; font-weight:700; cursor:pointer; box-shadow:0 8px 20px rgba(255,77,148,0.06); }

    /* rest of page (products etc) */
    main.container{ max-width:var(--max-w); margin:18px auto; padding:8px 18px 60px; }
    .categories{ display:flex; flex-wrap:wrap; gap:10px; margin:8px 0 18px; padding:0; list-style:none; }
    .cat-btn{ padding:8px 14px; border-radius:999px; border:1px solid #ffdfe8; background:#fff; color:var(--accent); cursor:pointer; font-weight:600; font-size:14px; }
    .cat-btn.active{ background:var(--accent); color:#fff; border-color:var(--accent) }

    .product { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; align-items:stretch; }
    .product-card{ background:#fff; border-radius:14px; box-shadow:var(--card-shadow); overflow:hidden; display:flex; flex-direction:column; transition: transform .18s ease, box-shadow .18s ease; min-height:420px; }
    .product-card:hover{ transform:translateY(-6px); box-shadow:0 20px 40px rgba(255,105,180,0.08) }
    .img-wrap{ position:relative; aspect-ratio:4/3; overflow:hidden; background:linear-gradient(180deg,#fff7fa 0%, #fff0f5 100%); display:flex; align-items:center; justify-content:center; }
    .img-wrap img{ width:100%; height:100%; object-fit:cover; display:block; transition: transform .45s ease; }
    .product-card:hover .img-wrap img{ transform:scale(1.05); }

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

    .toast { position: fixed; right: 20px; bottom: 20px; background: var(--toast-bg); color: #fff; padding: 10px 14px; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.3); z-index: 9999; opacity: 0; transform: translateY(8px); transition: opacity .22s ease, transform .22s ease; pointer-events: none; font-weight:700; }
    .toast.show { opacity: 1; transform: translateY(0); pointer-events: auto; }

    /* RESPONSIVE */
    @media (max-width:900px){
        .nav-links{ display:none; }
        .nav-search { flex:1; justify-content:flex-end; }
        .search-input { width:140px; }
        .search-input:focus, .nav-search.expanded .search-input { width:100%; max-width: 480px; }
        .profile-dropdown { right:8px; top: 60px; }
        .mobile-menu{ display:block; }
    }

    /* MOBILE SMALLER LAYOUT (improved) */
    @media (max-width:440px){
        .nav-inner {
            flex-wrap:wrap;
            align-items:center;
            padding:8px 10px;
            gap:8px;
            height: auto;
        }

        .nav-left {
            order: 1;
            width: auto;
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }
        .brand-title { font-size: 15px; }
        .brand-sub { display: none; }

        /*
         * New mobile ordering:
         *  - Search on the left (takes remaining width)
         *  - Cart button to the right of search
         *  - Profile button after cart (farthest right)
         *
         * We keep desktop styles untouched; only change orders and sizing on very small screens.
         */

        /* actions container spans full width so we can control internal alignment */
        .nav-actions {
            order: 2;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between; /* search left, controls right */
            gap: 8px;
            padding: 0;
        }

        /* search becomes flexible and sits at left */
        .nav-search {
            order: 1;
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        .nav-search form { width: 100%; display:flex; gap:8px; align-items:center; }
        /* make input take remaining space (left) */
        .search-input { width: 100%; max-width: none; padding:8px 10px; font-size:14px; }

        /* cart + profile grouped to the right */
        .cart-btn { order: 2; width:40px; height:40px; flex: 0 0 auto; }
        .profile-wrapper { order: 3; flex: 0 0 auto; display: flex; align-items: center; }
        .profile-btn { order: 3; width:40px; height:40px; }

        .cart-count { top: -6px; right: -6px; padding: 3px 6px; font-size: 11px; }

        .mobile-menu { display: none; }

        .img-wrap { aspect-ratio: 16/9; }
        .product { grid-template-columns: repeat(1, 1fr); }
    }

    @media (max-width:700px){
        .product{ grid-template-columns: repeat(2, 1fr); }
    }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <header class="site-header" role="banner">
        <div class="nav-inner">
            <div class="nav-left" aria-hidden="false">
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
            </div>

            <!-- RIGHT ACTIONS: search moved here so it sits beside the cart -->
            <div class="nav-actions" role="group" aria-label="Aksi">
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

                <!-- Cart: icon-only using svg if present, otherwise fallback to emoji -->
                <button class="cart-btn" id="cartBtn" onclick="location.href='cart/cart.php'" aria-label="Buka keranjang">
                    <?php if (file_exists('assets/icon-cart.svg')): ?>
                        <img src="assets/icon-cart.svg" alt="Keranjang">
                    <?php else: ?>
                        🛒
                    <?php endif; ?>
                    <span id="cart-count" class="cart-count" aria-live="polite">0</span>
                </button>

                <!-- Profile: image on button (use icon-user.svg or profile placeholder) -->
                <div class="profile-wrapper" style="position:relative;">
                    <button id="profileBtn" class="profile-btn" aria-haspopup="true" aria-expanded="false" aria-controls="profileDropdown" title="Akun">
                        <?php if (file_exists('assets/icon-user.svg')): ?>
                            <img src="assets/icon-user.svg" alt="Akun">
                        <?php elseif (file_exists('assets/profile.png')): ?>
                            <img src="assets/profile.png" alt="Akun">
                        <?php else: ?>
                            👤
                        <?php endif; ?>
                    </button>

                    <div id="profileDropdown" class="profile-dropdown" role="menu" aria-labelledby="profileBtn">
                        <a href="home.php" role="menuitem" tabindex="0">🏠 Home</a>
                        <a href="order/order_history.php" role="menuitem" tabindex="0">📦 Pesanan Saya</a>
                        <a href="auth/logout.php" role="menuitem" tabindex="0">🔓 Logout</a>
                    </div>
                </div>
            </div>

            <!-- Optional mobile menu (kept for fallback) -->
            <div id="mobileMenu" class="mobile-menu" role="region" aria-hidden="true">
                <div style="display:flex;flex-direction:column;gap:8px">
                    <a href="home.php" style="padding:10px;border-radius:8px;">Home</a>
                    <a href="order/order_history.php" style="padding:10px;border-radius:8px;">Pesanan Saya</a>
                    <a href="cart/cart.php" style="padding:10px;border-radius:8px;">Keranjang</a>
                    <a href="auth/logout.php" style="padding:10px;border-radius:8px;">Logout</a>
                </div>
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
                                <img src="<?php echo $imagePath; ?>"
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
                                <div>
                                    <div class="price">Rp <?php echo $price; ?></div>
                                </div>
                                <?php if (!empty($card['old_price']) && $card['old_price'] > $card['price']): ?>
                                    <div class="old-price">Rp <?php echo number_format($card['old_price'],0,',','.'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="card-actions">
                                <button class="btn btn-primary" onclick="location.href='checkout/checkout.php?product_id=<?php echo (int)$card['id']; ?>'">Beli sekarang</button>

                                <button class="btn btn-ghost icon-btn add-to-cart" data-id="<?php echo (int)$card['id']; ?>" aria-label="Tambah ke keranjang">
                                    🛒
                                </button>
                            </div>
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

    // NAV SEARCH expand/contract
    (function(){
        const navSearchWrap = document.getElementById('navSearch');
        const searchInput = navSearchWrap ? navSearchWrap.querySelector('.search-input') : null;

        if (searchInput && navSearchWrap) {
            searchInput.addEventListener('focus', () => navSearchWrap.classList.add('expanded'));
            searchInput.addEventListener('blur', () => {
                setTimeout(() => navSearchWrap.classList.remove('expanded'), 120);
            });

            document.addEventListener('click', (e) => {
                if (!navSearchWrap.contains(e.target)) {
                    navSearchWrap.classList.remove('expanded');
                }
            });
        }
    })();

    // PROFILE DROPDOWN: toggle, close on outside click / Esc
    (function(){
        const profileBtn = document.getElementById('profileBtn');
        const dropdown = document.getElementById('profileDropdown');

        if (!profileBtn || !dropdown) return;

        function openDropdown() {
            dropdown.classList.add('show');
            profileBtn.setAttribute('aria-expanded', 'true');
            const first = dropdown.querySelector('[role="menuitem"]');
            if (first) first.focus();
        }
        function closeDropdown() {
            dropdown.classList.remove('show');
            profileBtn.setAttribute('aria-expanded', 'false');
            profileBtn.focus();
        }
        function toggleDropdown() {
            if (dropdown.classList.contains('show')) closeDropdown();
            else openDropdown();
        }

        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDropdown();
        });

        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && e.target !== profileBtn) {
                if (dropdown.classList.contains('show')) closeDropdown();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (dropdown.classList.contains('show')) closeDropdown();
            }
        });

        dropdown.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const target = document.activeElement;
                if (target && target.getAttribute('role') === 'menuitem') {
                    target.click();
                }
            }
        });
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
