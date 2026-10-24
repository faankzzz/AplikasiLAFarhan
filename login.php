<?php
require_once 'config/database.php';
// Jika sudah login, redirect ke dashboard yang sesuai
if (isLoggedIn()) {
    $role = trim(getUserRole());
    if (empty($role)) {
        session_destroy();
        redirect('login.php');
    }

    match($role) {
        'super_admin' => redirect('super_admin/dashboard.php'),
        'admin_barang' => redirect('admin_stok/dashboard.php'),
        'admin_penjualan' => redirect('admin_penjualan/dashboard.php'),
        'sopir' => redirect('sopir/dashboard.php'),
        'konsumen' => redirect('index.php'),
        default => (function() {
            session_destroy();
            redirect('login.php');
        })()
    };
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] === 'konsumen' && $user['role_customer'] === 'toko' && $user['status_verifikasi'] === 'pending') {
                $error = 'Akun toko Anda masih menunggu verifikasi admin.';
            } elseif ($user['role'] === 'konsumen' && $user['role_customer'] === 'toko' && $user['status_verifikasi'] === 'rejected') {
                $error = 'Maaf, pendaftaran akun toko Anda ditolak oleh admin.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role_customer'] = $user['role_customer'] ?? '';
                
                match($user['role']) {
                    'super_admin' => redirect('super_admin/dashboard.php'),
                    'admin_barang' => redirect('admin_stok/dashboard.php'),
                    'admin_penjualan' => redirect('admin_penjualan/dashboard.php'),
                    'sopir' => redirect('sopir/dashboard.php'),
                    'konsumen' => redirect('index.php'),
                    default => (function() {
                        session_destroy();
                        redirect('login.php');
                    })()
                };
            }
        } else {
            $error = 'Email atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1d4ed8;
            --accent: #38bdf8;
            --bg: #d1e8f5;
            --surface: #e8f4fc;
            --card: #c8e4f5;
            --text: #1e293b;
            --muted: #64748b;
            --border: #4a90c4;
            --radius: 22px;
            --grad-blue: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            --shadow: 0 10px 25px rgba(30, 64, 175, .08);
            --font-heading: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: #d1e8f5;
            color: #1e293b;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ========================= LAYOUT ========================= */
        .login-container {
            display: flex;
            min-height: 100vh;
        }

        /* ========================= LEFT SIDE ========================= */
        .login-left {
            flex: 1;
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(rgba(15, 23, 42, .78), rgba(15, 23, 42, .78)),
                url('assets/css/gunung.jpg') center/cover no-repeat;
            padding: 60px 8%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
        }

        .login-left::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(56, 189, 248, .18) 0%, transparent 70%);
            top: -120px;
            left: -120px;
            border-radius: 50%;
        }

        .info-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .info-header-row {
            display: flex;
            align-items: center;
            gap: 36px;
            margin-bottom: 28px;
            width: 100%;
        }

        @media (max-width: 1200px) {
            .info-header-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 24px;
            }
        }

        .logo-wrapper {
            background: white;
            border-radius: 18px; /* Clean rounded corners */
            width: 200px;       /* Sleek rectangular width */
            height: 80px;       /* Sleek rectangular height */
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            animation: float 6s ease-in-out infinite;
            flex-shrink: 0;
            overflow: hidden;   /* Clip the cropped areas */
        }

        .logo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;  /* Automatically crops top & bottom white margins of the square logo */
            display: block;
        }

        .info-title {
            font-family: 'Outfit', sans-serif;
            font-size: 52px;
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .info-title span { color: #38bdf8; }

        .info-sub {
            font-size: 17px;
            color: rgba(255, 255, 255, .72);
            max-width: 480px;
            line-height: 1.6;
        }

        /* ========================= RIGHT SIDE ========================= */
        .login-right {
            flex: 1;
            width: 100%;
            background: #e8f4fc; /* Ice blue soft background */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 50px;
            position: relative;
        }

        .form-wrap {
            width: 100%;
            max-width: 420px;
        }

        .mobile-logo {
            display: none;
        }

        /* ========================= FORM HEAD ========================= */
        .form-head {
            text-align: center;
            margin-bottom: 36px;
        }

        .form-head h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .form-head p {
            color: #64748b;
            font-size: 15px;
        }

        /* ========================= ALERT ========================= */
        .alert {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #e11d48;
            padding: 14px 18px;
            border-radius: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        /* ========================= FORM ========================= */
        .form-group { margin-bottom: 20px; }

        .label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #64748b;
        }

        .forgot-link {
            font-size: 13px;
            text-decoration: none;
            color: #1e40af;
            font-weight: 700;
        }

        .forgot-link:hover { text-decoration: underline; }

        .input-field { position: relative; }

        /* ✅ FIX: Icon di kiri input — pakai left absolute dengan padding benar */
        .input-field i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
            transition: color .3s;
            z-index: 1;
        }

        .input {
            width: 100%;
            padding: 16px 18px 16px 50px;
            border-radius: 16px;
            border: 1.5px solid #cbd5e1; /* Soft gray-blue border to avoid any blue lines by default */
            background: #f8fafc;
            font-family: inherit;
            font-size: 15px;
            font-weight: 500;
            color: #0f172a;
            outline: none;
            transition: .3s;
        }

        .input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .input:focus {
            border-color: #1e40af;
            background: white;
            box-shadow: 0 0 0 4px rgba(30, 64, 175, .08);
        }

        /* ========================= CHECKBOX ========================= */
        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 26px;
            cursor: pointer;
        }

        .checkbox-row input {
            width: 17px;
            height: 17px;
            accent-color: #1e40af;
            cursor: pointer;
        }

        .checkbox-row span {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
        }

        /* ========================= BUTTON ========================= */
        .btn-login {
            width: 100%;
            border: none;
            padding: 16px;
            border-radius: 16px;
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            color: white;
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: .3s;
            box-shadow: 0 10px 24px rgba(37, 99, 235, .28);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px rgba(37, 99, 235, .38);
        }

        .btn-login:active { transform: translateY(0); }

        .btn-register-secondary {
            width: 100%;
            padding: 15px;
            border-radius: 16px;
            border: 1.5px solid #cbd5e1; /* Soft gray border */
            background: #f8fafc;
            color: #1e293b;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: .3s;
        }

        .btn-register-secondary:hover {
            background: white;
            border-color: #1e40af;
            color: #1e40af;
        }

        /* ========================= DIVIDER ========================= */
        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 24px 0;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #cbd5e1;
        }

        /* ========================= FOOTER ========================= */
        .form-footer {
            margin-top: 22px;
            text-align: center;
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            transition: .3s;
        }

        .back-home:hover { color: #1e40af; }

        /* ========================= ANIMATION ========================= */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        /* ========================= RESPONSIVE ========================= */
        @media (max-width: 1100px) {
            .login-left { display: none; }

            .login-right {
                max-width: 100%;
                padding: 40px 24px;
            }

            .mobile-logo {
                display: flex;
                justify-content: center;
                margin-bottom: 28px;
            }

            .mobile-logo img {
                width: 180px;
                height: auto;
            }
        }

        @media (max-width: 480px) {
            .login-right { padding: 30px 18px; }
            .form-head h2 { font-size: 26px; }

            .input {
                padding: 14px 16px 14px 46px;
                font-size: 14px;
            }

            .btn-login {
                padding: 14px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">

    <!-- LEFT SIDE -->
    <div class="login-left">
        <div class="info-content">

            <div class="info-header-row">
                <div class="logo-wrapper">
                    <img src="assets/css/logoo.png" alt="ALFA ONE Logo">
                </div>
                <h1 class="info-title">Senantiasa Mengalir <span>Bersama.</span></h1>
            </div>
            <p class="info-sub">Tetap Terhidrasi dan Sehat dengan Air Minum Berkualitas, 100% Murni & Berkualitas Tinggi.</p>

        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="login-right">
        <div class="form-wrap">

            <div class="mobile-logo">
                <img src="assets/css/logoo.png" alt="ALFA ONE">
            </div>

            <div class="form-head">
                <h2>Selamat Datang!</h2>
                <p>Masuk ke akun Anda untuk melanjutkan</p>
            </div>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <div class="label-row">
                        <label class="label">Alamat Email</label>
                    </div>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="input" placeholder="contoh@email.com" required value="<?= htmlspecialchars($email ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label class="label">Kata Sandi</label>
                        <a href="https://wa.me/6281532853333?text=Halo%20Admin,%20saya%20lupa%20password%20akun%20saya%20di%20Alfa%20One" target="_blank" class="forgot-link">Lupa Password?</a>
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="input" placeholder="Masukkan kata sandi" required>
                    </div>
                </div>

                <label class="checkbox-row">
                    <input type="checkbox" name="remember">
                    <span>Ingat Saya</span>
                </label>

                <button type="submit" class="btn-login">
                    <i class="fas fa-right-to-bracket"></i> Masuk Sekarang
                </button>
            </form>

            <div class="divider">atau</div>

            <a href="register.php" class="btn-register-secondary">
                <i class="fas fa-user-plus"></i> Belum punya akun? Daftar sekarang
            </a>

            <div class="form-footer">
                <a href="index.php" class="back-home">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>

        </div>
    </div>

</div>

</body>
</html>