<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('super_admin');
$user = getCurrentUser($pdo);

/**
 * PERBAIKAN QUERY:
 * 1. Menambahkan status 'menunggu' agar bisa dipantau sejak awal.
 * 2. Menggunakan NULLIF untuk memastikan jika string kosong di database dianggap NULL, 
 *    sehingga COALESCE bisa mengambil data cadangan dari tabel users.
 */
$stmt = $pdo->query("SELECT p.*, u.nama as konsumen_nama, u.telepon, 
    p.created_at as tgl_pesanan,
    COALESCE(NULLIF(NULLIF(p.alamat_pengiriman, ''), '0'), u.alamat) as alamat, 
    COALESCE(NULLIF(NULLIF(p.latitude_pengiriman, ''), '0.00000000'), u.latitude) as latitude, 
    COALESCE(NULLIF(NULLIF(p.longitude_pengiriman, ''), '0.00000000'), u.longitude) as longitude,
    u2.nama as sopir_nama, u2.telepon as sopir_telp
    FROM pesanan p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN users u2 ON p.sopir_id = u2.id
    WHERE p.status IN ('menunggu', 'dikirim', 'selesai')
    ORDER BY p.updated_at DESC");
$deliveryList = $stmt->fetchAll();

// Statistik Pengiriman
$activeDeliveries = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status = 'dikirim'")->fetchColumn();
$completedToday = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status = 'selesai' AND DATE(updated_at) = CURDATE()")->fetchColumn();
$totalCompleted = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status = 'selesai'")->fetchColumn();

// Data untuk Peta (Hanya yang punya koordinat)
$markers = [];
foreach ($deliveryList as $d) {
    if (!empty($d['latitude']) && !empty($d['longitude']) && (float)$d['latitude'] !== 0.0) {
        $markers[] = [
            'id' => $d['id'],
            'lat' => (float)$d['latitude'],
            'lng' => (float)$d['longitude'],
            'nama' => $d['konsumen_nama'],
            'status' => $d['status'],
            'sopir' => $d['sopir_nama'] ?? 'Belum Ditugaskan'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Pengiriman — Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
      :root {
        --primary: #2563eb; --bg: #f0f6fa; --surface: #ffffff; --text: #1e293b; --muted: #64748b; --border: rgba(59, 130, 246, 0.12);
        --sidebar-bg: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); --sidebar-text: #f8fafc; --sidebar-muted: #94a3b8;
        --grad-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
        --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif;
      }
      * { margin:0; padding:0; box-sizing:border-box; }
      body { font-family: 'Inter', sans-serif; background:#f0f6fa; color:#1e293b; min-height:100vh; overflow-x:hidden; }
      
      /* SIDEBAR */
      .sidebar { width:280px; background:linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; position: fixed; top:0; left:0; height: 100vh; z-index: 50; overflow-y: auto; }
      .sidebar-logo { display:flex; align-items:center; justify-content:center; padding:20px 24px; border-bottom:1px solid rgba(255,255,255,0.05); }
      .sidebar-profile { padding:24px 32px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; align-items:center; gap:12px; text-align:center; }
      .sidebar-avatar { width:64px; height:64px; border-radius:20px; background: rgba(255,255,255,0.08); color:white; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:800; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15); overflow:hidden; }
      .sidebar-avatar img { width:100%; height:100%; object-fit:cover; }
      .sidebar-user-info { display:flex; flex-direction:column; gap:4px; }
      .sidebar-user-name { font-weight:800; font-size:15px; color:white; }
      .sidebar-user-role { font-size:11px; font-weight:800; color: #38bdf8; text-transform:uppercase; letter-spacing:0.5px; }
      .sidebar-nav { padding:24px 16px; flex:1; }
      .nav-label { font-size:11px; color: #38bdf8; text-transform:uppercase; letter-spacing:.15em; margin:20px 16px 8px; font-weight:800; }
      .nav-item { display:flex; align-items:center; gap:16px; padding: 14px 18px; border-radius: 20px; color: #94a3b8; font-size:15px; transition: .3s ease; text-decoration:none; font-weight: 700; margin-bottom: 6px; }
      .nav-item i { font-size:18px; width:22px; text-align:center; }
      .nav-item:hover { background:rgba(255,255,255,0.05); color:white; }
      .nav-item.active { background:linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color:white; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }

      /* MAIN CONTENT */
      .main { flex:1; margin-left: 280px; display: flex; flex-direction: column; min-height: 100vh; }
      .topbar { height: 80px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); border-bottom: 2px solid rgba(56, 189, 248, 0.4); position: sticky; top: 0; z-index: 10; }
      .content { padding: 40px; width: 100%; }
      
      /* STATS */
      .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 40px; }
      .stat-card { background: #ffffff; border-radius: 40px; padding: 32px; border: 1px solid rgba(59, 130, 246, 0.12); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.02); transition: .4s; text-align: center; }
      .stat-icon { width: 64px; height: 64px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px; }
      .stat-icon.primary { background: #eff6ff; color: #2563eb; }
      .stat-icon.truck { background: #f0fdf4; color: #059669; }
      .stat-icon.warning { background: #fef3c7; color: #d97706; }
      .stat-val { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 800; color: #0f172a; }

      /* MAP & TABLE */
      .map-card { background: #ffffff; border-radius: 40px; height: 500px; border: 1px solid rgba(59, 130, 246, 0.12); overflow: hidden; margin-bottom: 40px; }
      #delivery-map { width: 100%; height: 100%; }
      .section-card { background: #ffffff; border-radius: 40px; padding: 40px; border: 1px solid rgba(59, 130, 246, 0.12); }
      table { width: 100%; border-collapse: collapse; }
      th { text-align: left; padding: 16px 12px; font-size: 12px; color: #64748b; text-transform: uppercase; border-bottom: 1px solid rgba(59, 130, 246, 0.12); }
      td { padding: 20px 12px; border-bottom: 1px solid rgba(59, 130, 246, 0.12); font-size: 15px; font-weight: 600; }
      tbody tr { cursor: pointer; transition: .2s; }
      tbody tr:hover { background: #f1f5f9; }

      /* BADGES */
      .status-badge { padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 800; }
      .status-menunggu { background: #f1f5f9; color: #64748b; border-left: 4px solid #94a3b8; }
      .status-siap_dikirim { background: #ecfeff; color: #0891b2; border-left: 4px solid #06b6d4; }
      .status-dikirim { background: #f1f5f9; color: #1d4ed8; border-left: 4px solid #3b82f6; }
      .status-selesai { background: #f0fdf4; color: #15803d; border-left: 4px solid #10b981; }
      
      tr.active-row { background: #f1f5f9 !important; outline: 2px solid #2563eb; }
      
      .user-avatar { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; margin-right: 12px; }

      @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); transition: 0.3s; }
        .sidebar.active { transform: translateX(0); }
        .main { margin-left: 0; }
        .topbar { padding: 0 20px; }
        .hamburger { display: flex !important; }
      }
      .hamburger { display: none; width: 44px; height: 44px; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 12px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99;"></div>
    
    <div class="layout" style="display:flex;">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo" style="justify-content: center; padding: 24px 0;">
                <img src="../assets/css/logoo.png" alt="ALFA ONE" style="width: 200px; height: auto; object-fit: contain;">
            </div>
            
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
            <header class="topbar">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></div>
                    <div class="topbar-title"><h2>Monitoring Pengiriman</h2></div>
                </div>
                <div id="real-time-clock" style="font-weight: 700; color: #2563eb;"></div>
            </header>

            <div class="content">
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon truck"><i class="fas fa-truck-loading"></i></div>
                        <div class="stat-val"><?= $activeDeliveries ?></div>
                        <div class="stat-label">Sedang Jalan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon primary"><i class="fas fa-check-double"></i></div>
                        <div class="stat-val"><?= $completedToday ?></div>
                        <div class="stat-label">Selesai Hari Ini</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning"><i class="fas fa-history"></i></div>
                        <div class="stat-val"><?= $totalCompleted ?></div>
                        <div class="stat-label">Total Sukses</div>
                    </div>
                </div>

                <div class="map-card">
                    <div id="delivery-map"></div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <h3 style="margin-bottom:20px;"><i class="fas fa-stream"></i> Alur Pengiriman Terkini</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>KONSUMEN</th>
                                <th>TANGGAL</th>
                                <th>ALAMAT</th>
                                <th>SOPIR</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveryList as $d): ?>
                            <?php 
                                // Amankan data koordinat agar tidak error di JS
                                $lat = (float)($d['latitude'] ?? 0);
                                $lng = (float)($d['longitude'] ?? 0);
                            ?>
                            <tr id="row-<?= $d['id'] ?>" onclick="focusMarker(<?= $d['id'] ?>, <?= $lat ?>, <?= $lng ?>, '<?= addslashes($d['konsumen_nama']) ?>', '<?= strtoupper($d['status']) ?>', '<?= addslashes($d['sopir_nama'] ?? 'Belum Ditugaskan') ?>')">
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <div class="user-avatar"><?= strtoupper(substr($d['konsumen_nama'], 0, 1)) ?></div>
                                        <div>
                                            <div style="font-weight: 800;"><?= htmlspecialchars($d['konsumen_nama']) ?></div>
                                            <div style="font-size: 11px; color: #64748b;"><?= $d['telepon'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size: 13px; color: #64748b;"><?= date('d M Y', strtotime($d['tgl_pesanan'])) ?></td>
                                <td title="<?= htmlspecialchars($d['alamat']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($d['alamat'], 0, 35, "...")) ?>
                                </td>
                                <td><i class="fas fa-id-badge" style="color:#cbd5e1;"></i> <?= htmlspecialchars($d['sopir_nama'] ?? '—') ?></td>
                                <td><span class="status-badge status-<?= strtolower($d['status']) ?>"><?= strtoupper(str_replace('_', ' ', $d['status'])) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const btn = document.getElementById('hamburgerBtn');
        const side = document.getElementById('sidebar');
        const over = document.getElementById('sidebarOverlay');
        if(btn) btn.onclick = () => { side.classList.toggle('active'); over.style.display = side.classList.contains('active') ? 'block' : 'none'; };
        if(over) over.onclick = () => { side.classList.remove('active'); over.style.display = 'none'; };

        // Clock
        setInterval(() => {
            const now = new Date();
            document.getElementById('real-time-clock').innerText = now.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'medium' });
        }, 1000);

        let map, markersMap = {}, infoWindow;

        function initMap() {
            map = new google.maps.Map(document.getElementById('delivery-map'), {
                center: { lat: -2.9295, lng: 104.6851 },
                zoom: 12,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                styles: [
                    { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }
                ]
            });

            infoWindow = new google.maps.InfoWindow();

            const markerData = <?= json_encode($markers) ?>;
            const usedCoords = {};

            markerData.forEach(m => {
                let lat = m.lat;
                let lng = m.lng;
                const coordKey = `${lat.toFixed(6)},${lng.toFixed(6)}`;

                if (usedCoords[coordKey]) {
                    lat += (Math.random() - 0.5) * 0.0002;
                    lng += (Math.random() - 0.5) * 0.0002;
                }
                usedCoords[coordKey] = true;

                let color = '#94a3b8';
                if(m.status === 'selesai') color = '#10b981';
                else if(m.status === 'dikirim') color = '#3b82f6';
                else if(m.status === 'menunggu') color = '#f59e0b';

                const marker = new google.maps.Marker({
                    position: { lat: lat, lng: lng },
                    map: map,
                    title: m.nama,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: color,
                        fillOpacity: 0.9,
                        strokeColor: '#ffffff',
                        strokeWeight: 2
                    }
                });

                marker.addListener('click', function() {
                    infoWindow.setContent(`
                        <div style="font-family:Inter; padding:5px;">
                            <b style="font-size:14px;">${m.nama}</b><br>
                            <span style="color:#64748b;">Status:</span> <b>${m.status.replace('_', ' ').toUpperCase()}</b><br>
                            <span style="color:#64748b;">Sopir:</span> ${m.sopir}
                        </div>
                    `);
                    infoWindow.open(map, marker);
                });

                markersMap[m.id] = marker;
            });
        }

        function focusMarker(id, lat, lng, nama, status, sopir) {
            if (!lat || !lng || lat === 0) {
                alert("Lokasi koordinat belum diatur untuk pesanan ini.");
                return;
            }

            const marker = markersMap[id];
            if (marker) {
                const pos = marker.getPosition();
                map.setCenter(pos);
                map.setZoom(17);
                setTimeout(() => {
                    infoWindow.setContent(`
                        <div style="font-family:Inter; padding:5px;">
                            <b style="font-size:14px;">${nama}</b><br>
                            <span style="color:#64748b;">Status:</span> <b>${status}</b><br>
                            <span style="color:#64748b;">Sopir:</span> ${sopir}
                        </div>
                    `);
                    infoWindow.open(map, marker);
                }, 100);
            } else {
                map.setCenter({ lat: lat, lng: lng });
                map.setZoom(17);
                infoWindow.setPosition({ lat: lat, lng: lng });
                infoWindow.setContent(`<b>${nama}</b><br>Status: ${status}<br>Sopir: ${sopir}`);
                infoWindow.open(map);
            }

            document.querySelectorAll('tbody tr').forEach(tr => tr.classList.remove('active-row'));
            const activeRow = document.getElementById(`row-${id}`);
            if(activeRow) activeRow.classList.add('active-row');

            if(window.innerWidth < 1024) {
                const mapEl = document.querySelector('.map-card');
                if(mapEl) mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap" async defer></script>
</body>
</html>