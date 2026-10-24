<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('sopir');
$user = getCurrentUser($pdo);

// Ambil riwayat pesanan selesai
$stmtP = $pdo->prepare("SELECT p.*, u.nama as konsumen_nama, u.telepon,
    COALESCE(p.alamat_pengiriman, u.alamat) as alamat,
    (SELECT GROUP_CONCAT(CONCAT(pr.nama,' x',pd.jumlah) SEPARATOR ', ') FROM transaksi_pesanan pd JOIN produk pr ON pd.produk_id = pr.id WHERE pd.pesanan_id = p.id) as produk_list
    FROM pesanan p JOIN users u ON p.user_id = u.id 
    WHERE p.sopir_id = ? AND p.status = 'selesai' 
    ORDER BY p.updated_at DESC");
$stmtP->execute([getUserId()]);
$riwayatPesanan = $stmtP->fetchAll();

// Ambil riwayat penggantian komplain selesai
$stmtK = $pdo->prepare("SELECT k.*, u.nama as konsumen_nama, u.telepon, u.alamat 
    FROM komplain k JOIN users u ON k.user_id = u.id 
    WHERE k.sopir_id = ? AND k.status = 'selesai' 
    ORDER BY k.tanggal_penggantian DESC");
$stmtK->execute([getUserId()]);
$riwayatKomplain = $stmtK->fetchAll();

function tgl_indo($timestamp) {
    if (!$timestamp) return '-';
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $d = date('d', $time);
    $m = $bulan[(int)date('m', $time)];
    $y = date('Y', $time);
    $hi = date('H:i', $time);
    return "$d $m $y $hi";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Riwayat Pengiriman — <?= htmlspecialchars($user['nama']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1e293b; line-height: 1.5; padding: 0; margin: 0; background: #f1f5f9; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #0f172a; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0; color: #64748b; font-size: 14px; }
        
        .info-summary { display: table; width: 100%; margin-bottom: 30px; font-size: 14px; }
        .info-summary div { display: table-cell; vertical-align: top; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8fafc; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; padding: 12px 15px; border: 1px solid #e2e8f0; text-align: left; }
        td { padding: 10px 12px; border: 1px solid #e2e8f0; font-size: 12px; vertical-align: top; word-break: break-word; line-height: 1.4; }
        
        .section-title { font-size: 16px; font-weight: 800; margin: 30px 0 15px; color: #3b82f6; display: flex; align-items: center; gap: 10px; }
        
        .footer { margin-top: 50px; color: #64748b; }
        .signature { margin-top: 80px; font-weight: 700; text-decoration: underline; }
        
        /* Layout utility */
        .layout-table { width: 100%; border: none !important; margin-bottom: 30px; }
        .layout-table td { border: none !important; padding: 0 !important; vertical-align: top; }

        #print-area { width: 700px; margin: 0; background: white; padding: 30px; box-sizing: border-box; }
        .print-container { display: flex; justify-content: center; padding: 40px 0; }

        /* Fix page breaks for both browser print and html2pdf */
        table { page-break-inside: auto; width: 100% !important; }
        tr { page-break-inside: avoid !important; break-inside: avoid !important; -webkit-column-break-inside: avoid !important; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }

        @media print {
            body { padding: 0; background: white; }
            .no-print, .no-print-btn, .no-print-area { display: none !important; }
            #print-area { padding: 0 !important; max-width: 100% !important; }
        }

        .no-print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 9999;
        }

        .download-btn {
            right: 210px;
            background: #10b981;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>

    <div class="no-print" style="position: fixed; bottom: 30px; right: 30px; display: flex; gap: 10px; z-index: 9999;">
        <button class="no-print-btn download-btn" id="downloadPdf">
            <i class="fas fa-download"></i> Download PDF
        </button>
        <button class="no-print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak / Simpan
        </button>
    </div>

    <div class="print-container">
    <div id="print-area">

    <div class="header">
        <h1>Riwayat Pengiriman Sopir</h1>
        <p>Pengiriman Sopir</p>
    </div>
    <table class="layout-table">    
        <tr>
            <td style="text-align: left; width: 50%;">
                <strong>Nama Driver:</strong> <?= htmlspecialchars($user['nama']) ?><br>
                <strong>ID Driver:</strong> DRV-<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?>
            </td>
            <td style="text-align: right; width: 50%;">
                <strong>Tanggal Cetak:</strong> <?= tgl_indo(time()) ?><br>
                <strong>Status:</strong> Laporan Selesai
            </td>
        </tr>
    </table>

    <div class="section-title">Riwayat Pengiriman </div>
    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 75px;">ID Order</th>
                <th style="width: 120px;">Konsumen</th>
                <th>Alamat & Produk</th>
                <th style="width: 130px;">Waktu Selesai</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($riwayatPesanan)): ?>
                <tr><td colspan="5" style="text-align: center;">Tidak ada data.</td></tr>
            <?php else: ?>
                <?php $n = 1; foreach ($riwayatPesanan as $rp): ?>
                    <tr>
                        <td><?= $n++ ?></td>
                        <td>#<?= str_pad($rp['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($rp['konsumen_nama']) ?></strong><br>
                            <small><?= htmlspecialchars($rp['telepon']) ?></small>
                        </td>
                        <td>
                            <div style="margin-bottom: 5px;">📍 <?= htmlspecialchars($rp['alamat']) ?></div>
                            <div style="color: #64748b; font-size: 11px; margin-bottom: 4px;">📦 <?= htmlspecialchars($rp['produk_list']) ?></div>
                            <div style="font-weight: 800; color: #0f172a; font-size: 12px;">💰 <?= formatRupiah($rp['total']) ?></div>
                        </td>
                        <td><?= tgl_indo($rp['updated_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($riwayatKomplain)): ?>
    <div class="section-title">2. Riwayat Penggantian Komplain</div>
    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 75px;">ID Komplain</th>
                <th style="width: 120px;">Konsumen</th>
                <th>Alamat & Deskripsi</th>
                <th style="width: 130px;">Waktu Selesai</th>
            </tr>
        </thead>
        <tbody>
            <?php $n = 1; foreach ($riwayatKomplain as $rk): ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <td>#K-<?= str_pad($rk['id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($rk['konsumen_nama']) ?></strong><br>
                        <small><?= htmlspecialchars($rk['telepon']) ?></small>
                    </td>
                    <td>
                        <div style="margin-bottom: 5px;">📍 <?= htmlspecialchars($rk['alamat']) ?></div>
                        <div style="font-style: italic; color: #64748b; font-size: 12px;">"<?= htmlspecialchars($rk['deskripsi']) ?>"</div>
                    </td>
                    <td><?= tgl_indo($rk['tanggal_penggantian']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <table class="layout-table" style="margin-top: 50px;">
        <tr>
            <td style="text-align: right;">
                <p style="margin: 0; font-size: 14px; color: #64748b;">Dicetak secara otomatis oleh sistem Alfa One.</p>
                <div class="signature"><?= htmlspecialchars($user['nama']) ?></div>
            </td>
        </tr>
    </table>
    </div> <!-- End print-area -->
    </div> <!-- End print-container -->

    <script>
        const downloadAction = function () {
            // Scroll ke atas dulu biar ngga kepotong canvas-nya
            window.scrollTo(0, 0);
            
            // Beri jeda sedikit biar browser selesai scroll dan render
            setTimeout(() => {
                const element = document.getElementById('print-area');
                const opt = {
                    margin:       [10, 10, 10, 10],
                    filename:     'Riwayat_Pengiriman_<?= date('Ymd_His') ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { 
                        scale: 2, 
                        useCORS: true, 
                        letterRendering: true,
                        backgroundColor: '#ffffff'
                    },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                    pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
                };

                html2pdf().set(opt).from(element).save();
            }, 500);
        };

        document.getElementById('downloadPdf').addEventListener('click', downloadAction);
    </script>
</body>
</html>
