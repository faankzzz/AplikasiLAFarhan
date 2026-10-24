<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_barang');
$user = getCurrentUser($pdo);

// Statistik
$totalProduk = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$totalStok = $pdo->query("SELECT COALESCE(SUM(stok),0) FROM produk")->fetchColumn();
$hampirHabis = $pdo->query("SELECT COUNT(*) FROM produk WHERE stok <= 20")->fetchColumn();

// Statistik Harian (Transparansi Masuk/Keluar Hari Ini)
$masukHariIni = $pdo->query("SELECT COALESCE(SUM(jumlah),0) FROM mutasi_stok WHERE tipe = 'masuk' AND DATE(tanggal) = CURDATE()")->fetchColumn();

// Keluar = Semua mutasi keluar (penjualan + pengurangan manual) sudah dicatat di mutasi_stok
$keluarHariIni = $pdo->query("SELECT COALESCE(SUM(jumlah),0) FROM mutasi_stok WHERE tipe = 'keluar' AND DATE(tanggal) = CURDATE()")->fetchColumn();

// Data grafik stok masuk (30 hari terakhir)
$stmtMasuk = $pdo->query("SELECT DATE(tanggal) as tgl, SUM(jumlah) as total FROM mutasi_stok WHERE tipe = 'masuk' AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(tanggal) ORDER BY tgl");
$dataMasuk = $stmtMasuk->fetchAll();

// Data grafik stok keluar (30 hari terakhir) - dari mutasi_stok
$stmtKeluar = $pdo->query("SELECT DATE(tanggal) as tgl, SUM(jumlah) as total FROM mutasi_stok WHERE tipe = 'keluar' AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(tanggal) ORDER BY tgl");
$dataKeluar = $stmtKeluar->fetchAll();

// Produk hampir habis list
$produkHabis = $pdo->query("SELECT * FROM produk WHERE stok <= 20 ORDER BY stok ASC LIMIT 10")->fetchAll();

$labelsMasuk = array_map(fn($d) => date('d/m', strtotime($d['tgl'])), $dataMasuk);
$valuesMasuk = array_map(fn($d) => (int)$d['total'], $dataMasuk);
$labelsKeluar = array_map(fn($d) => date('d/m', strtotime($d['tgl'])), $dataKeluar);
$valuesKeluar = array_map(fn($d) => (int)$d['total'], $dataKeluar);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Stok — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>

<style>
.topbar,
.content,
main,
body,
*,
*:focus {
    border-top: 0 !important;
    outline: none !important;
    -webkit-tap-highlight-color: transparent !important;
}

:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --secondary: #38bdf8;
    --accent: #0ea5e9;

    --bg: #f0f6fa;
    --surface: #ffffff;
    --surface-soft: #f8fafc;

    --text: #0f172a;
    --muted: #64748b;

    --border: rgba(59, 130, 246, 0.12);

    --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);

    --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);

    --shadow:
        0 10px 30px rgba(37, 99, 235, 0.04);

    --shadow-hover:
        0 20px 40px rgba(37, 99, 235, 0.08);

    --font-heading: 'Outfit', sans-serif;
    --font-body: 'Inter', sans-serif;

    --radius: 28px;
    --radius-lg: 40px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;

    background:
        radial-gradient(circle at top left,
            rgba(56, 189, 248, 0.15),
            transparent 35%),

        radial-gradient(circle at bottom right,
            rgba(37, 99, 235, 0.08),
            transparent 40%),

        #f0f6fa;

    color: #0f172a;

    min-height: 100vh;

    overflow-x: hidden;

    -webkit-font-smoothing: antialiased;

    line-height: 1.6;
}

/* ==========================================================================
   LAYOUT
   ========================================================================== */

.layout {
    display: flex;
    min-height: 100vh;
    max-width: 100vw;
    overflow-x: hidden;
}

/* ==========================================================================
   SIDEBAR
   ========================================================================== */

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
    color: #64748b;
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

/* ==========================================================================
   MAIN
   ========================================================================== */

.main {
    flex: 1;

    margin-left: 280px;

    display: flex;
    flex-direction: column;

    min-height: 100vh;
    max-width: 100vw;
    overflow-x: hidden;
}

/* ==========================================================================
   TOPBAR
   ========================================================================== */

.topbar {
    height: 85px;

    padding: 0 40px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);

    backdrop-filter: blur(14px);

    border-bottom: 2px solid rgba(56, 189, 248, 0.4);

    position: sticky;
    top: 0;

    z-index: 10;
}

.topbar-title h2 {
    font-family: 'Outfit', sans-serif;

    font-size: 24px;
    font-weight: 800;

    color: #1e40af;

    letter-spacing: -.5px;
}

.topbar-info {
    background: rgba(255, 255, 255, 0.8);

    padding: 12px 18px;

    border-radius: 18px;

    border: 1px solid #dbeafe;
}

/* ==========================================================================
   CONTENT
   ========================================================================== */

.content {
    padding: 40px;

    display: flex;
    flex-direction: column;

    gap: 32px;
}

/* ==========================================================================
   CARD UMUM
   ========================================================================== */

.card,
.stat-card,
.content>div:first-child {
    background: rgba(255, 255, 255, 0.88);

    backdrop-filter: blur(16px);

    border-radius: 34px;

    border: 1px solid rgba(255, 255, 255, 0.8);
    border-top: 3px solid #38bdf8;

    box-shadow: 0 10px 30px rgba(37, 99, 235, 0.04);

    transition: .35s ease;
}

.card:hover,
.stat-card:hover {
    transform: translateY(-6px);

    box-shadow:
        0 25px 45px rgba(37, 99, 235, 0.14);
}

/* ==========================================================================
   MONITORING
   ========================================================================== */

.monitoring-grid,
.monitoring-grid * {
    user-select: none;
}

/* ==========================================================================
   STAT GRID
   ========================================================================== */

.stat-grid {
    display: grid;

    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));

    gap: 24px;
}

.stat-card {
    padding: 28px;

    display: flex;
    align-items: center;

    gap: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;

    border-radius: 20px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 24px;

    flex-shrink: 0;
}

.stat-icon.primary {
    background: #dbeafe;
    color: #2563eb;
}

.stat-icon.sky {
    background: #e0f2fe;
    color: #0284c7;
}

.stat-icon.amber {
    background: #fee2e2;
    color: #ef4444;
}

.stat-val {
    font-family: 'Outfit', sans-serif;

    font-size: 34px;
    font-weight: 800;

    color: #0f172a;

    line-height: 1;
}

.stat-label {
    font-size: 12px;

    font-weight: 800;

    color: #64748b;

    text-transform: uppercase;

    letter-spacing: 1px;

    margin-top: 10px;
}

/* ==========================================================================
   CARD GRID
   ========================================================================== */

.card-grid {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 32px;
}

.card {
    padding: 32px;
}

.card-title {
    font-family: 'Outfit', sans-serif;

    font-size: 22px;
    font-weight: 800;

    margin-bottom: 24px;

    display: flex;
    align-items: center;
    gap: 12px;

    color: #0f172a;
}

.card-title i {
    color: #2563eb;
}

/* ==========================================================================
   TABLE
   ========================================================================== */

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    text-align: left;

    font-size: 12px;
    font-weight: 800;

    color: #38bdf8;

    padding: 18px;

    text-transform: uppercase;

    border-bottom: 1px solid rgba(59, 130, 246, .08);

    letter-spacing: 1px;
}

.table td {
    padding: 18px;

    font-size: 15px;

    border-bottom: 1px solid rgba(59, 130, 246, .06);

    color: #334155;
}

.table tbody tr {
    transition: .3s ease;
}

.table tbody tr:hover {
    background: #eff6ff;
}

/* ==========================================================================
   BADGE
   ========================================================================== */

.badge {
    padding: 8px 16px;

    border-radius: 999px;

    font-size: 11px;
    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.badge-danger {
    background: #fee2e2;
    color: #dc2626;

    border: 1px solid #fecaca;
}

.badge-warning {
    background: #fef3c7;
    color: #b45309;

    border: 1px solid #fde68a;
}

/* ==========================================================================
   CHART
   ========================================================================== */

.chart-container {
    position: relative;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

/* ==========================================================================
   HAMBURGER
   ========================================================================== */

.hamburger {
    display: none;
}

/* ==========================================================================
   MOBILE
   ========================================================================== */

@media (max-width: 1024px) {

    .sidebar {
        transform: translateX(-100%);
        z-index: 1000;
        transition: transform .35s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 20px 0 40px rgba(15, 23, 42, 0.15);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main {
        margin-left: 0;
    }

    .content {
        padding: 20px;
    }

    .topbar {
        padding: 16px 20px;

        height: auto;

        min-height: 85px;

        flex-direction: column;
        align-items: flex-start;
        justify-content: center;

        gap: 12px;
    }

    .topbar>div:first-child {
        width: 100%;

        justify-content: space-between !important;
    }

    .topbar-info {
        display: none;
    }

    .stat-grid {
        grid-template-columns: 1fr;
    }

    .card-grid {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 20px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow: hidden;
        min-width: 0;
    }

    .table thead { display: none; }
    .table tbody tr { display: block; background: #f8fafc; border-radius: 18px; margin: 16px 0; padding: 16px; border: 1px solid rgba(59, 130, 246, 0.12); }
    .table td { display: block; width: 100%; padding: 10px 0 !important; border: none !important; text-align: left !important; }
    .table td:not(:last-child) { border-bottom: 1px dashed rgba(59, 130, 246, 0.1) !important; margin-bottom: 8px; }
    .table td::before { content: attr(data-label); display: block; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }

    .monitoring-grid {
        grid-template-columns: 1fr !important;
    }

    .chart-container {
        height: 240px !important;
    }

    .sidebar-overlay {
        display: none;

        position: fixed;
        inset: 0;

        background: rgba(15, 23, 42, 0.5);

        backdrop-filter: blur(4px);

        z-index: 999;
    }

    .sidebar-overlay.active {
        display: block;
    }

    .hamburger {
        display: flex !important;

        width: 44px;
        height: 44px;

        align-items: center;
        justify-content: center;

        background: white;

        border-radius: 14px;

        cursor: pointer;

        color: #0f172a;

        font-size: 18px;

        box-shadow:
            0 6px 16px rgba(0, 0, 0, .08);
    }
}

@media(max-width:576px) {

    .topbar-title h2 {
        font-size: 20px;
    }

    .card-title {
        font-size: 18px;
    }

    .stat-val {
        font-size: 28px;
    }

    .table th,
    .table td {
        padding: 14px 10px;
    }
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
                <div class="topbar-title"><h2>Dashboard Admin Barang</h2></div>
            </div>
            <div class="topbar-info" style="text-align: right;">
                <div id="real-time-clock" style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Admin Barang</div>
            </div>
        </header>

        <div class="content">
            <!-- Monitoring Transparansi Hari Ini -->
            <div style="background: #ffffff; border-radius: 40px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 44px; height: 44px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #2563eb;">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div>
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #0f172a; margin: 0;">Monitoring Hari Ini</h3>
                            <p id="real-time-clock-2" style="font-size: 12px; color: #64748b; margin: 0; font-weight: 600;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="monitoring-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 24px; border-radius: 24px; display: flex; align-items: center; gap: 20px;">
                        <div style="width: 52px; height: 52px; background: rgba(59, 130, 246, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #166534; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div>
                            <div style="font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #166534;"><?= number_format($masukHariIni) ?></div>
                            <div style="font-size: 11px; font-weight: 700; color: #15803d; text-transform: uppercase; letter-spacing: 0.5px;">Barang Masuk (Hari Ini)</div>
                        </div>
                    </div>
                    
                    <div style="background: #fff1f2; border: 1px solid #fecaca; padding: 24px; border-radius: 24px; display: flex; align-items: center; gap: 20px;">
                        <div style="width: 52px; height: 52px; background: #c8e4f5; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #991b1b; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div>
                            <div style="font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #991b1b;"><?= number_format($keluarHariIni) ?></div>
                            <div style="font-size: 11px; font-weight: 700; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px;">Barang Keluar (Hari Ini)</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fas fa-box-open"></i></div>
                    <div>
                        <div class="stat-val"><?= $totalProduk ?></div>
                        <div class="stat-label">Varian Produk</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon sky"><i class="fas fa-warehouse"></i></div>
                    <div>
                        <div class="stat-val"><?= number_format($totalStok) ?></div>
                        <div class="stat-label">Total Persediaan</div>
                    </div>
                </div>
                <div class="stat-card" style="background: #fff1f2; border-color: #fecaca;">
                    <div class="stat-icon amber" style="background: #fee2e2; color: #ef4444;"><i class="fas fa-triangle-exclamation"></i></div>
                    <div>
                        <div class="stat-val" style="color: #b91c1c;"><?= $hampirHabis ?></div>
                        <div class="stat-label" style="color: #b91c1c;">Stok Hampir Habis/Stok Habis</div>
                    </div>
                </div>
            </div>

            <div class="card-grid">
                <div class="card">
                    <h3 class="card-title"><i class="fas fa-circle-arrow-down"></i> Barang Masuk (30 Hari)</h3>
                    <div class="chart-container" style="height: 320px;"><canvas id="chartMasuk"></canvas></div>
                </div>
                <div class="card">
                    <h3 class="card-title"><i class="fas fa-circle-arrow-up"></i> Barang Keluar (30 Hari)</h3>
                    <div class="chart-container" style="height: 320px;"><canvas id="chartKeluar"></canvas></div>
                </div>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h3 class="card-title" style="margin-bottom: 0;"><i class="fas fa-clipboard-check"></i> Produk Dibawah Ambang Batas</h3>
                    <a href="mutasi_stok.php" style="font-size: 12px; font-weight: 800; color: #2563eb; text-decoration: none;">Update Persediaan <i class="fas fa-chevron-right"></i></a>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>NAMA PRODUK</th>
                                <th>UNIT TERSEDIA</th>
                                <th>SATUAN</th>
                                <th>STATUS ALERT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produkHabis as $p): ?>
                            <tr>
                                <td data-label="Nama Produk" style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($p['nama']) ?></td>
                                <td data-label="Unit Tersedia" style="font-family: 'Inter', sans-serif; font-weight: 800; color: #ef4444;"><?= $p['stok'] ?></td>
                                <td data-label="Satuan" style="font-weight: 700; color: #64748b;"><?= strtoupper($p['satuan']) ?></td>
                                <td data-label="Status Alert">
                                    <?php if ($p['stok'] == 0): ?>
                                        <span class="badge badge-danger">KOSONG TOTAL</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">AMBANG BATAS</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
Chart.defaults.color = '#94a3b8';
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.weight = '600';

const setupChart = (id, labels, values, color, areaColor) => {
    const ctx = document.getElementById(id).getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 300);
    grad.addColorStop(0, areaColor);
    grad.addColorStop(1, 'rgba(255,255,255,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                borderColor: color,
                backgroundColor: grad,
                borderWidth: 4,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: color,
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 8,
                pointHitRadius: 10,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: { 
                legend: { display: false },
                datalabels: {
                    align: 'top',
                    anchor: 'end',
                    backgroundColor: areaColor === 'rgba(59, 130, 246, 0.1)' ? 'rgba(59, 130, 246, 0.1)' : 'rgba(100, 116, 139, 0.1)',
                    borderRadius: 4,
                    color: color,
                    font: { weight: '800', size: 10 },
                    formatter: function(value) { return value > 0 ? value : ''; },
                    padding: 4
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { padding: 10 } },
                y: { 
                    grid: { color: '#f1f5f9', drawBorder: false }, 
                    ticks: { padding: 10, stepSize: 1, precision: 0 },
                    grace: '15%'
                }
            }
        },
        plugins: [ChartDataLabels]
    });
};

setupChart('chartMasuk', <?= json_encode($labelsMasuk) ?>, <?= json_encode($valuesMasuk) ?>, '#3b82f6', 'rgba(59, 130, 246, 0.1)');
setupChart('chartKeluar', <?= json_encode($labelsKeluar) ?>, <?= json_encode($valuesKeluar) ?>, '#64748b', 'rgba(100, 116, 139, 0.05)');

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
    const clockEl2 = document.getElementById('real-time-clock-2');
    const timeStr = `${day}, ${date} ${month} ${year} • ${h}:${m}:${s}`;
    
    if (clockEl) clockEl.innerHTML = timeStr;
    if (clockEl2) clockEl2.innerHTML = timeStr;
};
setInterval(updateClock, 1000);
</script>
</body>
</html>
