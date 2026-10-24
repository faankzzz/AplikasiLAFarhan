<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('admin_penjualan');

// Filter (sama seperti di rekap.php)
$filterTanggal = $_GET['tanggal'] ?? '';
$filterBulan   = $_GET['bulan']   ?? '';
$filterMetode  = $_GET['metode']  ?? 'all';
$filterTipe    = $_GET['tipe']    ?? 'all';

$where  = "p.status != 'menunggu'";
$params = [];

if ($filterTanggal) {
    $where   .= " AND DATE(p.created_at) = ?";
    $params[] = $filterTanggal;
} elseif ($filterBulan) {
    $where   .= " AND DATE_FORMAT(p.created_at, '%Y-%m') = ?";
    $params[] = $filterBulan;
}

if ($filterMetode !== 'all') {
    $where   .= " AND p.metode_bayar = ?";
    $params[] = $filterMetode;
}

if ($filterTipe !== 'all') {
    $where   .= " AND u.role_customer = ?";
    $params[] = $filterTipe;
}

$stmt = $pdo->prepare("
    SELECT p.*, u.nama AS konsumen_nama, u.role_customer
    FROM pesanan p
    JOIN users u ON p.user_id = u.id
    WHERE $where
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$data = $stmt->fetchAll();

// Hitung ringkasan
$totalCash     = 0;
$totalTransfer = 0;
$matrix = [
    'toko'  => ['cash' => 0, 'transfer' => 0],
    'umum'  => ['cash' => 0, 'transfer' => 0],
];

foreach ($data as $p) {
    if ($p['metode_bayar'] === 'cash') $totalCash += $p['total'];
    else $totalTransfer += $p['total'];

    $tipe = $p['role_customer'];
    if ($tipe !== 'toko' && $tipe !== 'umum') {
        $tipe = 'umum';
    }
    $metode = $p['metode_bayar'];
    if ($metode !== 'cash' && $metode !== 'transfer') {
        $metode = 'cash';
    }
    if (isset($matrix[$tipe][$metode])) {
        $matrix[$tipe][$metode] += $p['total'];
    }
}
$totalAll = $totalCash + $totalTransfer;

// Buat nama file yang informatif
$namaFile = 'rekap_penjualan';
if ($filterTanggal) $namaFile .= '_' . $filterTanggal;
elseif ($filterBulan) $namaFile .= '_' . $filterBulan;
if ($filterMetode !== 'all') $namaFile .= '_' . $filterMetode;
if ($filterTipe   !== 'all') $namaFile .= '_' . $filterTipe;
$namaFile .= '.xls';

// Header untuk download Excel
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$namaFile\"");
header("Pragma: no-cache");
header("Expires: 0");

// BOM agar karakter Indonesia terbaca dengan benar di Excel
echo "\xEF\xBB\xBF";
?>
<html>
<head><meta charset="UTF-8"></head>
<body>

<table border="0" cellpadding="6">
    <tr>
        <td colspan="7" style="font-size:16pt; font-weight:bold;">REKAPITULASI PENJUALAN — ALFA ONE</td>
    </tr>
    <tr>
        <td colspan="7" style="font-size:11pt; color:#555;">
            Dicetak pada: <?= date('d/m/Y H:i') ?> WIB
            <?php if ($filterTanggal): ?> | Tanggal: <?= $filterTanggal ?><?php endif; ?>
            <?php if ($filterBulan):   ?> | Bulan: <?= $filterBulan ?><?php endif; ?>
            <?php if ($filterMetode !== 'all'): ?> | Metode: <?= strtoupper($filterMetode) ?><?php endif; ?>
            <?php if ($filterTipe   !== 'all'): ?> | Kategori: <?= strtoupper($filterTipe) ?><?php endif; ?>
        </td>
    </tr>
</table>

<br>

<!-- RINGKASAN OMZET -->
<table border="1" cellpadding="6" cellspacing="0">
    <tr bgcolor="#1d4ed8">
        <td colspan="4" style="font-weight:bold; color:white; font-size:12pt;">RINGKASAN PENDAPATAN</td>
    </tr>
    <tr bgcolor="#dbeafe">
        <td style="font-weight:bold; width:220px;">Keterangan</td>
        <td style="font-weight:bold; width:200px;">Nominal</td>
        <td style="font-weight:bold; width:220px;">Keterangan</td>
        <td style="font-weight:bold; width:200px;">Nominal</td>
    </tr>
    <tr>
        <td>Pendapatan Tunai (Cash)</td>
        <td>Rp <?= number_format($totalCash, 0, ',', '.') ?></td>
        <td>Pendapatan Transfer</td>
        <td>Rp <?= number_format($totalTransfer, 0, ',', '.') ?></td>
    </tr>
    <tr bgcolor="#eff6ff">
        <td style="font-weight:bold;">TOTAL PENDAPATAN BRUTO</td>
        <td colspan="3" style="font-weight:bold; font-size:13pt;">Rp <?= number_format($totalAll, 0, ',', '.') ?></td>
    </tr>
</table>

<br>

<!-- SEGMENTASI -->
<table border="1" cellpadding="6" cellspacing="0">
    <tr bgcolor="#92400e">
        <td colspan="4" style="font-weight:bold; color:white; font-size:12pt;">SEGMENTASI BISNIS</td>
    </tr>
    <tr bgcolor="#fef3c7">
        <td style="font-weight:bold; width:220px;">Segmen</td>
        <td style="font-weight:bold; width:200px;">Metode</td>
        <td style="font-weight:bold; width:200px;">Nominal</td>
        <td style="font-weight:bold; width:200px;">Subtotal Segmen</td>
    </tr>
    <tr>
        <td rowspan="2">TOKO (Bisnis)</td>
        <td>Tunai (Cash)</td>
        <td>Rp <?= number_format($matrix['toko']['cash'], 0, ',', '.') ?></td>
        <td rowspan="2" style="font-weight:bold;">Rp <?= number_format($matrix['toko']['cash'] + $matrix['toko']['transfer'], 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td>Transfer Bank</td>
        <td>Rp <?= number_format($matrix['toko']['transfer'], 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td rowspan="2">UMUM (Personal)</td>
        <td>Tunai (Cash)</td>
        <td>Rp <?= number_format($matrix['umum']['cash'], 0, ',', '.') ?></td>
        <td rowspan="2" style="font-weight:bold;">Rp <?= number_format($matrix['umum']['cash'] + $matrix['umum']['transfer'], 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td>Transfer Bank</td>
        <td>Rp <?= number_format($matrix['umum']['transfer'], 0, ',', '.') ?></td>
    </tr>
</table>

<br>

<!-- DETAIL TRANSAKSI -->
<table border="1" cellpadding="6" cellspacing="0">
    <tr bgcolor="#1e293b">
        <td colspan="7" style="font-weight:bold; color:white; font-size:12pt;">DETAIL TRANSAKSI</td>
    </tr>
    <tr bgcolor="#e2e8f0">
        <td style="font-weight:bold;">#</td>
        <td style="font-weight:bold;">No. Invoice</td>
        <td style="font-weight:bold;">Nama Pelanggan</td>
        <td style="font-weight:bold;">Tanggal Transaksi</td>
        <td style="font-weight:bold;">Metode Bayar</td>
        <td style="font-weight:bold;">Kategori</td>
        <td style="font-weight:bold;">Status</td>
        <td style="font-weight:bold;">Nominal (Rp)</td>
    </tr>
    <?php if (empty($data)): ?>
    <tr>
        <td colspan="8" style="text-align:center; color:#999;">Tidak ada data transaksi.</td>
    </tr>
    <?php else: ?>
    <?php $no = 1; foreach ($data as $p): ?>
    <tr>
        <td><?= $no++ ?></td>
        <td>#<?= str_pad($p['id'], 6, '0', STR_PAD_LEFT) ?></td>
        <td><?= htmlspecialchars($p['konsumen_nama']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
        <td><?= strtoupper($p['metode_bayar']) ?></td>
        <td><?= strtoupper($p['role_customer'] ?? 'umum') ?></td>
        <td><?= strtoupper($p['status']) ?></td>
        <td align="right">Rp <?= number_format($p['total'], 0, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr bgcolor="#f1f5f9">
        <td colspan="7" style="font-weight:bold; text-align:right;">TOTAL KESELURUHAN</td>
        <td style="font-weight:bold;" align="right">Rp <?= number_format($totalAll, 0, ',', '.') ?></td>
    </tr>
    <?php endif; ?>
</table>

</body>
</html>
