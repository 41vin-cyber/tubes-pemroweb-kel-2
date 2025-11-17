<?php
// register.php
include '../conn.php';

$msg = "";
// untuk mempertahankan value form saat ada error
$old = [
    'username' => '',
    'email' => ''
];

if (isset($_POST['register'])) {
    // ambil dan trim input
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfir_password = $_POST['konfir_password'] ?? '';

    // simpan old values (untuk ditampilkan kembali di form jika error)
    $old['username'] = $username;
    $old['email'] = $email;

    // validasi sederhana + validasi format email
    if ($password !== $konfir_password) {
        $msg = "Konfirmasi password tidak sesuai";
    } elseif ($username === '' || $email === '' || $password === '') {
        $msg = "Silakan isi semua field";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Format email tidak valid!";
    } else {
        // Cek apakah email sudah digunakan (prepared statement)
        if ($stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1")) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $msg = "Email sudah digunakan!";
                $stmt->close();
            } else {
                $stmt->close();
                // Hash password dan insert (prepared statement)
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                if ($ins = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)")) {
                    $ins->bind_param("sss", $username, $email, $password_hash);
                    if ($ins->execute()) {
                        // Registrasi sukses -> redirect ke login
                        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='login.php';</script>";
                        exit;
                    } else {
                        $msg = "Gagal registrasi! (server)";
                    }
                    $ins->close();
                } else {
                    $msg = "Gagal menyiapkan query registrasi";
                }
            }
        } else {
            $msg = "Gagal menyiapkan query cek email";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Registrasi</title>

    <style>
    :root{
      --bg: #fff7fb;
      --pink: #ff6aa6;
      --muted: #666;
      --radius: 12px;
      --gap: 16px;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }

    /* Reset sederhana */
    *{box-sizing:border-box}
    html,body{height:100%;margin:0;background:var(--bg);color:#111}
    .container{
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:24px;
    }

    /* Card */
    .login-card{
      width:100%;
      max-width:480px;
      background:white;
      border-radius:var(--radius);
      padding:55px;
      box-shadow:0 10px 30px rgba(0,0,0,0.08);
      border:1px solid rgba(0,0,0,0.04);
    }

    .login-card h1{
      margin:0 0 8px;
      font-size:1.9rem;
      line-height:1.05;
      text-align: center;
      padding: 20px 0 0 0;
    }
    .login-card .subtitle{
      margin:0 0 18px;
      color:var(--muted);
      font-size:1.05rem;
      text-align:center;
    }

    form ul{list-style:none;padding:0;margin:0;display:grid;gap:var(--gap)}
    label{display:block;font-size:1rem;margin-bottom:8px;color:#333}
    .input-wrap{position:relative}
    input[type="text"],input[type="password"], input[type="email"]{
      width:100%;
      padding:14px 16px;
      border-radius:10px;
      border:1px solid #e6e6e6;
      font-size:1.05rem;
      background:#fff;
    }
    input:focus{
      outline:none;
      border-color:var(--pink);
      box-shadow:0 0 0 6px rgba(255,106,166,0.08);
    }

    /* Toggle show password (tombol bersih) */
    .toggle-pw{
      position:absolute;
      right:10px;
      top:50%;
      transform:translateY(-50%);
      background:transparent;
      border:0;
      color:var(--pink);
      cursor:pointer;
      padding:6px;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .toggle-pw svg{ display:block; width:20px; height:20px; }

    /* Tombol submit */
    button[type="submit"]{
      width:100%;
      padding:14px 16px;
      border-radius:10px;
      border:0;
      background:var(--pink);
      color:white;
      font-weight:700;
      font-size:1.05rem;
      cursor:pointer;
    }
    button[disabled]{opacity:0.6;cursor:not-allowed}

    .help-text{margin-top:14px;text-align:center;color:var(--muted);font-size:1rem}
    .help-text a{color:var(--pink);text-decoration:none;font-weight:700}

    .error, .error-box {
      background:#fff0f6;
      color:#b0003a;
      padding:12px;
      border-radius:10px;
      border:1px solid rgba(176,0,58,0.06);
      font-size:1rem;
      margin-bottom:12px;
    }

    @media (max-width:520px){
      .login-card{ padding:24px; max-width:460px; }
      .login-card h1{ font-size:1.6rem; }
      input[type="text"], input[type="password"], input[type="email"]{ padding:12px 14px; font-size:1rem; }
      button[type="submit"]{ padding:12px; font-size:1rem; }
    }

    @media (max-width:380px){
      .login-card{ padding:18px; max-width:360px; border-radius:10px; }
      .login-card h1{ font-size:1.4rem; }
      form ul{ gap:12px }
      input[type="text"], input[type="password"], input[type="email"]{ padding:10px 12px; }
      button[type="submit"]{ padding:11px; font-size:0.98rem; }
    }

    /* extra */
    input:focus-visible{ outline:none; box-shadow:var(--focus); }
    </style>
</head>
<body>
  <div class="container">
    <main class="login-card" role="main" aria-labelledby="regTitle">
      <h1 id="regTitle">REGISTRASI</h1>
      <p class="subtitle">Buat akun untuk mengakses layanan kami</p>

      <!-- Pesan server -->
      <?php if ($msg !== ""): ?>
        <p class="error" role="alert"><?php echo htmlspecialchars($msg); ?></p>
      <?php endif; ?>

      <form id="regForm" action="" method="post" novalidate>
        <ul>
          <li>
            <label for="username">Username :</label>
            <div class="input-wrap">
              <input type="text"
                     name="username"
                     id="username"
                     autocomplete="username"
                     inputmode="text"
                     required
                     minlength="3"
                     pattern="[A-Za-z0-9_]{3,}"
                     title="Username minimal 3 karakter (huruf, angka, underscore)"
                     value="<?php echo htmlspecialchars($old['username']); ?>">
            </div>
          </li>

          <li>
            <label for="email">Email :</label>
            <div class="input-wrap">
              <input type="email"
                     name="email"
                     id="email"
                     autocomplete="email"
                     required
                     value="<?php echo htmlspecialchars($old['email']); ?>">
            </div>
          </li>

          <li>
            <label for="password">Password :</label>
            <div class="input-wrap">
              <input type="password"
                     name="password"
                     id="password"
                     autocomplete="new-password"
                     required
                     minlength="6">
              <!-- tombol toggle dengan SVG (eye / eye-off) -->
              <button type="button" class="toggle-pw" data-target="password" aria-label="Tampilkan password" aria-pressed="false">
                <!-- eye (default visible icon) -->
                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7z" stroke="#ff6aa6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3" stroke="#ff6aa6" stroke-width="2"/>
                </svg>
                <!-- eye-off (disembunyikan) -->
                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" style="display:none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-5 0-9.27-3.11-11-7a19.26 19.26 0 0 1 5.06-6.36m3.07-1.3A9.94 9.94 0 0 1 12 5c5 0 9.27 3.11 11 7a19.61 19.61 0 0 1-4.3 5.47M1 1l22 22" stroke="#ff6aa6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          </li>

          <li>
            <label for="konfir_password">Konfirmasi Password :</label>
            <div class="input-wrap">
              <input type="password"
                     name="konfir_password"
                     id="konfir_password"
                     autocomplete="new-password"
                     required
                     minlength="6">
              <button type="button" class="toggle-pw" data-target="konfir_password" aria-label="Tampilkan konfirmasi password" aria-pressed="false">
                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7z" stroke="#ff6aa6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3" stroke="#ff6aa6" stroke-width="2"/>
                </svg>
                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" style="display:none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-5 0-9.27-3.11-11-7a19.26 19.26 0 0 1 5.06-6.36m3.07-1.3A9.94 9.94 0 0 1 12 5c5 0 9.27 3.11 11 7a19.61 19.61 0 0 1-4.3 5.47M1 1l22 22" stroke="#ff6aa6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          </li>

          <li>
            <button type="submit" name="register">Register</button>
          </li>

          <li>
            <p class="help-text">Sudah punya akun? <a href="login.php">Login</a> sekarang</p>
          </li>
        </ul>
      </form>
    </main>
  </div>

  <script>
  (function () {
    // Toggle show/hide password & swap icons
    document.querySelectorAll('.toggle-pw').forEach(function(btn){
      btn.addEventListener('click', function(){
        var targetId = btn.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (!input) return;

        var iconEye = btn.querySelector('.icon-eye');
        var iconEyeOff = btn.querySelector('.icon-eye-off');

        if (input.type === 'password') {
          input.type = 'text';
          btn.setAttribute('aria-pressed', 'true');
          if (iconEye) iconEye.style.display = 'none';
          if (iconEyeOff) iconEyeOff.style.display = 'block';
        } else {
          input.type = 'password';
          btn.setAttribute('aria-pressed', 'false');
          if (iconEye) iconEye.style.display = 'block';
          if (iconEyeOff) iconEyeOff.style.display = 'none';
        }
      });
    });

    // Client-side validation sebelum submit
    var form = document.getElementById('regForm');
    if (form) {
      form.addEventListener('submit', function(e){
        // bersihkan pesan error sebelumnya
        var existing = document.querySelector('.error-box');
        if (existing) existing.remove();

        var email = document.getElementById('email').value.trim();
        var password = document.getElementById('password').value;
        var konfir = document.getElementById('konfir_password').value;
        var username = document.getElementById('username').value.trim();

        // simple email regex (client-side only)
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
          e.preventDefault();
          showClientError('Format email tidak valid!');
          document.getElementById('email').focus();
          return;
        }

        if (username.length < 3) {
          e.preventDefault();
          showClientError('Username minimal 3 karakter.');
          document.getElementById('username').focus();
          return;
        }

        if (password.length < 6) {
          e.preventDefault();
          showClientError('Password minimal 6 karakter.');
          document.getElementById('password').focus();
          return;
        }

        if (password !== konfir) {
          e.preventDefault();
          showClientError('Konfirmasi password tidak sesuai.');
          document.getElementById('konfir_password').focus();
          return;
        }
      });
    }

    function showClientError(msg) {
      var formEl = document.getElementById('regForm');
      var box = document.createElement('p');
      box.className = 'error-box';
      box.innerText = msg;
      formEl.parentNode.insertBefore(box, formEl);
    }

    // Hapus error saat mengetik
    ['username','email','password','konfir_password'].forEach(function(id){
      var el = document.getElementById(id);
      if (el) el.addEventListener('input', function(){
        var err = document.querySelector('.error-box');
        if (err) err.remove();
      });
    });
  })();
  </script>
</body>
</html>