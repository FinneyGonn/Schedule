<?php
// ==================== SOLICITUD CREAR - VERSIÓN MINIMAL ====================
session_start();
require_once '../../../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$usuario_id = $_SESSION['user_id'];
$rol_solicitado = (int)($data['rol_id'] ?? 0);
$motivo = trim($data['motivo'] ?? '');

if (!in_array($rol_solicitado, [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'Rol inválido']);
    exit;
}

try {
    $check = $conn->prepare("SELECT id FROM solicitudes_rol WHERE usuario_id = ? AND estado = 'pendiente'");
    $check->bind_param("i", $usuario_id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya tienes una solicitud pendiente']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO solicitudes_rol 
        (usuario_id, rol_solicitado_id, motivo_solicitud, estado, created_at) 
        VALUES (?, ?, ?, 'pendiente', NOW())");
    
    $stmt->bind_param("iis", $usuario_id, $rol_solicitado, $motivo);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Solicitud enviada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar']);
    }

} catch (Exception $e) {
    error_log("Error crear solicitud: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>