<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fonnte.php';
requireRole('admin_penjualan');
$user = getCurrentUser($pdo);

$success = flash('success');
$error = '';

// Aksi update status
if (isset($_POST['action'])) {
    $pesananId = (int)$_POST['pesanan_id'];
    $action = $_POST['action'];
    
    if ($action === 'konfirmasi') {
        $pdo->prepare("UPDATE pesanan SET status = 'diproses' WHERE id = ? AND status = 'menunggu'")->execute([$pesananId]);
        
        // Kirim notifikasi WA ke konsumen
        $stmtKons = $pdo->prepare("SELECT u.nama, u.telepon, u.no_wa, p.jam_pengantaran FROM pesanan p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $stmtKons->execute([$pesananId]);
        $konsumen = $stmtKons->fetch();
        if ($konsumen) {
            $noWA = getNomorWA($konsumen);
            $noOrder = '#' . str_pad($pesananId, 5, '0', STR_PAD_LEFT);
            $jamInfo = !empty($konsumen['jam_pengantaran']) ? "\nEstimasi pengantaran: *{$konsumen['jam_pengantaran']}*." : '';
            $pesanWA = "Halo *{$konsumen['nama']}*! 👋\n\n"
                     . "Pesanan Anda {$noOrder} telah *dikonfirmasi* dan sedang kami proses. ✅{$jamInfo}\n\n"
                     . "Terima kasih telah berbelanja di *Alfa One*!";
            if ($noWA) kirimNotifikasiWA($noWA, $pesanWA);
        }
        $success = 'Pesanan #'.$pesananId.' Dikonfirmasi!';

    } elseif ($action === 'siap_kirim') {
        $sopirId = (int)$_POST['sopir_id'];
        
        // 1. Hitung muatan pesanan ini
        $stmtThis = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN LOWER(pr.satuan) = 'dus' THEN tp.jumlah ELSE 0 END) as total_dus,
                SUM(CASE WHEN LOWER(pr.satuan) = 'galon' THEN tp.jumlah ELSE 0 END) as total_galon
            FROM transaksi_pesanan tp
            JOIN produk pr ON tp.produk_id = pr.id
            WHERE tp.pesanan_id = ?
        ");
        $stmtThis->execute([$pesananId]);
        $muatanBaru = $stmtThis->fetch();
        
        // 2. Hitung muatan aktif sopir (status siap_dikirim atau dikirim)
        $stmtActive = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN LOWER(pr.satuan) = 'dus' THEN tp.jumlah ELSE 0 END) as total_dus,
                SUM(CASE WHEN LOWER(pr.satuan) = 'galon' THEN tp.jumlah ELSE 0 END) as total_galon
            FROM pesanan p
            JOIN transaksi_pesanan tp ON p.id = tp.pesanan_id
            JOIN produk pr ON tp.produk_id = pr.id
            WHERE p.sopir_id = ? AND p.status IN ('siap_dikirim', 'dikirim')
        ");
        $stmtActive->execute([$sopirId]);
        $muatanAktif = $stmtActive->fetch();
        
        // 3. Ambil kapasitas sopir
        $stmtCap = $pdo->prepare("SELECT nama, max_dus, max_galon FROM users WHERE id = ?");
        $stmtCap->execute([$sopirId]);
        $cap = $stmtCap->fetch();
        
        $totalDus = ($muatanAktif['total_dus'] ?? 0) + ($muatanBaru['total_dus'] ?? 0);
        $totalGalon = ($muatanAktif['total_galon'] ?? 0) + ($muatanBaru['total_galon'] ?? 0);
        
        // Validasi: Cek jika kapasitas diset (>0) dan total muatan melebihi kapasitas
        if ($cap['max_dus'] > 0 && $totalDus > $cap['max_dus']) {
            $error = "Kapasitas DUS Penuh! ".$cap['nama']." saat ini membawa ".($muatanAktif['total_dus']??0)." dus. Total akan menjadi $totalDus dus (Maks: ".$cap['max_dus'].")";
        } elseif ($cap['max_galon'] > 0 && $totalGalon > $cap['max_galon']) {
            $error = "Kapasitas GALON Penuh! ".$cap['nama']." saat ini membawa ".($muatanAktif['total_galon']??0)." galon. Total akan menjadi $totalGalon galon (Maks: ".$cap['max_galon'].")";
        } else {
            // Cek status pesanan saat ini
            $stmtStatus = $pdo->prepare("SELECT status FROM pesanan WHERE id = ?");
            $stmtStatus->execute([$pesananId]);
            $currentStatus = $stmtStatus->fetchColumn();

            if (in_array($currentStatus, ['dikirim', 'selesai'])) {
                $pdo->prepare("UPDATE pesanan SET sopir_id = ? WHERE id = ?")->execute([$sopirId, $pesananId]);
            } else {
                $pdo->prepare("UPDATE pesanan SET status = 'dikirim', sopir_id = ? WHERE id = ?")->execute([$sopirId, $pesananId]);
            }

            // Kirim Notifikasi WhatsApp ke Konsumen
            $stmtInfo = $pdo->prepare("
                SELECT 
                    p.id AS pesanan_id,
                    p.jam_pengantaran,
                    c.nama AS konsumen_nama,
                    c.telepon AS konsumen_telp,
                    c.no_wa AS konsumen_wa,
                    s.nama AS sopir_nama,
                    s.telepon AS sopir_telp
                FROM pesanan p
                JOIN users c ON p.user_id = c.id
                JOIN users s ON s.id = ?
                WHERE p.id = ?
            ");
            $stmtInfo->execute([$sopirId, $pesananId]);
            $info = $stmtInfo->fetch();

            if ($info) {
                $targetNo = !empty($info['konsumen_wa']) ? $info['konsumen_wa'] : $info['konsumen_telp'];
                $jamStr = "-";
                if (!empty($info['jam_pengantaran'])) {
                    $slotJamMapping = [
                        'pagi'   => '08:00 – 10:00',
                        'siang'  => '10:00 – 13:00',
                        'sore'   => '13:00 – 17:30',
                    ];
                    $jamStr = ucfirst($info['jam_pengantaran']) . " (" . ($slotJamMapping[$info['jam_pengantaran']] ?? '') . ")";
                }
                
                $pesanWA = "Halo " . $info['konsumen_nama'] . ",\n\n"
                         . "Kabar baik! Pesanan Anda dengan ID #" . $info['pesanan_id'] . " saat ini sedang dalam proses pengiriman oleh kurir/sopir kami.\n\n"
                         . "Detail Kurir:\n"
                         . "- Nama: " . $info['sopir_nama'] . "\n"
                         . "- No. Telp/WA: " . $info['sopir_telp'] . "\n"
                         . "- Estimasi Pengiriman: " . $jamStr . "\n\n"
                         . "Mohon ditunggu di lokasi pengiriman ya. Terima kasih!";
                         
                kirimNotifikasiWA($targetNo, $pesanWA);
            }

            $success = 'Kurir Ditugaskan untuk #'.$pesananId.'!';
        }
    }
}

// Filter status
$filterStatus = $_GET['status'] ?? 'all';
$countries = ['all', 'menunggu', 'diproses', 'dikirim', 'selesai'];
if (!in_array($filterStatus, $countries)) $filterStatus = 'all';

$where = "1=1";
$params = [];
if ($filterStatus !== 'all') {
    $where .= " AND p.status = ?";
    $params[] = $filterStatus;
}

$stmt = $pdo->prepare("SELECT p.*, u.nama as konsumen_nama, u.telepon, u.alamat, u.role_customer,
    u2.nama as sopir_nama
    FROM pesanan p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN users u2 ON p.sopir_id = u2.id
    WHERE $where ORDER BY p.created_at DESC");
$stmt->execute($params);
$pesananList = $stmt->fetchAll();

// Daftar sopir beserta rute
$sopirList = $pdo->query("SELECT id, nama, rute_asal, rute_tujuan FROM users WHERE role = 'sopir'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan — Alfa One</title>
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
      .topbar .btn { white-space: nowrap;}
      .content { padding: 40px; width: 100%; display: flex; flex-direction: column; gap: 32px; }
      
      /* Filter Tabs */
      .status-tabs { display: flex; gap: 12px; margin-bottom: 8px; overflow-x: auto; padding-bottom: 12px; }
      .tab { padding: 14px 28px; border-radius: 50px; background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); color: #64748b; text-decoration: none; font-size: 14px; font-weight: 700; white-space: nowrap; transition: .3s; font-family: 'Outfit', sans-serif; }
      .tab:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
      .tab.active { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); border-color: transparent; color: white; box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.3); }
      
      /* Order Card */
      .order-card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); transition: .4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02); }
      .order-card:hover { border-color: #dbeafe; transform: translateY(-5px); box-shadow: 0 30px 60px -15px rgba(59, 130, 246, 0.12); }
      
      .order-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 2px dashed #f1f5f9; }
      .order-id { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #1e40af; letter-spacing: -0.5px; }
      .user-pill { display: inline-flex; padding: 8px 16px; border-radius: 12px; font-size: 11px; font-weight: 800; margin-left:14px; vertical-align: middle; }
      .user-pill.toko { background: #fffbeb; color: #92400e; }
      .user-pill.umum { background: #eff6ff; color: #1d4ed8; }
      
      .order-grid { display: grid; grid-template-columns: 1fr 340px; gap: 48px; }
      
      .info-sec { margin-bottom: 32px; }
      .label { font-size: 13px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px; display: block; }
      .val { font-size: 16px; font-weight: 600; line-height: 1.6; color: #334155; }
      
      .item-list { width: 100%; border-collapse: collapse; margin-top: 12px; }
      .item-list th { text-align: left; font-size: 13px; font-weight: 800; color: #94a3b8; padding: 12px 0; border-bottom: 1px solid rgba(59, 130, 246, 0.12); text-transform: uppercase; letter-spacing: 1px; }
      .item-list td { padding: 16px 0; font-size: 16px; font-weight: 700; border-bottom: 1px solid rgba(59, 130, 246, 0.12); color: #334155; }
      
      .payment-box { background: #f8fafc; border-radius: 32px; padding: 32px; border: 1px solid #f1f5f9; }
      .summary-item { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 16px; font-weight: 700; color: #64748b; }
      .final-price { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; color: #2563eb; margin-top: 16px; display: block; letter-spacing: -0.5px; }
      
      .btn { padding: 16px 28px; border-radius: 20px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer; transition: .3s; border: none; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 12px; width: 100%; }
      .btn-primary { background: #2563eb; color: white; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2); }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.3); }
      .btn-outline { background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); color: #64748b; }
      .btn-outline:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
      
      .select-input { width: 100%; padding: 14px 20px; border-radius: 18px; border: 1px solid rgba(59, 130, 246, 0.12); background: #ffffff; font-family: inherit; font-size: 16px; font-weight: 600; outline: none; margin-bottom: 20px; transition: .3s; color: #334155; cursor: pointer; }
      .select-input:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
      
      .alert { padding: 18px 28px; border-radius: 24px; font-size: 16px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

      @media (max-width: 1024px) {
        .sidebar { 
          transform: translateX(-100%); 
          z-index: 1000; 
          transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
          box-shadow: 20px 0 40px rgba(0,0,0,0.1);
        }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { padding: 16px; }
        .topbar { padding: 0 16px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; padding: 15px 16px; }
        .topbar > div { width: 100%; justify-content: space-between !important; }
        .topbar-info { display: none; } /* Hide clock on mobile to save space */
        .order-card { padding: 20px; border-radius: 25px; }
        .order-grid { grid-template-columns: 1fr; gap: 24px; }
        .order-header { flex-direction: column; gap: 15px; align-items: flex-start; }
        .item-list-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .status-tabs { padding-bottom: 5px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; font-size: 13px; }
        
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

    <!-- LEFT -->
    <div style="display: flex; align-items: center; gap: 15px;">
        <div class="hamburger" id="hamburgerBtn">
            <i class="fas fa-bars"></i>
        </div>
        <div class="topbar-title">
            <h2>Manajemen Pesanan</h2>
        </div>
    </div>

    <!-- RIGHT -->
    <div style="display: flex; align-items: center; gap: 15px;">

        <!-- INFO -->
        <div class="topbar-info" style="text-align: right;">
            <div id="real-time-clock" style="font-weight: 800; font-size: 14px;">
                <?= formatTanggalIndo(date('Y-m-d H:i:s')) ?>
            </div>
            <div style="font-size: 11px; color: #2563eb; font-weight: 800;">
                ADMIN PENJUALAN
            </div>
        </div>

        <!-- BUTTON -->
        <a href="export_excel.php" 
           class="btn btn-outline"
           style="padding:10px 16px; font-size:12px; width:auto; border-radius:10px; height: 40px;">
            <i class="fas fa-file-excel"></i> Export
        </a>

    </div>
</header>

        <div class="content">
            <?php if ($success): ?><div class="alert"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert" style="background: #fff1f2; color: #e11d48; border-color: #fee2e2;"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

            <div class="status-tabs">
                <a href="?status=all" class="tab <?= $filterStatus === 'all' ? 'active' : '' ?>">SEMUA DATA</a>
                <a href="?status=menunggu" class="tab <?= $filterStatus === 'menunggu' ? 'active' : '' ?>">MENUNGGU</a>
                <a href="?status=diproses" class="tab <?= $filterStatus === 'diproses' ? 'active' : '' ?>">DIPROSES</a>
                <a href="?status=dikirim" class="tab <?= $filterStatus === 'dikirim' ? 'active' : '' ?>">DIKIRIM</a>
                <a href="?status=selesai" class="tab <?= $filterStatus === 'selesai' ? 'active' : '' ?>">SELESAI</a>
            </div>

            <div class="order-list" style="display: flex; flex-direction: column; gap: 32px;">
                <?php foreach ($pesananList as $p): ?>
                    <?php
                    $stmtD = $pdo->prepare("SELECT pd.*, pr.nama as produk_nama, pr.satuan FROM transaksi_pesanan pd JOIN produk pr ON pd.produk_id = pr.id WHERE pd.pesanan_id = ?");
                    $stmtD->execute([$p['id']]);
                    $details = $stmtD->fetchAll();
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <h3 class="order-id">
                                    ORDER #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                                    <span class="user-pill <?= $p['role_customer'] ?>"><?= strtoupper($p['role_customer']) ?></span>
                                </h3>
                                <div style="font-size: 14px; color: #94a3b8; font-weight: 700; margin-top: 10px;">
                                    <i class="fas fa-calendar-alt" style="margin-right: 8px;"></i> <?= formatTanggalIndo($p['created_at']) ?> WIB
                                </div>
                            </div>
                            <?= getStatusBadge($p['status']) ?>
                        </div>
                        
                        <div class="order-grid">
                            <div class="main-info">
                                <div class="info-sec">
                                    <span class="label">Informasi Pemesan</span>
                                    <div class="val" style="font-weight: 800; font-size: 18px; color: #0f172a;"><?= htmlspecialchars($p['konsumen_nama']) ?></div>
                                    <div class="val" style="color: #2563eb; font-weight: 700; margin-top: 4px;"><i class="fas fa-phone-alt" style="margin-right: 8px; font-size: 13px;"></i> <?= $p['telepon'] ?></div>
                                </div>
                                <div class="info-sec">
                                    <span class="label">Alamat Destinasi</span>
                                    <div class="val" style="font-size: 15px; border-left: 4px solid #f1f5f9; padding-left: 18px; margin-top: 8px; font-weight: 600;"><?= htmlspecialchars($p['alamat_pengiriman'] ?? $p['alamat']) ?></div>
                                </div>
                                <?php if (!empty($p['jam_pengantaran'])): ?>
                                <div class="info-sec" style="margin-top: 0;">
                                    <div style="display: inline-flex; align-items: center; gap: 8px; background: #eff6ff; border: 1px solid #bfdbfe; padding: 10px 18px; border-radius: 14px;">
                                        <i class="fas fa-clock" style="color:#2563eb;"></i>
                                        <span style="font-weight: 800; font-size: 13px; color: #1e40af;">Slot Pengantaran: <?= htmlspecialchars($p['jam_pengantaran']) ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-sec" style="margin-top: 40px;">
                                    <span class="label">Detail Produk Pesanan</span>
                                    <div class="item-list-wrapper">
                                        <table class="item-list">
                                            <thead><tr><th>NAMA PRODUK</th><th>QTY</th><th style="text-align:right">SUBTOTAL</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($details as $d): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($d['produk_nama']) ?></td>
                                                    <td style="color: #2563eb;"><?= $d['jumlah'] ?> <?= strtoupper($d['satuan']) ?></td>
                                                    <td style="text-align:right; color: #0f172a; font-weight: 800;"><?= formatRupiah($d['subtotal']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <?php if ($p['catatan']): ?>
                                <div class="info-sec" style="background: #f8fafc; padding: 24px; border-radius: 24px; margin-top: 24px; border: 1px solid #f1f5f9;">
                                    <span class="label">Catatan Pesanan</span>
                                    <div class="val" style="font-size: 15px; color: #64748b; font-style: italic; font-weight: 600;">"<?= htmlspecialchars($p['catatan']) ?>"</div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="side-panel">
                                <div class="payment-box">
                                    <div class="summary-item">
                                        <span>Metode Pembayaran</span>
                                        <span style="color: #0f172a;"><?= strtoupper($p['metode_bayar']) ?></span>
                                    </div>
                                    <div class="summary-item" style="padding-top: 16px; border-top: 2px solid #fff; margin-top: 16px;">
                                        <span style="font-size: 12px; letter-spacing: 1px;">TOTAL TAGIHAN</span>
                                    </div>
                                    <span class="final-price"><?= formatRupiah($p['total']) ?></span>

                                    <?php if ($p['metode_bayar'] === 'transfer' && $p['bukti_transfer']): ?>
                                        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #cbd5e1;">
                                            <a href="<?= getUploadUrl($p['bukti_transfer']) ?>" target="_blank" class="btn btn-outline" style="background: #f0fdf4; color: #166534; border-color: #bbf7d0; font-size: 13px; padding: 12px;">
                                                <i class="fas fa-image"></i> LIHAT BUKTI TRANSFER
                                            </a>
                                        </div>
                                    <?php elseif ($p['metode_bayar'] === 'transfer' && !$p['bukti_transfer']): ?>
                                        <div style="margin-top: 20px; padding: 12px; background: #fef2f2; border-radius: 12px; border: 1px solid #fee2e2; color: #991b1b; font-size: 12px; font-weight: 700; text-align: center;">
                                            <i class="fas fa-clock"></i> BELUM UPLOAD BUKTI
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($p['rating']): ?>
                                <div style="margin-top: 24px; padding: 20px; background: #fffbeb; border-radius: 24px; border: 1px solid #fef3c7; text-align: center;">
                                    <div style="font-size: 11px; font-weight: 800; color: #92400e; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Kepuasan Pelanggan</div>
                                    <div style="display: flex; gap: 6px; justify-content: center;">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="fas fa-star" style="color: <?= $i <= $p['rating'] ? '#f59e0b' : '#e2e8f0' ?>; font-size: 20px;"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div style="margin-top: 40px;">
                                    <?php if ($p['status'] === 'menunggu'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="pesanan_id" value="<?= $p['id'] ?>">
                                            <button type="submit" name="action" value="konfirmasi" class="btn btn-primary">KONFIRMASI PESANAN <i class="fas fa-check-double"></i></button>
                                        </form>
                                    <?php elseif ($p['status'] === 'diproses'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="pesanan_id" value="<?= $p['id'] ?>">
                                            <span class="label">Penugasan Petugas Kurir</span>
                                            <select name="sopir_id" class="select-input" required>
                                                <option value="">-- Pilih Nama Sopir/Kurir --</option>
                                                <?php foreach ($sopirList as $s): ?>
                                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?><?php if (!empty($s['rute_asal']) && !empty($s['rute_tujuan'])): ?> — Rute: <?= htmlspecialchars($s['rute_asal']) ?> → <?= htmlspecialchars($s['rute_tujuan']) ?><?php endif; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="action" value="siap_kirim" class="btn btn-primary">ATUR JADWAL KIRIM <i class="fas fa-truck-fast"></i></button>
                                        </form>
                                    <?php elseif ($p['sopir_nama']): ?>
                                        <div style="background: #eff6ff; border: 2px solid #3b82f6; border-radius: 28px; padding: 20px; text-align: center;">
                                            <div style="font-size: 11px; font-weight: 800; color: #3b82f6; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">Petugas Pengiriman</div>
                                            <div style="font-weight: 800; font-size: 16px; color: #0f172a;"><i class="fas fa-user-check" style="margin-right: 10px; color: #3b82f6;"></i> <?= htmlspecialchars($p['sopir_nama']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <a href="pesanan_print.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-outline" style="margin-top: 16px;"><i class="fas fa-print" style="margin-right: 6px;"></i> CETAK INVOICE PESANAN</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($pesananList)): ?>
                <div class="order-card" style="text-align: center; padding: 120px 40px; border-style: dashed; background: transparent;">
                    <div style="width: 100px; height: 100px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px;">
                        <i class="fas fa-box-open" style="font-size: 40px; color: #cbd5e1;"></i>
                    </div>
                    <h4 style="font-family: 'Outfit', sans-serif; font-size: 24px; color: #64748b; font-weight: 800;">Tidak Ada Data Pesanan</h4>
                    <p style="font-size: 16px; color: #94a3b8; margin-top: 12px; font-weight: 600;">Data pesanan dengan status tersebut tidak ditemukan.</p>
                </div>
            <?php endif; ?>
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
