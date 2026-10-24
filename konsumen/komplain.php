<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('konsumen');
$user = getCurrentUser($pdo);

$error = '';
$success = '';

// Proses form komplain baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_komplain'])) {
    $pesanan_id = !empty($_POST['pesanan_id']) ? (int)$_POST['pesanan_id'] : null;
    $tipe = trim($_POST['tipe']);
    $deskripsi = trim($_POST['deskripsi']);
    
    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $foto = uploadFile($_FILES['foto'], 'komplain');
    }
    
    if (empty($tipe) || empty($deskripsi)) {
        $error = 'Tipe dan deskripsi pengaduan wajib diisi!';
    } else {
        // Validasi status pesanan jika pesanan_id dipilih
        if ($pesanan_id) {
            $stmtCek = $pdo->prepare("SELECT status FROM pesanan WHERE id = ? AND user_id = ?");
            $stmtCek->execute([$pesanan_id, getUserId()]);
            $orderStatus = $stmtCek->fetchColumn();
            
            if ($orderStatus === 'dikirim' && $tipe !== 'keterlambatan') {
                $error = 'Untuk pesanan yang sedang dikirim, Anda hanya dapat mengajukan komplain kategori "Delay Pengiriman".';
            } elseif ($orderStatus === 'selesai' && $tipe === 'keterlambatan') {
                $error = 'Pesanan sudah sampai (Selesai), kategori "Delay Pengiriman" tidak lagi berlaku.';
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("INSERT INTO komplain (user_id, pesanan_id, tipe, deskripsi, foto, status) VALUES (?,?,?,?,?,?)");
            if ($stmt->execute([getUserId(), $pesanan_id, $tipe, $deskripsi, $foto, 'menunggu'])) {
                $success = 'Laporan Anda Berhasil Terkirim! Tim kami akan segera merespons.';
            } else {
                $error = 'Gagal mengirim laporan. Silakan coba lagi.';
            }
        }
    }
}

// Proses konfirmasi terima barang pengganti
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_terima'])) {
    $kid = (int)$_POST['komplain_id'];
    
    // Ambil tipe komplain untuk pesan sukses yang sesuai
    $stmtTipe = $pdo->prepare("SELECT tipe FROM komplain WHERE id = ? AND user_id = ?");
    $stmtTipe->execute([$kid, getUserId()]);
    $tipeKomplain = $stmtTipe->fetchColumn();

    $pdo->prepare("UPDATE komplain SET konfirmasi_konsumen = 1 WHERE id = ? AND user_id = ?")->execute([$kid, getUserId()]);
    
    if ($tipeKomplain === 'keterlambatan') {
        $success = 'Terima kasih! Konfirmasi penyelesaian kendala pengiriman telah diterima.';
    } else {
        $success = 'Terima kasih! Penerimaan barang pengganti telah dikonfirmasi.';
    }
}


// Ambil riwayat komplain dengan nama sopir
$stmtK = $pdo->prepare("SELECT k.*, p.created_at as tgl_pesan, s.nama as nama_sopir FROM komplain k LEFT JOIN pesanan p ON k.pesanan_id = p.id LEFT JOIN users s ON k.sopir_id = s.id WHERE k.user_id = ? ORDER BY k.created_at DESC");
$stmtK->execute([getUserId()]);
$komplainList = $stmtK->fetchAll();

// Ambil daftar pesanan (selesai & ongoing) untuk dropdown
$stmtP = $pdo->prepare("SELECT id, created_at, total, status FROM pesanan WHERE user_id = ? AND status != 'menunggu' ORDER BY created_at DESC LIMIT 15");
$stmtP->execute([getUserId()]);
$pesananList = $stmtP->fetchAll();

// Jika ada pesanan_id dari URL, ambil untuk pre-select
$preSelectedId = isset($_GET['pesanan_id']) ? (int)$_GET['pesanan_id'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
     :root{
    --primary:#2563eb;
    --primary-dark:#1d4ed8;
    --bg:#f1f7fb;
    --surface:#ffffff;
    --surface-soft:#f8fbff;
    --text:#0f172a;
    --muted:#64748b;
    --border:#dbeafe;
    --sidebar-bg:#0f172a;
    --sidebar-text:#f8fafc;
    --sidebar-muted:#94a3b8;
    --success:#16a34a;
    --danger:#e11d48;
    --warning:#f59e0b;

    --shadow-sm:0 2px 10px rgba(15,23,42,.04);
    --shadow-md:0 10px 30px rgba(15,23,42,.08);
    --shadow-lg:0 20px 45px rgba(37,99,235,.12);

    --radius:24px;
    --radius-lg:32px;

    --grad-blue:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
    --font-heading:'Outfit',sans-serif;
    --font-body:'Inter',sans-serif;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{
    font-family:'Inter',sans-serif;
    background:#f1f7fb;
    color:#0f172a;
    min-height:100vh;
    overflow-x:hidden;
    -webkit-font-smoothing:antialiased;
    line-height:1.6;
}

/* =========================
   LAYOUT
========================= */

.layout{
    display:flex;
    min-height:100vh;
}

/* =========================
   SIDEBAR
========================= */

.sidebar{
    width:280px;
    background:#0f172a;
    border-right:1px solid rgba(255,255,255,.05);
    display:flex;
    flex-direction:column;
    position:fixed;
    left:0;
    top:0;
    height:100vh;
    z-index:1000;
    transition:.3s ease;
}

.sidebar-logo{
    padding:26px 0;
    border-bottom:1px solid rgba(255,255,255,.06);
    display:flex;
    justify-content:center;
    align-items:center;
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
    font-size:18px;
    font-weight:800;
    color:white;
}

.sidebar-user-role{
    font-size:12px;
    font-weight:700;
    color:#38bdf8;
    text-transform:uppercase;
    letter-spacing:1px;
}

.sidebar-nav{
    padding:24px 16px;
    flex:1;
    overflow-y:auto;
}

.nav-label{
    font-size:11px;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:.12em;
    margin:20px 16px 8px;
    font-weight:800;
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
    font-weight:700;
    transition:.25s ease;
    margin-bottom:6px;
}

.nav-item:hover{
    background:rgba(255,255,255,.06);
    color:white;
    transform:translateX(4px);
}

.nav-item.active{
    background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
    color:white;
    box-shadow:0 10px 20px rgba(37,99,235,.3);
}

/* =========================
   MAIN
========================= */

.main{
    flex:1;
    margin-left:280px;
    display:flex;
    flex-direction:column;
    min-height:100vh;
}

/* =========================
   TOPBAR
========================= */

.topbar{
    height:84px;
    padding:0 36px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    background:rgba(255,255,255,.9);
    backdrop-filter:blur(10px);
    border-bottom:1px solid #dbeafe;
    position:sticky;
    top:0;
    z-index:999;
}

.topbar-title h2{
    font-family:'Outfit',sans-serif;
    font-size:24px;
    font-weight:800;
    color:#0f172a;
    letter-spacing:-.5px;
}

.user-pill{
    display:flex;
    align-items:center;
    gap:12px;
    padding:8px 16px;
    background:white;
    border:1px solid #dbeafe;
    border-radius:50px;
    box-shadow:0 2px 10px rgba(15,23,42,.04);
}

.avatar{
    width:40px;
    height:40px;
    border-radius:12px;
    overflow:hidden;
    background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:14px;
    font-weight:800;
}

.avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

/* =========================
   CONTENT
========================= */

.content{
    padding:36px;
    display:grid;
    grid-template-columns:420px 1fr;
    gap:30px;
    align-items:start;
}

/* =========================
   CARD
========================= */

.section-card{
    background:#ffffff;
    border-radius:32px;
    padding:32px;
    border:1px solid #dbeafe;
    box-shadow:0 10px 30px rgba(15,23,42,.08);
}

.section-title{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:28px;
    font-size:22px;
    font-weight:800;
    font-family:'Outfit',sans-serif;
    color:#0f172a;
    letter-spacing:-.5px;
}

/* =========================
   FORM
========================= */

.field-group{
    margin-bottom:22px;
}

.field-label,
.label{
    display:block;
    margin-bottom:10px;
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:1px;
    color:#64748b;
}

.input{
    width:100%;
    border:1.5px solid #dbeafe;
    background:#f8fbff;
    border-radius:18px;
    padding:15px 18px;
    font-size:15px;
    font-weight:600;
    color:#0f172a;
    font-family:inherit;
    transition:.25s ease;
}

.input:focus{
    outline:none;
    border-color:#2563eb;
    background:white;
    box-shadow:0 0 0 5px rgba(37,99,235,.08);
}

textarea.input{
    min-height:140px;
    resize:none;
}

input[type="file"]{
    padding:14px;
    background:white;
}

.btn-submit{
    width:100%;
    border:none;
    background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);
    color:white;
    border-radius:18px;
    padding:16px;
    font-size:15px;
    font-weight:800;
    font-family:'Outfit',sans-serif;
    cursor:pointer;
    transition:.3s ease;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    box-shadow:0 15px 25px rgba(37,99,235,.25);
}

.btn-submit:hover{
    transform:translateY(-3px);
    box-shadow:0 20px 35px rgba(37,99,235,.35);
}

/* =========================
   ALERT
========================= */

.alert{
    grid-column:span 2;
    padding:18px 22px;
    border-radius:20px;
    display:flex;
    align-items:center;
    gap:12px;
    font-size:14px;
    font-weight:700;
    animation:fadeUp .4s ease;
}

.alert-success{
    background:#f0fdf4;
    color:#166534;
    border:1px solid #bbf7d0;
}

.alert-danger{
    background:#fff1f2;
    color:#be123c;
    border:1px solid #fecdd3;
}

/* =========================
   KOMPLAIN CARD
========================= */

.komplain-list{
    display:grid;
    gap:24px;
}

.komplain-card{
    background:#ffffff;
    border-radius:28px;
    padding:28px;
    border:1px solid #dbeafe;
    box-shadow:0 10px 30px rgba(15,23,42,.08);
    transition:.3s ease;
}

.komplain-card:hover{
    transform:translateY(-4px);
    box-shadow:0 20px 45px rgba(37,99,235,.12);
}

.komplain-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-bottom:20px;
}

/* =========================
   BADGE
========================= */

.badge{
    padding:8px 14px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.5px;
    border:1px solid transparent;
}

.badge-menunggu{
    background:#fffbeb;
    color:#b45309;
    border-color:#fde68a;
}

.badge-diproses{
    background:#eff6ff;
    color:#1d4ed8;
    border-color:#bfdbfe;
}

.badge-terjadwal_penggantian{
    background:#fef3c7;
    color:#92400e;
    border-color:#fde68a;
}

.badge-selesai{
    background:#f0fdf4;
    color:#15803d;
    border-color:#bbf7d0;
}

.badge-ditolak{
    background:#fef2f2;
    color:#991b1b;
    border-color:#fecaca;
}

/* =========================
   PROGRESS TRACKER
========================= */

.progress-tracker{
    display:flex;
    align-items:flex-start;
    margin-bottom:26px;
}

.progress-step{
    display:flex;
    flex-direction:column;
    align-items:center;
    flex:1;
    position:relative;
    z-index:1;
}

.progress-dot{
    width:34px;
    height:34px;
    border-radius:50%;
    background:#e2e8f0;
    color:#94a3b8;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:800;
    border:2px solid #e2e8f0;
    transition:.3s ease;
}

.progress-dot.done{
    background:#2563eb;
    border-color:#2563eb;
    color:white;
}

.progress-dot.done-green{
    background:#16a34a;
    border-color:#16a34a;
    color:white;
}

.progress-dot.active{
    background:white;
    border-color:#2563eb;
    color:#2563eb;
    box-shadow:0 0 0 5px rgba(37,99,235,.1);
}

.progress-label{
    margin-top:8px;
    font-size:12px;
    font-weight:700;
    color:#94a3b8;
    text-align:center;
}

.progress-label.active{
    color:#2563eb;
}

.progress-label.done-green{
    color:#16a34a;
}

.progress-line{
    flex:1;
    height:3px;
    background:#e2e8f0;
    margin:15px -8px 0;
    z-index:0;
}

.progress-line.done{
    background:#2563eb;
}

/* =========================
   EMPTY STATE
========================= */

.empty-state{
    text-align:center;
    padding:90px 20px;
    border:2px dashed #cbd5e1;
    border-radius:30px;
    background:rgba(255,255,255,.5);
}

.empty-state i{
    font-size:52px;
    color:#cbd5e1;
    margin-bottom:20px;
}

.empty-state p{
    font-size:15px;
    font-weight:700;
    color:#94a3b8;
}

/* =========================
   ANIMATION
========================= */

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(12px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

.animate-up{
    animation:fadeUp .4s ease;
}

/* =========================
   HAMBURGER
========================= */

.hamburger{
    display:none;
}

/* =========================
   OVERLAY
========================= */

.sidebar-overlay{
    display:none;
}

/* =========================
   RESPONSIVE TABLET
========================= */

@media (max-width:1024px){

    .sidebar{
        transform:translateX(-100%);
        z-index:1200;
        box-shadow:20px 0 40px rgba(0,0,0,.2);
    }

    .sidebar.active{
        transform:translateX(0);
    }

    .main{
        margin-left:0;
    }

    .content{
        grid-template-columns:1fr;
        padding:24px 18px;
    }

    .alert{
        grid-column:span 1;
    }

    .topbar{
        padding:0 18px;
    }

    .section-card{
        padding:26px 22px;
        border-radius:28px;
    }

    .hamburger{
        display:flex;
        width:42px;
        height:42px;
        border-radius:14px;
        align-items:center;
        justify-content:center;
        background:white;
        border:1px solid #dbeafe;
        box-shadow:0 2px 10px rgba(15,23,42,.04);
        cursor:pointer;
        font-size:18px;
    }

    .sidebar-overlay{
        position:fixed;
        inset:0;
        background:rgba(15,23,42,.45);
        backdrop-filter:blur(3px);
        z-index:1100;
    }

    .sidebar-overlay.active{
        display:block;
    }
}

/* =========================
   RESPONSIVE MOBILE
========================= */

@media (max-width:768px){

    .topbar{
        height:auto;
        padding:18px;
    }

    .topbar-title h2{
        font-size:20px;
    }

    .user-pill{
        display:none;
    }

    .content{
        padding:18px 14px;
        gap:20px;
    }

    .section-card{
        padding:22px 18px;
        border-radius:24px;
    }

    .section-title{
        font-size:18px;
        margin-bottom:22px;
    }

    .komplain-card{
        padding:20px;
        border-radius:24px;
    }

    .progress-label{
        font-size:10px;
    }

    .progress-dot{
        width:28px;
        height:28px;
        font-size:10px;
    }

    .progress-line{
        margin-top:12px;
    }

    .btn-submit{
        font-size:14px;
        padding:14px;
    }

    .input{
        font-size:14px;
        padding:14px 16px;
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
            <a href="pesanan.php" class="nav-item "><i class="fas fa-file-invoice nav-icon"></i> Riwayat Pesanan</a>
            <a href="komplain.php" class="nav-item active"><i class="fas fa-headset nav-icon"></i> Pusat Bantuan</a>
            
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
                <div class="topbar-title"><h2>Pusat Bantuan & Komplain</h2></div>
            </div>
            <div class="user-pill">
                <div class="avatar">
                    <?php if (!empty($user['foto'])): ?>
                        <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 10px; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <span style="font-weight: 700; font-size: 13px;"><?= htmlspecialchars($user['nama']) ?></span>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?><div class="alert alert-success animate-up"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger animate-up"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

            <div class="form-section">
                <div class="section-card">
                    <h3 class="section-title"><i class="fas fa-paper-plane" style="color: #2563eb;"></i>Ajukan Komplain Pesanan</h3>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="field-group">
                            <label class="field-label">Pesanan yang diajukan Komplain</label>
                            <select name="pesanan_id" class="input">
                                <option value="">— Tidak Spesifik —</option>
                                <?php foreach ($pesananList as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-status="<?= $p['status'] ?>" <?= ($preSelectedId == $p['id']) ? 'selected' : '' ?>>
                                        INV #<?= str_pad($p['id'], 6, '0', STR_PAD_LEFT) ?> 
                                        (<?= date('d M Y', strtotime($p['created_at'])) ?>) 
                                        — <?= strtoupper($p['status']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Kategori Komplain</label>
                            <select name="tipe" class="input" required>
                                <option value="keterlambatan">Delay Pengiriman</option>
                                <option value="kebocoran">Produk Bocor</option>
                                <option value="salah_produk">Salah Mengirim Jenis Produk</option>
                                <option value="lainnya">Kendala Lainnya</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Uraian Detail Kendala</label>
                            <textarea name="deskripsi" class="input" style="height: 140px; resize: none;" placeholder="Ceritakan detail komplain Anda" required></textarea>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Bukti Komplain (Foto Masalah)</label>
                            <input type="file" name="foto" class="input" accept="image/*">
                        </div>
                        <button type="submit" name="kirim_komplain" class="btn-submit">KIRIM KOMPLAIN<i class="fas fa-chevron-right"></i></button>
                    </form>
                </div>
            </div>

            <div class="history-section">
                <h3 class="section-title"><i class="fas fa-clock-rotate-left"></i> Komplain Terkirim</h3>
                <?php if (empty($komplainList)): ?>
                    <div class="section-card" style="text-align: center; color: #cbd5e1; padding: 100px 0; border-style: dashed; background: transparent;">
                        <i class="fas fa-comment-slash" style="font-size: 48px; opacity: 0.2; margin-bottom: 24px;"></i>
                        <p style="font-weight: 700; font-size: 16px; font-family: 'Outfit',sans-serif; letter-spacing: -0.5px;">BELUM ADA RIWAYAT</p>
                    </div>
                <?php else: ?>
                    <div class="komplain-list">
                        <?php foreach ($komplainList as $k): ?>
                            <div class="komplain-card">
                                <!-- Progress Tracker -->
                                <div class="progress-tracker">
                                    <?php 
                                    $s = $k['status'];
                                    $steps = [
                                        ['id' => 'menunggu', 'label' => 'Terkirim', 'icon' => '1'],
                                        ['id' => 'diproses', 'label' => 'Ditinjau', 'icon' => '2'],
                                        ['id' => 'terjadwal_penggantian', 'label' => 'Dijadwalkan', 'icon' => '3'],
                                        ['id' => 'selesai', 'label' => 'Selesai', 'icon' => 'check']
                                    ];
                                    
                                    if ($s === 'ditolak') {
                                        $steps = [
                                            ['id' => 'menunggu', 'label' => 'Terkirim', 'icon' => '1'],
                                            ['id' => 'ditolak', 'label' => 'Ditolak', 'icon' => 'x']
                                        ];
                                    }

                                    $currentIdx = 0;
                                    foreach($steps as $idx => $step) if($step['id'] == $s) $currentIdx = $idx;
                                    
                                    foreach($steps as $idx => $step): 
                                        $isDone = $idx < $currentIdx || ($s === 'selesai' && $step['id'] === 'selesai');
                                        $isActive = $idx === $currentIdx && $s !== 'selesai';
                                        $dotClass = $isDone ? ($step['id'] === 'selesai' ? 'done-green' : 'done') : ($isActive ? 'active' : '');
                                        $labelClass = $isActive ? 'active' : ($isDone && $step['id'] === 'selesai' ? 'done-green' : '');
                                    ?>
                                        <div class="progress-step">
                                            <div class="progress-dot <?= $dotClass ?>">
                                                <i class="fas fa-<?= $step['icon'] === 'check' ? 'check' : ($step['icon'] === 'x' ? 'xmark' : '') ?>"></i>
                                                <?= is_numeric($step['icon']) ? $step['icon'] : '' ?>
                                            </div>
                                            <div class="progress-label <?= $labelClass ?>"><?= $step['label'] ?></div>
                                        </div>
                                        <?php if($idx < count($steps)-1): ?>
                                            <div class="progress-line <?= $idx < $currentIdx ? 'done' : '' ?>"></div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <div class="komplain-header">
                                    <span class="badge badge-<?= $k['status'] ?>"><?= str_replace('_', ' ', $k['status']) ?></span>
                                    <span style="font-size: 12px; color: #94a3b8; font-weight: 700;"><?= formatTanggalIndo($k['created_at']) ?></span>
                                </div>
                                <div style="font-family: 'Outfit',sans-serif; font-weight: 800; font-size: 18px; margin-bottom: 12px; color: #0f172a; letter-spacing: -0.5px;">
                                    <?= strtoupper($k['tipe'] === 'kebocoran' ? 'Insiden Kebocoran' : ($k['tipe'] === 'kekurangan' ? 'Item Berkurang' : ($k['tipe'] === 'salah_produk' ? 'Varian Tertukar' : ($k['tipe'] === 'keterlambatan' ? 'Delay Pengiriman' : 'Laporan Umum')))) ?>
                                </div>
                                <p style="font-size: 14px; color: #64748b; line-height: 1.7;"><?= nl2br(htmlspecialchars($k['deskripsi'])) ?></p>
                                
                                <?php if ($k['balasan']): ?>
                                <div style="margin-top: 16px; padding: 16px 18px; background: #f0fdf4; border-radius: 16px; border: 1px solid #bbf7d0;">
                                    <div style="font-size: 10px; font-weight: 800; color: #15803d; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;"><i class="fas fa-reply"></i> Balasan Admin</div>
                                    <p style="font-size: 14px; color: #166534; font-weight: 600; line-height: 1.6;"><?= nl2br(htmlspecialchars($k['balasan'])) ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if ($k['tanggal_penggantian'] || $k['status'] === 'selesai'): ?>
                                <div style="margin-top: 12px; padding: 14px 18px; background: #eff6ff; border-radius: 16px; border: 1px solid #dbeafe; display: flex; align-items: center; gap: 12px;">
                                    <i class="fas fa-<?= $k['tanggal_penggantian'] ? 'truck-fast' : 'clipboard-check' ?>" style="color: #3b82f6; font-size: 18px;"></i>
                                    <div>
                                        <div style="font-size: 10px; font-weight: 800; color: #1d4ed8; text-transform: uppercase; letter-spacing: 1px;">
                                            <?= $k['status'] === 'selesai' ? ($k['tanggal_penggantian'] ? 'Penggantian Selesai' : 'Laporan Selesai') : 'Jadwal Penggantian' ?>
                                        </div>
                                        <div style="font-size: 13px; font-weight: 700; color: #1e40af;">
                                            <?php if($k['status'] === 'selesai'): ?>
                                                <?php $tgl = $k['tanggal_penggantian'] ?: $k['updated_at']; ?>
                                                <?= formatTanggalIndo($tgl) ?> WIB<?= $k['nama_sopir'] ? ' · Sopir: ' . htmlspecialchars($k['nama_sopir']) : '' ?>
                                            <?php else: ?>
                                                Jadwal: <?= formatTanggalIndo($k['tanggal_penggantian']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Konfirmasi Terima & Rating -->
                                <?php if ($k['status'] === 'selesai' && !$k['konfirmasi_konsumen']): ?>
                                    <form method="POST" style="margin-top: 16px;">
                                        <input type="hidden" name="komplain_id" value="<?= $k['id'] ?>">
                                        <button type="submit" name="konfirmasi_terima" class="btn-submit" style="padding: 12px; font-size: 13px; background: #16a34a;">
                                            <?= $k['tipe'] === 'keterlambatan' ? 'KONFIRMASI PENYELESAIAN KOMPLAIN' : 'KONFIRMASI TERIMA BARANG PENGGANTI' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                
                                <div style="display: flex; align-items: center; gap: 12px; margin-top: 24px;">
                                    <?php if ($k['pesanan_id']): ?>
                                        <div style="font-size: 11px; font-weight: 800; color: #2563eb; background: #eff6ff; color: #2563eb; padding: 6px 14px; border-radius: 8px;">
                                            <i class="fas fa-receipt"></i> REF: #<?= str_pad($k['pesanan_id'], 6, '0', STR_PAD_LEFT) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($k['foto']): ?>
                                        <a href="<?= getUploadUrl($k['foto'], 'komplain') ?>" target="_blank" style="font-size: 11px; font-weight: 800; color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                                            <i class="fas fa-image"></i> LIHAT FOTO BUKTI
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>

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
// Dynamic Complaint Type Filtering
const pesananSelect = document.querySelector('select[name="pesanan_id"]');
const tipeSelect = document.querySelector('select[name="tipe"]');

if (pesananSelect && tipeSelect) {
    function filterComplaintTypes() {
        const selectedOption = pesananSelect.options[pesananSelect.selectedIndex];
        const status = selectedOption.getAttribute('data-status');
        
        // Reset all options first
        Array.from(tipeSelect.options).forEach(opt => {
            opt.disabled = false;
            opt.style.display = 'block';
        });

        if (status === 'dikirim') {
            // ONLY Delay Pengiriman allowed
            Array.from(tipeSelect.options).forEach(opt => {
                if (opt.value !== 'keterlambatan') {
                    opt.disabled = true;
                    opt.style.display = 'none';
                }
            });
            tipeSelect.value = 'keterlambatan';
        } else if (status === 'selesai') {
            // ALL EXCEPT Delay Pengiriman allowed
            Array.from(tipeSelect.options).forEach(opt => {
                if (opt.value === 'keterlambatan') {
                    opt.disabled = true;
                    opt.style.display = 'none';
                }
            });
            // If current selection is delay, reset it
            if (tipeSelect.value === 'keterlambatan') {
                tipeSelect.value = '';
            }
        }
    }

    pesananSelect.addEventListener('change', filterComplaintTypes);
    // Initialize on page load
    window.addEventListener('DOMContentLoaded', filterComplaintTypes);
}
</script>
</body>
</html>
