<?php
session_start();
require_once 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    $sql = "SELECT id_usuario, nombre_completo, nombre_usuario,
                   password_hash, id_rol, activo
            FROM usuarios
            WHERE nombre_usuario = ?
            LIMIT 1";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([$usuario]);
    $datos = $consulta->fetch();

    if ($datos &&
        (int)$datos['activo'] === 1 &&
        password_verify($contrasena, $datos['password_hash'])) {

        session_regenerate_id(true);

        $_SESSION['id_usuario'] = $datos['id_usuario'];
        $_SESSION['nombre_completo'] = $datos['nombre_completo'];
        $_SESSION['nombre_usuario'] = $datos['nombre_usuario'];
        $_SESSION['id_rol'] = $datos['id_rol'];

        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos, o cuenta inactiva.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | SIGESMI</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #10243e, #176b87);
            padding: 20px;
        }

        .login {
            background: white;
            width: 100%;
            max-width: 400px;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(0,0,0,.25);
        }

        h1 {
            text-align: center;
            color: #10243e;
            margin-bottom: 8px;
        }

        .subtitulo {
            text-align: center;
            color: #64748b;
            margin-bottom: 28px;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: bold;
            color: #334155;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 16px;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #176b87;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #10243e; }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .pie {
            text-align: center;
            color: #64748b;
            font-size: 12px;
            margin-top: 22px;
        }
    </style>
</head>
<body>
    <main class="login">
        <h1>SIGESMI</h1>
        <p class="subtitulo">Sistema de Gestión de Servicios Informáticos</p>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario"
                   autocomplete="username" required>

            <label for="contrasena">Contraseña</label>
            <input type="password" id="contrasena" name="contrasena"
                   autocomplete="current-password" required>

            <button type="submit">Iniciar sesión</button>
        </form>

        <div class="pie">Acceso al sistema · SIGESMI</div>
    </main>
</body>
</html>