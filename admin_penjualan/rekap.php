<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_penjualan');
$user = getCurrentUser($pdo);

// Filter
$filterTanggal = $_GET['tanggal'] ?? '';
$filterBulan = $_GET['bulan'] ?? '';
$filterMetode = $_GET['metode'] ?? 'all';
$filterTipe = $_GET['tipe'] ?? 'all';

$where = "p.status != 'menunggu'";
$params = [];

if ($filterTanggal) {
    $where .= " AND DATE(p.created_at) = ?";
    $params[] = $filterTanggal;
} elseif ($filterBulan) {
    $where .= " AND DATE_FORMAT(p.created_at, '%Y-%m') = ?";
    $params[] = $filterBulan;
}

if ($filterMetode !== 'all') {
    $where .= " AND p.metode_bayar = ?";
    $params[] = $filterMetode;
}

if ($filterTipe !== 'all') {
    $where .= " AND u.role_customer = ?";
    $params[] = $filterTipe;
}

$stmt = $pdo->prepare("SELECT p.*, u.nama as konsumen_nama, u.role_customer FROM pesanan p JOIN users u ON p.user_id = u.id WHERE $where ORDER BY p.created_at DESC");
$stmt->execute($params);
$pesananList = $stmt->fetchAll();

// Hitung total & Matrix
$totalCash = 0; $totalTransfer = 0;
$matrix = [
    'toko' => ['cash' => 0, 'transfer' => 0],
    'umum' => ['cash' => 0, 'transfer' => 0]
];

foreach ($pesananList as $p) {
    if ($p['metode_bayar'] === 'cash') $totalCash += $p['total'];
    else $totalTransfer += $p['total'];
    
    $tipe = $p['role_customer'];
    if ($tipe !== 'toko' && $tipe !== 'umum') {
        $tipe = 'umum';
    }
    $metode = $p['metode_bayar'];
    if ($metode !== 'cash' && $metode !== 'transfer') {
        $metode = 'cash';
    }
    $matrix[$tipe][$metode] += $p['total'];
}
$totalAll = $totalCash + $totalTransfer;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Penjualan — Alfa One</title>
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
      
      .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
      .stat-card { background: #ffffff; border-radius: 32px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); transition: .4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.02); }
      .stat-card:hover { transform: translateY(-5px); border-color: #dbeafe; box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.1); }
      
      .stat-card.total-box { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; border: none; box-shadow: 0 20px 40px -10px rgba(29, 78, 216, 0.3); }
      .stat-card.total-box:hover { box-shadow: 0 30px 60px -12px rgba(29, 78, 216, 0.4); }
      
      .stat-label { font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px; display: block; }
      .total-box .stat-label { color: rgba(255,255,255,0.7); }
      .stat-val { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 800; line-height: 1; letter-spacing: -0.5px; }
      
      .section-card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02); }
      
      .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 140px 80px; gap: 24px; align-items: flex-end; margin-bottom: 40px; background: #f8fafc; padding: 32px; border-radius: 32px; border: 1px solid #f1f5f9; }
      .field-label { display: block; font-size: 13px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1.5px; }
      .input { width: 100%; padding: 14px 20px; border-radius: 18px; border: 1px solid rgba(59, 130, 246, 0.12); background: #ffffff; font-family: inherit; font-size: 15px; font-weight: 700; outline: none; transition: .3s; color: #334155; }
      .input:focus { border-color: #2563eb; box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.08); }
      
      .table { width: 100%; border-collapse: collapse; }
      .table th { text-align: left; font-size: 13px; font-weight: 800; color: #94a3b8; padding: 20px 24px; text-transform: uppercase; border-bottom: 1px solid rgba(59, 130, 246, 0.12); letter-spacing: 1.5px; }
      .table td { padding: 24px 24px; font-size: 16px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); color: #334155; }
      
      .btn { padding: 14px 28px; border-radius: 18px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer; transition: .3s; border: none; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 10px; }
      .btn-primary { background: #2563eb; color: white; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2); }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.3); }
      .btn-outline { background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); color: #64748b; }
      .btn-outline:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
      .btn-export { background: #ffffff; border: 2px solid #bbf7d0; color: #15803d; white-space: nowrap; }
      .btn-export:hover { background: #f0fdf4; border-color: #16a34a; color: #15803d; transform: translateY(-2px); box-shadow: 0 8px 20px -5px rgba(22,163,74,0.2); }
      .topbar .btn { white-space: nowrap; }
      
      .badge { padding: 8px 16px; border-radius: 50px; font-size: 11px; font-weight: 800; text-transform: uppercase; border: 1px solid transparent; }
      .badge-cash { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
      .badge-transfer { background: #eff6ff; color: #1e40af; border-color: #dbeafe; }

      .user-pill { display: inline-flex; padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 800; vertical-align: middle; }
      .user-pill.toko { background: #fffbeb; color: #92400e; }
      .user-pill.umum { background: #eff6ff; color: #1d4ed8; }

      .matrix-card { display: flex; flex-direction: column; gap: 16px; }
      .matrix-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #f8fafc; border-radius: 16px; border: 1px solid #f1f5f9; }
      .matrix-label { font-size: 14px; font-weight: 700; color: #64748b; display: flex; align-items: center; gap: 8px; }
      .matrix-val { font-size: 15px; font-weight: 800; color: #0f172a; }

      @media (max-width: 1024px) {
        .main { margin-left: 0; }
        .content { padding: 15px; gap: 20px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        .topbar-info { display: none; } /* Hide clock on mobile */
        .topbar-actions { width: 100%; justify-content: flex-end; }
        .section-card { padding: 24px 15px; border-radius: 30px; }
        .filter-row { grid-template-columns: 1fr; padding: 24px 15px; gap: 15px; }
        .stat-grid { grid-template-columns: 1fr !important; gap: 15px; }
        
        /* Table to Card Transformation */
        .table thead { display: none; }
        .table tbody tr { 
            display: block; 
            background: #f8fafc; 
            border-radius: 20px; 
            margin-bottom: 15px; 
            padding: 15px; 
            border: 1px solid #e2e8f0;
        }
        .table td { 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            width: 100%; 
            padding: 10px 0 !important; 
            border: none !important;
            text-align: left !important;
        }
        .table td::before {
            content: attr(data-label);
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
        }
        .table td:not(:last-child) { border-bottom: 1px dashed #e2e8f0 !important; }
        
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
                <div class="topbar-title"><h2>Laporan Pendapatan & Rekapitulasi</h2></div>
            </div>
            <div class="topbar-actions" style="display: flex; align-items: center; gap: 20px;">
                <div class="topbar-info" style="text-align: right;">
                    <div id="real-time-clock" style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                    <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Admin Penjualan</div>
                </div>
                <a href="export_rekap.php?<?= http_build_query(['tanggal' => $filterTanggal, 'bulan' => $filterBulan, 'metode' => $filterMetode, 'tipe' => $filterTipe]) ?>"
                   class="btn btn-export"
                   style="padding:10px 16px; font-size:13px; width:auto; border-radius:12px;">
                    <i class="fas fa-file-excel"></i> Export <span class="topbar-info">Rekapitulasi</span>
                </a>
            </div>
        </header>

        <div class="content">
            <div class="stat-grid">
                <div class="stat-card">
                    <span class="stat-label">Pendapatan Tunai (Cash)</span>
                    <div class="stat-val" style="color: #16a34a;"><?= formatRupiah($totalCash) ?></div>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Pendapatan Transfer</span>
                    <div class="stat-val" style="color: #2563eb;"><?= formatRupiah($totalTransfer) ?></div>
                </div>
                <div class="stat-card total-box">
                    <span class="stat-label">Total Pendapatan Keseluruhan</span>
                    <div class="stat-val"><?= formatRupiah($totalAll) ?></div>
                </div>
            </div>

            <div class="stat-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="stat-card" style="border-left: 6px solid #facc15;">
                    <span class="stat-label">Pendapatan Akun TOKO</span>
                    <div class="matrix-card">
                        <div class="matrix-item">
                            <span class="matrix-label"><i class="fas fa-money-bill-wave" style="color: #16a34a;"></i> Tunai</span>
                            <span class="matrix-val"><?= formatRupiah($matrix['toko']['cash']) ?></span>
                        </div>
                        <div class="matrix-item">
                            <span class="matrix-label"><i class="fas fa-credit-card" style="color: #2563eb;"></i> Transfer</span>
                            <span class="matrix-val"><?= formatRupiah($matrix['toko']['transfer']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
                            <span style="font-size: 12px; font-weight: 800; color: #94a3b8;">SUBTOTAL OMZET</span>
                            <span style="font-size: 18px; font-weight: 800; color: #0f172a;"><?= formatRupiah($matrix['toko']['cash'] + $matrix['toko']['transfer']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="stat-card" style="border-left: 6px solid #3b82f6;">
                    <span class="stat-label">Pendapatan Akun UMUM</span>
                    <div class="matrix-card">
                        <div class="matrix-item">
                            <span class="matrix-label"><i class="fas fa-money-bill-wave" style="color: #16a34a;"></i> Tunai</span>
                            <span class="matrix-val"><?= formatRupiah($matrix['umum']['cash']) ?></span>
                        </div>
                        <div class="matrix-item">
                            <span class="matrix-label"><i class="fas fa-credit-card" style="color: #2563eb;"></i> Transfer</span>
                            <span class="matrix-val"><?= formatRupiah($matrix['umum']['transfer']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
                            <span style="font-size: 12px; font-weight: 800; color: #94a3b8;">SUBTOTAL OMZET</span>
                            <span style="font-size: 18px; font-weight: 800; color: #0f172a;"><?= formatRupiah($matrix['umum']['cash'] + $matrix['umum']['transfer']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <form method="GET" class="filter-row">
                    <div>
                        <label class="field-label">Periodik Hari</label>
                        <input type="date" name="tanggal" class="input" value="<?= $filterTanggal ?>">
                    </div>
                    <div>
                        <label class="field-label">Periodik Bulan</label>
                        <input type="month" name="bulan" class="input" value="<?= $filterBulan ?>">
                    </div>
                    <div>
                        <label class="field-label">Metode Pembayaran</label>
                        <select name="metode" class="input">
                            <option value="all">Semua Metode</option>
                            <option value="cash" <?= $filterMetode === 'cash' ? 'selected' : '' ?>>Tunai (Cash)</option>
                            <option value="transfer" <?= $filterMetode === 'transfer' ? 'selected' : '' ?>>Transfer Bank</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Kategori</label>
                        <select name="tipe" class="input">
                            <option value="all">Semua Kategori</option>
                            <option value="toko" <?= $filterTipe === 'toko' ? 'selected' : '' ?>>Bisnis (Toko)</option>
                            <option value="umum" <?= $filterTipe === 'umum' ? 'selected' : '' ?>>Personal (Umum)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" title="Terapkan Filter"><i class="fas fa-filter"></i></button>
                    <a href="rekap.php" class="btn btn-outline" title="Reset Jurnal"><i class="fas fa-rotate"></i></a>
                </form>

                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>INVOICE</th>
                                <th>PELANGGAN</th>
                                <th style="min-width: 180px;">WAKTU</th>
                                <th>METODE</th>
                                <th>KATEGORI</th>
                                <th style="text-align: right;">NOMINAL</th>
                                <th style="text-align: center;">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pesananList)): ?>
                                <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 120px 0; font-weight: 800;">
                                    <i class="fas fa-folder-open" style="font-size: 48px; display: block; margin-bottom: 24px; opacity: 0.2;"></i>
                                    DATA TRANSAKSI TIDAK DITEMUKAN
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($pesananList as $p): ?>
                                <tr class="rekap-row">
                                    <td data-label="Invoice" style="font-weight: 800; color: #94a3b8; font-size: 13px;">#<?= str_pad($p['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td data-label="Pelanggan"><div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($p['konsumen_nama']) ?></div></td>
                                    <td data-label="Waktu"><div style="font-size: 15px; font-weight: 700; color: #334155;"><?= formatTanggalIndo($p['created_at']) ?></div></td>
                                    <td data-label="Metode"><span class="badge badge-<?= $p['metode_bayar'] ?>"><?= strtoupper($p['metode_bayar']) ?></span></td>
                                    <td data-label="Kategori"><span class="user-pill <?= $p['role_customer'] ?>"><?= strtoupper($p['role_customer'] ?? 'umum') ?></span></td>
                                    <td data-label="Nominal" style="text-align: right; font-weight: 800; color: #0f172a; font-size: 16px;"><?= formatRupiah($p['total']) ?></td>
                                    <td data-label="Status" style="text-align: center;"><div style="display: flex; justify-content: center;"><?= getStatusBadge($p['status']) ?></div></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
        if (clockEl) { clockEl.innerHTML = `${day}, ${date} ${month} ${year} • ${h}:${m}:${s}`; }
    };
    setInterval(updateClock, 1000);
    updateClock();
</script>
</body>
</html>
