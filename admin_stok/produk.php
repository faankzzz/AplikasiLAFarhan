<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_barang');
$user = getCurrentUser($pdo);

$error = '';
$success = '';

// Hapus produk
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM produk WHERE id = ?")->execute([$id]);
    $success = 'Produk berhasil dihapus!';
}

// Simpan produk (tambah/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga_toko = (float)$_POST['harga_toko'];
    $harga_umum = (float)$_POST['harga_umum'];
    $stok = (int)($_POST['stok'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? 'pcs');
    
    
    $foto = null; $foto2 = null; $foto3 = null;
    if (!empty($_FILES['foto']['name'])) { $foto = uploadFile($_FILES['foto'], 'produk'); }
    if (!empty($_FILES['foto2']['name'])) { $foto2 = uploadFile($_FILES['foto2'], 'produk'); }
    if (!empty($_FILES['foto3']['name'])) { $foto3 = uploadFile($_FILES['foto3'], 'produk'); }

    if (empty($nama) || $harga_toko <= 0 || $harga_umum <= 0) {
        $error = 'Nama dan harga wajib diisi!';
    } else {
        // Stok dengan batas maksimal 1000 (yang penting tidak negatif)
        if ($stok < 0) {
            $error = 'Stok tidak boleh negatif!';
        } else {
            if ($id) {

                // Update
                $sql = "UPDATE produk SET nama=?, deskripsi=?, harga_toko=?, harga_umum=?, stok=?, satuan=?";
                $params = [$nama, $deskripsi, $harga_toko, $harga_umum, $stok, $satuan];
                if ($foto) { $sql .= ", foto=?"; $params[] = $foto; }
                if ($foto2) { $sql .= ", foto2=?"; $params[] = $foto2; }
                if ($foto3) { $sql .= ", foto3=?"; $params[] = $foto3; }
                $sql .= " WHERE id=?";
                $params[] = $id;
                $pdo->prepare($sql)->execute($params);
                $success = 'Produk berhasil diperbarui!';
            } else {
                // Insert
                $pdo->prepare("INSERT INTO produk (nama, deskripsi, harga_toko, harga_umum, stok, satuan, foto, foto2, foto3) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$nama, $deskripsi, $harga_toko, $harga_umum, $stok, $satuan, $foto, $foto2, $foto3]);
                $success = 'Produk berhasil ditambahkan!';
            }
        }
    }
}

$produkList = $pdo->query("SELECT * FROM produk ORDER BY nama")->fetchAll();

// Edit mode
$editProduk = null;
if (isset($_GET['edit'])) {
    $stmtE = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
    $stmtE->execute([(int)$_GET['edit']]);
    $editProduk = $stmtE->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ==========================================================================
   RESET, UTILITIES, & GLOBAL MOBILE FIX
   ========================================================================== */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body, *, *:focus { 
  outline: none !important; 
  -webkit-tap-highlight-color: transparent !important; /* Hilangkan highlight kotak biru di mobile */
}

/* VARIABEL TEMA: PUTIH KEBIRUAN (SOFT BLUE CLEAN) */
:root {
  --primary: #2563eb;          /* Biru Utama Utama */
  --primary-dark: #1d4ed8;     /* Biru Gelap */
  --secondary: #38bdf8;        /* Sky Blue */
  --accent: #0ea5e9;           /* Biru Aksen */

  --bg: #f0f6fa;               /* Latar Belakang Putih Kebiruan Sangat Lembut */
  --surface: #ffffff;          /* Permukaan Putih Bersih */
  --surface-light: #f8fafc;    /* Putih Abu Sangat Terang */

  --text: #0f172a;             /* Teks Utama Gelap */
  --muted: #64748b;            /* Teks Redup/Keterangan */

  --border: rgba(59, 130, 246, 0.12); /* Border Biru Transparan Tipis */

  --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); /* Sidebar Elegan Gelap kontras */
  --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);  /* Gradasi Tombol/Aktif */

  --shadow: 0 10px 30px rgba(37, 99, 235, 0.04);       /* Shadow Lembut */
  --shadow-hover: 0 20px 40px rgba(37, 99, 235, 0.08); /* Shadow Interaktif */

  --font-heading: 'Outfit', sans-serif;
  --font-body: 'Inter', sans-serif;
}

body {
  font-family: 'Inter', sans-serif;
  background: 
    radial-gradient(circle at top left, rgba(56, 189, 248, 0.15), transparent 35%),
    radial-gradient(circle at bottom right, rgba(37, 99, 235, 0.08), transparent 40%),
    #f0f6fa;
  color: #0f172a;
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
  line-height: 1.6;
}

/* LAYOUT */
.layout {
  display: flex;
  min-height: 100vh;
}

/* SIDEBAR */
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

/* MAIN CONTENT CONTAINER */
.main {
  flex: 1;
  margin-left: 280px;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* TOPBAR */
.topbar {
  height: 85px;
  padding: 0 40px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
  backdrop-filter: blur(12px);
  border-bottom: 2px solid rgba(56, 189, 248, 0.4);
  position: sticky;
  top: 0;
  z-index: 10;
}

.topbar-title h2 {
  font-family: 'Outfit', sans-serif;
  font-size: 22px;
  font-weight: 800;
  color: #1e40af;
  letter-spacing: -0.5px;
}

.topbar-info {
  background: rgba(255, 255, 255, 0.5);
  padding: 10px 16px;
  border-radius: 16px;
  border: 1px solid rgba(59, 130, 246, 0.12);
}

/* GRID & CONTENT */
.content {
  padding: 40px;
  width: 100%;
}

.grid {
  display: grid;
  grid-template-columns: 390px 1fr;
  gap: 32px;
}

/* CARD KANAN & KIRI (PUTIH BERSIH ELEGAN) */
.card {
  background: #ffffff;
  border-radius: 32px;
  padding: 36px;
  border: 1px solid rgba(59, 130, 246, 0.12);
  border-top: 3px solid #38bdf8;
  height: fit-content;
  box-shadow: 0 10px 30px rgba(37, 99, 235, 0.04);
  transition: transform .3s ease, box-shadow .3s ease;
}

.card:hover {
  box-shadow: 0 20px 40px rgba(37, 99, 235, 0.08);
}

.card-title {
  font-family: 'Outfit', sans-serif;
  font-size: 22px;
  font-weight: 800;
  margin-bottom: 28px;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 12px;
  letter-spacing: -0.5px;
}

.card-title i {
  color: #2563eb;
}

/* FORM ELEMENTS */
.form-group {
  margin-bottom: 20px;
}

.label {
  font-size: 11px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 8px;
  display: block;
}

.input {
  width: 100%;
  padding: 14px 18px;
  border-radius: 16px;
  border: 1px solid rgba(56, 189, 248, 0.4);
  background: #f8fafc;
  font-family: inherit;
  font-size: 15px;
  font-weight: 600;
  color: #0f172a;
  transition: .25s ease;
}

.input:focus {
  border-color: #2563eb;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
}

textarea.input {
  font-weight: 500;
}

/* BUTTONS */
.btn {
  padding: 15px 24px;
  border-radius: 16px;
  font-family: 'Outfit', sans-serif;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: .3s ease;
  border: none;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.btn-primary {
  background: #2563eb;
  color: white;
  width: 100%;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.15);
}

.btn-primary:hover {
  background: #1d4ed8; transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
}

.btn-outline {
  background: #f8fafc;
  border: 1px solid rgba(56, 189, 248, 0.4);
  color: #64748b;
}

.btn-outline:hover {
  border-color: #2563eb;
  color: #2563eb;
  background: rgba(37, 99, 235, 0.02);
}

/* TABLE CONTAINER BOX */
.table-box {
  background: #ffffff;
  border-radius: 32px;
  border: 1px solid rgba(59, 130, 246, 0.12);
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(37, 99, 235, 0.04);
}

.table-box:hover {
  box-shadow: 0 20px 40px rgba(37, 99, 235, 0.08);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  text-align: left;
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
  padding: 20px 24px;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(59, 130, 246, 0.12);
  letter-spacing: 0.8px;
  background: #f8fafc;
}

.table td {
  padding: 20px 24px;
  border-bottom: 1px solid rgba(59, 130, 246, 0.12);
  font-size: 16px;
  color: #0f172a;
}

.table tbody tr {
  transition: background 0.2s ease;
}

.table tbody tr:hover {
  background: rgba(37, 99, 235, 0.01);
}

/* PRODUCT ASSETS ROW */
.prod-img {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  object-fit: contain;
  background: #f8fafc;
  padding: 4px;
  border: 1px solid rgba(59, 130, 246, 0.08);
}

.price-tag {
  display: block;
  font-weight: 800;
  color: #2563eb;
  font-size: 16px;
}

.stok-badge {
  padding: 6px 14px;
  border-radius: 10px;
  background: rgba(37, 99, 235, 0.06);
  color: #2563eb;
  font-weight: 800;
  font-size: 13px;
  display: inline-block;
}

/* NOTIFICATION SYSTEM */
.alert {
  padding: 16px 20px;
  border-radius: 18px;
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 32px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.alert-success {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.alert-danger {
  background: #fff1f2;
  color: #9f1239;
  border: 1px solid #fecdd3;
}

/* RESPONSIVE DESIGN (MOBILE ADAPTATION) */
@media (max-width: 1024px) {
  .sidebar { 
    transform: translateX(-100%); 
    z-index: 1000; 
    transition: transform .35s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 20px 0 40px rgba(15, 23, 42, 0.15);
  }
  
  .sidebar.active { 
    transform: translateX(0); 
  }
  
  .main { 
    margin-left: 0; 
  }
  
  .content { 
    padding: 20px; 
  }
  
  .topbar { 
    padding: 15px 20px; 
    height: auto; 
    min-height: 80px; 
    flex-direction: column; 
    align-items: flex-start; 
    justify-content: center; 
    gap: 10px; 
  }
  
  .topbar > div:first-child { 
    width: 100%; 
    justify-content: space-between !important; 
  }
  
  .topbar-info { 
    display: none; 
  }
  
  .grid { 
    grid-template-columns: 1fr; 
    gap: 24px; 
  }
  
  .card { 
    padding: 28px 20px; 
    border-radius: 24px; 
  }
  
  .table-box { 
    border-radius: 24px; 
  }
  
  /* RESPONSIVE TABLE TRANSFORMATION TO CARDS */
  .table thead { 
    display: none; 
  }
  
  .table tbody tr { 
    display: block; 
    background: #f8fafc; 
    border-radius: 18px; 
    margin: 16px;
    padding: 16px; 
    border: 1px solid rgba(59, 130, 246, 0.12);
  }
  
  .table td { 
    display: block; 
    width: 100%; 
    padding: 10px 0 !important; 
    border: none !important;
    text-align: left !important;
  }
  
  .table td:not(:last-child) { 
    border-bottom: 1px dashed rgba(59, 130, 246, 0.1) !important; 
    margin-bottom: 8px; 
  }
  
  .table td::before {
    content: attr(data-label);
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 4px;
    letter-spacing: 0.5px;
  }

  /* SIDEBAR OVERLAY GLASS */
  .sidebar-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.3);
    backdrop-filter: blur(4px);
    z-index: 999;
  }
  
  .sidebar-overlay.active { 
    display: block; 
  }

  /* HAMBURGER TRIGGER BUTTON */
  .hamburger {
    display: flex !important;
    width: 42px; 
    height: 42px;
    align-items: center; 
    justify-content: center;
    background: #ffffff;
    border: 1px solid rgba(59, 130, 246, 0.12);
    border-radius: 12px;
    cursor: pointer;
    color: #0f172a;
    font-size: 18px;
    box-shadow: 0 10px 30px rgba(37, 99, 235, 0.04);
  }
}

.hamburger { 
  display: none; 
}
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
                <div class="topbar-title"><h2>Manajemen Produk</h2></div>
            </div>
            <div class="topbar-info" style="text-align: right;">
                <div style="font-weight: 800; font-size: 14px;"><?= formatTanggalIndo(date('Y-m-d H:i:s')) ?></div>
                <div style="font-size: 11px; color: #2563eb; font-weight: 800; text-transform: uppercase;">Admin Barang</div>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>

            <div class="grid">
                <div class="card">
                    <h3 class="card-title">
                        <i class="fas <?= $editProduk ? 'fa-pen-to-square' : 'fa-circle-plus' ?>"></i>
                        <?= $editProduk ? 'Edit Produk' : 'Tambah Produk' ?>
                    </h3>
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($editProduk): ?><input type="hidden" name="id" value="<?= $editProduk['id'] ?>"><?php endif; ?>
                        
                        <div class="form-group">
                            <label class="label">Nama Identitas Produk</label>
                            <input type="text" name="nama" class="input" value="<?= $editProduk['nama'] ?? '' ?>" required placeholder="Contoh: Mineral 600ml">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="label">Satuan</label>
                                <input type="text" name="satuan" class="input" value="<?= $editProduk['satuan'] ?? 'pcs' ?>" required placeholder="Dus/Pcs">
                            </div>
                            <div class="form-group">
                                <label class="label">Stok Awal</label>
                                <input type="number" name="stok" class="input" value="<?= $editProduk['stok'] ?? '0' ?>" required min="0" step="1">
                            </div>
<!--
<div class="form-group">
    <label class="label">Stok Awal</label>
    <input type="number" name="stok" class="input"
           value="<?= $editProduk['stok'] ?? '0' ?>"
           required min="0" max="1000" step="1"
           oninput="if(this.value>1000)this.value=1000;">
</div>
-->                  
                        </div>
                        <div class="form-group">
                            <label class="label">Harga Khusus Toko (Rp)</label>
                            <input type="number" name="harga_toko" class="input" value="<?= $editProduk['harga_toko'] ?? '' ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="label">Harga Konsumen Umum (Rp)</label>
                            <input type="number" name="harga_umum" class="input" value="<?= $editProduk['harga_umum'] ?? '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="label">Foto Utama (Wajib)</label>
                            <input type="file" name="foto" class="input" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label class="label">Foto Tambahan 1 (Opsional)</label>
                            <input type="file" name="foto2" class="input" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label class="label">Foto Tambahan 2 (Opsional)</label>
                            <input type="file" name="foto3" class="input" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label class="label">Keterangan Tambahan</label>
                            <textarea name="deskripsi" class="input" style="height: 100px; resize: none;"><?= $editProduk['deskripsi'] ?? '' ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editProduk ? 'SIMPAN PERUBAHAN' : 'TAMBAH KE KATALOG' ?> <i class="fas fa-chevron-right"></i>
                        </button>
                        <?php if ($editProduk): ?>
                            <a href="produk.php" class="btn btn-outline" style="width: 100%; margin-top: 12px;">BATAL EDIT</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-box">
                    <div style="padding: 32px 24px; background: #fff; border-bottom: 1px solid rgba(59, 130, 246, 0.12); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-family:'Syne',sans-serif; font-size: 18px; font-weight: 800; color: #0f172a;">Data Produk</h3>
                        <span style="font-size: 12px; font-weight: 800; color: #94a3b8;"><?= count($produkList) ?> Produk Ditemukan</span>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>DETAIL PRODUK</th>
                                <th>HARGA UMUM</th>
                                <th>HARGA TOKO</th>
                                <th>STOK</th>
                                <th style="text-align: right;">OPSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produkList as $p): ?>                         
                        <tr>
                                <td data-label="Detail Produk">
                                    <div style="display: flex; align-items: center; gap: 16px;">
                                        <img src="<?= getUploadUrl($p['foto'], 'produk') ?>" class="prod-img">
                                        <div>
                                            <div style="font-weight: 800; color: #0f172a; font-size: 18px;"><?= htmlspecialchars($p['nama']) ?></div>
                                            <div style="font-size: 14px; color: #64748b; margin-top: 4px;"><?= htmlspecialchars($p['deskripsi'] ?: '-') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Harga Umum">
                                    <span class="price-tag" style="color: #64748b; font-size: 16px;"><?= formatRupiah($p['harga_umum']) ?></span>
                                </td>
                                <td data-label="Harga Toko">
                                    <span class="price-tag"><?= formatRupiah($p['harga_toko']) ?></span>
                                </td>
                                <td data-label="Stok">
                                    <span class="stok-badge"><?= $p['stok'] ?> <?= strtoupper($p['satuan']) ?></span>
                                </td>
                                <td data-label="Opsi" style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-outline" style="padding: 10px; width: 40px; height: 40px; border-radius: 12px;"><i class="fas fa-edit"></i></a>
                                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-outline" style="padding: 10px; width: 40px; height: 40px; border-radius: 12px; color: #ef4444; border-color: #fee2e2;" onclick="return confirm('Hapus produk ini?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
