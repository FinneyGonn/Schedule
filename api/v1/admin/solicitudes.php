<?php
// ============================================================
//  api/v1/admin/solicitudes.php
//  Métodos: GET (listar) | POST (aprobar o rechazar)
//
//  Al APROBAR:
//    1. Actualiza solicitudes_rol.estado = 'aprobado'
//    2. Actualiza usuarios.rol_id con el rol solicitado
//    3. Rechaza cualquier otra solicitud pendiente del mismo usuario
//
//  Al RECHAZAR:
//    1. Actualiza solicitudes_rol.estado = 'rechazado'
//    (el rol del usuario NO cambia)
//
//  Seguridad: CSRF, prepared statements, autenticación admin
// ============================================================
header('Content-Type: application/json; charset=utf-8');

// config.php ya llama session_start() internamente
require_once '../../../config/config.php';

// ── 1. Autenticación ─────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['rol_id'] != 1) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado.']);
    exit;
}

// ── 2. Validación CSRF ───────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
    $tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($tokenHeader) || !hash_equals($_SESSION['csrf_token'] ?? '', $tokenHeader)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF inválido.']);
        exit;
    }
}

// ── 3. Verificar conexión ────────────────────────────────────
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Sin conexión a la base de datos.']);
    exit;
}

// ── 4. Router ────────────────────────────────────────────────
try {
    match ($method) {
        'GET'  => listarSolicitudes($conn),
        'POST' => responderSolicitud($conn),
        default => respuesta405(),
    };
} catch (Exception $e) {
    error_log('[solicitudes.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor.']);
}

// ════════════════════════════════════════════════════════════
//  GET — Listar todas las solicitudes con datos del usuario
// ════════════════════════════════════════════════════════════
function listarSolicitudes(mysqli $conn): void
{
    $sql = "SELECT
                s.id,
                u.nombre,
                u.apellido,
                ra.nombre_rol  AS rol_actual,
                rs.nombre_rol  AS nombre_rol,
                s.estado,
                DATE_FORMAT(s.fecha_solicitud, '%d/%m/%Y %H:%i') AS created_at
            FROM solicitudes_rol s
            JOIN usuarios u  ON s.usuario_id      = u.id
            JOIN roles    ra ON u.rol_id           = ra.id
            JOIN roles    rs ON s.rol_solicitado_id = rs.id
            ORDER BY
                FIELD(s.estado, 'pendiente', 'aprobado', 'rechazado'),
                s.fecha_solicitud DESC
            LIMIT 50";

    $result = $conn->query($sql);
    if (!$result) throw new Exception($conn->error);

    $solicitudes = [];
    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }

    echo json_encode($solicitudes);
}

// ════════════════════════════════════════════════════════════
//  POST — Aprobar o rechazar una solicitud
// ════════════════════════════════════════════════════════════
function responderSolicitud(mysqli $conn): void
{
    $body     = json_decode(file_get_contents('php://input'), true);
    $id       = (int)($body['id']       ?? 0);
    $decision = trim($body['decision']  ?? '');

    // Validar parámetros
    if (!$id) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'ID de solicitud inválido.']);
        return;
    }

    if (!in_array($decision, ['aprobado', 'rechazado'])) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'Decisión inválida. Usa "aprobado" o "rechazado".']);
        return;
    }

    // Traer la solicitud con el usuario y rol solicitado — prepared statement
    $stmt = $conn->prepare(
        "SELECT s.id, s.usuario_id, s.rol_solicitado_id, s.estado
         FROM solicitudes_rol s
         WHERE s.id = ?
         LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();

    if (!$solicitud) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'Solicitud no encontrada.']);
        return;
    }

    if ($solicitud['estado'] !== 'pendiente') {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'Esta solicitud ya fue procesada.']);
        return;
    }

    // ── Iniciar transacción para que todo ocurra junto o nada ──
    $conn->begin_transaction();

    try {
        // 1. Actualizar el estado de la solicitud
        $upd = $conn->prepare(
            "UPDATE solicitudes_rol SET estado = ? WHERE id = ?"
        );
        $upd->bind_param('si', $decision, $id);
        $upd->execute();

        if ($decision === 'aprobado') {
            // 2. Cambiar el rol del usuario en la tabla usuarios
            $updRol = $conn->prepare(
                "UPDATE usuarios SET rol_id = ? WHERE id = ?"
            );
            $updRol->bind_param('ii', $solicitud['rol_solicitado_id'], $solicitud['usuario_id']);
            $updRol->execute();

            // 3. Rechazar automáticamente otras solicitudes pendientes
            //    del mismo usuario (ya no tienen sentido)
            $cancelar = $conn->prepare(
                "UPDATE solicitudes_rol
                 SET estado = 'rechazado'
                 WHERE usuario_id = ? AND estado = 'pendiente' AND id != ?"
            );
            $cancelar->bind_param('ii', $solicitud['usuario_id'], $id);
            $cancelar->execute();
        }

        $conn->commit();

        $msg = $decision === 'aprobado'
            ? 'Solicitud aprobada y rol actualizado correctamente.'
            : 'Solicitud rechazada correctamente.';

        echo json_encode(['ok' => true, 'mensaje' => $msg]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // El catch externo lo loguea y devuelve 500
    }
}

// ════════════════════════════════════════════════════════════
//  405 — Método no permitido
// ════════════════════════════════════════════════════════════
function respuesta405(): void
{
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
}