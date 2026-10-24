<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('konsumen');
$user = getCurrentUser($pdo);

$error = '';
$success = '';

// Ambil produk
$produkList = $pdo->query("SELECT * FROM produk ORDER BY nama")->fetchAll();

// Definisi slot jam pengantaran
$slotJam = [
    'pagi'   => ['label' => 'Pagi',   'jam' => '08:00 – 10:00'],
    'siang'  => ['label' => 'Siang',  'jam' => '10:00 – 13:00'],
    'sore'   => ['label' => 'Sore',   'jam' => '13:00 – 17:30'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode = $_POST['metode_bayar'] ?? 'cash';
    $catatan = trim($_POST['catatan'] ?? '');
    $jamPengantaran = $_POST['jam_pengantaran'] ?? '';
    $items = $_POST['qty'] ?? [];
    
    // Cek verifikasi akun toko
    if ($user['role_customer'] === 'toko' && $user['status_verifikasi'] !== 'verified') {
        $error = 'Akun toko Anda belum terverifikasi!';
    } else {
        // Filter item yang dipesan (qty > 0)
        $orderItems = [];
        foreach ($items as $produkId => $qty) {
            $qty = (int)$qty;
            if ($qty > 0) {
                $orderItems[$produkId] = $qty;
            }
        }
        
        if (empty($orderItems)) {
            $error = 'Pilih minimal 1 produk!';
        } else {
            // Validasi stok
            $total = 0;
            $details = [];
            foreach ($orderItems as $produkId => $qty) {
                $stmtP = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
                $stmtP->execute([$produkId]);
                $prod = $stmtP->fetch();
                if (!$prod) continue;
                
                if ($prod['stok'] < $qty) {
                    $error = "Stok {$prod['nama']} tidak mencukupi! Tersisa: {$prod['stok']}";
                    break;
                }
                
                $harga = ($user['role_customer'] === 'toko') ? $prod['harga_toko'] : $prod['harga_umum'];
                $subtotal = $harga * $qty;
                $total += $subtotal;
                $details[] = [
                    'produk_id' => $prod['id'],
                    'jumlah' => $qty,
                    'harga_satuan' => $harga,
                    'subtotal' => $subtotal
                ];
            }
            
            if (!$error) {
                // Upload bukti transfer
                $buktiTransfer = null;
                if ($metode === 'transfer') {
                    if (empty($_FILES['bukti_transfer']['name'])) {
                        $error = 'Wajib upload bukti transfer!';
                    } else {
                        $buktiTransfer = uploadFile($_FILES['bukti_transfer'], 'bukti_transfer');
                        if (!$buktiTransfer) {
                            $error = 'Gagal upload bukti transfer!';
                        }
                    }
                }
                
                if (!$error) {
                    // Validasi slot jam
                    if (empty($jamPengantaran) || !array_key_exists($jamPengantaran, $slotJam)) {
                        $error = 'Silakan pilih slot jam pengantaran!';
                    }
                }

                if (!$error) {
                    $pdo->beginTransaction();
                    try {
                        $latP = !empty($_POST['latitude_pengiriman']) ? $_POST['latitude_pengiriman'] : null;
                        $lngP = !empty($_POST['longitude_pengiriman']) ? $_POST['longitude_pengiriman'] : null;
                        $alamatP = $_POST['alamat_pengiriman'] ?? '';
                        $jamLabel = $slotJam[$jamPengantaran]['label'] . ' (' . $slotJam[$jamPengantaran]['jam'] . ')';
                        $stmt = $pdo->prepare("INSERT INTO pesanan (user_id, total, metode_bayar, bukti_transfer, status, catatan, jam_pengantaran, alamat_pengiriman, latitude_pengiriman, longitude_pengiriman) VALUES (?,?,?,?,?,?,?,?,?,?)");
                        $stmt->execute([getUserId(), $total, $metode, $buktiTransfer, 'menunggu', $catatan, $jamLabel, $alamatP, $latP, $lngP]);
                        $pesananId = $pdo->lastInsertId();
                        
                        foreach ($details as $d) {
                            $stmt2 = $pdo->prepare("INSERT INTO transaksi_pesanan (pesanan_id, produk_id, jumlah, harga_satuan, subtotal) VALUES (?,?,?,?,?)");
                            $stmt2->execute([$pesananId, $d['produk_id'], $d['jumlah'], $d['harga_satuan'], $d['subtotal']]);
                        }
                        
                        $pdo->commit();
                        $success = 'Pesanan Berhasil! Silakan cek menu Riwayat.';
                        // Refresh produk list
                        $produkList = $pdo->query("SELECT * FROM produk WHERE stok > 0 ORDER BY nama")->fetchAll();
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = 'Gagal membuat pesanan: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$hargaField = ($user['role_customer'] === 'toko') ? 'harga_toko' : 'harga_umum';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Produk — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAjAySxEH3JC2BV_pDHddk4rPxfI8klhKs&libraries=places&callback=initMap" async defer></script>
    <style>
:root {
    --primary: #2F56D3;
    --primary-dark: #1E43B8;
    --bg: #F4F8FF;
    --surface: #FFFFFF;
    --card: #FFFFFF;
    --card-soft: #EEF5FF;
    --text: #0F172A;
    --muted: #64748B
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
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: 'Inter', sans-serif; background:#F4F8FF; color:#0F172A; min-height:100vh; -webkit-font-smoothing: antialiased; line-height: 1.6; }
      
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
      .topbar { height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: #FFFFFF; border-bottom: 1px solid rgba(59, 130, 246, 0.12); position: sticky; top: 0; z-index: 1100; }
      .topbar-title h2 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; color: #0f172a; }
      
      .content { padding: 40px; display: flex; flex-direction: column; gap: 40px; width: 100%; align-items: stretch; }
      
      /* Product Catalog */
      .catalog-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; align-items: stretch; }
      .product-card { background:#FFFFFF; border-radius: 32px; padding: 20px; border: 1px solid rgba(59, 130, 246, 0.12); transition: .4s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column; box-shadow: 0 10px 20px -5px rgba(0,0,0,0.02); height: 100%; position: relative; }
      .product-card:hover { transform: translateY(-10px); border-color: #dbeafe; box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.1); }
      
     .product-img-box { background: linear-gradient(135deg, #DCEEFF 0%, #C7E0FF 100%); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; position: relative; overflow: hidden; width: 100%; padding-top: 100%; }
      .product-img-box img { position: absolute; top: 10%; left: 10%; width: 80%; height: 80%; object-fit: contain; transition: .4s; }
      .product-swiper { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
      .product-card:hover .product-img-box img { transform: scale(1.05); }
      .swiper-pagination-bullet { background: #cbd5e1; opacity: 1; }
      .swiper-pagination-bullet-active { background: #2F56D3; width: 20px; border-radius: 10px; }
      
      .product-name { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 6px; color: #0f172a; }
      .stock-badge { position: absolute; top: 12px; right: 12px; background: #c8e4f5; padding: 6px 12px; border-radius: 12px; font-size: 13px; font-weight: 800; color: #2F56D3; box-shadow: 0 4px 6px rgba(0,0,0,0.05); z-index: 2; }
      
      .product-price { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: #2F56D3; margin-bottom: 20px; }
      
      .qty-box { display: flex; align-items: center; background: #E5EEFF; border-radius: 16px; padding: 6px; margin-top: auto; }
      .qty-btn { width: 36px; height: 36px; border-radius: 12px; border: none; background: #E5EEFF; color: #1e293b; cursor: pointer; transition: .3s; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; }
      .qty-btn:hover { background: #2F56D3; color: white; transform: scale(1.1); }
      .qty-input { flex: 1; border: none; background: transparent; text-align: center; font-weight: 800; font-size: 15px; width: 40px; color: #0f172a; outline: none; }
      
      /* Checkout Panel */
      .checkout-panel { background: #FFFFFF; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.04); }
      .checkout-title { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; margin-bottom: 32px; display: flex; align-items: center; gap: 12px; color: #0f172a; letter-spacing: -0.5px; }
      
      .checkout-grid { display: grid; grid-template-columns: 420px 1fr; gap: 48px; }
      .checkout-info-side { display: flex; flex-direction: column; }
      .checkout-map-side { display: flex; flex-direction: column; }
      
      .basket-list { margin-bottom: 32px; max-height: 250px; overflow-y: auto; padding-right: 10px; }
      .basket-list::-webkit-scrollbar { width: 6px; }
      .basket-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
      
      .basket-item { display: flex; justify-content: space-between; font-size: 16px; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #f1f5f9; align-items: center; }
      .basket-item-name { font-weight: 700; color: #334155; }
      
      .total-row { display: flex; justify-content: space-between; font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; margin-bottom: 32px; color: #2F56D3; padding-top: 16px; border-top: 2px solid #f8fafc; letter-spacing: -0.5px; }
      
      .field-label { display: block; font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px; }
      .input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 1px solid rgba(59, 130, 246, 0.12); background: #f8fafc; font-family: inherit; font-size: 16px; font-weight: 600; outline: none; transition: .3s; margin-bottom: 24px; }
      .input:focus { border-color: #2F56D3; background: #fbfdff; box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.08); }
      
      .btn-order { background: #2F56D3; color: white; border: none; padding: 18px; border-radius: 20px; width: 100%; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer; transition: .4s; box-shadow: 0 15px 30px -10px rgba(59, 130, 246, 0.4); display: flex; align-items: center; justify-content: center; gap: 12px; }
      .btn-order:hover { background: #1E43B8; transform: translateY(-4px); box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.5); }
      .btn-order:disabled { opacity: 0.2; cursor: not-allowed; transform: none; box-shadow: none; }
      
      .alert { padding: 18px; border-radius: 20px; font-size: 14px; font-weight: 700; margin-bottom: 32px; grid-column: span 2; display: flex; align-items: center; gap: 12px; }
      .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
      .alert-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fee2e2; }

      @media (max-width: 1024px) {
        .sidebar { 
          transform: translateX(-100%); 
          z-index: 1200; 
          transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
          box-shadow: 20px 0 40px rgba(0,0,0,0.1);
        }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .content { grid-template-columns: 1fr; padding: 20px; }
        .topbar { padding: 0 20px; z-index: 1100; }
        .checkout-panel { position: static; margin-top: 24px; padding: 32px 24px; border-radius: 30px; }
        .checkout-grid { grid-template-columns: 1fr; gap: 32px; }
        .catalog-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
        @media (max-width: 640px) { .catalog-grid { grid-template-columns: 1fr; } }
        
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
          background: #b8d8ed;
          border-radius: 12px;
          cursor: pointer;
          color: #0F172A;
          font-size: 18px;
        }
        .topbar-price-badge { display: none !important; }
      }

      .hamburger { display: none; }
      .pac-container {
          z-index: 10000 !important;
          border-radius: 16px;
          border: 1px solid rgba(59, 130, 246, 0.12);
          font-family: 'Inter', sans-serif;
          box-shadow: 0 10px 25px rgba(0,0,0,0.1);
          margin-top: 5px;
      }
      .pac-item {
          padding: 10px 14px;
          cursor: pointer;
          font-size: 14px;
          display: flex;
          align-items: center;
          border-bottom: 1px solid #f1f5f9;
      }
      .pac-item:hover {
          background-color: #f8fafc;
      }
      .pac-item-query {
          font-size: 14px;
          font-weight: 600;
          color: #1e293b;
      }
      .pac-matched {
          color: #2F56D3;
      }
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
            <a href="pesan.php" class="nav-item active"><i class="fas fa-shopping-cart nav-icon"></i> Belanja Sekarang</a>
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
                <div class="topbar-title"><h2>Pesan Produk</h2></div>
            </div>
            <div class="topbar-price-badge" style="padding: 10px 20px; background: #eff6ff; border-radius: 50px; font-size: 14px; font-weight: 800; color: #2F56D3; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-tag"></i> HARGA <?= strtoupper($user['role_customer']) ?> AKTIF
            </div>
        </header>

        <form method="POST" enctype="multipart/form-data">
            <div class="content">
                <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

                <div class="catalog-grid">
                    <?php foreach ($produkList as $p): ?>
                        <div class="product-card">
                            <div class="product-img-box">
                                <?php
                                $fotos = [];
                                if (!empty($p['foto'])) $fotos[] = $p['foto'];
                                if (!empty($p['foto2'])) $fotos[] = $p['foto2'];
                                if (!empty($p['foto3'])) $fotos[] = $p['foto3'];
                                ?>
                                <?php if (count($fotos) > 0): ?>
                                    <div class="swiper product-swiper" style="width: 100%; height: 100%;">
                                        <div class="swiper-wrapper">
                                            <?php foreach ($fotos as $f): ?>
                                            <div class="swiper-slide" style="display: flex; align-items: center; justify-content: center;">
                                                <img src="<?= getUploadUrl($f, 'produk') ?>" alt="<?= htmlspecialchars($p['nama']) ?>" 
                                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNmOGZhZmMiIHJ4PSIyMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgZm9udC1zaXplPSIxMiIgZm9udC13ZWlnaHQ9IjgwMCIgZmlsbD0iIzY0NzQ4YiIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+8J+QiBHYW1iYXIgTmFnPC90ZXh0Pjwvc3ZnPg=='; this.style.objectFit='contain';">
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="swiper-pagination"></div>
                                    </div>
                                <?php else: ?>
                                    <i class="fas fa-bottle-water" style="font-size: 64px; color: #2F56D3; opacity: 0.1;"></i>
                                <?php endif; ?>
                                <span class="stock-badge">Stok: <?= $p['stok'] ?></span>
                            </div>
                            <div class="product-name"><?= htmlspecialchars($p['nama']) ?></div>
                            <div class="product-price"><?= formatRupiah($p[$hargaField]) ?></div>
                            
                            <div class="qty-box" style="<?= $p['stok'] <= 0 ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>">
                                <button type="button" class="qty-btn" onclick="modifyQty('<?= $p['id'] ?>', -1)" <?= $p['stok'] <= 0 ? 'disabled' : '' ?>><i class="fas fa-minus"></i></button>
                                <input type="number" name="qty[<?= $p['id'] ?>]" id="qty-<?= $p['id'] ?>" class="qty-input" value="0" min="0" max="<?= $p['stok'] ?>" data-price="<?= $p[$hargaField] ?>" data-name="<?= htmlspecialchars($p['nama']) ?>" onchange="updateBasket()" <?= $p['stok'] <= 0 ? 'disabled' : '' ?>>
                                <button type="button" class="qty-btn" onclick="modifyQty('<?= $p['id'] ?>', 1, <?= $p['stok'] ?>)" <?= $p['stok'] <= 0 ? 'disabled' : '' ?>><i class="fas fa-plus"></i></button>
                            </div>
                            <?php if ($p['stok'] <= 0): ?>
                                <div style="text-align: center; color: #ef4444; font-size: 11px; font-weight: 800; margin-top: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fas fa-circle-exclamation"></i> Stok Habis
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-panel">
                    <h3 class="checkout-title"><i class="fas fa-basket-shopping" style="color: #2F56D3;"></i> Ringkasan Belanja</h3>
                    
                    <div class="checkout-grid">
                        <div class="checkout-info-side">
                            <div id="basket-content" class="basket-list">
                                <div style="text-align: center; color: #94a3b8; padding: 40px 0; font-size: 14px; font-weight: 600;">
                                    <i class="fas fa-cart-plus" style="font-size: 24px; display: block; margin-bottom: 12px; opacity: 0.3;"></i>
                                    Keranjang masih kosong
                                </div>
                            </div>

                            <div class="total-row">
                                <span>Total Bayar</span>
                                <span id="final-total">Rp 0</span>
                            </div>
                        

                            <div class="checkout-form">
                                <label class="field-label">Pilih Metode Pembayaran</label>
                                <select name="metode_bayar" id="pay-method" class="input" onchange="togglePayInfo(this.value)">
                                    <option value="cash">Cash (Bayar Di Tempat)</option>
                                    <option value="transfer">Transfer Bank</option>
                                </select>

                                <div id="transfer-info" style="display: none; background: #fdf6b2; padding: 20px; border-radius: 20px; border: 1px solid #f9ebcc; margin-bottom: 24px; font-size: 13px;">
                                    <p style="font-weight: 800; color: #854d0e; margin-bottom: 6px;">Transfer ke Rekening TIRTA OSMOSIS SAMPURNA PALEMBANG</p>
                                    <p style="font-weight: 700; font-size: 15px; color: #0f172a;"> 0216661138 (Bank BCA)</p>
                                    <p style="font-weight: 700; font-size: 15px; color: #0f172a;"> 1130066000070 (Bank Mandiri)</p>
                                    <p style="margin-top: 12px; font-size: 11px; color: #92400e; font-weight: 600;">*Wajib lampirkan bukti transfer di bawah ini.</p>
                                </div>
                                
                                <div id="upload-box" style="display: none;">
                                    <label class="field-label">Lampirkan Bukti Transfer</label>
                                    <input type="file" name="bukti_transfer" class="input">
                                </div>

                                <!-- SLOT JAM PENGANTARAN -->
                                <label class="field-label" style="margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-clock" style="color:#2F56D3;"></i> Pilih Slot Jam Pengantaran
                                </label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;" id="slot-jam-grid">
                                    <?php foreach ($slotJam as $key => $slot): ?>
                                    <label class="slot-jam-card" for="slot-<?= $key ?>" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px 12px; border-radius: 18px; border: 2px solid rgba(59,130,246,0.15); cursor: pointer; transition: all .25s; background: #f8fafc; text-align: center;">
                                        <input type="radio" name="jam_pengantaran" id="slot-<?= $key ?>" value="<?= $key ?>" style="display:none;" <?= !empty($_POST['jam_pengantaran']) && $_POST['jam_pengantaran'] === $key ? 'checked' : '' ?> required>
                                        <span style="font-weight: 800; font-size: 18px; color: #0f172a; margin-bottom: 4px;"><?= $slot['label'] ?></span>
                                        <span style="font-size: 11px; color: #64748b; font-weight: 700; margin-top: 2px;"><?= $slot['jam'] ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <style>
                                    .slot-jam-card:has(input:checked) {
                                        border-color: #2F56D3 !important;
                                        background: #eff6ff !important;
                                        box-shadow: 0 0 0 4px rgba(47,86,211,0.12);
                                    }
                                    .slot-jam-card:has(input:checked) span { color: #1e40af !important; }
                                    .slot-jam-card:hover { border-color: #93c5fd !important; background: #f0f7ff !important; }
                                </style>

                                <label class="field-label">Catatan Pesanan (Opsional)</label>
                                <textarea name="catatan" class="input" style="height: 100px; resize: none;" placeholder="Contoh: Titip di satpam..."></textarea>

                                <button type="submit" class="btn-order" id="order-submit" disabled>KONFIRMASI PESANAN <i class="fas fa-check-double"></i></button>
                            </div>
                        </div>
                        
<div class="checkout-map-side">
    <label class="field-label" style="color:#0f172a;">
        <i class="fas fa-search" style="color:#2F56D3;"></i> Cari Alamat Pengiriman
    </label>

    <!-- Search dengan autocomplete dropdown -->
    <div style="position: relative; margin-bottom: 15px;">
        <div style="display: flex; gap: 10px;">
            <div style="position: relative; flex: 1;">
                <input type="text" id="search-address" class="input" style="margin-bottom: 0;"
                    placeholder="Ketik nama jalan, kelurahan, atau daerah..."
                    autocomplete="off">
                <!-- Dropdown autocomplete -->
                <div id="autocomplete-list" style="
                    display: none;
                    position: absolute;
                    top: 100%; left: 0; right: 0;
                    background: #c8e4f5;
                    border: 1px solid rgba(59, 130, 246, 0.12);
                    border-radius: 16px;
                    box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15);
                    z-index: 9999;
                    max-height: 280px;
                    overflow-y: auto;
                    margin-top: 6px;
                "></div>
            </div>
            <button type="button" onclick="searchLocation()" class="qty-btn"
                style="width: 50px; height: 50px; flex-shrink: 0; background: #2F56D3; color: white;">
                <i class="fas fa-magnifying-glass"></i>
            </button>
        </div>
    </div>

    <!-- Tombol lokasi saya -->
    <button type="button" onclick="useMyLocation()" style="
        width: 100%; margin-bottom: 15px;
        padding: 12px; border-radius: 16px;
        border: 2px dashed #dbeafe;
        background: #eff6ff; color: #2F56D3;
        font-weight: 700; font-size: 14px; cursor: pointer;
        transition: .3s; display: flex; align-items: center;
        justify-content: center; gap: 8px;">
        <i class="fas fa-crosshairs"></i>
        <span id="gps-btn-text">Gunakan Lokasi Saya (GPS)</span>
    </button>

    <!-- Peta -->
    <div style="border-radius: 24px; overflow: hidden; border: 1px solid rgba(59, 130, 246, 0.12);
        height: 380px; margin-bottom: 20px; z-index: 1;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);">
        <div id="delivery-map" style="width: 100%; height: 100%;"></div>
    </div>

    <input type="hidden" name="latitude_pengiriman" id="lat-pengiriman"
        value="<?= htmlspecialchars($user['latitude'] ?? '') ?>">
    <input type="hidden" name="longitude_pengiriman" id="lng-pengiriman"
        value="<?= htmlspecialchars($user['longitude'] ?? '') ?>">

    <label class="field-label">Detail Alamat Pengiriman</label>
    <textarea name="alamat_pengiriman" id="alamat-pengiriman" class="input"
        style="height: 100px; resize: none;" required
        placeholder="Alamat akan terisi otomatis saat memilih lokasi di peta..."
><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>

    <div style="background: #eff6ff; padding: 15px; border-radius: 16px;
        font-size: 13px; color: #2F56D3; font-weight: 600;">
        <i class="fas fa-info-circle"></i>
        Ketik nama jalan lalu pilih dari saran, atau geser pin tepat di depan pagar.
        Alamat akan terisi otomatis.
    </div>
</div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
// =============================================
//  MAP & GOOGLE PLACES API INITIALIZATION
// =============================================
let map, deliveryMarker, geocoder, autocomplete;
let deliveryLat = <?= $user['latitude'] ? floatval($user['latitude']) : '-2.990494' ?>;
let deliveryLng = <?= $user['longitude'] ? floatval($user['longitude']) : '104.756534' ?>;

function initMap() {
    geocoder = new google.maps.Geocoder();
    const initialLocation = { lat: deliveryLat, lng: deliveryLng };

    // 1. Inisialisasi Peta Google
    map = new google.maps.Map(document.getElementById('delivery-map'), {
        center: initialLocation,
        zoom: 16,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
    });

    // 2. Buat Pin yang Bisa Digeser (Draggable Marker)
    deliveryMarker = new google.maps.Marker({
        position: initialLocation,
        map: map,
        draggable: true,
        title: "Alamat Pengiriman"
    });

    // Listener saat user selesai menggeser pin
    deliveryMarker.addListener('dragend', function() {
        const coords = deliveryMarker.getPosition();
        updateDeliveryLocation(coords.lat(), coords.lng());
        reverseGeocode(coords);
    });

    // Listener saat user mengklik area peta manapun
    map.addListener('click', function(e) {
        deliveryMarker.setPosition(e.latLng);
        updateDeliveryLocation(e.latLng.lat(), e.latLng.lng());
        reverseGeocode(e.latLng);
    });

    // Inisialisasi Google Places Autocomplete
    initAutocomplete();

    // Auto GPS jika belum ada koordinat di database
    <?php if (empty($user['latitude'])): ?>
    useMyLocation();
    <?php endif; ?>
}

function updateDeliveryLocation(lat, lng) {
    document.getElementById('lat-pengiriman').value = lat;
    document.getElementById('lng-pengiriman').value = lng;
}

// =============================================
//  REVERSE GEOCODING
//  Pin digeser/klik -> Alamat teks terisi otomatis
// =============================================
function reverseGeocode(latLng) {
    const textarea = document.getElementById('alamat-pengiriman');
    textarea.placeholder = 'Mencari alamat...';

    geocoder.geocode({ location: latLng }, function(results, status) {
        if (status === 'OK') {
            if (results[0]) {
                textarea.value = results[0].formatted_address;
                textarea.placeholder = 'Alamat pengiriman...';
            }
        } else {
            textarea.placeholder = 'Gagal mengambil alamat otomatis, isi manual.';
        }
    });
}

// =============================================
//  AUTOCOMPLETE - Google Places API
//  Ketik -> Muncul saran tempat & jalan dari Google
// =============================================
function initAutocomplete() {
    const searchInput = document.getElementById('search-address');

    // Buat Autocomplete Widget dari Google
    autocomplete = new google.maps.places.Autocomplete(searchInput, {
        componentRestrictions: { country: 'id' }, // Hanya mencari di Indonesia
        fields: ['geometry', 'formatted_address', 'name'] // Hanya meminta data dasar agar hemat biaya
    });

    // Ikat pencarian ke viewport peta (Location Biasing)
    autocomplete.bindTo('bounds', map);

    // Saat user memilih salah satu alamat dari saran
    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        if (!place.geometry || !place.geometry.location) {
            alert('Lokasi tidak detail, silakan cari lokasi lain.');
            return;
        }

        // Pindahkan peta & pin ke lokasi terpilih
        map.setCenter(place.geometry.location);
        map.setZoom(18);
        deliveryMarker.setPosition(place.geometry.location);

        updateDeliveryLocation(place.geometry.location.lat(), place.geometry.location.lng());

        // Isi input alamat lengkap secara otomatis
        searchInput.value = place.name;
        document.getElementById('alamat-pengiriman').value = place.formatted_address;
    });

    // Cegah submit form saat menekan Enter di kolom pencarian
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
}

// =============================================
//  SEARCH MANUAL (tombol kaca pembesar)
// =============================================
function searchLocation() {
    let query = document.getElementById('search-address').value.trim();
    if (query.length < 3) { alert('Ketik minimal 3 huruf...'); return; }
    if (!query.toLowerCase().includes('palembang')) query += ' Palembang';

    geocoder.geocode({ address: query }, function(results, status) {
        if (status === 'OK') {
            const location = results[0].geometry.location;
            map.setCenter(location);
            map.setZoom(18);
            deliveryMarker.setPosition(location);
            updateDeliveryLocation(location.lat(), location.lng());
            document.getElementById('alamat-pengiriman').value = results[0].formatted_address;
        } else {
            alert('Lokasi tidak ditemukan di Palembang.');
        }
    });
}

// =============================================
//  GPS LOKASI SAYA
// =============================================
function useMyLocation() {
    const btn = document.getElementById('gps-btn-text');
    if (!navigator.geolocation) { alert('Browser tidak mendukung GPS.'); return; }

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari lokasi...';

    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const userLatLng = { lat: lat, lng: lng };

            map.setCenter(userLatLng);
            map.setZoom(18);
            deliveryMarker.setPosition(userLatLng);
            updateDeliveryLocation(lat, lng);
            reverseGeocode(userLatLng);

            btn.innerHTML = '<i class="fas fa-check"></i> Lokasi ditemukan!';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya (GPS)';
            }, 3000);
        },
        function (err) {
            btn.innerHTML = '<i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya (GPS)';
            alert('GPS gagal: ' + err.message);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
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

// Swiper
document.querySelectorAll('.product-swiper').forEach(el => {
    new Swiper(el, {
        loop: true,
        pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
        grabCursor: true
    });
});

// Basket
function modifyQty(id, delta, max) {
    const input = document.getElementById('qty-' + id);
    let next = (parseInt(input.value) || 0) + delta;
    if (next < 0) next = 0;
    if (max !== undefined && next > max) next = max;
    input.value = next;
    updateBasket();
}

function updateBasket() {
    const inputs = document.querySelectorAll('.qty-input');
    const container = document.getElementById('basket-content');
    const totalDisplay = document.getElementById('final-total');
    const submitBtn = document.getElementById('order-submit');

    let total = 0, html = '', count = 0;

    inputs.forEach(input => {
        let qty = parseInt(input.value) || 0;
        if (qty > 0) {
            let price = parseInt(input.dataset.price);
            let sub = qty * price;
            total += sub;
            count++;
            html += `
                <div class="basket-item">
                    <span class="basket-item-name">${input.dataset.name} <span style="color:#2F56D3">×${qty}</span></span>
                    <span style="font-weight:800;color:#0f172a;">Rp ${sub.toLocaleString('id-ID')}</span>
                </div>`;
        }
    });

    container.innerHTML = count > 0 ? html :
        '<div style="text-align:center;color:#94a3b8;padding:40px 0;font-size:14px;font-weight:600;"><i class="fas fa-cart-plus" style="font-size:24px;display:block;margin-bottom:12px;opacity:0.3;"></i>Keranjang masih kosong</div>';

    submitBtn.disabled = count === 0;
    totalDisplay.innerText = 'Rp ' + total.toLocaleString('id-ID');
}

function togglePayInfo(val) {
    document.getElementById('transfer-info').style.display = val === 'transfer' ? 'block' : 'none';
    document.getElementById('upload-box').style.display   = val === 'transfer' ? 'block' : 'none';
}
</script>

</body>
</html>