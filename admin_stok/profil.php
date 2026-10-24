<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_barang');
$user = getCurrentUser($pdo);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');

        if (empty($nama) || empty($email)) {
            $error = 'Nama dan Email wajib diisi!';
        } else {
            // Cek email duplikat
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error = 'Email sudah digunakan oleh akun lain!';
            } else {
                $foto_path = $user['foto'];
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $new_foto = uploadFile($_FILES['foto'], 'profiles');
                    if ($new_foto) {
                        if (!empty($user['foto'])) {
                            @unlink(UPLOAD_DIR . $user['foto']);
                        }
                        $foto_path = $new_foto;
                    } else {
                        $error = 'Format file tidak didukung atau upload gagal.';
                    }
                }

                if (!$error) {
                    $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, telepon = ?, foto = ? WHERE id = ?");
                    if ($stmt->execute([$nama, $email, $telepon, $foto_path, $user['id']])) {
                        $success = 'Profil berhasil diperbarui!';
                        $user['nama'] = $nama;
                        $user['email'] = $email;
                        $user['telepon'] = $telepon;
                        $user['foto'] = $foto_path;
                        $_SESSION['nama'] = $nama;
                    } else {
                        $error = 'Gagal memperbarui profil.';
                    }
                }
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $old_pwd = $_POST['old_pwd'] ?? '';
        $new_pwd = $_POST['new_pwd'] ?? '';
        $confirm_pwd = $_POST['confirm_pwd'] ?? '';

        if (empty($old_pwd) || empty($new_pwd) || empty($confirm_pwd)) {
            $error = 'Semua field kata sandi wajib diisi!';
        } elseif (!password_verify($old_pwd, $user['password'])) {
            $error = 'Kata sandi lama salah!';
        } elseif (strlen($new_pwd) < 6) {
            $error = 'Kata sandi baru minimal 6 karakter!';
        } elseif ($new_pwd !== $confirm_pwd) {
            $error = 'Konfirmasi kata sandi baru tidak cocok!';
        } else {
            $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hash, $user['id']])) {
                $success = 'Kata sandi berhasil diubah! Silakan gunakan kata sandi baru saat login selanjutnya.';
                $user['password'] = $hash;
            } else {
                $error = 'Gagal mengubah kata sandi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Akun — Admin Barang</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --secondary: #38bdf8;
        --bg: #f0f6fa;
        --surface: #ffffff;
        --text: #0f172a;
        --muted: #64748b;
        --border: rgba(59, 130, 246, 0.12);
        --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        --sidebar-text: #f8fafc;
        --sidebar-muted: #94a3b8;
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);

        --font-heading: 'Outfit', sans-serif;
        --font-body: 'Inter', sans-serif;
      }
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: 'Inter', sans-serif; background:#f0f6fa; color:#0f172a; min-height:100vh; overflow-x:hidden; -webkit-font-smoothing: antialiased; line-height: 1.6; }
      
      .layout { display:flex; min-height:100vh; }
      .sidebar { width:280px; background:linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right:1px solid rgba(255,255,255,0.03); display:flex; flex-direction:column; flex-shrink:0; position: fixed; height: 100vh; z-index: 100; box-shadow: 10px 0 40px rgba(15, 23, 42, 0.05); }
      .sidebar-logo { font-family: 'Outfit', sans-serif; font-size:24px; font-weight:800; color:white; padding:32px; border-bottom:1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 12px; letter-spacing: -0.5px; }
      .sidebar-logo i { color: #38bdf8; font-size: 24px; }
      .sidebar-profile { padding: 24px 32px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; }
      .sidebar-avatar { width: 64px; height: 64px; border-radius: 20px; background: rgba(255,255,255,0.08); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15); overflow: hidden; border: 1px solid rgba(255,255,255,0.1); }
      .sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
      .sidebar-user-info { display: flex; flex-direction: column; gap: 2px; }
      .sidebar-user-name { font-weight: 800; font-size: 16px; color: white; }
      .sidebar-user-role { font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; }

      .sidebar-nav { padding: 24px 16px; flex: 1; overflow-y: auto; }
      .nav-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .15em; margin: 20px 16px 8px; font-weight: 800; }
      .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 20px; cursor: pointer; color: #94a3b8; font-size: 15px; transition: .3s ease; margin-bottom: 6px; text-decoration: none; font-weight: 700; }
      .nav-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
      .nav-item.active { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }
      
      .main { flex:1; margin-left: 280px; display: flex; flex-direction: column; min-height: 100vh; }
      .topbar { height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: #ffffff; border-bottom: 1px solid rgba(59, 130, 246, 0.12); position: sticky; top: 0; z-index: 10; }
      .topbar-title h2 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #1e40af; letter-spacing: -0.5px; }
      
      .user-pill { display: flex; align-items: center; gap: 12px; padding: 8px 20px; background: #f1f5f9; border-radius: 50px; }
      .user-avatar { width: 32px; height: 32px; border-radius: 10px; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; overflow: hidden; }
      .user-avatar img { width: 100%; height: 100%; object-fit: cover; }

      .content { padding: 40px; width: 100%; }
      
      .card { background: #ffffff; border-radius: 32px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); border-top: 3px solid #38bdf8; box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.04); margin-bottom: 24px; }
      .card-header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); }
      .card-title { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; }
      .card-title i { color: #2563eb; }
      
      .form-group { margin-bottom: 24px; }
      .label { display: block; font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px; }
      .input { width: 100%; padding: 14px 20px; border-radius: 16px; border: 1px solid rgba(59, 130, 246, 0.12); background: #f8fafc; font-family: inherit; font-size: 16px; font-weight: 600; outline: none; transition: .3s; }
      .input:focus { border-color: #2563eb; background: #c8e4f5; box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.08); }
      
      .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
      
      .btn { padding: 14px 28px; border-radius: 16px; border: none; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer; transition: .3s; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
      .btn-primary { background: #2563eb; color: white; box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.3); }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.4); }
      
      .alert { padding: 16px 20px; border-radius: 16px; margin-bottom: 24px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 12px; }
      .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
      .alert-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fee2e2; }      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); z-index: 1000; transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 20px 0 40px rgba(0,0,0,0.1); }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; padding: 15px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        .user-pill { display: none; } /* Hide user pill on mobile topbar */
        
        .card { padding: 24px 15px; border-radius: 28px; }
        .form-row { grid-template-columns: 1fr; gap: 0; }
        .avatar-sec { flex-direction: column; gap: 20px !important; align-items: center !important; text-align: center; }
        .btn { width: 100%; }
        
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 999; }
        .sidebar-overlay.active { display: block; }
        .hamburger { display: flex !important; width: 40px; height: 40px; align-items: center; justify-content: center; background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); border-radius: 12px; cursor: pointer; color: #0f172a; font-size: 18px; }
      }
}

      .hamburger { display: none; }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo" style="justify-content: center;"><img src="../assets/css/logoo.png" alt="ALFA ONE" style="width: 200px; height: auto; object-fit: contain;"></div>
        
        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <?php if ($user['foto']): ?>
                    <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                <?php else: ?>
                    <i class="fas fa-truck-ramp-box"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="sidebar-user-role">Pusat Logistik</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Inventory</div>
            <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-chart-line nav-icon"></i> Dashboard</a>
            <a href="produk.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'produk.php' ? 'active' : '' ?>"><i class="fas fa-boxes-stacked nav-icon"></i> Katalog Produk</a>
            <a href="mutasi_stok.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'mutasi_stok.php' ? 'active' : '' ?>"><i class="fas fa-truck-ramp-box nav-icon"></i> Mutasi Stok</a>
            <a href="sopir.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'sopir.php' ? 'active' : '' ?>"><i class="fas fa-truck-moving nav-icon"></i> Kapasitas Sopir</a>
            
            <div class="nav-label">Akun</div>
            <a href="profil.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>"><i class="fas fa-user-circle nav-icon"></i> Profil Akun</a>
            <a href="../logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt nav-icon"></i> Keluar</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="hamburger" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="topbar-title"><h2>Pengaturan Profil</h2></div>
            </div>
            <div class="user-pill">
                <div style="text-align: right;">
                    <div style="font-weight: 800; font-size: 13px;"><?= htmlspecialchars($user['nama']) ?></div>
                    <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">ADMIN BARANG</div>
                </div>
                <div class="user-avatar">
                    <?php if (!empty($user['foto'])): ?>
                        <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                    <?php else: ?>
                        <i class="fas fa-truck-ramp-box"></i>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user"></i> Data Diri</h3>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="avatar-sec" style="display: flex; align-items: center; gap: 24px; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px dashed rgba(59, 130, 246, 0.12);">
                        <div class="sidebar-avatar" style="width: 80px; height: 80px; border-radius: 20px; font-size: 32px; background: rgba(30, 64, 175, 0.1); color: #2563eb;">
                            <?php if (!empty($user['foto'])): ?>
                                <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-truck-ramp-box"></i>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <label class="label">Foto Profil</label>
                            <input type="file" name="foto" accept="image/*" style="font-size: 13px;">
                            <p style="font-size: 11px; color: #64748b; margin-top: 5px;">Format: JPG, PNG, WebP. Maks 2MB.</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label">Nama Lengkap</label>
                            <input type="text" name="nama" class="input" value="<?= htmlspecialchars($user['nama']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="label">Nomor Telepon/WA</label>
                            <input type="text" name="telepon" class="input" value="<?= htmlspecialchars($user['telepon'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="label">Alamat Email</label>
                        <input type="email" name="email" class="input" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lock"></i> Keamanan & Kata Sandi</h3>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="update_password" value="1">
                    <div class="form-group">
                        <label class="label">Kata Sandi Lama</label>
                        <input type="password" name="old_pwd" class="input" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="label">Kata Sandi Baru</label>
                            <input type="password" name="new_pwd" class="input" placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="form-group">
                            <label class="label">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="confirm_pwd" class="input" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="background: #0f172a; box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.3);"><i class="fas fa-key"></i> Perbarui Kata Sandi</button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
    }
</script>
</body>
</html>
