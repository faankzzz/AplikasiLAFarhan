<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('admin_penjualan');

// Header Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=report_penjualan.xls");

// Ambil data pesanan
$stmt = $pdo->query("
    SELECT p.*, u.nama as nama_konsumen
    FROM pesanan p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");
$data = $stmt->fetchAll();

// Output Excel (format table HTML)
echo "<table border='1'>";
echo "<tr>
    <th>ID</th>
    <th>Nama Konsumen</th>
    <th>Total</th>
    <th>Metode</th>
    <th>Status</th>
    <th>Tanggal</th>
</tr>";

foreach ($data as $d) {
    echo "<tr>
        <td>{$d['id']}</td>
        <td>{$d['nama_konsumen']}</td>
        <td>{$d['total']}</td>
        <td>{$d['metode_bayar']}</td>
        <td>{$d['status']}</td>
        <td>{$d['created_at']}</td>
    </tr>";
}

echo "</table>";