<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

try {
    // Usamos JOIN para traer el nombre del usuario y el nombre del rol solicitado
    $query = "SELECT 
                u.nombre, 
                r.nombre_rol, 
                s.estado, 
                s.fecha_solicitud as created_at 
              FROM solicitudes_rol s
              JOIN usuarios u ON s.usuario_id = u.id
              JOIN roles r ON s.rol_solicitado = r.id
              ORDER BY s.fecha_solicitud DESC 
              LIMIT 10";

    $res = $conn->query($query);
    $actividad = [];

    while ($row = $res->fetch_assoc()) {
        $actividad[] = $row;
    }

    echo json_encode($actividad);

} catch (Exception $e) {
    echo json_encode([]);
}