<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('super_admin');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_pesanan.xls");

echo "<table border='1'>";
echo "<tr>
        <th>ID Pesanan</th>
        <th>Nama Konsumen</th>
        <th>Telepon</th>
        <th>Alamat</th>
        <th>Status</th>
        <th>Total</th>
        <th>Tanggal</th>
      </tr>";

$stmt = $pdo->query("SELECT p.*, u.nama, u.telepon 
    FROM pesanan p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC");

while ($row = $stmt->fetch()) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['nama']}</td>
            <td>{$row['telepon']}</td>
            <td>{$row['alamat_pengiriman']}</td>
            <td>{$row['status']}</td>
            <td>{$row['total']}</td>
            <td>{$row['created_at']}</td>
          </tr>";
}
echo "</table>";