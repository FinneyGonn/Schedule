<?php
// ============================================================
//  api/v1/admin/actividad.php
//  Método: GET — Últimas solicitudes de cambio de rol
//  Usado por: panel Home (actividad reciente) y panel Solicitudes
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

try {
    // Traer id de la solicitud (necesario para aprobar/rechazar desde el JS),
    // nombre del usuario, rol solicitado, estado y fecha
    $query = "SELECT
                s.id,
                u.nombre,
                r.nombre_rol,
                s.estado,
                DATE_FORMAT(s.fecha_solicitud, '%d/%m/%Y %H:%i') AS created_at
              FROM solicitudes_rol s
              JOIN usuarios u ON s.usuario_id = u.id
              JOIN roles    r ON s.rol_solicitado = r.id
              ORDER BY s.fecha_solicitud DESC
              LIMIT 20";

    $res = $conn->query($query);

    if (!$res) {
        throw new Exception($conn->error);
    }

    $actividad = [];
    while ($row = $res->fetch_assoc()) {
        $actividad[] = $row;
    }

    echo json_encode($actividad);

} catch (Exception $e) {
    error_log('[actividad.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener actividad.']);
}