<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../../../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión.']);
    exit;
}

$tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($tokenHeader) || !hash_equals($_SESSION['csrf_token'] ?? '', $tokenHeader)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sin conexión a la base de datos.']);
    exit;
}

$data    = json_decode(file_get_contents('php://input'), true);
$rol_id  = (int)($data['rol_id'] ?? 0);
$motivo  = trim($data['motivo'] ?? '');
$user_id = (int)$_SESSION['user_id'];

if (!in_array($rol_id, [1, 2])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Rol solicitado inválido.']);
    exit;
}

if ((int)$_SESSION['rol_id'] === $rol_id) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Ya tienes ese rol asignado.']);
    exit;
}

try {
    // Verificar solicitud pendiente
    $check = $conn->prepare(
        "SELECT id FROM solicitudes_rol WHERE usuario_id = ? AND estado = 'pendiente' LIMIT 1"
    );
    $check->bind_param('i', $user_id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya tienes una solicitud pendiente. Espera a que sea procesada.']);
        exit;
    }

    // Guardar el rol actual del usuario para poder revertirlo si se revoca
    $rol_anterior_id = (int)$_SESSION['rol_id'];

    // Insertar solicitud con rol_anterior_id
    $stmt = $conn->prepare(
        "INSERT INTO solicitudes_rol (usuario_id, rol_solicitado_id, rol_anterior_id, motivo_solicitud, estado)
         VALUES (?, ?, ?, ?, 'pendiente')"
    );
    $stmt->bind_param('iiis', $user_id, $rol_id, $rol_anterior_id, $motivo);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    // Obtener nombre del solicitante
    $uStmt = $conn->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ? LIMIT 1");
    $uStmt->bind_param('i', $user_id);
    $uStmt->execute();
    $uRow = $uStmt->get_result()->fetch_assoc();
    $nombreSolicitante = trim(($uRow['nombre'] ?? '') . ' ' . ($uRow['apellido'] ?? ''));

    $roles    = [1 => 'Administrador', 2 => 'Profesor'];
    $asunto   = 'Nueva solicitud de cambio de rol';
    $msgNotif = "$nombreSolicitante solicitó el rol de " . ($roles[$rol_id] ?? 'desconocido') . ".";

    // Notificar a todos los admins
    $admins = $conn->query("SELECT id FROM usuarios WHERE rol_id = 1");
    if ($admins && $admins->num_rows > 0) {
        $notif = $conn->prepare(
            "INSERT INTO notificaciones (usuario_id, asunto, mensaje, tipo, leida, created_at)
             VALUES (?, ?, ?, 'Sistema', 0, NOW())"
        );
        while ($admin = $admins->fetch_assoc()) {
            $notif->bind_param('iss', $admin['id'], $asunto, $msgNotif);
            $notif->execute();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Solicitud enviada correctamente.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}