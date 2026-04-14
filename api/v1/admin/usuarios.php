<?php
// api/v1/admin/usuarios.php

header('Content-Type: application/json');

require_once '../../../config/config.php';

// Verificar que $conn existe antes de usarla
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error de conexión a la base de datos"
    ]);
    exit;
}

try {
    $query = "SELECT 
                u.id, 
                u.nombre, 
                u.apellido, 
                u.nickname, 
                u.correo, 
                u.rol_id,
                r.nombre_rol as rol
              FROM usuarios u
              INNER JOIN roles r ON u.rol_id = r.id
              ORDER BY u.id DESC";

    $result = $conn->query($query);

    if (!$result) {
        // Si falla por 'nickname', intenta con 'usuario'
        $query = str_replace('u.nickname', 'u.usuario', $query);
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Error en la consulta: " . $conn->error);
        }
    }

    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }

    echo json_encode(["ok" => true, "data" => $usuarios]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener usuarios: " . $e->getMessage()
    ]);
}