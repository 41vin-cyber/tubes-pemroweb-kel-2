<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../conn.php';

// Helper untuk menampilkan halaman "tidak ada produk" (se-tema)
function no_product_page($msg = "Tidak ada produk yang dipilih.") {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Tidak Ada Produk</title>
        <style>
            :root{
                --bg:#ffe6f2;
                --pink-light:#ffb3d9;
                --pink-mid:#ff66b3;
                --pink-dark:#d63384;
                --card-shadow: 0 8px 30px rgba(255,105,180,0.12);
                --radius:14px;
                --max-w:860px;
                --side-pad:20px;
            }
            *{box-sizing:border-box;margin:0;padding:0}
            body{font-family:"Poppins", Arial, sans-serif;background:var(--bg);padding:32px var(--side-pad);color:#222}
            .card{max-width:var(--max-w);margin:44px auto;background:#fff;padding:28px;border-radius:var(--radius);box-shadow:var(--card-shadow);text-align:center}
            .emoji{font-size:56px;margin-bottom:12px}
            h2{color:var(--pink-dark);margin-bottom:8px;font-size:22px}
            p{color:#666;margin-bottom:18px}
            .btn{display:inline-block;padding:10px 16px;border-radius:12px;background:var(--pink-mid);color:#fff;font-weight:800;text-decoration:none;transition:transform .14s}
            .btn:hover{transform:translateY(-3px);background:var(--pink-dark)}
        </style>
    </head>
    <body>
        <div class="card" role="alert" aria-live="assertive">
            <div class="emoji">🛒</div>
            <h2><?= htmlspecialchars($msg) ?></h2>
            <p><a class="btn" href="../cart/cart.php">← Kembali ke Keranjang</a></p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// MODE 1: Single product via query string (beli sekarang dari katalog)
$single_product = null;
if (isset($_GET['product_id'])) {
    $pid = intval($_GET['product_id']);
    if ($pid <= 0) no_product_page("ID produk tidak valid.");

    $stmt = mysqli_prepare($conn, "SELECT id AS product_id, name, price, stock FROM products WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $pid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if (!$res || mysqli_num_rows($res) === 0) {
        no_product_page("Produk tidak ditemukan.");
    }
    $row = mysqli_fetch_assoc($res);
    $single_product = [
        'product_id' => (int)$row['product_id'],
        'name' => $row['name'],
        'price' => (float)$row['price'],
        'quantity' => 1
    ];
}

// MODE 2: Cart mode (form posted or cart_ids param)
$cart_items = [];
if (!$single_product) {
    $selected = [];
    if (isset($_POST['selected']) && is_array($_POST['selected']) && count($_POST['selected'])>0) {
        $selected = array_map('intval', $_POST['selected']);
    } elseif (isset($_POST['cart_ids']) && trim($_POST['cart_ids']) !== '') {
        $tmp = explode(',', $_POST['cart_ids']);
        $selected = array_map('intval', $tmp);
    } elseif (isset($_GET['cart_ids']) && trim($_GET['cart_ids']) !== '') {
        $tmp = explode(',', $_GET['cart_ids']);
        $selected = array_map('intval', $tmp);
    }

    $selected = array_filter($selected, function($v){ return $v > 0; });

    if (count($selected) > 0) {
        $ids_csv = implode(',', $selected);
        $sql = "
            SELECT cart.id AS cart_id, cart.quantity, cart.product_id,
                   products.name, products.price
            FROM cart
            JOIN products ON cart.product_id = products.id
            WHERE cart.id IN ($ids_csv)
        ";
        $query = mysqli_query($conn, $sql);
        if ($query === false) {
            die("Query error: " . mysqli_error($conn));
        }
        while ($r = mysqli_fetch_assoc($query)) {
            $cart_items[] = [
                'cart_id' => (int)$r['cart_id'],
                'product_id' => (int)$r['product_id'],
                'name' => $r['name'],
                'price' => (float)$r['price'],
                'quantity' => (int)$r['quantity']
            ];
        }
    }
}

// If neither single_product nor cart_items -> show friendly message
if (!$single_product && count($cart_items) === 0) {
    no_product_page("Tidak ada produk untuk checkout.");
}

// compute totals
$total_produk = 0;
$items_for_render = [];
if ($single_product) {
    $items_for_render[] = $single_product;
    $total_produk = $single_product['price'] * $single_product['quantity'];
} else {
    foreach ($cart_items as $it) {
        $items_for_render[] = $it;
        $total_produk += $it['price'] * $it['quantity'];
    }
}

// prepare cart_ids csv for form (if any)
$cart_ids_csv = '';
if (!$single_product) {
    $cart_ids = array_map(function($i){ return intval($i['cart_id']); }, $cart_items);
    $cart_ids_csv = implode(',', $cart_ids);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Checkout</title>
    <style>
        :root{
            --max-w:860px;
            --side-pad:20px;
            --bg:#ffe6f2;
            --pink-light:#ffb3d9;
            --pink-mid:#ff66b3;
            --pink-dark:#d63384;
            --muted:#6b6b6b;
            --card-shadow:0 8px 30px rgba(255,105,180,0.12);
            --radius:14px;
            --gap:16px;
        }

        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:"Poppins", Arial, sans-serif;
            background:var(--bg);
            padding:28px var(--side-pad);
            color:#222;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }

        .container{
            max-width:var(--max-w);
            margin:0 auto;
            background:#fff;
            padding:26px;
            border-radius:var(--radius);
            box-shadow:var(--card-shadow);
        }

        h2{font-size:24px;color:var(--pink-dark);margin-bottom:8px;font-weight:800}
        h3{font-size:16px;color:var(--pink-dark);margin:18px 0 10px;font-weight:700}

        /* Desktop table */
        .product-table{width:100%;border-collapse:collapse;margin-bottom:16px}
        .product-table th{background:var(--pink-light);color:var(--pink-dark);text-align:left;padding:12px;font-weight:800}
        .product-table td{padding:12px;border-bottom:1px solid #ffdfe8;color:#222;vertical-align:middle}
        .product-table tr:hover{background:#fff0f6}

        /* Card layout for mobile */
        .item-card{
            display:none;
            background:#fff;
            border-radius:12px;
            padding:12px;
            margin-bottom:12px;
            box-shadow:0 6px 18px rgba(255,105,180,0.06);
        }
        .item-card .row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
        .item-card .label{color:var(--muted);font-weight:700}

        /* form elements */
        select, textarea {
            width:100%;
            padding:12px;
            border-radius:12px;
            border:1px solid #ffdfe8;
            font-size:14px;
            font-family:inherit;
        }
        textarea{min-height:100px;resize:vertical}

        .summary{
            background:linear-gradient(180deg,#fff7fa 0,#fff0f5 100%);
            padding:16px;border-radius:12px;margin:18px 0;border-left:6px solid var(--pink-mid);
        }
        .summary-row{display:flex;justify-content:space-between;margin-bottom:10px;font-weight:800;color:#222}
        .summary-row.total{border-top:3px solid var(--pink-light);padding-top:10px;font-size:18px;color:var(--pink-dark)}

        .btn-primary{
            display:inline-block;width:100%;padding:14px;border-radius:12px;background:var(--pink-mid);color:#fff;font-weight:800;border:none;cursor:pointer;font-size:16px;
            transition:transform .14s ease, background .14s ease;
        }
        .btn-primary:hover{transform:translateY(-3px);background:var(--pink-dark)}

        .btn-back{
            display:inline-block;margin-top:12px;padding:10px 14px;border-radius:12px;border:2px solid #ffdfe8;background:#fff;color:var(--pink-dark);text-decoration:none;font-weight:800;text-align:center;width:100%;
        }
        .btn-back:hover{background:#ffe6f7}

        .muted{color:var(--muted)}

        /* responsive: convert table -> cards */
        @media (max-width:768px){
            .product-table{display:none}
            .item-card{display:block}
            .container{padding:20px}
        }
    </style>
</head>
<body>
<div class="container" role="main" aria-labelledby="checkoutTitle">
    <h2 id="checkoutTitle">🛒 Checkout</h2>

    <form action="checkout_process.php" method="POST" novalidate>
        <?php if ($single_product): ?>
            <input type="hidden" name="mode" value="single">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($single_product['product_id']) ?>">
            <input type="hidden" name="quantity" value="<?= htmlspecialchars($single_product['quantity']) ?>">
        <?php else: ?>
            <input type="hidden" name="mode" value="cart">
            <input type="hidden" name="cart_ids" value="<?= htmlspecialchars($cart_ids_csv) ?>">
        <?php endif; ?>

        <h3>📋 Ringkasan Produk</h3>

        <!-- desktop table -->
        <table class="product-table" role="table" aria-label="Ringkasan produk">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items_for_render as $it):
                $sub = $it['price'] * $it['quantity'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($it['name']) ?></td>
                    <td>Rp <?= number_format($it['price'],0,',','.') ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td>Rp <?= number_format($sub,0,',','.') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- mobile cards -->
        <?php foreach ($items_for_render as $it):
            $sub = $it['price'] * $it['quantity'];
        ?>
            <div class="item-card" aria-hidden="false">
                <div class="row">
                    <div>
                        <div style="font-weight:800;color:var(--pink-dark);"><?= htmlspecialchars($it['name']) ?></div>
                        <div class="muted" style="font-size:13px">Rp <?= number_format($it['price'],0,',','.') ?> • Qty <?= (int)$it['quantity'] ?></div>
                    </div>
                    <div style="font-weight:800">Rp <?= number_format($sub,0,',','.') ?></div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="summary" aria-live="polite">
            <div class="summary-row">
                <span>Subtotal Produk:</span>
                <span id="subtotalDisplay">Rp <?= number_format($total_produk,0,',','.') ?></span>
            </div>
        </div>

        <input type="hidden" id="baseTotal" value="<?= htmlspecialchars($total_produk) ?>">

        <h3>💳 Metode Pembayaran</h3>
        <div style="margin-bottom:12px">
            <select name="metode_pembayaran" required aria-required="true">
                <option value="">-- Pilih Metode --</option>
                <option value="Transfer Bank">Transfer Bank</option>
                <option value="E-Wallet">E-Wallet</option>
                <option value="COD">COD (Bayar Ditempat)</option>
            </select>
        </div>

        <h3>📍 Alamat Pengiriman</h3>
        <div style="margin-bottom:12px">
            <textarea name="alamat" placeholder="Masukkan alamat pengiriman lengkap Anda..." required aria-required="true"></textarea>
        </div>

        <h3>🚚 Jenis Pengiriman</h3>
        <div style="margin-bottom:12px">
            <select name="pengiriman" id="pengiriman" onchange="updateTotal()" required aria-required="true">
                <option value="">-- Pilih Jenis Pengiriman --</option>
                <option value="Reguler|20000">Reguler (+ Rp 20.000) - 3-5 hari</option>
                <option value="Express|35000">Express (+ Rp 35.000) - 1-2 hari</option>
                <option value="Kargo|50000">Kargo (+ Rp 50.000) - Same Day</option>
            </select>
        </div>

        <div class="summary" aria-label="Rincian biaya">
            <div class="summary-row">
                <span>Subtotal Produk:</span>
                <span id="subtotalDisplay_bottom">Rp <?= number_format($total_produk,0,',','.') ?></span>
            </div>
            <div class="summary-row">
                <span>Ongkir:</span>
                <span id="ongkirDisplay">Rp 0</span>
            </div>
            <div class="summary-row">
                <span>Administrasi:</span>
                <span id="adminDisplay">Rp 5.000</span>
            </div>
            <div class="summary-row total">
                <span>Total Pembayaran:</span>
                <span id="finalTotal">Rp <?= number_format($total_produk + 20000 + 5000,0,',','.') ?></span>
            </div>
        </div>

        <input type="hidden" name="total_final" id="total_final_input" value="<?= htmlspecialchars($total_produk + 20000 + 5000) ?>">

        <button type="submit" class="btn-primary">💳 Bayar Sekarang</button>

        <a href="<?= $single_product ? '../' : '../cart/cart.php' ?>" class="btn-back" style="display:block;margin-top:12px">← Kembali</a>
    </form>
</div>

<script>
function updateTotal() {
    let subtotal = Number(document.getElementById('baseTotal').value) || 0;
    let adminFee = 5000;
    let pengirimanEl = document.getElementById('pengiriman');
    let shippingCost = 0;
    if (pengirimanEl && pengirimanEl.value) {
        let parts = pengirimanEl.value.split("|");
        if (parts.length > 1) shippingCost = Number(parts[1]) || 0;
    }
    let finalTotal = subtotal + shippingCost + adminFee;

    const format = (n) => n.toLocaleString('id-ID');

    const subEls = document.querySelectorAll('#subtotalDisplay, #subtotalDisplay_bottom');
    subEls.forEach(e => e.innerText = "Rp " + format(subtotal));

    document.getElementById('ongkirDisplay').innerText = "Rp " + format(shippingCost);
    document.getElementById('adminDisplay').innerText = "Rp " + format(adminFee);
    document.getElementById('finalTotal').innerText = "Rp " + format(finalTotal);

    const hidden = document.getElementById('total_final_input');
    if (hidden) hidden.value = finalTotal;
}

document.addEventListener('DOMContentLoaded', function(){
    updateTotal();
});
</script>
</body>
</html>
