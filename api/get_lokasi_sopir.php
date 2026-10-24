<?php
require_once __DIR__ . '/../config/auth.php';
header('Content-Type: application/json');

$user = getCurrentUser($pdo);
// Bisa diakses oleh admin_penjualan atau super_admin
if (!$user || !in_array($user['role'], ['admin_penjualan', 'super_admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Ambil semua sopir yang posisinya tidak null
    $stmt = $pdo->query("SELECT id, nama, latitude, longitude FROM users WHERE role = 'sopir' AND latitude IS NOT NULL");
    $sopir = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $sopir]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
