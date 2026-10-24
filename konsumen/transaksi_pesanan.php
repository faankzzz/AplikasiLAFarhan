<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('konsumen');
$user = getCurrentUser($pdo);

$pesananId = (int)($_GET['id'] ?? 0);

// Ambil detail pesanan dan pastikan itu milik user yang sedang login
$stmt = $pdo->prepare("SELECT p.*, u2.nama as sopir_nama, u2.telepon as sopir_telp 
    FROM pesanan p 
    LEFT JOIN users u2 ON p.sopir_id = u2.id 
    WHERE p.id = ? AND p.user_id = ?");
$stmt->execute([$pesananId, $user['id']]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    redirect('konsumen/pesanan.php');
}

// Ambil daftar produk
$stmtD = $pdo->prepare("SELECT pd.*, pr.nama as produk_nama, pr.satuan 
    FROM transaksi_pesanan pd 
    JOIN produk pr ON pd.produk_id = pr.id 
    WHERE pd.pesanan_id = ?");
$stmtD->execute([$pesananId]);
$details = $stmtD->fetchAll();

// Menentukan tahapan status untuk UI Timeline
$statusOrder = ['menunggu', 'diproses', 'dikirim', 'selesai'];
$currentStep = array_search($pesanan['status'], $statusOrder);
if ($currentStep === false) $currentStep = 0;

// Handle kirim rating
$successRating = '';
if (isset($_POST['kirim_rating'])) {
    $rating = min(5, max(1, (int)$_POST['rating']));
    $stmtR = $pdo->prepare("UPDATE pesanan SET rating = ? WHERE id = ? AND user_id = ?");
    if ($stmtR->execute([$rating, $pesananId, $user['id']])) {
        $successRating = 'Terima kasih atas penilaian Anda!';
        $pesanan['rating'] = $rating;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= str_pad($pesananId, 5, '0', STR_PAD_LEFT) ?> — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap" async defer></script>
    <style>
      :root {
        --primary: #2F56D3; --primary-dark: #1E43B8; --bg: #F4F8FF; --surface: #FFFFFF; --text: #0F172A; --muted: #64748B; --border: #D6E4FF;
        --sidebar-bg: #0f172a; --sidebar-text: #F8FAFC; --sidebar-muted: #94A3B8;
        --grad-blue: linear-gradient(135deg, #2F56D3 0%, #1E43B8 100%);
        --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif;
      }
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: 'Inter', sans-serif; background:#F4F8FF; color:#0F172A; min-height:100vh; overflow-x:hidden; -webkit-font-smoothing: antialiased; line-height: 1.6; font-size: 18px; }
      
      .layout { display:flex; min-height:100vh; }
      
      .sidebar { width:280px; background:#0f172a; border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; flex-shrink:0; position: fixed; left: 0; top: 0; height: 100vh; z-index: 1000; transition: .3s; }
      .sidebar-logo { font-family: 'Outfit', sans-serif; font-size:28px; font-weight:800; color:white; padding:0 32px 32px; border-bottom:1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; gap: 12px; letter-spacing: -0.5px; }
      .sidebar-logo i { color: #38bdf8; font-size: 24px; }      .sidebar-profile { padding: 24px 32px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; }
      .sidebar-avatar { width: 64px; height: 64px; border-radius: 20px; background: rgba(255,255,255,0.1); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2); overflow: hidden; border: 1px solid rgba(255,255,255,0.1); }
      .sidebar-avatar img { width: 100%; height: 100%; object-fit: cover; }
      .sidebar-user-info { display: flex; flex-direction: column; gap: 2px; }
      .sidebar-user-name { font-weight: 800; font-size: 18px; color: white; }
      .sidebar-user-role { font-size: 13px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; }
      .sidebar-nav { padding:24px 16px; flex:1; }
      .nav-label { font-size:11px; color:#94A3B8; text-transform:uppercase; letter-spacing:.1em; margin: 20px 16px 8px; font-weight: 800; }
      .nav-item { display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:18px; cursor:pointer; color:#94A3B8; font-size:15px; transition:.2s; margin-bottom:6px; text-decoration: none; font-weight: 600; }
      .nav-item:hover { background:rgba(255,255,255,0.05); color:white; }
      .nav-item.active { background:linear-gradient(135deg, #2F56D3 0%, #1E43B8 100%); color:white; box-shadow: 0 10px 15px -3px rgba(30, 64, 175, 0.3); }
      
      .main { flex:1; margin-left: 280px; display: flex; flex-direction: column; min-height: 100vh; }
      .topbar { height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.75); backdrop-filter: blur(14px); border-bottom: 1px solid #D6E4FF; position: sticky; top: 0; z-index: 1100; }
      .topbar-title { display: flex; align-items: center; gap: 16px; }
      .topbar-title h2 { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
      .btn-back { color: #94a3b8; font-size: 18px; transition: .2s; }
      .btn-back:hover { color: #2F56D3; transform: translateX(-5px); }
      
      .content { padding: 40px; width: 100%; display: flex; flex-direction: column; gap: 40px; }
      
      .card { background: #FFFFFF; border-radius: 48px; padding: 48px; border: 1px solid #D6E4FF; box-shadow: 0 10px 30px rgba(15,23,42,0.04); }
      .card-title { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 10px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px dashed #E2E8F0; }
      
      /* Timeline UI */
      .timeline { display: flex; align-items: center; justify-content: space-between; position: relative; margin: 20px 0 40px; }
      .timeline::before { content: ''; position: absolute; top: 24px; left: 40px; right: 40px; height: 4px; background: #f1f5f9; z-index: 0; border-radius: 4px; }
      .timeline-step { display: flex; flex-direction: column; align-items: center; gap: 12px; position: relative; z-index: 1; width: 80px; }
      .step-icon { width: 64px; height: 64px; border-radius: 20px; background: #F1F5F9; border: 4px solid #E2E8F0; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #cbd5e1; transition: .4s; }
      .step-label { font-size: 15px; font-weight: 800; text-transform: uppercase; color: #94a3b8; text-align: center; }
      
      .timeline-step.active .step-icon { background: #2F56D3; border-color: #bfdbfe; color: white; box-shadow: 0 10px 15px -3px rgba(47, 86, 211, 0.3); transform: scale(1.1); }
      .timeline-step.active .step-label { color: #2F56D3; }
      .timeline-step.completed .step-icon { background: #16a34a; border-color: #bbf7d0; color: white; }
      .timeline-step.completed .step-label { color: #16a34a; }

      /* Tables & Info Grid */
      .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
      .info-sec { display: flex; flex-direction: column; gap: 6px; }
      .info-label { font-size: 15px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
      .info-val { font-size: 20px; font-weight: 700; color: #1e293b; line-height: 1.5; }
      
      .driver-box { background: #eff6ff; border: 2px solid #bfdbfe; border-radius: 24px; padding: 24px; display: flex; align-items: center; gap: 20px; }
      .driver-avatar { width: 50px; height: 50px; border-radius: 14px; background: #2F56D3; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; }
      
      .order-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
      .order-table th { background: #F8FBFF; text-align: left; font-size: 16px; font-weight: 800; color: #94a3b8; padding: 16px 20px; text-transform: uppercase; border-bottom: 2px solid #E2E8F0; }
      .order-table td { padding: 16px 20px; font-size: 18px; color: #334155; border-bottom: 1px solid #EEF2FF; font-weight: 600; }
      
      .price-row { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 20px; border-top: 2px dashed #e2e8f0; }
      .price-total { font-family: 'Outfit', sans-serif; font-size: 36px; font-weight: 800; color: #2F56D3; letter-spacing: -0.5px; }
      
      .proof-box { margin-top: 32px; padding: 24px; border: 2px dashed #cbd5e1; border-radius: 24px; text-align: center; }
      .proof-img { max-width: 100%; border-radius: 12px; margin-top: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
      
      @media (max-width: 1024px) {
        .sidebar { 
          transform: translateX(-100%); 
          z-index: 1200; 
          transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
          box-shadow: 20px 0 40px rgba(0,0,0,0.1);
        }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { padding: 20px; }
        .topbar { padding: 0 20px; z-index: 1100; }
        .card { padding: 24px; border-radius: 30px; }
        .info-grid { grid-template-columns: 1fr; gap: 24px; }
        .timeline { flex-wrap: wrap; gap: 20px; justify-content: center; margin-top: 0; }
        .timeline::before { display: none; }
        .timeline-step { width: 100px; }
        
        .sidebar-overlay {
          display: none;
          position: fixed;
          top: 0; left: 0; right: 0; bottom: 0;
          background: rgba(15, 23, 42, 0.5);
          backdrop-filter: blur(4px);
          z-index: 1150;
        }
        .sidebar-overlay.active { display: block; }

        .hamburger {
          display: flex !important;
          width: 40px; height: 40px;
          align-items: center; justify-content: center;
          background: #E5EEFF;
          border-radius: 12px;
          cursor: pointer;
          color: #0F172A;
          font-size: 18px;
        }
        .price-total { font-size: 22px; }
        .driver-box { padding: 16px; flex-direction: column; text-align: center; }
      }

      .hamburger { display: none; }

      /* Rating Stars */
      .stars-input { display: flex; gap: 8px; justify-content: center; margin: 16px 0; }
      .star-btn { background: none; border: none; font-size: 32px; color: #e2e8f0; cursor: pointer; transition: .2s; padding: 0; }
      .star-btn:hover, .star-btn.active { color: #f59e0b; transform: scale(1.2); }
      .btn-rate { background: #f59e0b; color: white; padding: 14px 36px; border-radius: 14px; font-weight: 800; border: none; cursor: pointer; transition: .3s; font-family: 'Outfit', sans-serif; font-size: 16px; }
      .btn-rate:hover { background: #d97706; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3); }

      .alert { padding: 20px; border-radius: 20px; font-size: 16px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
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
            <a href="dashboard.php" class="nav-item "><i class="fas fa-home nav-icon"></i> Dashboard</a>
            <a href="pesan.php" class="nav-item "><i class="fas fa-shopping-cart nav-icon"></i> Belanja Sekarang</a>
            <a href="pesanan.php" class="nav-item active"><i class="fas fa-file-invoice nav-icon"></i> Riwayat Pesanan</a>
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
                <div class="topbar-title">
                    <a href="pesanan.php" class="btn-back"><i class="fas fa-chevron-left"></i></a>
                    <h2>Detail Pesanan #<?= str_pad($pesananId, 5, '0', STR_PAD_LEFT) ?></h2>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <?php if (in_array($pesanan['status'], ['selesai', 'dikirim'])): ?>
                    <a href="komplain.php?pesanan_id=<?= $pesananId ?>" class="btn" style="background: #fff1f2; color: #e11d48; border: 1px solid #ffe4e6; padding: 10px 24px; font-size: 14px; border-radius: 14px; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 8px;"><i class="fas fa-circle-exclamation"></i> AJUKAN KOMPLAIN</a>
                <?php endif; ?>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #eff6ff; color: #2F56D3; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px;">
                    <?php if (!empty($user['foto'])): ?>
                        <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 12px; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($successRating): ?>
                <div class="alert"><i class="fas fa-circle-check"></i> <?= $successRating ?></div>
            <?php endif; ?>

            <!-- Timeline Status -->
            <div class="card">
                <h3 class="card-title" style="border:none; padding:0; margin-bottom:40px;"><i class="fas fa-route" style="color:#2F56D3;"></i> Pantau Pesanan</h3>
                <div class="timeline">
                    <!-- Status 0: Menunggu -->
                    <div class="timeline-step <?= $currentStep >= 0 ? ($currentStep > 0 ? 'completed' : 'active') : '' ?>">
                        <div class="step-icon"><i class="fas fa-file-signature"></i></div>
                        <div class="step-label">Menunggu<br>Verifikasi</div>
                    </div>
                    <!-- Status 1: Diproses -->
                    <div class="timeline-step <?= $currentStep >= 1 ? ($currentStep > 1 ? 'completed' : 'active') : '' ?>">
                        <div class="step-icon"><i class="fas fa-box-open"></i></div>
                        <div class="step-label">Pesanan<br>Diproses</div>
                    </div>
                    <!-- Status 2: Dikirim -->
                    <div class="timeline-step <?= $currentStep >= 2 ? ($currentStep > 2 ? 'completed' : 'active') : '' ?>">
                        <div class="step-icon"><i class="fas fa-truck-fast"></i></div>
                        <div class="step-label">Pesanan<br>Diantar</div>
                    </div>
                    <!-- Status 3: Selesai -->
                    <div class="timeline-step <?= $currentStep >= 3 ? 'completed' : '' ?>">
                        <div class="step-icon"><i class="fas fa-house-circle-check"></i></div>
                        <div class="step-label">Pesanan<br>Selesai</div>
                    </div>
                </div>
                
                <?php if ($currentStep >= 2 && $pesanan['sopir_nama']): ?>
                <div class="driver-box">
                    <div class="driver-avatar"><i class="fas fa-user-tie"></i></div>
                    <div>
                        <div class="info-label">Diantar Oleh Sopir:</div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #1e3a8a;"><?= htmlspecialchars($pesanan['sopir_nama']) ?></div>
                        <div style="font-size: 13px; color: #3b82f6; font-weight: 700; margin-top: 4px;"><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($pesanan['sopir_telp'] ?? 'Tidak ada kontak') ?></div>
                    </div>
                </div>
                
                <!-- Live GPS Tracking Map for Konsumen -->
                <div id="trackingMap" style="height: 400px; border-radius: 20px; border: 2px solid #bfdbfe; margin-top: 24px; background: #f8fafc; position: relative;">
                    <div id="mapStatus" style="position: absolute; top: 10px; right: 10px; z-index: 1000; background: rgba(255,255,255,0.9); padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; color: #2F56D3; border: 1px solid #D6E4FF; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <i class="fas fa-satellite-dish fa-spin"></i> MENGHUBUNGKAN GPS...
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Detail Pesanan -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-receipt" style="color:#2F56D3;"></i> Rincian Belanja</h3>
                
                <div class="info-grid">
                    <div class="info-sec">
                        <span class="info-label">Waktu Pemesanan</span>
                        <span class="info-val"><?= formatTanggalIndo($pesanan['created_at']) ?> WIB</span>
                    </div>
                    <div class="info-sec">
                        <span class="info-label">Metode Pembayaran</span>
                        <span class="info-val" style="color: #2F56D3; text-transform: uppercase;"><?= $pesanan['metode_bayar'] ?></span>
                    </div>
                    <div class="info-sec" style="grid-column: 1 / -1;">
                        <span class="info-label">Alamat Pengiriman Sesuai Titik GPS</span>
                        <span class="info-val" style="background:#f8fafc; padding:16px; border-radius:16px; border:1px solid #f1f5f9; display:block; margin-top:8px;">
                            <i class="fas fa-map-marker-alt" style="color:#ef4444; margin-right:8px;"></i> <?= htmlspecialchars($pesanan['alamat_pengiriman'] ?: $user['alamat']) ?>
                        </span>
                    </div>
                </div>

                <table class="order-table">
                    <thead>
                        <tr>
                            <th>PRODUK</th>
                            <th>BANYAKNYA</th>
                            <th style="text-align: right;">TOTAL HARGA BAYAR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['produk_nama']) ?></td>
                            <td><?= $d['jumlah'] ?> <?= strtoupper($d['satuan']) ?></td>
                            <td style="text-align: right;"><?= formatRupiah($d['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="price-row">
                    <span style="font-size: 15px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">TOTAL KESELURUHAN PEMBAYARAN</span>
                    <span class="price-total"><?= formatRupiah($pesanan['total']) ?></span>
                </div>
                
                <?php if ($pesanan['catatan']): ?>
                <div style="margin-top: 24px; padding: 20px; background: #fffbeb; border-radius: 20px; border: 1px solid #fef3c7;">
                    <span class="info-label" style="color: #b45309;">Catatan Pesanan Anda</span>
                    <p style="font-size: 14px; color: #92400e; font-style: italic; font-weight: 600;">"<?= htmlspecialchars($pesanan['catatan']) ?>"</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Bukti Pengiriman -->
            <?php if ($pesanan['status'] === 'selesai' && $pesanan['bukti_pengiriman']): ?>
            <div class="card">
                <h3 class="card-title"><i class="fas fa-camera" style="color:#16a34a;"></i> Bukti Pengiriman Tiba</h3>
                <div style="display: flex; gap: 40px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <img src="../uploads/<?= htmlspecialchars($pesanan['bukti_pengiriman']) ?>" alt="Bukti Kirim" style="width: 100%; border-radius: 24px; border: 4px solid #f1f5f9; box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1);">
                    </div>
                    <div style="flex: 1; min-width: 250px;">
                        <div class="info-sec" style="margin-bottom: 24px;">
                            <span class="info-label">Keterangan dari Sopir</span>
                            <div class="info-val" style="font-size: 16px; background: #f0fdf4; color: #15803d; padding: 20px; border-radius: 20px; border: 1px solid #bbf7d0;">
                                <?= htmlspecialchars($pesanan['catatan_pengiriman'] ?: 'Diterima dengan baik dan sesuai.') ?>
                            </div>
                        </div>
                        <div class="info-sec">
                            <span class="info-label">Diselesaikan Pada</span>
                            <span class="info-val"><?= formatTanggalIndo($pesanan['updated_at']) ?> WIB</span>
                        </div>
                    </div>
                </div>

                <!-- Star Rating Section -->
                <div style="margin-top: 40px; padding-top: 32px; border-top: 2px dashed #f1f5f9; text-align: center;">
                    <?php if (!$pesanan['rating']): ?>
                        <div style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 18px; color: #1e293b; margin-bottom: 8px;">Bagaimana Kualitas Pengiriman Kami?</div>
                        <p style="font-size: 14px; color: #94a3b8; margin-bottom: 16px;">Berikan rating untuk meningkatkan layanan kami.</p>
                        
                        <form method="POST">
                            <div class="stars-input" id="starGroup">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <button type="button" class="star-btn" onclick="setRate(<?= $i ?>)"><i class="fas fa-star" id="star-<?= $i ?>"></i></button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="ratingVal" value="">
                            <button type="submit" name="kirim_rating" id="btnSubmitRate" class="btn-rate" style="display:none;">KIRIM RATING</button>
                        </form>
                    <?php else: ?>
                        <div style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 14px; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px;">RATING ANDA UNTUK PESANAN INI</div>
                        <div style="display: flex; gap: 6px; justify-content: center;">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="fas fa-star" style="font-size: 24px; color: <?= $i <= $pesanan['rating'] ? '#f59e0b' : '#e2e8f0' ?>;"></i>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
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

    // Live GPS Tracking untuk Konsumen - Hanya jika ada sopir & status dikirim
    let map, directionsService, directionsRenderer;
    let userMarker, driverMarker;
    let lastLat = null, lastLng = null;
    const userLat = <?= json_encode($pesanan['latitude_pengiriman'] ?? $user['latitude'] ?? -2.990494) ?>;
    const userLng = <?= json_encode($pesanan['longitude_pengiriman'] ?? $user['longitude'] ?? 104.756534) ?>;

    function initMap() {
        const mapEl = document.getElementById('trackingMap');
        if (!mapEl) return;

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            suppressMarkers: true,
            polylineOptions: {
                strokeColor: '#3b82f6',
                strokeOpacity: 0.8,
                strokeWeight: 6
            }
        });

        const initialLocation = { lat: parseFloat(userLat), lng: parseFloat(userLng) };
        map = new google.maps.Map(mapEl, {
            center: initialLocation,
            zoom: 13,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });

        directionsRenderer.setMap(map);

        userMarker = new google.maps.Marker({
            position: initialLocation,
            map: map,
            title: "Titik Pengantaran"
        });

        const userInfoWindow = new google.maps.InfoWindow({
            content: '<b>Titik Pengantaran</b><br/><?= htmlspecialchars($pesanan['alamat_pengiriman'] ?: $user['alamat']) ?>'
        });
        userMarker.addListener('click', () => userInfoWindow.open(map, userMarker));
        userInfoWindow.open(map, userMarker);

        // Mulai pelacakan
        updateSopirLocation();
        setInterval(updateSopirLocation, 5000);
    }

    function updateSopirLocation() {
        fetch(`../api/get_lokasi_sopir_pesanan.php?pesanan_id=<?= $pesananId ?>`)
            .then(res => res.json())
            .then(data => {
                const statusEl = document.getElementById('mapStatus');
                if (data.status === 'success' && data.data) {
                    const sopir = data.data;
                    const sopirLat = parseFloat(sopir.latitude);
                    const sopirLng = parseFloat(sopir.longitude);
                    const sopirLatLng = new google.maps.LatLng(sopirLat, sopirLng);
                    const userLatLng = new google.maps.LatLng(parseFloat(userLat), parseFloat(userLng));
                    
                    if (statusEl) statusEl.innerHTML = `<i class="fas fa-circle" style="color:#10b981; font-size:8px;"></i> LIVE: ${new Date().toLocaleTimeString('id-ID')} WIB`;

                    if (driverMarker) {
                        driverMarker.setPosition(sopirLatLng);
                    } else {
                        driverMarker = new google.maps.Marker({
                            position: sopirLatLng,
                            map: map,
                            title: sopir.nama,
                            icon: {
                                url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                            }
                        });

                        const driverInfoWindow = new google.maps.InfoWindow({
                            content: `<b>${sopir.nama}</b><br/><span style="color:#10b981; font-size: 11px; font-weight:800;"><i class="fas fa-satellite"></i> SEDANG MENGANTAR</span>`
                        });
                        driverMarker.addListener('click', () => driverInfoWindow.open(map, driverMarker));
                        driverInfoWindow.open(map, driverMarker);
                    }

                    if (lastLat === null || Math.abs(sopirLat - lastLat) > 0.0001 || Math.abs(sopirLng - lastLng) > 0.0001) {
                        directionsService.route({
                            origin: sopirLatLng,
                            destination: userLatLng,
                            travelMode: google.maps.TravelMode.DRIVING
                        }, function(response, status) {
                            if (status === 'OK') {
                                directionsRenderer.setDirections(response);
                            }
                        });

                        lastLat = sopirLat;
                        lastLng = sopirLng;

                        const bounds = new google.maps.LatLngBounds();
                        bounds.extend(sopirLatLng);
                        bounds.extend(userLatLng);
                        map.fitBounds(bounds);
                    }
                } else {
                    if (statusEl) statusEl.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> SOPIR OFFLINE`;
                }
            })
            .catch(err => {
                const statusEl = document.getElementById('mapStatus');
                if (statusEl) statusEl.innerHTML = `<i class="fas fa-wifi" style="color:#ef4444;"></i> GANGGUAN SINYAL`;
            });
    }

    function setRate(val) {
        document.getElementById('ratingVal').value = val;
        document.getElementById('btnSubmitRate').style.display = 'inline-block';
        for (let i = 1; i <= 5; i++) {
            const star = document.getElementById('star-' + i);
            if (i <= val) {
                star.parentElement.classList.add('active');
            } else {
                star.parentElement.classList.remove('active');
            }
        }
    }
</script>

<style>
#trackingMap { font-family: 'Inter', sans-serif; }
.leaflet-popup-content-wrapper { font-family: 'Inter', sans-serif; }
@keyframes pulse { 0%{transform:scale(1)} 50%{transform:scale(1.05)} 100%{transform:scale(1)} }
@media (max-width: 768px) { #trackingMap { height: 280px; } }
</style>
</body>
</html>
