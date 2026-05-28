<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sin conexión.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    $stmt = $conn->prepare(
        "SELECT s.id, r.nombre AS rol_solicitado,
                DATE_FORMAT(s.created_at, '%d/%m/%Y %H:%i') AS fecha,
                s.estado
         FROM solicitudes_rol s
         JOIN roles r ON s.rol_solicitado_id = r.id
         WHERE s.usuario_id = ?
         ORDER BY s.created_at DESC
         LIMIT 20"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $solicitudes = [];
    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }

    echo json_encode($solicitudes);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
