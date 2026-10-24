<?php
require_once __DIR__ . '/../config/auth.php';
header('Content-Type: application/json');

$user = getCurrentUser($pdo);
if (!$user || $user['role'] !== 'sopir') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (isset($data['lat']) && isset($data['lng'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET latitude = ?, longitude = ? WHERE id = ?");
        $stmt->execute([$data['lat'], $data['lng'], $user['id']]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing coordinate data']);
}
