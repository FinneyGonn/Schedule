<?php
session_start();
require_once '../../../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$usuario_id = $_SESSION['usuario_id'];
$rol_solicitado = $data['rol_id'];

try {
    // Verificamos si ya existe una pendiente
    $check = $conn->query("SELECT id FROM solicitudes_rol WHERE usuario_id = $usuario_id AND estado = 'pendiente'");
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya tienes una solicitud pendiente.']);
        exit;
    }

    // Usamos created_at y NOW() para que MySQL ponga la hora actual
    $sql = "INSERT INTO solicitudes_rol (usuario_id, rol_solicitado_id, created_at, estado) 
            VALUES ($usuario_id, $rol_solicitado, NOW(), 'pendiente')";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Solicitud enviada correctamente']);
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}