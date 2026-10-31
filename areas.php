<?php
require_once "conexion.php";

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre_area"] ?? "");
    $ubicacion = trim($_POST["ubicacion"] ?? "");
    $responsable = trim($_POST["responsable"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $estado = $_POST["estado"] ?? "Activo";

    if ($nombre === "") {
        $error = "Escribe el nombre del área.";
    } else {
        try {
            $sql = "INSERT INTO areas
                    (nombre_area, ubicacion, responsable,
                     telefono, correo, estado)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                $nombre, $ubicacion, $responsable,
                $telefono, $correo, $estado
            ]);

            header("Location: areas.php?guardado=1");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "No se pudo guardar el área. Verifica la base de datos.";
        }
    }
}

if (isset($_GET["guardado"])) {
    $mensaje = "¡Área registrada correctamente!";
}

$areas = $conexion->query(
    "SELECT id_area, nombre_area, ubicacion,
            responsable, telefono, correo, estado
     FROM areas
     ORDER BY id_area ASC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Áreas | SIGESMI</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background: #f3f6fb;
    color: #263449;
}

.sidebar {
    position: fixed;
    width: 245px;
    height: 100vh;
    background: #17243b;
    color: white;
    padding: 25px 16px;
}

.logo {
    font-size: 23px;
    font-weight: bold;
    margin: 0 10px 8px;
}

.subtitulo {
    color: #aab7cb;
    font-size: 12px;
    margin: 0 10px 32px;
}

.sidebar a {
    display: block;
    padding: 13px 14px;
    margin-bottom: 7px;
    color: #dbe4f2;
    text-decoration: none;
    border-radius: 8px;
}

.sidebar a:hover,
.sidebar a.activo {
    background: #2e69d1;
    color: white;
}

main {
    margin-left: 245px;
    padding: 32px;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

h1 {
    font-size: 28px;
    margin-bottom: 7px;
}

.descripcion {
    color: #77849a;
    font-size: 14px;
}

.boton {
    border: none;
    background: #2864d7;
    color: white;
    padding: 12px 18px;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    font-weight: bold;
}

.boton:hover {
    background: #194eae;
}

.tarjeta {
    background: white;
    border-radius: 13px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 18px #17243b0c;
}

.tarjeta h2 {
    font-size: 19px;
    margin-bottom: 20px;
}

.formulario {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
}

.campo label {
    display: block;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 8px;
}

.campo input,
.campo select {
    width: 100%;
    padding: 12px;
    border: 1px solid #d9e1ec;
    border-radius: 7px;
    background: white;
    font-size: 14px;
}

.campo input:focus,
.campo select:focus {
    outline: 2px solid #c9dcff;
    border-color: #2864d7;
}

.completo {
    grid-column: 1 / -1;
}

.acciones {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.secundario {
    background: #e9eef6;
    color: #263449;
}

.alerta {
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 18px;
}

.exito {
    background: #e3f7eb;
    color: #17643a;
}

.error {
    background: #fde8e8;
    color: #9d2020;
}

.tabla-contenedor {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th, td {
    padding: 14px 12px;
    border-bottom: 1px solid #edf0f5;
    text-align: left;
    font-size: 13px;
}

th {
    color: #748197;
    background: #f8fafd;
}

.estado {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    background: #e1f6e9;
    color: #187044;
    font-size: 12px;
}

.vacio {
    text-align: center;
    color: #77849a;
    padding: 28px;
}

@media (max-width: 800px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .sidebar a {
        display: inline-block;
    }

    main {
        margin-left: 0;
        padding: 18px;
    }

    .encabezado {
        align-items: flex-start;
        flex-direction: column;
    }

    .formulario {
        grid-template-columns: 1fr;
    }

    .completo {
        grid-column: auto;
    }
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="logo">SIGESMI</div>
    <div class="subtitulo">Gestión de servicios informáticos</div>

    <a href="index.php">▦ Panel principal</a>
    <a href="nuevo_servicio.php">＋ Nuevo servicio</a>
    <a href="servicios.php">▤ Servicios</a>
    <a href="areas.php" class="activo">▦ Áreas</a>
    <a href="equipos.php">▣ Equipos</a>
    <a href="solicitantes.php">♙ Solicitantes</a>
    <a href="tecnicos.php">⚙️ Técnicos</a>
    <a href="evidencias.php">▧ Evidencias</a>
</aside>

<main>
    <div class="encabezado">
        <div>
            <h1>Gestión de áreas</h1>
            <p class="descripcion">
                Registra y consulta las áreas de la institución.
            </p>
        </div>

        <a class="boton" href="#nueva-area">＋ Nueva área</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alerta exito">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alerta error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <section class="tarjeta" id="nueva-area">
        <h2>Registrar nueva área</h2>

        <form method="POST">
            <div class="formulario">
                <div class="campo">
                    <label>Nombre del área *</label>
                    <input type="text" name="nombre_area"
                           placeholder="Ej. Informática" required>
                </div>

                <div class="campo">
                    <label>Ubicación</label>
                    <input type="text" name="ubicacion"
                           placeholder="Ej. Oficina principal">
                </div>

                <div class="campo">
                    <label>Responsable</label>
                    <input type="text" name="responsable"
                           placeholder="Nombre del responsable">
                </div>

                <div class="campo">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono"
                           placeholder="Número de contacto">
                </div>

                <div class="campo">
                    <label>Correo electrónico</label>
                    <input type="email" name="correo"
                           placeholder="area@institucion.gob.mx">
                </div>

                <div class="campo">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="acciones">
                <button class="boton" type="submit">
                    Guardar área
                </button>
                <a class="boton secundario" href="areas.php">
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <section class="tarjeta">
        <h2>Áreas registradas (<?= count($areas) ?>)</h2>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nombre del área</th>
                        <th>Ubicación</th>
                        <th>Responsable</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($areas) > 0): ?>
                    <?php foreach ($areas as $area): ?>
                        <tr>
                            <td><?= (int)$area["id_area"] ?></td>
                            <td>
                                <?= htmlspecialchars($area["nombre_area"] ?? "") ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($area["ubicacion"] ?? "") ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($area["responsable"] ?? "") ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($area["telefono"] ?? "") ?>
                            </td>
                            <td>
                                <span class="estado">
                                    <?= htmlspecialchars($area["estado"] ?? "") ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="vacio">
                            Todavía no hay áreas registradas.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

</body>
</html>