<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CONTROL DE HEADERS: Solo mandamos JSON si NO es una página visual (admin.php)
if (!defined('PAGINA_HTML')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
} else {
    // Si es admin.php, forzamos que el navegador lo lea como HTML
    header('Content-Type: text/html; charset=utf-8');
}

// Configuración de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'horarios');

try {
    // PDO para lógica moderna
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // MySQLi ($conn) para compatibilidad con stats.php y usuarios.php
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }

} catch(Exception $e) {
    // Solo mandamos respuesta JSON si falló una API
    if (!defined('PAGINA_HTML')) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'Error de conexión: ' . $e->getMessage()]);
        exit();
    } else {
        die("Error de conexión a la base de datos: " . $e->getMessage());
    }
}

/**
 * Funciones de utilidad
 */
function sanitizar($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function respuesta($ok, $mensaje, $datos = []) {
    // Esta función solo debe usarse en archivos de la carpeta /api/
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => $ok, 
        'mensaje' => $mensaje, 
        'datos' => $datos
    ]);
    exit();
}
?>