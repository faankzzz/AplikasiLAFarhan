<?php
require_once __DIR__ . '/database.php';

// ============================================
// AUTH MIDDLEWARE
// ============================================

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array(getUserRole(), $roles)) {
        redirect('login.php');
    }
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([getUserId()]);
    return $stmt->fetch();
}
