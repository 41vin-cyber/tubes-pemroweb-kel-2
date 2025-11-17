<?php
session_start();
include '../conn.php';

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' ");
    $user = mysqli_fetch_assoc($query);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] == "admin") {
                header("Location: ../admin/dasboard.php");
            } else {
                header("Location: ../home.php");
            }
            exit();
    }
    $error = true;
}

?>

<?php
session_start();
include '../conn.php';

$error = false;
$error_msg = "";

// Tangani login
if(isset($_POST['login'])){
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = true;
        $error_msg = "Username dan password wajib diisi.";
    } else {
        // Prepared statement untuk keamanan
        if ($stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1")) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] == "admin") {
                    header("Location: ../admin/dasboard.php");
                } else {
                    header("Location: ../home.php");
                }
                exit();
            } else {
                $error = true;
                $error_msg = "Username atau password salah.";
            }
        } else {
            $error = true;
            $error_msg = "Gagal memproses login (server).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
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

    /* Card dibuat lebih besar */
    .login-card{
      width:100%;
      max-width:480px;
      background:white;
      border-radius:var(--radius);
      padding:55px;
      box-shadow:0 10px 30px rgba(0,0,0,0.08);
      border:1px solid rgba(0,0,0,0.04);
    }

    /* Header lebih besar */
    .login-card h1{
      margin:0 0 8px;
      font-size:1.9rem;
      line-height:1.05;
      text-align: center;
      padding: 20px;
    }
    .login-card .subtitle{
      margin:0 0 18px;
      color:var(--muted);
      font-size:1.05rem;
    }

    /* Form: jarak antar elemen diperbesar */
    form ul{list-style:none;padding:0;margin:0;display:grid;gap:var(--gap)}
    label{display:block;font-size:1rem;margin-bottom:8px;color:#333}
    .input-wrap{position:relative}
    input[type="text"],input[type="password"]{
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

    /* Toggle show password (ikon SVG) */
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
    .toggle-pw svg{ width:20px; height:20px; display:block; }

    /* Tombol lebih besar */
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

    /* Bantuan / link */
    .help-text{margin-top:14px;text-align:center;color:var(--muted);font-size:1rem}
    .help-text a{color:var(--pink);text-decoration:none;font-weight:700}

    /* Error */
    .error{background:#fff0f6;color:#b0003a;padding:12px;border-radius:10px;border:1px solid rgba(176,0,58,0.06);font-size:1rem;margin-bottom:12px}

    /* Responsive */
    @media (max-width:520px){
      .login-card{ padding:24px; max-width:460px; }
      .login-card h1{ font-size:1.6rem; }
      input[type="text"], input[type="password"]{ padding:12px 14px; font-size:1rem; }
      button[type="submit"]{ padding:12px; font-size:1rem; }
    }

    @media (max-width:380px){
      .login-card{ padding:18px; max-width:360px; border-radius:10px; }
      .login-card h1{ font-size:1.4rem; }
      form ul{ gap:12px }
      input[type="text"], input[type="password"]{ padding:10px 12px; }
      button[type="submit"]{ padding:11px; font-size:0.98rem; }
    }
    input:focus-visible{ outline:none; box-shadow:var(--focus); }
  </style>
</head>
<body>
  <div class="container">
    <main class="login-card" role="main" aria-labelledby="loginTitle">
      <h1 id="loginTitle">LOGIN</h1>
      <p class="subtitle">Masuk untuk melanjutkan ke akun Anda</p>

      <!-- PHP error message dari server -->
      <?php if($error): ?>
        <div class="error" role="alert"><?php echo htmlspecialchars($error_msg); ?></div>
      <?php endif; ?>

      <form id="loginForm" action="" method="post" novalidate>
        <ul>
          <li>
            <label for="username">Username :</label>
            <div class="input-wrap">
              <input type="text" name="username" id="username" autocomplete="username" inputmode="text" required>
            </div>
          </li>

          <li>
            <label for="password">Password :</label>
            <div class="input-wrap" style="position:relative">
              <input type="password" name="password" id="password" autocomplete="current-password" required>
              <!-- Toggle eye button with SVG icons -->
              <button type="button" class="toggle-pw" id="togglePw" aria-pressed="false" aria-label="Tampilkan password">
                <!-- eye (visible) -->
                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7z" stroke="#ff6aa6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3" stroke="#ff6aa6" stroke-width="2"/>
                </svg>
                <!-- eye-off (hidden by default) -->
                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" style="display:none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-5 0-9.27-3.11-11-7a19.26 19.26 0 0 1 5.06-6.36m3.07-1.3A9.94 9.94 0 0 1 12 5c5 0 9.27 3.11 11 7a19.61 19.61 0 0 1-4.3 5.47M1 1l22 22" stroke="#ff6aa6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          </li>

          <li>
            <button type="submit" name="login">Login</button>
          </li>
        </ul>
      </form>

      <p class="help-text">Belum punya akun? <a href="register.php">Register</a></p>
    </main>
  </div>

  <script>
    (function () {
      var form = document.getElementById('loginForm');
      var username = document.getElementById('username');
      var password = document.getElementById('password');
      var toggle = document.getElementById('togglePw');
      var submitBtn = form.querySelector('button[type="submit"]');

      // Toggle password & swap SVG icons
      toggle.addEventListener('click', function () {
        var eye = toggle.querySelector('.icon-eye');
        var eyeOff = toggle.querySelector('.icon-eye-off');

        if (password.type === 'password') {
          password.type = 'text';
          toggle.setAttribute('aria-pressed', 'true');
          if (eye) eye.style.display = 'none';
          if (eyeOff) eyeOff.style.display = 'block';
          toggle.setAttribute('aria-label','Sembunyikan password');
        } else {
          password.type = 'password';
          toggle.setAttribute('aria-pressed', 'false');
          if (eye) eye.style.display = 'block';
          if (eyeOff) eyeOff.style.display = 'none';
          toggle.setAttribute('aria-label','Tampilkan password');
          password.focus();
        }
      });

      // Simple client-side validation + friendly error display
      form.addEventListener('submit', function (e) {
        // remove previous client errors
        var prev = form.querySelector('.client-error');
        if (prev) prev.remove();

        var errors = [];
        if (!username.value.trim()) errors.push('Username tidak boleh kosong.');
        if (!password.value.trim()) errors.push('Password tidak boleh kosong.');
        if (password.value && password.value.length > 0 && password.value.length < 6) errors.push('Password minimal 6 karakter.');

        if (errors.length) {
          e.preventDefault();
          var box = document.createElement('div');
          box.className = 'error client-error';
          box.setAttribute('role','alert');
          // buat list pesan
          box.innerHTML = errors.map(function(s){ return '<div>• ' + s + '</div>'; }).join('');
          form.prepend(box);

          // fokus ke field pertama yang bermasalah
          if (!username.value.trim()) username.focus();
          else password.focus();

          return false;
        }

        // disable tombol submit untuk mencegah klik ganda
        submitBtn.disabled = true;
        submitBtn.innerText = 'Memproses...';
      });

      // enable/disable submit based on input
      function updateSubmitState(){
        submitBtn.disabled = !(username.value.trim() && password.value.trim() && password.value.length >= 6);
      }
      username.addEventListener('input', updateSubmitState);
      password.addEventListener('input', updateSubmitState);
      updateSubmitState();
    })();
  </script>
</body>
</html>