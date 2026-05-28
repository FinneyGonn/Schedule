<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

echo json_encode([
    'success' => true,
    'csrf_token' => $_SESSION['csrf_token'] ?? ''
]);
