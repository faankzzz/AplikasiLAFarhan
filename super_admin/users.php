<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('super_admin');
$user = getCurrentUser($pdo);

$success = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== $user['id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'")->execute([$id]);
        $success = "User berhasil dihapus!";
    }
}

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['telepon'] ?? '');
    $role = $_POST['role'];
    $pass = $_POST['password'];
    $alamat = trim($_POST['alamat'] ?? '');
    $max_dus = (int)($_POST['max_dus'] ?? 0);
    $max_galon = (int)($_POST['max_galon'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Email sudah digunakan!";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, telepon, role, password, alamat, status_verifikasi, max_dus, max_galon) VALUES (?, ?, ?, ?, ?, ?, 'verified', ?, ?)");
        if ($stmt->execute([$nama, $email, $telepon, $role, $hash, $alamat, $max_dus, $max_galon])) {
            $success = "User baru berhasil ditambahkan!";
        }
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = (int)$_POST['user_id'];
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['telepon'] ?? '');
    $role = $_POST['role'];
    $alamat = trim($_POST['alamat'] ?? '');
    $max_dus = (int)($_POST['max_dus'] ?? 0);
    $max_galon = (int)($_POST['max_galon'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        $error = "Email sudah digunakan!";
    } else {
        $query = "UPDATE users SET nama = ?, email = ?, telepon = ?, role = ?, alamat = ?, max_dus = ?, max_galon = ?";
        $params = [$nama, $email, $telepon, $role, $alamat, $max_dus, $max_galon];
        
        if (!empty($_POST['password'])) {
            $query .= ", password = ?";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        $query .= " WHERE id = ?";
        $params[] = $id;
        
        if ($pdo->prepare($query)->execute($params)) {
            $success = "Data user berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui data user.";
        }
    }
}

$staff = $pdo->query("SELECT * FROM users WHERE role IN ('admin_penjualan', 'admin_barang', 'sopir', 'super_admin') ORDER BY role, nama")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User — Alfa One</title>
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
  --sidebar-width: 280px;
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

* { margin: 0; padding: 0; box-sizing: border-box; }

html, body {
  width: 100%;
  overflow-x: hidden;
}

body {
  font-family: 'Inter', sans-serif;
  background: #f0f6fa;
  color: #1e293b;
  min-height: 100vh;
  font-size: 16px;
}

/* ===== LAYOUT ===== */
.layout {
  display: flex;
  width: 100vw;
  min-height: 100vh;
  overflow-x: hidden;
}

/* ===== SIDEBAR ===== */









/* ===== MAIN ===== */
.main {
  margin-left: 280px;
  flex: 1 1 0;
  width: 0;
  min-width: 0;
  padding: 36px 40px;
  box-sizing: border-box;
  overflow-x: hidden;
}

/* ===== HEADER ===== */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 36px;
  gap: 20px;
}

.page-header h1 {
  font-family: 'Outfit', sans-serif;
  font-size: 28px;
  font-weight: 800;
  margin: 0;
}

.page-header p {
  color: #64748b;
  font-size: 15px;
  font-weight: 500;
  margin: 4px 0 0;
}

/* ===== ALERT ===== */
.alert {
  padding: 18px 24px;
  border-radius: 16px;
  margin-bottom: 24px;
  font-weight: 700;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.alert-danger  { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* ===== CARD ===== */
.card { background: #ffffff;
  border-radius: 28px;
  border: 1px solid rgba(59, 130, 246, 0.12);
  overflow: hidden;
  width: 100%;
}

.card-header {
  padding: 24px 32px;
  border-bottom: 1px solid rgba(59, 130, 246, 0.12);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-header-title {
  font-family: 'Outfit', sans-serif;
  font-size: 18px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 10px;
}

.staff-count {
  background: #eff6ff;
  color: #2563eb;
  font-size: 13px;
  font-weight: 800;
  padding: 4px 14px;
  border-radius: 50px;
}

/* ===== TABLE ===== */
.table-wrap { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; }

th {
  text-align: left;
  padding: 16px 24px;
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 700;
  background: #f8fafc;
  border-bottom: 1px solid rgba(59, 130, 246, 0.12);
}

td {
  padding: 20px 24px;
  font-size: 16px;
  border-bottom: 1px solid rgba(59, 130, 246, 0.12);
  vertical-align: middle;
}

tr:last-child td { border-bottom: none; }
tr:hover td { background: #fafbff; }

/* Avatar in table */
.user-cell {
  display: flex;
  align-items: center;
  gap: 14px;
}

.user-avatar-sm {
  width: 42px; height: 42px;
  border-radius: 14px;
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 800;
  flex-shrink: 0;
}

.user-name { font-weight: 700; font-size: 16px; }
.user-email-sm { font-size: 13px; color: #64748b; margin-top: 2px; }

/* Role badge */
.role-badge {
  padding: 6px 14px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: inline-block;
}

.role-super_admin    { background: #eff6ff; color: #1d4ed8; }
.role-admin_penjualan { background: #f0fdf4; color: #15803d; }
.role-admin_barang   { background: #fff7ed; color: #c2410c; }
.role-sopir          { background: #faf5ff; color: #7c3aed; }

/* ===== BUTTONS ===== */
.btn {
  padding: 11px 22px;
  border-radius: 12px;
  border: none;
  font-weight: 700;
  cursor: pointer;
  font-size: 14px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: .2s;
  font-family: 'Inter', sans-serif;
}

.btn-primary { background: #2563eb; color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
.btn-primary:hover { background: #1d4ed8; opacity: .9; transform: translateY(-1px); }
.btn-danger { background: #fee2e2; color: #ef4444; }
.btn-danger:hover { background: #fecaca; }

/* ===== HAMBURGER ===== */
.hamburger { display: none; }

/* ===== OVERLAY ===== */
.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  z-index: 49;
}
.sidebar-overlay.active { display: block; }

/* ===== MODAL ===== */
.modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15,23,42,0.5);
  backdrop-filter: blur(4px);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  background: #ffffff;
  padding: 40px;
  border-radius: 32px;
  width: 100%;
  max-width: 520px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
}

.modal-title {
  font-family: 'Outfit', sans-serif;
  font-size: 22px;
  font-weight: 800;
}

.modal-close {
  width: 36px; height: 36px;
  border-radius: 10px;
  border: 1px solid rgba(59, 130, 246, 0.12);
  background: #ffffff;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  color: #64748b;
  transition: .2s;
}

.modal-close:hover { background: #f1f5f9; color: #1e293b; }

/* ===== FORM ===== */
.form-group { margin-bottom: 20px; }

.form-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-bottom: 8px;
}

.form-control {
  width: 100%;
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px solid rgba(59, 130, 246, 0.12);
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
  transition: border-color .2s;
  outline: none;
  background: #ffffff;
}

.form-control:focus { border-color: #2563eb; }

textarea.form-control { min-height: 90px; resize: vertical; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
  .sidebar.active { transform: translateX(0); }

  .main { margin-left: 0; width: 100%; padding: 24px 20px; }

  .hamburger {
    display: flex;
    width: 44px; height: 44px;
    align-items: center; justify-content: center;
    background: #ffffff;
    border: 1px solid rgba(59, 130, 246, 0.12);
    border-radius: 12px;
    cursor: pointer;
    color: #1e293b;
    font-size: 18px;
    flex-shrink: 0;
  }

  .form-grid { grid-template-columns: 1fr; }
  .modal-content { padding: 24px; border-radius: 24px; }
}

@media (max-width: 640px) {
  .page-header { flex-direction: column; align-items: flex-start; gap: 20px; }
  .page-header .header-actions { width: 100%; flex-direction: column; }
  .page-header .btn { width: 100%; justify-content: center; }
  td, th { padding: 14px 12px; font-size: 14px; }
  .user-email-sm { display: none; } /* Hide email on small mobile to save space */
  .staff-count { display: none; }
}
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

    <!-- HEADER -->
    <div class="page-header">
      <div style="display:flex; align-items:center; gap:16px;">
        <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
        <div>
          <h1>Manajemen Pegawai</h1>
          <p>Kelola data akses pegawai sistem.</p>
        </div>
      </div>
      <div class="header-actions" style="display:flex; align-items:center; gap:12px;">
        <a href="export_users.php" class="btn" style="background: #ecfdf5; color: #059669; border: 1px solid #10b981; font-weight: 800;"><i class="fas fa-file-excel"></i> <span class="btn-text">Export Pegawai</span></a>
        <button class="btn btn-primary" onclick="document.getElementById('modalAdd').style.display='flex'">
          <i class="fas fa-plus"></i> <span class="btn-text">Tambah Pegawai</span>
        </button>
      </div>
    </div>

    <!-- ALERTS -->
    <?php if($success): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if($error): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <!-- TABLE CARD -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title">
          <i class="fas fa-users-cog" style="color:#2563eb"></i>
          Daftar Pegawai Sistem
        </div>
        <span class="staff-count"><?= count($staff) ?> Pegawai</span>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Pegawai</th>
              <th>Telepon</th>
              <th>Role</th>
              <th>Kapasitas (D/G)</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($staff as $s): ?>
            <tr>
              <td>
                <div class="user-cell">
                  <div class="user-avatar-sm">
                    <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                  </div>
                  <div>
                    <div class="user-name"><?= htmlspecialchars($s['nama']) ?></div>
                    <div class="user-email-sm"><?= htmlspecialchars($s['email']) ?></div>
                  </div>
                </div>
              </td>
              <td style="color:#64748b; font-size:15px;"><?= htmlspecialchars($s['telepon'] ?? '-') ?></td>
              <td>
                <span class="role-badge role-<?= $s['role'] ?>">
                  <?= str_replace('_', ' ', $s['role']) ?>
                </span>
              </td>
              <td>
                <?php if ($s['role'] === 'sopir'): ?>
                  <span style="font-weight: 700; color: #2563eb; font-size: 14px;">
                    <i class="fas fa-box"></i> <?= $s['max_dus'] ?> / <i class="fas fa-faucet-drip"></i> <?= $s['max_galon'] ?>
                  </span>
                <?php else: ?>
                  <span style="color: #64748b; font-size: 13px;">-</span>
                <?php endif; ?>
              </td>
              <td style="white-space: nowrap;">
                <button class="btn" style="background: #f1f5f9; color: #475569;" 
                        onclick='openEditModal(<?= json_encode($s) ?>)'>
                  <i class="fas fa-edit"></i> Edit
                </button>
                <?php if($s['id'] !== $user['id']): ?>
                  <a href="?delete=<?= $s['id'] ?>" class="btn btn-danger"
                     onclick="return confirm('Yakin hapus pegawai ini? Data tidak bisa dikembalikan.')">
                    <i class="fas fa-trash"></i> Hapus
                  </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- MODAL TAMBAH -->
<div id="modalAdd" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title">Tambah Pegawai Baru</div>
      <button class="modal-close" onclick="document.getElementById('modalAdd').style.display='none'">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <form method="POST">
      <input type="hidden" name="tambah_user" value="1">

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="nama" class="form-control" placeholder="Budi Santoso" required>
        </div>
        <div class="form-group">
          <label class="form-label">Telepon / WA</label>
          <input type="tel" name="telepon" class="form-control" placeholder="08xx-xxxx-xxxx" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="budi@alfaone.id" required>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Role / Posisi</label>
          <select name="role" class="form-control">
            <option value="admin_penjualan">Admin Penjualan</option>
            <option value="admin_barang">Admin Barang</option>
            <option value="sopir">Sopir</option>
            <option value="super_admin">Super Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Password Default</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
      </div>

      <div class="form-group" id="tambahPayloadFields" style="display:none;">
        <label class="form-label">Kapasitas Maksimal Muatan (Khusus Sopir)</label>
        <div class="form-grid">
          <div style="position:relative;">
            <input type="number" name="max_dus" class="form-control" placeholder="Maks Dus" min="0" value="0">
            <small style="font-size:10px; color:#64748b;">Maksimal Dus</small>
          </div>
          <div style="position:relative;">
            <input type="number" name="max_galon" class="form-control" placeholder="Maks Galon" min="0" value="0">
            <small style="font-size:10px; color:#64748b;">Maksimal Galon</small>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-control" placeholder="Jl. Contoh No. 1, Jakarta" required></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:16px; font-size:15px; border-radius:14px;">
        <i class="fas fa-save"></i> Simpan Pegawai
      </button>
    </form>
  </div>
</div>

<script>
  // Sidebar Toggle Logic
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
      document.body.style.overflow = '';
    });
  }

  // Show/Hide Payload fields based on role
  function togglePayloadFields(select) {
    const parent = select.closest('form');
    const payloadFields = parent.querySelector('#tambahPayloadFields') || parent.querySelector('#editPayloadFields');
    if (payloadFields) {
      payloadFields.style.display = select.value === 'sopir' ? 'block' : 'none';
    }
  }

  document.querySelectorAll('select[name="role"]').forEach(select => {
    select.addEventListener('change', function() {
      togglePayloadFields(this);
    });
  });

  // Modal Edit Logic
  function openEditModal(data) {
    const modal = document.getElementById('modalEdit');
    modal.querySelector('input[name="user_id"]').value = data.id;
    modal.querySelector('input[name="nama"]').value = data.nama;
    modal.querySelector('input[name="telepon"]').value = data.telepon;
    modal.querySelector('input[name="email"]').value = data.email;
    modal.querySelector('select[name="role"]').value = data.role;
    modal.querySelector('textarea[name="alamat"]').value = data.alamat;
    modal.querySelector('input[name="max_dus"]').value = data.max_dus;
    modal.querySelector('input[name="max_galon"]').value = data.max_galon;
    
    // Initial toggle for edit modal
    togglePayloadFields(modal.querySelector('select[name="role"]'));
    
    modal.style.display = 'flex';
  }

  // Close modals on backdrop click
  window.onclick = function(e) {
    if (e.target.classList.contains('modal')) {
      e.target.style.display = 'none';
    }
  };
</script>

<!-- MODAL EDIT -->
<div id="modalEdit" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title">Edit Data Pegawai</div>
      <button class="modal-close" onclick="document.getElementById('modalEdit').style.display='none'">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <form method="POST">
      <input type="hidden" name="edit_user" value="1">
      <input type="hidden" name="user_id" value="">

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Telepon / WA</label>
          <input type="tel" name="telepon" class="form-control" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Role / Posisi</label>
          <select name="role" class="form-control">
            <option value="admin_penjualan">Admin Penjualan</option>
            <option value="admin_barang">Admin Barang</option>
            <option value="sopir">Sopir</option>
            <option value="super_admin">Super Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Password Baru (Kosongkan jika tetap)</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••">
        </div>
      </div>

      <div class="form-group" id="editPayloadFields" style="display:none;">
        <label class="form-label">Kapasitas Maksimal Muatan (Khusus Sopir)</label>
        <div class="form-grid">
          <div style="position:relative;">
            <input type="number" name="max_dus" class="form-control" placeholder="Maks Dus" min="0">
            <small style="font-size:10px; color:#64748b;">Maksimal Dus</small>
          </div>
          <div style="position:relative;">
            <input type="number" name="max_galon" class="form-control" placeholder="Maks Galon" min="0">
            <small style="font-size:10px; color:#64748b;">Maksimal Galon</small>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-control" required></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:16px; font-size:15px; border-radius:14px;">
        <i class="fas fa-save"></i> Simpan Perubahan
      </button>
    </form>
  </div>
</div>
</body>
</html>