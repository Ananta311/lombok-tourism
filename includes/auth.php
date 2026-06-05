<?php
// ============================================
// includes/auth.php
// Autentikasi & Session Helper
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin($redirect = '../index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireAdmin($redirect = '../index.php') {
    if (!isAdmin()) {
        header("Location: $redirect");
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $id = intval($_SESSION['user_id']);
    $stmt = $db->prepare("SELECT u.*, p.full_name, p.bio, p.phone, p.location, p.foto_profile 
                        FROM users u 
                        LEFT JOIN profile p ON p.user_id = u.id 
                        WHERE u.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}