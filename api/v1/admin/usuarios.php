<?php
// api/v1/admin/usuarios.php

// 1. Incluir la conexión (ajusta la ruta según tu config.php)
require_once '../../../config/config.php';

// 2. Indicar que la respuesta será JSON
header('Content-Type: application/json');

try {
    // 3. Consulta SQL con JOIN para traer el nombre del rol
    // Usamos u.* para los datos del usuario y r.nombre_rol para el texto del rol
    $query = "SELECT 
                u.id, 
                u.nombre, 
                u.apellido, 
                u.usuario, 
                u.correo, 
                u.rol_id,
                r.nombre_rol as rol
              FROM usuarios u
              INNER JOIN roles r ON u.rol_id = r.id
              ORDER BY u.id DESC";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }

    $usuarios = [];

    // 4. Recorrer los resultados y guardarlos en un array
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }

    // 5. Enviar el JSON al navegador
    echo json_encode($usuarios);

} catch (Exception $e) {
    // En caso de error, enviar mensaje claro
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}