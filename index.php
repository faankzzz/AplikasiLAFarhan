<?php
require_once 'config/database.php';
require_once 'config/auth.php';

// Ambil daftar produk
$stmt = $pdo->query("SELECT * FROM produk ORDER BY nama");
$produkList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfa One — Halaman Beranda</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&callback=initMap" async defer></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/index_style.css?v=2.9">
</head>
<body>

<nav class="nav" id="main-nav">
    <a href="#" class="nav-logo">
        <img src="<?= BASE_URL ?>assets/css/logoo.png" alt="ALFA ONE">
    </a>

    <!-- SEARCH BAR TENGAH NAVBAR -->
    <div class="nav-search">
        <i class="fas fa-search"></i>
        <input type="text" id="product-search" placeholder="Cari produk...">
    </div>

    <div class="nav-toggle" id="nav-toggle">
        <i class="fas fa-bars"></i>
    </div>

    <div class="nav-links">
        <?php if (isLoggedIn() && getUserRole() === 'konsumen'): ?>
            <a href="index.php" class="nav-active">
                <i class="fas fa-home" style="margin-right:6px;"></i>Beranda
            </a>
            <a href="konsumen/pesan.php">
                <i class="fas fa-shopping-cart" style="margin-right:6px;"></i>Belanja
            </a>
            <a href="konsumen/pesanan.php">
                <i class="fas fa-file-invoice" style="margin-right:6px;"></i>Pesanan
            </a>
            <a href="konsumen/komplain.php">
                <i class="fas fa-headset" style="margin-right:6px;"></i>Komplain
            </a>
            <div class="nav-divider"></div>
            <a href="konsumen/profil.php">
                <i class="fas fa-user-circle" style="margin-right:6px;"></i><?= htmlspecialchars($_SESSION['nama'] ?? 'Profil') ?>
            </a>
            <a href="logout.php" style="color: #f87171 !important;">
                <i class="fas fa-sign-out-alt" style="margin-right:6px;"></i>Keluar
            </a>
        <?php elseif (isLoggedIn()): ?>
            <a href="login.php" class="btn btn-primary">DASHBOARD</a>
        <?php else: ?>
            <a href="login.php" style="margin-right: 15px;">Login</a>
            <a href="register.php" class="btn btn-primary">DAFTAR SEKARANG</a>
        <?php endif; ?>
    </div>
</nav>

<!-- MOBILE MENU OVERLAY -->
<div class="nav-overlay" id="nav-overlay"></div>
<div class="nav-mobile" id="nav-mobile">
    <?php if (isLoggedIn() && getUserRole() === 'konsumen'): ?>
        <a href="index.php" class="nav-active"><i class="fas fa-home"></i> Beranda</a>
        <a href="konsumen/pesan.php"><i class="fas fa-shopping-cart"></i> Belanja</a>
        <a href="konsumen/pesanan.php"><i class="fas fa-file-invoice"></i> Pesanan</a>
        <a href="konsumen/komplain.php"><i class="fas fa-headset"></i> Komplain</a>
        <a href="konsumen/profil.php"><i class="fas fa-user-circle"></i> Profil</a>
        <a href="logout.php" style="color: #f43f5e;"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    <?php elseif (isLoggedIn()): ?>
        <a href="login.php" class="nav-active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="logout.php" style="color: #f43f5e;"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    <?php else: ?>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="register.php" class="nav-active"><i class="fas fa-user-plus"></i> Daftar Sekarang</a>
    <?php endif; ?>
</div>
<section class="hero" id="beranda">
    <div class="hero-grid">
        <div class="hero-text" data-aos="fade-right" data-aos-duration="1000">
            <span class="hero-tag">Air Mineral Pilihan Keluarga</span>
            <h1>Senantiasa Mengalir <span> Bersama </span></h1>
            <p style="font-family: 'Quicksand', sans-serif; font-size: 22px; color: rgba(255, 255, 255, 0.95); margin-bottom: 40px; line-height: 1.6; font-weight: 500;">Tetap terhidrasi dan sehat dengan Air Minum Berkualitas 💧 Rasain Segernya!</p>
            <div class="hero-btns">
                <a href="#produk" class="btn btn-primary">LIHAT PRODUK</a>
                <a href="register.php" class="btn btn-outline">Daftar Sebagai Toko</a>
            </div>
        </div>
        <div class="hero-img" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
            <img src="<?= BASE_URL ?>assets/css/logoo.png" alt="Produk Alfa One" style="filter: drop-shadow(0 20px 40px rgba(255, 255, 255, 0.1));">
        </div>
    </div>
    <div class="wave-container">
        <svg viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path fill="#dbeafe" fill-opacity="1" d="M0,160L48,176C96,192,192,224,288,224C384,224,480,192,576,165.3C672,139,768,117,864,122.7C960,128,1056,160,1152,165.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </div>
</section>

<!-- SECTION KEUNGGULAN -->
<section class="features" id="keunggulan">
    <div class="features-grid">
        <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
            <div class="feature-icon"><i class="fas fa-filter"></i></div>
            <h3>Filtrasi Modern</h3>
            <p>Diproses dengan teknologi reverse osmosis untuk menjamin kemurnian setiap tetes air.</p>
        </div>
        <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
            <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
            <h3>100% Higienis</h3>
            <p>Airnya diproses menggunakan teknologi nanofiltrasi/ultrafiltrasi dan berasal dari sumber air alami.</p>
        </div>
        <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
            <div class="feature-icon"><i class="fas fa-truck-fast"></i></div>
            <h3>Pengiriman Cepat</h3>
            <p>Armada khusus yang siap mengantarkan pesanan Anda tepat waktu ke lokasi.</p>
        </div>
        <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
            <div class="feature-icon"><i class="fas fa-tags"></i></div>
            <h3>Harga Kompetitif</h3>
            <p>Dapatkan kualitas air minum premium dengan harga yang sangat terjangkau.</p>
        </div>
    </div>
</section>

<section class="section" id="produk">
    <div class="section-header" data-aos="fade-up">
        <span style="color: #2563eb; font-weight: 800; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;">Katalog Kami</span>
        <h2>Produk Berkualitas Untuk Anda</h2>
        <p>Tersedia dalam berbagai ukuran kemasan yang praktis untuk menemani setiap aktivitas harian Anda.</p>
    </div>

    <div id="no-product-msg" style="display:none; text-align:center; padding: 60px 20px;">
        <i class="fas fa-box-open" style="font-size:56px; color:#e2e8f0; display:block; margin-bottom:20px;"></i>
        <p style="color:#94a3b8; font-size:16px; font-weight:600;">Produk tidak ditemukan. Coba kata kunci lain.</p>
    </div>

    <div class="product-grid" id="product-grid">
        <?php foreach ($produkList as $p): ?>
        <div class="product-card" data-aos="fade-up" data-nama="<?= strtolower(htmlspecialchars($p['nama'])) ?> <?= strtolower($p['satuan']) ?>">
            <div class="product-img">
                <?php
                // Kumpulkan foto yang ada
                $fotos = [];
                if (!empty($p['foto'])) $fotos[] = $p['foto'];
                if (!empty($p['foto2'])) $fotos[] = $p['foto2'];
                if (!empty($p['foto3'])) $fotos[] = $p['foto3'];
                ?>
                
                <?php if (count($fotos) > 1): ?>
                    <div class="swiper product-swiper" style="width: 100%; height: 100%;">
                        <div class="swiper-wrapper">
                            <?php foreach ($fotos as $f): ?>
                            <div class="swiper-slide" style="display: flex; align-items: center; justify-content: center;">
                                <img src="<?= getUploadUrl($f) ?>" alt="<?= htmlspecialchars($p['nama']) ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                <?php elseif (count($fotos) === 1): ?>
                    <img src="<?= getUploadUrl($fotos[0]) ?>" alt="<?= htmlspecialchars($p['nama']) ?>">
                <?php else: ?>
                    <i class="fas fa-bottle-water" style="font-size: 64px; color: #2563eb; opacity: 0.1;"></i>
                <?php endif; ?>
                <div style="position: absolute; bottom: 20px; right: 20px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); padding: 6px 14px; border-radius: 12px; font-size: 11px; font-weight: 800; color: white;">
                    <?= $p['satuan'] ?>
                </div>
            </div>
            <h4><?= htmlspecialchars($p['nama']) ?></h4>
            <span class="price"><?= formatRupiah($p['harga_umum']) ?></span>
            <?php if (isLoggedIn() && getUserRole() === 'konsumen'): ?>
                <a href="konsumen/pesan.php" class="btn btn-primary" style="width: 100%; justify-content: center; border-radius: 20px;">
                    <i class="fas fa-shopping-cart"></i> PESAN SEKARANG
                </a>
            <?php elseif (isLoggedIn()): ?>
                <a href="login.php" class="btn btn-primary" style="width: 100%; justify-content: center; border-radius: 20px;">DASHBOARD</a>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="showLoginModal()" class="btn btn-primary" style="width: 100%; justify-content: center; border-radius: 20px;">PESAN SEKARANG</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- SECTION TESTIMONI -->
<section class="testimonials" id="testimoni">
    <div class="section-header" data-aos="fade-up">
        <h2>Kepercayaan Pelanggan Kami</h2>
    </div>
    <div class="testi-grid">
        <div class="testi-card" data-aos="fade-up" data-aos-delay="100">
            <div class="testi-content">
                "Sudah Langganan, gercep, seller ramah, Terima kasih semuanya sudah lengkap, Dipertahankan pelayanan terbaiknya, Terima kasih seller..."
            </div>
            <div class="testi-user">
                <!-- Ubah src gambar di bawah ini dengan foto pelanggan -> -->
                <img src="<?= BASE_URL ?>assets/css/claraningsih.jpeg" alt="Foto Pelanggan 1" class="testi-user-img" onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\'><rect width=\'80\' height=\'80\' fill=\'%23f1f5f9\'/><text x=\'50%\' y=\'50%\' font-family=\'sans-serif\' font-size=\'14\' fill=\'%2394a3b8\' dominant-baseline=\'middle\' text-anchor=\'middle\'>Foto 1</text></svg>'">
                <div class="testi-user-info">
                    <h5>Clara Ningsih Shopee</h5>
                </div>
            </div>
        </div>
        <div class="testi-card" data-aos="fade-up" data-aos-delay="200">
            <div class="testi-content">
                "Kualitas bagus, barang exp masih lama, tidak ada rusak dan tidak ada bocor, kemasan bagus. mantap pokoknya mah"
            </div>
            <div class="testi-user">
                <!-- Ubah src gambar di bawah ini dengan foto pelanggan -> -->
                <img src="<?= BASE_URL ?>assets/css/testi2.png" alt="Foto Pelanggan 2" class="testi-user-img" onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\'><rect width=\'80\' height=\'80\' fill=\'%23f1f5f9\'/><text x=\'50%\' y=\'50%\' font-family=\'sans-serif\' font-size=\'14\' fill=\'%2394a3b8\' dominant-baseline=\'middle\' text-anchor=\'middle\'>Foto 2</text></svg>'">
                <div class="testi-user-info">
                    <h5>shalsabillaay1234 Shopee</h5>
                </div>
            </div>
        </div>
        <div class="testi-card" data-aos="fade-up" data-aos-delay="300">
            <div class="testi-content">
                "Rasa Segar dan Enak, Kualitas Bagus, Produknya Asli, airnya tidak terasa mentah."
            </div>
            <div class="testi-user">
                <!-- Ubah src gambar di bawah ini dengan foto pelanggan -> -->
                <img src="<?= BASE_URL ?>assets/css/testi3.png" alt="Foto Pelanggan 3" class="testi-user-img" onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\'><rect width=\'80\' height=\'80\' fill=\'%23f1f5f9\'/><text x=\'50%\' y=\'50%\' font-family=\'sans-serif\' font-size=\'14\' fill=\'%2394a3b8\' dominant-baseline=\'middle\' text-anchor=\'middle\'>Foto 3</text></svg>'">
                <div class="testi-user-info">
                    <h5>pully2pooh Shopee</h5>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="integrated-section" id="integrated-section">
    <div class="integrated-grid">
        <div class="info-content" data-aos="fade-right">
            <h2>Alfa One, Pilihan Hidrasi Terpercaya Keluarga</h2>
            <p>Alfa One hadir sebagai solusi air minum berkualitas tinggi yang diproses dengan teknologi filtrasi modern untuk memastikan kemurnian di setiap tetesnya. Kami memahami pentingnya hidrasi bagi kesehatan Anda dan keluarga, itulah sebabnya Alfa One senantiasa menjaga standar kualitas yang ketat mulai dari sumber air hingga sampai ke tangan konsumen. Dengan berbagai pilihan kemasan praktis, Alfa One siap menemani setiap aktivitas harian Anda, baik di rumah, kantor, maupun saat dalam perjalanan.</p>

            <h4 style="font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Cara Belanja di Platform Alfa One</h4>
            <p>Menyehatkan rasanya ketika bisa memenuhi kebutuhan harian hanya dengan menggerakkan jari di layar smartphone atau laptop dari rumah. Anda hanya perlu mendaftar, memilih produk favorit Anda dari katalog kami, dan menentukan lokasi pengantarannya.</p>

            <h4 style="font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Jadilah Mitra Kami</h4>
            <p>Dapatkan berbagai penawaran menarik dan harga khusus dengan menjadi agen atau mitra resmi Alfa One. Kami membuka peluang bagi Anda yang ingin mengembangkan jaringan distribusi produk air minum berkualitas.</p>
        </div>
        <div class="map-container" data-aos="fade-left">
            <div id="leaflet-map"></div>
        </div>
    </div>
</section>

<!-- SECTION FAQ -->
<section class="faq-section" id="faq">
    <div class="section-header" data-aos="fade-up">
        <span style="color: #2563eb; font-weight: 800; font-size: 14px; letter-spacing: 2px; text-transform: uppercase;">Bantuan</span>
        <h2>Pertanyaan yang Sering Diajukan</h2>
    </div>
    <div class="faq-container">
        <div class="faq-item" data-aos="fade-up" data-aos-delay="100">
            <div class="faq-question">
                Bagaimana cara memesan produk Alfa One?
                <i class="fas fa-chevron-down faq-icon"></i>
            </div>
            <div class="faq-answer">
                <br>Sangat mudah! Anda hanya perlu membuat akun atau login. Setelah itu, pilih produk di halaman katalog, masukkan alamat pengiriman, dan pesanan Anda akan segera diproses oleh admin kami.
            </div>
        </div>
        <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
            <div class="faq-question">
                Apakah ada minimal pembelian?
                <i class="fas fa-chevron-down faq-icon"></i>
            </div>
            <div class="faq-answer">
                <br>Untuk pembelian mandiri/eceran tidak ada minimal pemesanan. Namun, untuk agen/toko yang ingin mendapatkan harga grosir (Harga Toko), silakan lihat syarat dan ketentuan pada dashboard kemitraan.
            </div>
        </div>
        <div class="faq-item" data-aos="fade-up" data-aos-delay="300">
            <div class="faq-question">
                Apakah ongkos kirim ditanggung pembeli?
                <i class="fas fa-chevron-down faq-icon"></i>
            </div>
            <div class="faq-answer">
                <br>Kami menyediakan gratis ongkos kirim untuk wilayah pengiriman tertentu di area Palembang dan sekitarnya (syarat & ketentuan berlaku).
            </div>
        </div>
        <div class="faq-item" data-aos="fade-up" data-aos-delay="400">
            <div class="faq-question">
                Bagaimana cara menjadi mitra toko?
                <i class="fas fa-chevron-down faq-icon"></i>
            </div>
            <div class="faq-answer">
                <br>Anda dapat mendaftarkan diri dengan menekan tombol "Daftar Sebagai Toko/Mitra". Isi data diri dan alamat usaha Anda. Admin Penjualan kami akan memverifikasi Akun Toko Anda.
            </div>
        </div>
    </div>
</section>

<!-- CTA BANNER -->
<section class="cta-section">
    <div class="cta-box" data-aos="zoom-in">
        <div class="cta-content">
            <h2>Siap Menjadi Bagian dari Alfa One?</h2>
            <p>Nikmati air minum berkualitas untuk kebutuhan keluarga sehari-hari, atau jadilah mitra resmi kami dan dapatkan berbagai keuntungan menarik untuk mengembangkan usaha Anda.</p>
            <a href="register.php" class="btn btn-primary btn-white">DAFTAR SEKARANG</a>
            <a href="login.php" class="btn btn-outline" style="border-color: white; color: white;">LOGIN UNTUK BELANJA</a>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="footer-grid">
        <div class="footer-col" id="tentang">
            <a href="#" class="nav-logo" style="margin-bottom: 25px; color: white; scale: 0.8; transform-origin: left; height: auto;">
                <img src="assets/css/logoo.png" alt="ALFA ONE">
            </a>
            <p style="max-width: 500px;">Berkomitmen memberikan solusi hidrasi terbaik dengan standar kualitas tinggi dan layanan pengantaran yang terpercaya di seluruh wilayah Palembang dan sekitarnya.</p>
    <div style="display: flex; gap: 15px; margin-top: 30px;">
    <a href="https://instagram.com/alfaone.id" target="_blank" style="color: white; font-size: 20px;">
        <i class="fab fa-instagram"></i>
    </a>
    <a href="https://wa.me/6281532853333" target="_blank" style="color: white; font-size: 20px;">
        <i class="fab fa-whatsapp"></i>
    </a>
    </div>
        </div>
        <div class="footer-col">
            <h5>Kontak Kami</h5>
            <p style="margin-bottom: 15px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-map-marker-alt" style="color: #22d3ee; width: 20px;"></i> 
               Komplek Pergudangan Blok C1-C2 Kecamatan Alang-Alang Lebar
            </p>
            <p style="margin-bottom: 15px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-phone" style="color: #22d3ee; width: 20px;"></i> 
                (62-711) 354478
            </p>
            <p style="display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-envelope" style="color: #22d3ee; width: 20px;"></i> 
                tirtaosmosisssampurna@yahoo.com
            </p>
        </div>
    </div>
    <div class="copyright">
        &copy; 2026 ALFA ONE — SENANTIASA MENGALIR BERSAMA.
    </div>
</footer>

<!-- LOGIN WARNING MODAL -->
<div class="modal-overlay" id="loginModal">
    <div class="modal-card
        <div class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></div>
        <div class="modal-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h3>Anda Harus Login Dulu!</h3>
        <p>Anda harus login terlebih dahulu agar bisa melakukan pemesanan produk. Belum punya akun? Daftar sekarang aja!</p>
        <div class="modal-actions">
            <a href="login.php" class="btn btn-primary" style="width: 100%; justify-content: center; border-radius: 20px;">LOGIN SEKARANG</a>
            <a href="register.php" class="btn btn-outline" style="width: 100%; justify-content: center; border-radius: 20px; border-color: #e2e8f0; color: #64748b;">DAFTAR AKUN BARU</a>
        </div>
    </div>
</div>

<script>
    // Inisialisasi AOS (Animasi Scroll)
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });

    // Inisialisasi Swiper (Slider Foto Produk)
    new Swiper('.product-swiper', {
        loop: true,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        autoplay: {
            delay: 2000,
            disableOnInteraction: false,
        }
    });

    // Script FAQ Accordion
    document.querySelectorAll('.faq-question').forEach(item => {
        item.addEventListener('click', () => {
            const parent = item.parentElement;
            const isActive = parent.classList.contains('active');
            
            // Tutup semua faq yang terbuka
            document.querySelectorAll('.faq-item').forEach(child => {
                child.classList.remove('active');
            });

            // Jika sebelumnya tidak aktif, maka buka
            if (!isActive) {
                parent.classList.add('active');
            }
        });
    });

    window.addEventListener('scroll', () => {
        const nav = document.getElementById('main-nav');
        if (!nav) return;

        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
            nav.classList.add('is-scrolled');
        } else {
            nav.classList.remove('scrolled');
            nav.classList.remove('is-scrolled');
        }
    });


    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    // MODAL FUNCTIONS
    const modal = document.getElementById('loginModal');
    
    function showLoginModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
    
    // Close modal when clicking overlay
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // GOOGLE MAPS INITIALIZATION
    window.initMap = function() {
        const warehouseLocation = { lat: <?= GUDANG_LAT ?>, lng: <?= GUDANG_LNG ?> };
        const map = new google.maps.Map(document.getElementById('leaflet-map'), {
            center: warehouseLocation,
            zoom: 15,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            scrollwheel: false
        });

        const infowindow = new google.maps.InfoWindow({
            content: '<div style="padding:10px; font-family:\'Outfit\',sans-serif; text-align:center;"><b style="font-size:16px; color:#1d4ed8; display:block; margin-bottom:5px;"><i class="fas fa-location-dot"></i> GUDANG ALFA ONE</b><span style="font-size:13px; color:#64748b; font-weight:600;">Komplek Pergudangan Blok C1-C2 Kecamatan Alang-Alang Lebar</span></div>'
        });

        const marker = new google.maps.Marker({
            position: warehouseLocation,
            map: map,
            title: 'GUDANG ALFA ONE'
        });

        marker.addListener('click', () => {
            infowindow.open(map, marker);
        });

        // Open by default
        infowindow.open(map, marker);
    }
    // PRODUCT SEARCH FILTER
    const searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('#product-grid .product-card');
            const noMsg = document.getElementById('no-product-msg');
            let visibleCount = 0;

            cards.forEach(function(card) {
                const nama = (card.getAttribute('data-nama') || '').toLowerCase();
                if (nama.indexOf(query) !== -1) {
                    card.classList.remove('hidden-by-search');
                    visibleCount++;
                } else {
                    card.classList.add('hidden-by-search');
                }
            });

            if (noMsg) noMsg.style.display = (visibleCount === 0 && query !== '') ? 'block' : 'none';
        });
    }

    // MOBILE MENU SCRIPT
    const navToggle = document.getElementById('nav-toggle');
    const navMobile = document.getElementById('nav-mobile');
    const navOverlay = document.getElementById('nav-overlay');

    if (navToggle) {
        navToggle.addEventListener('click', () => {
            navMobile.classList.toggle('active');
            navOverlay.classList.toggle('active');
            navToggle.querySelector('i').classList.toggle('fa-bars');
            navToggle.querySelector('i').classList.toggle('fa-times');
            document.body.style.overflow = navMobile.classList.contains('active') ? 'hidden' : 'auto';
        });
    }

    if (navOverlay) {
        navOverlay.addEventListener('click', () => {
            navMobile.classList.remove('active');
            navOverlay.classList.remove('active');
            navToggle.querySelector('i').classList.add('fa-bars');
            navToggle.querySelector('i').classList.remove('fa-times');
            document.body.style.overflow = 'auto';
        });
    }

    // Close mobile menu on link click
    document.querySelectorAll('.nav-mobile a').forEach(link => {
        link.addEventListener('click', () => {
            navMobile.classList.remove('active');
            navOverlay.classList.remove('active');
            navToggle.querySelector('i').classList.add('fa-bars');
            navToggle.querySelector('i').classList.remove('fa-times');
            document.body.style.overflow = 'auto';
        });
    });
</script>
</body>
</html>
