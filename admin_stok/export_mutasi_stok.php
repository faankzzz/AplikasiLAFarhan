<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireRole(['admin_barang', 'super_admin']);

// Ambil filter tanggal (kalau ada)
$tanggal = $_GET['tanggal'] ?? '';

// Query data
$sql = "
    SELECT m.*, p.nama as produk_nama, p.satuan
    FROM mutasi_stok m
    JOIN produk p ON m.produk_id = p.id
";
$params = [];

if ($tanggal) {
    $sql .= " WHERE m.tanggal = ? ";
    $params[] = $tanggal;
}

$sql .= " ORDER BY m.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Header Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_mutasi_stok_" . ($tanggal ?: date('Y-m-d')) . ".xls");

// Format tanggal Indo
function tgl($date) {
    return date('d/m/Y', strtotime($date));
}
?>

<table border="1">
    <thead>
        <tr style="background:#eee; font-weight:bold;">
            <th>No</th>
            <th>Tanggal</th>
            <th>Waktu</th>
            <th>Produk</th>
            <th>Tipe</th>
            <th>Jumlah</th>
            <th>Satuan</th>
            <th>Stok Awal</th>
            <th>Stok Akhir</th>
            <th>Kategori</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; foreach ($data as $d): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= tgl($d['tanggal']) ?></td>
            <td><?= date('H:i', strtotime($d['created_at'])) ?></td>
            <td><?= htmlspecialchars($d['produk_nama']) ?></td>
            <td><?= strtoupper($d['tipe']) ?></td>
            <td><?= $d['jumlah'] ?></td>
            <td><?= strtoupper($d['satuan']) ?></td>
            <td><?= $d['stok_awal'] ?></td>
            <td><?= $d['stok_akhir'] ?></td>
            <td><?= strtoupper(str_replace('_',' ',$d['kategori'])) ?></td>
            <td><?= htmlspecialchars($d['keterangan']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>