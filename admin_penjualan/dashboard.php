<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_penjualan');
$user = getCurrentUser($pdo);

// Stats hari ini
$today = date('Y-m-d');
$stmtToday = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total),0) as pendapatan FROM pesanan WHERE DATE(created_at) = ?");
$stmtToday->execute([$today]);
$statsToday = $stmtToday->fetch();

$stmtCash = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM pesanan WHERE DATE(created_at) = ? AND metode_bayar = 'cash' AND status != 'menunggu'");
$stmtCash->execute([$today]);
$cashToday = $stmtCash->fetchColumn();

$stmtTransfer = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM pesanan WHERE DATE(created_at) = ? AND metode_bayar = 'transfer' AND status != 'menunggu'");
$stmtTransfer->execute([$today]);
$transferToday = $stmtTransfer->fetchColumn();

$pendingCount = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status = 'menunggu'")->fetchColumn();

// Pesanan hari ini
$stmtTodayOrders = $pdo->prepare("
    SELECT p.*, u.nama as konsumen_nama, u.role_customer 
    FROM pesanan p 
    JOIN users u ON p.user_id = u.id 
    WHERE DATE(p.created_at) = ? 
    ORDER BY p.created_at DESC
");
$stmtTodayOrders->execute([$today]);
$todayOrders = $stmtTodayOrders->fetchAll();

// Grafik penjualan 5 bulan terakhir - Memecah Cash & Transfer
$stmtGrafik = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as bln, 
        SUM(CASE WHEN metode_bayar = 'cash' THEN total ELSE 0 END) as cash_income,
        SUM(CASE WHEN metode_bayar = 'transfer' THEN total ELSE 0 END) as transfer_income
    FROM pesanan 
    WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 4 MONTH), '%Y-%m-01') 
    AND status != 'menunggu' 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
    ORDER BY bln
");
$dataGrafik = $stmtGrafik->fetchAll();

$labelsGrafik = [];
$valuesCash = [];
$valuesTransfer = [];

$bulanIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Buat array 5 bulan terakhir
for ($i = 4; $i >= 0; $i--) {
    $time = strtotime("-$i months");
    $keyBulan = date('Y-m', $time);
    $namaBulan = $bulanIndo[(int)date('n', $time)] . ' ' . date('Y', $time);
    $labelsGrafik[] = $namaBulan;
     
    $found = false;
    foreach ($dataGrafik as $d) {
        if ($d['bln'] == $keyBulan) {
            $valuesCash[] = (int)$d['cash_income'];
            $valuesTransfer[] = (int)$d['transfer_income'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $valuesCash[] = 0;
        $valuesTransfer[] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
      :root {
        --primary: #2563eb;
        
        --cash: #0d9488;
        --bg: #f0f6fa;
        --surface: #ffffff;
        --text: #1e293b;
        --muted: #64748b;
        --border: rgba(59, 130, 246, 0.12);
        --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        --sidebar-text: #f8fafc;
        --sidebar-muted: #94a3b8;
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        --grad-teal: linear-gradient(135deg, #0d9488 0%, #065f46 100%);

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
      
      .content { padding: 40px; width: 100%; }
      
      .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 32px; margin-bottom: 40px; }
      .stat-card { background: #ffffff; border-radius: 40px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); border-top: 3px solid #38bdf8; box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02); transition: .4s cubic-bezier(0.16, 1, 0.3, 1); }
      .stat-card:hover { transform: translateY(-8px); border-color: #dbeafe; box-shadow: 0 30px 50px -10px rgba(59, 130, 246, 0.1); }
      
      .stat-icon { width: 64px; height: 64px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 24px; }
      .stat-icon.primary { background: #eff6ff; color: #2563eb; }
      .stat-icon.sky { background: #e0f2fe; color: #0284c7; }
      .stat-icon.teal { background: #f0fdf4; color: #0d9488; }
      .stat-icon.amber { background: #fffbeb; color: #d97706; }
      
      .stat-val { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 800; color: #0f172a; line-height: 1; letter-spacing: -0.5px; }
      .stat-label { font-size: 13px; color: #64748b; font-weight: 800; margin-top: 10px; text-transform: uppercase; letter-spacing: 1px; }
      
      .section-card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); border-top: 3px solid #38bdf8; box-shadow: 0 20px 30px -10px rgba(0,0,0,0.03); }
      .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
      .section-title { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #1e40af; letter-spacing: -0.5px; }
      
      .user-pill { display: flex; align-items: center; gap: 12px; padding: 8px 20px; background: #f1f5f9; border-radius: 50px; }
      .avatar { width: 32px; height: 32px; border-radius: 10px; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; }

      /* Table Styles */
      .table { width: 100%; border-collapse: collapse; }
      .table th { text-align: left; font-size: 13px; font-weight: 800; color: #38bdf8; padding: 18px; text-transform: uppercase; border-bottom: 1px solid rgba(59, 130, 246, 0.12); letter-spacing: 1.5px; }
      .table td { padding: 24px 18px; font-size: 16px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); vertical-align: top; color: #334155; font-weight: 600; }
      .user-pill { padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
      .user-pill.toko { background: #fffbeb; color: #92400e; }
      .user-pill.umum { background: #eff6ff; color: #1d4ed8; }

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
        .section-card { padding: 24px 15px; border-radius: 30px; }
        .chart-container { height: 280px !important; }
        
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
                <div class="topbar-title"><h2>Pusat Penjualan</h2></div>
            </div>
            <div class="topbar-info" style="text-align: right;">
                <div id="real-time-clock" style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Admin Penjualan</div>
            </div>
        </header>

        <div class="content">
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fas fa-cart-shopping"></i></div>
                    <div class="stat-val"><?= $statsToday['total'] ?></div>
                    <div class="stat-label">Order Hari Ini</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-wallet"></i></div>
                    <div class="stat-val" style="font-size: 24px;"><?= formatRupiah($cashToday) ?></div>
                    <div class="stat-label">Pendapatan Tunai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon sky"><i class="fas fa-building-columns"></i></div>
                    <div class="stat-val" style="font-size: 24px;"><?= formatRupiah($transferToday) ?></div>
                    <div class="stat-label">Pendapatan Transfer</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fas fa-clock-rotate-left"></i></div>
                    <div class="stat-val" style="color: #d97706;"><?= $pendingCount ?></div>
                    <div class="stat-label">Pending Konfirmasi</div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">Tren Pendapatan (Cash vs Transfer)</h3>
                    <div style="font-size: 13px; font-weight: 800; color: #2563eb; background: #eff6ff; padding: 10px 24px; border-radius: 50px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calendar-check"></i> 5 Bulan Terakhir
                    </div>
                </div>
                <div class="chart-container" style="height: 450px; position: relative;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>

            <div class="section-card" style="margin-top: 40px;">
                <div class="section-header">
                    <h3 class="section-title">Pesanan Masuk Hari Ini</h3>
                    <a href="pesanan.php" style="font-size: 14px; font-weight: 800; color: #2563eb; text-decoration: none;">Lihat Semua <i class="fas fa-arrow-right" style="margin-left: 8px;"></i></a>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th> ID ORDER #</th>
                                <th>KONSUMEN</th>
                                <th>TOTAL PEMBAYARAN</th>
                                <th>METODE</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayOrders as $o): ?>
                            <tr>
                                <td style="font-family: 'Outfit', sans-serif; font-weight: 800; color: #0f172a;">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div style="font-weight: 800; color: #1e293b;"><?= htmlspecialchars($o['konsumen_nama']) ?></div>
                                    <span class="user-pill <?= $o['role_customer'] ?>" style="margin-top: 6px; display: inline-block;"><?= $o['role_customer'] ?></span>
                                </td>
                                <td style="font-weight: 800; color: #2563eb;"><?= formatRupiah($o['total']) ?></td>
                                <td style="text-transform: uppercase;"><?= $o['metode_bayar'] ?></td>
                                <td><?= getStatusBadge($o['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($todayOrders)): ?>
                <div style="text-align: center; padding: 60px 20px; color: #94a3b8; font-weight: 600;">
                    <i class="fas fa-ghost" style="font-size: 32px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>Belum ada pesanan masuk hari ini.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
Chart.defaults.color = '#94a3b8';
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.weight = '600';

const ctx = document.getElementById('mainChart').getContext('2d');

const createGrad = (color1, color2) => {
    const g = ctx.createLinearGradient(0, 0, 0, 400);
    g.addColorStop(0, color1);
    g.addColorStop(1, color2);
    return g;
};

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labelsGrafik) ?>,
        datasets: [
            {
                label: 'Transfer',
                data: <?= json_encode($valuesTransfer) ?>,
                borderColor: '#3b82f6',
                backgroundColor: createGrad('rgba(59, 130, 246, 0.1)', 'rgba(255, 255, 255, 0)'),
                borderWidth: 4,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3b82f6',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 8
            },
            {
                label: 'Cash',
                data: <?= json_encode($valuesCash) ?>,
                borderColor: '#0d9488',
                backgroundColor: createGrad('rgba(13, 148, 136, 0.1)', 'rgba(255, 255, 255, 0)'),
                borderWidth: 4,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0d9488',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 8
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { 
                display: true, 
                position: 'top',
                align: 'end',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 12, weight: '700' }
                }
            },
            datalabels: {
                align: 'top',
                anchor: 'end',
                backgroundColor: 'transparent',
                borderRadius: 4,
                color: (context) => context.dataset.borderColor,
                font: { weight: '800', size: 10 },
                formatter: (v) => v > 0 ? 'Rp ' + v.toLocaleString('id-ID') : '',
                padding: 4,
                offset: 4
            },
            tooltip: {
                backgroundColor: '#0f172a',
                padding: 16,
                titleFont: { size: 14, weight: '800' },
                bodyFont: { size: 14, weight: '700' },
                cornerRadius: 12,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9', drawBorder: false },
                ticks: {
                    callback: v => {
                        if (v % 1 === 0) {
                            return 'Rp ' + v.toLocaleString('id-ID');
                        }
                    },
                    padding: 12,
                    font: { size: 11 }
                },
                grace: '15%'
            },
            x: {
                grid: { display: false },
                ticks: { padding: 12, font: { size: 11 } }
            }
        }
    },
    plugins: [ChartDataLabels]
});

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
updateClock();
</script>
</body>
</html>

