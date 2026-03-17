<?php
require_once '../../../config/config.php';
header('content-type: application/json');

$response = [];

try{
    //Esto es para contar usuarios totales
    $res = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
    $response['total_usuarios'] = $res->fetch_assoc()['total'];

    //Lo siguiente lo usaré para poder contar profesores (profe rol =  3)
    $res = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol_id = 3");
    $response['total_profesores'] = $res->fetch_assoc()['total'];

    //ahora, contaremos las solicitudes pendientes
    $res = $conn->query("SELECT COUNT(*) AS total FROM solicitudes_rol WHERE estado = 'pendiente'");
    $response['total_solicitudes'] = $res->fetch_assoc()['total'];

    //agregaremos lo que falta, esto es grupos, horarios, notificaciones, etc.

    echo json_encode($response);
    } catch (exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
}
?>