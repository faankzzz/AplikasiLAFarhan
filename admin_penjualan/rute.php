<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_penjualan');
$user = getCurrentUser($pdo);

// Fallback koordinat jika belum terdefinisi di config
if (!defined('GUDANG_LAT')) define('GUDANG_LAT', -6.200000);
if (!defined('GUDANG_LNG')) define('GUDANG_LNG', 106.816666);

$sortBy = $_GET['sort'] ?? 'created_at';
$success = '';
$error = '';

// Handle update rute sopir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rute_sopir'])) {
    $sopirIdRute = (int)$_POST['sopir_id_rute'];
    $ruteAsal    = trim($_POST['rute_asal'] ?? '');
    $ruteTujuan  = trim($_POST['rute_tujuan'] ?? '');
    if ($sopirIdRute && $ruteAsal && $ruteTujuan) {
        $pdo->prepare("UPDATE users SET rute_asal = ?, rute_tujuan = ? WHERE id = ? AND role = 'sopir'")
            ->execute([$ruteAsal, $ruteTujuan, $sopirIdRute]);
        $success = 'Rute sopir berhasil diperbarui!';
    } else {
        $error = 'Isi semua field rute sopir!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_bulk'])) {
    $sopirId = (int)$_POST['sopir_id'];
    $pesananIds = $_POST['pesanan_ids'] ?? [];
    
    if ($sopirId && !empty($pesananIds)) {
        // 1. Hitung muatan total dari semua pesanan yang dipilih
        $placeholders = implode(',', array_fill(0, count($pesananIds), '?'));
        $stmtNew = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN LOWER(pr.satuan) = 'dus' THEN tp.jumlah ELSE 0 END) as total_dus,
                SUM(CASE WHEN LOWER(pr.satuan) = 'galon' THEN tp.jumlah ELSE 0 END) as total_galon
            FROM transaksi_pesanan tp
            JOIN produk pr ON tp.produk_id = pr.id
            WHERE tp.pesanan_id IN ($placeholders)
        ");
        $stmtNew->execute($pesananIds);
        $muatanBaru = $stmtNew->fetch();

        // 2. Hitung muatan aktif sopir saat ini
        $stmtActive = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN LOWER(pr.satuan) = 'dus' THEN tp.jumlah ELSE 0 END) as total_dus,
                SUM(CASE WHEN LOWER(pr.satuan) = 'galon' THEN tp.jumlah ELSE 0 END) as total_galon
            FROM pesanan p
            JOIN transaksi_pesanan tp ON p.id = tp.pesanan_id
            JOIN produk pr ON tp.produk_id = pr.id
            WHERE p.sopir_id = ? AND p.status = 'dikirim'
        ");
        $stmtActive->execute([$sopirId]);
        $muatanAktif = $stmtActive->fetch();

        // 3. Ambil kapasitas sopir
        $stmtCap = $pdo->prepare("SELECT nama, max_dus, max_galon FROM users WHERE id = ?");
        $stmtCap->execute([$sopirId]);
        $cap = $stmtCap->fetch();

        $totalDus = ($muatanAktif['total_dus'] ?? 0) + ($muatanBaru['total_dus'] ?? 0);
        $totalGalon = ($muatanAktif['total_galon'] ?? 0) + ($muatanBaru['total_galon'] ?? 0);

        if ($cap['max_dus'] > 0 && $totalDus > $cap['max_dus']) {
            $error = "Gagal! Kapasitas DUS ".$cap['nama']." penuh ($totalDus/".$cap['max_dus'].")";
        } elseif ($cap['max_galon'] > 0 && $totalGalon > $cap['max_galon']) {
            $error = "Gagal! Kapasitas GALON ".$cap['nama']." penuh ($totalGalon/".$cap['max_galon'].")";
        } else {
            $placeholders = implode(',', array_fill(0, count($pesananIds), '?'));
            $sql = "UPDATE pesanan SET status = 'dikirim', sopir_id = ? WHERE id IN ($placeholders) AND status = 'diproses' AND sopir_id IS NULL";
            $params = array_merge([$sopirId], $pesananIds);
            $pdo->prepare($sql)->execute($params);
            $success = count($pesananIds) . " Pesanan Berhasil Ditugaskan!";
        }
    }
}

$stmt = $pdo->prepare("SELECT p.*, u.nama as konsumen_nama, u.telepon, u.role_customer, s.nama as sopir_nama,
    COALESCE(p.alamat_pengiriman, u.alamat) as alamat, 
    COALESCE(p.latitude_pengiriman, u.latitude) as latitude, 
    COALESCE(p.longitude_pengiriman, u.longitude) as longitude
    FROM pesanan p JOIN users u ON p.user_id = u.id LEFT JOIN users s ON p.sopir_id = s.id WHERE p.status = 'diproses' ORDER BY p.created_at ASC");
$stmt->execute();
$pesananList = $stmt->fetchAll();

foreach ($pesananList as &$p) {
    if ($p['latitude'] && $p['longitude']) {
        $p['jarak'] = haversine(GUDANG_LAT, GUDANG_LNG, $p['latitude'], $p['longitude']);
    } else {
        $p['jarak'] = 0;
    }
}
unset($p);

$sopirList = $pdo->query("
    SELECT u.id, u.nama, u.max_dus, u.max_galon, u.rute_asal, u.rute_tujuan,
           COALESCE(load_data.total_dus, 0) as current_dus,
           COALESCE(load_data.total_galon, 0) as current_galon
    FROM users u
    LEFT JOIN (
        SELECT p.sopir_id,
               SUM(CASE WHEN LOWER(pr.satuan) = 'dus' THEN tp.jumlah ELSE 0 END) as total_dus,
               SUM(CASE WHEN LOWER(pr.satuan) = 'galon' THEN tp.jumlah ELSE 0 END) as total_galon
        FROM pesanan p
        JOIN transaksi_pesanan tp ON p.id = tp.pesanan_id
        JOIN produk pr ON tp.produk_id = pr.id
        WHERE p.status = 'dikirim'
        GROUP BY p.sopir_id
    ) load_data ON u.id = load_data.sopir_id
    WHERE u.role = 'sopir'
    ORDER BY u.nama ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Rute Pengiriman — Alfa One</title>
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
      
      /* SIDEBAR STYLES */
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
      .topbar { height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: #ffffff; border-bottom: 1px solid rgba(59, 130, 246, 0.12); position: sticky; top: 0; z-index: 50; }
      .topbar-title h2 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #0f172a; }
      
      .content { padding: 40px; width: 100%; display: flex; flex-direction: column; gap: 32px; }
      
      /* MAP FIX */
      .map-container { background: #ffffff; border-radius: 40px; overflow: hidden; height: 600px; width: 100%; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05); position: relative; }
      #route-map { width: 100%; height: 100%; min-height: 600px; z-index: 1; }
      
      .assign-card { background: #ffffff; border-radius: 32px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); display: flex; justify-content: space-between; align-items: center; }
      .assign-label { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
      .select-field { padding: 12px 16px; border-radius: 14px; border: 1px solid rgba(59, 130, 246, 0.12); outline: none; width: 240px; font-weight: 700; }
      
      .table-card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); }
      .table { width: 100%; border-collapse: collapse; }
      .table th { text-align: left; font-size: 13px; font-weight: 800; color: #94a3b8; padding: 18px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); text-transform: uppercase; }
      .table td { padding: 20px 18px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); vertical-align: middle; }
      
      .btn { padding: 14px 28px; border-radius: 16px; font-weight: 700; cursor: pointer; transition: .3s; border: none; display: inline-flex; align-items: center; gap: 10px; }
      .btn-primary { background: #2563eb; color: white; } .btn-primary:hover { background: #1d4ed8; }

      .checkbox-custom { width: 20px; height: 20px; accent-color: #2563eb; cursor: pointer; }
      .badge-distance { padding: 6px 14px; background: #eff6ff; color: #2563eb; border-radius: 10px; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }
      .alert { padding: 20px 28px; border-radius: 24px; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }

      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); transition: .3s; }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { padding: 15px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        #real-time-clock { display: none; } /* Hide clock on mobile */
        
        .map-container { height: 350px; border-radius: 30px; }
        #route-map { min-height: 350px; }
        
        .assign-card { flex-direction: column; gap: 20px; align-items: flex-start; padding: 24px 20px; border-radius: 30px; }
        .assign-controls { width: 100%; display: flex; flex-direction: column; gap: 12px; }
        .select-field { width: 100% !important; }
        .btn-primary { width: 100%; }

        .table-card { padding: 20px 15px; border-radius: 30px; }
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

        .hamburger { display: flex !important; }
        .sidebar-overlay.active { display: block; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99; backdrop-filter: blur(4px); }
      }
      .hamburger { display: none; width: 40px; height: 40px; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 12px; cursor: pointer; }
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
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['nama'] ?? 'Admin') ?></div>
                <div class="sidebar-user-role">Pusat Penjualan</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Penjualan</div>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="verifikasi.php" class="nav-item"><i class="fas fa-circle-check"></i> Verifikasi Akun</a>
            <a href="pesanan.php" class="nav-item"><i class="fas fa-receipt"></i> Data Pesanan</a>
            <a href="komplain.php" class="nav-item"><i class="fas fa-circle-exclamation"></i> Komplain & Retur</a>
            <a href="rute.php" class="nav-item active"><i class="fas fa-route"></i> Pengaturan Rute</a>
            <a href="rekap.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Rekapitulasi</a>
            
            <div class="nav-label">Akun</div>
            <a href="profil.php" class="nav-item"><i class="fas fa-user-circle"></i> Profil Akun</a>
            <a href="../logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></div>
                <div class="topbar-title"><h2>Manajemen Rute Pengiriman</h2></div>
            </div>
            <div style="text-align: right;">
                <div id="real-time-clock" style="font-weight: 800; font-size: 14px;"></div>
                <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Admin Penjualan</div>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?><div class="alert"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert" style="background: #fff1f2; color: #e11d48; border-color: #fee2e2;"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

            <div class="map-container">
                <div id="route-map"></div>
            </div>

            <form method="POST" style="margin-top: 40px;">
                <div class="assign-card">
                    <div class="assign-label">
                        <i class="fas fa-truck-ramp-box" style="color: #2563eb; font-size: 20px;"></i>
                        <span>Atur Sopir Pengiriman</span>
                    </div>
                    <div class="assign-controls">
                        <select name="sopir_id" class="select-field" required style="width: 320px;">
                            <option value="">— Pilih Sopir Tersedia —</option>
                            <?php foreach ($sopirList as $s): 
                                $sisaDus = $s['max_dus'] - $s['current_dus'];
                                $sisaGalon = $s['max_galon'] - $s['current_galon'];
                                
                                $txtDus = ($s['max_dus'] > 0 && $sisaDus <= 0) ? " DUS PENUH" : $sisaDus . " Dus";
                                $txtGalon = ($s['max_galon'] > 0 && $sisaGalon <= 0) ? " GALON PENUH" : $sisaGalon . " Galon";

                                $labelSisa = ($s['max_dus'] > 0 || $s['max_galon'] > 0) 
                                    ? "(Sisa Slot Truk: $txtDus, $txtGalon)" 
                                    : "(Tanpa Limit)";
                            ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['nama']) ?> <?= $labelSisa ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_bulk" class="btn btn-primary">TUGASKAN SOPIR <i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>

                <div class="table-card" style="margin-top: 32px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><input type="checkbox" id="selectAll" class="checkbox-custom"></th>
                                <th>NAMA PELANGGAN</th>
                                <th>SOPIR</th>
                                <th>SLOT JAM</th>
                                <th>ESTIMASI JARAK</th>
                                <th>ALAMAT PENGIRIMAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pesananList as $p): ?>
                            <tr>
                                <td data-label="Pilih"><input type="checkbox" name="pesanan_ids[]" value="<?= $p['id'] ?>" class="p-check checkbox-custom"></td>
                                <td data-label="Nama Pelanggan">
                                    <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($p['konsumen_nama']) ?></div>
                                    <div style="font-size: 13px; color: #94a3b8; font-weight: 700; margin-top: 4px;">ORD #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                </td>
                                <td data-label="Sopir">
                                    <?php if ($p['sopir_nama']): ?>
                                        <span style="background: #eff6ff; color: #2563eb; padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-user-check"></i> <?= htmlspecialchars($p['sopir_nama']) ?></span>
                                    <?php else: ?>
                                        <span style="background: #fef2f2; color: #ef4444; padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 12px;">Belum Ditugaskan</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Slot Jam">
                                    <?php if (!empty($p['jam_pengantaran'])): ?>
                                        <span style="background: #f0fdf4; color: #15803d; padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"><i class="fas fa-clock"></i> <?= htmlspecialchars($p['jam_pengantaran']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 12px; font-weight: 700;">Tidak dipilih</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Estimasi Jarak"><span class="badge-distance"><i class="fas fa-map-pin"></i> <?= round($p['jarak'], 1) ?> KM</span></td>
                                <td data-label="Alamat Pengiriman"><div style="font-size: 14px; color: #64748b; font-weight: 600; max-width: 400px; line-height: 1.5;"><?= htmlspecialchars($p['alamat']) ?></div></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- ======================================================
                 SECTION: MANAJEMEN RUTE SOPIR
                 ====================================================== -->
            <div class="table-card" style="margin-top: 16px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px dashed #f1f5f9;">
                    <div style="background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <i class="fas fa-route"></i>
                    </div>
                    <div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #0f172a;">Manajemen Rute Sopir</div>
                        <div style="font-size: 13px; color: #64748b; font-weight: 600;">Atur daerah operasi tiap sopir di wilayah Palembang &amp; sekitarnya</div>
                    </div>
                </div>

                <div style="display: grid; gap: 20px;">
                    <?php foreach ($sopirList as $s): ?>
                    <div style="background: #f8fafc; border-radius: 24px; padding: 24px; border: 1px solid #e2e8f0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div style="width: 48px; height: 48px; border-radius: 16px; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #2563eb;">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 800; font-size: 16px; color: #0f172a;"><?= htmlspecialchars($s['nama']) ?></div>
                                    <?php if (!empty($s['rute_asal']) && !empty($s['rute_tujuan'])): ?>
                                        <div style="margin-top: 4px; display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #2563eb;">
                                            <i class="fas fa-location-dot" style="color: #22c55e;"></i>
                                            <?= htmlspecialchars($s['rute_asal']) ?>
                                            <i class="fas fa-arrow-right" style="font-size: 10px; color: #94a3b8;"></i>
                                            <i class="fas fa-location-dot" style="color: #ef4444;"></i>
                                            <?= htmlspecialchars($s['rute_tujuan']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="margin-top: 4px; font-size: 12px; color: #f59e0b; font-weight: 700;"><i class="fas fa-triangle-exclamation"></i> Rute belum diset</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Form Edit Rute -->
                            <form method="POST" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <input type="hidden" name="sopir_id_rute" value="<?= $s['id'] ?>">
                                <div style="position: relative;">
                                    <i class="fas fa-location-dot" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #22c55e; font-size: 13px;"></i>
                                    <input type="text" name="rute_asal" value="<?= htmlspecialchars($s['rute_asal'] ?? '') ?>" placeholder="Asal (mis: Km 5)" required
                                        style="padding: 10px 14px 10px 32px; border-radius: 14px; border: 1px solid #e2e8f0; font-size: 14px; font-weight: 600; width: 180px; outline: none; transition: .2s;"
                                        onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                                </div>
                                <i class="fas fa-arrow-right" style="color: #94a3b8; font-size: 12px;"></i>
                                <div style="position: relative;">
                                    <i class="fas fa-location-dot" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #ef4444; font-size: 13px;"></i>
                                    <input type="text" name="rute_tujuan" value="<?= htmlspecialchars($s['rute_tujuan'] ?? '') ?>" placeholder="Tujuan (mis: Banyuasin)" required
                                        style="padding: 10px 14px 10px 32px; border-radius: 14px; border: 1px solid #e2e8f0; font-size: 14px; font-weight: 600; width: 200px; outline: none; transition: .2s;"
                                        onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                                </div>
                                <button type="submit" name="update_rute_sopir" style="background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; border: none; padding: 10px 20px; border-radius: 14px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                                    <i class="fas fa-floppy-disk"></i> Simpan Rute
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    // Select All
    document.getElementById('selectAll').onclick = function() {
        document.querySelectorAll('.p-check').forEach(c => c.checked = this.checked);
    };

    // Sidebar Mobile
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (hamburgerBtn) {
        hamburgerBtn.onclick = () => { sidebar.classList.add('active'); overlay.classList.add('active'); };
        overlay.onclick = () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); };
    }

    // Real-time Clock
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        document.getElementById('real-time-clock').innerHTML = now.toLocaleDateString('id-ID', options).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Google Maps
    function initMap() {
        const gudangPos = { lat: <?= GUDANG_LAT ?>, lng: <?= GUDANG_LNG ?> };
        const map = new google.maps.Map(document.getElementById('route-map'), {
            center: gudangPos,
            zoom: 12,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            styles: [
                { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }
            ]
        });

        // Gudang Marker
        new google.maps.Marker({
            position: gudangPos,
            map: map,
            title: 'GUDANG ALFA ONE',
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 14,
                fillColor: '#3b82f6',
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 3
            }
        });

        const infoWindow = new google.maps.InfoWindow();

       // Simpan semua marker
const markers = {};

// Customer Markers
<?php foreach ($pesananList as $p): if ($p['latitude'] && $p['longitude']): ?>
(function() {

    const marker = new google.maps.Marker({
        position: {
            lat: <?= $p['latitude'] ?>,
            lng: <?= $p['longitude'] ?>
        },
        map: map,
        title: '<?= addslashes($p['konsumen_nama']) ?>',

        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 10,

            // MERAH = belum ditugaskan
            // HIJAU = sudah ditugaskan
            fillColor: '<?= $p['sopir_id'] ? "#22c55e" : "#ef4444" ?>',

            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 3
        }
    });

    markers[<?= $p['id'] ?>] = marker;

    marker.addListener('click', function() {

        infoWindow.setContent(`
            <b><?= addslashes($p['konsumen_nama']) ?></b><br>
            ORD #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?><br>
            Status :
            <?= $p['sopir_id'] ? 'Sudah Ditugaskan' : 'Belum Ditugaskan' ?>
        `);

        infoWindow.open(map, marker);

    });

})();
<?php endif; endforeach; ?>

// Klik baris tabel => fokus ke marker
document.querySelectorAll('.order-row').forEach(row => {

    row.addEventListener('clic  k', function() {

        const id = this.dataset.id;
        const marker = markers[id];

        if(marker){

            map.setCenter(marker.getPosition());
            map.setZoom(17);

            google.maps.event.trigger(
                marker,
                'click'
            );

        }

    });

});
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap" async defer></script>
</body>
</html>