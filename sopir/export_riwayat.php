<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('sopir');

// Set header agar browser mendownload file sebagai Excel (.xls)
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Riwayat Pengiriman Sopir_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil data riwayat pesanan (sama seperti di halaman utama)
$stmtP = $pdo->prepare("SELECT p.*, u.nama as konsumen_nama, 
    COALESCE(p.alamat_pengiriman, u.alamat) as alamat,
    (SELECT GROUP_CONCAT(CONCAT(pr.nama,' x',pd.jumlah) SEPARATOR ', ') FROM transaksi_pesanan pd JOIN produk pr ON pd.produk_id = pr.id WHERE pd.pesanan_id = p.id) as produk_list
    FROM pesanan p JOIN users u ON p.user_id = u.id 
    WHERE p.sopir_id = ? AND p.status = 'selesai' 
    ORDER BY p.updated_at DESC");
$stmtP->execute([getUserId()]);
$riwayatPesanan = $stmtP->fetchAll();
?>

<table border="1">
    <tr>
        <th colspan="5" style="font-size: 16px; font-weight: bold;">LAPORAN RIWAYAT PENGIRIMAN</th>
    </tr>
    <tr>
        <th>No</th>
        <th>ID Pesanan</th>
        <th>Nama Konsumen</th>
        <th>Alamat Pengiriman</th>
        <th>Produk</th>
        <th>Tanggal Selesai</th>
    </tr>
    <?php 
    $no = 1;
    foreach ($riwayatPesanan as $rp): ?>
    <tr>
        <td><?= $no++ ?></td>
        <td>ORDER #<?= str_pad($rp['id'], 6, '0', STR_PAD_LEFT) ?></td>
        <td><?= htmlspecialchars($rp['konsumen_nama']) ?></td>
        <td><?= htmlspecialchars($rp['alamat']) ?></td>
        <td><?= htmlspecialchars($rp['produk_list']) ?></td>
        <td><?= $rp['updated_at'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>