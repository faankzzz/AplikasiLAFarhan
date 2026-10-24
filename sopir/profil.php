<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('sopir');
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
    <title>Profil Akun — Sopir</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8; --bg: #f0f6fa; --surface: #ffffff; --text: #1e293b; --muted: #64748b; --border: rgba(59, 130, 246, 0.12);
        --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); --sidebar-text: #f8fafc; --sidebar-muted: #94a3b8;
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif;
      }
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: 'Inter', sans-serif; background:#f0f6fa; color:#1e293b; min-height:100vh; overflow-x:hidden; -webkit-font-smoothing: antialiased; line-height: 1.6; }
      
      .layout { display:flex; min-height:100vh; }
      
      .sidebar { width:280px; background:linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right:1px solid rgba(255,255,255,0.03); display:flex; flex-direction:column; flex-shrink:0; position: fixed; height: 100vh; z-index: 100; box-shadow: 10px 0 40px rgba(15, 23, 42, 0.05); }
      .sidebar-logo { font-family: 'Outfit', sans-serif; font-size:22px; font-weight:800; color:white; padding:32px; border-bottom:1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 12px; letter-spacing: -0.5px; }
      .sidebar-logo i { color: #38bdf8; font-size: 24px; }
      
      .sidebar-profile { padding: 24px 32px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; }
      .sidebar-avatar { width: 64px; height: 64px; border-radius: 20px; background: rgba(255,255,255,0.08); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15); overflow: hidden; border: 1px solid rgba(255,255,255,0.1); }
      .sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
      .sidebar-user-info { display: flex; flex-direction: column; gap: 2px; }
      .sidebar-user-name { font-weight: 800; font-size: 16px; color: white; }
      .sidebar-user-role { font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; }

      .sidebar-nav { padding:24px 16px; flex:1; overflow-y: auto; }
      .nav-label { font-size:11px; color: #38bdf8; text-transform:uppercase; letter-spacing:.15em; margin: 20px 16px 8px; font-weight: 800; }
      .nav-item { display:flex; align-items:center; gap:12px; padding: 14px 18px; border-radius: 20px; cursor: pointer; color: #94a3b8; font-size:15px; transition: .3s ease; margin-bottom: 6px; text-decoration: none; font-weight: 700; }
      .nav-item:hover { background:rgba(255,255,255,0.05); color:white; }
      .nav-item.active { background:linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color:white; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }
      
      .main { flex:1; margin-left: 280px; display: flex; flex-direction: column; min-height: 100vh; }
      .topbar { height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); border-bottom: 2px solid rgba(56, 189, 248, 0.4); position: sticky; top: 0; z-index: 10; }
      .topbar-title h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #1e40af; letter-spacing: -0.5px; }
      
      .content { padding: 40px; width: 100%; display: flex; flex-direction: column; gap: 40px; }
      
      .avatar { display: flex; align-items: center; justify-content: center; overflow: hidden; background: #2563eb; color: white; }
      .avatar img { width: 100%; height: 100%; object-fit: cover; }

      .card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02); }
      .card-title { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px dashed #f1f5f9; }
      .card-title i { color: #2563eb; font-size: 26px; }
      
      .form-group { margin-bottom: 32px; }
      .label { display: block; font-size: 15px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1.5px; }
      .input { width: 100%; padding: 16px 24px; border-radius: 20px; border: 1px solid rgba(59, 130, 246, 0.12); background: #f8fafc; font-family: inherit; font-size: 18px; font-weight: 700; outline: none; transition: .3s; }
      .input:focus { border-color: #2563eb; background: #ffffff; box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.08); }
      
      .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
      
      .btn { padding: 16px 32px; border-radius: 20px; border: none; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 18px; cursor: pointer; transition: .3s; display: inline-flex; align-items: center; gap: 12px; text-decoration: none; justify-content: center; }
      .btn-primary { background: #2563eb; color: white; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2); }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.3); }
      .btn-dark { background: #0f172a; color: white; box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.3); }
      .btn-dark:hover { transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(15, 23, 42, 0.4); }
      
      .alert { padding: 16px 20px; border-radius: 20px; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
      .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
      .alert-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fee2e2; }

      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); z-index: 1000; transition: transform .3s; box-shadow: 20px 0 40px rgba(0,0,0,0.1); }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { padding: 15px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        #real-time-clock { display: none; } /* Hide clock on mobile */
        
        .card { padding: 24px 15px; border-radius: 30px; }
        .form-row { grid-template-columns: 1fr; gap: 0; }
        .avatar-sec { flex-direction: column; gap: 20px !important; align-items: center !important; text-align: center; }
        .btn { width: 100%; }

        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 999; }
        .sidebar-overlay.active { display: block; }
        .hamburger { display: flex !important; width: 40px; height: 40px; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 12px; cursor: pointer; color: #1e293b; font-size: 18px; }
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
                <?php if (!empty($user['foto'])): ?>
                    <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                <?php else: ?>
                    <i class="fas fa-user-tie"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="sidebar-user-role">Sopir Pengiriman</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Operasional</div>
            <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-truck-fast nav-icon"></i> Tugas Kiriman</a>
            <a href="riwayat.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'riwayat.php' ? 'active' : '' ?>"><i class="fas fa-history nav-icon"></i> Riwayat Tugas</a>
            <div class="nav-label">Sistem</div>
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
            <div style="text-align: right;">
                <div id="real-time-clock" style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Driver Operational</div>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

            <div class="card">
                <h3 class="card-title"><i class="fas fa-id-card"></i> Data Sopir</h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="avatar-sec" style="display: flex; align-items: center; gap: 32px; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px dashed rgba(59, 130, 246, 0.12);">
                        <div class="avatar" style="width: 100px; height: 100px; border-radius: 30px; font-size: 40px; background: rgba(0,0,0,0.05); color: #2563eb;">
                            <?php if (!empty($user['foto'])): ?>
                                <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-user-tie"></i>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <label class="label">Foto Profil</label>
                            <input type="file" name="foto" accept="image/*" style="font-size: 14px;">
                            <p style="font-size: 12px; color: #64748b; margin-top: 8px;">Format: JPG, PNG, WebP. Maks 2MB.</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="label">Nama Lengkap</label><input type="text" name="nama" class="input" value="<?= htmlspecialchars($user['nama']) ?>" required></div>
                        <div class="form-group"><label class="label">Nomor WA</label><input type="text" name="telepon" class="input" value="<?= htmlspecialchars($user['telepon'] ?? '') ?>" required></div>
                    </div>
                    <div class="form-group"><label class="label">Email</label><input type="email" name="email" class="input" value="<?= htmlspecialchars($user['email']) ?>" required></div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-pen"></i> Simpan Perubahan</button>
                </form>
            </div>
            <div class="card">
                <h3 class="card-title"><i class="fas fa-shield-halved"></i> Ganti Password </h3>
                <form action="" method="POST">
                    <input type="hidden" name="update_password" value="1">
                    <div class="form-group"><label class="label">Password Sekarang</label><input type="password" name="old_pwd" class="input" required></div>
                    <div class="form-row">
                        <div class="form-group"><label class="label">Password Baru</label><input type="password" name="new_pwd" class="input" required></div>
                        <div class="form-group"><label class="label">Ulangi Password</label><input type="password" name="confirm_pwd" class="input" required></div>
                    </div>
                    <button type="submit" class="btn btn-dark"><i class="fas fa-key"></i> Ganti Password</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script>
// Sidebar Toggle
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

// Real-time Clock
const updateClock = () => {
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    const now = new Date();
    const day = days[now.getDay()];
    const date = now.getDate();
    const month = months[now.getMonth() + 1];
    const year = now.getFullYear();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    
    const clockEl = document.getElementById('real-time-clock');
    if (clockEl) {
        clockEl.innerHTML = `${day}, ${date} ${month} ${year} • ${h}:${m}:${s}`;
    }
};
setInterval(updateClock, 1000);
</script>
</body>
</html>
