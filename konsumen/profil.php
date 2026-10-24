<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('konsumen');
$user = getCurrentUser($pdo);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $no_wa = trim($_POST['no_wa'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
        if (empty($nama) || empty($email)) {
            $error = 'Nama dan Email wajib diisi!';
        } else {
            // Cek email duplikat
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error = 'Email sudah digunakan oleh akun lain!';
            } else {
                $foto_path = $user['foto'];
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $new_foto = uploadFile($_FILES['foto'], 'profiles');
                    if ($new_foto) {
                        if (!empty($user['foto'])) {
                            @unlink(UPLOAD_DIR . $user['foto']);
                        }
                        $foto_path = $new_foto;
                    } else {
                        $error = 'Format file tidak didukung atau upload gagal.';
                    }
                }

                if (!$error) {
                    $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, telepon = ?, no_wa = ?, alamat = ?, latitude = ?, longitude = ?, foto = ? WHERE id = ?");
                    if ($stmt->execute([$nama, $email, $telepon, $no_wa, $alamat, $latitude, $longitude, $foto_path, $user['id']])) {
                        $success = 'Profil berhasil diperbarui!';
                        $user['nama'] = $nama;
                        $user['email'] = $email;
                        $user['telepon'] = $telepon;
                        $user['no_wa'] = $no_wa;
                        $user['alamat'] = $alamat;
                        $user['latitude'] = $latitude;
                        $user['longitude'] = $longitude;
                        $user['foto'] = $foto_path;
                        $_SESSION['nama'] = $nama;
                    } else {
                        $error = 'Gagal memperbarui profil.';
                    }
                }
            }
        }
    } elseif (isset($_POST['update_password'])) {
        $old_pwd = $_POST['old_pwd'] ?? '';
        $new_pwd = $_POST['new_pwd'] ?? '';
        $confirm_pwd = $_POST['confirm_pwd'] ?? '';

        if (empty($old_pwd) || empty($new_pwd) || empty($confirm_pwd)) {
            $error = 'Semua field kata sandi wajib diisi!';
        } elseif (!password_verify($old_pwd, $user['password'])) {
            $error = 'Kata sandi lama salah!';
        } elseif (strlen($new_pwd) < 6) {
            $error = 'Kata sandi baru minimal 6 karakter!';
        } elseif ($new_pwd !== $confirm_pwd) {
            $error = 'Konfirmasi kata sandi baru tidak cocok!';
        } else {
            $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hash, $user['id']])) {
                $success = 'Kata sandi berhasil diubah! Silakan gunakan kata sandi baru untuk akses berikutnya.';
                $user['password'] = $hash;
            } else {
                $error = 'Gagal mengubah kata sandi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Akun — Konsumen</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places&callback=initMap" async defer></script>
<style>
:root{
    --primary:#1e40af;
    --primary-dark:#1e3a8a;
    --bg:#dbeefe;
    --surface:#f4f9fd;
    --card:#ffffff;
    --text:#0f172a;
    --muted:#64748b;
    --border:#cbd5e1;

    --sidebar-bg:#0f172a;
    --sidebar-hover:rgba(255,255,255,.08);
    --sidebar-text:#f8fafc;
    --sidebar-muted:#94a3b8;

    --success-bg:#f0fdf4;
    --success-text:#15803d;

    --danger-bg:#fef2f2;
    --danger-text:#dc2626;

    --shadow:0 10px 30px rgba(15,23,42,.06);
    --shadow-hover:0 18px 40px rgba(15,23,42,.12);

    --radius-xl:32px;
    --radius-lg:22px;
    --radius-md:16px;

    --grad-blue:linear-gradient(135deg,#2563eb 0%,#1e3a8a 100%);

    --font-heading:'Outfit',sans-serif;
    --font-body:'Inter',sans-serif;
}

/* RESET */
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
    background:#dbeefe;
    color:#0f172a;
    min-height:100vh;
    overflow-x:hidden;
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
    top:0;
    left:0;
    height:100vh;
    z-index:1000;
    display:flex;
    flex-direction:column;
    border-right:1px solid rgba(255,255,255,.05);
    transition:.3s ease;
}

.sidebar-logo{
    padding:24px;
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
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.08);
    color:white;
    font-size:28px;
    margin-bottom:14px;
    border:1px solid rgba(255,255,255,.08);
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
    color:#38bdf8;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
}

.sidebar-nav{
    padding:24px 16px;
    flex:1;
}

.nav-label{
    margin:20px 16px 8px;
    font-size:11px;
    color:#94a3b8;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:1.5px;
}

.nav-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 16px;
    margin-bottom:6px;
    border-radius:18px;
    color:#94a3b8;
    text-decoration:none;
    font-size:15px;
    font-weight:600;
    transition:.25s ease;
}

.nav-item:hover{
    background:rgba(255,255,255,.08);
    color:white;
    transform:translateX(3px);
}

.nav-item.active{
    background:linear-gradient(135deg,#2563eb 0%,#1e3a8a 100%);
    color:white;
    box-shadow:0 10px 20px rgba(37,99,235,.35);
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
    height:80px;
    background:rgba(255,255,255,.7);
    backdrop-filter:blur(14px);
    border-bottom:1px solid #cbd5e1;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 36px;
    position:sticky;
    top:0;
    z-index:900;
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
    background:white;
    padding:8px 16px;
    border-radius:999px;
    border:1px solid #cbd5e1;
    box-shadow:0 10px 30px rgba(15,23,42,.06);
}

.user-avatar{
    width:38px;
    height:38px;
    border-radius:12px;
    overflow:hidden;
    background:#1e40af;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
    font-weight:700;
}

.user-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

/* CONTENT */
.content{
    padding:36px;
    display:flex;
    flex-direction:column;
    gap:28px;
}

/* CARD */
.card{
    background:rgba(255,255,255,.75);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.6);
    border-radius:32px;
    padding:32px;
    box-shadow:0 10px 30px rgba(15,23,42,.06);
    transition:.3s ease;
}

.card:hover{
    box-shadow:0 18px 40px rgba(15,23,42,.12);
}

.card-title{
    font-family:'Outfit',sans-serif;
    font-size:20px;
    font-weight:800;
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:28px;
    padding-bottom:18px;
    border-bottom:1px dashed #dbe4ef;
}

.card-title i{
    color:#1e40af;
}

/* FORM */
.form-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:22px;
}

.form-group{
    margin-bottom:22px;
}

.label,
.field-label{
    display:block;
    margin-bottom:10px;
    font-size:12px;
    font-weight:800;
    color:#64748b;
    text-transform:uppercase;
    letter-spacing:1px;
}

.input{
    width:100%;
    padding:15px 18px;
    border-radius:18px;
    border:1px solid #cbd5e1;
    background:white;
    font-family:inherit;
    font-size:15px;
    font-weight:600;
    color:#0f172a;
    outline:none;
    transition:.25s ease;
}

.input:focus{
    border-color:#3b82f6;
    box-shadow:0 0 0 5px rgba(59,130,246,.12);
    transform:translateY(-1px);
}

/* BUTTON */
.btn{
    border:none;
    cursor:pointer;
    border-radius:18px;
    padding:15px 26px;
    font-size:15px;
    font-weight:700;
    font-family:'Outfit',sans-serif;
    transition:.25s ease;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
}

.btn-primary{
    background: #1e40af;
    color:white;
    box-shadow:0 12px 24px rgba(37,99,235,.25);
}

.btn-primary:hover{
    background: #1e3a8a; transform:translateY(-3px);
    box-shadow:0 18px 30px rgba(37,99,235,.35);
}

/* ALERT */
.alert{
    padding:18px 20px;
    border-radius:20px;
    display:flex;
    align-items:center;
    gap:12px;
    font-size:14px;
    font-weight:700;
    border:1px solid transparent;
}

.alert-success{
    background:#f0fdf4;
    color:#15803d;
    border-color:#bbf7d0;
}

.alert-danger{
    background:#fef2f2;
    color:#dc2626;
    border-color:#fecaca;
}

/* MAP */
#map-container{
    width:100%;
    height:320px;
    border-radius:24px;
    overflow:hidden;
    border:1px solid #cbd5e1;
    box-shadow:0 10px 30px rgba(15,23,42,.06);
}

/* MOBILE */
.hamburger{
    display:none;
}

.sidebar-overlay{
    display:none;
}

@media(max-width:1024px){

    .sidebar{
        transform:translateX(-100%);
        box-shadow:20px 0 50px rgba(0,0,0,.2);
    }

    .sidebar.active{
        transform:translateX(0);
    }

    .sidebar-overlay{
        position:fixed;
        inset:0;
        background:rgba(15,23,42,.55);
        backdrop-filter:blur(4px);
        z-index:950;
    }

    .sidebar-overlay.active{
        display:block;
    }

    .main{
        margin-left:0;
    }

    .topbar{
        padding:14px 20px;
        height:auto;
        min-height:80px;
    }

    .content{
        padding:20px;
    }

    .card{
        padding:24px 18px;
        border-radius:26px;
    }

    .form-row{
        grid-template-columns:1fr;
        gap:0;
    }

    .user-pill{
        display:none;
    }

    .hamburger{
        width:42px;
        height:42px;
        border-radius:14px;
        background:white;
        border:1px solid #cbd5e1;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        font-size:18px;
        color:#0f172a;
        box-shadow:0 10px 30px rgba(15,23,42,.06);
    }

    .avatar-sec{
        flex-direction:column;
        text-align:center;
        align-items:center !important;
    }

    #map-container{
        height:250px;
    }

    .btn{
        width:100%;
    }
}
</style>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Profil — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAjAySxEH3JC2BV_pDHddk4rPxfI8klhKs&libraries=places&callback=initMap" async defer></script>
    <style>
/* CSS Styles remain unchanged... */
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
            <a href="komplain.php" class="nav-item "><i class="fas fa-headset nav-icon"></i> Pusat Bantuan</a>
            
            <div class="nav-label">Akun</div>
            <a href="profil.php" class="nav-item active"><i class="fas fa-user-circle nav-icon"></i> Profil Akun</a>
            <a href="../logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt nav-icon"></i> Keluar</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="hamburger" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="topbar-title"><h2>Pengaturan Profil</h2></div>
            </div>
            <div class="user-pill">
                <div style="text-align: right;">
                    <div style="font-weight: 800; font-size: 13px;"><?= htmlspecialchars($user['nama']) ?></div>
                    <div style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Member <?= ucfirst($user['role_customer']) ?></div>
                </div>
                <div class="user-avatar">
                    <?php if (!empty($user['foto'])): ?>
                        <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div>
            <?php endif; ?>

            <div class="card">
                <h3 class="card-title"><i class="fas fa-user-gear"></i> Informasi Dasar Profil</h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="avatar-sec" style="display: flex; align-items: center; gap: 24px; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 2px dashed #f1f5f9;">
                        <div class="user-avatar" style="width: 80px; height: 80px; border-radius: 20px; font-size: 32px; background: rgba(30, 64, 175, 0.1); color: #1e40af;">
                            <?php if (!empty($user['foto'])): ?>
                                <img src="<?= getUploadUrl($user['foto']) ?>" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <label class="field-label">Foto Profil</label>
                            <input type="file" name="foto" accept="image/*" style="font-size: 13px;">
                            <p style="font-size: 11px; color: #64748b; margin-top: 5px;">Format: JPG, PNG, WebP. Maks 2MB.</p>
                        </div>
                    </div>
                    <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label class="label">Nama Lengkap</label>
                            <input type="text" name="nama" class="input" value="<?= htmlspecialchars($user['nama']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="label">Nomor Telepon</label>
                            <input type="text" name="telepon" class="input" value="<?= htmlspecialchars($user['telepon'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="label">Nomor WhatsApp (Untuk Notifikasi)</label>
                            <input type="text" name="no_wa" class="input" value="<?= htmlspecialchars($user['no_wa'] ?? '') ?>" placeholder="Kosongkan jika sama dengan No. Telepon">
                        </div>
                        <div class="form-group">
                            <label class="label">Email</label>
                            <input type="email" name="email" class="input" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>
                    
                    <h4 style="font-family: 'Outfit',sans-serif; font-size: 16px; font-weight: 800; color: #0f172a; margin: 32px 0 16px; padding-top: 24px; border-top: 2px dashed #f1f5f9;"><i class="fas fa-map-location-dot" style="color: #1e40af; margin-right: 8px;"></i> Lokasi Default Konsumen</h4>
                    
                    <label class="label" style="color:#0f172a;">
                        <i class="fas fa-search" style="color:#1e40af;"></i> Cari Alamat Anda
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
                                    border: 1px solid #cbd5e1;
                                    border-radius: 16px;
                                    box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15);
                                    z-index: 9999;
                                    max-height: 280px;
                                    overflow-y: auto;
                                    margin-top: 6px;
                                "></div>
                            </div>
                            <button type="button" onclick="searchLocation()" class="btn btn-primary"
                                style="width: 50px; height: 50px; flex-shrink: 0; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 16px;">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Tombol lokasi saya -->
                    <button type="button" onclick="useMyLocation()" style="
                        width: 100%; margin-bottom: 20px;
                        padding: 12px; border-radius: 16px;
                        border: 2px dashed #dbeafe;
                        background: #eff6ff; color: #1e40af;
                        font-weight: 700; font-size: 14px; cursor: pointer;
                        transition: .3s; display: flex; align-items: center;
                        justify-content: center; gap: 8px;">
                        <i class="fas fa-crosshairs"></i>
                        <span id="gps-btn-text">Gunakan Lokasi Saya (GPS)</span>
                    </button>
                    
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Atau geser pin merah pada peta di bawah ini ke lokasi persis rumah/toko Anda.</p>
                    
                    <div id="map-container" style="border-radius: 20px; overflow: hidden; border: 1px solid #cbd5e1; height: 320px; margin-bottom: 20px; z-index: 1;">
                        <div id="map" style="width: 100%; height: 100%;"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="label">Latitude (Otomatis)</label>
                            <input type="text" name="latitude" id="lat" class="input" value="<?= htmlspecialchars($user['latitude'] ?? '') ?>" readonly style="background: #e2e8f0;">
                        </div>
                        <div class="form-group">
                            <label class="label">Longitude (Otomatis)</label>
                            <input type="text" name="longitude" id="lng" class="input" value="<?= htmlspecialchars($user['longitude'] ?? '') ?>" readonly style="background: #e2e8f0;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="label">Detail Alamat Lengkap / Patokan Jalan</label>
                        <textarea name="alamat" id="alamat" class="input" style="height: 100px; resize: none;" placeholder="Contoh: Jl. Sudirman No. 10, Pagar Hitam (Samping minimarket)" required><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-floppy-disk"></i> Simpan Data & Titik Lokasi</button>
                </form>
            </div>

            <div class="card">
                <h3 class="card-title"><i class="fas fa-lock"></i> Ubah Kata Sandi</h3>
                <form action="" method="POST">
                    <input type="hidden" name="update_password" value="1">
                    <div class="form-group">
                        <label class="label">Kata Sandi Sebelumnya</label>
                        <input type="password" name="old_pwd" class="input" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="label">Kata Sandi Baru</label>
                            <input type="password" name="new_pwd" class="input" placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="form-group">
                            <label class="label">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="confirm_pwd" class="input" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="background: #0f172a; box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.3);"><i class="fas fa-key"></i> Ganti Kata Sandi</button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    let map, marker, geocoder, autocomplete;
    let lat = <?= $user['latitude'] ? $user['latitude'] : '-2.990494' ?>; // Default Palembang
    let lng = <?= $user['longitude'] ? $user['longitude'] : '104.756534' ?>;

    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const alamatInput = document.getElementById('alamat');

    function initMap() {
        geocoder = new google.maps.Geocoder();
        const initialLocation = { lat: lat, lng: lng };

        map = new google.maps.Map(document.getElementById('map'), {
            center: initialLocation,
            zoom: 15,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });

        marker = new google.maps.Marker({
            position: initialLocation,
            map: map,
            draggable: true,
            title: "Lokasi Anda"
        });

        <?php if (empty($user['latitude'])): ?>
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const pos = { lat: position.coords.latitude, lng: position.coords.longitude };
                map.setCenter(pos);
                map.setZoom(16);
                marker.setPosition(pos);
                updateLocationInfo(pos.lat.toFixed(7), pos.lng.toFixed(7));
                reverseGeocode(pos);
            });
        }
        <?php endif; ?>

        marker.addListener('dragend', function() {
            const coords = marker.getPosition();
            updateLocationInfo(coords.lat().toFixed(7), coords.lng().toFixed(7));
            reverseGeocode(coords);
        });

        map.addListener('click', function(e) {
            marker.setPosition(e.latLng);
            updateLocationInfo(e.latLng.lat().toFixed(7), e.latLng.lng().toFixed(7));
            reverseGeocode(e.latLng);
        });

        initAutocomplete();
    }

    function updateLocationInfo(latitude, longitude) {
        latInput.value = latitude;
        lngInput.value = longitude;
    }

    function reverseGeocode(latLng) {
        alamatInput.placeholder = 'Mencari alamat...';
        geocoder.geocode({ location: latLng }, function(results, status) {
            if (status === 'OK') {
                if (results[0]) {
                    alamatInput.value = results[0].formatted_address;
                    alamatInput.placeholder = 'Contoh: Jl. Sudirman No. 10...';
                }
            } else {
                alamatInput.placeholder = 'Gagal ambil alamat, isi manual.';
            }
        });
    }

    function initAutocomplete() {
        const searchInput = document.getElementById('search-address');
        if (!searchInput) return;

        autocomplete = new google.maps.places.Autocomplete(searchInput, {
            componentRestrictions: { country: 'id' },
            fields: ['geometry', 'formatted_address', 'name']
        });

        autocomplete.bindTo('bounds', map);

        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (!place.geometry || !place.geometry.location) {
                alert('Lokasi tidak detail, silakan cari lokasi lain.');
                return;
            }

            map.setCenter(place.geometry.location);
            map.setZoom(18);
            marker.setPosition(place.geometry.location);
            updateLocationInfo(place.geometry.location.lat().toFixed(7), place.geometry.location.lng().toFixed(7));

            searchInput.value = place.name;
            alamatInput.value = place.formatted_address;
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    }

    function searchLocation() {
        const searchInput = document.getElementById('search-address');
        if (!searchInput) return;
        let query = searchInput.value.trim();
        if (query.length < 3) { alert('Ketik minimal 3 huruf...'); return; }
        if (!query.toLowerCase().includes('palembang')) query += ' Palembang';

        geocoder.geocode({ address: query }, function(results, status) {
            if (status === 'OK') {
                const location = results[0].geometry.location;
                map.setCenter(location);
                map.setZoom(18);
                marker.setPosition(location);
                updateLocationInfo(location.lat().toFixed(7), location.lng().toFixed(7));
                alamatInput.value = results[0].formatted_address;
            } else {
                alert('Lokasi tidak ditemukan di Palembang.');
            }
        });
    }

    function useMyLocation() {
        const btnText = document.getElementById('gps-btn-text');
        if (!navigator.geolocation) { alert('Browser tidak mendukung GPS.'); return; }
        btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari lokasi...';

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const latVal = position.coords.latitude;
                const lngVal = position.coords.longitude;
                const userLatLng = { lat: latVal, lng: lngVal };

                map.setCenter(userLatLng);
                map.setZoom(18);
                marker.setPosition(userLatLng);
                updateLocationInfo(latVal.toFixed(7), lngVal.toFixed(7));
                reverseGeocode(userLatLng);
                btnText.innerHTML = 'Gunakan Lokasi Saya (GPS)';
            },
            function (error) {
                alert('Gagal mendapatkan lokasi. Pastikan izin GPS aktif.');
                btnText.innerHTML = 'Gunakan Lokasi Saya (GPS)';
            }
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
</script>
</body>
</html>
