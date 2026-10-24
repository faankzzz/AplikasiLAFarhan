<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('super_admin');
$user = getCurrentUser($pdo);

$success = '';
$error = '';

// --- PROSES CRUD PRODUK ---

// 1. Tambah & Edit Produk
if (isset($_POST['save_produk'])) {
    $id = $_POST['id'] ?? null;
    $nama = $_POST['nama'];
    $satuan = $_POST['satuan'];
    $harga_toko = $_POST['harga_toko'];
    $harga_umum = $_POST['harga_umum'];
    $stok = $_POST['stok'];

    try {
        if ($id) {
            // Update
            $sql = "UPDATE produk SET nama=?, satuan=?, harga_toko=?, harga_umum=?, stok=? WHERE id=?";
            $pdo->prepare($sql)->execute([$nama, $satuan, $harga_toko, $harga_umum, $stok, $id]);
            $success = "Produk berhasil diperbarui!";
        } else {
            // Insert
            $sql = "INSERT INTO produk (nama, satuan, harga_toko, harga_umum, stok) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$nama, $satuan, $harga_toko, $harga_umum, $stok]);
            $success = "Produk baru berhasil ditambahkan!";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan database!";
    }
}

// 2. Hapus Produk
if (isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM produk WHERE id = ?")->execute([$_POST['delete_id']]);
    $success = "Produk berhasil dihapus!";
}

// 3. Proses Mutasi Stok
if (isset($_POST['save_mutasi'])) {
    $type = $_POST['type'] ?? 'masuk';
    $produk_id = (int)$_POST['produk_id'];
    $jumlah = (int)$_POST['jumlah'];
    $kategori = $_POST['kategori'] ?? 'lainnya';
    $keterangan = trim($_POST['keterangan']);
    
    if ($produk_id > 0 && $jumlah > 0 && !empty($keterangan)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT stok FROM produk WHERE id = ?");
            $stmt->execute([$produk_id]);
            $stok_awal = (int)$stmt->fetchColumn();

            if ($type === 'masuk') {
                $stok_akhir = $stok_awal + $jumlah;
                $pdo->prepare("UPDATE produk SET stok = ? WHERE id = ?")->execute([$stok_akhir, $produk_id]);
            } else {
                if ($stok_awal < $jumlah) throw new Exception("Stok tidak cukup!");
                $stok_akhir = $stok_awal - $jumlah;
                $pdo->prepare("UPDATE produk SET stok = ? WHERE id = ?")->execute([$stok_akhir, $produk_id]);
            }

            $pdo->prepare("INSERT INTO mutasi_stok (produk_id, tipe, jumlah, kategori, stok_awal, stok_akhir, keterangan, tanggal) VALUES (?,?,?,?,?,?,?,CURDATE())")
                ->execute([$produk_id, $type, $jumlah, $kategori, $stok_awal, $stok_akhir, $keterangan]);
            
            $pdo->commit();
            $success = "Mutasi stok berhasil disimpan!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = "Semua field mutasi wajib diisi!";
    }
}

// --- DATA UNTUK TAMPILAN ---
// Statistik Inventori
$totalProduk = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$totalStok = $pdo->query("SELECT COALESCE(SUM(stok),0) FROM produk")->fetchColumn();
$hampirHabis = $pdo->query("SELECT COUNT(*) FROM produk WHERE stok <= 20")->fetchColumn();

// Daftar Semua Produk
$produkList = $pdo->query("SELECT * FROM produk ORDER BY nama ASC")->fetchAll();

// Riwayat Mutasi Terbaru
$riwayatMutasi = $pdo->query("
    SELECT m.*, p.nama as produk_nama, p.satuan 
    FROM mutasi_stok m 
    JOIN produk p ON m.produk_id = p.id 
    ORDER BY m.created_at DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventori Global — Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8; --bg: #f0f6fa; --surface: #ffffff; --text: #1e293b; --muted: #64748b; --border: rgba(59, 130, 246, 0.12);
        --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); --sidebar-text: #f8fafc; --sidebar-muted: #94a3b8;
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif;
      }
      /* ===== SIDEBAR UNIFIED ===== */
      .sidebar { width:280px; background:linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; position: fixed; top:0; left:0; height: 100vh; z-index: 50; overflow-y: auto; }
      .sidebar-logo { display:flex; align-items:center; justify-content:center; padding:20px 24px; border-bottom:1px solid rgba(255,255,255,0.05); }
      .sidebar-profile { padding:24px 32px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; align-items:center; gap:12px; text-align:center; }
      .sidebar-avatar { width:64px; height:64px; border-radius:20px; background: rgba(255,255,255,0.08); color:white; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:800; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15); overflow:hidden; }
      .sidebar-avatar img { width:100%; height:100%; object-fit:cover; }
      .sidebar-user-info { display:flex; flex-direction:column; gap:4px; }
      .sidebar-user-name { font-weight:800; font-size:15px; color:white; }
      .sidebar-user-role { font-size:11px; font-weight:800; color: #38bdf8; text-transform:uppercase; letter-spacing:0.5px; }
      .sidebar-nav { padding:24px 16px; flex:1; overflow-y:auto; }
      .nav-label { font-size:11px; color: #38bdf8; text-transform:uppercase; letter-spacing:.15em; margin:20px 16px 8px; font-weight:800; }
      .nav-item { display:flex; align-items:center; gap:16px; padding: 14px 18px; border-radius: 20px; color: #94a3b8; font-size:15px; transition: .3s ease; text-decoration:none; font-weight: 700; margin-bottom: 6px; }
      .nav-item i { font-size:18px; width:22px; text-align:center; }
      .nav-item:hover { background:rgba(255,255,255,0.05); color:white; }
      .nav-item.active { background:linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color:white; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: 'Inter', sans-serif; background:#f0f6fa; color:#1e293b; min-height:100vh; overflow-x:hidden; }
      .layout { display:flex; min-height:100vh; width: 100%; }
      
      /* SIDEBAR */

      .main { flex:1; margin-left: 280px; padding: 40px; }
      
      /* KOMPONEN CRUD */
      .btn { padding: 12px 24px; border-radius: 14px; border: none; font-weight: 700; cursor: pointer; transition: .3s; font-family: 'Outfit', sans-serif; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 14px; }
      .btn-primary { background: #2563eb; color: white; } .btn-primary:hover { background: #1d4ed8; }
      .btn-circle { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: .2s; }
      .btn-edit { background: #eff6ff; color: #2563eb; }
      .btn-delete { background: #fff1f2; color: #e11d48; }
      
      .alert { padding: 16px 20px; border-radius: 16px; margin-bottom: 24px; font-weight: 600; font-size: 14px; }
      .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

      table { width: 100%; border-collapse: collapse; margin-top: 20px; }
      th { text-align: left; padding: 16px; color: #64748b; font-size: 12px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); text-transform: uppercase; letter-spacing: 0.5px; }
      td { padding: 16px; font-size: 14px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); }
      
      .card { background: #ffffff; padding: 24px; border-radius: 24px; border: 1px solid rgba(59, 130, 246, 0.12); margin-bottom: 24px; }
      .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
      .card-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; }
      
      .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
      .stat-card { background: #ffffff; padding: 24px; border-radius: 20px; border: 1px solid rgba(59, 130, 246, 0.12); text-align: center; }
      .stat-val { font-size: 32px; font-weight: 800; font-family: 'Outfit', sans-serif; margin-bottom: 8px; }
      .stat-label { font-size: 13px; color: #64748b; font-weight: 600; }
      
      .badge { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; }
      .badge-danger { background: #fef2f2; color: #991b1b; }
      .badge-warning { background: #fefce8; color: #854d0e; }
      .badge-success { background: #f0fdf4; color: #166534; }

      /* MODAL */
      .modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
      .modal.active { display: flex; }
      .modal-content { background: #ffffff; width: 500px; padding: 40px; border-radius: 30px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
      .form-group { margin-bottom: 18px; }
      .form-label { display: block; font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
      .input { width: 100%; padding: 14px 18px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.12); background: #f8fafc; font-weight: 600; outline: none; transition: .2s; font-family: 'Inter', sans-serif; }
      .input:focus { border-color: #2563eb; background: #ffffff; }

      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); z-index: 1000; transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 20px 0 40px rgba(0,0,0,0.1); }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; padding: 15px; }
        .header { flex-direction: column; align-items: flex-start !important; gap: 20px; }
        .header .btn { width: 100%; justify-content: center; }
        .stat-grid { grid-template-columns: 1fr; gap: 15px; }
        .card { padding: 20px 15px; border-radius: 30px; }
        
        .grid-bottom { grid-template-columns: 1fr !important; gap: 32px; }
        .modal-content { width: 90%; padding: 30px 20px; border-radius: 24px; }
        
        /* Table to Card Transformation */
        table thead { display: none; }
        table tbody tr { 
            display: block; 
            background: #f8fafc; 
            border-radius: 20px; 
            margin-bottom: 20px; 
            padding: 15px; 
            border: 1px solid #e2e8f0;
        }
        table td { 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            width: 100%; 
            padding: 10px 0 !important; 
            border: none !important;
            text-align: left !important;
        }
        table td::before {
            content: attr(data-label);
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
        }
        table td:not(:last-child) { border-bottom: 1px dashed #e2e8f0 !important; }

        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 999; }
        .sidebar-overlay.active { display: block; }
        .hamburger { display: flex !important; width: 40px; height: 40px; align-items: center; justify-content: center; background: #f1f5f9; border: 1px solid rgba(59, 130, 246, 0.12); border-radius: 12px; cursor: pointer; color: #1e293b; font-size: 18px; }
      }
      .hamburger { display: none; width: 40px; height: 40px; align-items: center; justify-content: center; background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); border-radius: 12px; cursor: pointer; }
      
      .active-masuk { background: #ffffff; color: #16a34a; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
      .active-keluar { background: #ffffff; color: #dc2626; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo" style="justify-content: center; padding: 24px 0;"><img src="../assets/css/logoo.png" alt="ALFA ONE" style="width: 200px; height: auto; object-fit: contain;"></div>
        
        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <?php if (!empty($user['foto'])): ?>
                    <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                <?php else: ?>
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="sidebar-user-role">Super Admin</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-th-large nav-icon"></i> Dashboard</a>
            <a href="users.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users-cog nav-icon"></i> Manajemen Pegawai</a>
            <a href="konsumen.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'konsumen.php' ? 'active' : '' ?>"><i class="fas fa-users nav-icon"></i> Daftar Konsumen</a>
            
            <div class="nav-label">Monitoring</div>
            <a href="pesanan.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'pesanan.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart nav-icon"></i> Semua Pesanan</a>
            <a href="inventori.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'inventori.php' ? 'active' : '' ?>"><i class="fas fa-boxes nav-icon"></i> Inventori Barang</a>
            <a href="pengiriman.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'pengiriman.php' ? 'active' : '' ?>"><i class="fas fa-truck-fast nav-icon"></i> Lacak Pengiriman</a>
            
            <div class="nav-label">Akun</div>
            <a href="profil.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>"><i class="fas fa-user-circle nav-icon"></i> Profil Akun</a>
            <a href="../logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt nav-icon"></i> Keluar</a>
        </nav>
    </aside>

    <main class="main">
        <header class="header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></div>
                <div>
                    <h1 style="font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; margin: 0;">Pantauan Inventori Barang</h1>
                    <p style="color: #64748b; font-weight: 500; font-size: 14px; margin: 0;">Kelola stok dan harga produk Alfa One.</p>
                </div>
            </div>
            <button onclick="openModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Produk</button>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-val"><?= number_format($totalProduk) ?></div>
                <div class="stat-label">Jenis Produk</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?= number_format($totalStok) ?></div>
                <div class="stat-label">Total Barang Tersedia</div>
            </div>
            <div class="stat-card" style="border-color: #fecaca; background: #fff1f2;">
                <div class="stat-val" style="color: #b91c1c;"><?= number_format($hampirHabis) ?></div>
                <div class="stat-label" style="color: #b91c1c;">Hampir Habis (≤ 20)</div>
            </div>
        </div>

        <div class="card" style="overflow-x: auto;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ul"></i> Katalog & Persediaan Barang</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>NAMA PRODUK</th>
                        <th>SATUAN</th>
                        <th>HARGA TOKO</th>
                        <th>HARGA UMUM</th>
                        <th>STOK</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produkList as $p): ?>
                    <tr>
                        <td data-label="Nama Produk" style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($p['nama']) ?></td>
                        <td data-label="Satuan" style="font-weight: 700; color: #64748b;"><?= strtoupper($p['satuan']) ?></td>
                        <td data-label="Harga Toko" style="font-weight: 700; color: #2563eb;"><?= formatRupiah($p['harga_toko']) ?></td>
                        <td data-label="Harga Umum" style="font-weight: 700; color: #64748b;"><?= formatRupiah($p['harga_umum']) ?></td>
                        <td data-label="Stok" style="font-weight: 800; font-size: 16px;"><?= $p['stok'] ?></td>
                        <td data-label="Status">
                            <?php if ($p['stok'] <= 0): ?>
                                <span class="badge badge-danger">KOSONG</span>
                            <?php elseif ($p['stok'] <= 20): ?>
                                <span class="badge badge-warning">LIMIT</span>
                            <?php else: ?>
                                <span class="badge badge-success">AMAN</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Aksi">
                            <div style="display: flex; gap: 8px;">
                                <button onclick='editProduk(<?= json_encode($p) ?>)' class="btn-circle btn-edit" title="Edit"><i class="fas fa-pen"></i></button>
                                <form method="POST" onsubmit="return confirm('Hapus produk ini?')" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-circle btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="grid-bottom" style="display: grid; grid-template-columns: 400px 1fr; gap: 24px; align-items: start; margin-top: 32px;">
            <!-- Form Mutasi -->
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-exchange-alt"></i> Update Stok (Mutasi)</h3></div>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; background: #f1f5f9; padding: 6px; border-radius: 12px;">
                        <label style="cursor:pointer; text-align:center; padding:12px; border-radius:10px; font-weight:800; font-size:14px; transition:.3s;" id="labelMasuk" class="active-masuk">
                            <input type="radio" name="type" value="masuk" checked style="display:none" onchange="updateMutasiUI('masuk')"> MASUK
                        </label>
                        <label style="cursor:pointer; text-align:center; padding:10px; border-radius:10px; font-weight:800; font-size:14px; transition:.3s;" id="labelKeluar">
                            <input type="radio" name="type" value="keluar" style="display:none" onchange="updateMutasiUI('keluar')"> KELUAR
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size:14px;">Produk</label>
                        <select name="produk_id" class="input" required style="font-size:16px;">
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach($produkList as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> (Stok: <?= $p['stok'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size:14px;">Kategori</label>
                        <select name="kategori" class="input" style="font-size:16px;">
                            <option value="pabrik">Pabrik / Restock</option>
                            <option value="rusak">Barang Rusak</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size:14px;">Jumlah</label>
                        <input type="number" name="jumlah" class="input" required min="1" style="font-size:16px;">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size:14px;">Keterangan</label>
                        <input type="text" name="keterangan" class="input" required placeholder="Contoh: Stok Masuk dari Pabrik" style="font-size:16px;">
                    </div>

                    <button type="submit" name="save_mutasi" class="btn btn-primary" style="width:100%; justify-content:center; padding:16px;" id="btnMutasi">SIMPAN MUTASI</button>
                </form>
            </div>

            <!-- Tabel Riwayat Mutasi -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history"></i> Riwayat Mutasi Terkini</h3>
                    <a href="../admin_stok/export_mutasi_stok.php" class="btn" style="background:#ecfdf5; color:#047857; font-size:12px;"><i class="fas fa-file-excel"></i> Export</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="font-size:13px;">WAKTU</th>
                            <th style="font-size:13px;">PRODUK</th>
                            <th style="text-align:center; font-size:13px;">AWAL</th>
                            <th style="text-align:center; font-size:13px;">MUTASI</th>
                            <th style="text-align:center; font-size:13px;">AKHIR</th>
                            <th style="font-size:13px;">KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($riwayatMutasi as $m): ?>
                        <tr>
                            <td data-label="Waktu" style="font-size:14px; color:#64748b;"><?= date('d/m H:i', strtotime($m['created_at'])) ?></td>
                            <td data-label="Produk" style="font-weight:700; font-size:16px;"><?= htmlspecialchars($m['produk_nama']) ?></td>
                            <td data-label="Awal" style="text-align:center; color:#64748b; font-size:16px;"><?= $m['stok_awal'] ?></td>
                            <td data-label="Mutasi" style="text-align:center;">
                                <span style="padding:6px 14px; border-radius:8px; font-weight:800; font-size:14px; background: <?= $m['tipe'] == 'masuk' ? '#f0fdf4' : '#fff1f2' ?>; color: <?= $m['tipe'] == 'masuk' ? '#166534' : '#991b1b' ?>;">
                                    <?= $m['tipe'] == 'masuk' ? '+' : '-' ?><?= $m['jumlah'] ?>
                                </span>
                            </td>
                            <td data-label="Akhir" style="text-align:center; font-weight:800; font-size:18px;"><?= $m['stok_akhir'] ?></td>
                            <td data-label="Keterangan" style="font-size:15px; color:#64748b;"><?= htmlspecialchars($m['keterangan']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($riwayatMutasi)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;">Belum ada riwayat mutasi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div class="modal" id="modalProduk">
    <div class="modal-content">
        <h2 id="modalTitle" style="font-family: 'Outfit', sans-serif; margin-bottom: 25px; font-weight: 800;">Tambah Produk</h2>
        <form method="POST">
            <input type="hidden" name="id" id="f_id">
            <div class="form-group">
                <label class="form-label">Nama Produk</label>
                <input type="text" name="nama" id="f_nama" class="input" required placeholder="Contoh: Air Mineral 600ml">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Satuan</label>
                    <input type="text" name="satuan" id="f_satuan" class="input" required placeholder="DUS / PCS / GALON">
                </div>
                <div class="form-group">
                    <label class="form-label">Stok Awal</label>
                    <input type="number" name="stok" id="f_stok" class="input" required value="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Harga Toko (Grosir)</label>
                <input type="number" name="harga_toko" id="f_harga_toko" class="input" required placeholder="Rp">
            </div>
            <div class="form-group">
                <label class="form-label">Harga Umum (Retail)</label>
                <input type="number" name="harga_umum" id="f_harga_umum" class="input" required placeholder="Rp">
            </div>
            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <button type="button" onclick="closeModal()" class="btn" style="flex: 1; justify-content: center; background: #f1f5f9;">Batal</button>
                <button type="submit" name="save_produk" class="btn btn-primary" style="flex: 1; justify-content: center;">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Logic Sidebar Mobile
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }

    // Logic Modal CRUD
    const modal = document.getElementById('modalProduk');
    
    function openModal() {
        document.getElementById('modalTitle').innerText = 'Tambah Produk';
        document.getElementById('f_id').value = '';
        document.getElementById('f_nama').value = '';
        document.getElementById('f_satuan').value = '';
        document.getElementById('f_stok').value = '0';
        document.getElementById('f_harga_toko').value = '';
        document.getElementById('f_harga_umum').value = '';
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    function editProduk(data) {
        document.getElementById('modalTitle').innerText = 'Edit Produk';
        document.getElementById('f_id').value = data.id;
        document.getElementById('f_nama').value = data.nama;
        document.getElementById('f_satuan').value = data.satuan;
        document.getElementById('f_stok').value = data.stok;
        document.getElementById('f_harga_toko').value = data.harga_toko;
        document.getElementById('f_harga_umum').value = data.harga_umum;
        modal.classList.add('active');
    }

    // Close modal if click outside
    window.onclick = function(event) {
        if (event.target == modal) closeModal();
    }

    function updateMutasiUI(type) {
        const labelMasuk = document.getElementById('labelMasuk');
        const labelKeluar = document.getElementById('labelKeluar');
        const btn = document.getElementById('btnMutasi');

        if (type === 'masuk') {
            labelMasuk.classList.add('active-masuk');
            labelKeluar.classList.remove('active-keluar');
            btn.style.background = 'linear-gradient(135deg, #2563eb 0%, #3b82f6 100%)';
        } else {
            labelMasuk.classList.remove('active-masuk');
            labelKeluar.classList.add('active-keluar');
            btn.style.background = 'linear-gradient(135deg, #ef4444 0%, #b91c1c 100%)';
        }
    }
</script>
</body>
</html>