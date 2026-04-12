<?php
// api/v1/admin/usuarios.php

// 1. Incluir la conexión 
// (Asegúrate de que la ruta sea correcta. Si usuarios.php está en api/v1/admin/, 
// subir 3 niveles con ../../../ es correcto para llegar a la raíz)
require_once '../../../config/config.php';

// No definimos PAGINA_HTML porque este archivo SI debe ser JSON.
// El header ya lo manda tu config.php modificado.

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
        // Si falla por 'nickname', intentamos con 'usuario' por si acaso
        $query = str_replace('u.nickname', 'u.usuario', $query);
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Error en la consulta: " . $conn->error);
        }
    }

    $usuarios = [];

    // 4. Recorrer los resultados
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }

    // 5. Enviar respuesta usando tu función 'respuesta' de config.php para mantener el formato
    // O directamente el json_encode si tu frontend espera el array limpio:
    echo json_encode($usuarios);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener usuarios: " . $e->getMessage()
    ]);
}