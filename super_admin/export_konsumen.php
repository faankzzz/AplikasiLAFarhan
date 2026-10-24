<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('super_admin');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_konsumen_" . date('Y-m-d') . ".xls");

echo "<table border='1'>";
echo "<tr style='background:#eee; font-weight:bold;'>
        <th>No</th>
        <th>Nama Konsumen</th>
        <th>Tipe</th>
        <th>Email</th>
        <th>Telepon</th>
        <th>Alamat</th>
        <th>Status Verifikasi</th>
        <th>Tanggal Daftar</th>
      </tr>";

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'konsumen' ORDER BY created_at DESC");

$no = 1;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $tipe = $row['role_customer'] == 'toko' ? 'TOKO' : 'UMUM';
    $status = strtoupper($row['status_verifikasi']);

    echo "<tr>
            <td>{$no}</td>
            <td>" . htmlspecialchars($row['nama']) . "</td>
            <td>{$tipe}</td>
            <td>" . htmlspecialchars($row['email']) . "</td>
            <td>'{$row['telepon']}</td>
            <td>" . htmlspecialchars($row['alamat']) . "</td>
            <td>{$status}</td>
            <td>{$row['created_at']}</td>
          </tr>";

    $no++;
}

echo "</table>";
?>