<?php
/**
 * Project: qblockx
 * Created by: Wayne
 * Generated: 2026-03-09
 * 
 */

session_start();

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

function requireAdmin() {
    requireAuth();
    if ($_SESSION['role'] !== 'admin') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
}

function getAuthUser() {
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['email']   ?? null,
        'role'  => $_SESSION['role']    ?? null,
    ];
}
