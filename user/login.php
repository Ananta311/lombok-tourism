<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) { header('Location: ../index.php'); exit; }

$error = '';
$redirect = sanitize($_GET['redirect'] ?? '../index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = sanitize($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Username/email dan password wajib diisi.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            $dest = ($user['role'] === 'admin') ? '../admin/dashboard.php' : $redirect;
            header("Location: $dest");
            exit;
        } else {
            $error = 'Username/email atau password salah.';
        }
    }
}
$pageTitle = 'Masuk - Lombok Tourism';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="auth-page">
    <!-- Left Panel -->
    <div class="auth-left">
        <div class="auth-left-content">
            <h2>Selamat Datang Kembali di Lombok Tourism</h2>
            <p style="opacity:.8;line-height:1.7;margin:16px 0 32px">
                Masuk untuk mengakses fitur lengkap: beri rating, tulis komentar, dan upload foto pengalaman wisata Anda.
        </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-right">
        <div class="auth-form-wrap">
            <div class="auth-logo">
                <div class="logo-icon"><i class="fas fa-compass"></i></div>
                <span>Lombok Tourism</span>
            </div>
            <h1 class="auth-title">Masuk ke Akun</h1>
            <p class="auth-subtitle">Masukkan kredensial Anda untuk melanjutkan</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Akun berhasil dibuat! Silakan login.
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <div class="form-group">
                    <label class="form-label">Username / Email <span>*</span></label>
                    <div class="input-group">
                        <input type="text" name="login" class="form-control"
                               placeholder="Masukkan username atau email"
                               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                               required style="padding-left:44px">
                        <i class="fas fa-user" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--gray-400)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex;justify-content:space-between">
                        <span>Password <span style="color:var(--danger)">*</span></span>
                        <a href="#" style="font-size:.82rem;color:var(--blue-600);font-weight:500">Lupa password?</a>
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control"
                               placeholder="Masukkan password" required>
                        <button type="button" class="input-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="remember">
                        <span style="font-size:.88rem;color:var(--gray-600)">Ingat saya selama 30 hari</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary"
                        style="width:100%;justify-content:center;padding:14px;font-size:1rem">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>

            <div class="auth-divider">atau</div>

            <p class="auth-link">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
            <p class="auth-link" style="margin-top:10px">
                <a href="../index.php"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
            </p>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>