php
<?php
require_once "conexion.php";

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = trim($_POST["codigo_inventario"] ?? "");
    $tipo = trim($_POST["tipo_equipo"] ?? "");
    $marca = trim($_POST["marca"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $serie = trim($_POST["numero_serie"] ?? "");
    $id_area = $_POST["id_area"] ?? "";
    $responsable = trim($_POST["usuario_responsable"] ?? "");
    $sistema = trim($_POST["sistema_operativo"] ?? "");
    $fecha = $_POST["fecha_adquisicion"] ?? "";
    $estado = $_POST["estado_equipo"] ?? "Activo";
    $observaciones = trim($_POST["observaciones"] ?? "");

    if ($codigo === "" || $tipo === "" || $id_area === "") {
        $error = "Completa el código de inventario, el tipo de equipo y el área.";
    } else {
        try {
            $sql = "INSERT INTO equipos
                (codigo_inventario, tipo_equipo, marca, modelo,
                 numero_serie, id_area, usuario_responsable,
                 sistema_operativo, fecha_adquisicion,
                 estado_equipo, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conexion->prepare($sql);

            $stmt->execute([
                $codigo,
                $tipo,
                $marca,
                $modelo,
                $serie,
                (int)$id_area,
                $responsable,
                $sistema,
                $fecha !== "" ? $fecha : null,
                $estado,
                $observaciones
            ]);

            header("Location: equipos.php?guardado=1");
            exit;

        } catch (PDOException $e) {
            error_log($e->getMessage());

            if ($e->getCode() === "23000") {
                $error = "No se pudo registrar el equipo. Comprueba que el código de inventario no esté repetido y que el área exista.";
            } else {
                $error = "No se pudo guardar el equipo. Verifica los datos de la base de datos.";
            }
        }
    }
}

if (isset($_GET["guardado"])) {
    $mensaje = "¡Equipo registrado correctamente!";
}

$areas = $conexion->query(
    "SELECT id_area, nombre_area
     FROM areas
     WHERE estado = 'Activo'
     ORDER BY nombre_area ASC"
)->fetchAll();

$equipos = $conexion->query(
    "SELECT
        e.id_equipo,
        e.codigo_inventario,
        e.tipo_equipo,
        e.marca,
        e.modelo,
        e.numero_serie,
        e.usuario_responsable,
        e.sistema_operativo,
        e.fecha_adquisicion,
        e.estado_equipo,
        a.nombre_area
     FROM equipos e
     INNER JOIN areas a ON e.id_area = a.id_area
     ORDER BY e.id_equipo ASC"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Equipos | SIGESMI</title>

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
.campo select,
.campo textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d9e1ec;
    border-radius: 7px;
    background: white;
    font-family: inherit;
    font-size: 14px;
}

.campo textarea {
    min-height: 90px;
    resize: vertical;
}

.campo input:focus,
.campo select:focus,
.campo textarea:focus {
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
    min-width: 1050px;
}

th, td {
    padding: 13px 12px;
    border-bottom: 1px solid #edf0f5;
    text-align: left;
    font-size: 13px;
}

th {
    color: #748197;
    background: #f8fafd;
}

.etiqueta {
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
    <a href="areas.php">▦ Áreas</a>
    <a href="equipos.php" class="activo">▣ Equipos</a>
    <a href="solicitantes.php">♙ Solicitantes</a>
    <a href="tecnicos.php">⚙️ Técnicos</a>
    <a href="evidencias.php">▧ Evidencias</a>
</aside>

<main>

    <div class="encabezado">
        <div>
            <h1>Gestión de equipos</h1>
            <p class="descripcion">
                Registra y consulta los equipos informáticos de la institución.
            </p>
        </div>

        <a class="boton" href="#nuevo-equipo">＋ Nuevo equipo</a>
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

    <section class="tarjeta" id="nuevo-equipo">
        <h2>Registrar nuevo equipo</h2>

        <form method="POST">
            <div class="formulario">

                <div class="campo">
                    <label>Código de inventario *</label>
                    <input type="text" name="codigo_inventario"
                           placeholder="Ej. EQ-SIG-0051" required>
                </div>

                <div class="campo">
                    <label>Tipo de equipo *</label>
                    <select name="tipo_equipo" required>
                        <option value="">Selecciona un tipo</option>
                        <option>Computadora de escritorio</option>
                        <option>Laptop</option>
                        <option>Impresora</option>
                        <option>Monitor</option>
                        <option>Teclado</option>
                        <option>Mouse</option>
                        <option>Escáner</option>
                        <option>Regulador</option>
                        <option>Servidor</option>
                        <option>Otro</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Marca</label>
                    <input type="text" name="marca"
                           placeholder="Ej. Dell, HP, Lenovo">
                </div>

                <div class="campo">
                    <label>Modelo</label>
                    <input type="text" name="modelo"
                           placeholder="Modelo del equipo">
                </div>

                <div class="campo">
                    <label>Número de serie</label>
                    <input type="text" name="numero_serie"
                           placeholder="Número de serie del fabricante">
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
                    <label>Usuario responsable</label>
                    <input type="text" name="usuario_responsable"
                           placeholder="Nombre de la persona responsable">
                </div>

                <div class="campo">
                    <label>Sistema operativo</label>
                    <select name="sistema_operativo">
                        <option value="">Selecciona una opción</option>
                        <option>Windows 11</option>
                        <option>Windows 10</option>
                        <option>Windows 8.1</option>
                        <option>Linux</option>
                        <option>macOS</option>
                        <option>No aplica</option>
                        <option>Otro</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Fecha de adquisición</label>
                    <input type="date" name="fecha_adquisicion">
                </div>

                <div class="campo">
                    <label>Estado del equipo</label>
                    <select name="estado_equipo">
                        <option value="Activo">Activo</option>
                        <option value="En mantenimiento">En mantenimiento</option>
                        <option value="En reparación">En reparación</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>

                <div class="campo completo">
                    <label>Observaciones</label>
                    <textarea name="observaciones"
                              placeholder="Describe características, fallas o detalles del equipo"></textarea>
                </div>

            </div>

            <div class="acciones">
                <button class="boton" type="submit">
                    Guardar equipo
                </button>

                <a class="boton secundario" href="equipos.php">
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <section class="tarjeta">
        <h2>Equipos registrados (<?= count($equipos) ?>)</h2>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Área</th>
                        <th>Responsable</th>
                        <th>Sistema operativo</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($equipos) > 0): ?>

                    <?php foreach ($equipos as $equipo): ?>
                        <tr>
                            <td><?= (int)$equipo["id_equipo"] ?></td>

                            <td>
                                <?= htmlspecialchars($equipo["codigo_inventario"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($equipo["tipo_equipo"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($equipo["marca"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($equipo["modelo"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($equipo["nombre_area"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($equipo["usuario_responsable"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($equipo["sistema_operativo"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($equipo["estado_equipo"] ?? "") ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="9" class="vacio">
                            Todavía no hay equipos registrados.
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