<?php
require_once "conexion.php";

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido_paterno = trim($_POST["apellido_paterno"] ?? "");
    $apellido_materno = trim($_POST["apellido_materno"] ?? "");
    $cargo = trim($_POST["cargo"] ?? "");
    $id_area = $_POST["id_area"] ?? "";
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");

    if ($nombre === "" || $apellido_paterno === "" || $id_area === "") {
        $error = "Completa el nombre, el apellido paterno y el área.";
    } else {
        try {
            $sql = "INSERT INTO solicitantes
                    (nombre, apellido_paterno, apellido_materno,
                     cargo, id_area, telefono, correo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                $nombre,
                $apellido_paterno,
                $apellido_materno,
                $cargo,
                (int)$id_area,
                $telefono,
                $correo
            ]);

            header("Location: solicitantes.php?guardado=1");
            exit;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "No se pudo guardar el solicitante. Verifica los datos e inténtalo de nuevo.";
        }
    }
}

if (isset($_GET["guardado"])) {
    $mensaje = "¡Solicitante registrado correctamente!";
}

$areas = $conexion->query(
    "SELECT id_area, nombre_area
     FROM areas
     WHERE estado = 'Activo'
     ORDER BY nombre_area ASC"
)->fetchAll();

$solicitantes = $conexion->query(
    "SELECT
        s.id_solicitante,
        s.nombre,
        s.apellido_paterno,
        s.apellido_materno,
        s.cargo,
        s.telefono,
        s.correo,
        a.nombre_area
     FROM solicitantes s
     INNER JOIN areas a ON s.id_area = a.id_area
     ORDER BY s.id_solicitante ASC"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Solicitantes | SIGESMI</title>

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
    overflow-y: auto;
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
    display: inline-block;
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
    min-width: 850px;
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
    <a href="areas.php">▦ Áreas</a>
    <a href="equipos.php">▣ Equipos</a>
    <a href="solicitantes.php" class="activo">♙ Solicitantes</a>
    <a href="tecnicos.php">⚙️ Técnicos</a>
    <a href="evidencias.php">▧ Evidencias</a>
</aside>

<main>

    <div class="encabezado">
        <div>
            <h1>Gestión de solicitantes</h1>
            <p class="descripcion">
                Registra y consulta a las personas que solicitan servicios informáticos.
            </p>
        </div>

        <a class="boton" href="#nuevo-solicitante">＋ Nuevo solicitante</a>
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

    <section class="tarjeta" id="nuevo-solicitante">
        <h2>Registrar nuevo solicitante</h2>

        <form method="POST">
            <div class="formulario">

                <div class="campo">
                    <label>Nombre *</label>
                    <input type="text" name="nombre"
                           placeholder="Nombre de la persona" required>
                </div>

                <div class="campo">
                    <label>Apellido paterno *</label>
                    <input type="text" name="apellido_paterno"
                           placeholder="Apellido paterno" required>
                </div>

                <div class="campo">
                    <label>Apellido materno</label>
                    <input type="text" name="apellido_materno"
                           placeholder="Apellido materno">
                </div>

                <div class="campo">
                    <label>Cargo</label>
                    <input type="text" name="cargo"
                           placeholder="Ej. Auxiliar administrativo">
                </div>

                <div class="campo">
                    <label>Área *</label>
                    <select name="id_area" required>
                        <option value="">Selecciona un área</option>

                        <?php foreach ($areas as $area): ?>
                            <option value="<?= (int)$area["id_area"] ?>">
                                <?= htmlspecialchars($area["nombre_area"]) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="campo">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono"
                           placeholder="Número de contacto">
                </div>

                <div class="campo">
                    <label>Correo electrónico</label>
                    <input type="email" name="correo"
                           placeholder="persona@institucion.gob.mx">
                </div>

            </div>

            <div class="acciones">
                <button class="boton" type="submit">
                    Guardar solicitante
                </button>

                <a class="boton secundario" href="solicitantes.php">
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <section class="tarjeta">
        <h2>Solicitantes registrados (<?= count($solicitantes) ?>)</h2>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nombre completo</th>
                        <th>Cargo</th>
                        <th>Área</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($solicitantes) > 0): ?>

                    <?php foreach ($solicitantes as $solicitante): ?>
                        <tr>
                            <td>
                                <?= (int)$solicitante["id_solicitante"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    trim(
                                        $solicitante["nombre"] . " " .
                                        $solicitante["apellido_paterno"] . " " .
                                        ($solicitante["apellido_materno"] ?? "")
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($solicitante["cargo"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($solicitante["nombre_area"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($solicitante["telefono"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($solicitante["correo"] ?? "") ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="6" class="vacio">
                            Todavía no hay solicitantes registrados.
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