<?php
require_once '../config/config.php'; // Ajusta la ruta según tu carpeta

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuesta(false, 'Método no permitido');
}

// 1. Recoger datos (nombres deben coincidir con el append del JS)
$nombre     = sanitizar($_POST['nombre'] ?? '');
$apellido   = sanitizar($_POST['apellido'] ?? '');
$nickname   = sanitizar($_POST['nickname'] ?? '');
$correo     = sanitizar($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

// 2. Validaciones básicas
if (empty($nombre) || empty($apellido) || empty($nickname) || empty($correo) || empty($contrasena)) {
    respuesta(false, 'Todos los campos son obligatorios');
}

try {
    // 3. Verificar si el correo o el nickname ya existen (son campos UNI)
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? OR nickname = ?");
    $stmt->execute([$correo, $nickname]);
    
    if ($stmt->fetch()) {
        respuesta(false, 'El correo o el nombre de usuario ya están en uso');
    }

    // 4. Encriptar contraseña
    $passHash = password_hash($contrasena, PASSWORD_DEFAULT);

    // 5. Insertar (respetando mayúsculas/minúsculas de tu base de datos)
    // Nota: rol_id lo ponemos como 2 (Usuario) por defecto
    $sql = "INSERT INTO usuarios (Nombre, Apellido, nickname, correo, contrasena, rol_id) 
            VALUES (?, ?, ?, ?, ?, 2)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $apellido, $nickname, $correo, $passHash]);

    respuesta(true, 'Cuenta creada exitosamente');

} catch (PDOException $e) {
    respuesta(false, 'Error en base de datos: ' . $e->getMessage());
}
?>