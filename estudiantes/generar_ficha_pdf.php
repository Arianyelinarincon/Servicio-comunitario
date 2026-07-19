<?php
session_start();
require_once '../config/conexion.php';

// ========== INCLUIR DOMPDF ==========
require_once '../estadisticas/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// ========== VERIFICAR AUTENTICACIÓN ==========
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("ID de estudiante no válido.");
}

// ========== OBTENER DATOS DEL ESTUDIANTE Y REPRESENTANTE ==========
$stmt = $conexion->prepare("
    SELECT e.*, 
        r.nombre_completo AS rep_nombre, r.cedula AS rep_cedula, r.telefono AS rep_telefono,
        r.fecha_nacimiento AS rep_fecha_nac, r.estado_civil AS rep_estado_civil, r.afinidad,
        r.sexo AS rep_sexo, r.pais_nacimiento AS rep_pais_nac, r.estado_nacimiento AS rep_estado_nac,
        r.nacionalidad AS rep_nacionalidad, r.direccion AS rep_direccion,
        r.estado_residencia AS rep_estado_res, r.municipio AS rep_municipio,
        r.parroquia AS rep_parroquia, r.ciudad AS rep_ciudad
    FROM estudiantes e 
    LEFT JOIN representantes r ON e.representante_id = r.id 
    WHERE e.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    die("Estudiante no encontrado.");
}

// ========== OBTENER HISTORIAL ESCOLAR ==========
$stmt_ins = $conexion->prepare("SELECT * FROM inscripciones WHERE estudiante_id = ? ORDER BY ano_escolar DESC");
$stmt_ins->bind_param("i", $id);
$stmt_ins->execute();
$inscripciones = $stmt_ins->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_ins->close();

// ========== DETECTAR MODO ==========
$es_preview = isset($_GET['preview']) && $_GET['preview'] == '1';
$es_download = isset($_GET['download']) && $_GET['download'] == '1';

if (!$es_preview && !$es_download) {
    $es_download = true;
}

// ========== FUNCIONES AUXILIARES ==========
function checkmark($valor) {
    return ($valor === 'Si' || $valor === 'Sí') ? '&#10003;' : '';
}

function formatearCaso($texto, $tipo = 'titulo') {
    if (empty($texto)) return '';
    $texto = trim($texto);
    
    if (!function_exists('mb_convert_case')) {
        if ($tipo === 'oracion') {
            return ucfirst(strtolower($texto));
        }
        return ucwords(strtolower($texto));
    }
    
    if ($tipo === 'oracion') {
        $minuscula = mb_strtolower($texto, "UTF-8");
        return mb_strtoupper(mb_substr($minuscula, 0, 1, "UTF-8"), "UTF-8") . mb_substr($minuscula, 1, null, "UTF-8");
    }
    
    return mb_convert_case($texto, MB_CASE_TITLE, "UTF-8");
}

// ========== INICIAR BUFFER ==========
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Inscripción</title>
    
    <style>
        /* ===== CONFIGURACIÓN GLOBAL ===== */
        *, *:before, *:after {
            box-sizing: border-box !important;
        }

        /* ===== CONFIGURACIÓN DE PÁGINA ===== */
        @page {
            size: letter portrait;
            margin: 0 !important; /* Los márgenes los da el padding de .hoja */
        }
        
        html, body {
            margin: 0;
            padding: 0;
            background-color: <?= $es_preview ? '#f4f6f9' : '#fff' ?>;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            line-height: 1.4; /* Interlineado más aireado */
        }

        /* ===== CONTENEDOR PRINCIPAL ===== */
        .hoja {
            background-color: #fff;
            max-width: 21.59cm;          /* Ancho de carta */
            margin: <?= $es_preview ? '20px auto' : '0 auto' ?>;
            padding: 1.2cm 1.5cm;         /* Márgenes generosos como en la primera imagen */
            box-shadow: <?= $es_preview ? '0 0 10px rgba(0,0,0,0.1)' : 'none' ?>;
            box-sizing: border-box;
        }

        /* ===== CABECERA ===== */
        .header-container {
            position: relative;
            height: 3.5cm;
            margin-bottom: 0.2cm;
        }
        .fotos-container {
            position: absolute;
            top: 0;
            right: 0;
        }
        .foto-box {
            display: inline-block;
            width: 2.5cm;  
            height: 3.2cm; 
            border: 1px solid #000;
            background: transparent;
            margin-left: 0.4cm;
        }
        .header-title {
            position: absolute;
            top: 1.3cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ===== SECCIONES ===== */
        .seccion-titulo {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 0.5cm;    
            margin-bottom: 0.2cm;   
            font-size: 10.5pt;
            clear: both;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
        }
        .fila-dato {
            display: table;
            width: 100%;
            margin-bottom: 0.2cm;  
            table-layout: fixed !important;
        }
        .celda-dato {
            display: table-cell;
            vertical-align: bottom;
            font-size: 10pt;
            word-wrap: break-word; 
            overflow: hidden;
            padding: 0;
        }
        .celda-dato label {
            font-size: 10pt;
            font-weight: bold;
            margin-right: 0.1cm;
            display: inline;
            white-space: nowrap; 
        }
        .valor-dato {
            font-size: 10pt;
            font-weight: normal;
            display: inline;
            word-wrap: break-word; 
        }
        .opcion-group {
            display: inline-block;
            margin-right: 0.25cm;
            vertical-align: bottom;
            font-weight: bold;
        }
        .check-mark {
            font-family: "DejaVu Sans", "Arial Unicode MS", sans-serif !important;
            display: inline-block;
            font-weight: bold;
            font-size: 11pt;
            color: #000;
            width: 0.45cm;
            text-align: center;
            border-bottom: 1px solid #000;
            margin-left: 0.05cm;
            min-height: 15px;
        }

        /* ===== TABLA DE CONTROL MÉDICO ===== */
        .tabla-medica {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.15cm;
            margin-bottom: 0.3cm;
            table-layout: fixed !important;
        }
        .tabla-medica td {
            padding: 4px 0;
            vertical-align: top;
        }
        .tabla-medica label {
            font-weight: bold;
            font-size: 10pt;
        }

        /* ===== TABLA DE HISTORIAL ===== */
        .tabla-historial {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.4cm;
            font-size: 8.5pt;
            text-align: center;
            table-layout: fixed !important;
        }
        .tabla-historial th,
        .tabla-historial td {
            border: 1px solid #000;
            padding: 4px 2px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }
        .tabla-historial th {
            font-weight: bold;
            font-size: 7.5pt;
            background: transparent;
        }
        .tabla-historial td {
            font-size: 8.5pt;
            height: 0.8cm; 
        }

        /* ===== BOTONES (solo preview) ===== */
        .btn-accion {
            display: <?= $es_preview ? 'block' : 'none' ?>;
            text-align: center;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            transition: background 0.2s;
            margin: 0 4px;
        }
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn svg { margin-right: 6px; fill: currentColor; }

        /* ===== CORRECCIONES PARA IMPRESIÓN ===== */
        @media print {
            body { background-color: #fff !important; }
            .btn-accion { display: none !important; }
            .hoja {
                box-shadow: none !important;
                margin: 0 auto !important;
                max-width: 100% !important;
                padding: 1.2cm 1.5cm !important;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php if ($es_preview): ?>
<div class="btn-accion">
    <button onclick="window.print()" class="btn btn-info">
        <svg width="14" height="14" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg> Imprimir
    </button>
    <a href="generar_ficha_pdf.php?id=<?= $id ?>&download=1" class="btn btn-success">
        <svg width="14" height="14" viewBox="0 0 24 24"><path d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4.5-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 10h1V8.5H9V10zm5.5 2H15V8.5h-.5v3.5zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6z"/></svg> Descargar PDF
    </a>
    <a href="ver_ficha.php?id=<?= $id ?>" class="btn btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg> Volver
    </a>
</div>
<?php endif; ?>

<div class="hoja">

    <!-- ===== CABECERA ===== -->
    <div class="header-container">
        <div class="fotos-container">
            <span class="foto-box"></span>
            <span class="foto-box"></span>
        </div>
        <div class="header-title">FICHA DE INSCRIPCIÓN</div>
    </div>

    <!-- ===== DATOS DEL ALUMNO ===== -->
    <div class="seccion-titulo">DATOS DEL ALUMNO</div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:55%;"><label>Nombres y Apellidos.</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['apellido'] . ' ' . $estudiante['nombre'])) ?></span></div>
        <div class="celda-dato" style="width:30%;"><label>Fecha Nacimiento:</label> <span class="valor-dato"><?= !empty($estudiante['fecha_nacimiento']) ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) : '' ?></span></div>
        <div class="celda-dato" style="width:15%;"><label>Sexo</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['genero'] ?? '')) ?></span></div>
    </div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:32%;"><label>Cedula Escolar/</label> <span class="valor-dato"><?= htmlspecialchars($estudiante['cedula_escolar'] ?? '') ?></span></div>
        <div class="celda-dato" style="width:33%;"><label>Nacionalidad</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['nacionalidad'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:35%;"><label>Pais de Nacimiento</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['pais_nacimiento'] ?? '')) ?></span></div>
    </div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:35%;"><label>Estado de Nacimiento</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['estado_nacimiento'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:65%;"><label>Dirección:</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['direccion'] ?? '', 'oracion')) ?></span></div>
    </div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:30%;"><label>Estado de Residencia</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['estado_residencia'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:26%;"><label>Municipio</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['municipio'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:24%;"><label>Parroquia:</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['parroquia'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:20%;"><label>Ciudad</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['ciudad'] ?? '')) ?></span></div>
    </div>

    <!-- ===== TABLA DE CONTROL MÉDICO ===== -->
    <table class="tabla-medica">
        <tr>
            <td style="width: 38%;"><label>Sufre alguna enfermedad:</label></td>
            <td style="width: 20%; white-space: nowrap;">
                <span class="opcion-group">Si <span class="check-mark"><?= checkmark($estudiante['enfermedad'] ?? '') ?></span></span>
                <span class="opcion-group">No <span class="check-mark"><?= ($estudiante['enfermedad'] ?? '') == 'No' ? '&#10003;' : '' ?></span></span>
            </td>
            <td style="width: 42%;"><label>Cuál:</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['enfermedad_cual'] ?? '', 'oracion')) ?></span></td>
        </tr>
        <tr>
            <td><label>Puede realizar Ed. Física:</label></td>
            <td style="white-space: nowrap;">
                <span class="opcion-group">Si <span class="check-mark"><?= checkmark($estudiante['educacion_fisica'] ?? '') ?></span></span>
                <span class="opcion-group">No <span class="check-mark"><?= ($estudiante['educacion_fisica'] ?? '') == 'No' ? '&#10003;' : '' ?></span></span>
            </td>
            <td><label>Por qué:</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['educacion_fisica_porque'] ?? '', 'oracion')) ?></span></td>
        </tr>
        <tr>
            <td><label>Alergico a medicamento:</label></td>
            <td style="white-space: nowrap;">
                <span class="opcion-group">Si <span class="check-mark"><?= checkmark($estudiante['alergia'] ?? '') ?></span></span>
                <span class="opcion-group">No <span class="check-mark"><?= ($estudiante['alergia'] ?? '') == 'No' ? '&#10003;' : '' ?></span></span>
            </td>
            <td><label>Cuál:</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['alergia_cual'] ?? '', 'oracion')) ?></span></td>
        </tr>
    </table>

    <!-- ===== DATOS DEL REPRESENTANTE ===== -->
    <div class="seccion-titulo">DATOS DEL REPRESENTANTE</div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:35%;"><label>Cédula de Identidad:</label> <span class="valor-dato"><?= htmlspecialchars($estudiante['rep_cedula'] ?? '') ?></span></div>
        <div class="celda-dato" style="width:65%;"><label>Nombres y Apellidos:</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_nombre'] ?? '')) ?></span></div>
    </div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:25%;"><label>Fecha Nacimiento</label> <span class="valor-dato"><?= !empty($estudiante['rep_fecha_nac']) ? date('d/m/Y', strtotime($estudiante['rep_fecha_nac'])) : '' ?></span></div>
        <div class="celda-dato" style="width:23%;"><label>Estado Civil.</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_estado_civil'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:22%;"><label>Afinidad:</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['afinidad'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:30%;"><label>Teléfono:</label> <span class="valor-dato"><?= htmlspecialchars($estudiante['rep_telefono'] ?? '') ?></span></div>
    </div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:12%;"><label>Sexo</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_sexo'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:28%;"><label>Pais de Nacimiento</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_pais_nac'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:30%;"><label>Estado de Nacimiento:</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_estado_nac'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:30%;"><label>Nacionalidad:</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_nacionalidad'] ?? '')) ?></span></div>
    </div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:40%;"><label>Direccion</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_direccion'] ?? '', 'oracion')) ?></span></div>
        <div class="celda-dato" style="width:32%;"><label>Estado de Residencia.</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_estado_res'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:28%;"><label>Municipio</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_municipio'] ?? '')) ?></span></div>
    </div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:50%;"><label>Parroquia</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_parroquia'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:50%;"><label>Ciudad</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['rep_ciudad'] ?? '')) ?></span></div>
    </div>

    <!-- ===== DATOS DE LOS PADRES ===== -->
    <div class="seccion-titulo">DATOS DE LOS PADRES</div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:55%;"><label>Nombres y Apellidos de la Madre</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['madre_nombre'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:20%;"><label>Cédula</label> <span class="valor-dato"><?= htmlspecialchars($estudiante['madre_cedula'] ?? '') ?></span></div>
        <div class="celda-dato" style="width:25%;"><label>Teléfono</label> <span class="valor-dato"><?= htmlspecialchars($estudiante['madre_telefono'] ?? '') ?></span></div>
    </div>

    <div class="fila-dato">
        <div class="celda-dato" style="width:55%;"><label>Nombre y Apellido del Padre</label> <span class="valor-dato"><?= htmlspecialchars(formatearCaso($estudiante['padre_nombre'] ?? '')) ?></span></div>
        <div class="celda-dato" style="width:20%;"><label>Cédula</label> <span class="valor-dato"><?= htmlspecialchars($estudiante['padre_cedula'] ?? '') ?></span></div>
        <div class="celda-dato" style="width:25%;"><label>Teléfono</label> <span class="valor-dato"><?= htmlspecialchars($estudiante['padre_telefono'] ?? '') ?></span></div>
    </div>

    <!-- ===== HISTORIAL ESCOLAR ===== -->
    <table class="tabla-historial">
        <thead>
            <tr>
                <th style="width: 12%;">Año Escolar</th>
                <th style="width: 13%;">Grado y<br>Sección</th>
                <th style="width: 5%;">Reg.</th>
                <th style="width: 5%;">Rep</th>
                <th style="width: 5%;">C</th>
                <th style="width: 5%;">F</th>
                <th style="width: 5%;">P</th>
                <th style="width: 6%;">Peso</th>
                <th style="width: 6%;">Talla</th>
                <th style="width: 16%;">Firma<br>Representa-<br>nte</th>
                <th style="width: 11%;">Fecha<br>Inscripción</th>
                <th style="width: 11%;">Funcionario</th>
            </tr>
        </thead>
        <tbody>
            <?php for($i = 0; $i < 8; $i++): ?>
                <?php 
                $ins = isset($inscripciones[$i]) ? $inscripciones[$i] : null; 
                if ($ins):
                    $anos = explode('-', $ins['ano_escolar']);
                    $ano1 = isset($anos[0]) ? substr(trim($anos[0]), -2) : '';
                    $ano2 = isset($anos[1]) ? substr(trim($anos[1]), -2) : '';
                    $peso = isset($ins['peso']) && is_numeric($ins['peso']) ? round($ins['peso']) : '';
                    $talla = isset($ins['talla']) && is_numeric($ins['talla']) ? round($ins['talla']) : '';
                    if ($peso == 0) $peso = '';
                    if ($talla == 0) $talla = '';
                ?>
                <tr>
                    <td>20<?= htmlspecialchars($ano1) ?> -<br>20<?= htmlspecialchars($ano2) ?></td>
                    <td><?= htmlspecialchars(formatearCaso($ins['grado_seccion'] ?? '')) ?></td>
                    <td><?= htmlspecialchars(formatearCaso($ins['registro'] ?? '')) ?></td>
                    <td><?= htmlspecialchars(formatearCaso($ins['repite'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($ins['c'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['f'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['p'] ?? '') ?></td>
                    <td><?= $peso ?></td>
                    <td><?= $talla ?></td>
                    <td></td>
                    <td><?= !empty($ins['fecha_inscripcion']) ? date('d/m/Y', strtotime($ins['fecha_inscripcion'])) : '' ?></td>
                    <td></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td>20__ -<br>20__</td>
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
                <?php endif; ?>
            <?php endfor; ?>
        </tbody>
    </table>

</div>

</body>
</html>
<?php
$html = ob_get_clean();

// ========== SI ES PREVIEW, MOSTRAR EN PANTALLA ==========
if ($es_preview) {
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $html;
    exit;
}

// ========== SI ES DESCARGA, GENERAR PDF ==========
try {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');
    $options->set('encoding', 'UTF-8');

    $dompdf = new Dompdf($options);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->loadHtml($html);
    $dompdf->render();

    // ========== NOMBRE DEL ARCHIVO ==========
    $nombre_limpio = preg_replace('/[^a-zA-Z0-9_]/', '_', $estudiante['apellido'] . '_' . $estudiante['nombre']);
    $cedula_escolar = $estudiante['cedula_escolar'] ?? 'sin_cedula';
    $nombre_archivo = "ficha_inscripcion_" . $nombre_limpio . "_" . $cedula_escolar . ".pdf";

    $dompdf->stream($nombre_archivo, array('Attachment' => 1));
} catch (Exception $e) {
    die("Error al generar el PDF: " . $e->getMessage());
}
exit;
?>