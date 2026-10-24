<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_penjualan');
$user = getCurrentUser($pdo);

$success = '';
$error = '';

// Update status komplain
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['komplain_id'];
    $status = $_POST['status'];
    $balasan = trim($_POST['balasan'] ?? '');
    $sopir_id = !empty($_POST['sopir_id']) ? (int)$_POST['sopir_id'] : null;
    $tanggal_penggantian = !empty($_POST['tanggal_penggantian']) ? $_POST['tanggal_penggantian'] : null;

    // Cek apakah sudah dikonfirmasi konsumen
    $stmtCek = $pdo->prepare("SELECT konfirmasi_konsumen FROM komplain WHERE id = ?");
    $stmtCek->execute([$id]);
    $isConfirmed = $stmtCek->fetchColumn();

    if ($isConfirmed) {
        $error = 'Laporan ini sudah dikonfirmasi oleh konsumen dan tidak dapat diubah lagi.';
    } else {
        $stmt = $pdo->prepare("UPDATE komplain SET status = ?, balasan = ?, sopir_id = ?, tanggal_penggantian = ? WHERE id = ?");
        if ($stmt->execute([$status, $balasan ?: null, $sopir_id, $tanggal_penggantian, $id])) {
            $success = 'Status Komplain Berhasil Diperbarui!';
        } else {
            $error = 'Gagal memperbarui status.';
        }
    }
}

// Ambil statistik komplain
$stmtStats = $pdo->query("SELECT status, COUNT(*) as c FROM komplain GROUP BY status");
$statsK = $stmtStats->fetchAll(PDO::FETCH_KEY_PAIR);

// Ambil daftar sopir untuk dropdown
$sopirList = $pdo->query("SELECT id, nama FROM users WHERE role = 'sopir' ORDER BY nama")->fetchAll();

// Ambil daftar komplain aktif
$stmtList = $pdo->query("SELECT k.*, u.nama as nama_customer, u.role_customer, s.nama as nama_sopir
    FROM komplain k 
    JOIN users u ON k.user_id = u.id 
    LEFT JOIN users s ON k.sopir_id = s.id
    ORDER BY k.created_at DESC");
$komplainList = $stmtList->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Komplain — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
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
      
      .content { padding: 40px; width: 100%; display: flex; flex-direction: column; gap: 32px; }
      
      .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 8px; }
      .stat-card { background: #ffffff; border-radius: 32px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.02); transition: .3s; }
      .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.1); border-color: #dbeafe; }
      .stat-label { font-size: 13px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px; display: block; }
      .stat-val { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 800; color: #1e293b; line-height: 1; }
      
      .section-card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); border-top: 3px solid #38bdf8; box-shadow: 0 20px 30px -10px rgba(0,0,0,0.03); }
      .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
      .section-title { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #1e40af; letter-spacing: -0.5px; }
      
      .table { width: 100%; border-collapse: collapse; }
      .table th { text-align: left; font-size: 13px; font-weight: 800; color: #38bdf8; padding: 18px; text-transform: uppercase; border-bottom: 1px solid rgba(59, 130, 246, 0.12); letter-spacing: 1.5px; }
      .table td { padding: 24px 18px; font-size: 18px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); vertical-align: top; color: #334155; }
      
      .btn { padding: 14px 24px; border-radius: 16px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer; transition: .3s; border: none; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 10px; }
      .btn-primary { background: #2563eb; color: white; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2); }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.3); }
      
      .select-field { padding: 12px 16px; border-radius: 14px; border: 1px solid rgba(59, 130, 246, 0.12); background: #ffffff; font-family: inherit; font-size: 14px; font-weight: 700; outline: none; transition: .3s; width: 100%; color: #334155; }
      .select-field:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.05); }
      .textarea-field { padding: 14px 18px; border-radius: 18px; border: 1px solid rgba(59, 130, 246, 0.12); background: #ffffff; font-family: inherit; font-size: 14px; font-weight: 600; outline: none; transition: .3s; width: 100%; resize: none; min-height: 90px; margin-top: 10px; color: #334155; }
      .textarea-field:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.05); }
      
      .alert { padding: 20px 28px; border-radius: 24px; font-size: 16px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
      
      .sopir-row { margin-top: 10px; display: none; }
      .sopir-row.visible { display: block; }

      @media (max-width: 1024px) {
        .sidebar { 
          transform: translateX(-100%); 
          z-index: 1000; 
          transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
          box-shadow: 20px 0 40px rgba(0,0,0,0.1);
        }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { padding: 15px; gap: 20px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        .topbar-info { display: none; } /* Hide clock on mobile */
        .section-card { padding: 20px 15px; border-radius: 30px; }
        .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .stat-card { padding: 20px; border-radius: 24px; }
        .stat-val { font-size: 24px; }
        
        /* Table to Card Transformation */
        .table thead { display: none; }
        .table tbody tr { 
            display: block; 
            background: #f8fafc; 
            border-radius: 24px; 
            margin-bottom: 20px; 
            padding: 20px; 
            border: 1px solid #e2e8f0;
        }
        .table td { 
            display: block; 
            width: 100%; 
            padding: 10px 0 !important; 
            border: none !important;
            text-align: left !important;
        }
        .table td:not(:last-child) { border-bottom: 1px dashed #e2e8f0 !important; padding-bottom: 15px !important; margin-bottom: 10px; }
        
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
                <div class="topbar-title"><h2>Pusat Bantuan & Komplain</h2></div>
            </div>
            <div class="topbar-info" style="text-align: right;">
                <div id="real-time-clock" style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Admin Penjualan</div>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?><div class="alert"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert" style="background:#fef2f2; color:#991b1b; border-color:#fee2e2;"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

            <div class="stat-grid">
                <div class="stat-card">
                    <span class="stat-label">Laporan Baru</span>
                    <div class="stat-val" style="color: #f59e0b;"><?= $statsK['menunggu'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Sedang Review</span>
                    <div class="stat-val" style="color: #3b82f6;"><?= $statsK['diproses'] ?? 0 ?></div>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Jadwal Ganti</span>
                    <div class="stat-val" style="color: #10b981;"><?= $statsK['terjadwal_penggantian'] ?? 0 ?></div>
                </div>
                <div class="stat-card" style="background: #f0f6fa;">
                    <span class="stat-label">Total Selesai</span>
                    <div class="stat-val" style="color: #94a3b8;"><?= $statsK['selesai'] ?? 0 ?></div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">Log Pengaduan Konsumen</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>IDENTITAS PELANGGAN</th>
                                <th style="min-width: 300px;">DETAIL KENDALA</th>
                                <th style="text-align: center;">FOTO</th>
                                <th>STATUS</th>
                                <th style="min-width: 300px;">TINDAKAN ADMIN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($komplainList as $k): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 19px;"><?= htmlspecialchars($k['nama_customer']) ?></div>
                                    <div style="font-size: 13px; color: #94a3b8; font-weight: 700; margin-top: 6px;"><i class="fas fa-clock" style="margin-right: 6px;"></i> <?= formatTanggalIndo($k['created_at']) ?></div>
                                    <div style="display: inline-block; padding: 6px 14px; background: #eff6ff; color: #2563eb; font-size: 11px; font-weight: 800; border-radius: 10px; margin-top: 10px;"><?= strtoupper($k['role_customer']) ?></div>
                                    
                                    <?php if ($k['nama_sopir'] && $k['status'] === 'terjadwal_penggantian'): ?>
                                    <div style="font-size: 12px; color: #10b981; font-weight: 800; margin-top: 10px; border-top: 1px solid #f1f5f9; padding-top: 6px;"><i class="fas fa-truck-fast" style="margin-right: 6px;"></i>Kurir: <?= htmlspecialchars($k['nama_sopir']) ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if ($k['tanggal_penggantian']): ?>
                                    <div style="font-size: 12px; color: #64748b; font-weight: 800; margin-top: 4px;">
                                        <i class="fas <?= $k['status'] === 'selesai' ? 'fa-circle-check' : 'fa-calendar-day' ?>" style="margin-right: 6px;"></i> <?= $k['status'] === 'selesai' ? 'Selesai' : 'Jadwal' ?>: <?= formatTanggalIndo($k['tanggal_penggantian']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: #1e293b; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; font-size: 16px;">
                                        <i class="fas fa-triangle-exclamation" style="color: #ef4444;"></i>
                                        <?= strtoupper($k['tipe'] === 'kebocoran' ? 'Varian Bocor' : ($k['tipe'] === 'kekurangan' ? 'Kurang Stok' : ($k['tipe'] === 'salah_produk' ? 'Mismatch Produk' : ($k['tipe'] === 'keterlambatan' ? 'Delay Kirim' : 'Lain-lain')))) ?>
                                    </div>
                                    <div style="font-size: 17px; color: #64748b; line-height: 1.6; font-style: italic; font-weight: 600;">"<?= htmlspecialchars($k['deskripsi']) ?>"</div>
                                    
                                    <?php if ($k['pesanan_id']): ?>
                                    <div style="font-size: 12px; font-weight: 800; color: #2563eb; margin-top: 12px; border-top: 1px dashed #e2e8f0; padding-top: 10px;">REFERENSI ORD #<?= str_pad($k['pesanan_id'], 5, '0', STR_PAD_LEFT) ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if ($k['balasan']): ?>
                                    <div style="margin-top: 16px; padding: 14px 18px; background: #f0fdf4; border-radius: 18px; border: 1px solid #bbf7d0; font-size: 16px; font-weight: 700; color: #166534; line-height: 1.5;">
                                        <i class="fas fa-share" style="margin-right: 8px; transform: scaleX(-1);"></i> <?= htmlspecialchars($k['balasan']) ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($k['konfirmasi_konsumen']): ?>
                                    <div style="margin-top: 12px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <span style="font-size: 11px; font-weight: 800; color: #16a34a; background: #f0fdf4; padding: 6px 14px; border-radius: 10px; border: 1px solid #bbf7d0;"><i class="fas fa-check-double" style="margin-right: 6px;"></i> <?= $k['tipe'] === 'keterlambatan' ? 'SOLUSI DISETUJUI' : 'KONFIRMASI DITERIMA' ?></span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($k['foto']): ?>
                                        <a href="<?= getUploadUrl($k['foto']) ?>" target="_blank" style="width: 54px; height: 54px; display: inline-flex; background: #eff6ff; border-radius: 16px; align-items: center; justify-content: center; color: #2563eb; transition: .3s; border: 1px solid rgba(59, 130, 246, 0.12);"><i class="fas fa-images" style="font-size: 20px;"></i></a>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-size: 13px; font-weight: 800;">TIDAK ADA</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= getStatusBadge($k['status']) ?></td>
                                <td>
                                    <?php if ($k['konfirmasi_konsumen']): ?>
                                        <div style="padding: 18px; background: #f8fafc; border-radius: 20px; border: 2px dashed #e2e8f0; text-align: center;">
                                            <i class="fas fa-lock" style="color: #cbd5e1; margin-bottom: 8px; font-size: 20px;"></i>
                                            <div style="font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Laporan Terkunci</div>
                                            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 4px;">Proses telah selesai & disetujui</div>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                                            <input type="hidden" name="komplain_id" value="<?= $k['id'] ?>">
                                            <div style="display: flex; gap: 10px;">
                                                <select name="status" class="select-field" onchange="toggleSopir(this, <?= $k['id'] ?>)" style="flex: 1;">
                                                    <option value="menunggu" <?= $k['status'] === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                                    <option value="terjadwal_penggantian" <?= $k['status'] === 'terjadwal_penggantian' ? 'selected' : '' ?>>Jadwal Penggantian</option>
                                                    <option value="selesai" <?= $k['status'] === 'selesai' ? 'selected' : '' ?>>Laporan Selesai</option>
                                                    <option value="ditolak" <?= $k['status'] === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-primary" style="padding: 12px; width: 50px; flex-shrink: 0;"><i class="fas fa-save" style="font-size: 18px;"></i></button>
                                            </div>
                                            
                                            <div class="sopir-row <?= $k['status'] === 'terjadwal_penggantian' ? 'visible' : '' ?>" id="sopir-row-<?= $k['id'] ?>">
                                                <select name="sopir_id" class="select-field">
                                                    <option value="">— Pilih Kurir Pengganti —</option>
                                                    <?php foreach ($sopirList as $s): ?>
                                                        <option value="<?= $s['id'] ?>" <?= $k['sopir_id'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nama']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="sopir-row <?= $k['status'] === 'terjadwal_penggantian' ? 'visible' : '' ?>" id="jadwal-row-<?= $k['id'] ?>">
                                                <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; display: block; margin-top: 10px;">Tgl Penggantian</label>
                                                <input type="date" name="tanggal_penggantian" class="select-field" value="<?= !empty($k['tanggal_penggantian']) ? date('Y-m-d', strtotime($k['tanggal_penggantian'])) : '' ?>" min="<?= date('Y-m-d') ?>">
                                            </div>
                                            
                                            <textarea name="balasan" class="textarea-field" placeholder="Tulis instruksi atau balasan untuk konsumen..."><?= htmlspecialchars($k['balasan'] ?? '') ?></textarea>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($komplainList)): ?>
                <div style="text-align: center; padding: 120px 40px; border: 2px dashed #f1f5f9; border-radius: 32px; margin-top: 32px;">
                    <div style="width: 100px; height: 100px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px;">
                        <i class="fas fa-ghost" style="font-size: 40px; color: #cbd5e1;"></i>
                    </div>
                    <h4 style="font-family: 'Outfit', sans-serif; font-size: 24px; color: #64748b; font-weight: 800;">Belum Ada Laporan</h4>
                    <p style="font-size: 16px; color: #94a3b8; margin-top: 12px; font-weight: 600;">Tidak ada pengaduan konsumen yang masuk saat ini.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
function toggleSopir(select, id) {
    const sopirRow = document.getElementById('sopir-row-' + id);
    const jadwalRow = document.getElementById('jadwal-row-' + id);
    if (select.value === 'terjadwal_penggantian') {
        sopirRow.classList.add('visible');
        jadwalRow.classList.add('visible');
    } else {
        sopirRow.classList.remove('visible');
        jadwalRow.classList.remove('visible');
    }
}

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
