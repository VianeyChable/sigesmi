<?php
require_once "conexion.php";

$mensaje = "";
$error = "";

function generarFolio($conexion) {
    $anio = date("Y");
    $prefijo = "SIG-" . $anio . "-";

    $sql = "SELECT MAX(CAST(SUBSTRING(folio, 10) AS UNSIGNED))
            FROM servicios
            WHERE folio LIKE ?";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([$prefijo . "%"]);

    $numero = (int)$consulta->fetchColumn() + 1;

    return $prefijo . str_pad($numero, 4, "0", STR_PAD_LEFT);
}

function obtenerOpciones($conexion, $sql) {
    return $conexion->query($sql)->fetchAll();
}

try {
    $areas = obtenerOpciones(
        $conexion,
        "SELECT id_area, nombre_area
         FROM areas ORDER BY nombre_area"
    );

    $solicitantes = obtenerOpciones(
        $conexion,
        "SELECT id_solicitante, nombre, apellido_paterno,
                apellido_materno, id_area
         FROM solicitantes
         ORDER BY nombre, apellido_paterno"
    );

    $equipos = obtenerOpciones(
        $conexion,
        "SELECT id_equipo, codigo_inventario, tipo_equipo,
                marca, modelo
         FROM equipos ORDER BY codigo_inventario"
    );

    $tecnicos = obtenerOpciones(
        $conexion,
        "SELECT id_tecnico, nombre, apellido_paterno,
                apellido_materno
         FROM tecnicos ORDER BY nombre"
    );

    $tipos = obtenerOpciones(
        $conexion,
        "SELECT id_tipo_servicio, nombre_tipo
         FROM tipos_servicio ORDER BY nombre_tipo"
    );

    $estados = obtenerOpciones(
        $conexion,
        "SELECT id_estado, nombre_estado
         FROM estados_servicio ORDER BY id_estado"
    );

    $prioridades = obtenerOpciones(
        $conexion,
        "SELECT id_prioridad, nombre_prioridad
         FROM prioridades ORDER BY nivel"
    );

    $folioVista = generarFolio($conexion);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $fecha = str_replace(
            "T", " ",
            $_POST["fecha_solicitud"] ?? ""
        );

        if (strlen($fecha) === 16) {
            $fecha .= ":00";
        }

        $area = (int)($_POST["id_area"] ?? 0);
        $solicitante = (int)($_POST["id_solicitante"] ?? 0);
        $equipo = ($_POST["id_equipo"] ?? "") !== ""
            ? (int)$_POST["id_equipo"] : null;
        $tecnico = ($_POST["id_tecnico"] ?? "") !== ""
            ? (int)$_POST["id_tecnico"] : null;
        $tipo = (int)($_POST["id_tipo_servicio"] ?? 0);
        $estado = (int)($_POST["id_estado"] ?? 0);
        $prioridad = (int)($_POST["id_prioridad"] ?? 0);

        $problema = trim($_POST["descripcion_problema"] ?? "");
        $trabajo = trim($_POST["trabajo_realizado"] ?? "");
        $observaciones = trim($_POST["observaciones"] ?? "");

        if (
            !$area || !$solicitante || !$tipo ||
            !$estado || !$prioridad || !$problema || !$fecha
        ) {
            throw new Exception(
                "Completa todos los campos obligatorios."
            );
        }

        $folio = generarFolio($conexion);

        $sql = "INSERT INTO servicios (
                    folio, fecha_solicitud, id_area,
                    id_solicitante, id_equipo, id_tecnico,
                    id_tipo_servicio, id_estado, id_prioridad,
                    descripcion_problema, trabajo_realizado,
                    observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $consulta = $conexion->prepare($sql);

        $consulta->execute([
            $folio,
            $fecha,
            $area,
            $solicitante,
            $equipo,
            $tecnico,
            $tipo,
            $estado,
            $prioridad,
            $problema,
            $trabajo !== "" ? $trabajo : null,
            $observaciones !== "" ? $observaciones : null
        ]);

        header("Location: index.php?guardado=1");
        exit;
    }
} catch (Throwable $e) {
    $error = "No se pudo guardar el servicio. " .
             $e->getMessage();
}

function h($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

function seleccionado($valor, $actual) {
    return (string)$valor === (string)$actual
        ? "selected" : "";
}

$actual = $_POST ?? [];
$folioMostrar = $folioVista ?? "";
$fechaMostrar = $actual["fecha_solicitud"]
    ?? date("Y-m-d\TH:i");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nuevo servicio | SIGESMI</title>
<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    background: #f1f5f9;
    color: #1e293b;
    font-family: Arial, sans-serif;
}

header {
    background: #14233d;
    color: white;
    padding: 20px;
}

header h1 { margin: 0 0 6px; font-size: 24px; }
header p { margin: 0; color: #cbd5e1; }

main {
    max-width: 1050px;
    margin: 28px auto;
    padding: 0 16px;
}

.panel {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px #0f172a0d;
}

h2 { margin-top: 0; }

.grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.campo {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.completo { grid-column: 1 / -1; }

label { font-weight: 600; font-size: 14px; }

input, select, textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    background: white;
    font: inherit;
}

textarea { min-height: 95px; resize: vertical; }

input:focus, select:focus, textarea:focus {
    outline: 2px solid #93c5fd;
    border-color: #2563eb;
}

.acciones {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    flex-wrap: wrap;
}

.boton {
    display: inline-block;
    padding: 12px 18px;
    border: 0;
    border-radius: 7px;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
}

.guardar { background: #2563eb; color: white; }
.cancelar { background: #e2e8f0; color: #1e293b; }

.folio {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 12px;
    border-radius: 7px;
    font-weight: bold;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    margin-bottom: 16px;
    border-radius: 7px;
    overflow-wrap: anywhere;
}

.nota { color: #64748b; font-size: 13px; }

@media (max-width: 650px) {
    .grid { grid-template-columns: 1fr; }
    .completo { grid-column: auto; }
    .panel { padding: 17px; }
}
</style>
</head>
<body>

<header>
    <h1>SIGESMI</h1>
    <p>Sistema de Gestión de Servicios y Mantenimiento Informático</p>
</header>

<main>
<section class="panel">
    <h2>Registrar nuevo servicio</h2>
    <p class="nota">Los campos marcados con * son obligatorios.</p>

    <?php if ($error !== ""): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="grid">

            <div class="campo">
                <label>Folio del servicio</label>
                <div class="folio">
                    <?= h($folioMostrar) ?>
                </div>
                <p class="nota">Se genera automáticamente al guardar.</p>
            </div>

            <div class="campo">
                <label for="fecha_solicitud">Fecha de solicitud *</label>
                <input type="datetime-local"
                       id="fecha_solicitud"
                       name="fecha_solicitud"
                       value="<?= h($fechaMostrar) ?>"
                       required>
            </div>

            <div class="campo">
                <label for="id_area">Área *</label>
                <select id="id_area" name="id_area" required>
                    <option value="">Selecciona un área</option>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= h($a["id_area"]) ?>"
                        <?= seleccionado($a["id_area"], $actual["id_area"] ?? "") ?>>
                        <?= h($a["nombre_area"]) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="id_solicitante">Solicitante *</label>
                <select id="id_solicitante" name="id_solicitante" required>
                    <option value="">Selecciona un solicitante</option>
                    <?php foreach ($solicitantes as $s): ?>
                    <option value="<?= h($s["id_solicitante"]) ?>"
                        data-area="<?= h($s["id_area"]) ?>"
                        <?= seleccionado($s["id_solicitante"], $actual["id_solicitante"] ?? "") ?>>
                        <?= h(trim($s["nombre"] . " " .
                            $s["apellido_paterno"] . " " .
                            $s["apellido_materno"])) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="id_equipo">Equipo</label>
                <select id="id_equipo" name="id_equipo">
                    <option value="">Sin equipo asociado</option>
                    <?php foreach ($equipos as $e): ?>
                    <option value="<?= h($e["id_equipo"]) ?>"
                        <?= seleccionado($e["id_equipo"], $actual["id_equipo"] ?? "") ?>>
                        <?= h($e["codigo_inventario"] . " - " .
                            $e["tipo_equipo"] . " " .
                            $e["marca"] . " " . $e["modelo"]) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="id_tecnico">Técnico asignado</label>
                <select id="id_tecnico" name="id_tecnico">
                    <option value="">Sin técnico asignado</option>
                    <?php foreach ($tecnicos as $t): ?>
                    <option value="<?= h($t["id_tecnico"]) ?>"
                        <?= seleccionado($t["id_tecnico"], $actual["id_tecnico"] ?? "") ?>>
                        <?= h(trim($t["nombre"] . " " .
                            $t["apellido_paterno"] . " " .
                            $t["apellido_materno"])) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="id_tipo_servicio">Tipo de servicio *</label>
                <select id="id_tipo_servicio" name="id_tipo_servicio" required>
                    <option value="">Selecciona el tipo</option>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= h($t["id_tipo_servicio"]) ?>"
                        <?= seleccionado($t["id_tipo_servicio"], $actual["id_tipo_servicio"] ?? "") ?>>
                        <?= h($t["nombre_tipo"]) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="id_prioridad">Prioridad *</label>
                <select id="id_prioridad" name="id_prioridad" required>
                    <option value="">Selecciona la prioridad</option>
                    <?php foreach ($prioridades as $p): ?>
                    <option value="<?= h($p["id_prioridad"]) ?>"
                        <?= seleccionado($p["id_prioridad"], $actual["id_prioridad"] ?? "") ?>>
                        <?= h($p["nombre_prioridad"]) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="id_estado">Estado del servicio *</label>
                <select id="id_estado" name="id_estado" required>
                    <option value="">Selecciona el estado</option>
                    <?php foreach ($estados as $es): ?>
                    <option value="<?= h($es["id_estado"]) ?>"
                        <?= seleccionado($es["id_estado"], $actual["id_estado"] ?? "") ?>>
                        <?= h($es["nombre_estado"]) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo completo">
                <label for="descripcion_problema">Descripción del problema *</label>
                <textarea id="descripcion_problema"
                          name="descripcion_problema"
                          required><?= h($actual["descripcion_problema"] ?? "") ?></textarea>
            </div>

            <div class="campo completo">
                <label for="trabajo_realizado">Trabajo realizado</label>
                <textarea id="trabajo_realizado"
                          name="trabajo_realizado"><?= h($actual["trabajo_realizado"] ?? "") ?></textarea>
            </div>

            <div class="campo completo">
                <label for="observaciones">Observaciones</label>
                <textarea id="observaciones"
                          name="observaciones"><?= h($actual["observaciones"] ?? "") ?></textarea>
            </div>
        </div>

        <div class="acciones">
            <button type="submit" class="boton guardar">
                Guardar servicio
            </button>
            <a href="index.php" class="boton cancelar">Cancelar</a>
        </div>
    </form>
</section>
</main>

<script>
const area = document.getElementById("id_area");
const solicitante = document.getElementById("id_solicitante");

function filtrarSolicitantes() {
    const areaElegida = area.value;

    for (const opcion of solicitante.options) {
        if (!opcion.value) continue;

        const coincide = opcion.dataset.area === areaElegida;
        opcion.hidden = areaElegida !== "" && !coincide;
    }

    const actual = solicitante.selectedOptions[0];

    if (actual && actual.value &&
        areaElegida !== "" &&
        actual.dataset.area !== areaElegida) {
        solicitante.value = "";
    }
}

area.addEventListener("change", filtrarSolicitantes);
filtrarSolicitantes();
</script>

</body>
</html>