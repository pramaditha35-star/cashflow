<?php
// login.php
require_once 'config/database.php';
require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
checkGuest();

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Pendaftaran akun berhasil! Silakan masuk dengan akun baru Anda.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT * FROM user WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] == 1) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['status'] = $user['status'];
                    
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Akun Anda dinonaktifkan. Silakan hubungi administrator.';
                }
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (\PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cash Flow System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">

<div class="login-container">
    <div class="login-header">
        <h1>Cash Flow System</h1>
        <p>Silakan masuk ke akun Anda</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert-success-login">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="input-control" placeholder="Masukkan username" required autocomplete="username" autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="input-control" placeholder="Masukkan password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-submit">Masuk Aplikasi</button>
    </form>

    <div class="footer-note">
        Belum punya akun? <a href="register.php">Daftar Sekarang</a>
    </div>
</div>

</body>
</html>
