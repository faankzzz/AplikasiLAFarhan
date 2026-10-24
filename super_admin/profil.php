<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('super_admin');
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
                        // Hapus foto lama jika ada
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
                $success = 'Kata sandi berhasil diubah!';
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
    <title>Profil Akun — Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      :root {
        --primary: #2563eb; --bg: #f0f6fa; --surface: #ffffff; --text: #1e293b; --muted: #64748b; --border: rgba(59, 130, 246, 0.12);
        --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); --sidebar-text: #f8fafc; --sidebar-muted: #94a3b8;
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif;
      }
      /* ===== SIDEBAR UNIFIED ===== */
      .sidebar { width:280px; background:linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; position: fixed; top:0; left:0; height: 100vh; z-index: 50; overflow-y: auto; }
      .sidebar-logo { display:flex; align-items:center; justify-content:center; padding:20px 24px; border-bottom:1px solid rgba(255,255,255,0.05); }
      .sidebar-profile { padding:24px 32px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; align-items:center; gap:12px; text-align:center; }
      .sidebar-avatar { width:64px; height:64px; border-radius:20px; background: rgba(255,255,255,0.08); color:white; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:800; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15); overflow:hidden; }
      .sidebar-avatar img { width:100%; height:100%; object-fit:cover; }
      .sidebar-user-info { display:flex; flex-direction:column; gap:4px; }
      .sidebar-user-name { font-weight:800; font-size:15px; color:white; }
      .sidebar-user-role { font-size:11px; font-weight:800; color: #38bdf8; text-transform:uppercase; letter-spacing:0.5px; }
      .sidebar-nav { padding:24px 16px; flex:1; overflow-y:auto; }
      .nav-label { font-size:11px; color: #38bdf8; text-transform:uppercase; letter-spacing:.15em; margin:20px 16px 8px; font-weight:800; }
      .nav-item { display:flex; align-items:center; gap:16px; padding: 14px 18px; border-radius: 20px; color: #94a3b8; font-size:15px; transition: .3s ease; text-decoration:none; font-weight: 700; margin-bottom: 6px; }
      .nav-item i { font-size:18px; width:22px; text-align:center; }
      .nav-item:hover { background:rgba(255,255,255,0.05); color:white; }
      .nav-item.active { background:linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color:white; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: 'Inter', sans-serif; background:#f0f6fa; color:#1e293b; min-height:100vh; overflow-x:hidden; }
      .layout { display:flex; min-height:100vh; width: 100%; }

      .main { flex:1; margin-left: 280px; padding: 40px; }
      .btn { padding: 14px 24px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; border: none; border-radius: 12px; font-family: 'Outfit', sans-serif; font-weight: 700; cursor: pointer; display: inline-block; text-decoration: none; transition: .3s; }
      .btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4); }
      .form-group { margin-bottom: 20px; }
      .form-group label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; }
      .form-group input { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.12); font-family: 'Inter', sans-serif; font-size: 14px; }
      .form-group input:focus { border-color: #2563eb; outline: none; }
      .card { background: #ffffff; padding: 32px; border-radius: 32px; border: 1px solid rgba(59, 130, 246, 0.12); margin-bottom: 32px; }
      .card-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; margin-bottom: 24px; color: #1e293b; display: flex; align-items: center; gap: 12px; }
      
      .alert { padding: 16px; border-radius: 16px; margin-bottom: 24px; font-weight: 700; border: 1px solid rgba(59, 130, 246, 0.12); }
      .alert-success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
      .alert-danger { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); z-index: 1000; transition: transform .3s; box-shadow: 20px 0 40px rgba(0,0,0,0.1); }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; padding: 15px; }
        .header { height: auto !important; min-height: 80px; padding: 15px 0; flex-direction: column; align-items: flex-start !important; gap: 15px; margin-bottom: 25px !important; }
        .header > div:first-child { width: 100%; justify-content: space-between !important; }
        .header > div:last-child { display: none; } /* Hide clock on mobile */
        
        .card { padding: 24px 15px; border-radius: 28px; }
        .form-grid { grid-template-columns: 1fr !important; gap: 0 !important; }
        .avatar-sec { flex-direction: column; gap: 20px !important; align-items: center !important; text-align: center; }
        .btn { width: 100%; }
        
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 999; }
        .sidebar-overlay.active { display: block; }
        .hamburger { display: flex !important; width: 40px; height: 40px; align-items: center; justify-content: center; background: #f1f5f9; border: 1px solid rgba(59, 130, 246, 0.12); border-radius: 12px; cursor: pointer; color: #1e293b; font-size: 18px; margin-bottom: 0; }
      }

      .hamburger { display: none; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo" style="justify-content: center; padding: 24px 0;"><img src="../assets/css/logoo.png" alt="ALFA ONE" style="width: 200px; height: auto; object-fit: contain;"></div>
        
        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <?php if (!empty($user['foto'])): ?>
                    <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                <?php else: ?>
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="sidebar-user-role">Super Admin</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-th-large nav-icon"></i> Dashboard</a>
            <a href="users.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users-cog nav-icon"></i> Manajemen Pegawai</a>
            <a href="konsumen.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'konsumen.php' ? 'active' : '' ?>"><i class="fas fa-users nav-icon"></i> Daftar Konsumen</a>
            
            <div class="nav-label">Monitoring</div>
            <a href="pesanan.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'pesanan.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart nav-icon"></i> Semua Pesanan</a>
            <a href="inventori.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'inventori.php' ? 'active' : '' ?>"><i class="fas fa-boxes nav-icon"></i> Inventori Barang</a>
            <a href="pengiriman.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'pengiriman.php' ? 'active' : '' ?>"><i class="fas fa-truck-fast nav-icon"></i> Lacak Pengiriman</a>
            
            <div class="nav-label">Akun</div>
            <a href="profil.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>"><i class="fas fa-user-circle nav-icon"></i> Profil Akun</a>
            <a href="../logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt nav-icon"></i> Keluar</a>
        </nav>
    </aside>

    <main class="main">
        <header class="header" style="height: 80px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="hamburger" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </div>
                <div>
                    <h1 style="font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; margin: 0;">Pengaturan Profil</h1>
                    <p style="color: #64748b; font-weight: 500; font-size: 14px; margin: 0;">Kelola informasi akun dan keamanan Anda.</p>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Super Admin</div>
            </div>
        </header>

        <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <div class="card">
            <h3 class="card-title"><i class="fas fa-user"></i> Data Diri</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <div class="avatar-sec" style="display: flex; align-items: center; gap: 30px; margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px dashed rgba(59, 130, 246, 0.12);">
                    <div class="sidebar-avatar" style="width: 100px; height: 100px; font-size: 40px; border-radius: 30px;">
                        <?php if (!empty($user['foto'])): ?>
                            <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                        <?php else: ?>
                            <i class="fas fa-user-shield"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px;">Foto Profil</label>
                        <input type="file" name="foto" accept="image/*" style="font-size: 13px; color: #64748b;">
                        <p style="font-size: 11px; color: #64748b; margin-top: 8px;">Format: JPG, PNG atau WebP. Maks 2MB.</p>
                    </div>
                </div>
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>NAMA LENGKAP</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>NOMOR TELEPON</label>
                        <input type="text" name="telepon" value="<?= htmlspecialchars($user['telepon'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>EMAIL</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <button type="submit" class="btn">Simpan Perubahan</button>
            </form>
        </div>

        <div class="card">
            <h3 class="card-title"><i class="fas fa-lock"></i> Ubah Kata Sandi</h3>
            <form method="POST">
                <input type="hidden" name="update_password" value="1">
                <div class="form-group">
                    <label>KATA SANDI LAMA</label>
                    <input type="password" name="old_pwd" required>
                </div>
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>KATA SANDI BARU</label>
                        <input type="password" name="new_pwd" placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="form-group">
                        <label>KONFIRMASI KATA SANDI</label>
                        <input type="password" name="confirm_pwd" required>
                    </div>
                </div>
                <button type="submit" class="btn" style="background: #0f172a;">Perbarui Kata Sandi</button>
            </form>
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
