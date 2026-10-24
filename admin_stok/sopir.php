<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_barang');
$user = getCurrentUser($pdo);

$success = '';
$error = '';

// Handle Update Kapasitas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_kapasitas'])) {
    $sopirId = (int)$_POST['sopir_id'];
    $max_dus = (int)$_POST['max_dus'];
    $max_galon = (int)$_POST['max_galon'];
    $stmt = $pdo->prepare("UPDATE users SET max_dus = ?, max_galon = ? WHERE id = ? AND role = 'sopir'");
    if ($stmt->execute([$max_dus, $max_galon, $sopirId])) {
        $success = "Kapasitas muatan berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui kapasitas.";
    }
}

$sopirList = $pdo->query("SELECT * FROM users WHERE role = 'sopir' ORDER BY nama ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kapasitas Sopir — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8; --secondary: #38bdf8; --bg: #f0f6fa; --surface: #ffffff; --text: #0f172a; --muted: #64748b; --border: rgba(59, 130, 246, 0.12);
        --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); --sidebar-text: #f8fafc; --sidebar-muted: #94a3b8;
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif;
      }
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: 'Inter', sans-serif; background:#f0f6fa; color:#0f172a; min-height:100vh; overflow-x:hidden; line-height: 1.6; }
      .layout { display:flex; min-height:100vh; }
      .sidebar { width:280px; background:linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right:1px solid rgba(255,255,255,0.03); display:flex; flex-direction:column; flex-shrink:0; position: fixed; height: 100vh; z-index: 100; box-shadow: 10px 0 40px rgba(15, 23, 42, 0.05); }
      .sidebar-logo { font-family: 'Outfit', sans-serif; font-size:24px; font-weight:800; color:white; padding:32px; border-bottom:1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 12px; letter-spacing: -0.5px; }
      .sidebar-logo i { color: #38bdf8; }
      .sidebar-profile { padding: 24px 32px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; }
      .sidebar-avatar { width: 64px; height: 64px; border-radius: 20px; background: rgba(255,255,255,0.08); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15); overflow: hidden; border: 1px solid rgba(255,255,255,0.1); }
      .sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
      .sidebar-user-info { display: flex; flex-direction: column; gap: 2px; }
      .sidebar-user-name { font-weight: 800; font-size: 16px; color: white; }
      .sidebar-user-role { font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; }

      .sidebar-nav { padding: 24px 16px; flex: 1; overflow-y: auto; }
      .nav-label { font-size: 11px; color: #38bdf8; text-transform: uppercase; letter-spacing: .15em; margin: 20px 16px 8px; font-weight: 800; }
      .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 20px; cursor: pointer; color: #94a3b8; font-size: 15px; transition: .3s ease; margin-bottom: 6px; text-decoration: none; font-weight: 700; }
      .nav-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
      .nav-item.active { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }
      .main { flex:1; margin-left: 280px; display: flex; flex-direction: column; min-height: 100vh; }
      .topbar { height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); border-bottom: 2px solid rgba(56, 189, 248, 0.4); position: sticky; top: 0; z-index: 10; }
      .content { padding: 40px; }
      .card { background: #ffffff; border-radius: 40px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); border-top: 3px solid #38bdf8; box-shadow: 0 10px 30px rgba(37, 99, 235, 0.04); }
      .card-title { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 32px; display: flex; align-items: center; gap: 12px; }
      .table { width: 100%; border-collapse: collapse; }
      .table th { text-align: left; padding: 18px; font-size: 13px; color: #38bdf8; text-transform: uppercase; border-bottom: 1px solid rgba(59, 130, 246, 0.12); }
      .table td { padding: 18px; font-size: 16px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); }
      .form-control { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.12); font-weight: 600; outline: none; transition: .2s; }
      .form-control:focus { border-color: #2563eb; }
      .btn { padding: 12px 24px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; transition: .2s; }
      .btn-primary { background: #2563eb; color: white; }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-2px); opacity: 0.9; }
      .alert { padding: 16px 20px; border-radius: 16px; margin-bottom: 24px; font-weight: 700; }
      .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
      @media (max-width: 1024px) { 
        .sidebar { transform: translateX(-100%); z-index: 1000; transition: .3s; } 
        .sidebar.active { transform: translateX(0); } 
        .main { margin-left: 0; } 
        .content { padding: 15px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        #real-time-clock { display: none; } /* Hide clock on mobile */
        .card { padding: 24px 15px; border-radius: 30px; }
        
        /* Table to Card Transformation */
        .table thead { display: none; }
        .table tbody tr { 
            display: block; 
            background: #f8fafc; 
            border-radius: 20px; 
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
        .table td:not(:last-child) { border-bottom: 1px dashed #e2e8f0 !important; margin-bottom: 10px; }
        .table td::before {
            content: attr(data-label);
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .table td input { width: 100% !important; }
        .table td button { width: 100%; }

        .hamburger { display: flex !important; } 
      }
      .hamburger { display: none; width: 40px; height: 40px; align-items: center; justify-content: center; background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); border-radius: 12px; cursor: pointer; color: #0f172a; font-size: 18px; }
      .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 999; }
      .sidebar-overlay.active { display: block; }
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
            <div style="display: flex; align-items: center; gap: 15px;"><div class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></div><h2>Kelola Kapasitas Sopir</h2></div>
            <div id="real-time-clock" style="font-weight: 800; font-size: 14px;"></div>
        </header>
        <div class="content">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <div class="card">
                <h3 class="card-title"><i class="fas fa-truck-fast"></i> Daftar Kapasitas Sopir</h3>
                <table class="table">
                    <thead><tr><th>NAMA SOPIR</th><th>MAKS DUS</th><th>MAKS GALON</th><th>AKSI</th></tr></thead>
                    <tbody>
                        <?php foreach ($sopirList as $s): ?>
                        <form method="POST">
                            <input type="hidden" name="sopir_id" value="<?= $s['id'] ?>">
                            <tr>
                                <td data-label="Nama Sopir" style="font-weight: 800;"><?= htmlspecialchars($s['nama']) ?></td>
                                <td data-label="Maks Dus"><input type="number" name="max_dus" class="form-control" value="<?= $s['max_dus'] ?>" min="0" style="width: 100px;"></td>
                                <td data-label="Maks Galon"><input type="number" name="max_galon" class="form-control" value="<?= $s['max_galon'] ?>" min="0" style="width: 100px;"></td>
                                <td data-label="Opsi"><button type="submit" name="update_kapasitas" class="btn btn-primary"><i class="fas fa-save"></i> Update Kapasitas</button></td>
                            </tr>
                        </form>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script>
    const updateClock = () => {
        const now = new Date();
        document.getElementById('real-time-clock').innerHTML = now.toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'medium' }).replace(/\./g, ':');
    };
    setInterval(updateClock, 1000); updateClock();
    const hamburgerBtn = document.getElementById('hamburgerBtn'), sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay');
    hamburgerBtn.onclick = () => { sidebar.classList.add('active'); overlay.classList.add('active'); };
    overlay.onclick = () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); };
</script>
</body>
</html>
