<?php
session_start();

// Solo mandamos headers de API si NO es una página HTML
if (!defined('PAGINA_HTML')) {
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'horarios');

try {
    // 1. Mantener tu PDO (por si el login lo usa)
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 2. AGREGAR MySQLi (la variable $conn que piden stats.php y usuarios.php)
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error de conexión: ' . $e->getMessage()]);
    exit();
}

/**
 * Limpia los datos de entrada para evitar XSS básico
 */
function sanitizar($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida si el formato de email es correcto
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * codigo que robe de por ahi, no me juzguen
 */
function respuesta($ok, $mensaje, $datos = []) {
    echo json_encode([
        'ok' => $ok, 
        'mensaje' => $mensaje, 
        'datos' => $datos
    ]);
    exit();
}
?>