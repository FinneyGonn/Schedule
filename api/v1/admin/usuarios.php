<?php
header('Content-Type: application/json; charset=utf-8');
// DEBUG TEMPORAL - borrar después
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error) {
        ob_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'debug_error' => $error['message'],
            'debug_file'  => $error['file'],
            'debug_line'  => $error['line']
        ]);
    } else {
        ob_end_flush();
    }
});

require_once '../../../config/config.php';

try {
    // Verificar conexión
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Sin conexión a la base de datos");
    }

    // Detectar si la columna es 'nickname' o 'usuario'
    $columnas = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'nickname'");
    $campoNick = ($columnas && $columnas->num_rows > 0) ? 'u.nickname' : 'u.usuario';

    $query = "SELECT 
                u.id, 
                u.nombre, 
                u.apellido, 
                $campoNick AS nickname,
                u.correo, 
                u.rol_id,
                r.nombre_rol AS rol
              FROM usuarios u
              INNER JOIN roles r ON u.rol_id = r.id
              ORDER BY u.id DESC";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Error en consulta: " . $conn->error);
    }

    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }

    // El JS en admin.php espera un array directo (data.map(...))
    header('Content-Type: application/json');
    echo json_encode($usuarios);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ok"      => false,
        "mensaje" => $e->getMessage()
    ]);
}