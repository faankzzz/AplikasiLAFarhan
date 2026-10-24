<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_penjualan');
$user = getCurrentUser($pdo);

$success = '';

function isPdfFile($path) {
    return !empty($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
}

// Aksi verifikasi
if (isset($_POST['action'])) {
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'verified' : 'rejected';
    $pdo->prepare("UPDATE users SET status_verifikasi = ? WHERE id = ? AND role_customer = 'toko'")->execute([$status, $userId]);
    $success = ($action === 'approve') ? 'Akun Toko Berhasil Diverifikasi!' : 'Pendaftaran Akun Toko Ditolak.';
}

// Daftar akun toko
$pendingList = $pdo->query("SELECT * FROM users WHERE role = 'konsumen' AND role_customer = 'toko' ORDER BY FIELD(status_verifikasi, 'pending', 'verified', 'rejected'), created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      :root {
        --primary: #2563eb;
        --bg: #f0f6fa;
        --surface: #ffffff;
        --text: #1e293b;
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
      body { font-family: 'Inter', sans-serif; background:#f0f6fa; color:#1e293b; min-height:100vh; overflow-x:hidden; -webkit-font-smoothing: antialiased; line-height: 1.6; }
      
      .layout { display:flex; min-height:100vh; }
      
      .sidebar {
  width: 280px;
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
  border-right: 1px solid rgba(255, 255, 255, 0.03);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  position: fixed;
  height: 100vh;
  z-index: 100;
  box-shadow: 10px 0 40px rgba(15, 23, 42, 0.05);
}

.sidebar-logo {
  font-family: 'Outfit', sans-serif;
  font-size: 24px;
  font-weight: 800;
  color: white;
  padding: 32px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  gap: 12px;
  letter-spacing: -0.5px;
}

.sidebar-profile {
  padding: 24px 32px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  text-align: center;
}

.sidebar-avatar {
  width: 64px;
  height: 64px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.08);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.sidebar-user-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.sidebar-user-name {
  font-weight: 800;
  font-size: 16px;
  color: white;
}

.sidebar-user-role {
  font-size: 11px;
  font-weight: 800;
  color: #38bdf8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sidebar-nav {
  padding: 24px 16px;
  flex: 1;
  overflow-y: auto;
}

.nav-label {
  font-size: 11px;
  color: #38bdf8;
  text-transform: uppercase;
  letter-spacing: .15em;
  margin: 20px 16px 8px;
  font-weight: 800;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-radius: 20px;
  cursor: pointer;
  color: #94a3b8;
  font-size: 15px;
  transition: .3s ease;
  margin-bottom: 6px;
  text-decoration: none;
  font-weight: 700;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: white;
}

.nav-item.active {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  color: white;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
}
      
      .main { flex:1; margin-left: 280px; display: flex; flex-direction: column; min-height: 100vh; }
      .topbar { height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); border-bottom: 2px solid rgba(56, 189, 248, 0.4); position: sticky; top: 0; z-index: 10; }
      .topbar-title h2 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #1e40af; letter-spacing: -0.5px; }
      
      .content { padding: 40px; width: 100%; display: flex; flex-direction: column; gap: 40px; }
      
      .verify-card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02); transition: .4s cubic-bezier(0.16, 1, 0.3, 1); }
      .verify-card:hover { transform: translateY(-8px); border-color: #dbeafe; box-shadow: 0 30px 60px -15px rgba(59, 130, 246, 0.1); }
      
      .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 2px dashed #f1f5f9; }
      .user-title { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #1e40af; letter-spacing: -0.5px; }
      .user-meta { display: flex; gap: 32px; font-size: 14px; color: #64748b; font-weight: 700; margin-top: 10px; }
      .user-meta i { color: #2563eb; }
      
      .badge { padding: 10px 20px; border-radius: 50px; font-size: 12px; font-weight: 800; text-transform: uppercase; border: 1px solid transparent; }
      .badge-pending { background: #fffbeb; color: #92400e; border-color: #fef3c7; }
      .badge-verified { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
      .badge-rejected { background: #fff1f2; color: #9f1239; border-color: #fee2e2; }
      
      .info-row { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
      .info-label { display: block; font-size: 13px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px; }
      
      .addr-box { background: #f8fafc; padding: 28px; border-radius: 28px; border: 1px solid #f1f5f9; margin-bottom: 40px; }
      .addr-text { font-size: 16px; font-weight: 600; color: #334155; line-height: 1.6; }
      
      .img-preview { width: 100%; height: 320px; background: #f1f5f9; border-radius: 28px; overflow: hidden; position: relative; cursor: pointer; border: 1px solid rgba(59, 130, 246, 0.12); transition: .3s; }
      .img-preview:hover { border-color: #2563eb; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
      .img-preview img,
      .doc-preview { width: 100%; height: 100%; }
      .img-preview img { object-fit: cover; transition: .6s cubic-bezier(0.16, 1, 0.3, 1); }
      .img-preview:hover img { transform: scale(1.05); }
      .doc-preview { border: 0; background: #ffffff; }
      .no-img { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #cbd5e1; gap: 12px; }
      .no-img i { font-size: 40px; }
      
      .action-btns { display: flex; gap: 20px; margin-top: 40px; padding-top: 32px; border-top: 1px solid #f1f5f9; }
      .btn { padding: 18px 32px; border-radius: 20px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer; transition: .3s; border: none; display: inline-flex; align-items: center; gap: 12px; justify-content: center; flex: 1; }
      
      .btn-approve { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2); }
      .btn-approve:hover { transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.3); }
      
      .btn-reject { background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); color: #64748b; }
      .btn-reject:hover { border-color: #ef4444; color: #ef4444; }
      
      .alert { padding: 20px 28px; border-radius: 24px; font-size: 16px; font-weight: 700; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 12px; }

      .user-pill { display: flex; align-items: center; gap: 12px; padding: 8px 20px; background: #f1f5f9; border-radius: 50px; }
      .avatar { width: 32px; height: 32px; border-radius: 10px; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; }

      @media (max-width: 1024px) {
        .sidebar { 
          transform: translateX(-100%); 
          z-index: 1000; 
          transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
          box-shadow: 20px 0 40px rgba(0,0,0,0.1);
        }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { padding: 15px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        .topbar-info { display: none; } /* Hide clock on mobile */
        .verify-card { padding: 24px 15px; border-radius: 30px; }
        .card-header { flex-direction: column; gap: 20px; }
        .user-meta { flex-direction: column; gap: 10px; }
        .info-row { grid-template-columns: 1fr; gap: 20px; }
        .img-preview { height: 220px; }
        .action-btns { flex-direction: column; gap: 10px; }
        .btn { width: 100%; }
        
        .sidebar-overlay {
          display: none;
          position: fixed;
          top: 0; left: 0; right: 0; bottom: 0;
          background: rgba(15, 23, 42, 0.5);
          backdrop-filter: blur(4px);
          z-index: 999;
        }
        .sidebar-overlay.active { display: block; }

        .hamburger {
          display: flex !important;
          width: 40px; height: 40px;
          align-items: center; justify-content: center;
          background: #f1f5f9;
          border-radius: 12px;
          cursor: pointer;
          color: #1e293b;
          font-size: 18px;
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
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="sidebar-user-role">Pusat Penjualan</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Penjualan</div>
            <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie nav-icon"></i> Dashboard</a>
            <a href="verifikasi.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'verifikasi.php' ? 'active' : '' ?>"><i class="fas fa-circle-check nav-icon"></i> Verifikasi Akun</a>
            <a href="pesanan.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'pesanan.php' ? 'active' : '' ?>"><i class="fas fa-receipt nav-icon"></i> Data Pesanan</a>
            <a href="komplain.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'komplain.php' ? 'active' : '' ?>"><i class="fas fa-circle-exclamation nav-icon"></i> Komplain & Retur</a>
            <a href="rute.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'rute.php' ? 'active' : '' ?>"><i class="fas fa-route nav-icon"></i> Pengaturan Rute</a>
            <a href="rekap.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'rekap.php' ? 'active' : '' ?>"><i class="fas fa-file-invoice-dollar nav-icon"></i> Rekapitulasi</a>
            
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
                <div class="topbar-title"><h2>Pusat Verifikasi Akun Toko</h2></div>
            </div>
            <div class="topbar-info" style="text-align: right;">
                <div id="real-time-clock" style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Admin Penjualan</div>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?><div class="alert"><i class="fas fa-shield-check"></i> <?= $success ?></div><?php endif; ?>

            <?php if (empty($pendingList)): ?>
                <div class="verify-card" style="text-align: center; padding: 120px 40px; border-style: dashed; background: transparent;">
                    <div style="width: 100px; height: 100px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px;">
                        <i class="fas fa-users-viewfinder" style="font-size: 40px; color: #cbd5e1;"></i>
                    </div>
                    <h4 style="font-family: 'Outfit', sans-serif; font-size: 24px; color: #64748b; font-weight: 800;">Antrian Masuk Kosong</h4>
                    <p style="font-size: 16px; color: #94a3b8; margin-top: 12px; font-weight: 600;">Saat ini tidak ada pendaftaran mitra toko yang perlu diverifikasi.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pendingList as $u): ?>
                    <div class="verify-card">
                        <div class="card-header">
                            <div>
                                <h3 class="user-title"><?= htmlspecialchars($u['nama']) ?></h3>
                                <div class="user-meta">
                                    <span><i class="fas fa-at"></i> <?= $u['email'] ?></span>
                                    <span><i class="fas fa-phone-volume"></i> <?= $u['telepon'] ?></span>
                                    <span><i class="fas fa-calendar-day"></i> Bergabung: <?= formatTanggalIndo($u['created_at']) ?></span>
                                </div>
                            </div>
                            <span class="badge badge-<?= $u['status_verifikasi'] ?>"><?= strtoupper($u['status_verifikasi']) ?></span>
                        </div>

                        <div class="addr-box">
                            <span class="info-label">Alamat Lengkap Toko</span>
                            <div class="addr-text"><?= htmlspecialchars($u['alamat']) ?></div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 30px;">
                            <div>
                                <span class="info-label">LEGALITAS USAHA (SKU/NIB)</span>
                                <div class="img-preview" onclick="window.open('<?= getUploadUrl($u['surat_usaha']) ?>', '_blank')">
                                    <?php if ($u['surat_usaha']): ?>
                                        <?php if (isPdfFile($u['surat_usaha'])): ?>
                                            <iframe
                                                class="doc-preview"
                                                src="<?= getUploadUrl($u['surat_usaha']) ?>#toolbar=0&navpanes=0&scrollbar=1"
                                                title="Preview Surat Usaha"
                                            ></iframe>
                                        <?php else: ?>
                                            <img src="<?= getUploadUrl($u['surat_usaha']) ?>" alt="SKU">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="no-img"><i class="fas fa-file-shield"></i><div style="font-size: 13px; font-weight: 800;">DOKUMEN TIDAK TERUNGGAH</div></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="info-label">KARTU MEMBER TOKO</span>
                                <div class="img-preview" onclick="window.open('<?= getUploadUrl($u['kartu_member']) ?>', '_blank')">
                                    <?php if ($u['kartu_member']): ?>
                                        <img src="<?= getUploadUrl($u['kartu_member']) ?>" alt="Kartu Member">
                                    <?php else: ?>
                                        <div class="no-img"><i class="fas fa-address-card"></i><div style="font-size: 13px; font-weight: 800;">DOKUMEN TIDAK TERUNGGAH</div></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="info-label">HALAMAN DEPAN TOKO</span>
                                <div class="img-preview" onclick="window.open('<?= getUploadUrl($u['halaman_depan_toko']) ?>', '_blank')">
                                    <?php if (!empty($u['halaman_depan_toko'])): ?>
                                        <img src="<?= getUploadUrl($u['halaman_depan_toko']) ?>" alt="Foto Halaman Depan Toko">
                                    <?php else: ?>
                                        <div class="no-img"><i class="fas fa-store"></i><div style="font-size: 13px; font-weight: 800;">FOTO BELUM TERUNGGAH</div></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($u['status_verifikasi'] === 'pending'): ?>
                        <div class="action-btns">
                            <form method="POST" style="flex: 1.5;">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve">TERBITKAN VERIFIKASI <i class="fas fa-certificate"></i></button>
                            </form>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-reject" onclick="return confirm('Apakah Anda yakin ingin menolak pendaftaran mitra ini?')">TOLAK AKSES <i class="fas fa-ban"></i></button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
    updateClock();
</script>
</body>
</html>
