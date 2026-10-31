<?php
require_once 'conexion.php';

// Consultar los servicios registrados
$sql = "SELECT
            s.id_servicio,
            s.folio,
            s.fecha_solicitud,
            a.nombre_area,
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
            es.nombre_estado
        FROM servicios s
        INNER JOIN areas a
            ON s.id_area = a.id_area
        INNER JOIN solicitantes so
            ON s.id_solicitante = so.id_solicitante
        LEFT JOIN equipos e
            ON s.id_equipo = e.id_equipo
        INNER JOIN estados_servicio es
            ON s.id_estado = es.id_estado
        ORDER BY s.id_servicio ASC";

$servicios = $conexion->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios | SIGESMI</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f3f6fb;
            color: #263445;
        }

        .sidebar {
            width: 240px;
            height: 100vh;
            background: #14243d;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 15px;
        }

        .logo {
            font-size: 23px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitulo {
            text-align: center;
            font-size: 12px;
            color: #b8c7dc;
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            color: #dce6f3;
            text-decoration: none;
            padding: 13px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.activo {
            background: #2563eb;
            color: white;
        }

        .contenido {
            margin-left: 240px;
            padding: 30px;
        }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .encabezado h1 {
            font-size: 27px;
            margin-bottom: 7px;
        }

        .encabezado p {
            color: #718096;
            font-size: 14px;
        }

        .boton {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .boton:hover {
            background: #1d4ed8;
        }

        .tarjeta {
            background: white;
            padding: 22px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .resumen {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .contador {
            background: #eaf1ff;
            color: #1d4ed8;
            padding: 15px 20px;
            border-radius: 10px;
            font-weight: bold;
        }

        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th {
            background: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 14px 12px;
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e8edf4;
            font-size: 13px;
            vertical-align: top;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .folio {
            color: #2563eb;
            font-weight: bold;
        }

        .estado {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            white-space: nowrap;
        }

        .vacio {
            text-align: center;
            padding: 35px;
            color: #718096;
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .sidebar a {
                display: inline-block;
                margin: 3px;
            }

            .contenido {
                margin-left: 0;
                padding: 18px;
            }

            .encabezado h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="logo">SIGESMI</div>
        <div class="subtitulo">Gestión de Servicios Informáticos</div>

        <a href="index.php">🏠 Panel principal</a>
        <a href="nuevo_servicio.php">➕ Nuevo servicio</a>
        <a href="servicios.php" class="activo">📋 Servicios registrados</a>
        <a href="areas.php">🏢 Áreas</a>
        <a href="solicitantes.php">👥 Solicitantes</a>
        <a href="equipos.php">💻 Equipos</a>
        <a href="tecnicos.php">🧑‍🔧 Técnicos</a>
        <a href="evidencias.php">📎 Evidencias</a>
    </aside>

    <main class="contenido">

        <div class="encabezado">
            <div>
                <h1>Servicios registrados</h1>
                <p>Consulta los trabajos de mantenimiento y soporte informático.</p>
            </div>

            <a href="nuevo_servicio.php" class="boton">
                + Nuevo servicio
            </a>
        </div>

        <section class="tarjeta">

            <div class="resumen">
                <h2>Listado de servicios</h2>

                <div class="contador">
                    Total de servicios: <?= count($servicios) ?>
                </div>
            </div>

            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Área</th>
                            <th>Solicitante</th>
                            <th>Equipo</th>
                            <th>Servicio realizado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($servicios) > 0): ?>

                        <?php foreach ($servicios as $servicio): ?>
                            <tr>
                                <td>
                                    <?= (int) $servicio['id_servicio'] ?>
                                </td>

                                <td class="folio">
                                    <?= htmlspecialchars($servicio['folio'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($servicio['fecha_solicitud'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($servicio['nombre_area'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($servicio['solicitante'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($servicio['equipo'] ?? 'Sin equipo', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($servicio['trabajo_realizado'] ?? 'Pendiente de registrar', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <span class="estado">
                                        <?= htmlspecialchars($servicio['nombre_estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="vacio">
                                Todavía no hay servicios para mostrar.
                                Puedes registrar uno con el botón
                                <strong>Nuevo servicio</strong>.
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