<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fonnte.php';
requireRole('sopir');
$user = getCurrentUser($pdo);

$success = '';

// Handle konfirmasi penggantian selesai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selesai_penggantian'])) {
    $komplainId = (int)$_POST['komplain_id'];
    $stmt = $pdo->prepare("UPDATE komplain SET status = 'selesai', tanggal_penggantian = NOW() WHERE id = ? AND sopir_id = ?");
    if ($stmt->execute([$komplainId, getUserId()])) {
        $success = 'Penggantian barang berhasil dikonfirmasi!';
    }
}

// Handle Konfirmasi Selesai Perjalanan (Kembali ke Gudang)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selesai_ritase'])) {
    $stmt = $pdo->prepare("INSERT INTO ritase (sopir_id, tanggal, jumlah) VALUES (?, CURDATE(), 1) 
                           ON DUPLICATE KEY UPDATE jumlah = jumlah + 1");
    $stmt->execute([getUserId()]);
    
    // Ambil angka terbaru
    $stmtR = $pdo->prepare("SELECT jumlah FROM ritase WHERE sopir_id = ? AND tanggal = CURDATE()");
    $stmtR->execute([getUserId()]);
    $newCount = $stmtR->fetchColumn();

    header("Location: dashboard.php?ritase_success=" . $newCount);
    exit;
}

// Ambil pesan sukses dari URL jika ada
if (isset($_GET['ritase_success'])) {
    $success = 'Perjalanan ke-' . $_GET['ritase_success'] . ' berhasil dicatat! Truk siap dimuat kembali.';
}

// Ambil jumlah perjalanan hari ini dari tabel ritase
$stmtR = $pdo->prepare("SELECT jumlah FROM ritase WHERE sopir_id = ? AND tanggal = CURDATE()");
$stmtR->execute([getUserId()]);
$ritaseCount = $stmtR->fetchColumn() ?: 0;

$where = "p.sopir_id = ? AND p.status = 'dikirim'";
$params = [getUserId()];

$stmt = $pdo->prepare("SELECT p.*, u.nama as konsumen_nama, u.telepon, u.no_wa, p.jam_pengantaran,
    COALESCE(p.alamat_pengiriman, u.alamat) as alamat, 
    COALESCE(p.latitude_pengiriman, u.latitude) as latitude, 
    COALESCE(p.longitude_pengiriman, u.longitude) as longitude,
    (SELECT GROUP_CONCAT(CONCAT(pr.nama,' x',pd.jumlah) SEPARATOR ', ') FROM transaksi_pesanan pd JOIN produk pr ON pd.produk_id = pr.id WHERE pd.pesanan_id = p.id) as produk_list
    FROM pesanan p JOIN users u ON p.user_id = u.id WHERE $where ORDER BY p.jam_pengantaran ASC, p.created_at ASC");
$stmt->execute($params);
$pengirimanList = $stmt->fetchAll();

// Tugas penggantian komplain yang ditugaskan ke sopir ini
$stmtK = $pdo->prepare("SELECT k.*, u.nama as konsumen_nama, u.telepon, u.alamat FROM komplain k JOIN users u ON k.user_id = u.id WHERE k.sopir_id = ? AND k.status = 'terjadwal_penggantian' ORDER BY k.created_at ASC");
$stmtK->execute([getUserId()]);
$penggantianList = $stmtK->fetchAll();

// Hitung jarak
foreach ($pengirimanList as &$pg) {
    if ($pg['latitude'] && $pg['longitude']) {
        $pg['jarak'] = haversine(GUDANG_LAT, GUDANG_LNG, $pg['latitude'], $pg['longitude']);
    } else {
        $pg['jarak'] = 0;
    }
}
unset($pg);

// Stats
$totalHariIni = count($pengirimanList);
$selesaiHariIni = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE sopir_id = ? AND status = 'selesai' AND DATE(updated_at) = CURDATE()");
$selesaiHariIni->execute([getUserId()]);
$doneToday = $selesaiHariIni->fetchColumn();

// Ambil waktu terakhir ritase dicatat dari tabel baru
$stmtLast = $pdo->prepare("SELECT last_updated FROM ritase WHERE sopir_id = ? AND tanggal = CURDATE()");
$stmtLast->execute([getUserId()]);
$lastRitaseTime = $stmtLast->fetchColumn();

// Cek jumlah pesanan yang diselesaikan SESUDAH ritase terakhir (buat validasi tombol)
$stmtNewDone = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE sopir_id = ? AND status = 'selesai' AND (updated_at > ? OR ? IS NULL) AND DATE(updated_at) = CURDATE()");
$stmtNewDone->execute([getUserId(), $lastRitaseTime, $lastRitaseTime]);
$newOrdersDoneSinceLastRitase = $stmtNewDone->fetchColumn();

// Hitung muatan aktif saat ini (Dus & Galon)
$stmtLoad = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN LOWER(pr.satuan) = 'dus' THEN tp.jumlah ELSE 0 END) as total_dus,
        SUM(CASE WHEN LOWER(pr.satuan) = 'galon' THEN tp.jumlah ELSE 0 END) as total_galon
    FROM pesanan p
    JOIN transaksi_pesanan tp ON p.id = tp.pesanan_id
    JOIN produk pr ON tp.produk_id = pr.id
    WHERE p.sopir_id = ? AND p.status = 'dikirim'
");
$stmtLoad->execute([getUserId()]);
$currentLoad = $stmtLoad->fetch();

// Hitung total barang yang sudah sukses diantar hari ini (Ringkasan Kerja)
$stmtDoneItems = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN LOWER(pr.satuan) = 'dus' THEN tp.jumlah ELSE 0 END) as total_dus,
        SUM(CASE WHEN LOWER(pr.satuan) = 'galon' THEN tp.jumlah ELSE 0 END) as total_galon
    FROM pesanan p
    JOIN transaksi_pesanan tp ON p.id = tp.pesanan_id
    JOIN produk pr ON tp.produk_id = pr.id
    WHERE p.sopir_id = ? AND p.status = 'selesai' AND DATE(p.updated_at) = CURDATE()
");
$stmtDoneItems->execute([getUserId()]);
$doneItems = $stmtDoneItems->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengiriman — Alfa One</title>
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
      
      .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
      .stat-card { background: #ffffff; border-radius: 32px; padding: 28px; border: 1px solid rgba(59, 130, 246, 0.12); border-top: 3px solid #38bdf8; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 20px -5px rgba(0,0,0,0.02); }
      .stat-icon { width: 56px; height: 56px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
      .stat-icon.orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
      .stat-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
      
      .stat-val { font-family: 'Outfit', sans-serif; font-size: 36px; font-weight: 800; color: #0f172a; line-height: 1; letter-spacing: -0.5px; }
      .stat-label { font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 12px; }
      
      .task-card { background: #ffffff; border-radius: 40px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); transition: .4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02); }
      .task-card:hover { transform: translateY(-8px); border-color: #dbeafe; box-shadow: 0 30px 60px -15px rgba(59, 130, 246, 0.1); }
      
      .task-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 2px dashed #f1f5f9; }
      .task-cust { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
      
      .info-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 32px; }
      .info-item { display: flex; align-items: center; gap: 14px; color: #475569; font-size: 18px; margin-bottom: 14px; font-weight: 600; }
      .info-item i { width: 24px; color: #2563eb; text-align: center; font-size: 20px; }
      
      .btn-action { 
          background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; border: none; padding: 18px 32px; border-radius: 20px; 
          font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer; transition: .3s; 
          text-decoration: none; display: inline-flex; align-items: center; gap: 12px; width: 100%; justify-content: center;
          box-shadow: 0 10px 20px -3px rgba(59, 130, 246, 0.3);
      }
      .btn-action:hover { transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.3); }
      
      .badge { padding: 8px 18px; border-radius: 50px; font-size: 12px; font-weight: 800; text-transform: uppercase; border: 1px solid transparent; }
      .badge-cash { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
      .badge-transfer { background: #eff6ff; color: #1e40af; border-color: #dbeafe; }

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
        .topbar { padding: 0 16px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; padding: 15px 16px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        .topbar-info { display: none; } /* Hide clock on mobile */
        .stat-grid { grid-template-columns: 1fr; }
        .info-grid { grid-template-columns: 1fr; gap: 20px; }
        .task-card { padding: 24px; border-radius: 30px; }
        .task-header div:last-child { align-items: flex-start !important; width: 100%; justify-content: space-between; flex-direction: row !important; }
        
        /* Penggantian list adjustment */
        .penggantian-card-grid { grid-template-columns: 1fr !important; }
        
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

      /* Progress Bar Styles */
      .capacity-container { margin-top: 12px; }
      .progress-bg { background: #f1f5f9; height: 10px; border-radius: 50px; overflow: hidden; margin-top: 8px; border: 1px solid #e2e8f0; }
      .progress-fill { height: 100%; transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 50px; }
      .fill-dus { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
      .fill-galon { background: linear-gradient(90deg, #0ea5e9, #0284c7); }
      
      .stat-footer { font-size: 11px; font-weight: 800; color: #94a3b8; display: flex; justify-content: space-between; margin-top: 8px; text-transform: uppercase; }
      
      .perf-card { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; border-radius: 32px; padding: 28px; display: flex; align-items: center; gap: 20px; box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.4); }
      .perf-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
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
                <div class="topbar-title"><h2>Dashboard Pengiriman</h2></div>
            </div>
            <div class="topbar-info" style="text-align: right;">
                <div id="real-time-clock" style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Driver Operational</div>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?>
            <div style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 18px 24px; border-radius: 20px; font-weight: 700; display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <i class="fas fa-circle-check"></i> <?= $success ?>
            </div>
            <?php endif; ?>
            
            <div class="stat-grid animate-up">
                <!-- Performa Hari Ini -->
                <div class="perf-card">
                    <div class="perf-icon"><i class="fas fa-truck-ramp-box"></i></div>
                    <div>
                        <div style="font-size: 13px; font-weight: 700; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px;">Total Perjalanan Hari Ini</div>
                        <div class="stat-val" style="color: white; font-size: 32px; margin-top: 4px;"><?= $ritaseCount ?> <span style="font-size: 14px; opacity: 0.8;">KALI JALAN</span></div>
                        <div style="font-size: 11px; margin-top: 4px; opacity: 0.9;">Total: <?= (int)$doneItems['total_dus'] ?> Dus & <?= (int)$doneItems['total_galon'] ?> Galon terkirim</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
                    <div>
                        <div class="stat-val"><?= $doneToday ?></div>
                        <div class="stat-label">Pengiriman Berhasil Hari Ini</div>
                    </div>
                </div>

                <!-- Kapasitas Dus -->
                <div class="stat-card">
                    <div style="width: 100%;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <div class="stat-label" style="margin-top: 0;">Muatan Dus</div>
                                <div class="stat-val" style="font-size: 28px;"><?= (int)$currentLoad['total_dus'] ?> <span style="font-size: 14px; color: #94a3b8;">/ <?= $user['max_dus'] ?: '∞' ?></span></div>
                            </div>
                            <div class="stat-icon orange" style="width: 44px; height: 44px; font-size: 18px;"><i class="fas fa-box"></i></div>
                        </div>
                        <div class="capacity-container">
                            <?php 
                                $percentDus = $user['max_dus'] > 0 ? min(100, ($currentLoad['total_dus'] / $user['max_dus']) * 100) : 0;
                            ?>
                            <div class="progress-bg">
                                <div class="progress-fill fill-dus" style="width: <?= $percentDus ?>%;"></div>
                            </div>
                            <div class="stat-footer">
                                <span>Status Truk</span>
                                <span><?= round($percentDus) ?>% Terpakai</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kapasitas Galon -->
                <div class="stat-card">
                    <div style="width: 100%;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <div class="stat-label" style="margin-top: 0;">Muatan Galon</div>
                                <div class="stat-val" style="font-size: 28px;"><?= (int)$currentLoad['total_galon'] ?> <span style="font-size: 14px; color: #94a3b8;">/ <?= $user['max_galon'] ?: '∞' ?></span></div>
                            </div>
                            <div class="stat-icon green" style="width: 44px; height: 44px; font-size: 18px; background: linear-gradient(135deg, #0ea5e9, #0284c7);"><i class="fas fa-faucet-drip"></i></div>
                        </div>
                        <div class="capacity-container">
                            <?php 
                                $percentGalon = $user['max_galon'] > 0 ? min(100, ($currentLoad['total_galon'] / $user['max_galon']) * 100) : 0;
                            ?>
                            <div class="progress-bg">
                                <div class="progress-fill fill-galon" style="width: <?= $percentGalon ?>%;"></div>
                            </div>
                            <div class="stat-footer">
                                <span>Status Truk</span>
                                <span><?= round($percentGalon) ?>% Terpakai</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($penggantianList)): ?>
                <div class="stat-card animate-up" style="border-color: #fde68a; background: #fffbeb;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);"><i class="fas fa-rotate-left"></i></div>
                    <div>
                        <div class="stat-val" style="color: #b45309;"><?= count($penggantianList) ?></div>
                        <div class="stat-label">Penggantian Antri</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php 
                // Cek apakah sopir berhak klik "Konfirmasi Kembali"
                // Syarat: SELURUH tugas (Pengiriman + Penggantian) harus KOSONG
                $hasActiveTasks = (count($pengirimanList) > 0 || count($penggantianList) > 0);
                
                // Ambil waktu terakhir ritase dicatat
                $stmtLast = $pdo->prepare("SELECT last_updated FROM ritase WHERE sopir_id = ? AND tanggal = CURDATE()");
                $stmtLast->execute([getUserId()]);
                $lastRitaseTime = $stmtLast->fetchColumn();

                $stmtNew = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE sopir_id = ? AND status = 'selesai' AND (updated_at > ? OR ? IS NULL) AND DATE(updated_at) = CURDATE()");
                $stmtNew->execute([getUserId(), $lastRitaseTime, $lastRitaseTime]);
                $newOrdersSinceLast = $stmtNew->fetchColumn();

                $showRitaseButton = (!$hasActiveTasks && $newOrdersSinceLast > 0);
            ?>

            <?php if ($showRitaseButton): ?>
                <div class="animate-up" style="background: #ffffff; border-radius: 32px; padding: 32px; border: 2px solid #3b82f6; display: flex; align-items: center; justify-content: space-between; gap: 24px; box-shadow: 0 20px 40px -10px rgba(56, 189, 248, 0.4); margin-bottom: 24px;">
                    <div style="flex: 1;">
                        <h4 style="font-family: 'Outfit', sans-serif; font-size: 20px; color: #1e293b; margin-bottom: 4px;">Konfirmasi Kembali ke Gudang?</h4>
                        <p style="font-size: 14px; color: #64748b; font-weight: 500;">Truk Anda terdeteksi kosong. Klik tombol di samping jika Anda sudah kembali ke gudang untuk mencatat perjalanan pengiriman.</p>
                    </div>
                    <form method="POST">
                        <button type="submit" name="selesai_ritase" class="btn-action" style="padding: 18px 40px; width: auto;">
                            <i class="fas fa-warehouse"></i> KONFIRMASI SAMPAI GUDANG
                        </button>
                    </form>
                </div>
            <?php endif; ?>


                <?php if (empty($pengirimanList)): ?>
                    <div style="text-align: center; padding: 100px 40px; border: 2px dashed #e2e8f0; border-radius: 40px;">
                        <i class="fas fa-truck-ramp-box" style="font-size: 56px; color: #cbd5e1; margin-bottom: 24px; opacity: 0.5;"></i>
                        <h4 style="font-family: 'Syne', sans-serif; font-size: 20px; color: #64748b;">Belum Ada Tugas</h4>
                        <p style="font-size: 14px; color: #94a3b8; font-weight: 500; margin-top: 12px;">Anda belum mengantongi jadwal pengiriman. Silakan hubungi Sales Admin.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pengirimanList as $p): ?>
                        <div class="task-card">
                            <div class="task-header">
                                <div>
                                    <div class="task-cust"><?= htmlspecialchars($p['konsumen_nama']) ?></div>
                                    <div style="display: flex; gap: 12px; align-items: center; margin-top: 6px;">
                                        <div style="color: #2563eb; font-size: 12px; font-weight: 800; letter-spacing: 1px;">ID: #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                        <div style="color: #94a3b8; font-size: 12px; font-weight: 700;"><i class="far fa-calendar-alt" style="margin-right: 4px;"></i> <?= date('d M Y', strtotime($p['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                    <?= getStatusBadge($p['status']) ?>
                                    <span style="font-size: 11px; color: #94a3b8; font-weight: 800;"><?= $p['jarak'] ?> KM DARI GUDANG</span>
                                </div>
                            </div>
                            
                            <!-- Slot Jam Pengantaran -->
                            <?php if (!empty($p['jam_pengantaran'])): ?>
                            <div style="display: inline-flex; align-items: center; gap: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 8px 16px; border-radius: 12px; margin-bottom: 16px;">
                                <i class="fas fa-clock" style="color: #15803d; font-size: 13px;"></i>
                                <span style="font-weight: 800; font-size: 13px; color: #15803d;">Slot Pengantaran: <?= htmlspecialchars($p['jam_pengantaran']) ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="info-grid">
                                <div>
                                    <div class="info-item"><i class="fas fa-phone-flip"></i> <?= htmlspecialchars($p['telepon']) ?></div>
                                    <div class="info-item"><i class="fas fa-location-arrow"></i> <?= htmlspecialchars($p['alamat']) ?></div>
                                    <div class="info-item"><i class="fas fa-box-archive"></i> <?= htmlspecialchars($p['produk_list']) ?></div>
                                    
                                    <div style="margin-top: 24px; display: flex; align-items: center; gap: 16px;">
                                        <div style="font-family: 'Syne', sans-serif; font-weight: 800; color: #0f172a; font-size: 22px;"><?= formatRupiah($p['total']) ?></div>
                                        <span class="badge badge-<?= $p['metode_bayar'] ?>"><?= strtoupper($p['metode_bayar']) ?></span>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 12px; justify-content: flex-end;">
                                    <?php
                                        $noWAKonsumen = getNomorWA(['telepon' => $p['telepon'], 'no_wa' => $p['no_wa']]);
                                        $namaKons = $p['konsumen_nama'];
                                        $namaSopir = $user['nama'];
                                        $pesanTerlambat = "Halo Kak {$namaKons} \u{1F44B}\n\nSaya {$namaSopir} dari Alfa One, sedang dalam perjalanan mengantar pesanan Anda.\n\nMohon maaf, ada sedikit keterlambatan. Kami segera tiba secepatnya. Terima kasih atas kesabarannya \u{1F64F}";
                                    ?>
                                    <?php if ($noWAKonsumen): ?>
                                    <a href="<?= getWADeepLink($noWAKonsumen, $pesanTerlambat) ?>" target="_blank"
                                        style="background: linear-gradient(135deg, #25d366, #128c7e); color: white; text-decoration: none; padding: 12px 18px; border-radius: 16px; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                                        <i class="fab fa-whatsapp" style="font-size: 16px;"></i> Hubungi via WA
                                    </a>
                                    <?php endif; ?>
                                    <a href="detail.php?id=<?= $p['id'] ?>" class="btn-action">BUKA PETA &amp; KONFIRMASI <i class="fas fa-chevron-right"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($penggantianList)): ?>
            <div style="margin-top: 8px;">
                <div style="font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #b45309; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-rotate-left" style="background: #fef3c7; color: #b45309; padding: 10px; border-radius: 14px;"></i>
                    Penggantian Barang Terjadwal
                </div>
                <div style="display: grid; gap: 20px;">
                    <?php foreach ($penggantianList as $k): ?>
                    <div style="background: #ffffff; border-radius: 32px; padding: 28px; border: 2px solid #fde68a; box-shadow: 0 10px 20px -5px rgba(245,158,11,0.08);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px dashed #fef3c7;">
                            <div>
                                <div style="font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($k['konsumen_nama']) ?></div>
                                <div style="display: flex; gap: 12px; align-items: center; margin-top: 6px;">
                                    <div style="color: #b45309; font-size: 12px; font-weight: 800; letter-spacing: 1px;">KOMPLAIN #<?= str_pad($k['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                    <div style="color: #94a3b8; font-size: 12px; font-weight: 700;"><i class="far fa-calendar-alt" style="margin-right: 4px;"></i> <?= date('d M Y', strtotime($k['created_at'])) ?></div>
                                </div>
                            </div>
                            <span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 8px 16px; border-radius: 50px; font-size: 12px; font-weight: 800;">PENGGANTIAN</span>
                        </div>
                        <div class="info-grid penggantian-card-grid">
                            <div>
                                <div class="info-item"><i class="fas fa-phone-flip"></i> <?= htmlspecialchars($k['telepon']) ?></div>
                                <div class="info-item"><i class="fas fa-location-arrow"></i> <?= htmlspecialchars($k['alamat']) ?></div>
                                <div class="info-item" style="margin-top: 12px;">
                                    <i class="fas fa-triangle-exclamation" style="color: #ef4444;"></i>
                                    <span style="font-size: 16px; color: #64748b; font-style: italic;">"<?= htmlspecialchars($k['deskripsi']) ?>"</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: flex-end;">
                                <form method="POST" style="width: 100%;" onsubmit="return confirm('Konfirmasi penggantian barang sudah diantarkan ke ' + '<?= addslashes(htmlspecialchars($k['konsumen_nama'])) ?>' + '?')">
                                    <input type="hidden" name="komplain_id" value="<?= $k['id'] ?>">
                                    <button type="submit" name="selesai_penggantian" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; padding: 14px 20px; border-radius: 18px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 13px; cursor: pointer; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 15px -3px rgba(245,158,11,0.2);">
                                        <i class="fas fa-circle-check"></i> KONFIRMASI SELESAI
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Live Tracking Sopir
if (navigator.geolocation) {
    navigator.geolocation.watchPosition(function(position) {
        fetch('../api/update_lokasi_sopir.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                lat: position.coords.latitude,
                lng: position.coords.longitude
            })
        }).catch(err => console.error("Gagal mengirim lokasi:", err));
    }, function(error) {
        console.error("GPS Error:", error);
    }, {
        enableHighAccuracy: false,
        maximumAge: Infinity // Pakai lokasi terakhir yang diingat laptop agar cepat
    });
}

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
