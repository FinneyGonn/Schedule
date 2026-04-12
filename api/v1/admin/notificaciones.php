<?php
// 1. Incluimos tu conexión real (ajusta la ruta si es necesario)
require_once '../../../config/config.php'; 

header('Content-Type: application/json');

// 2. Recibimos los datos del "fetch"
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$tipo    = $data['tipo'] ?? 'Sistema';
$mensaje = $data['mensaje'] ?? '';
$destino = $data['destino'] ?? 'todos';

if (empty($mensaje)) {
    echo json_encode(["success" => false, "message" => "El mensaje está vacío"]);
    exit;
}

try {
    // 3. Lógica para "Todos los usuarios"
    if ($destino === 'todos') {
        // Obtenemos todos los IDs de la tabla usuarios
        $stmtUsers = $pdo->query("SELECT id FROM usuarios");
        $usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        // Preparamos la inserción (más rápido dentro de un bucle)
        $sql = "INSERT INTO notificaciones (usuario_id, mensaje, tipo, leida, created_at) 
                VALUES (?, ?, ?, 0, NOW())";
        $stmtInsert = $pdo->prepare($sql);

        foreach ($usuarios as $user) {
            $stmtInsert->execute([$user['id'], $mensaje, $tipo]);
        }

        echo json_encode(["success" => true, "count" => count($usuarios)]);
    } else {
        // Aquí podrías añadir lógica para un solo usuario si lo necesitas luego
        echo json_encode(["success" => false, "message" => "Destino no soportado aún"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}