<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('konsumen');
$user = getCurrentUser($pdo);

// Ambil semua pesanan user
$stmt = $pdo->prepare("SELECT p.*, u2.nama as sopir_nama FROM pesanan p LEFT JOIN users u2 ON p.sopir_id = u2.id WHERE p.user_id = ? ORDER BY p.created_at DESC");
$stmt->execute([getUserId()]);
$pesananList = $stmt->fetchAll();

// Ambil semua detail produk untuk pesanan-pesanan tersebut
$allDetails = [];
if (!empty($pesananList)) {
    $orderIds = array_column($pesananList, 'id');
  
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmtDetails = $pdo->prepare("SELECT tp.*, pr.nama as produk_nama, pr.foto, pr.satuan 
                                FROM transaksi_pesanan tp 
                                JOIN produk pr ON tp.produk_id = pr.id 
                                WHERE tp.pesanan_id IN ($placeholders)");
    $stmtDetails->execute($orderIds);
    $detailsData = $stmtDetails->fetchAll();
    
    // Group by pesanan_id
    foreach ($detailsData as $d) {
        $allDetails[$d['pesanan_id']][] = $d;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --bg: #eaf4ff;
  --surface: #ffffff;
  --surface-2: #dbeeff;
  --text: #0f172a;
  --muted: #64748b;
  --border: #bfdbfe;
  --sidebar-bg: #0f172a;
  --sidebar-text: #f8fafc;
  --sidebar-muted: #94a3b8;
  --grad-blue: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  --shadow: 0 10px 30px rgba(37, 99, 235, 0.08);

  --font-heading: 'Outfit', sans-serif;
  --font-body: 'Inter', sans-serif;
}

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
}

body{
  font-family:'Inter', sans-serif;
  background:#eaf4ff;
  color:#0f172a;
  min-height:100vh;
  line-height:1.6;
  -webkit-font-smoothing:antialiased;
}

/* LAYOUT */
.layout{
  display:flex;
  min-height:100vh;
}

/* SIDEBAR */
.sidebar{
  width:280px;
  background:#0f172a;
  position:fixed;
  left:0;
  top:0;
  height:100vh;
  z-index:1000;
  display:flex;
  flex-direction:column;
  border-right:1px solid rgba(255,255,255,.06);
  transition:.3s ease;
}

.sidebar-logo{
  padding:24px 0;
  display:flex;
  justify-content:center;
  align-items:center;
  border-bottom:1px solid rgba(255,255,255,.05);
}

.sidebar-profile{
  padding:24px 32px;
  display:flex;
  flex-direction:column;
  align-items:center;
  text-align:center;
  border-bottom:1px solid rgba(255,255,255,.05);
}

.sidebar-avatar{
  width:74px;
  height:74px;
  border-radius:22px;
  overflow:hidden;
  background:rgba(255,255,255,.08);
  display:flex;
  align-items:center;
  justify-content:center;
  color:white;
  font-size:28px;
  border:2px solid rgba(255,255,255,.08);
}

.sidebar-avatar img{
  width:100%;
  height:100%;
  object-fit:cover;
}

.sidebar-user-name{
  color:white;
  font-size:18px;
  font-weight:800;
}

.sidebar-user-role{
  color:#60a5fa;
  font-size:12px;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:1px;
}

.sidebar-nav{
  padding:24px 16px;
  overflow-y:auto;
  flex:1;
}

.nav-label{
  color:#94a3b8;
  font-size:11px;
  font-weight:700;
  margin:20px 16px 8px;
  text-transform:uppercase;
  letter-spacing:1px;
}

.nav-item{
  display:flex;
  align-items:center;
  gap:12px;
  padding:14px 16px;
  border-radius:18px;
  text-decoration:none;
  color:#94a3b8;
  font-size:15px;
  font-weight:600;
  transition:.25s ease;
  margin-bottom:6px;
}

.nav-item:hover{
  background:rgba(255,255,255,.06);
  color:white;
  transform:translateX(4px);
}

.nav-item.active{
  background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color:white;
  box-shadow:0 10px 20px rgba(37,99,235,.3);
}

/* MAIN */
.main{
  flex:1;
  margin-left:280px;
  display:flex;
  flex-direction:column;
  min-height:100vh;
}

/* TOPBAR */
.topbar{
  height:82px;
  background:rgba(255,255,255,.85);
  backdrop-filter:blur(12px);
  border-bottom:1px solid #bfdbfe;
  padding:0 32px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  position:sticky;
  top:0;
  z-index:999;
}

.topbar-title h2{
  font-family:'Outfit', sans-serif;
  font-size:24px;
  font-weight:800;
  color:#0f172a;
  letter-spacing:-0.5px;
}

/* CONTENT */
.content{
  padding:32px;
}

/* ORDER LIST */
.history-list{
  display:grid;
  gap:24px;
}

/* CARD */
.order-card{
  background:#ffffff;
  border-radius:28px;
  border:1px solid #bfdbfe;
  padding:28px;
  box-shadow:0 10px 30px rgba(37, 99, 235, 0.08);
  transition:.3s ease;
}

.order-card:hover{
  transform:translateY(-4px);
  box-shadow:0 20px 40px rgba(37,99,235,.12);
}

/* HEADER */
.order-header{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:20px;
  margin-bottom:24px;
  padding-bottom:20px;
  border-bottom:1px solid #e2e8f0;
}

.order-id{
  font-family:'Outfit', sans-serif;
  font-size:22px;
  font-weight:800;
  color:#0f172a;
}

.order-time{
  margin-top:6px;
  font-size:14px;
  color:#64748b;
  font-weight:600;
}

/* PRODUCT */
.order-products{
  display:flex;
  flex-direction:column;
  gap:18px;
  margin-bottom:24px;
}

.product-item{
  display:flex;
  align-items:center;
  gap:16px;
  background:#f8fbff;
  border:1px solid #dbeafe;
  border-radius:20px;
  padding:14px;
}

.product-thumb{
  width:72px;
  height:72px;
  border-radius:18px;
  overflow:hidden;
  background:white;
  border:1px solid #dbeafe;
  flex-shrink:0;
  display:flex;
  align-items:center;
  justify-content:center;
}

.product-thumb img{
  width:80%;
  height:80%;
  object-fit:contain;
}

.product-info{
  flex:1;
  min-width:0;
}

.product-name{
  font-size:16px;
  font-weight:700;
  color:#0f172a;
  margin-bottom:4px;
}

.product-qty{
  font-size:13px;
  color:#64748b;
  font-weight:600;
}

.product-price{
  text-align:right;
}

.item-subtotal{
  font-size:16px;
  font-weight:800;
  color:#1d4ed8;
}

/* FOOTER */
.card-footer{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:20px;
  flex-wrap:wrap;
  padding-top:20px;
  border-top:1px solid #e2e8f0;
}

.footer-metrics{
  display:flex;
  gap:28px;
  flex-wrap:wrap;
}

.metric{
  display:flex;
  flex-direction:column;
  gap:4px;
}

.metric-label{
  font-size:11px;
  text-transform:uppercase;
  letter-spacing:1px;
  font-weight:700;
  color:#94a3b8;
}

.metric-val{
  font-size:15px;
  font-weight:700;
  color:#0f172a;
}

/* BUTTON */
.btn{
  border:none;
  text-decoration:none;
  cursor:pointer;
  padding:12px 22px;
  border-radius:14px;
  font-size:14px;
  font-weight:700;
  transition:.25s ease;
  display:inline-flex;
  align-items:center;
  gap:10px;
}

.btn-detail{
  background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color:white;
  box-shadow:0 8px 18px rgba(37,99,235,.25);
}

.btn-detail:hover{
  transform:translateY(-2px);
  box-shadow:0 14px 28px rgba(37,99,235,.3);
}

.btn-komplain{
  background:#fff1f2;
  color:#e11d48;
  border:1px solid #fecdd3;
}

.btn-komplain:hover{
  background:#ffe4e6;
}

/* EMPTY */
.empty-state{
  background:#ffffff;
  border:2px dashed #bfdbfe;
  border-radius:32px;
  padding:80px 30px;
  text-align:center;
}

.empty-icon{
  font-size:70px;
  color:#bfdbfe;
  margin-bottom:24px;
}

.empty-text{
  font-size:16px;
  color:#64748b;
  margin-bottom:28px;
  font-weight:600;
}

/* HAMBURGER */
.hamburger{
  display:none;
}

/* MOBILE */
@media(max-width:1024px){

  .sidebar{
    transform:translateX(-100%);
    box-shadow:20px 0 40px rgba(0,0,0,.15);
  }

  .sidebar.active{
    transform:translateX(0);
  }

  .main{
    margin-left:0;
  }

  .topbar{
    padding:0 18px;
  }

  .content{
    padding:20px;
  }

  .order-card{
    padding:22px;
    border-radius:24px;
  }

  .order-header{
    flex-direction:column;
    align-items:flex-start;
  }

  .card-footer{
    flex-direction:column;
    align-items:flex-start;
  }

  .footer-metrics{
    width:100%;
    flex-direction:column;
    gap:16px;
  }

  .product-item{
    align-items:flex-start;
  }

  .product-price{
    margin-left:auto;
  }

  .hamburger{
    display:flex;
    width:42px;
    height:42px;
    border-radius:12px;
    align-items:center;
    justify-content:center;
    background:#dbeafe;
    color:#2563eb;
    font-size:18px;
    cursor:pointer;
  }

  .sidebar-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.45);
    backdrop-filter:blur(4px);
    z-index:900;
  }

  .sidebar-overlay.active{
    display:block;
  }
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
                <div class="topbar-title"><h2>Riwayat Pesanan</h2></div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="text-align: right;">
                    <div style="font-weight: 800; font-size: 14px; color: #0f172a;"><?= htmlspecialchars($user['nama']) ?></div>
                    <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Member <?= ucfirst($user['role_customer']) ?></div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; overflow: hidden; flex-shrink: 0;">
                    <?php if (!empty($user['foto'])): ?>
                        <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 12px; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="history-list">
                <?php if (empty($pesananList)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt empty-icon"></i>
                        <p class="empty-text">Anda belum memiliki riwayat pesanan yang aktif.</p>
                        <a href="pesan.php" class="btn" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; display: inline-flex; box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.3);">MULAI BELANJA</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($pesananList as $p): 
                        $prods = $allDetails[$p['id']] ?? [];
                    ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <h3 class="order-id">TRANSAKSI #<?= str_pad($p['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                    <div class="order-time">
                                        <i class="far fa-calendar-alt" style="font-size: 12px; margin-right: 4px;"></i>
                                        <?= formatTanggalIndo($p['created_at']) ?> WIB
                                    </div>
                                </div>
                                <?= getStatusBadge($p['status']) ?>
                            </div>

                            <!-- Product List Section -->
                            <div class="order-products">
                                <?php foreach ($prods as $pr): ?>
                                    <div class="product-item">
                                        <div class="product-thumb">
                                            <?php if ($pr['foto']): ?>
                                                <img src="<?= getUploadUrl($pr['foto'], 'produk') ?>" alt="<?= htmlspecialchars($pr['produk_nama']) ?>" onerror="this.src='../assets/images/no-image.png'">
                                            <?php else: ?>
                                                <i class="fas fa-bottle-water" style="color: #cbd5e1;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-info">
                                            <div class="product-name"><?= htmlspecialchars($pr['produk_nama']) ?></div>
                                            <div class="product-qty">x<?= $pr['jumlah'] ?> <?= strtoupper($pr['satuan']) ?></div>
                                        </div>
                                        <div class="product-price">
                                            <span class="item-subtotal"><?= formatRupiah($pr['subtotal']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="card-footer">
                                <div class="footer-metrics">
                                    <div class="metric">
                                        <span class="metric-label">Jumlah Pembayaran</span>
                                        <span class="metric-val" style="color: #2563eb; font-size: 20px;"><?= formatRupiah($p['total']) ?></span>
                                    </div>
                                    <div class="metric">
                                        <span class="metric-label">Metode Pembayaran</span>
                                        <span class="metric-val" style="font-size: 13px;"><?= strtoupper($p['metode_bayar']) ?></span>
                                    </div>
                                    <?php if ($p['sopir_nama']): ?>
                                    <div class="metric">
                                        <span class="metric-label">Sopir</span>
                                        <span class="metric-val" style="font-size: 13px;"><i class="fas fa-id-badge" style="color:#2563eb; margin-right:4px;"></i> <?= htmlspecialchars($p['sopir_nama']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="display: flex; gap: 8px;">
                                        <a href="transaksi_pesanan.php?id=<?= $p['id'] ?>" class="btn btn-detail" style="padding: 10px 20px;">
                                            Detail Pesanan <i class="fas fa-arrow-right-long" style="font-size: 12px;"></i>
                                        </a>
                                        <?php if (in_array($p['status'], ['selesai', 'dikirim'])): ?>
                                            <a href="komplain.php?pesanan_id=<?= $p['id'] ?>" class="btn" style="background: #fff1f2; color: #e11d48; border: 1px solid #ffe4e6; padding: 10px 16px;"><i class="fas fa-circle-exclamation"></i> Ajukan Komplain</a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($p['rating']): ?>
                                        <div style="display: flex; gap: 4px; background: #fffbeb; padding: 10px 16px; border-radius: 14px; border: 1px solid #fef3c7;">
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <i class="fas fa-star" style="color: <?= $i <= $p['rating'] ? '#f59e0b' : '#e2e8f0' ?>; font-size: 14px;"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
