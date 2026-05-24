<?php
// ============================================================
//  api/v1/admin/stats.php
//  Método: GET — Estadísticas generales del dashboard
//  Seguridad: autenticación por sesión
// ============================================================
header('Content-Type: application/json; charset=utf-8');

require_once '../../../config/config.php';

// ── Autenticación ────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['rol_id'] != 1) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado.']);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Sin conexión a la base de datos.']);
    exit;
}

$response = [];

try {
    // 1. Total de usuarios registrados
    $res = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
    $response['total_usuarios'] = (int)$res->fetch_assoc()['total'];

    // 2. Profesores — rol_id = 2
    $res = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol_id = 2");
    $response['total_profesores'] = (int)$res->fetch_assoc()['total'];

    // 3. Solicitudes pendientes
    $res = $conn->query("SELECT COUNT(*) AS total FROM solicitudes_rol WHERE estado = 'pendiente'");
    $response['total_solicitudes'] = $res ? (int)$res->fetch_assoc()['total'] : 0;

    // 4. Grupos creados
    $res = $conn->query("SELECT COUNT(*) AS total FROM grupos");
    $response['total_grupos'] = $res ? (int)$res->fetch_assoc()['total'] : 0;

    // 5. Salones — tabla: salones
    $res = $conn->query("SELECT COUNT(*) AS total FROM salones");
    $response['total_salones'] = $res ? (int)$res->fetch_assoc()['total'] : 0;

    // 6. Horarios activos — tabla: horarios
    $res = $conn->query("SELECT COUNT(*) AS total FROM horarios");
    $response['total_horarios'] = $res ? (int)$res->fetch_assoc()['total'] : 0;

    echo json_encode($response);

} catch (Exception $e) {
    // Loguear internamente, no exponer al cliente
    error_log('[stats.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener estadísticas.']);
}