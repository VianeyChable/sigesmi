<?php
require_once "conexion.php";

function contar($conexion, $sql) {
    return (int) $conexion->query($sql)->fetchColumn();
}

$trabajosMes = contar($conexion,
    "SELECT COUNT(*) FROM servicios
     WHERE fecha_solicitud >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
     AND fecha_solicitud < DATE_FORMAT(
         DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')"
);

$trabajosHoy = contar($conexion,
    "SELECT COUNT(*) FROM servicios
     WHERE fecha_solicitud >= CURDATE()
     AND fecha_solicitud < CURDATE() + INTERVAL 1 DAY"
);

$equiposAtendidos = contar($conexion,
    "SELECT COUNT(DISTINCT id_equipo) FROM servicios
     WHERE id_equipo IS NOT NULL
     AND fecha_solicitud >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
     AND fecha_solicitud < DATE_FORMAT(
         DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')"
);

$areasAtendidas = contar($conexion,
    "SELECT COUNT(DISTINCT id_area) FROM servicios
     WHERE fecha_solicitud >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
     AND fecha_solicitud < DATE_FORMAT(
         DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')"
);

$sql = "SELECT
            s.folio,
            s.fecha_solicitud,
            a.nombre_area AS area,
            CONCAT_WS(' ',
                so.nombre,
                so.apellido_paterno,
                so.apellido_materno
            ) AS solicitante,
            CONCAT_WS(' ',
                e.tipo_equipo,
                e.marca,
                e.modelo
            ) AS equipo,
            s.trabajo_realizado,
            es.nombre_estado AS estado
        FROM servicios s

        INNER JOIN areas a
            ON s.id_area = a.id_area

        INNER JOIN solicitantes so
            ON s.id_solicitante = so.id_solicitante

        LEFT JOIN equipos e
            ON s.id_equipo = e.id_equipo

        INNER JOIN estados_servicio es
            ON s.id_estado = es.id_estado

        ORDER BY s.fecha_solicitud DESC
        LIMIT 10";

$servicios = $conexion->query($sql)->fetchAll();

function h($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIGESMI | Panel principal</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    color: #243247;
}

.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 250px;
    background: #142d4e;
    color: white;
    padding: 28px 18px;
    flex-shrink: 0;
}

.logo {
    font-size: 25px;
    font-weight: bold;
    margin-bottom: 8px;
}

.sublogo {
    color: #b8c9df;
    font-size: 12px;
    margin-bottom: 38px;
    line-height: 1.5;
}

.menu-title {
    color: #9fb4cf;
    font-size: 11px;
    margin: 22px 10px 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.menu a {
    display: block;
    text-decoration: none;
    color: #e0e9f5;
    padding: 13px 12px;
    border-radius: 8px;
    margin-bottom: 6px;
    font-size: 14px;
}

.menu a.active,
.menu a:hover {
    background: #244a76;
    color: white;
}

.main {
    flex: 1;
    min-width: 0;
    padding: 30px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

h1 {
    font-size: 27px;
    margin-bottom: 8px;
    color: #172f4f;
}

.subtitle {
    color: #78869a;
    font-size: 14px;
}

.button {
    background: #2368d8;
    color: white;
    text-decoration: none;
    padding: 13px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
}

.button:hover {
    background: #1854b4;
}

.cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 30px;
}

.card {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(24, 49, 79, 0.05);
    border: 1px solid #e8edf4;
}

.card-label {
    color: #748198;
    font-size: 13px;
    margin-bottom: 17px;
}

.card-number {
    font-size: 31px;
    font-weight: bold;
    color: #17385f;
}

.card-note {
    font-size: 12px;
    color: #8b97a8;
    margin-top: 9px;
}

.panel {
    background: white;
    border: 1px solid #e8edf4;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 3px 15px rgba(24, 49, 79, 0.04);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

h2 {
    font-size: 18px;
    color: #17385f;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    min-width: 700px;
}

th {
    text-align: left;
    background: #f5f8fc;
    color: #63738a;
    padding: 14px 12px;
    font-weight: 600;
}

td {
    padding: 15px 12px;
    border-bottom: 1px solid #edf1f6;
}

.empty {
    text-align: center;
    padding: 35px 15px;
    color: #7d8ba0;
}

.status {
    background: #edf3fc;
    color: #245a9d;
    border-radius: 20px;
    padding: 6px 10px;
    display: inline-block;
    font-size: 12px;
}

.footer {
    text-align: center;
    color: #8b97a8;
    font-size: 12px;
    margin-top: 25px;
}

@media (max-width: 1050px) {
    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .layout {
        display: block;
    }

    .sidebar {
        width: 100%;
        padding: 20px;
    }

    .sublogo {
        margin-bottom: 18px;
    }

    .menu {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .menu-title {
        margin-top: 15px;
    }

    .menu a {
        padding: 10px;
    }

    .main {
        padding: 20px 14px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    h1 {
        font-size: 23px;
    }
}
</style>
</head>

<body>
<div class="layout">

    <aside class="sidebar">
        <div class="logo">SIGESMI</div>
        <div class="sublogo">
            Sistema de Gestión de Servicios<br>
            y Mantenimiento Informático
        </div>

        <div class="menu-title">Principal</div>
        <nav class="menu">
            <a class="active" href="index.php">▦ &nbsp; Panel principal</a>
            <a href="nuevo_servicio.php">＋ &nbsp; Nuevo servicio</a>
            <a href="servicios.php">☷ &nbsp; Servicios</a>
        </nav>

        <div class="menu-title">Administración</div>
        <nav class="menu">
            <a href="areas.php">▤ &nbsp; Áreas</a>
            <a href="equipos.php">▣ &nbsp; Equipos</a>
            <a href="solicitantes.php">♙ &nbsp; Solicitantes</a>
            <a href="tecnicos.php">⚙️ &nbsp; Técnicos</a>
            <a href="evidencias.php">  Evidencias</a>

<a href="reportes.php">  Reportes</a>

</nav> </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h1>Panel principal</h1>
                <p class="subtitle">
                    Resumen de servicios informáticos
                </p>
            </div>

            <a class="button" href="nuevo_servicio.php">
                ＋ Nuevo Servicio
            </a>
        </header>

        <section class="cards">
            <div class="card">
                <div class="card-label">Trabajos del mes</div>
                <div class="card-number"><?= $trabajosMes ?></div>
                <div class="card-note">Solicitudes registradas este mes</div>
            </div>

            <div class="card">
                <div class="card-label">Trabajos de hoy</div>
                <div class="card-number"><?= $trabajosHoy ?></div>
                <div class="card-note">Solicitudes registradas hoy</div>
            </div>

            <div class="card">
                <div class="card-label">Equipos atendidos</div>
                <div class="card-number"><?= $equiposAtendidos ?></div>
                <div class="card-note">Equipos distintos este mes</div>
            </div>

            <div class="card">
                <div class="card-label">Áreas atendidas</div>
                <div class="card-number"><?= $areasAtendidas ?></div>
                <div class="card-note">Áreas con servicios este mes</div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Servicios recientes</h2>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Área</th>
                            <th>Solicitante</th>
                            <th>Equipo</th>
                            <th>Trabajo realizado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($servicios)): ?>
                        <tr>
                            <td colspan="7" class="empty">
                                Todavía no hay servicios registrados.
                                <br><br>
                                Utiliza el botón "Nuevo Servicio"
                                para comenzar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($servicios as $s): ?>
                        <tr>
                            <td><?= h($s["folio"]) ?></td>
                            <td><?= h($s["fecha_solicitud"]) ?></td>
                            <td><?= h($s["area"]) ?></td>
                            <td><?= h($s["solicitante"]) ?></td>
                            <td><?= h($s["equipo"] ?: "No especificado") ?></td>
                            <td><?= h($s["trabajo_realizado"] ?: "Pendiente de registrar") ?></td>
                            <td><span class="status"><?= h($s["estado"]) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="footer">
            SIGESMI · Sistema de Gestión de Servicios y Mantenimiento Informático
        </div>
    </main>

</div>
</body>
</html>