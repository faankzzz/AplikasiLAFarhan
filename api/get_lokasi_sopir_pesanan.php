<?php
require_once __DIR__ . '/../config/auth.php';
header('Content-Type: application/json');

$user = getCurrentUser($pdo);
if (!$user || $user['role'] !== 'konsumen') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$pesananId = (int)($_GET['pesanan_id'] ?? 0);
if (!$pesananId) {
    echo json_encode(['status' => 'error', 'message' => 'Missing pesanan_id']);
    exit;
}

try {
    // Ambil sopir YANG MILIK PESANAN INI dan milik konsumen login
    $stmt = $pdo->prepare("
        SELECT u.id, u.nama, u.latitude, u.longitude 
        FROM users u 
        JOIN pesanan p ON u.id = p.sopir_id 
        WHERE p.id = ? AND p.user_id = ? AND u.role = 'sopir' AND u.latitude IS NOT NULL
    ");
    $stmt->execute([$pesananId, $user['id']]);
    $sopir = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sopir) {
        echo json_encode(['status' => 'success', 'data' => $sopir]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Sopir tidak ditemukan atau GPS belum aktif']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

