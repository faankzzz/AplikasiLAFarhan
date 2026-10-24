<?php
require_once 'config/database.php';
require_once 'config/auth.php';

if (isLoggedIn()) {
    redirect('konsumen/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $role_customer = $_POST['role_customer'] ?? 'umum';
    $alamat = trim($_POST['alamat'] ?? '');
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
    
    // Validasi
    if (empty($nama) || empty($email) || empty($password) || empty($alamat)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($password !== $password2) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Cek email duplikat
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar!';
        } else {
            $kartuMember = null;
            $suratUsaha = null;
            $fotoHalamanDepanToko = null;
            $statusVerif = 'verified';
            
            // Upload dokumen toko
            if ($role_customer === 'toko') {
                $statusVerif = 'pending';
                
                if (!empty($_FILES['kartu_member']['name'])) {
                    $kartuMember = uploadFile($_FILES['kartu_member'], 'kartu_member');
                }
                if (!empty($_FILES['surat_usaha']['name'])) {
                    $suratUsaha = uploadFile($_FILES['surat_usaha'], 'surat_usaha');
                }
                if (!empty($_FILES['halaman_depan_toko']['name'])) {
                    $fotoHalamanDepanToko = uploadFile($_FILES['halaman_depan_toko'], 'foto_toko');
                }
                
                if (!$kartuMember || !$suratUsaha || !$fotoHalamanDepanToko) {
                    $error = 'Upload Kartu Member, Surat Usaha, dan foto halaman depan toko wajib untuk akun Toko!';
                }
            }
            
            if (!$error) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (nama, email, telepon, password, role, role_customer, alamat, latitude, longitude, kartu_member, surat_usaha, halaman_depan_toko, status_verifikasi) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $nama, $email, $telepon, $hash, 'konsumen', $role_customer,
                    $alamat, $latitude ?: null, $longitude ?: null,
                    $kartuMember, $suratUsaha, $fotoHalamanDepanToko, $statusVerif
                ]);
                
                if ($role_customer === 'toko') {
                    $success = 'Pendaftaran Berhasil! Akun toko Anda sedang dalam proses verifikasi 1 x 24 jam kerja.';
                } else {
                    $success = 'Pendaftaran Berhasil! Silakan masuk ke akun Anda.';
                }
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
    <title>Daftar Akun — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places&callback=initMap" async defer></script>
    <style>
      :root { --primary: #3b82f6; --primary-dark: #1d4ed8; --bg: #d1e8f5; --surface: #e8f4fc; --text: #1e293b; --muted: #64748b; --border: #4a90c4; --radius: 20px; --grad-blue: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif; }
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: 'Inter', sans-serif; background: #d1e8f5; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 10px 20px; -webkit-font-smoothing: antialiased; line-height: 1.4; }
      .register-container { width: 100%; max-width: 800px; background: #c8e4f5; border-radius: 24px; padding: 32px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9; }
      .header { text-align: center; margin-bottom: 24px; }
      .header i { font-size: 32px; color: #3b82f6; margin-bottom: 12px; }
      .h-title { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 4px; letter-spacing: -1px; }
      .h-sub { color: #64748b; font-size: 14px; font-weight: 400; }
      .h-sub a { color: #3b82f6; text-decoration: none; font-weight: 800; }
      .form-group { margin-bottom: 16px; }
      .label { display: block; font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 1px; }
      .role-select { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
      .role-btn { border: 1px solid #4a90c4; border-radius: 16px; padding: 12px; cursor: pointer; text-align: center; transition: .3s; background: #f8fafc; }
      .role-btn i { font-size: 20px; margin-bottom: 4px; display: block; color: #94a3b8; }
      .role-btn span { display: block; font-weight: 700; font-size: 13px; font-family: 'Outfit', sans-serif; color: #1e293b; }
      .role-btn.active { border-color: #3b82f6; background: #eff6ff; }
      .role-btn.active i { color: #3b82f6; }
      .role-btn.active span { color: #3b82f6; }
      .input { width: 100%; padding: 10px 16px; border-radius: 12px; border: 1px solid #4a90c4; background: #f8fafc; font-family: inherit; font-size: 14px; font-weight: 600; outline: none; transition: .3s; }
      .input:focus { border-color: #3b82f6; background: #c8e4f5; box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.08); }
      .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
      .btn { padding: 14px; border-radius: 14px; border: none; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 15px; cursor: pointer; transition: .4s; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; }
      .btn-primary { background: #3b82f6; color: white; width: 100%; box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4); }
      .btn-primary:hover { background: #1d4ed8; transform: translateY(-3px); box-shadow: 0 15px 30px -10px rgba(59, 130, 246, 0.5); }
      .alert { padding: 12px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; font-weight: 600; border: 1px solid; display: flex; align-items: center; gap: 10px; }
      .alert-success { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
      .alert-danger { background: #fff1f2; color: #e11d48; border-color: #fee2e2; }
      #toko-info { display: none; background: #fffbeb; padding: 16px; border-radius: 16px; border: 1px solid #fef3c7; margin-top: 16px; }
      .toko-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
      @media (max-width: 640px) { 
        .register-container { padding: 24px 16px; border-radius: 0; box-shadow: none; border: none; } 
        .form-row { grid-template-columns: 1fr; gap: 0; } 
        .toko-grid { grid-template-columns: 1fr; }
        #map { height: 200px !important; }
        .h-title { font-size: 22px; }
        .header img { width: 140px; }
        .header i { display: none; }
      }
    </style>
</head>
<body>

<div class="register-container">
    <div class="header">
        <img src="assets/css/logoo.png" alt="ALFA ONE" style="width: 160px; height: auto; margin-bottom: 15px;">
        <h2 class="h-title">Daftar Akun Baru</h2>
        <p class="h-sub">Sudah terdaftar? <a href="login.php">Masuk Akun</a></p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= $success ?></div><?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="label">Pilih Jenis Akun</label>
            <div class="role-select">
                <div class="role-btn active" id="btn-umum" onclick="chooseRole('umum')">
                    <i class="fas fa-user-check"></i>
                    <span>Umum</span>
                </div>
                <div class="role-btn" id="btn-toko" onclick="chooseRole('toko')">
                    <i class="fas fa-store"></i>
                    <span>Bisnis/Toko</span>
                </div>
            </div>
            <input type="hidden" name="role_customer" id="role_input" value="umum">
        </div>

        <div class="form-group">
            <label class="label">Nama Lengkap</label>
            <input type="text" name="nama" class="input" placeholder="Masukkan nama Anda" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="label">Email</label>
                <input type="email" name="email" class="input" placeholder="email@contoh.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="label">Telepon/WA</label>
                <input type="tel" name="telepon" class="input" placeholder="08xxxxxxxx" value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="label">Kata sandi</label>
                <input type="password" name="password" class="input" placeholder="Min. 6 karakter" required>
            </div>
            <div class="form-group">
                <label class="label">Konfirmasi</label>
                <input type="password" name="password2" class="input" placeholder="Ulangi kata sandi" required>
            </div>
        </div>

        <div class="form-group" style="background: #ffffff; padding: 24px; border-radius: 24px; border: 1px solid #dbeafe; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03); margin-bottom: 24px;">
            <label class="label" style="font-size: 14px; margin-bottom: 12px; color: #0f172a;">
                <i class="fas fa-search" style="color:#3b82f6; margin-right: 6px;"></i> Cari Alamat Rumah / Toko
            </label>

            <!-- Search dengan autocomplete dropdown -->
            <div style="position: relative; margin-bottom: 15px;">
                <div style="display: flex; gap: 10px;">
                    <div style="position: relative; flex: 1;">
                        <input type="text" id="search-address" class="input" style="margin-bottom: 0;"
                            placeholder="Ketik nama jalan, kelurahan, atau daerah..." autocomplete="off">
                        <!-- Dropdown autocomplete -->
                        <div id="autocomplete-list" style="
                            display: none;
                            position: absolute;
                            top: 100%; left: 0; right: 0;
                            background: #ffffff;
                            border: 1px solid #4a90c4;
                            border-radius: 16px;
                            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15);
                            z-index: 9999;
                            max-height: 280px;
                            overflow-y: auto;
                            margin-top: 6px;
                        "></div>
                    </div>
                    <button type="button" onclick="searchLocation()" class="btn"
                        style="width: 50px; height: 50px; flex-shrink: 0; background: #3b82f6; color: white; border-radius: 12px; padding: 0;">
                        <i class="fas fa-magnifying-glass"></i>
                    </button>
                </div>
            </div>

            <!-- Tombol lokasi saya -->
            <button type="button" onclick="useMyLocation()" style="
                width: 100%; margin-bottom: 15px;
                padding: 12px; border-radius: 16px;
                border: 2px dashed #93c5fd;
                background: #eff6ff; color: #3b82f6;
                font-family: 'Outfit', sans-serif;
                font-weight: 700; font-size: 14px; cursor: pointer;
                transition: .3s; display: flex; align-items: center;
                justify-content: center; gap: 8px;">
                <i class="fas fa-crosshairs"></i>
                <span id="gps-btn-text">Gunakan Lokasi Saya (GPS)</span>
            </button>

            <!-- Peta -->
            <div style="border-radius: 20px; overflow: hidden; border: 1px solid #4a90c4;
                height: 280px; margin-bottom: 20px; z-index: 1;">
                <div id="map" style="width: 100%; height: 100%;"></div>
            </div>

            <input type="hidden" name="latitude" id="lat" value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
            <input type="hidden" name="longitude" id="lng" value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">

            <label class="label">Detail Alamat Lengkap / Patokan</label>
            <textarea name="alamat" id="alamat" class="input"
                style="height: 100px; resize: none;" required
                placeholder="Alamat akan terisi otomatis saat memilih lokasi di peta..."><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>

                <div id="toko-info">
                    <div style="background: #eff6ff; padding: 16px; border-radius: 16px; border: 1px solid #dbeafe; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                        <div>
                            <p style="font-size: 13px; color: #1e40af; font-weight: 700; margin-bottom: 2px;">
                                <i class="fas fa-id-card"></i> Syarat Akun Toko
                            </p>
                            <p style="font-size: 11px; color: #60a5fa; line-height: 1.3;">
                                Wajib memiliki <b>Kartu Member Digital</b> resmi Alfa One.
                            </p>
                        </div>
                        <a href="buat_kartu.php" target="_blank" class="btn" style="background: #c8e4f5; color: #3b82f6; border: 1px solid #3b82f6; padding: 8px 12px; font-size: 11px; width: auto; box-shadow: none;"> 
                            BUAT KARTU <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    
                    <div class="toko-grid">
                        <div class="form-group">
                            <label class="label">Unggah Kartu Member</label>
                            <input type="file" name="kartu_member" class="input" accept="image/*" style="padding: 10px;">
                        </div>
                        <div class="form-group">
                            <label class="label">Unggah Surat Usaha</label>
                            <input type="file" name="surat_usaha" class="input" accept=".pdf,image/*" style="padding: 10px;">
                        </div>
                        <div class="form-group">
                            <label class="label">Foto Halaman Depan Toko</label>
                            <input type="file" name="halaman_depan_toko" class="input" accept="image/*" style="padding: 10px;">
                        </div>
                    </div>
                </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 10px;">DAFTAR SEKARANG <i class="fas fa-paper-plane"></i></button>
    </form>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" style="color:#64748b; font-size: 14px; font-weight: 600; text-decoration: none;"><i class="fas fa-arrow-left" style="margin-right: 6px;"></i> Ke Beranda</a>
    </div>
</div>


<script>
    function chooseRole(role) {
        document.getElementById('role_input').value = role;
        const btnUmum = document.getElementById('btn-umum');
        const btnToko = document.getElementById('btn-toko');
        const tokoInfo = document.getElementById('toko-info');

        if (role === 'umum') {
            btnUmum.classList.add('active');
            btnToko.classList.remove('active');
            tokoInfo.style.display = 'none';
        } else {
            btnUmum.classList.remove('active');
            btnToko.classList.add('active');
            tokoInfo.style.display = 'block';
        }
    }

    // =============================================
    //  MAP INITIALIZATION & GOOGLE MAPS API LOGIC
    // =============================================
    let map, marker, geocoder, autocomplete;
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const alamatInput = document.getElementById('alamat');

    let defaultLat = latInput.value ? parseFloat(latInput.value) : -2.990494; // Palembang
    let defaultLng = lngInput.value ? parseFloat(lngInput.value) : 104.756534; // Palembang

    function initMap() {
        geocoder = new google.maps.Geocoder();
        const initialLocation = { lat: defaultLat, lng: defaultLng };

        map = new google.maps.Map(document.getElementById('map'), {
            center: initialLocation,
            zoom: 14,
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

        marker.addListener('dragend', function() {
            const coords = marker.getPosition();
            updateInputs(coords.lat().toFixed(7), coords.lng().toFixed(7));
            reverseGeocode(coords);
        });

        map.addListener('click', function(e) {
            marker.setPosition(e.latLng);
            updateInputs(e.latLng.lat().toFixed(7), e.latLng.lng().toFixed(7));
            reverseGeocode(e.latLng);
        });

        initAutocomplete();
    }

    function updateInputs(lat, lng) {
        latInput.value = lat;
        lngInput.value = lng;
    }

    function reverseGeocode(latLng) {
        alamatInput.placeholder = 'Mencari alamat...';
        geocoder.geocode({ location: latLng }, function(results, status) {
            if (status === 'OK') {
                if (results[0]) {
                    alamatInput.value = results[0].formatted_address;
                    alamatInput.placeholder = 'Alamat lengkap...';
                }
            } else {
                alamatInput.placeholder = 'Gagal ambil alamat, isi manual.';
            }
        });
    }

    function initAutocomplete() {
        const searchInput = document.getElementById('search-address');
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
            updateInputs(place.geometry.location.lat().toFixed(7), place.geometry.location.lng().toFixed(7));

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
        let query = searchInput.value.trim();
        if (query.length < 3) { alert('Ketik minimal 3 huruf...'); return; }
        if (!query.toLowerCase().includes('palembang')) query += ' Palembang';

        geocoder.geocode({ address: query }, function(results, status) {
            if (status === 'OK') {
                const location = results[0].geometry.location;
                map.setCenter(location);
                map.setZoom(18);
                marker.setPosition(location);
                updateInputs(location.lat().toFixed(7), location.lng().toFixed(7));
                alamatInput.value = results[0].formatted_address;
            } else {
                alert('Lokasi tidak ditemukan di Palembang.');
            }
        });
    }

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
                marker.setPosition(userLatLng);
                updateInputs(lat.toFixed(7), lng.toFixed(7));
                reverseGeocode(userLatLng);

                btn.innerHTML = '<i class="fas fa-check"></i> Lokasi ditemukan!';
                setTimeout(() => {
                    btn.innerHTML = 'Gunakan Lokasi Saya (GPS)';
                }, 3000);
            },
            function (err) {
                btn.innerHTML = 'Gunakan Lokasi Saya (GPS)';
                alert('GPS gagal: ' + err.message);
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    <?php if (isset($_POST['role_customer']) && $_POST['role_customer'] === 'toko'): ?>
        chooseRole('toko');
    <?php endif; ?>
</script>

</body>
</html>
