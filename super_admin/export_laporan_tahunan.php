<?php
/**
 * SKRIP EXPORT LAPORAN TAHUNAN (VERSI SEDERHANA)
 * ---------------------------------------------
 * Digunakan untuk mengunduh laporan tahunan dalam format Excel.
 * Dapat diakses oleh Super Admin.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// Pastikan hanya Super Admin yang bisa mengakses
requireRole('super_admin');

// Ambil tahun dari parameter GET, default ke tahun saat ini
$tahun = $_GET['tahun'] ?? date('Y');

// Query data untuk laporan tahunan
// Contoh: Total Pesanan, Total Pendapatan, Jumlah Pengguna Baru
try {
    // Total Pesanan
    $stmtTotalPesanan = $pdo->prepare("SELECT COUNT(id) FROM pesanan WHERE YEAR(created_at) = ? AND status != 'menunggu'");
    $stmtTotalPesanan->execute([$tahun]);
    $totalPesanan = $stmtTotalPesanan->fetchColumn();

    // Total Pendapatan
    $stmtTotalPendapatan = $pdo->prepare("SELECT SUM(total) FROM pesanan WHERE YEAR(created_at) = ? AND status = 'selesai'");
    $stmtTotalPendapatan->execute([$tahun]);
    $totalPendapatan = $stmtTotalPendapatan->fetchColumn();

    // Jumlah Pengguna Baru (Konsumen)
    $stmtPenggunaBaru = $pdo->prepare("SELECT COUNT(id) FROM users WHERE YEAR(created_at) = ? AND role = 'konsumen'");
    $stmtPenggunaBaru->execute([$tahun]);
    $penggunaBaru = $stmtPenggunaBaru->fetchColumn();

    // Jumlah Komplain Selesai
    $stmtKomplainSelesai = $pdo->prepare("SELECT COUNT(id) FROM komplain WHERE YEAR(created_at) = ? AND status = 'selesai'");
    $stmtKomplainSelesai->execute([$tahun]);
    $komplainSelesai = $stmtKomplainSelesai->fetchColumn();

    // Ambil Detail Pesanan Tahunan
    $stmtOrders = $pdo->prepare("SELECT p.*, u.nama as customer_name FROM pesanan p JOIN users u ON p.user_id = u.id WHERE YEAR(p.created_at) = ? ORDER BY p.created_at DESC");
    $stmtOrders->execute([$tahun]);
    $ordersDetail = $stmtOrders->fetchAll();

    // Ambil Detail Mutasi Stok Tahunan
    $stmtMutasi = $pdo->prepare("SELECT m.*, pr.nama as produk_nama, pr.satuan FROM mutasi_stok m JOIN produk pr ON m.produk_id = pr.id WHERE YEAR(m.tanggal) = ? ORDER BY m.created_at DESC");
    $stmtMutasi->execute([$tahun]);
    $mutasiDetail = $stmtMutasi->fetchAll();

    // Ambil Detail Komplain Tahunan
    $stmtKomplain = $pdo->prepare("SELECT k.*, u.nama as customer_name FROM komplain k JOIN users u ON k.user_id = u.id WHERE YEAR(k.created_at) = ? ORDER BY k.created_at DESC");
    $stmtKomplain->execute([$tahun]);
    $komplainDetail = $stmtKomplain->fetchAll();

} catch (PDOException $e) {
    // Tangani error database
    die("Error database: " . $e->getMessage());
}

// Buat nama file yang informatif
$namaFile = 'laporan_tahunan_' . $tahun . '.xls';

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
        <td colspan="2" style="font-size:16pt; font-weight:bold;">LAPORAN TAHUNAN — ALFA ONE (TAHUN <?= $tahun ?>)</td>
    </tr>
    <tr>
        <td colspan="2" style="font-size:11pt; color:#555;">
            Dicetak pada: <?= date('d/m/Y H:i') ?> WIB
        </td>
    </tr>
</table>

<br>

<table border="1" cellpadding="6" cellspacing="0">
    <tr bgcolor="#1d4ed8">
        <td colspan="2" style="font-weight:bold; color:white; font-size:12pt;">RINGKASAN KINERJA TAHUNAN</td>
    </tr>
    <tr bgcolor="#dbeafe">
        <td style="font-weight:bold; width:300px;">Indikator</td>
        <td style="font-weight:bold; width:200px;">Nilai</td>
    </tr>
    <tr>
        <td>Total Pesanan Diterima</td>
        <td><?= number_format($totalPesanan, 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td>Total Pendapatan Bruto (Pesanan Selesai)</td>
        <td>Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td>Jumlah Konsumen Baru</td>
        <td><?= number_format($penggunaBaru, 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td>Jumlah Komplain Diselesaikan</td>
        <td><?= number_format($komplainSelesai, 0, ',', '.') ?></td>
    </tr>
</table>

<br><br>

<!-- BAGIAN DETAIL PESANAN -->
<table border="1" cellpadding="6" cellspacing="0">
    <tr bgcolor="#1e293b">
        <td colspan="6" style="font-weight:bold; color:white; font-size:12pt;">DETAIL PESANAN TAHUN <?= $tahun ?></td>
    </tr>
    <tr bgcolor="#f1f5f9">
        <td style="font-weight:bold;">ID Pesanan</td>
        <td style="font-weight:bold;">Nama Pelanggan</td>
        <td style="font-weight:bold;">Tanggal Transaksi</td>
        <td style="font-weight:bold;">Status</td>
        <td style="font-weight:bold;">Metode Bayar</td>
        <td style="font-weight:bold;">Total (Rp)</td>
    </tr>
    <?php if(empty($ordersDetail)): ?>
        <tr><td colspan="6" align="center">Tidak ada data pesanan di tahun ini.</td></tr>
    <?php else: ?>
        <?php foreach($ordersDetail as $o): ?>
        <tr>
            <td>#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars($o['customer_name']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
            <td><?= strtoupper($o['status']) ?></td>
            <td><?= strtoupper($o['metode_bayar'] ?? '-') ?></td>
            <td align="right"><?= number_format($o['total'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<br><br>

<!-- BAGIAN DETAIL MUTASI STOK -->
<table border="1" cellpadding="6" cellspacing="0">
    <tr bgcolor="#1e293b">
        <td colspan="7" style="font-weight:bold; color:white; font-size:12pt;">DETAIL MUTASI STOK TAHUN <?= $tahun ?></td>
    </tr>
    <tr bgcolor="#f1f5f9">
        <td style="font-weight:bold;">Tanggal</td>
        <td style="font-weight:bold;">Produk</td>
        <td style="font-weight:bold;">Tipe</td>
        <td style="font-weight:bold;">Jumlah</td>
        <td style="font-weight:bold;">Stok Awal</td>
        <td style="font-weight:bold;">Stok Akhir</td>
        <td style="font-weight:bold;">Keterangan</td>
    </tr>
    <?php if(empty($mutasiDetail)): ?>
        <tr><td colspan="7" align="center">Tidak ada data mutasi stok di tahun ini.</td></tr>
    <?php else: ?>
        <?php foreach($mutasiDetail as $m): ?>
        <tr>
            <td><?= date('d/m/Y', strtotime($m['tanggal'])) ?></td>
            <td><?= htmlspecialchars($m['produk_nama']) ?> (<?= strtoupper($m['satuan']) ?>)</td>
            <td style="color: <?= $m['tipe'] == 'masuk' ? 'green' : 'red' ?>;"><?= strtoupper($m['tipe']) ?></td>
            <td align="center"><?= ($m['tipe'] == 'masuk' ? '+' : '-') . $m['jumlah'] ?></td>
            <td align="center"><?= $m['stok_awal'] ?></td>
            <td align="center"><?= $m['stok_akhir'] ?></td>
            <td><?= htmlspecialchars($m['keterangan']) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>
<br><br>

<!-- BAGIAN DETAIL KOMPLAIN -->
<table border="1" cellpadding="6" cellspacing="0">
    <tr bgcolor="#1e293b">
        <td colspan="5" style="font-weight:bold; color:white; font-size:12pt;">DETAIL KOMPLAIN TAHUN <?= $tahun ?></td>
    </tr>
    <tr bgcolor="#f1f5f9">
        <td style="font-weight:bold;">ID</td>
        <td style="font-weight:bold;">Pelanggan</td>
        <td style="font-weight:bold;">Tanggal Masuk</td>
        <td style="font-weight:bold;">Status</td>
        <td style="font-weight:bold;">Isi Pesan Komplain</td>
    </tr>
    <?php if(empty($komplainDetail)): ?>
        <tr><td colspan="5" align="center">Tidak ada data komplain di tahun ini.</td></tr>
    <?php else: ?>
        <?php foreach($komplainDetail as $k): ?>
        <tr>
            <td>#<?= $k['id'] ?></td>
            <td><?= htmlspecialchars($k['customer_name']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($k['created_at'])) ?></td>
            <td><?= strtoupper($k['status']) ?></td>
            <td><?= htmlspecialchars($k['pesan'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>
</body>
</html>