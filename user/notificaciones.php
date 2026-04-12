<?php
// Reportar errores para ver qué pasa exactamente si falla
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Intentar encontrar la conexión de forma segura
$posibles_rutas = [
    '../config/config.php',
    '../../config/config.php',
    'config/config.php'
];

$conectado = false;
foreach ($posibles_rutas as $ruta) {
    if (file_exists($ruta)) {
        require_once $ruta;
        $conectado = true;
        break;
    }
}

if (!$conectado) {
    die(json_encode(["error" => "No se encontro el archivo de conexion (config.php)"]));
}

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

// Si no hay sesión, usamos un ID temporal para que no explote mientras pruebas
$usuario_id = $_SESSION['user_id'] ?? 1; 

try {
    // Usamos $pdo o $conn según lo que tengas en tu config.php
    $base = isset($pdo) ? $pdo : $conn;
    
    if ($base instanceof PDO) {
        $stmt = $base->prepare("SELECT id, mensaje, tipo, leida, created_at FROM notificaciones WHERE usuario_id = ? ORDER BY created_at DESC");
        $stmt->execute([$usuario_id]);
        $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Por si usas mysqli
        $stmt = $base->prepare("SELECT id, mensaje, tipo, leida, created_at FROM notificaciones WHERE usuario_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $notificaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    echo json_encode($notificaciones);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}