<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// ============================================
// AUTO-DETECT ENVIRONMENT (LOKAL / INFINITYFREE)
// ============================================
// Deteksi apakah sedang jalan di localhost (XAMPP) atau di hosting InfinityFree
// Tidak perlu ubah-ubah manual, cukup upload apa adanya!

$is_localhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']) 
                || (($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1')
                || (($_SERVER['REMOTE_ADDR'] ?? '') === '::1');

if ($is_localhost) {
    // ==========================================
    // KONFIGURASI LOKAL (XAMPP)
    // ==========================================
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'bismillah_la');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    
    // Deteksi BASE_URL otomatis agar jalan di folder AplikasiLAFarhan maupun root (htdocs)
    if (strpos($_SERVER['REQUEST_URI'], '/AplikasiLAFarhan/') === 0) {
        define('BASE_URL', '/AplikasiLAFarhan/');
    } else {
        define('BASE_URL', '/');
    }
} else {
    // ==========================================
    // KONFIGURASI INFINITYFREE (HOSTING)
    // ==========================================
    // PENTING: Ganti nilai-nilai di bawah ini sesuai data dari panel InfinityFree Anda!
    // Anda bisa lihat informasi ini di: InfinityFree Panel > MySQL Databases
    define('DB_HOST', 'sql305.infinityfree.com');          // Hostname MySQL dari InfinityFree
    define('DB_NAME', 'if0_42251662_bismillah_la');        // Nama database (awalan if0_xxxxxxxx_)
    define('DB_USER', 'if0_42251662');                     // Username MySQL
    define('DB_PASS', '1CU7l7j7JxCHnO');                   // Password MySQL
    define('BASE_URL', '/');                               // Di hosting, root langsung '/'
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Koordinat Gudang
define('GUDANG_LAT', -2.928755470243505);
define('GUDANG_LNG', 104.6849743882116);
define('GUDANG_ALAMAT', 'Jl.Soekarno Hatta Alang-alang Lebar, Palembang');
define('GOOGLE_MAPS_API_KEY', 'AIzaSyAjAySxEH3JC2BV_pDHddk4rPxfI8klhKs');

// ============================================
// HELPER FUNCTIONS
// ============================================

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? '';
}

function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function flash($key, $message = null) {
    if ($message) {
        $_SESSION['flash'][$key] = $message;
    } else {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggalIndo($tanggal, $showTime = true) {
    if (!$tanggal) return '-';
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    $time = strtotime($tanggal);
    $h = $hari[date('w', $time)];
    $d = date('j', $time);
    $m = $bulan[date('n', $time)];
    $y = date('Y', $time);
    $i = date('H:i', $time);
    
    $dateStr = "$h, $d $m $y";
    return $showTime ? "$dateStr • $i" : $dateStr;
}

function uploadFile($file, $folder = 'general') {
    $targetDir = UPLOAD_DIR . $folder . '/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','pdf'];
    if (!in_array($ext, $allowed)) return false;
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $folder . '/' . $filename;
    }
    return false;
}

function getUploadUrl($path, $folder = null) {
    if (!$path) return BASE_URL . 'assets/img/no-image.png';
    return BASE_URL . 'uploads/' . $path;
}

// Haversine formula - hitung jarak (km)
function haversine($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($earthRadius * $c, 2);
}

// Hitung sudut/arah dari titik 1 ke titik 2 (0-360 derajat)
function calculateBearing($lat1, $lng1, $lat2, $lng2) {
    if (!$lat1 || !$lng1 || !$lat2 || !$lng2) return 0;
    
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);

    $dLng = $lng2 - $lng1;
    $y = sin($dLng) * cos($lat2);
    $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dLng);
    $bearing = atan2($y, $x);
    $bearing = rad2deg($bearing);
    return round(($bearing + 360) % 360, 2);
}

function getStatusBadge($status) {
    $labels = [
        'menunggu' => 'Menunggu',
        'diproses' => 'Diproses',
        'siap_dikirim' => 'Siap Kirim',
        'dikirim' => 'Dikirim',
        'selesai' => 'Selesai'
    ];
    
    $colors = [
        'menunggu' => 'background: #eff6ff; color: #3b82f6; border: 1px solid #dbeafe;',
        'diproses' => 'background: #b8d8ed; color: #1d4ed8; border: 1px solid #bfdbfe;',
        'siap_dikirim' => 'background: #ecfeff; color: #0891b2; border: 1px solid #cffafe;',
        'dikirim' => 'background: #eef2ff; color: #4338ca; border: 1px solid #e0e7ff;',
        'selesai' => 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;'
    ];
    
    $style = $colors[$status] ?? 'background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;';
    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    
    return '<span class="badge badge-' . $status . '" style="padding: 6px 14px; border-radius: 50px; font-size: 10px; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px; ' . $style . '">' . $label . '</span>';
}

function getVerifBadge($status) {
    $labels = [
        'pending' => 'Pending',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak'
    ];
    
    $colors = [
        'pending' => 'background: #fffbeb; color: #d97706; border: 1px solid #fef3c7;',
        'verified' => 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;',
        'rejected' => 'background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2;'
    ];
    
    $style = $colors[$status] ?? 'background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;';
    $label = $labels[$status] ?? ucfirst($status);
    
    return '<span class="badge badge-' . $status . '" style="padding: 6px 14px; border-radius: 50px; font-size: 10px; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px; ' . $style . '">' . $label . '</span>';
}
