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
    $stmt = $pdo->prepare("
        SELECT id, Nombre, Apellido, correo, contrasena, rol_id 
        FROM usuarios 
        WHERE correo = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['contrasena'])) {
        respuesta(false, 'Email o contraseña incorrectos');
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['Nombre']  = $user['Nombre'];
    $_SESSION['apellido'] = $user['Apellido'] ?? '';
    $_SESSION['correo']  = $user['correo'];
    $_SESSION['rol_id']  = $user['rol_id'];

    respuesta(true, 'Inicio de sesión exitoso', [
        'user_id' => $user['id'],
        'Nombre'  => $user['Nombre'],
        'Apellido' => $user['Apellido'] ?? '',
        'correo'  => $user['correo'],
        'rol_id'  => $user['rol_id']
    ]);

} catch(PDOException $e) {
    respuesta(false, 'Error en el servidor: ' . $e->getMessage());
}
?>