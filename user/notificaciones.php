<?php
// Ajusta esta ruta para que llegue a tu archivo de conexión real
require_once '../config/config.php'; 

header('Content-Type: application/json');
session_start();

// Usamos el ID de la sesión. Si no existe, usamos el 1 para pruebas.
$usuario_id = $_SESSION['user_id'] ?? 1; 

try {
    // Usamos los nombres de tu tabla: mensaje, tipo, leida, created_at
    $sql = "SELECT id, mensaje, tipo, leida, created_at 
            FROM notificaciones 
            WHERE usuario_id = ? 
            ORDER BY created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($notificaciones);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}