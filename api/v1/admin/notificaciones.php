<?php
session_start(); // AGREGADO: Sin esto, $_SESSION no funciona
require_once '../../../config/config.php';

header('Content-Type: application/json');

// ── GET: traer notificaciones del usuario en sesión ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([]);
        exit;
    }

    $userId = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare(
            "SELECT id, asunto, mensaje, tipo, leida, created_at 
             FROM notificaciones 
             WHERE usuario_id = ? 
             ORDER BY created_at DESC 
             LIMIT 50"
        );
        $stmt->execute([$userId]);
        $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($notificaciones ? $notificaciones : []);
    } catch (Exception $e) {
        echo json_encode([]);
    }
    exit;
} // <--- Aquí estaba el problema, cerraba mal la lógica

// ── POST: enviar notificación (solo admin) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['rol_id'] != 1) {
        echo json_encode(["success" => false, "message" => "No autorizado"]);
        exit;
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $asunto  = trim($data['asunto']  ?? '');
    $tipo    = $data['tipo']    ?? 'Sistema';
    $mensaje = trim($data['mensaje'] ?? '');
    $destino = $data['destino'] ?? 'todos';

    if (empty($mensaje) || empty($asunto)) {
        echo json_encode(["success" => false, "message" => "Asunto y mensaje son requeridos"]);
        exit;
    }

    try {
        if ($destino === 'todos') {
            $stmtUsers = $pdo->query("SELECT id FROM usuarios");
            $usuarios  = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

            $sql = "INSERT INTO notificaciones (usuario_id, asunto, mensaje, tipo, leida, created_at) 
                    VALUES (?, ?, ?, ?, 0, NOW())";
            $stmtInsert = $pdo->prepare($sql);

            foreach ($usuarios as $user) {
                $stmtInsert->execute([$user['id'], $asunto, $mensaje, $tipo]);
            }

            echo json_encode(["success" => true, "count" => count($usuarios)]);
        } else {
            echo json_encode(["success" => false, "message" => "Destino no soportado"]);
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}