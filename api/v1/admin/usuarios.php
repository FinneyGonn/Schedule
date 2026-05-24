<?php
// ============================================================
//  api/v1/admin/usuarios.php
//  Métodos: GET (listar) | POST (crear) | DELETE (desactivar)
//  Seguridad: CSRF, prepared statements, password_hash,
//             session_start via config, sin debug en producción
// ============================================================
header('Content-Type: application/json; charset=utf-8');

require_once '../../../config/config.php';

// ── 1. Sesión y autenticación ────────────────────────────────
// session_start() ya se llama dentro de config.php
if (!isset($_SESSION['user_id']) || $_SESSION['rol_id'] != 1) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado.']);
    exit;
}

// ── 2. Validación CSRF (solo para métodos que modifican datos) ─
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

// ── 4. Router por método HTTP ────────────────────────────────
try {
    match ($method) {
        'GET'    => listarUsuarios($conn),
        'POST'   => crearUsuario($conn),
        'DELETE' => desactivarUsuario($conn),
        default  => respuesta405(),
    };
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor.']);
    // Loguear internamente sin exponer detalles al cliente
    error_log('[usuarios.php] ' . $e->getMessage());
}

// ════════════════════════════════════════════════════════════
//  GET — Listar todos los usuarios
// ════════════════════════════════════════════════════════════
function listarUsuarios(mysqli $conn): void
{
    // Detectar si la columna se llama 'nickname' o 'usuario'
    $col    = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'nickname'");
    $campo  = ($col && $col->num_rows > 0) ? 'u.nickname' : 'u.usuario';

    // Prepared statement: aunque no hay parámetros externos aquí,
    // usamos query normal porque la consulta es estática y segura.
    $sql = "SELECT
                u.id,
                u.nombre,
                u.apellido,
                $campo          AS nickname,
                u.correo,
                u.rol_id,
                r.nombre_rol    AS rol,
                u.activo
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            ORDER BY u.id DESC";

    $result = $conn->query($sql);
    if (!$result) throw new Exception($conn->error);

    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }

    echo json_encode($usuarios);
}

// ════════════════════════════════════════════════════════════
//  POST — Crear nuevo usuario
// ════════════════════════════════════════════════════════════
function crearUsuario(mysqli $conn): void
{
    $body = json_decode(file_get_contents('php://input'), true);

    // Validación de campos obligatorios
    $nombre   = trim($body['nombre']   ?? '');
    $apellido = trim($body['apellido'] ?? '');
    $nickname = trim($body['nickname'] ?? '');
    $correo   = trim($body['correo']   ?? '');
    $rol_id   = (int)($body['rol_id']  ?? 3);
    $password = $body['password']      ?? '';

    if (!$nombre || !$correo || !$password) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'Nombre, correo y contraseña son obligatorios.']);
        return;
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'El correo no tiene un formato válido.']);
        return;
    }

    if (strlen($password) < 8) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'La contraseña debe tener al menos 8 caracteres.']);
        return;
    }

    // Rol válido: 1=Admin, 2=Profesor, 3=Estudiante
    if (!in_array($rol_id, [1, 2, 3])) $rol_id = 3;

    // Verificar correo duplicado — prepared statement
    $chk = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
    $chk->bind_param('s', $correo);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'Ya existe un usuario con ese correo.']);
        return;
    }

    // Hash seguro de la contraseña (bcrypt por defecto)
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Detectar columna nickname/usuario para inserción
    $col   = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'nickname'");
    $campo = ($col && $col->num_rows > 0) ? 'nickname' : 'usuario';

    // INSERT con prepared statement — elimina SQL Injection
    $stmt = $conn->prepare(
        "INSERT INTO usuarios (nombre, apellido, $campo, correo, password, rol_id, activo)
         VALUES (?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param('sssssi', $nombre, $apellido, $nickname, $correo, $hash, $rol_id);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $nuevoId = $conn->insert_id;
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $nuevoId, 'mensaje' => 'Usuario creado correctamente.']);
}

// ════════════════════════════════════════════════════════════
//  DELETE — Desactivar usuario (soft delete)
//  No borramos físicamente para preservar historial
// ════════════════════════════════════════════════════════════
function desactivarUsuario(mysqli $conn): void
{
    $body = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($body['id'] ?? 0);

    if (!$id) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'ID de usuario inválido.']);
        return;
    }

    // No permitir que el admin se desactive a sí mismo
    if ($id === (int)$_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'mensaje' => 'No puedes desactivar tu propia cuenta.']);
        return;
    }

    // Soft delete — prepared statement
    $stmt = $conn->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    echo json_encode(['ok' => true, 'mensaje' => 'Usuario desactivado correctamente.']);
}

// ════════════════════════════════════════════════════════════
//  405 — Método no permitido
// ════════════════════════════════════════════════════════════
function respuesta405(): void
{
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
}