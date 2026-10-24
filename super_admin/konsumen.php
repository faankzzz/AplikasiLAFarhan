<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('super_admin');
$user = getCurrentUser($pdo);

$success = '';

// --- CRUD: Tambah / Edit Konsumen ---
if (isset($_POST['save_konsumen'])) {
    $id = $_POST['id'] ?? null;
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $telepon = $_POST['telepon'];
    $alamat = $_POST['alamat'];
    $role_customer = $_POST['role_customer'];
    $latitude = $_POST['latitude'] ?: null;
    $longitude = $_POST['longitude'] ?: null;

    // Ambil data lama jika edit
    $oldData = null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $oldData = $stmt->fetch();
    }

    // Proses Upload Dokumen
    $kartu_member = $oldData['kartu_member'] ?? null;
    $surat_usaha = $oldData['surat_usaha'] ?? null;
    $halaman_depan_toko = $oldData['halaman_depan_toko'] ?? null;

    if (!empty($_FILES['kartu_member']['name'])) { $kartu_member = uploadFile($_FILES['kartu_member'], 'kartu_member'); }
    if (!empty($_FILES['surat_usaha']['name'])) { $surat_usaha = uploadFile($_FILES['surat_usaha'], 'surat_usaha'); }
    if (!empty($_FILES['halaman_depan_toko']['name'])) { $halaman_depan_toko = uploadFile($_FILES['halaman_depan_toko'], 'foto_toko'); }

    if ($id) {
        $sql = "UPDATE users SET nama=?, email=?, telepon=?, alamat=?, role_customer=?, latitude=?, longitude=?, 
                kartu_member=?, surat_usaha=?, halaman_depan_toko=? WHERE id=?";
        $pdo->prepare($sql)->execute([
            $nama, $email, $telepon, $alamat, $role_customer, $latitude, $longitude,
            $kartu_member, $surat_usaha, $halaman_depan_toko, $id
        ]);
        $success = "Data konsumen berhasil diperbarui!";
    } else {
        $password = password_hash('konsumen123', PASSWORD_DEFAULT);
        $statusVerif = ($role_customer === 'toko') ? 'pending' : 'verified';
        $sql = "INSERT INTO users (nama, email, telepon, alamat, role_customer, role, password, status_verifikasi, latitude, longitude, kartu_member, surat_usaha, halaman_depan_toko) 
                VALUES (?, ?, ?, ?, ?, 'konsumen', ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $nama, $email, $telepon, $alamat, $role_customer, $password, $statusVerif, 
            $latitude, $longitude, $kartu_member, $surat_usaha, $halaman_depan_toko
        ]);
        $success = "Konsumen baru berhasil ditambahkan!";
    }
}

// --- CRUD: Hapus ---
if (isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST['delete_id']]);
    $success = "Data konsumen berhasil dihapus!";
}

// --- AKSI: Verifikasi Toko ---
if (isset($_POST['action'])) {
    $userId = (int)$_POST['user_id'];
    $status = ($_POST['action'] === 'approve') ? 'verified' : 'rejected';
    $pdo->prepare("UPDATE users SET status_verifikasi = ? WHERE id = ?")->execute([$status, $userId]);
    $success = ($_POST['action'] === 'approve') ? 'Akun Toko Berhasil Diverifikasi!' : 'Pendaftaran Akun Toko Ditolak.';
}

// --- AKSI: Reset Password ---
if (isset($_POST['reset_password'])) {
    $userId = (int)$_POST['user_id'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newPassword, $userId]);
    $success = "Password konsumen berhasil direset!";
}
// --- DATA: Filter & Search ---
$filterType = $_GET['tipe'] ?? 'all';
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users WHERE role = 'konsumen'";
$params = [];
if ($filterType !== 'all') { $query .= " AND role_customer = ?"; $params[] = $filterType; }
if ($search) {
    $query .= " AND (nama LIKE ? OR email LIKE ? OR telepon LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$konsumenList = $stmt->fetchAll();

// HITUNG TOTAL KONSUMEN
$totalKonsumen = count($konsumenList);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Konsumen — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8; --bg: #f0f6fa; --surface: #ffffff; --text: #1e293b; 
            --muted: #64748b; --border: rgba(59, 130, 246, 0.12);
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
        body { font-family: 'Inter', sans-serif; background: #f0f6fa; color: #1e293b; }

        .layout { display: flex; min-height: 100vh; }

        /* SIDEBAR */

        .nav-icon { width: 24px; text-align: center; }

        /* MAIN CONTENT */
        .main { flex: 1; margin-left: 280px; padding: 40px; width: 100%; transition: .3s; }

        .header-box {
            background: #ffffff; padding: 30px 40px; border-radius: 30px; margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(59, 130, 246, 0.12);
        }
        .header-box h1 { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 800; }

        /* CRUD STYLES */
        .filter-card { background: #ffffff; padding: 30px; border-radius: 24px; border: 1px solid rgba(59, 130, 246, 0.12); margin-bottom: 30px; display: flex; gap: 20px; align-items: flex-end; }
        .form-group { flex: 1; }
        .label { display: block; font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 10px; }
        .input { width: 100%; padding: 15px 20px; border-radius: 14px; border: 1px solid rgba(59, 130, 246, 0.12); background: #f8fafc; font-weight: 600; outline: none; }

        .btn { padding: 15px 30px; border-radius: 14px; border: none; font-weight: 700; cursor: pointer; transition: .3s; font-family: 'Outfit', sans-serif; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .btn-primary { background: #2563eb; color: white; } .btn-primary:hover { background: #1d4ed8; }

        /* GRID & CARDS - FONT UPDATED */
        .konsumen-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 30px; }
        .konsumen-card { background: #ffffff; border-radius: 35px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); position: relative; transition: .3s; }
        
        .card-header-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-right: 80px; }
        
        .badge-role { padding: 8px 18px; border-radius: 50px; font-size: 13px; font-weight: 800; text-transform: uppercase; border: 1px solid transparent; }
        .badge-toko { background: #fffbeb; color: #b45309; border-color: #fef3c7; }
        .badge-umum { background: #f0f9ff; color: #0369a1; border-color: #e0f2fe; }

        .badge-status { padding: 8px 14px; border-radius: 12px; font-size: 12px; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px; }
        .status-verified { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .status-pending { background: #fffaf0; color: #9a3412; border: 1px solid #fed7aa; }
        .status-rejected { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .name { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; margin-bottom: 15px; color: #0f172a; word-break: break-word; }
        .meta { color: #475569; margin-bottom: 25px; font-size: 15px; line-height: 1.6; font-weight: 500; }
        .address { background: #f8fafc; padding: 22px; border-radius: 24px; font-size: 15px; border: 1px solid #f1f5f9; color: #334155; line-height: 1.5; font-weight: 500; }
/* Container aksi kita buat lebih rapi */
.card-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    /* Menghilangkan absolute agar tidak menabrak teks */
}

/* Header meta kita buat flexbox untuk memisahkan badge dan tombol */
.card-header-meta {
    display: flex;
    justify-content: space-between; /* Menendang badge ke kiri, tombol ke kanan */
    align-items: center;
    margin-bottom: 25px;
    padding-right: 0; /* Reset padding ini */
}

/* Memastikan badge-badge di kiri tetap rapi */
.badges-left {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

/* Ukuran tombol lingkaran agar lebih konsisten */
.btn-circle { 
    width: 42px; 
    height: 42px; 
    border-radius: 12px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    border: none; 
    cursor: pointer; 
    font-size: 16px; 
    transition: all 0.2s;
}
        .btn-edit { background: #eff6ff; color: #2563eb; }
        .btn-delete { background: #fff1f2; color: #e11d48; }

        /* STATS BADGE */
        .stats-badge { background: #f8fafc; padding: 12px 24px; border-radius: 20px; border: 1px solid rgba(59, 130, 246, 0.12); display: flex; align-items: center; gap: 15px; }
        .stats-info strong { font-size: 20px; font-family: 'Outfit', sans-serif; color: #2563eb; }

        /* MODAL */
        .modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 20px; }
        .modal.active { display: flex; }
        .modal-content { background: #ffffff; width: 100%; max-width: 800px; padding: 40px; border-radius: 35px; max-height: 90vh; overflow-y: auto; }

        /* HAMBURGER */
        .hamburger { display: none; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 49; }
        .sidebar-overlay.active { display: block; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); z-index: 1000; transition: transform .3s; }
            .sidebar.active { transform: translateX(0); }
            .main { margin-left: 0; padding: 24px 20px; }
            .header-box { flex-direction: column; align-items: flex-start; gap: 20px; padding: 24px; }
            .header-box div:last-child { width: 100%; flex-direction: column; align-items: flex-start !important; }
            .stats-badge { width: 100%; justify-content: center; }
            .header-box .btn { width: 100%; justify-content: center; }
            .filter-card { flex-direction: column; align-items: stretch; padding: 24px; }
            .form-group { flex: none; width: 100%; }
            .konsumen-grid { grid-template-columns: 1fr; }
            .hamburger {
                display: flex; width: 44px; height: 44px; align-items: center; justify-content: center;
                background: #ffffff; border: 1px solid rgba(59, 130, 246, 0.12); border-radius: 12px; cursor: pointer;
                color: #1e293b; font-size: 18px;
            }
            .modal-content { padding: 24px; border-radius: 24px; }
            #toko_docs div { grid-template-columns: 1fr !important; }
            #docsContent { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>
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
            <header class="header-box">
                <div style="display:flex; align-items:center; gap:16px;">
                    <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
                    <div>
                        <h1>Daftar Konsumen</h1>
                        <p style="color: #64748b; font-size: 16px;">Manajemen database pelanggan Alfa One.</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="stats-badge">
                        <div class="btn-circle btn-edit" style="cursor: default;"><i class="fas fa-users"></i></div>
                        <div class="stats-info">
                            <span>Total Konsumen</span>
                            <strong><?= $totalKonsumen ?> Orang</strong>
                        </div>
                    </div>
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <a href="export_konsumen.php" class="btn" style="background: #ecfdf5; color: #059669; border: 1px solid #10b981; font-weight: 800; font-size: 15px;"><i class="fas fa-file-excel"></i> Export </a>
                    <button onclick="openModal()" class="btn btn-primary" style="font-weight: 800; font-size: 15px;"><i class="fas fa-plus"></i> Tambah Konsumen</button>
                </div>
            </header>
            <div class="sidebar-overlay" id="sidebarOverlay"></div>

            <?php if ($success): ?>
                <div style="padding: 20px; background: #f0fdf4; color: #15803d; border-radius: 20px; margin-bottom: 30px; font-weight: 700; border: 1px solid #bbf7d0; font-size: 16px;">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="filter-card">
                <div class="form-group">
                    <label class="label">Pencarian</label>
                    <input type="text" name="search" class="input" placeholder="Nama, email, atau telepon..." value="<?= htmlspecialchars($search) ?>" style="font-size: 16px;">
                </div>
                <div class="form-group" style="flex: 0 0 250px;">
                    <label class="label">Kategori</label>
                    <select name="tipe" class="input" style="font-size: 16px;">
                        <option value="all">Semua Tipe</option>
                        <option value="toko" <?= $filterType === 'toko' ? 'selected' : '' ?>>Toko (Bisnis)</option>
                        <option value="umum" <?= $filterType === 'umum' ? 'selected' : '' ?>>Umum (Personal)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 54px;"><i class="fas fa-filter"></i> Filter</button>
            </form>

            <div class="konsumen-grid">
                <?php foreach ($konsumenList as $k): ?>
                    <div class="konsumen-card">
                        <div class="card-actions">
                            <button onclick='editKonsumen(<?= json_encode($k) ?>)' class="btn-circle btn-edit" title="Edit Data"><i class="fas fa-edit"></i></button>
                            <button onclick='openResetModal(<?= $k['id'] ?>, "<?= htmlspecialchars($k['nama']) ?>")' class="btn-circle" style="background: #f0f9ff; color: #0369a1;" title="Reset Password"><i class="fas fa-key"></i></button>
                            <form method="POST" onsubmit="return confirm('Hapus konsumen ini?')" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $k['id'] ?>">
                                <button type="submit" class="btn-circle btn-delete" title="Hapus Data"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php if ($k['role_customer'] === 'toko'): ?>
                                <button onclick='viewDocs(<?= json_encode($k) ?>)' class="btn-circle" style="background: #fef3c7; color: #b45309;" title="Lihat Dokumen"><i class="fas fa-file-invoice"></i></button>
                            <?php endif; ?>
                        </div>

                        <div class="card-header-meta">
                            <span class="badge-role badge-<?= $k['role_customer'] ?? 'umum' ?>">
                                <?= htmlspecialchars($k['role_customer'] ?? 'umum') ?>
                            </span>

                            <?php 
                            $status = $k['status_verifikasi'] ?? 'pending';
                            $statusLabel = 'Pending'; $statusClass = 'status-pending'; $statusIcon = 'fa-clock';
                            if ($status === 'verified') { $statusLabel = 'Verified'; $statusClass = 'status-verified'; $statusIcon = 'fa-check-circle'; }
                            elseif ($status === 'rejected') { $statusLabel = 'Rejected'; $statusClass = 'status-rejected'; $statusIcon = 'fa-times-circle'; }
                            ?>
                            <span class="badge-status <?= $statusClass ?>">
                                <i class="fas <?= $statusIcon ?>"></i> <?= $statusLabel ?>
                            </span>
                        </div>
                        
                        <h3 class="name"><?= htmlspecialchars($k['nama']) ?></h3>
                        
                        <div class="meta">
                            <div><i class="fas fa-envelope" style="width:30px; color:#2563eb"></i> <?= htmlspecialchars($k['email']) ?></div>
                            <div><i class="fas fa-phone-alt" style="width:30px; color:#2563eb"></i> <?= htmlspecialchars($k['telepon']) ?></div>
                        </div>
                        
                        <div class="address">
                            <i class="fas fa-map-marker-alt" style="margin-right: 12px; color: #ef4444; font-size: 18px;"></i>
                            <?= htmlspecialchars($k['alamat'] ?: 'Alamat belum diatur') ?>
                        </div>

                        <?php if ($k['role_customer'] === 'toko' && ($k['status_verifikasi'] === 'pending' || empty($k['status_verifikasi']))): ?>
                            <div style="margin-top: 30px; display: flex; gap: 15px;">
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="user_id" value="<?= $k['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 14px; padding: 18px;">APPROVE</button>
                                </form>
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="user_id" value="<?= $k['id'] ?>">
                                    <button type="submit" name="action" value="reject" class="btn" style="width: 100%; justify-content: center; background: #f1f5f9; color: #64748b; font-size: 14px; padding: 18px;">REJECT</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <div class="modal" id="modalKonsumen">
        <div class="modal-content" style="width: 800px;">
            <h2 id="modalTitle" style="font-family: 'Outfit', sans-serif; margin-bottom: 25px;">Tambah Konsumen</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="f_id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="label">Nama Lengkap</label>
                        <input type="text" name="nama" id="f_nama" class="input" required style="font-size: 16px;">
                    </div>
                    <div class="form-group">
                        <label class="label">Email</label>
                        <input type="email" name="email" id="f_email" class="input" required style="font-size: 16px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                    <div class="form-group">
                        <label class="label">Telepon</label>
                        <input type="text" name="telepon" id="f_telepon" class="input" required style="font-size: 16px;">
                    </div>
                    <div class="form-group">
                        <label class="label">Tipe Pelanggan</label>
                        <select name="role_customer" id="f_role" class="input" onchange="toggleTokoFields()" style="font-size: 16px;">
                            <option value="umum">Umum (Personal)</option>
                            <option value="toko">Toko (Bisnis)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label class="label">Alamat Lengkap</label>
                    <textarea name="alamat" id="f_alamat" class="input" style="height: 60px; resize: none; font-size: 16px;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                    <div class="form-group">
                        <label class="label">Latitude</label>
                        <input type="text" name="latitude" id="f_lat" class="input" placeholder="Contoh: -2.9xx" style="font-size: 16px;">
                    </div>
                    <div class="form-group">
                        <label class="label">Longitude</label>
                        <input type="text" name="longitude" id="f_lng" class="input" placeholder="Contoh: 104.7xx" style="font-size: 16px;">
                    </div>
                </div>

                <div id="toko_docs" style="display: none; margin-top: 25px; padding: 20px; background: #fffbeb; border-radius: 20px; border: 1px solid #fef3c7;">
                    <h4 style="font-family: 'Outfit', sans-serif; font-size: 14px; margin-bottom: 15px; color: #b45309;">DOKUMEN VERIFIKASI TOKO</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="label" style="font-size: 11px;">Kartu Member</label>
                            <input type="file" name="kartu_member" class="input" accept="image/*" style="font-size: 12px; padding: 8px;">
                        </div>
                        <div class="form-group">
                            <label class="label" style="font-size: 11px;">Surat Usaha (SKU/NIB)</label>
                            <input type="file" name="surat_usaha" class="input" accept=".pdf,image/*" style="font-size: 12px; padding: 8px;">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="label" style="font-size: 11px;">Foto Depan Toko</label>
                            <input type="file" name="halaman_depan_toko" class="input" accept="image/*" style="font-size: 12px; padding: 8px;">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap;">
                    <button type="button" onclick="closeModal()" class="btn" style="flex: 1; min-width: 120px; justify-content: center; background: #f1f5f9;">Batal</button>
                    <button type="submit" name="save_konsumen" class="btn btn-primary" style="flex: 1; min-width: 120px; justify-content: center;">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detail Dokumen -->
    <div class="modal" id="modalDocs">
        <div class="modal-content" style="width: 900px; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="font-family: 'Outfit', sans-serif;">Berkas Verifikasi Toko</h2>
                <button onclick="closeDocsModal()" class="btn-circle" style="background: #f1f5f9;"><i class="fas fa-times"></i></button>
            </div>
            <div id="docsContent" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <!-- Content via JS -->
            </div>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div class="modal" id="modalResetPassword">
        <div class="modal-content" style="max-width: 450px;">
            <h2 style="font-family: 'Outfit', sans-serif; margin-bottom: 10px;">Reset Password</h2>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 25px;">Reset password untuk: <strong id="reset_user_name" style="color: #1e293b;"></strong></p>
            <form method="POST">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="form-group">
                    <label class="label">Password Baru</label>
                    <input type="text" name="new_password" class="input" required placeholder="Masukkan password baru..." style="font-size: 16px;">
                    <p style="font-size: 12px; color: #64748b; margin-top: 8px;">* Pastikan konsumen mencatat password baru ini.</p>
                </div>
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="button" onclick="closeResetModal()" class="btn" style="flex: 1; justify-content: center; background: #f1f5f9;">Batal</button>
                    <button type="submit" name="reset_password" class="btn btn-primary" style="flex: 1; justify-content: center;">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalKonsumen');
        const modalDocs = document.getElementById('modalDocs');

        function toggleTokoFields() {
            const role = document.getElementById('f_role').value;
            const tokoDocs = document.getElementById('toko_docs');
            tokoDocs.style.display = (role === 'toko') ? 'block' : 'none';
        }

        function openModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Konsumen';
            document.getElementById('f_id').value = '';
            document.getElementById('f_nama').value = '';
            document.getElementById('f_email').value = '';
            document.getElementById('f_telepon').value = '';
            document.getElementById('f_role').value = 'umum';
            document.getElementById('f_alamat').value = '';
            document.getElementById('f_lat').value = '';
            document.getElementById('f_lng').value = '';
            toggleTokoFields();
            modal.classList.add('active');
        }
        function closeModal() { modal.classList.remove('active'); }
        
        function editKonsumen(data) {
            document.getElementById('modalTitle').innerText = 'Edit Konsumen';
            document.getElementById('f_id').value = data.id;
            document.getElementById('f_nama').value = data.nama;
            document.getElementById('f_email').value = data.email;
            document.getElementById('f_telepon').value = data.telepon;
            document.getElementById('f_role').value = data.role_customer;
            document.getElementById('f_alamat').value = data.alamat;
            document.getElementById('f_lat').value = data.latitude || '';
            document.getElementById('f_lng').value = data.longitude || '';
            toggleTokoFields();
            modal.classList.add('active');
        }

        function viewDocs(data) {
            const baseUrl = '<?= BASE_URL ?>uploads/';
            const getUrl = (path) => path ? baseUrl + path : '<?= BASE_URL ?>assets/img/no-image.png';
            
            let html = `
                <div>
                    <p class="label">Kartu Member</p>
                    <div style="height:200px; background:#f8fafc; border-radius:15px; overflow:hidden; border:1px solid #e2e8f0;">
                        <img src="${getUrl(data.kartu_member)}" style="width:100%; height:100%; object-fit:contain;">
                    </div>
                </div>
                <div>
                    <p class="label">Surat Usaha (SKU/NIB)</p>
                    <div style="height:200px; background:#f8fafc; border-radius:15px; overflow:hidden; border:1px solid #e2e8f0;">
                        ${data.surat_usaha && data.surat_usaha.endsWith('.pdf') 
                            ? `<iframe src="${getUrl(data.surat_usaha)}" style="width:100%; height:100%; border:0;"></iframe>`
                            : `<img src="${getUrl(data.surat_usaha)}" style="width:100%; height:100%; object-fit:contain;">`
                        }
                    </div>
                </div>
                <div style="grid-column: span 2;">
                    <p class="label">Foto Depan Toko</p>
                    <div style="height:200px; background:#f8fafc; border-radius:15px; overflow:hidden; border:1px solid #e2e8f0;">
                        <img src="${getUrl(data.halaman_depan_toko)}" style="width:100%; height:100%; object-fit:contain;">
                    </div>
                </div>
                <div style="grid-column: span 2; background: #f8fafc; padding: 20px; border-radius: 15px; border: 1px solid #e2e8f0;">
                    <p class="label">Lokasi Koordinat</p>
                    <p style="font-weight: 700; color: #2563eb;">
                        <i class="fas fa-map-marker-alt"></i> ${data.latitude || '-'}, ${data.longitude || '-'}
                    </p>
                </div>
            `;
            document.getElementById('docsContent').innerHTML = html;
            modalDocs.classList.add('active');
        }

        function closeDocsModal() { modalDocs.classList.remove('active'); }

        const modalReset = document.getElementById('modalResetPassword');
        function openResetModal(id, name) {
            document.getElementById('reset_user_id').value = id;
            document.getElementById('reset_user_name').innerText = name;
            modalReset.classList.add('active');
        }
        function closeResetModal() { modalReset.classList.remove('active'); }

        // Sidebar logic
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

        window.onclick = function(event) { 
            if (event.target == modal) closeModal(); 
            if (event.target == modalDocs) closeDocsModal();
            if (event.target == modalReset) closeResetModal();
        }
    </script>
</body>
</html>