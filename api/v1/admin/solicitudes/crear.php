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

// Verificar sesión del usuario (cualquier rol puede pedir cambio)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión.']);
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

// Solo se puede solicitar Profesor (2) o Administrador (1)
if (!in_array($rol_id, [1, 2])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Rol solicitado inválido.']);
    exit;
}

// No puede solicitar el mismo rol que ya tiene
if ((int)$_SESSION['rol_id'] === $rol_id) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Ya tienes ese rol asignado.']);
    exit;
}

try {
    // Verificar si ya tiene una solicitud pendiente
    $check = $conn->prepare(
        "SELECT id FROM solicitudes_rol WHERE usuario_id = ? AND estado = 'pendiente' LIMIT 1"
    );
    $check->bind_param('i', $user_id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya tienes una solicitud pendiente. Espera a que sea procesada.']);
        exit;
    }

    // Insertar la solicitud con prepared statement
    $stmt = $conn->prepare(
        "INSERT INTO solicitudes_rol (usuario_id, rol_solicitado_id, motivo_solicitud, estado, fecha_solicitud)
         VALUES (?, ?, ?, 'pendiente', NOW())"
    );
    $stmt->bind_param('iis', $user_id, $rol_id, $motivo);

    if ($stmt->execute()) {
        // Notificar a todos los admins
        $admins = $conn->query("SELECT id FROM usuarios WHERE rol_id = 1");
        if ($admins && $admins->num_rows > 0) {
            $notif = $conn->prepare(
                "INSERT INTO notificaciones (usuario_id, asunto, mensaje, tipo, leida, created_at)
                 VALUES (?, 'Nueva solicitud de cambio de rol', ?, 'Sistema', 0, NOW())"
            );
            // Obtener nombre del usuario que solicita
            $uRow = $conn->query("SELECT nombre, apellido FROM usuarios WHERE id = $user_id")->fetch_assoc();
            $nombreSolicitante = ($uRow['nombre'] ?? '') . ' ' . ($uRow['apellido'] ?? '');
            $roles = [1 => 'Administrador', 2 => 'Profesor'];
            $msgNotif = trim($nombreSolicitante) . " solicitó el rol de " . ($roles[$rol_id] ?? 'desconocido') . ".";

            while ($admin = $admins->fetch_assoc()) {
                $notif->bind_param('is', $admin['id'], $msgNotif);
                $notif->execute();
            }
        }

        echo json_encode(['success' => true, 'message' => 'Solicitud enviada correctamente.']);
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    error_log('[crear.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}