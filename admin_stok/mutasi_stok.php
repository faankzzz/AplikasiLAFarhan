<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_barang');
$user = getCurrentUser($pdo);

$success = flash('success');
$error = flash('error');

// Filter Tanggal - Opsional (Default kosong untuk melihat semua riwayat)
$filterTanggal = $_GET['tanggal'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'masuk';
    $produk_id = (int)$_POST['produk_id'];
    $jumlah = (int)$_POST['jumlah'];
    $kategori = $_POST['kategori'] ?? 'lainnya';
    $keterangan = trim($_POST['keterangan']);
    $tanggal = $_POST['tanggal'];
    
    if ($produk_id <= 0 || $jumlah <= 0 || empty($keterangan) || empty($tanggal)) {
        $error = 'Semua field wajib diisi!';
    } else {
        $pdo->beginTransaction();
        try {
            // Ambil stok saat ini sebagai stok_awal
            $stmt = $pdo->prepare("SELECT stok FROM produk WHERE id = ?");
            $stmt->execute([$produk_id]);
            $stok_awal = (int)$stmt->fetchColumn();

            if ($type === 'masuk') {
                $stok_akhir = $stok_awal + $jumlah;
                $pdo->prepare("UPDATE produk SET stok = ? WHERE id = ?")->execute([$stok_akhir, $produk_id]);
            } else {
                if ($stok_awal < $jumlah) {
                    throw new Exception("Stok tidak cukup untuk dikurangi (Sisa: $stok_awal)");
                }
                $stok_akhir = $stok_awal - $jumlah;
                $pdo->prepare("UPDATE produk SET stok = ? WHERE id = ?")->execute([$stok_akhir, $produk_id]);
            }

            // Simpan ke mutasi_stok
            $pdo->prepare("INSERT INTO mutasi_stok (produk_id, tipe, jumlah, kategori, stok_awal, stok_akhir, keterangan, tanggal) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$produk_id, $type, $jumlah, $kategori, $stok_awal, $stok_akhir, $keterangan, $tanggal]);
            
            $pdo->commit();
            flash('success', "Mutasi stok berhasil disimpan!");
            header("Location: mutasi_stok.php?tanggal=$tanggal");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

$produkList = $pdo->query("SELECT * FROM produk ORDER BY nama")->fetchAll();

$sqlRiwayat = "
    SELECT m.*, p.nama as produk_nama, p.satuan, m.created_at as mutasi_waktu
    FROM mutasi_stok m 
    JOIN produk p ON m.produk_id = p.id 
";
if ($filterTanggal) {
    $sqlRiwayat .= " WHERE m.tanggal = ? ";
}
$sqlRiwayat .= " ORDER BY m.created_at DESC LIMIT 50 ";

$stmtRiwayat = $pdo->prepare($sqlRiwayat);
if ($filterTanggal) {
    $stmtRiwayat->execute([$filterTanggal]);
} else {
    $stmtRiwayat->execute();
}
$riwayat = $stmtRiwayat->fetchAll();

// Ringkasan (Gunakan tanggal filter atau hari ini)
$summaryTanggal = $filterTanggal ?: date('Y-m-d');
$stmtSummary = $pdo->prepare("
    SELECT p.nama, p.satuan,
           SUM(CASE WHEN m.tipe = 'masuk' THEN m.jumlah ELSE 0 END) as total_masuk,
           SUM(CASE WHEN m.tipe = 'keluar' THEN m.jumlah ELSE 0 END) as total_keluar
    FROM produk p
    LEFT JOIN mutasi_stok m ON p.id = m.produk_id AND m.tanggal = ?
    GROUP BY p.id
    HAVING total_masuk > 0 OR total_keluar > 0
");
$stmtSummary->execute([$summaryTanggal]);
$summary = $stmtSummary->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mutasi Stok Alfa One</title>
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
      body { font-family: 'Inter', sans-serif; background:#f0f6fa; color:#0f172a; min-height:100vh; line-height: 1.6; }
      
      .layout { display:flex; min-height:100vh; }
            .sidebar { width:280px; background:linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; flex-shrink:0; position: fixed; height: 100vh; z-index: 100; }
      .sidebar-logo { font-family: 'Outfit', sans-serif; font-size:24px; font-weight:800; color:white; padding:32px; border-bottom:1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 12px; letter-spacing: -0.5px; }
      .sidebar-logo i { color: #38bdf8; }

      .sidebar-profile { padding: 24px 32px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; }
      .sidebar-avatar { width: 64px; height: 64px; border-radius: 20px; background: rgba(255,255,255,0.1); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2); overflow: hidden; border: 1px solid rgba(255,255,255,0.1); }
      .sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
      .sidebar-user-info { display: flex; flex-direction: column; gap: 2px; }
      .sidebar-user-name { font-weight: 800; font-size: 16px; color: white; }
      .sidebar-user-role { font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; }

      .sidebar-nav { padding: 24px 16px; flex: 1; overflow-y: auto; }
      .nav-label { font-size: 11px; color: #38bdf8; text-transform: uppercase; letter-spacing: .15em; margin: 20px 16px 8px; font-weight: 800; }
      .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 20px; cursor: pointer; color: #94a3b8; font-size: 15px; transition: .3s ease; margin-bottom: 6px; text-decoration: none; font-weight: 700; }
      .nav-item:hover { background: rgba(255, 255, 255, 0.05); color: white; }
      .nav-item.active { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }

      @media (max-width: 1024px) {
        .sidebar { 
          transform: translateX(-100%); 
          z-index: 1000; 
          transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
          box-shadow: 20px 0 40px rgba(0,0,0,0.1);
        }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { padding: 20px; }
        .topbar { padding: 0 20px; }
        
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
          background: #ffffff;
          border: 1px solid rgba(59, 130, 246, 0.12);
          border-radius: 12px;
          cursor: pointer;
          color: #0f172a;
          font-size: 18px;
        }
      }

      .hamburger { display: none; }

      .main { flex:1; margin-left: 280px; display: flex; flex-direction: column; }
     .topbar {height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); border-bottom: 2px solid rgba(56, 189, 248, 0.4); position: sticky; top: 0; z-index: 10;
}

.topbar-left h2 {font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: #1e40af;
}.topbar-right {
  display: flex; align-items: center; gap: 20px; /* 🔥 INI YANG BIKIN GA MEPEET */
}.topbar-info {
  text-align: right;
}.topbar-date { font-weight: 800; font-size: 14px;
}
.topbar-role {font-size: 11px;color: #2563eb; font-weight: 800; text-transform: uppercase;
}
.btn-export {
  display: inline-flex;align-items: center;gap: 8px;padding: 10px 18px;border-radius: 14px;background: #ecfdf5;color: #047857;border: 1px solid #a7f3d0;font-weight: 700; font-size: 14px;text-decoration: none;transition: .3s;
}
.btn-export:hover {background: #d1fae5; transform: translateY(-2px);
}
      
      .content { padding: 40px; display: grid; grid-template-columns: 1fr 400px; gap: 40px; }
      .card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); border-top: 3px solid #38bdf8; box-shadow: 0 10px 30px rgba(37, 99, 235, 0.04); margin-bottom: 24px; }
      .card-title { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #0f172a; }
      
      .form-group { margin-bottom: 20px; }
      .label { font-size: 11px; font-weight: 800; color: #ffffff; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block; }
      .input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 1px solid rgba(59, 130, 246, 0.12); background: #ffffff; font-family: inherit; font-size: 16px; font-weight: 600; outline: none; transition: .3s; }
      .input:focus { border-color: #ffffff !important; background: #ffffff !important; box-shadow: none !important; }

      .btn-primary { background: #2563eb; color: white; border: none; padding: 16px 24px; border-radius: 18px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer; transition: .3s; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.3); }
      
      .type-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; padding: 6px; background: #f1f5f9; border-radius: 18px; }
      .type-option { cursor: pointer; text-align: center; padding: 12px; border-radius: 14px; font-weight: 800; font-size: 13px; transition: .3s; display: flex; align-items: center; justify-content: center; gap: 8px; color: #64748b; }
      .type-option input { display: none; }
      .type-option.active.masuk { background: rgba(22, 163, 74, 0.1); color: #16a34a; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
      .type-option.active.keluar { background: rgba(220, 38, 38, 0.1); color: #dc2626; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }

      .table-box { background: #ffffff; border-radius: 40px; border: 1px solid rgba(59, 130, 246, 0.12); overflow: hidden; grid-column: span 1; box-shadow: 0 10px 30px rgba(37, 99, 235, 0.04); }
      .table { width: 100%; border-collapse: collapse; }
      .table th { text-align: left; font-size: 11px; font-weight: 800; color: #94a3b8; padding: 20px 24px; text-transform: uppercase; border-bottom: 1px solid rgba(59, 130, 246, 0.12); }
      .table td { padding: 20px 24px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); font-size: 15px; font-weight: 600; }
      
      .stok-badge { padding: 6px 12px; border-radius: 10px; font-weight: 800; font-size: 12px; }
      .badge-masuk { background: #f0fdf4; color: #15803d; }
      .badge-keluar { background: #fff1f2; color: #e11d48; }

      .summary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px; }
      .summary-card { padding: 20px; background: #ffffff; border-radius: 24px; border: 1px solid rgba(59, 130, 246, 0.12); display: flex; flex-direction: column; gap: 8px; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.02); }
      .summary-title { font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; }
      .summary-val { font-size: 18px; font-weight: 800; color: #0f172a; }

      .alert { padding: 18px; border-radius: 20px; font-size: 14px; font-weight: 700; margin-bottom: 32px; display: flex; align-items: center; gap: 12px; grid-column: span 2; }
      .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
      .alert-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fee2e2; }

      @media (max-width: 1024px) {
        .main { margin-left: 0; }
        .sidebar { transform: translateX(-100%); z-index: 1000; transition: transform .35s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 20px 0 40px rgba(15, 23, 42, 0.15); }
        .sidebar.active { transform: translateX(0); }
        .content { padding: 20px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div { width: 100%; justify-content: space-between !important; }
        .topbar-info { display: none; }
        .alert { grid-column: span 1; }
        .filter-row { flex-direction: column; align-items: stretch !important; gap: 15px !important; }
        .filter-form { width: 100%; }
        .filter-form input { flex: 1; }
        .left-col, .right-col { grid-column: span 1; width: 100%; }
        .content { display: flex; flex-direction: column; }
        
        .table thead { display: none; }
        .table tbody tr { display: block; background: #f8fafc; border-radius: 18px; margin: 16px; padding: 16px; border: 1px solid rgba(59, 130, 246, 0.12); }
        .table td { display: block; width: 100%; padding: 10px 0 !important; border: none !important; text-align: left !important; }
        .table td:not(:last-child) { border-bottom: 1px dashed rgba(59, 130, 246, 0.1) !important; margin-bottom: 8px; }
        .table td::before { content: attr(data-label); display: block; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }
        
        .hamburger { display: flex !important; width: 42px; height: 42px; align-items: center; justify-content: center; background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); border-radius: 12px; cursor: pointer; color: #0f172a; font-size: 18px; box-shadow: 0 10px 30px rgba(37, 99, 235, 0.04); }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 999; }
        .sidebar-overlay.active { display: block; }
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
                <div class="topbar-left">
                    <h2>Manajemen Stok</h2>
                </div>
            </div>
    <div class="topbar-right">
        <div class="topbar-info">
            <div class="topbar-date"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
            <div class="topbar-role">Admin Barang</div>
        </div>
        <a href="export_mutasi_stok.php" class="btn-export">
            <i class="fas fa-file-excel"></i> <span>Export Mutasi</span>
        </a>
    </div>
</header>
        <div class="content">
            <?php if ($success): ?><div class="alert alert-success animate-up"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger animate-up"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

            <div class="left-col">
                <div class="filter-row" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 20px;">
                        <?= $filterTanggal ? 'Laporan Harian: ' . formatTanggalIndo($filterTanggal, false) : 'Riwayat Mutasi' ?>
                    </h3>
                    <form action="" method="GET" class="filter-form" style="display: flex; gap: 8px; align-items: center;">
                        <input type="date" name="tanggal" class="input" style="padding: 8px 12px; font-size: 14px; width: 160px;" value="<?= $filterTanggal ?>">
                        <button type="submit" class="btn-primary" style="padding: 8px 16px; border-radius: 12px; width: auto;"><i class="fas fa-search"></i></button>
                        <?php if ($filterTanggal): ?>
                            <a href="mutasi_stok.php" class="btn" style="background: #f1f5f9; color: #64748b; padding: 8px 16px; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 700;"><i class="fas fa-sync"></i></a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="summary-grid">
                    <?php if (empty($summary)): ?>
                        <div class="summary-card" style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 40px;">
                            <i class="fas fa-folder-open" style="font-size: 24px; margin-bottom: 8px;"></i>
                            <p>Tidak ada mutasi stok pada tanggal ini.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($summary as $s): ?>
                            <div class="summary-card">
                                <span class="summary-title"><?= htmlspecialchars($s['nama']) ?></span>
                                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                    <div style="color: #16a34a; font-weight: 700;">+<?= $s['total_masuk'] ?></div>
                                    <div style="color: #dc2626; font-weight: 700;">-<?= $s['total_keluar'] ?></div>
                                </div>
                                <div style="font-size: 10px; color: #94a3b8; text-align: right;"><?= strtoupper($s['satuan']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="table-box">
                    <div style="padding: 24px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); font-weight: 800; font-family: 'Outfit', sans-serif;">Jurnal Mutasi Harian</div>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>WAKTU</th>
                                    <th>PRODUK</th>
                                    <th style="text-align: center;">STOK AWAL</th>
                                    <th style="text-align: center;">MUTASI</th>
                                    <th style="text-align: center;">STOK AKHIR</th>
                                    <th>KETERANGAN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayat as $r): ?>
                                <tr>
                                    <td data-label="Waktu" style="font-size: 13px;">
                                        <div style="font-weight: 800; color: #0f172a;"><?= date('d/m/y', strtotime($r['tanggal'])) ?></div>
                                        <div style="font-size: 10px; color: #94a3b8; font-weight: 700;"><?= date('H:i', strtotime($r['mutasi_waktu'])) ?></div>
                                    </td>
                                    <td data-label="Produk" style="font-weight: 700;"><?= htmlspecialchars($r['produk_nama']) ?></td>
                                    <td data-label="Stok Awal" style="color: #94a3b8; text-align: center;"><?= $r['stok_awal'] ?></td>
                                    <td data-label="Mutasi" style="text-align: center;">
                                        <span class="stok-badge <?= $r['tipe'] === 'masuk' ? 'badge-masuk' : 'badge-keluar' ?>">
                                            <?= $r['tipe'] === 'masuk' ? '+' : '-' ?><?= $r['jumlah'] ?>
                                        </span>
                                    </td>
                                    <td data-label="Stok Akhir" style="font-weight: 800; text-align: center;"><?= $r['stok_akhir'] ?></td>
                                    <td data-label="Keterangan">
                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($r['keterangan']) ?></div>
                                        <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 800;"><?= str_replace('_', ' ', $r['kategori']) ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="right-col">
                <div class="card">
                    <h3 class="card-title"><i class="fas fa-edit"></i> Update Stok</h3>
                    <form method="POST">
                        <div class="type-selector">
                            <label class="type-option masuk active" id="labelMasuk">
                                <input type="radio" name="type" value="masuk" checked onchange="toggleType('masuk')">
                                <i class="fas fa-plus-circle"></i> Masuk
                            </label>
                            <label class="type-option keluar" id="labelKeluar">
                                <input type="radio" name="type" value="keluar" onchange="toggleType('keluar')">
                                <i class="fas fa-minus-circle"></i> Keluar
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="label">Pilih Produk</label>
                            <select name="produk_id" class="input" required>
                                <option value="">— Pilih Produk —</option>
                                <?php foreach ($produkList as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> (Stok: <?= $p['stok'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label">Kategori</label>
                            <select name="kategori" class="input">
                                <option value="pabrik">Penerimaan Pabrik</option>
                                <option value="rusak_bocor">Rusak / Bocor</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label">Jumlah</label>
                            <input type="number" name="jumlah" class="input" required min="1" placeholder="0">
                        </div>

                        <div class="form-group">
                            <label class="label">Keterangan / Alasan</label>
                            <input type="text" name="keterangan" class="input" required placeholder="Contoh: Stok Baru dari Pabrik">
                        </div>

                        <div class="form-group">
                            <label class="label">Tanggal</label>
                            <input type="date" name="tanggal" class="input" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <button type="submit" class="btn-primary" id="submitBtn">SIMPAN PERUBAHAN</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function toggleType(type) {
        const labelMasuk = document.getElementById('labelMasuk');
        const labelKeluar = document.getElementById('labelKeluar');
        const submitBtn = document.getElementById('submitBtn');

        if (type === 'masuk') {
            labelMasuk.classList.add('active');
            labelKeluar.classList.remove('active');
            submitBtn.style.background = 'linear-gradient(135deg, #2563eb 0%, #3b82f6 100%)';
        } else {
            labelMasuk.classList.remove('active');
            labelKeluar.classList.add('active');
            submitBtn.style.background = 'linear-gradient(135deg, #ef4444 0%, #b91c1c 100%)';
        }
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
</script>
</body>
</html>
