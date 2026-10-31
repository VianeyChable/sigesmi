<?php
require_once 'conexion.php';

$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$id_area = $_GET['id_area'] ?? '';
$id_estado = $_GET['id_estado'] ?? '';

$areas = $conexion->query(
    "SELECT id_area, nombre_area FROM areas ORDER BY nombre_area"
)->fetchAll();

$estados = $conexion->query(
    "SELECT id_estado, nombre_estado FROM estados_servicio ORDER BY id_estado"
)->fetchAll();

$sql = "SELECT
            s.folio,
            s.fecha_solicitud,
            a.nombre_area,
            CONCAT_WS(' ',
                sol.nombre,
                sol.apellido_paterno,
                sol.apellido_materno
            ) AS solicitante,
            CONCAT_WS(' ',
                e.tipo_equipo,
                e.marca,
                e.modelo
            ) AS equipo,
            CONCAT_WS(' ',
                t.nombre,
                t.apellido_paterno,
                t.apellido_materno
            ) AS tecnico,
            s.trabajo_realizado,
            es.nombre_estado
        FROM servicios s
        LEFT JOIN areas a ON s.id_area = a.id_area
        LEFT JOIN solicitantes sol
            ON s.id_solicitante = sol.id_solicitante
        LEFT JOIN equipos e ON s.id_equipo = e.id_equipo
        LEFT JOIN tecnicos t ON s.id_tecnico = t.id_tecnico
        LEFT JOIN estados_servicio es
            ON s.id_estado = es.id_estado
        WHERE 1=1";

$params = [];

if ($fecha_inicio !== '') {
    $sql .= " AND s.fecha_solicitud >= ?";
    $params[] = $fecha_inicio;
}

if ($fecha_fin !== '') {
    $sql .= " AND s.fecha_solicitud < DATE_ADD(?, INTERVAL 1 DAY)";
    $params[] = $fecha_fin;
}

if ($id_area !== '' && ctype_digit($id_area)) {
    $sql .= " AND s.id_area = ?";
    $params[] = (int)$id_area;
}

if ($id_estado !== '' && ctype_digit($id_estado)) {
    $sql .= " AND s.id_estado = ?";
    $params[] = (int)$id_estado;
}

$sql .= " ORDER BY s.fecha_solicitud DESC, s.id_servicio DESC";

$consulta = $conexion->prepare($sql);
$consulta->execute($params);
$reportes = $consulta->fetchAll();

$total = count($reportes);

// Contar servicios por estado según los filtros aplicados
$contadores = [
'Pendiente' => 0,
'Asignado' => 0,
'En proceso' => 0,
'En espera' => 0,
'Finalizado' => 0,
'Cancelado' => 0
];

foreach ($reportes as $reporte) {
$estado = $reporte['nombre_estado'] ?? '';

if (isset($contadores[$estado])) {
    $contadores[$estado]++;
}

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reportes | SIGESMI</title>
<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    padding: 25px;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

.contenedor {
    max-width: 1400px;
    margin: auto;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

h1 { color: #10243e; }
.tarjetas-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.tarjeta {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0000000d;
    border-left: 5px solid #176b87;
}

.tarjeta span {
    display: block;
    color: #64748b;
    font-size: 14px;
    margin-bottom: 10px;
}

.tarjeta strong {
    display: block;
    color: #10243e;
    font-size: 30px;
}

.tarjeta.pendiente {
    border-left-color: #f59e0b;
}

.tarjeta.proceso {
    border-left-color: #3b82f6;
}

.tarjeta.finalizado {
    border-left-color: #16a34a;
}

@media print {
    .tarjetas-resumen {
        grid-template-columns: repeat(4, 1fr);
    }
}

.filtros, .resultados {
    background: white;
    padding: 22px;
    margin-top: 20px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #0000000d;
}

.formulario {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
}

input, select {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
}

button, .boton {
    display: inline-block;
    padding: 11px 16px;
    border: none;
    border-radius: 7px;
    background: #176b87;
    color: white;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}

.secundario { background: #64748b; }

.resumen {
    margin-top: 20px;
    padding: 18px;
    background: #dbeafe;
    border-radius: 10px;
    font-weight: bold;
}

.tabla {
    overflow-x: auto;
    margin-top: 15px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1050px;
}

th, td {
    padding: 11px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    font-size: 13px;
    vertical-align: top;
}

th {
    background: #10243e;
    color: white;
}

@media print {
    body { background: white; padding: 0; }
    .no-imprimir { display: none !important; }
    .filtros, .resultados { box-shadow: none; padding: 5px; }
    th { background: #ddd !important; color: black !important; }
    table { min-width: 0; }
    th, td { font-size: 9px; padding: 5px; }
}
</style>
</head>
<body>
<div class="contenedor">

    <div class="encabezado">
        <div>
            <h1>Reportes de servicios</h1>
            <p>SIGESMI · Gestión de Servicios Informáticos</p>
        </div>

        <div class="no-imprimir">
            <a class="boton secundario" href="index.php">Volver al panel</a>
            <button onclick="window.print()">Imprimir / Guardar PDF</button>
        </div>
    </div>

    <section class="filtros no-imprimir">
        <h3>Filtrar reportes</h3>

        <form method="GET" action="reportes.php">
            <div class="formulario">
                <div>
                    <label for="fecha_inicio">Fecha inicial</label>
                    <input type="date" id="fecha_inicio"
                           name="fecha_inicio"
                           value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>

                <div>
                    <label for="fecha_fin">Fecha final</label>
                    <input type="date" id="fecha_fin"
                           name="fecha_fin"
                           value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>

                <div>
                    <label for="id_area">Área</label>
                    <select id="id_area" name="id_area">
                        <option value="">Todas las áreas</option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= $area['id_area'] ?>"
                                <?= (string)$id_area === (string)$area['id_area']
                                    ? 'selected' : '' ?>>
                                <?= htmlspecialchars($area['nombre_area']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="id_estado">Estado</label>
                    <select id="id_estado" name="id_estado">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado['id_estado'] ?>"
                                <?= (string)$id_estado === (string)$estado['id_estado']
                                    ? 'selected' : '' ?>>
                                <?= htmlspecialchars($estado['nombre_estado']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <p>
                <button type="submit">Consultar</button>
                <a class="boton secundario" href="reportes.php">Limpiar filtros</a>
            </p>
        </form>
    </section>

    <div class="tarjetas-resumen">

<div class="tarjeta">
    <span>Total de servicios</span>
    <strong><?= $total ?></strong>
</div>

<div class="tarjeta pendiente">
    <span>Pendientes</span>
    <strong><?= $contadores['Pendiente'] ?></strong>
</div>

<div class="tarjeta proceso">
    <span>En proceso</span>
    <strong><?= $contadores['En proceso'] ?></strong>
</div>

<div class="tarjeta finalizado">
    <span>Finalizados</span>
    <strong><?= $contadores['Finalizado'] ?></strong>
</div>

</div>

    <section class="resultados">
        <h3>Detalle de servicios</h3>

        <div class="tabla">
            <table>
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Área</th>
                        <th>Solicitante</th>
                        <th>Equipo</th>
                        <th>Técnico</th>
                        <th>Trabajo realizado</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($total > 0): ?>
                    <?php foreach ($reportes as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['folio'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['fecha_solicitud'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['nombre_area'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['solicitante'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['equipo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['tecnico'] ?? '') ?></td>
                            <td><?= nl2br(htmlspecialchars($r['trabajo_realizado'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($r['nombre_estado'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No se encontraron servicios con esos filtros.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>
</body>
</html>