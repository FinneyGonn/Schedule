<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

// Usamos el ID del usuario que guardamos en la sesión al loguearnos
session_start();
$usuario_id = $_SESSION['user_id'] ?? null;

if (!$usuario_id) {
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

try {
    // Ajustado a tus nombres de columna: mensaje, leida, created_at
    $query = "SELECT id, mensaje, tipo, leida, created_at 
              FROM notificaciones 
              WHERE usuario_id = ? 
              ORDER BY created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notificaciones = [];
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = $row;
    }

    echo json_encode($notificaciones);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}