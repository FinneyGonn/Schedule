<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

try {
    // Esta consulta une las tablas para traer el nombre del usuario
    // y el nombre del rol que está solicitando
    $sql = "SELECT s.id, u.nombre, r.nombre_rol, s.fecha, s.estado 
            FROM solicitudes_rol s
            JOIN usuarios u ON s.usuario_id = u.id
            JOIN roles r ON s.rol_solicitado_id = r.id
            ORDER BY s.created_at DESC LIMIT 5";
            
    $res = $conn->query($sql);
    $actividades = [];

    while($row = $res->fetch_assoc()) {
        $actividades[] = $row;
    }

    echo json_encode($actividades);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}