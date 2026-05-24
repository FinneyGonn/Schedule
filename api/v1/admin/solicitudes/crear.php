<?php
session_start();
require_once '../../../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$rol_id = (int)($data['rol_id'] ?? 0);
$motivo = trim($data['motivo'] ?? '');

if (!in_array($rol_id, [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'Rol inválido']);
    exit;
}

try {
    $check = $conn->prepare("SELECT id FROM solicitudes_rol WHERE usuario_id = ? AND estado = 'pendiente'");
    $check->bind_param("i", $_SESSION['user_id']);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya tienes una solicitud pendiente']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO solicitudes_rol (usuario_id, rol_solicitado_id, motivo_solicitud, estado, created_at) 
                            VALUES (?, ?, ?, 'pendiente', NOW())");
    $stmt->bind_param("iis", $_SESSION['user_id'], $rol_id, $motivo);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Solicitud enviada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la solicitud']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>