<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuesta(false, 'Método no permitido');
}

// 1. Asegúrate de que el formulario HTML envíe 'correo' y 'contrasena'
$email = sanitizar($_POST['correo'] ?? '');
$password = $_POST['contrasena'] ?? ''; 

if (empty($email) || empty($password)) {
    respuesta(false, 'Todos los campos son obligatorios');
}

// Validar formato de email
if (!validarEmail($email)) {
    respuesta(false, 'Email inválido');
}

try {
    // 2. Consulta usando los nombres exactos de tu tabla (Nombre, correo, contrasena)
    $stmt = $pdo->prepare("
        SELECT id, Nombre, correo, contrasena 
        FROM usuarios 
        WHERE correo = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Usar FETCH_ASSOC es más limpio

    // 3. Verificación de usuario y password
    if (!$user || !password_verify($password, $user['contrasena'])) {
        respuesta(false, 'Email o contraseña incorrectos');
    }

    // 4. Iniciar sesión usando los nombres de la BD para no confundirte
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['Nombre']  = $user['Nombre'];
    $_SESSION['correo']  = $user['correo'];

    respuesta(true, 'Inicio de sesión exitoso', [
        'user_id' => $user['id'],
        'Nombre'  => $user['Nombre'],
        'correo'  => $user['correo']
    ]);

} catch(PDOException $e) {
    respuesta(false, 'Error en el servidor: ' . $e->getMessage());
}
?>