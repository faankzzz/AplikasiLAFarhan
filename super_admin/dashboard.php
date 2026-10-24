<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('super_admin');
$user = getCurrentUser($pdo);

// Statistik Sederhana
$totalSales = $pdo->query("SELECT SUM(total) FROM pesanan WHERE status = 'selesai'")->fetchColumn() ?: 0;
$totalOrders = $pdo->query("SELECT COUNT(*) FROM pesanan")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();

// Pesanan Terbaru
$recentOrders = $pdo->query("SELECT p.*, u.nama as customer_name FROM pesanan p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard — Alfa One</title>
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
  --sidebar-width: 280px;
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

html, body {
  width: 100%;
  height: 100%;
  overflow-x: hidden;
}

body { 
  font-family: 'Inter', sans-serif; 
  background: #f0f6fa; 
  color: #1e293b; 
  font-size: 16px;
}

/* ===== LAYOUT ===== */
.layout {
  display: flex;
  width: 100vw;
  min-height: 100vh;
  overflow-x: hidden;
}

/* ===== MAIN CONTENT ===== */
.main {
  margin-left: 280px;
  flex: 1 1 0;
  width: 0; /* let flex handle it */
  min-width: 0;
  padding: 36px 40px;
  box-sizing: border-box;
  overflow-x: hidden;
}

/* ===== HEADER ===== */
.header { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 40px;
  gap: 20px;
}

.header h1 { 
  font-family: 'Outfit', sans-serif; 
  font-size: 30px;
  font-weight: 800; 
  margin: 0;
}

.header p {
  color: #64748b;
  font-weight: 500;
  font-size: 15px;
  margin: 4px 0 0;
}

/* ===== STATS GRID ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  width: 100%;
  margin-bottom: 32px;
  box-sizing: border-box;
}

.stat-card { background: #ffffff; 
  padding: 32px; 
  border-radius: 24px; 
  border: 1px solid rgba(59, 130, 246, 0.12);
  min-width: 0;
}

.stat-card i { 
  font-size: 26px; 
  color: #2563eb; 
  margin-bottom: 18px;
  display: block;
}

.stat-val { 
  font-size: 30px;
  font-weight: 800; 
  font-family: 'Outfit', sans-serif;
  line-height: 1.1;
  word-break: break-word;
}

.stat-label { 
  font-size: 15px;
  color: #64748b; 
  font-weight: 600;
  margin-top: 6px;
}

/* ===== CONTENT GRID ===== */
.content-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 24px;
  width: 100%;
  box-sizing: border-box;
}

.card { background: #ffffff; 
  padding: 36px; 
  border-radius: 28px; 
  border: 1px solid rgba(59, 130, 246, 0.12); border-top: 3px solid #38bdf8;
  min-width: 0;
}

.card-title { 
  font-family: 'Outfit', sans-serif; 
  font-size: 20px;
  font-weight: 800; 
  margin-bottom: 24px; 
  display: flex; 
  align-items: center; 
  gap: 12px; 
}

/* ===== TABLE ===== */
table { width: 100%; border-collapse: collapse; }

th { 
  text-align: left; 
  padding: 14px 16px; 
  font-size: 13px; 
  color: #64748b; 
  text-transform: uppercase; 
  letter-spacing: 1px; 
  border-bottom: 1px solid rgba(59, 130, 246, 0.12); 
  font-weight: 700;
}

td { 
  padding: 18px 16px; 
  font-size: 16px;
  border-bottom: 1px solid rgba(59, 130, 246, 0.12); 
}

.badge { padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 700; }
.badge-success { background: #f0fdf4; color: #166534; }
.badge-warning { background: #fffbeb; color: #92400e; }
.badge-info    { background: #eff6ff; color: #1d4ed8; }

/* ===== QUICK ACTIONS ===== */
.quick-card {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  color: white;
  border: none;
  padding: 36px;
  border-radius: 28px;
}

.quick-card h3 {
  font-family: 'Outfit', sans-serif;
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 20px;
}

.quick-btn-primary {
  background: #ffffff;
  color: #2563eb;
  padding: 18px;
  border-radius: 16px;
  text-decoration: none;
  font-weight: 700;
  font-size: 16px;
  text-align: center;
  display: block;
  margin-bottom: 12px;
  transition: .2s;
}

.quick-btn-primary:hover { opacity: .9; transform: translateY(-1px); }

.quick-btn-ghost {
  background: rgba(255,255,255,0.12);
  color: white;
  padding: 18px;
  border-radius: 16px;
  text-decoration: none;
  font-weight: 700;
  font-size: 16px;
  text-align: center;
  display: block;
  border: 1px solid rgba(255,255,255,0.25);
  transition: .2s;
}

.quick-btn-ghost:hover { background: rgba(255,255,255,0.2); }

/* ===== CLOCK ===== */
#real-time-clock {
  font-weight: 800;
  font-size: 15px;
  text-align: right;
}

.clock-role {
  font-size: 12px;
  color: #2563eb;
  font-weight: 800;
  text-transform: uppercase;
  text-align: right;
}

/* ===== HAMBURGER ===== */
.hamburger { display: none; }

/* ===== OVERLAY ===== */
.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  z-index: 49;
}

.sidebar-overlay.active { display: block; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .content-grid { grid-template-columns: 1fr; }
}

@media (max-width: 1024px) {
  .sidebar.active { transform: translateX(0); z-index: 50; }

  .main { 
    margin-left: 0; 
    width: 100%;
    padding: 24px 20px;
  }

  .hamburger {
    display: flex;
    width: 44px; height: 44px;
    align-items: center; justify-content: center;
    background: #ffffff;
    border: 1px solid rgba(59, 130, 246, 0.12);
    border-radius: 12px;
    cursor: pointer;
    color: #1e293b;
    font-size: 18px;
    flex-shrink: 0;
  }
}

@media (max-width: 640px) {
  .stats-grid { grid-template-columns: 1fr; }
  .stat-val { font-size: 26px; }
  .header h1 { font-size: 22px; }
  .header .top-right-info { display: none; } /* Hide clock on mobile */
  .main { padding: 16px; }
}
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
    <!-- HEADER -->
    <header class="header">
      <div style="display:flex; align-items:center; gap:16px;">
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
          <i class="fas fa-bars"></i>
        </button>
        <div>
          <h1>Halo, <?= htmlspecialchars($user['nama']) ?>! 👋</h1>
          <p>Selamat datang di Pusat Kendali Super Admin.</p>
        </div>
      </div>
      <div class="top-right-info">
        <div id="real-time-clock"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
        <div class="clock-role">Super Admin</div>
      </div>
    </header>

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-val"><?= formatRupiah($totalSales) ?></div>
        <div class="stat-label">Total Pendapatan Menyeluruh</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-receipt"></i></div>
        <div class="stat-val"><?= $totalOrders ?></div>
        <div class="stat-label">Total Pesanan</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-val"><?= $totalUsers ?></div>
        <div class="stat-label">Total Pengguna</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-boxes"></i></div>
        <div class="stat-val"><?= $totalProducts ?></div>
        <div class="stat-label">Total Produk</div>
      </div>
    </div>

    <!-- CONTENT -->
    <div class="content-grid">
      <div class="card">
        <div class="card-title"><i class="fas fa-clock"></i> Pesanan Terbaru</div>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Pelanggan</th>
              <th>Tanggal</th>
              <th>Total</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($recentOrders as $order): ?>
            <tr>
              <td style="font-weight:700;">#<?= $order['id'] ?></td>
              <td><?= htmlspecialchars($order['customer_name']) ?></td>
              <td style="font-size: 14px; font-weight: 500; color: #64748b;"><?= formatTanggalIndo($order['created_at']) ?></td>
              <td style="font-weight:600;"><?= formatRupiah($order['total']) ?></td>
              <td><?= getStatusBadge($order['status']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="quick-card">
        <h3>Beberapa Tindakan</h3>
        <a href="users.php" class="quick-btn-primary">
          <i class="fas fa-user-plus"></i> Tambah Pegawai Baru
        </a>
        <a href="export_laporan_tahunan.php?tahun=<?= date('Y') ?>" class="quick-btn-ghost">
          <i class="fas fa-file-excel"></i> Laporan Tahunan <?= date('Y') ?>
        </a>
      </div>
    </div>
  </main>
</div>

<script>
  const hamburgerBtn = document.getElementById('hamburgerBtn');
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  hamburgerBtn?.addEventListener('click', () => {
    sidebar.classList.add('active');
    sidebarOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  });

  sidebarOverlay?.addEventListener('click', () => {
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
    document.body.style.overflow = '';
  });

  // Real-time Clock
  const days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

  function updateClock() {
    const now = new Date();
    const str = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()+1]} ${now.getFullYear()} • ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;
    const el = document.getElementById('real-time-clock');
    if (el) el.textContent = str;
  }
  setInterval(updateClock, 1000);
  updateClock();
</script>
</body>
</html>