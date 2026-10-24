<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin_penjualan');
$user = getCurrentUser($pdo);

$pesananId = (int)($_GET['id'] ?? 0);

// Ambil data pesanan
$stmt = $pdo->prepare("SELECT p.*, u.nama as konsumen_nama, u.telepon as konsumen_telp, u.alamat,
    u2.nama as sopir_nama, u2.telepon as sopir_telp
    FROM pesanan p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN users u2 ON p.sopir_id = u2.id
    WHERE p.id = ?");
$stmt->execute([$pesananId]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    die("Pesanan tidak ditemukan.");
}

// Ambil detail produk
$stmtD = $pdo->prepare("SELECT pd.*, pr.nama as produk_nama, pr.satuan 
    FROM transaksi_pesanan pd 
    JOIN produk pr ON pd.produk_id = pr.id 
    WHERE pd.pesanan_id = ?");
$stmtD->execute([$pesananId]);
$details = $stmtD->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= str_pad($pesananId, 5, '0', STR_PAD_LEFT) ?> - Alfa One</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6; --text: #1e293b; --muted: #64748b; --border: #b3d4e8;
            --font-heading: 'Outfit', sans-serif; --font-body: 'Inter', sans-serif;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', sans-serif; color: #1e293b; background: #f1f5f9; font-size: 16px; line-height: 1.4; }
        
        .print-container { 
            max-width: 1000px; 
            margin: 20px auto; 
            background: white; 
            padding: 30px 50px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #b3d4e8;
        }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 4px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px; }
        .brand { font-family: 'Outfit', sans-serif; font-size: 42px; font-weight: 800; color: #3b82f6; letter-spacing: -2px; line-height: 1; }
        .brand-sub { font-weight: 800; color: #64748b; letter-spacing: 4px; font-size: 12px; text-transform: uppercase; margin-top: 5px; }
        
        .company-info { text-align: right; color: #64748b; font-size: 13px; line-height: 1.4; }
        .company-info strong { color: #0f172a; font-size: 16px; display: block; margin-bottom: 2px; font-family: 'Outfit', sans-serif; }
        
        .invoice-title { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; text-transform: uppercase; margin-bottom: 25px; text-align: center; letter-spacing: 6px; color: #0f172a; }
        
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .meta-box { background: #f8fafc; padding: 15px 20px; border-radius: 15px; border: 1px solid #b3d4e8; }
        .meta-box h4 { font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 800; color: #3b82f6; margin-bottom: 10px; border-bottom: 2px dashed #e2e8f0; padding-bottom: 8px; text-transform: uppercase; letter-spacing: 2px; }
        
        .info-row { display: flex; margin-bottom: 6px; align-items: baseline; }
        .info-label { width: 120px; color: #64748b; font-weight: 700; font-size: 12px; text-transform: uppercase; }
        .info-val { flex: 1; font-weight: 800; color: #0f172a; font-size: 15px; }
        
        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .item-table th { background: #0f172a; color: white; padding: 12px 20px; text-align: left; font-size: 13px; font-weight: 800; text-transform: uppercase; }
        .item-table td { padding: 10px 20px; border-bottom: 1px solid #b3d4e8; font-size: 18px; font-weight: 700; }
        
.total-section {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 25px;
}

.total-box {
    width: 500px;
    border: 3px solid #0f172a; /* balikin border */
    border-radius: 20px;
    padding: 15px 25px;
    background: #fff;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    white-space: nowrap;
    margin-bottom: 5px;
    font-size: 16px;
    font-weight: 700;
    color: #64748b;
}

.total-row.grand {
    border-top: 4px double #0f172a;
    padding-top: 10px;
    margin-top: 5px;
    font-size: 24px;
    font-weight: 900;
    color: #3b82f6; /* balikin warna biru */
    font-family: 'Outfit', sans-serif;
}   
.signatures {
    display: flex;
    justify-content: center; /* Tengah horizontal */
    gap: 100px; /* Jarak antar kolom */
    margin-top: 30px;
}

.sig-box {
    width: 250px; /* Biar sama lebar */
    text-align: center;
}

.sig-title {
    font-weight: 800;
    color: #64748b;
    margin-bottom: 60px;
    font-size: 13px;
    text-transform: uppercase;
}

.sig-line {
    width: 100%;
    border-bottom: 3px solid #0f172a;
    margin-bottom: 8px;
}

.sig-name {
    font-weight: 800;
    font-size: 16px;
    color: #0f172a;
    text-transform: uppercase;
}

        @media print {
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .print-container { 
                margin: 0 !important; 
                max-width: 100% !important; 
                width: 100% !important;
                box-shadow: none !important; 
                border: none !important; 
                padding: 10px 40px !important;
                zoom: 0.7; /* Mengecilkan sedikit lagi agar Landscape muat */
            }
            .meta-box { background: #f8fafc !important; -webkit-print-color-adjust: exact; }
            .item-table th { background: #0f172a !important; color: white !important; -webkit-print-color-adjust: exact; }
            @page { 
                size: landscape; 
                margin: 0.3cm; 
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="print-container">
    <div class="header" style="page-break-inside: avoid;">
        <div>
            <div class="brand">ALFA ONE</div>
        </div>
        <div class="company-info">
            <strong>GUDANG ALFA ONE</strong>
            Komplek Pergudangan Blok C1-C2 Kecamatan Alang-Alang Lebar<br>
            Palembang, Sumatera Selatan<br>
            tirtaosmosisssampurna@yahoo.com
        </div>
    </div>
    
    <div class="invoice-title">SURAT JALAN / INVOICE</div>
    
    <div class="meta-grid" style="page-break-inside: avoid;">
        <div class="meta-box">
            <h4>INFORMASI DOKUMEN</h4>
            <div class="info-row"><div class="info-label">NO. INVOICE</div><div class="info-val">INV/<?= date('Ymd', strtotime($pesanan['created_at'])) ?>/<?= str_pad($pesananId, 5, '0', STR_PAD_LEFT) ?></div></div>
            <div class="info-row"><div class="info-label">TGL ORDER</div><div class="info-val"><?= formatTanggalIndo($pesanan['created_at']) ?></div></div>
            <div class="info-row"><div class="info-label">PEMBAYARAN</div><div class="info-val" style="color: #3b82f6;"><?= strtoupper($pesanan['metode_bayar']) ?></div></div>
        </div>
        
        <div class="meta-box">
            <h4>DATA KONSUMEN</h4>
            <div class="info-row"><div class="info-label">NAMA</div><div class="info-val"><?= htmlspecialchars($pesanan['konsumen_nama']) ?></div></div>
            <div class="info-row"><div class="info-label">KONTAK</div><div class="info-val"><?= htmlspecialchars($pesanan['konsumen_telp']) ?></div></div>
            <div class="info-row">
                <div class="info-label">ALAMAT</div>
                <div class="info-val" style="font-size: 13px; line-height: 1.3;"><?= htmlspecialchars($pesanan['alamat']) ?></div>
            </div>
        </div>
    </div>
    
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 8%;">NO</th>
                <th style="width: 42%;">DESKRIPSI PRODUK</th>
                <th style="width: 15%; text-align: center;">BANYAKNYA</th>
                <th style="width: 15%; text-align: right;">HARGA SATUAN</th>
                <th style="width: 20%; text-align: right;">Total Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($details as $d): ?>
            <tr>
                <td style="color: #94a3b8;"><?= $no++ ?></td>
                <td><?= htmlspecialchars($d['produk_nama']) ?></td>
                <td style="text-align: center; color: #3b82f6;"><?= $d['jumlah'] ?> <?= strtoupper($d['satuan']) ?></td>
                <td style="text-align: right;"><?= formatRupiah($d['harga_satuan']) ?></td>
                <td style="text-align: right;"><?= formatRupiah($d['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="total-section" style="page-break-inside: avoid;">
        <div class="total-box">
            <div class="total-row"><span>Total Bayar</span><span><?= formatRupiah($pesanan['total']) ?></span></div>
            <div class="total-row grand"><span>TOTAL BELANJA</span><span><?= formatRupiah($pesanan['total']) ?></span></div>
        </div>
    </div>
    
    <div class="signatures" style="page-break-inside: avoid;">
        <div class="sig-box">
            <div class="sig-title">SOPIR</div>
            <div class="sig-line"></div>
            <div class="sig-name"><?= htmlspecialchars($pesanan['sopir_nama'] ?: '............................') ?></div>
        </div>
        <div class="sig-box">
            <div class="sig-title">KONSUMEN</div>
            <div class="sig-line"></div>
            <div class="sig-name"><?= htmlspecialchars($pesanan['konsumen_nama']) ?></div>
        </div>
    </div>
    
    <div style="margin-top: 25px; text-align: center; border-top: 2px solid #e2e8f0; padding-top: 10px; color: #94a3b8; font-size: 11px; font-weight: 800; letter-spacing: 1px; page-break-inside: avoid;">
        TERIMA KASIH TELAH MEMILIH LAYANAN KAMI. SIMPAN DOKUMEN INI SEBAGAI BUKTI TRANSAKSI YANG SAH.
    </div>
</div>

</body>
</html>
