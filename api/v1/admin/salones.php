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
        'GET'    => listarSalones($conn),
        'POST'   => crearSalon($conn),
        'DELETE' => eliminarSalon($conn),
        default  => respuesta405(),
    };
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor.']);
    error_log('[salones.php] ' . $e->getMessage());
}

function listarSalones(mysqli $conn): void
{
    $result = $conn->query("SELECT id, nombre, capacidad FROM salones ORDER BY nombre ASC");
    if (!$result) throw new Exception($conn->error);
    $salones = [];
    while ($row = $result->fetch_assoc()) {
        $salones[] = $row;
    }
    echo json_encode($salones);
}

function crearSalon(mysqli $conn): void
{
    $body      = json_decode(file_get_contents('php://input'), true);
    $nombre    = trim($body['nombre'] ?? '');
    $capacidad = (int)($body['capacidad'] ?? 0);
    if (!$nombre) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'El nombre del salón es obligatorio.']);
        return;
    }
    $stmt = $conn->prepare("INSERT INTO salones (nombre, capacidad) VALUES (?, ?)");
    $stmt->bind_param('si', $nombre, $capacidad);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $conn->insert_id, 'mensaje' => 'Salón creado correctamente.']);
}

function eliminarSalon(mysqli $conn): void
{
    $body = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($body['id'] ?? 0);
    if (!$id) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'ID de salón inválido.']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM salones WHERE id = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    echo json_encode(['ok' => true, 'mensaje' => 'Salón eliminado correctamente.']);
}

function respuesta405(): void
{
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
}
