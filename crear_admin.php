<?php
require_once 'conexion.php';

$nombre_completo = 'Administrador SIGESMI';
$nombre_usuario = 'admin';
$correo = 'admin@sigesmi.local';
$contrasena = 'Admin2026*';

$password_hash = password_hash($contrasena, PASSWORD_DEFAULT);

try {
    $buscar = $conexion->prepare(
        "SELECT id_usuario FROM usuarios WHERE nombre_usuario = ?"
    );
    $buscar->execute([$nombre_usuario]);

    if ($buscar->fetch()) {
        exit("El usuario admin ya existe. No se creó otro.");
    }

    $sql = "INSERT INTO usuarios
            (nombre_completo, nombre_usuario, correo,
             password_hash, id_rol, activo, fecha_creacion)
            VALUES (?, ?, ?, ?, 1, 1, NOW())";

    $insertar = $conexion->prepare($sql);
    $insertar->execute([
        $nombre_completo,
        $nombre_usuario,
        $correo,
        $password_hash
    ]);

    echo "Usuario administrador creado correctamente.<br>";
    echo "Usuario: admin<br>";
    echo "Contraseña: Admin2026*<br>";
    echo "Por seguridad, elimina este archivo después de ejecutarlo.";

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo "No se pudo crear el usuario. Revisa la tabla usuarios y el rol.";
}
?>