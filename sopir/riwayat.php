<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('sopir');
$user = getCurrentUser($pdo);

// Ambil riwayat pesanan selesai
$stmtP = $pdo->prepare("SELECT p.*, u.nama as konsumen_nama, u.telepon,
    COALESCE(p.alamat_pengiriman, u.alamat) as alamat,
    (SELECT GROUP_CONCAT(CONCAT(pr.nama,' x',pd.jumlah) SEPARATOR ', ') FROM transaksi_pesanan pd JOIN produk pr ON pd.produk_id = pr.id WHERE pd.pesanan_id = p.id) as produk_list
    FROM pesanan p JOIN users u ON p.user_id = u.id 
    WHERE p.sopir_id = ? AND p.status = 'selesai' 
    ORDER BY p.updated_at DESC");
$stmtP->execute([getUserId()]);
$riwayatPesanan = $stmtP->fetchAll();

// Ambil riwayat penggantian komplain selesai
$stmtK = $pdo->prepare("SELECT k.*, u.nama as konsumen_nama, u.telepon, u.alamat 
    FROM komplain k JOIN users u ON k.user_id = u.id 
    WHERE k.sopir_id = ? AND k.status = 'selesai' 
    ORDER BY k.tanggal_penggantian DESC");
$stmtK->execute([getUserId()]);
$riwayatKomplain = $stmtK->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Tugas — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      :root {
        --primary: #2563eb; --bg: #f0f6fa; --surface: #ffffff; --text: #1e293b; --muted: #64748b; --border: rgba(59, 130, 246, 0.12);
        --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); --sidebar-text: #f8fafc; --sidebar-muted: #94a3b8;
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif;
        --success: #10b981;
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
      .topbar { height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: #ffffff; border-bottom: 1px solid rgba(59, 130, 246, 0.12); position: sticky; top: 0; z-index: 10; gap: 20px; }
      .topbar-title h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #1e40af; letter-spacing: -0.5px; }
      
      /* New Export Button Style */
      .btn-export {
          display: flex;
          align-items: center;
          gap: 8px;
          background: #10b981;
          color: white;
          padding: 10px 18px;
          border-radius: 14px;
          text-decoration: none;
          font-weight: 700;
          font-size: 14px;
          transition: 0.3s;
          box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
      }
      .btn-export:hover {
          background: #059669;
          transform: translateY(-2px);
          box-shadow: 0 6px 15px rgba(16, 185, 129, 0.25);
      }

      .btn-pdf {
          display: flex;
          align-items: center;
          gap: 8px;
          background: #ef4444;
          color: white;
          padding: 10px 18px;
          border-radius: 14px;
          text-decoration: none;
          font-weight: 700;
          font-size: 14px;
          transition: 0.3s;
          box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
      }
      .btn-pdf:hover {
          background: #dc2626;
          transform: translateY(-2px);
          box-shadow: 0 6px 15px rgba(239, 68, 68, 0.25);
      }

      /* Divider Tanggal */
      .date-divider {
          display: flex;
          align-items: center;
          gap: 15px;
          margin: 32px 0 20px;
          position: relative;
      }
      .date-divider span {
          background: #f1f5f9;
          color: #64748b;
          padding: 6px 16px;
          border-radius: 50px;
          font-size: 12px;
          font-weight: 800;
          text-transform: uppercase;
          letter-spacing: 1px;
          white-space: nowrap;
          border: 1px solid rgba(59, 130, 246, 0.12);
      }
      .date-divider::after {
          content: '';
          flex: 1;
          height: 1px;
          background: rgba(59, 130, 246, 0.12);
      }

      .content { padding: 40px; width: 100%; display: flex; flex-direction: column; gap: 40px; }
      
      .section-card { background: #ffffff; border-radius: 40px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02); }
      .section-title { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
      .section-title i { color: #2563eb; }

      .history-card { background: #f8fafc; border-radius: 28px; padding: 24px; border: 1px solid rgba(59, 130, 246, 0.12); margin-bottom: 16px; transition: .3s; }
      .history-card:hover { border-color: #3b82f6; background: #ffffff; box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.1); }
      
      .history-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px dashed #e2e8f0; }
      .history-cust { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #0f172a; }
      .history-date { font-size: 14px; font-weight: 700; color: #64748b; }
      
      .info-item { display: flex; align-items: center; gap: 10px; color: #475569; font-size: 16px; margin-bottom: 8px; font-weight: 600; }
      .info-item i { width: 18px; color: #2563eb; text-align: center; font-size: 18px; }
      
      .badge { padding: 8px 16px; border-radius: 50px; font-size: 12px; font-weight: 800; text-transform: uppercase; }
      .badge-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); z-index: 1000; transition: transform .3s; box-shadow: 20px 0 40px rgba(0,0,0,0.1); }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { padding: 15px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        .topbar-actions { width: 100%; justify-content: flex-end; margin-top: 5px; }
        .topbar-date { display: none; } /* Hide clock on mobile */
        .btn-export span, .btn-pdf span { display: none; } 
        .btn-export, .btn-pdf { padding: 10px 14px; }
        
        .history-header { flex-direction: column; gap: 10px; }
        .history-header div:last-child { text-align: left !important; display: flex; align-items: center; justify-content: space-between; width: 100%; }
        .history-header div:last-child span { margin-top: 0 !important; }

        .sidebar-overlay {
          display: none;
          position: fixed;
          top: 0; left: 0; right: 0; bottom: 0;
          background: rgba(15, 23, 42, 0.5);
          backdrop-filter: blur(4px);
          z-index: 999;
        }
        .sidebar-overlay.active { display: block; }
      }
      @media (max-width: 1024px) {
        #hamburgerBtn { display: block !important; }
        .topbar-date { display: none; }
      }
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
            <a href="dashboard.php" class="nav-item"><i class="fas fa-truck-fast nav-icon"></i> Tugas Kiriman</a>
            <a href="riwayat.php" class="nav-item active"><i class="fas fa-history nav-icon"></i> Riwayat Tugas</a>
            
            <div class="nav-label">Sistem</div>
            <a href="profil.php" class="nav-item"><i class="fas fa-user-circle nav-icon"></i> Profil Akun</a>
            <a href="../logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt nav-icon"></i> Keluar</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button id="hamburgerBtn" class="no-print" style="background: none; border: none; font-size: 24px; color: #1e293b; cursor: pointer; display: none;">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-title"><h2>Riwayat Pengiriman</h2></div>
            </div>
            
            <div class="topbar-actions" style="display: flex; align-items: center; gap: 10px;">
                <a href="riwayat_print.php" target="_blank" class="btn-pdf">
                    <i class="fas fa-file-pdf"></i> 
                    <span>Download PDF</span>
                </a>
                <a href="export_riwayat.php" class="btn-export">
                    <i class="fas fa-file-excel"></i> 
                    <span>Excel</span>
                </a>

                <div style="text-align: right; border-left: 1px solid rgba(59, 130, 246, 0.12); padding-left: 15px;" class="topbar-date">
                    <div style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                    <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Riwayat Selesai</div>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="section-card">
                <div class="section-title"><i class="fas fa-boxes-packing"></i> Pengiriman Selesai</div>
                <?php if (empty($riwayatPesanan)): ?>
                    <div style="text-align: center; padding: 40px; color: #94a3b8;">Belum ada riwayat pengiriman.</div>
                <?php else: ?>
                    <?php 
                        $lastDate = '';
                        foreach ($riwayatPesanan as $rp): 
                            $currentDate = date('Y-m-d', strtotime($rp['updated_at']));
                            if ($currentDate !== $lastDate):
                                $lastDate = $currentDate;
                    ?>
                        <div class="date-divider">
                            <span><i class="far fa-calendar-check" style="margin-right: 6px;"></i> <?= formatTanggalIndo($rp['updated_at']) ?></span>
                        </div>
                    <?php endif; ?>
                        <div class="history-card">
                            <div class="history-header">
                                <div>
                                    <div class="history-cust"><?= htmlspecialchars($rp['konsumen_nama']) ?></div>
                                    <div style="color: #2563eb; font-size: 13px; font-weight: 800; margin-top: 4px;">ORDER #<?= str_pad($rp['id'], 6, '0', STR_PAD_LEFT) ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="history-date"><?= formatTanggalIndo($rp['updated_at']) ?></div>
                                    <span class="badge badge-success" style="margin-top: 8px; display: inline-block;">BERHASIL</span>
                                </div>
                            </div>
                            <div class="info-item"><i class="fas fa-location-arrow"></i> <?= htmlspecialchars($rp['alamat']) ?></div>
                            <div class="info-item"><i class="fas fa-box-archive"></i> <?= htmlspecialchars($rp['produk_list']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($riwayatKomplain)): ?>
            <div class="section-card">
                <div class="section-title" style="color: #b45309;"><i class="fas fa-rotate-left"></i> Penggantian Barang Komplain Selesai</div>
                <?php foreach ($riwayatKomplain as $rk): ?>
                    <div class="history-card" style="border-left: 4px solid #f59e0b;">
                        <div class="history-header">
                            <div>
                                <div class="history-cust"><?= htmlspecialchars($rk['konsumen_nama']) ?></div>
                                <div style="color: #b45309; font-size: 13px; font-weight: 800; margin-top: 4px;">KOMPLAIN #<?= str_pad($rk['id'], 5, '0', STR_PAD_LEFT) ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div class="history-date"><?= formatTanggalIndo($rk['tanggal_penggantian']) ?></div>
                                <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; margin-top: 8px; display: inline-block;">PENGGANTIAN SELESAI</span>
                            </div>
                        </div>
                        <div class="info-item"><i class="fas fa-location-arrow"></i> <?= htmlspecialchars($rk['alamat']) ?></div>
                        <div class="info-item" style="font-style: italic; color: #64748b;">"<?= htmlspecialchars($rk['deskripsi']) ?>"</div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    // Sidebar Toggle for Mobile
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