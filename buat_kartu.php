<?php
require_once 'config/database.php';
require_once 'config/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generator Kartu Member Toko — Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
      :root {
        --primary: #3b82f6;
        --deep-blue: #1e40af;
        --light-blue: #dbeafe;
        --aqueous: #f0f9ff;
        --grad-blue: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        --font-heading: 'Outfit', sans-serif;
        --font-body: 'Inter', sans-serif;
      }
      
      * { margin:0; padding:0; box-sizing:border-box; }
      body { 
        font-family: 'Inter', sans-serif; 
        background-color: #f0f9ff;
        background-image: 
          radial-gradient(at 0% 0%, hsla(210,100%,96%,1) 0, transparent 50%), 
          radial-gradient(at 100% 0%, hsla(200,100%,94%,1) 0, transparent 50%);
        color: #1e293b; 
        min-height: 100vh; 
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        overflow-x: hidden;
      }

      /* Animated Blobs */
      .blob {
        position: absolute;
        width: 500px;
        height: 500px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.05) 100%);
        filter: blur(80px);
        border-radius: 50%;
        z-index: -1;
        animation: float 20s infinite alternate;
      }
      @keyframes float {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(100px, 50px) scale(1.1); }
      }

      .container {
        width: 100%;
        max-width: 1000px;
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 40px;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        padding: 50px;
        border-radius: 40px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 40px 80px -20px rgba(0,0,0,0.08);
        z-index: 10;
      }

      .form-section h2 {
        font-family: 'Outfit', sans-serif;
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 24px;
        letter-spacing: -1px;
        color: #1e40af;
      }

      .form-group { margin-bottom: 24px; }
      .label { display: block; font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px; }
      .input { 
        width: 100%; padding: 16px 20px; border-radius: 18px; border: 2px solid #e2e8f0; 
        font-family: inherit; font-size: 16px; outline: none; transition: .3s;
        background: white;
      }
      .input:focus { border-color: #3b82f6; box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.1); }

      .btn-download {
        width: 100%; padding: 18px; border-radius: 20px; border: none;
        background: #1e40af; color: white; font-family: 'Outfit', sans-serif;
        font-weight: 700; font-size: 17px; cursor: pointer; transition: .4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex; align-items: center; justify-content: center; gap: 12px;
        margin-top: 30px;
        box-shadow: 0 15px 30px -5px rgba(30, 64, 175, 0.3);
      }
      .btn-download:hover { transform: scale(1.02) translateY(-5px); box-shadow: 0 20px 40px -5px rgba(30, 64, 175, 0.4); }

      /* MEMBER CARD DESIGN - AQUEOUS THEME */
      .card-preview-area {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f0f9ff;
        border-radius: 30px;
        padding: 30px;
        border: 2px dashed #bfdbfe;
        position: relative;
      }

      #member-card {
        width: 480px;
        height: 280px;
        background: white;
        border-radius: 24px;
        position: relative;
        overflow: hidden;
        padding: 40px;
        color: #1e40af;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
        border: 1px solid rgba(255, 255, 255, 0.8);
        transition: .5s cubic-bezier(0.16, 1, 0.3, 1);
      }
      
      #member-card:hover { transform: translateY(-10px) rotateX(5deg); }

      /* Wave Background */
      .card-waves {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 120px;
        z-index: 1;
        overflow: hidden;
      }
      .wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 200%;
        height: 100%;
        background-repeat: repeat no-repeat;
        background-position: 0 bottom;
        transform-origin: center bottom;
      }
      .wave1 {
        z-index: 1000;
        opacity: 0.5;
        animation: waveAnim 15s linear infinite;
      }
      .wave2 {
        z-index: 999;
        animation: waveAnim 20s linear infinite;
      }
      .wave svg { width: 100%; height: 100%; display: block; }
      @keyframes waveAnim {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
      }

      .bubbles {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: 0;
      }
      .bubble {
        position: absolute;
        background: radial-gradient(circle at 30% 30%, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05));
        border-radius: 50%;
        border: 1px solid rgba(59, 130, 246, 0.05);
      }

      .card-header { display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 5; margin-bottom: 25px; }
      .card-logo { display: flex; align-items: center; gap: 10px; font-weight: 800; font-family: 'Outfit', sans-serif; font-size: 20px; color: #1e40af; }
      .card-logo i { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 24px; }
      .card-type { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #3b82f6; background: #eff6ff; padding: 6px 14px; border-radius: 50px; border: 1px solid #dbeafe; }

      .card-body { position: relative; z-index: 5; }
      .card-store-name { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; margin-bottom: 5px; text-transform: uppercase; color: #1e40af; letter-spacing: -0.5px; }
      .card-owner { font-size: 16px; color: #64748b; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; }
      .card-owner::before { content: ''; width: 20px; height: 2px; background: #3b82f6; display: inline-block; border-radius: 2px; }

      .card-footer { position: absolute; bottom: 35px; left: 40px; right: 40px; display: flex; justify-content: space-between; align-items: flex-end; z-index: 5; }
      .card-address { font-size: 11px; color: #94a3b8; max-width: 250px; line-height: 1.5; font-weight: 500; }
      .card-id { font-family: 'Outfit', sans-serif; font-size: 13px; color: #3b82f6; font-weight: 800; display: flex; align-items: center; gap: 6px; }

      .chip { 
        width: 50px; height: 38px; 
        background: linear-gradient(135deg, #e5e7eb 0%, #9ca3af 100%); 
        border-radius: 8px; margin-bottom: 20px; position: relative;
        box-shadow: inset 0 2px 4px rgba(255,255,255,0.5), 0 4px 8px rgba(0,0,0,0.05);
        border: 1px solid rgba(255,255,255,0.3);
      }
      .chip::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='50' height='38' viewBox='0 0 50 38' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M10 0v38M20 0v38M30 0v38M40 0v38M0 10h50M0 20h50M0 30h50' stroke='rgba(0,0,0,0.1)' fill='none'/%3E%3C/svg%3E");
      }
      
      .back-link { margin-top: 20px; display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 700; transition: .2s; }
      .back-link:hover { color: #3b82f6; transform: translateX(-5px); }

      @media (max-width: 1000px) {
        .container { grid-template-columns: 1fr; padding: 40px 30px; }
        #member-card { width: 100%; max-width: 480px; height: auto; aspect-ratio: 1.7/1; }
      }
    </style>
</head>
<body>

<div class="blob" style="top: -100px; left: -100px;"></div>
<div class="blob" style="bottom: -100px; right: -100px; animation-delay: -5s;"></div>

<div class="container">
    <div class="form-section">
        <a href="register.php" class="back-link"><i class="fas fa-arrow-left"></i> KEMBALI KE REGISTER</a>
        <h2 style="margin-top: 25px;">Kartu Member Toko</h2>
        <p style="color: #64748b; margin-bottom: 35px; font-size: 16px; line-height: 1.6;">Lengkapi identitas toko Anda untuk menerbitkan kartu Member Toko Alfa One.</p>

        <div class="form-group">
            <label class="label">Isi Nama Toko</label>
            <input type="text" id="input-store" class="input" placeholder="Masukkan Nama Toko Anda" maxlength="25">
        </div>
        <div class="form-group">
            <label class="label">Nama Pemilik Toko</label>
            <input type="text" id="input-owner" class="input" placeholder="Masukkan Nama Pemilik Toko" maxlength="30">
        </div>
        <div class="form-group">
            <label class="label">Alamat Operasional</label>
            <textarea id="input-address" class="input" style="min-height: 100px; resize: none;" placeholder="Alamat lengkap lokasi toko..."></textarea>
        </div>

        <button onclick="downloadCard()" class="btn-download">
            <i class="fas fa-cloud-arrow-down"></i> DOWNLOAD KARTU MEMBER TOKO
        </button>
        <p style="font-size: 13px; color: #94a3b8; margin-top: 25px; text-align: center; font-weight: 600;">
            <i class="fas fa-shield-halved" style="color: #3b82f6; margin-right: 6px;"></i> Kartu akan otomatis terunduh dalam format PNG kualitas HD.
        </p>
    </div>

    <div class="card-preview-area">
        <div id="member-card">
            <!-- Fluid Ornaments -->
            <div class="bubbles">
                <div class="bubble" style="width: 100px; height: 100px; top: -20px; right: -20px;"></div>
                <div class="bubble" style="width: 60px; height: 60px; bottom: 40px; left: 10%; opacity: 0.5;"></div>
                <div class="bubble" style="width: 30px; height: 30px; top: 40%; right: 15%;"></div>
            </div>
            
            <div class="card-waves">
                <div class="wave wave1">
                    <svg viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path fill="#eff6ff" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                </div>
                <div class="wave wave2">
                    <svg viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path fill="#3b82f6" fill-opacity="0.15" d="M0,160L48,176C96,192,192,224,288,213.3C384,203,480,149,576,149.3C672,149,768,203,864,224C960,245,1056,235,1152,213.3C1248,192,1344,160,1392,144L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                </div>
            </div>

            <div class="card-header">
                <div class="card-logo">
                    ALFA ONE
                </div>
                <div class="card-type">MEMBER TOKO</div>
            </div>
            <div class="card-body">
                <div class="chip"></div>
                <div class="card-store-name" id="display-store">NAMA TOKO ANDA</div>
                <div class="card-owner" id="display-owner">Nama Pemilik Toko</div>
            </div>
            <div class="card-footer">
                <div class="card-address" id="display-address">Alamat lengkap toko akan ditampilkan secara otomatis di bagian kartu ini.</div>
            </div>
        </div>
    </div>
</div>


<script>
    const inputStore = document.getElementById('input-store');
    const inputOwner = document.getElementById('input-owner');
    const inputAddress = document.getElementById('input-address');

    const displayStore = document.getElementById('display-store');
    const displayOwner = document.getElementById('display-owner');
    const displayAddress = document.getElementById('display-address');

    inputStore.addEventListener('input', (e) => {
        displayStore.innerText = e.target.value || 'NAMA TOKO ANDA';
    });

    inputOwner.addEventListener('input', (e) => {
        displayOwner.innerText = e.target.value || 'Nama Pemilik Toko';
    });

    inputAddress.addEventListener('input', (e) => {
        displayAddress.innerText = e.target.value || 'Alamat lengkap toko akan ditampilkan di bagian ini.';
    });

    function downloadCard() {
        if (!inputStore.value || !inputOwner.value) {
            alert('Silakan isi Nama Toko dan Nama Pemilik dulu ya bang!');
            return;
        }

        const btn = document.querySelector('.btn-download');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> MEMPROSES...';
        btn.disabled = true;

        html2canvas(document.querySelector("#member-card"), {
            scale: 3, // High quality
            backgroundColor: null
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = `Member-AlfaOne-${inputStore.value}.png`;
            link.href = canvas.toDataURL("image/png");
            link.click();
            
            btn.innerHTML = '<i class="fas fa-download"></i> UNDUH KARTU DIGITAL';
            btn.disabled = false;
        });
    }
</script>

</body>
</html>
