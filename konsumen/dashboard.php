<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('konsumen');
$user = getCurrentUser($pdo);

// Hitung statistik
$stmtTotal = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total),0) as pengeluaran FROM pesanan WHERE user_id = ?");
$stmtTotal->execute([getUserId()]);
$stats = $stmtTotal->fetch();

$stmtPending = $pdo->prepare("SELECT COUNT(*) as c FROM pesanan WHERE user_id = ? AND status NOT IN ('selesai')");
$stmtPending->execute([getUserId()]);
$pending = $stmtPending->fetch()['c'];

// Pesanan terbaru
$stmtRecent = $pdo->prepare("SELECT p.*, 
    (SELECT GROUP_CONCAT(pr.nama SEPARATOR ', ') FROM transaksi_pesanan pd JOIN produk pr ON pd.produk_id = pr.id WHERE pd.pesanan_id = p.id) as produk_list
    FROM pesanan p WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 5");
$stmtRecent->execute([getUserId()]);
$recentOrders = $stmtRecent->fetchAll();

$tanggal_indo = formatTanggalIndo(date('Y-m-d H:i:s'));

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Saya — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
:root {
    --primary: #2F56D3;
    --primary-dark: #1E43B8;

    --bg: #F4F8FF;
    --surface: #FFFFFF;

    --text: #0F172A;
    --muted: #64748B;

    --border: #D6E4FF;

    --sidebar-bg: #0f172a;
    --sidebar-text: #F8FAFC;
    --sidebar-muted: #94A3B8;

    --success: #22C55E;
    --warning: #F59E0B;
    --danger: #EF4444;

    --grad-blue: linear-gradient(135deg, #2F56D3 0%, #1E43B8 100%);

    --font-heading: 'Outfit', sans-serif;
    --font-body: 'Inter', sans-serif;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family: 'Inter', sans-serif;
    background: #F4F8FF;
    color: #0F172A;
    min-height:100vh;
    -webkit-font-smoothing: antialiased;
    line-height:1.6;
}

.layout{
    display:flex;
    min-height:100vh;
}

/* ================= SIDEBAR ================= */

.sidebar{
    width:280px;
    background: #0f172a;
    border-right:1px solid rgba(255,255,255,0.05);
    display:flex;
    flex-direction:column;
    flex-shrink:0;
    position:fixed;
    left:0;
    top:0;
    height:100vh;
    z-index:1000;
    transition:.3s;
}

.sidebar-logo{
    padding:24px 0;
    border-bottom:1px solid rgba(255,255,255,0.05);
    display:flex;
    justify-content:center;
}

.sidebar-profile{
    padding:24px 32px;
    border-bottom:1px solid rgba(255,255,255,0.05);
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:12px;
    text-align:center;
}

.sidebar-avatar{
    width:64px;
    height:64px;
    border-radius:20px;
    background: rgba(255,255,255,0.08);
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    border:1px solid rgba(255,255,255,0.08);
}

.sidebar-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.sidebar-user-name{
    font-size:18px;
    font-weight:800;
    color:white;
}

.sidebar-user-role{
    font-size:12px;
    font-weight:700;
    color:#60A5FA;
    text-transform:uppercase;
    letter-spacing:1px;
}

.sidebar-nav{
    padding:24px 16px;
    flex:1;
}

.nav-label{
    font-size:11px;
    color:#94A3B8;
    text-transform:uppercase;
    letter-spacing:.1em;
    margin:20px 16px 8px;
    font-weight:800;
}

.nav-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 16px;
    border-radius:18px;
    color:#94A3B8;
    text-decoration:none;
    font-size:15px;
    font-weight:600;
    transition:.25s;
    margin-bottom:6px;
}

.nav-item:hover{
    background: rgba(255,255,255,0.06);
    color:white;
}

.nav-item.active{
    background: linear-gradient(135deg, #2F56D3 0%, #1E43B8 100%);
    color:white;
    box-shadow:0 12px 25px rgba(47,86,211,0.35);
}

/* ================= MAIN ================= */

.main{
    flex:1;
    margin-left:280px;
    min-height:100vh;
}

/* ================= TOPBAR ================= */

.topbar{
    height:80px;
    padding:0 40px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(14px);
    border-bottom:1px solid #D6E4FF;
    position:sticky;
    top:0;
    z-index:999;
}

.topbar-title h2{
    font-family: 'Outfit', sans-serif;
    font-size:24px;
    font-weight:800;
    color: #0F172A;
}

/* ================= CONTENT ================= */

.content{
    padding:40px;
}

/* ================= WELCOME ================= */

.welcome-banner{
    background: linear-gradient(135deg, #2F56D3 0%, #1E43B8 100%);
    border-radius:36px;
    padding:48px;
    color:white;
    margin-bottom:32px;
    position:relative;
    overflow:hidden;
    box-shadow:0 25px 50px rgba(47,86,211,0.25);
}

.welcome-banner h1{
    font-size:38px;
    font-family: 'Outfit', sans-serif;
    font-weight:800;
    letter-spacing:-1px;
}

.welcome-banner .btn{
    margin-top:30px;
}

/* ================= BUTTON ================= */

.btn{
    padding:14px 26px;
    border-radius:18px;
    font-weight:700;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:10px;
    transition:.3s;
    border:none;
    cursor:pointer;
}

.btn-primary{
    background: #2F56D3;
    color:white;
    box-shadow:0 15px 30px rgba(47,86,211,0.25);
}

.btn-primary:hover{
    background: #1E43B8; transform:translateY(-4px);
}

.btn-outline{
    background:white;
    color: #2F56D3;
    border:1px solid #D6E4FF;
}

.btn-outline:hover{
    background:#F8FBFF;
}

/* ================= QUICK ACTION ================= */

.quick-actions{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:20px;
    margin-bottom:36px;
}

.quick-btn{
    background:white;
    border-radius:28px;
    padding:28px 20px;
    border:1px solid #D6E4FF;
    text-decoration:none;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:14px;
    color:#334155;
    font-weight:700;
    transition:.3s cubic-bezier(0.16,1,0.3,1);
    box-shadow:0 4px 20px rgba(15,23,42,0.04);
}

.quick-btn:hover{
    transform:translateY(-8px);
    box-shadow:0 20px 40px rgba(37,99,235,0.12);
}

.quick-btn .qicon{
    width:56px;
    height:56px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:22px;
}

/* ================= STAT CARD ================= */

.stat-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:24px;
    margin-bottom:40px;
}

.stat-card{
    background:white;
    border-radius:32px;
    padding:30px;
    border:1px solid #D6E4FF;
    transition:.3s cubic-bezier(0.16,1,0.3,1);
    box-shadow:0 4px 20px rgba(15,23,42,0.04);
}

.stat-card:hover{
    transform:translateY(-8px);
    box-shadow:0 20px 40px rgba(37,99,235,0.12);
}

.stat-label{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:1px;
    color:#64748B;
    font-weight:800;
    margin-bottom:8px;
}

.stat-val{
    font-size:30px;
    font-family: 'Outfit', sans-serif;
    font-weight:800;
    color: #0F172A;
}

/* ================= SECTION ================= */

.section-card{
    background:white;
    border-radius:36px;
    padding:40px;
    border:1px solid #D6E4FF;
    box-shadow:0 10px 30px rgba(15,23,42,0.04);
}

.section-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.section-title{
    font-size:24px;
    font-family: 'Outfit', sans-serif;
    font-weight:800;
    color: #0F172A;
}

/* ================= TABLE ================= */

.table{
    width:100%;
    border-collapse:collapse;
}

.table th{
    padding:18px;
    text-align:left;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:1px;
    color:#64748B;
    border-bottom:2px solid #EEF2FF;
}

.table td{
    padding:18px;
    border-bottom:1px solid #EEF2FF;
    font-size:15px;
}

.table tr:hover{
    background:#F8FBFF;
}

/* ================= MOBILE ================= */

.hamburger{
    display:none;
}

@media(max-width:1024px){

    .sidebar{
        transform:translateX(-100%);
    }

    .sidebar.active{
        transform:translateX(0);
    }

    .main{
        margin-left:0;
    }

    .content{
        padding:20px;
    }

    .topbar{
        padding:0 20px;
    }

    .welcome-banner{
        padding:32px;
    }

    .welcome-banner h1{
        font-size:28px;
    }

    .quick-actions{
        grid-template-columns:1fr 1fr;
    }

    .stat-grid{
        grid-template-columns:1fr;
    }

    .hamburger{
        display:flex;
        width:42px;
        height:42px;
        border-radius:14px;
        background:#E5EEFF;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        color:#2F56D3;
    }
}

      .hamburger { display: none; }

      .stat-card { transition: .3s cubic-bezier(0.16,1,0.3,1); cursor: default; }
      .stat-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px -10px rgba(59,130,246,0.12); border-color: #bfdbfe; }

      .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 32px; }
      .quick-btn { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 28px 20px; background: #c8e4f5; border-radius: 28px; border: 1px solid #D6E4FF; text-decoration: none; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 13px; color: #334155; transition: .3s cubic-bezier(0.16,1,0.3,1); text-align: center; }
      .quick-btn:hover { transform: translateY(-8px); box-shadow: 0 20px 40px -10px rgba(59,130,246,0.14); border-color: #bfdbfe; color: #2F56D3; }
      .quick-btn .qicon { width: 52px; height: 52px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; }

    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="layout">
        <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo" style="justify-content: center; padding: 24px 0;">
            <img src="../assets/css/logoo.png" alt="ALFA ONE" style="width: 200px; height: auto; object-fit: contain;">
        </div>
        
        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <?php if ($user['foto']): ?>
                    <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="sidebar-user-role">Member <?= ucfirst($user['role_customer']) ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Menu Utama</div>
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-home nav-icon"></i> Dashboard</a>
            <a href="pesan.php" class="nav-item "><i class="fas fa-shopping-cart nav-icon"></i> Belanja Sekarang</a>
            <a href="pesanan.php" class="nav-item "><i class="fas fa-file-invoice nav-icon"></i> Riwayat Pesanan</a>
            <a href="komplain.php" class="nav-item "><i class="fas fa-headset nav-icon"></i> Pusat Bantuan</a>
            
            <div class="nav-label">Akun</div>
            <a href="profil.php" class="nav-item "><i class="fas fa-user-circle nav-icon"></i> Profil Akun</a>
            <a href="../logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt nav-icon"></i> Keluar</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="hamburger" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="topbar-title"><h2>Dashboard Konsumen</h2></div>
            </div>
            <div class="topbar-info" style="text-align: right;">
                <div style="font-weight: 800; font-size: 14px;"><?= $tanggal_indo ?></div>
                <div style="font-size: 11px; color: #2F56D3; font-weight: 800; text-transform: uppercase;">Konsumen <?= ucfirst($user['role_customer']) ?></div>
            </div>
        </header>

        <div class="content">
            <div class="welcome-banner animate-in">
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 36px; margin-bottom: 12px; letter-spacing: -1px;">Selamat Datang, <?= htmlspecialchars($user['nama']) ?>!</h1>
                <a href="pesan.php" class="btn" style="background: #c8e4f5; color: #2F56D3; margin-top: 32px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">BELANJA SEKARANG <i class="fas fa-arrow-right"></i></a>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="quick-actions">
                <a href="pesan.php" class="quick-btn animate-in">
                    <div class="qicon" style="background: linear-gradient(135deg,#3b82f6,#1d4ed8);"><i class="fas fa-shopping-cart"></i></div>
                    Belanja Sekarang
                </a>
                <a href="pesanan.php" class="quick-btn animate-in">
                    <div class="qicon" style="background: linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-file-invoice"></i></div>
                    Pesanan Saya
                </a>
                <a href="komplain.php" class="quick-btn animate-in">
                    <div class="qicon" style="background: linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-headset"></i></div>
                    Komplain
                </a>
                <a href="profil.php" class="quick-btn animate-in">
                    <div class="qicon" style="background: linear-gradient(135deg,#8b5cf6,#6d28d9);"><i class="fas fa-user-circle"></i></div>
                    Profil Akun
                </a>
            </div>

            <div class="stat-grid">
                <div class="stat-card animate-in">
                    <span class="stat-label">Total Transaksi</span>
                    <div class="stat-val"><?= $stats['total'] ?> Pesanan</div>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Pengeluaran</span>
                    <div class="stat-val"><?= formatRupiah($stats['pengeluaran']) ?></div>
                </div>
                <div class="stat-card" style="background: #eff6ff; border-color: #bfdbfe;">
                    <span class="stat-label" style="color: #1d4ed8;">Aktif Saat Ini</span>
                    <div class="stat-val" style="color: #1d4ed8;"><?= $pending ?> Proses</div>
                </div>
            </div>



            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">Riwayat Pesanan Terbaru</h3>
                    <a href="pesanan.php" class="btn btn-outline" style="font-size: 11px;">RIWAYAT LENGKAP</a>
                </div>
                
                <?php if (empty($recentOrders)): ?>
                    <div style="text-align: center; padding: 60px;">
                        <i class="fas fa-box-open" style="font-size: 48px; color: #e2e8f0; margin-bottom: 20px;"></i>
                        <p style="color: #94a3b8; font-size: 14px; font-weight: 600;">Belum ada pesanan yang tercatat.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Order</th>
                                    <th>Tanggal</th>
                                    <th>Ringkasan Produk</th>
                                    <th style="text-align: right;">Total Bayar</th>
                                    <th style="text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td style="font-weight: 800; color: #2F56D3;">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                    <td style="color: #64748b; font-size: 13px;"><?= formatTanggalIndo($o['created_at']) ?></td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #334155;"><?= htmlspecialchars($o['produk_list'] ?: '-') ?></td>
                                    <td style="text-align: right; font-weight: 800; color: #0f172a;"><?= formatRupiah($o['total']) ?></td>
                                    <td style="text-align: center;"><?= getStatusBadge($o['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
