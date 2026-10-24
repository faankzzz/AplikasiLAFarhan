<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('sopir');
$user = getCurrentUser($pdo);

$pesananId = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

// Ambil data pesanan
$stmt = $pdo->prepare("SELECT p.*, u.nama as konsumen_nama, u.telepon, u.role_customer, 
    COALESCE(p.alamat_pengiriman, u.alamat) as alamat, 
    COALESCE(p.latitude_pengiriman, u.latitude) as latitude, 
    COALESCE(p.longitude_pengiriman, u.longitude) as longitude
    FROM pesanan p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.sopir_id = ?");
$stmt->execute([$pesananId, getUserId()]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    redirect('sopir/dashboard.php');
}

// Detail produk
$stmtD = $pdo->prepare("SELECT pd.*, pr.nama as produk_nama, pr.satuan FROM transaksi_pesanan pd JOIN produk pr ON pd.produk_id = pr.id WHERE pd.pesanan_id = ?");
$stmtD->execute([$pesananId]);
$details = $stmtD->fetchAll();

// Aksi: Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'dikirim') {
        $pdo->prepare("UPDATE pesanan SET status = 'dikirim' WHERE id = ? AND sopir_id = ?")->execute([$pesananId, getUserId()]);
        $success = 'Status diperbarui: Sedang Dikirim';
        $pesanan['status'] = 'dikirim';
    } elseif ($action === 'selesai') {
        // Upload bukti
        $buktiPengiriman = null;
        if (!empty($_FILES['bukti_pengiriman']['name'])) {
            $buktiPengiriman = uploadFile($_FILES['bukti_pengiriman'], 'bukti_pengiriman');
        }
        $catatanPengiriman = trim($_POST['catatan_pengiriman'] ?? '');
        
        $pdo->beginTransaction();
        try {
            // Update pesanan
            $pdo->prepare("UPDATE pesanan SET status = 'selesai', bukti_pengiriman = ?, catatan_pengiriman = ? WHERE id = ? AND sopir_id = ?")
                ->execute([$buktiPengiriman, $catatanPengiriman, $pesananId, getUserId()]);
            
            // Kurangi stok otomatis & catat ke mutasi_stok
            foreach ($details as $d) {
                $stmtStok = $pdo->prepare("SELECT stok FROM produk WHERE id = ?");
                $stmtStok->execute([$d['produk_id']]);
                $currentStok = $stmtStok->fetchColumn();
                
                $newStok = max(0, $currentStok - $d['jumlah']);
                $pdo->prepare("UPDATE produk SET stok = ? WHERE id = ?")->execute([$newStok, $d['produk_id']]);
                
                // Catat mutasi keluar (penjualan) agar muncul di jurnal mutasi stok
                $pdo->prepare("INSERT INTO mutasi_stok (produk_id, tipe, jumlah, kategori, stok_awal, stok_akhir, keterangan, tanggal) VALUES (?,?,?,?,?,?,?,NOW())")
                    ->execute([$d['produk_id'], 'keluar', $d['jumlah'], 'penjualan_app', $currentStok, $newStok, 'Penjualan - Pesanan #' . str_pad($pesananId, 6, '0', STR_PAD_LEFT)]);
            }
            
            $pdo->commit();
            $success = 'Pengiriman selesai! Stok otomatis berkurang.';
            $pesanan['status'] = 'selesai';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal: ' . $e->getMessage();
        }
    }
}

$jarak = 0;
if ((float)$pesanan['latitude'] != 0 && (float)$pesanan['longitude'] != 0) {
    $jarak = haversine(GUDANG_LAT, GUDANG_LNG, $pesanan['latitude'], $pesanan['longitude']);
}

// Anggap jarak > 1000km sebagai koordinat tidak valid (biasanya lari ke 0,0)
$jarak_valid = ($jarak > 0 && $jarak < 1000);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Penugasan</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap" async defer></script>
    <style>
      :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8; --bg: #f0f6fa; --surface: #ffffff; --text: #1e293b; --muted: #64748b; --border: rgba(59, 130, 246, 0.12);
        --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); --sidebar-text: #f8fafc; --sidebar-muted: #94a3b8;
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif;
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
      .topbar-title { display: flex; align-items: center; gap: 16px; }
      .topbar-title h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #1e40af; letter-spacing: -0.5px; }
      .btn-back { color: #94a3b8; font-size: 22px; transition: .2s; }
      .btn-back:hover { color: #2563eb; transform: translateX(-5px); }
      
      .content { padding: 40px; display: grid; grid-template-columns: 1fr 480px; gap: 40px; width: 100%; }
      
      .card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02); margin-bottom: 32px; height: fit-content; }
      .card-title { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; margin-bottom: 32px; display: flex; align-items: center; gap: 12px; color: #0f172a; letter-spacing: -0.5px; }
      .card-title i { color: #2563eb; font-size: 28px; }
      
      .info-list { display: flex; flex-direction: column; gap: 28px; }
      .info-item { display: grid; grid-template-columns: 140px 1fr; gap: 20px; align-items: flex-start; }
      .info-label { font-size: 15px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 4px; }
      .info-val { font-size: 20px; font-weight: 700; color: #1e293b; line-height: 1.6; }
      
      .order-table { width: 100%; border-collapse: collapse; margin-top: 24px; border-radius: 24px; overflow: hidden; border: 1px solid #f1f5f9; }
      .order-table th { background: #f8fafc; text-align: left; font-size: 15px; font-weight: 800; color: #94a3b8; padding: 18px 24px; text-transform: uppercase; }
      .order-table td { padding: 18px 24px; font-size: 18px; color: #1e293b; border-bottom: 1px solid rgba(59, 130, 246, 0.12); font-weight: 600; }
      
      .price-row { display: flex; justify-content: space-between; align-items: center; margin-top: 32px; padding-top: 24px; border-top: 2px dashed #f1f5f9; }
      .price-total { font-family: 'Outfit', sans-serif; font-size: 36px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
      
      .map-container { width: 100%; height: 420px; border-radius: 24px; overflow: hidden; border: 1px solid rgba(59, 130, 246, 0.12); margin-bottom: 24px; background: #f8fafc; }
      #delivery-map { width: 100%; height: 100%; }
      
      .action-form { display: flex; flex-direction: column; gap: 20px; }
      .field-group { display: flex; flex-direction: column; gap: 10px; }
      .input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 1px solid rgba(59, 130, 246, 0.12); background: #f8fafc; font-family: inherit; font-size: 16px; font-weight: 600; outline: none; transition: .3s; }
      .input:focus { border-color: #2563eb; background: #ffffff; }
      
      .btn { padding: 18px 28px; border-radius: 24px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 18px; cursor: pointer; transition: .3s; border: none; display: flex; align-items: center; gap: 12px; justify-content: center; width: 100%; }
      .btn-primary { background: #2563eb; color: white; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2); }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(59, 130, 246, 0.3); }
      
      .btn-outline { background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); color: #64748b; text-decoration: none; }
      .btn-outline:hover { border-color: #2563eb; color: #2563eb; }

      .alert { padding: 20px; border-radius: 24px; font-size: 15px; font-weight: 700; margin-bottom: 40px; grid-column: span 2; display: flex; align-items: center; gap: 12px; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

      .badge { padding: 6px 14px; border-radius: 50px; font-size: 10px; font-weight: 800; text-transform: uppercase; border: 1px solid transparent; }
      .badge-cash { background: #fef3c7; color: #92400e; border-color: #fde68a; }
      .badge-transfer { background: #eff6ff; color: #1e40af; border-color: #dbeafe; }



      .custom-div-icon { background: none; border: none; }

      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); z-index: 1000; transition: transform .3s; box-shadow: 20px 0 40px rgba(0,0,0,0.1); }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { padding: 15px; grid-template-columns: 1fr; gap: 20px; }
        .topbar { padding: 15px 20px; height: auto; min-height: 80px; flex-direction: column; align-items: flex-start; justify-content: center; gap: 10px; }
        .topbar > div:first-child { width: 100%; justify-content: space-between !important; }
        .topbar-info { display: none; } /* Hide clock on mobile */
        .card { padding: 24px; border-radius: 30px; }
        .right-col { order: -1; }
        .info-item { grid-template-columns: 1fr; gap: 4px; }
        .info-val { font-size: 16px; }
        .price-total { font-size: 28px; }
        .order-table td { padding: 12px 15px; font-size: 15px; }
        .order-table th { padding: 12px 15px; font-size: 13px; }
        .map-container { height: 280px; }
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
                <?php if (!empty($user['foto'])): ?>
                    <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                <?php else: ?>
                    <i class="fas fa-user-tie"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="sidebar-user-role">Driver Operational</div>
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
            <div class="topbar-title">
                <div class="hamburger" id="hamburgerBtn" style="margin-right: 10px;"><i class="fas fa-bars"></i></div>
                <a href="dashboard.php" class="btn-back"><i class="fas fa-chevron-left"></i></a>
                <h2>Detail Penugasan #<?= str_pad($pesananId, 5, '0', STR_PAD_LEFT) ?></h2>
            </div>
            <div class="topbar-info" style="display: flex; align-items: center; gap: 20px;">
                <div id="gpsStatus"></div>
                <div style="text-align: right;">
                    <div style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                    <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Driver Operational</div>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?><div class="alert"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>

            <div class="left-col">
                <div class="card">
                    <div class="card-title"><i class="fas fa-user-check"></i> Identitas Konsumen </div>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Pelanggan</span>
                            <div class="info-val"><?= htmlspecialchars($pesanan['konsumen_nama']) ?> <span class="badge" style="background:#f1f5f9; color:#64748b; margin-left:10px;"><?= strtoupper($pesanan['role_customer']) ?></span></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Telepon</span>
                            <div class="info-val" style="color: #2563eb;"><?= htmlspecialchars($pesanan['telepon']) ?></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Detail Alamat Konsumen</span>
                            <div class="info-val"><?= htmlspecialchars($pesanan['alamat']) ?></div>
                        </div>
                        <?php if ($jarak_valid): ?>
                        <div class="info-item">
                            <span class="info-label">Estimasi Jarak</span>
                            <div class="info-val"><?= $jarak ?> Kilometer dari Titik Muat</div>
                        </div>
                        <?php elseif ($pesanan['latitude'] != 0): ?>
                        <div class="info-item">
                            <span class="info-label">Estimasi Jarak</span>
                            <div class="info-val" style="color: #ef4444; font-size: 14px;">
                                <i class="fas fa-triangle-exclamation"></i> Lokasi Tidak Valid (Harus di-update oleh Konsumen)
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title"><i class="fas fa-boxes-stacked"></i> Detail Produk yang dipesan </div>
                    <table class="order-table">
                        <thead><tr><th>NAMA PRODUK</th><th>JUMLAH</th><th style="text-align: right;">TOTAL BAYAR</th></tr></thead>
                        <tbody>
                            <?php foreach ($details as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['produk_nama']) ?></td>
                                <td><?= $d['jumlah'] ?> <?= strtoupper($d['satuan']) ?></td>
                                <td style="text-align: right; font-weight: 700;"><?= formatRupiah($d['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="price-row">
                        <div style="font-size: 11px; font-weight: 800; color: #94a3b8; letter-spacing: 1px;">TOTAL PENAGIHAN:</div>
                        <div class="price-total"><?= formatRupiah($pesanan['total']) ?></div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
                        <span class="badge badge-<?= $pesanan['metode_bayar'] ?>">PEMBAYARAN <?= strtoupper($pesanan['metode_bayar']) ?></span>
                    </div>
                </div>
            </div>

            <div class="right-col">
                <div class="card">
                    <div class="card-title"><i class="fas fa-map-location-dot"></i> Panduan Rute</div>
                    <div class="map-container">
                        <?php if ($jarak_valid): ?>
                            <div id="delivery-map"></div>
                        <?php else: ?>
                            <div style="height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:16px; color:#cbd5e1; padding: 40px; text-align:center;">
                                <i class="fas fa-map-location-dot" style="font-size: 48px; opacity: 0.5;"></i>
                                <div style="font-size: 13px; font-weight: 800; line-height: 1.6; color: #94a3b8;">
                                    TITIK LOKASI BELUM DIATUR<br>
                                    <span style="font-weight: 400; font-size: 11px;">Konsumen perlu menandai lokasi di profil akun mereka.</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($jarak_valid): ?>
                       <div style="display: flex; justify-content: center;">
                            <a href="https://www.google.com/maps?q=<?= $pesanan['latitude'] ?>,<?= $pesanan['longitude'] ?>" 
                            target="_blank" 
                            class="btn btn-outline" 
                            style="width: auto;">
                            <i class="fab fa-google"></i> GOOGLE MAPS
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-title"><i class="fas fa-file-signature"></i> Finalisasi</div>
                    <?php if ($pesanan['status'] === 'dikirim'): ?>
                        <form method="POST" enctype="multipart/form-data" class="action-form">
                            <input type="hidden" name="action" value="selesai">
                            <div class="field-group"><label class="info-label">Bukti Distribusi</label><input type="file" name="bukti_pengiriman" class="input" required></div>
                            <div class="field-group"><label class="info-label">Keterangan</label><textarea name="catatan_pengiriman" class="input" style="height: 100px; resize: none;"></textarea></div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-circle-check"></i> SELESAI</button>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; padding: 24px; background: #f0fdf4; border-radius: 24px;">TUGAS SELESAI</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    let map = null, driverMarker = null;
    const destLat = <?= $pesanan['latitude'] ? floatval($pesanan['latitude']) : 0 ?>;
    const destLng = <?= $pesanan['longitude'] ? floatval($pesanan['longitude']) : 0 ?>;
    const jarakValid = <?= $jarak_valid ? 'true' : 'false' ?>;

    function initMap() {
        const mapEl = document.getElementById('delivery-map');
        if (!mapEl || !jarakValid) return;

        map = new google.maps.Map(mapEl, {
            center: { lat: destLat, lng: destLng },
            zoom: 15,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            scrollwheel: false
        });

        const destMarker = new google.maps.Marker({
            position: { lat: destLat, lng: destLng },
            map: map,
            title: "<?= htmlspecialchars($pesanan['konsumen_nama']) ?>"
        });

        const destInfoWindow = new google.maps.InfoWindow({
            content: "<b><?= htmlspecialchars($pesanan['konsumen_nama']) ?></b>"
        });
        destMarker.addListener('click', () => destInfoWindow.open(map, destMarker));
        destInfoWindow.open(map, destMarker);
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

    // Live Tracking Sopir
    const gpsStatus = document.getElementById('gpsStatus');

    function updateGpsStatus(status, msg) {
        if (!gpsStatus) return;
        const icons = { ok: '🟢', error: '🔴', searching: '🟡' };
        gpsStatus.innerHTML = `<span style="font-size:11px; font-weight:700; color:#64748b;">${icons[status] || '⚪'} ${msg}</span>`;
    }

    if (navigator.geolocation) {
        updateGpsStatus('searching', 'Mendeteksi lokasi...');
        
        const sendLocation = (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            updateGpsStatus('ok', 'Lokasi aktif');
            
            fetch('../api/update_lokasi_sopir.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ lat: lat, lng: lng })
            }).catch(err => console.error("API Error:", err));

            if (map !== null) {
                const driverPos = { lat: lat, lng: lng };
                if (!driverMarker) {
                    driverMarker = new google.maps.Marker({
                        position: driverPos,
                        map: map,
                        title: 'Lokasi Anda Sekarang',
                        icon: {
                            url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                        }
                    });
                    const driverInfoWindow = new google.maps.InfoWindow({
                        content: 'Lokasi Anda Sekarang'
                    });
                    driverMarker.addListener('click', () => driverInfoWindow.open(map, driverMarker));
                } else {
                    driverMarker.setPosition(driverPos);
                }
            }
        };

        // Fix Instan (Cepat untuk Laptop)
        navigator.geolocation.getCurrentPosition(sendLocation, (err) => console.warn(err), {
            enableHighAccuracy: false, timeout: 5000
        });

        // Pantau pergerakan (Tanpa High Accuracy agar tidak stuck)
        navigator.geolocation.watchPosition(sendLocation, function(error) {
            const msgs = {
                1: 'Izin GPS ditolak browser',
                2: 'Sinyal lokasi tidak tersedia',
                3: 'Gagal mendeteksi lokasi'
            };
            updateGpsStatus('error', msgs[error.code] || 'GPS error');
        }, {
            enableHighAccuracy: false,
            maximumAge: 30000
        });
    } else {
        updateGpsStatus('error', 'Browser tidak mendukung GPS');
    }
</script>
</body>
</html>
