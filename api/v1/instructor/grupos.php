<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol_id'] != 2) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado. Solo instructores.']);
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
        'GET'    => listarMisGrupos($conn),
        'POST'   => crearGrupo($conn),
        'DELETE' => eliminarGrupo($conn),
        default  => respuesta405(),
    };
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno.']);
    error_log('[instructor/grupos] ' . $e->getMessage());
}

function listarMisGrupos(mysqli $conn): void
{
    $userId = (int)$_SESSION['user_id'];
    $grupoId = (int)($_GET['id'] ?? 0);

    // Detail view: return group + members
    if ($grupoId) {
        $stmt = $conn->prepare(
            "SELECT g.id, g.nombre, g.descripcion, g.codigo_union, g.clase,
                    g.hora_inicio, g.hora_fin, g.dias, g.cupo, g.created_at,
                    (SELECT COUNT(*) FROM grupo_usuario gu WHERE gu.grupo_id = g.id) AS total_miembros
             FROM grupos g WHERE g.id = ? AND g.creado_por = ? LIMIT 1"
        );
        $stmt->bind_param('ii', $grupoId, $userId);
        $stmt->execute();
        $grupo = $stmt->get_result()->fetch_assoc();
        if (!$grupo) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'mensaje' => 'Grupo no encontrado.']);
            return;
        }

        $mStmt = $conn->prepare(
            "SELECT u.id, u.Nombre, u.Apellido, u.correo
             FROM grupo_usuario gu
             JOIN usuarios u ON gu.usuario_id = u.id
             WHERE gu.grupo_id = ? ORDER BY u.Nombre ASC"
        );
        $mStmt->bind_param('i', $grupoId);
        $mStmt->execute();
        $miembros = $mStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $grupo['miembros'] = $miembros;
        echo json_encode($grupo);
        return;
    }

    // List view
    $stmt = $conn->prepare(
        "SELECT g.id, g.nombre, g.descripcion, g.codigo_union, g.clase,
                g.hora_inicio, g.hora_fin, g.dias, g.cupo, g.created_at,
                (SELECT COUNT(*) FROM grupo_usuario gu WHERE gu.grupo_id = g.id) AS total_miembros
         FROM grupos g
         WHERE g.creado_por = ?
         ORDER BY g.created_at DESC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $grupos = [];
    while ($row = $result->fetch_assoc()) {
        $grupos[] = $row;
    }
    echo json_encode($grupos);
}

function crearGrupo(mysqli $conn): void
{
    $body          = json_decode(file_get_contents('php://input'), true);
    $nombre        = trim($body['nombre'] ?? '');
    $clase         = trim($body['clase'] ?? '');
    $hora_inicio   = trim($body['hora_inicio'] ?? '07:00');
    $hora_fin      = trim($body['hora_fin'] ?? '09:00');
    $dias          = trim($body['dias'] ?? '');
    $cupo          = (int)($body['cupo'] ?? 0);
    $descripcion   = trim($body['descripcion'] ?? '');
    $creado_por    = (int)$_SESSION['user_id'];

    if (!$nombre || !$clase) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'Nombre y clase son obligatorios.']);
        return;
    }

    if ($hora_inicio >= $hora_fin) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'La hora de inicio debe ser anterior a la de fin.']);
        return;
    }

    $chk = $conn->prepare("SELECT id FROM grupos WHERE nombre = ? AND creado_por = ? LIMIT 1");
    $chk->bind_param('si', $nombre, $creado_por);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'Ya tienes un grupo con ese nombre.']);
        return;
    }

    $codigo = generarCodigoUnico($conn);

    $stmt = $conn->prepare(
        "INSERT INTO grupos (nombre, descripcion, codigo_union, clase, hora_inicio, hora_fin, dias, cupo, creado_por)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssssssii', $nombre, $descripcion, $codigo, $clase, $hora_inicio, $hora_fin, $dias, $cupo, $creado_por);

    if (!$stmt->execute()) throw new Exception($stmt->error);

    http_response_code(201);
    echo json_encode([
        'ok' => true,
        'id' => $conn->insert_id,
        'codigo_union' => $codigo,
        'mensaje' => 'Grupo creado correctamente.'
    ]);
}

function generarCodigoUnico(mysqli $conn): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
        $chk = $conn->prepare("SELECT id FROM grupos WHERE codigo_union = ? LIMIT 1");
        $chk->bind_param('s', $code);
        $chk->execute();
    } while ($chk->get_result()->num_rows > 0);
    return $code;
}

function eliminarGrupo(mysqli $conn): void
{
    $body = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($body['id'] ?? 0);
    $userId = (int)$_SESSION['user_id'];

    if (!$id) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'ID de grupo inválido.']);
        return;
    }

    $chk = $conn->prepare("SELECT id FROM grupos WHERE id = ? AND creado_por = ? LIMIT 1");
    $chk->bind_param('ii', $id, $userId);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'mensaje' => 'No puedes eliminar un grupo que no te pertenece.']);
        return;
    }

    $conn->prepare("DELETE FROM horarios WHERE grupo_id = ?")->execute([$id]);
    $conn->prepare("DELETE FROM grupo_usuario WHERE grupo_id = ?")->execute([$id]);
    $stmt = $conn->prepare("DELETE FROM grupos WHERE id = ? AND creado_por = ?");
    $stmt->bind_param('ii', $id, $userId);
    if (!$stmt->execute()) throw new Exception($stmt->error);

    echo json_encode(['ok' => true, 'mensaje' => 'Grupo eliminado correctamente.']);
}

function respuesta405(): void
{
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
}
