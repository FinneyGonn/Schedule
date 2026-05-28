<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol_id'] != 1) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
    $tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($tokenHeader) || !hash_equals($_SESSION['csrf_token'] ?? '', $tokenHeader)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF inválido.']);
        exit;
    }
}

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Sin conexión a la base de datos.']);
    exit;
}

try {
    match ($method) {
        'GET'    => listarBloques($conn),
        'POST'   => crearBloque($conn),
        'PUT'    => actualizarBloque($conn),
        'DELETE' => eliminarBloque($conn),
        default  => respuesta405(),
    };
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor.']);
    error_log('[horarios.php] ' . $e->getMessage());
}

function listarBloques(mysqli $conn): void
{
    $grupo_id = (int)($_GET['grupo_id'] ?? 0);
    if (!$grupo_id) {
        echo json_encode([]);
        return;
    }
    $stmt = $conn->prepare(
        "SELECT id, grupo_id, nombre, dia, hora_inicio, hora_fin, salon, profesor, color
         FROM horarios WHERE grupo_id = ?
         ORDER BY FIELD(dia,'lunes','martes','miercoles','jueves','viernes','sabado'), hora_inicio"
    );
    $stmt->bind_param('i', $grupo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bloques = [];
    while ($row = $result->fetch_assoc()) {
        $bloques[] = $row;
    }
    echo json_encode($bloques);
}

function crearBloque(mysqli $conn): void
{
    $body        = json_decode(file_get_contents('php://input'), true);
    $grupo_id    = (int)($body['grupo_id'] ?? 0);
    $nombre      = trim($body['nombre'] ?? '');
    $dia         = trim($body['dia'] ?? '');
    $hora_inicio = trim($body['hora_inicio'] ?? '');
    $hora_fin    = trim($body['hora_fin'] ?? '');
    $salon       = trim($body['salon'] ?? '');
    $profesor    = trim($body['profesor'] ?? '');
    $color       = trim($body['color'] ?? 'c0');

    if (!$grupo_id || !$nombre || !$dia || !$hora_inicio || !$hora_fin) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'Faltan campos obligatorios.']);
        return;
    }

    $stmt = $conn->prepare(
        "INSERT INTO horarios (grupo_id, nombre, dia, hora_inicio, hora_fin, salon, profesor, color)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssssss', $grupo_id, $nombre, $dia, $hora_inicio, $hora_fin, $salon, $profesor, $color);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $conn->insert_id, 'mensaje' => 'Bloque creado correctamente.']);
}

function actualizarBloque(mysqli $conn): void
{
    $body        = json_decode(file_get_contents('php://input'), true);
    $id          = (int)($body['id'] ?? 0);
    $nombre      = trim($body['nombre'] ?? '');
    $dia         = trim($body['dia'] ?? '');
    $hora_inicio = trim($body['hora_inicio'] ?? '');
    $hora_fin    = trim($body['hora_fin'] ?? '');
    $salon       = trim($body['salon'] ?? '');
    $profesor    = trim($body['profesor'] ?? '');
    $color       = trim($body['color'] ?? 'c0');

    if (!$id) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'ID de bloque inválido.']);
        return;
    }

    $stmt = $conn->prepare(
        "UPDATE horarios SET nombre=?, dia=?, hora_inicio=?, hora_fin=?, salon=?, profesor=?, color=? WHERE id=?"
    );
    $stmt->bind_param('sssssssi', $nombre, $dia, $hora_inicio, $hora_fin, $salon, $profesor, $color, $id);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    echo json_encode(['ok' => true, 'mensaje' => 'Bloque actualizado correctamente.']);
}

function eliminarBloque(mysqli $conn): void
{
    $body = json_decode(file_get_contents('php://input'), true);

    if (!empty($body['clear_all'])) {
        $grupo_id = (int)($body['grupo_id'] ?? 0);
        if (!$grupo_id) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'mensaje' => 'ID de grupo inválido.']);
            return;
        }
        $stmt = $conn->prepare("DELETE FROM horarios WHERE grupo_id = ?");
        $stmt->bind_param('i', $grupo_id);
        $stmt->execute();
        echo json_encode(['ok' => true, 'mensaje' => 'Horario limpiado correctamente.']);
        return;
    }

    $id = (int)($body['id'] ?? 0);
    if (!$id) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'ID de bloque inválido.']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM horarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    echo json_encode(['ok' => true, 'mensaje' => 'Bloque eliminado correctamente.']);
}

function respuesta405(): void
{
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
}
