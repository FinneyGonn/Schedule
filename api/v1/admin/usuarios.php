<?php
// api/v1/admin/usuarios.php

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

    echo json_encode([
        "ok"     => true,
        "datos"  => $usuarios
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ok"      => false,
        "mensaje" => $e->getMessage()
    ]);
}