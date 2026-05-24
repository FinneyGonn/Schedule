<?php
// ============================================================
//  api/v1/admin/grupos.php
//  Métodos: GET (listar) | POST (crear) | DELETE (eliminar)
//  Seguridad: CSRF, prepared statements, autenticación
// ============================================================
header('Content-Type: application/json; charset=utf-8');

require_once '../../../config/config.php';

// ── 1. Sesión y autenticación ────────────────────────────────
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
        'GET'    => listarGrupos($conn),
        'POST'   => crearGrupo($conn),
        'DELETE' => eliminarGrupo($conn),
        default  => respuesta405(),
    };
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor.']);
    error_log('[grupos.php] ' . $e->getMessage());
}

// ════════════════════════════════════════════════════════════
//  GET — Listar grupos con nombre del creador y total miembros
// ════════════════════════════════════════════════════════════
function listarGrupos(mysqli $conn): void
{
    // La tabla grupos tiene: id, nombre, descripcion, creado_por, created_at
    // La tabla de miembros se llama grupo_usuario (no grupo_usuarios)
    $sql = "SELECT
                g.id,
                g.nombre,
                g.descripcion,
                g.creado_por,
                g.created_at,
                CONCAT(u.nombre, ' ', u.apellido) AS prof_nombre,
                (
                    SELECT COUNT(*) FROM grupo_usuario gu WHERE gu.grupo_id = g.id
                ) AS total_miembros
            FROM grupos g
            LEFT JOIN usuarios u ON g.creado_por = u.id
            ORDER BY g.id DESC";

    $result = $conn->query($sql);
    if (!$result) throw new Exception($conn->error);

    $grupos = [];
    while ($row = $result->fetch_assoc()) {
        $grupos[] = $row;
    }

    echo json_encode($grupos);
}

// ════════════════════════════════════════════════════════════
//  POST — Crear nuevo grupo
// ════════════════════════════════════════════════════════════
function crearGrupo(mysqli $conn): void
{
    $body        = json_decode(file_get_contents('php://input'), true);
    $nombre      = trim($body['nombre']      ?? '');
    $descripcion = trim($body['descripcion'] ?? '');
    $creado_por  = (int)$_SESSION['user_id']; // siempre el admin autenticado

    if (!$nombre) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'El nombre del grupo es obligatorio.']);
        return;
    }

    // Verificar nombre duplicado — prepared statement
    $chk = $conn->prepare("SELECT id FROM grupos WHERE nombre = ? LIMIT 1");
    $chk->bind_param('s', $nombre);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'Ya existe un grupo con ese nombre.']);
        return;
    }

    // INSERT usando las columnas reales de la tabla: nombre, descripcion, creado_por
    $stmt = $conn->prepare(
        "INSERT INTO grupos (nombre, descripcion, creado_por) VALUES (?, ?, ?)"
    );
    $stmt->bind_param('ssi', $nombre, $descripcion, $creado_por);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $nuevoId = $conn->insert_id;
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $nuevoId, 'mensaje' => 'Grupo creado correctamente.']);
}

// ════════════════════════════════════════════════════════════
//  DELETE — Eliminar grupo y sus bloques de horario
// ════════════════════════════════════════════════════════════
function eliminarGrupo(mysqli $conn): void
{
    $body = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($body['id'] ?? 0);

    if (!$id) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'ID de grupo inválido.']);
        return;
    }

    // Borrar bloques de horario del grupo primero (integridad referencial)
    $delH = $conn->prepare("DELETE FROM horarios WHERE grupo_id = ?");
    $delH->bind_param('i', $id);
    $delH->execute();

    // Borrar miembros del grupo (tabla real: grupo_usuario)
    $delM = $conn->prepare("DELETE FROM grupo_usuario WHERE grupo_id = ?");
    $delM->bind_param('i', $id);
    $delM->execute();

    // Borrar el grupo
    $stmt = $conn->prepare("DELETE FROM grupos WHERE id = ?");
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    echo json_encode(['ok' => true, 'mensaje' => 'Grupo eliminado correctamente.']);
}

// ════════════════════════════════════════════════════════════
//  405 — Método no permitido
// ════════════════════════════════════════════════════════════
function respuesta405(): void
{
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
}