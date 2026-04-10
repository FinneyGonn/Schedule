<?php
error_reporting(0);
require_once '../../../config/config.php';
header('Content-Type: application/json');

$response = [];

try {
    // 1. Usuarios totales
    $res = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
    $response['total_usuarios'] = $res->fetch_assoc()['total'];

    // 2. Profesores (rol_id = 3)
    $res = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol_id = 3");
    $response['total_profesores'] = $res->fetch_assoc()['total'];

    // 3. Solicitudes pendientes
    $res = $conn->query("SELECT COUNT(*) AS total FROM solicitudes_rol WHERE estado = 'pendiente'");
    $response['total_solicitudes'] = $res->fetch_assoc()['total'];

    // 4. Salones (Usando tu tabla 'aulas')
    $res = $conn->query("SELECT COUNT(*) AS total FROM aulas");
    $response['total_salones'] = $res ? $res->fetch_assoc()['total'] : 0;

    // 5. Horarios (Usando tu tabla 'clases')
    $res = $conn->query("SELECT COUNT(*) AS total FROM clases");
    $response['total_horarios'] = $res ? $res->fetch_assoc()['total'] : 0;

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}