<?php
require_once 'conexion.php';

$mensaje = '';
$error = '';

function escapar($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

// Registrar evidencia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_servicio = filter_input(INPUT_POST, 'id_servicio', FILTER_VALIDATE_INT);
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (!$id_servicio || $id_servicio < 1) {
        $error = 'Selecciona un servicio válido.';
    } elseif (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Selecciona un archivo válido para subir.';
    } else {
        $archivo = $_FILES['archivo'];

        // Límite de 5 MB
        if ($archivo['size'] > 5 * 1024 * 1024) {
            $error = 'El archivo no debe superar los 5 MB.';
        } else {
            // Validar el tipo real del archivo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($archivo['tmp_name']);

            $tiposPermitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf'
            ];

            if (!isset($tiposPermitidos[$mime])) {
                $error = 'Formato no permitido. Usa JPG, PNG, WEBP o PDF.';
            } else {
                // Confirmar que el servicio existe
                $consulta = $conexion->prepare(
                    "SELECT id_servicio FROM servicios WHERE id_servicio = ?"
                );
                $consulta->execute([$id_servicio]);

                if (!$consulta->fetch()) {
                    $error = 'El servicio seleccionado no existe.';
                } else {
                    $carpeta = __DIR__ . '/uploads/evidencias/';

                    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
                        $error = 'No se pudo crear la carpeta para las evidencias.';
                    } else {
                        // Nombre aleatorio para evitar sobrescribir archivos
                        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $tiposPermitidos[$mime];
                        $destino = $carpeta . $nombreArchivo;

                        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
                            $error = 'No se pudo guardar el archivo en el servidor.';
                        } else {
                            $rutaRelativa = 'uploads/evidencias/' . $nombreArchivo;

                            try {
                                $insertar = $conexion->prepare(
                                    "INSERT INTO evidencias
                                    (id_servicio, nombre_archivo, ruta_archivo, tipo_archivo, descripcion)
                                    VALUES (?, ?, ?, ?, ?)"
                                );

                                $insertar->execute([
                                    $id_servicio,
                                    basename($archivo['name']),
                                    $rutaRelativa,
                                    $mime,
                                    $descripcion
                                ]);

                                $mensaje = 'Evidencia registrada correctamente.';
                            } catch (PDOException $e) {
                                // Si falla el registro, retirar el archivo para no dejarlo huérfano
                                if (is_file($destino)) {
                                    unlink($destino);
                                }

                                error_log($e->getMessage());
                                $error = 'No se pudo registrar la evidencia en la base de datos.';
                            }
                        }
                    }
                }
            }
        }
    }
}

// Consultar servicios para el formulario
$consultaServicios = $conexion->query(
    "SELECT id_servicio, folio
     FROM servicios
     ORDER BY id_servicio DESC"
);
$listaServicios = $consultaServicios->fetchAll();

// Consultar evidencias registradas
$sql = "SELECT
            ev.id_evidencia,
            ev.id_servicio,
            ev.nombre_archivo,
            ev.ruta_archivo,
            ev.tipo_archivo,
            ev.descripcion,
            ev.fecha_subida,
            s.folio
        FROM evidencias ev
        INNER JOIN servicios s
            ON ev.id_servicio = s.id_servicio
        ORDER BY ev.id_evidencia DESC";

$evidencias = $conexion->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Evidencias | SIGESMI</title>

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
            min-height: 100vh;
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

        .tarjeta {
            background: white;
            padding: 24px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .tarjeta h2 {
            font-size: 19px;
            margin-bottom: 20px;
        }

        .formulario {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .campo.completo {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: bold;
            color: #475569;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d5deea;
            border-radius: 8px;
            background: white;
            font-size: 14px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        .ayuda {
            color: #64748b;
            font-size: 12px;
        }

        .boton {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .boton:hover {
            background: #1d4ed8;
        }

        .alerta {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .exito {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .contador {
            display: inline-block;
            background: #eaf1ff;
            color: #1d4ed8;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }

        th {
            background: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 13px 12px;
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 13px 12px;
            border-bottom: 1px solid #e8edf4;
            font-size: 13px;
            vertical-align: top;
        }

        .archivo {
            color: #2563eb;
            font-weight: bold;
            text-decoration: none;
        }

        .archivo:hover {
            text-decoration: underline;
        }

        .vacio {
            text-align: center;
            padding: 30px;
            color: #718096;
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 100%;
                min-height: auto;
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

            .formulario {
                grid-template-columns: 1fr;
            }

            .campo.completo {
                grid-column: auto;
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
    <a href="servicios.php">📋 Servicios registrados</a>
    <a href="areas.php">🏢 Áreas</a>
    <a href="solicitantes.php">👥 Solicitantes</a>
    <a href="equipos.php">💻 Equipos</a>
    <a href="tecnicos.php">🧑‍🔧 Técnicos</a>
    <a href="evidencias.php" class="activo">📎 Evidencias</a>
</aside>

<main class="contenido">

    <header class="encabezado">
        <h1>Evidencias de servicios</h1>
        <p>Registra y consulta fotografías y documentos de los trabajos realizados.</p>
    </header>

    <?php if ($mensaje !== ''): ?>
        <div class="alerta exito">
            <?= escapar($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alerta error">
            <?= escapar($error) ?>
        </div>
    <?php endif; ?>

    <section class="tarjeta">
        <h2>📎 Registrar nueva evidencia</h2>

        <form method="POST" enctype="multipart/form-data" class="formulario">

            <div class="campo">
                <label for="id_servicio">Servicio relacionado *</label>

                <select name="id_servicio" id="id_servicio" required>
                    <option value="">Selecciona un servicio</option>

                    <?php foreach ($listaServicios as $servicio): ?>
                        <option
                            value="<?= (int)$servicio['id_servicio'] ?>"
                            <?= (string)($_POST['id_servicio'] ?? '') === (string)$servicio['id_servicio'] ? 'selected' : '' ?>
                        >
                            <?= escapar($servicio['folio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="archivo">Fotografía o documento *</label>

                <input
                    type="file"
                    name="archivo"
                    id="archivo"
                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                    required
                >

                <span class="ayuda">Formatos permitidos: JPG, PNG, WEBP y PDF. Máximo 5 MB.</span>
            </div>

            <div class="campo completo">
                <label for="descripcion">Descripción de la evidencia</label>

                <textarea
                    name="descripcion"
                    id="descripcion"
                    maxlength="1000"
                    placeholder="Ejemplo: Fotografía del equipo después del mantenimiento."
                ><?= escapar($_POST['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="campo completo">
                <div>
                    <button type="submit" class="boton">
                        Guardar evidencia
                    </button>
                </div>
            </div>

        </form>
    </section>

    <section class="tarjeta">
        <h2>📂 Evidencias registradas</h2>

        <div class="contador">
            Total de evidencias: <?= count($evidencias) ?>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Folio del servicio</th>
                        <th>Archivo</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Fecha de registro</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($evidencias) > 0): ?>

                    <?php foreach ($evidencias as $evidencia): ?>
                        <tr>
                            <td><?= (int)$evidencia['id_evidencia'] ?></td>

                            <td>
                                <?= escapar($evidencia['folio']) ?>
                            </td>

                            <td>
                                <a
                                    class="archivo"
                                    href="<?= escapar($evidencia['ruta_archivo']) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <?= escapar($evidencia['nombre_archivo']) ?>
                                </a>
                            </td>

                            <td><?= escapar($evidencia['tipo_archivo']) ?></td>

                            <td><?= escapar($evidencia['descripcion'] ?: 'Sin descripción') ?></td>

                            <td><?= escapar($evidencia['fecha_subida']) ?></td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="6" class="vacio">
                            Todavía no hay evidencias registradas.
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