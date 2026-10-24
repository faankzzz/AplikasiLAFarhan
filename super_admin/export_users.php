<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('super_admin');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_pegawai_" . date('Y-m-d') . ".xls");

echo "<table border='1'>";
echo "<tr style='background:#eee; font-weight:bold;'>
        <th>No</th>
        <th>Nama Pegawai</th>
        <th>Email</th>
        <th>Telepon</th>
        <th>Role</th>
        <th>Alamat</th>
        <th>Tanggal Bergabung</th>
      </tr>";

$stmt = $pdo->query("SELECT * FROM users WHERE role IN ('super_admin', 'admin_penjualan', 'admin_barang', 'sopir') ORDER BY role, nama ASC");
$no = 1;
while ($row = $stmt->fetch()) {
    $role = strtoupper(str_replace('_', ' ', $row['role']));
    echo "<tr>
            <td>{$no}</td>
            <td>" . htmlspecialchars($row['nama']) . "</td>
            <td>" . htmlspecialchars($row['email']) . "</td>
            <td>'{$row['telepon']}</td>
            <td>{$role}</td>
            <td>" . htmlspecialchars($row['alamat']) . "</td>
            <td>{$row['created_at']}</td>
          </tr>";
    $no++;
}

echo "</table>";
