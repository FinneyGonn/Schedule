<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuesta(false, 'Método no permitido');
}

// Datos del formulario
$email = sanitizar($_POST['correo'] ?? '');
$password = $_POST['contrasena'] ?? '';

if (empty($email) || empty($password)) {
    respuesta(false, 'Todos los campos son obligatorios');
}

// Validar email
if (!validarEmail($email)) {
    respuesta(false, 'Email inválido');
}

try {
    // 🔥 AQUÍ agregamos rol_id
    $stmt = $pdo->prepare("
        SELECT id, Nombre, correo, contrasena, rol_id 
        FROM usuarios 
        WHERE correo = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificación
    if (!$user || !password_verify($password, $user['contrasena'])) {
        respuesta(false, 'Email o contraseña incorrectos');
    }

    // Sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['Nombre']  = $user['Nombre'];
    $_SESSION['correo']  = $user['correo'];
    $_SESSION['rol_id']  = $user['rol_id']; // hacemos la peticion a la base de datos para obtener el rol_id y lo guardamos en la sesión

    // 🔥 RESPUESTA con rol incluido
    respuesta(true, 'Inicio de sesión exitoso', [
        'user_id' => $user['id'],
        'Nombre'  => $user['Nombre'],
        'correo'  => $user['correo'],
        'rol_id'  => $user['rol_id'] // incluimos el rol_id en la respuesta para que el frontend pueda usarlo
    ]);

} catch(PDOException $e) {
    respuesta(false, 'Error en el servidor: ' . $e->getMessage());
}
?>