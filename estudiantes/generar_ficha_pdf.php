<?php
session_start();
require_once '../config/conexion.php';
require_once '../estadisticas/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("ID de estudiante no válido.");
}

// Obtener datos del estudiante, representante y padres
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

// Obtener historial escolar
$stmt_ins = $conexion->prepare("SELECT * FROM inscripciones WHERE estudiante_id = ? ORDER BY ano_escolar DESC");
$stmt_ins->bind_param("i", $id);
$stmt_ins->execute();
$inscripciones = $stmt_ins->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_ins->close();

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Inscripción</title>
    <style>
        @page {
            size: letter portrait;
            margin: 1.2cm 1.2cm 1.2cm 1.2cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }

        /* ========== ENCABEZADO ========== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .header-left {
            width: 20%;
        }
        .header-center {
            text-align: center;
            flex: 1;
        }
        .header-right {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
            justify-content: flex-end;
            width: 20%;
        }
        .foto-box {
            width: 65px;
            height: 85px;
            border: 1.5px solid #000;
            background: #fafafa;
        }

        .titulo {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            padding-top: 5px;
        }

        /* ========== SECCIONES ========== */
        .seccion {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 12pt;
            margin-top: 14px;
            margin-bottom: 4px;
            border-bottom: 0.5px solid #000;
            padding-bottom: 1px;
        }

        /* ========== FILAS ========== */
        .fila {
            margin-bottom: 3px;
            font-size: 12pt;
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
        }
        .fila .label {
            font-weight: bold;
            margin-right: 2px;
            white-space: nowrap;
        }
        .fila .dato {
            display: inline-block;
            padding: 0 2px;
            font-weight: normal;
        }
        .fila .check {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1.5px solid #000;
            text-align: center;
            line-height: 13px;
            font-weight: bold;
            font-size: 10pt;
            margin: 0 3px;
            background: white;
        }
        .fila .check.marcado {
            background: #003366;
            color: white;
        }
        .fila .check-label {
            margin-right: 6px;
        }
        .fila .sep {
            margin: 0 12px;
        }

        /* ========== TABLA ========== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 9pt;
        }
        th, td {
            border: 1px solid #000;
            text-align: center;
            padding: 3px 2px;
            height: 18px;
            vertical-align: middle;
        }
        th {
            font-weight: bold;
            background-color: #f2f2f2;
            font-size: 8pt;
            text-transform: uppercase;
        }
        .firma-linea {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 35px;
            height: 10px;
        }
        .ano-vacio {
            color: #999;
            font-size: 8pt;
        }
    </style>
</head>
<body>

<!-- ========== ENCABEZADO ========== -->
<div class="header">
    <div class="header-left">
        <!-- Espacio vacío a la izquierda -->
    </div>
    <div class="header-center">
        <div class="titulo">FICHA DE INSCRIPCIÓN</div>
    </div>
    <div class="header-right">
        <div class="foto-box"></div>
        <div class="foto-box"></div>
    </div>
</div>

<!-- ========== DATOS DEL ALUMNO ========== -->
<div class="seccion">DATOS DEL ALUMNO</div>

<!-- Fila 1: Nombres y Apellidos, Fecha Nacimiento, Sexo -->
<div class="fila">
    <span class="label">Nombres y Apellidos:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></span>
    <span class="sep"></span>
    <span class="label">Fecha Nacimiento:</span>
    <span class="dato"><?= date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) ?></span>
    <span class="sep"></span>
    <span class="label">Sexo:</span>
    <span class="dato"><?= ($estudiante['genero'] == 'V') ? 'Varón' : 'Hembra' ?></span>
</div>

<!-- Fila 2: Cédula Escolar, Nacionalidad, País de Nacimiento -->
<div class="fila">
    <span class="label">Cédula Escolar:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['cedula_escolar']) ?></span>
    <span class="sep"></span>
    <span class="label">Nacionalidad:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['nacionalidad'] ?? 'Venezolana') ?></span>
    <span class="sep"></span>
    <span class="label">País de Nacimiento:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['pais_nacimiento'] ?? 'Venezuela') ?></span>
</div>

<!-- Fila 3: Estado de Nacimiento, Dirección -->
<div class="fila">
    <span class="label">Estado de Nacimiento:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['estado_nacimiento']) ?></span>
    <span class="sep"></span>
    <span class="label">Dirección:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['direccion']) ?></span>
</div>

<!-- Fila 4: Estado de Residencia, Municipio, Parroquia, Ciudad -->
<div class="fila">
    <span class="label">Estado de Residencia:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['estado_residencia']) ?></span>
    <span class="sep"></span>
    <span class="label">Municipio:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['municipio']) ?></span>
    <span class="sep"></span>
    <span class="label">Parroquia:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['parroquia']) ?></span>
    <span class="sep"></span>
    <span class="label">Ciudad:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['ciudad']) ?></span>
</div>

<!-- Fila 5: Sufre Alguna Enfermedad, Cuál, Puede Realizar Educación Física -->
<div class="fila">
    <span class="label">Sufre Alguna Enfermedad:</span>
    <span class="check <?= ($estudiante['enfermedad'] == 'Si') ? 'marcado' : '' ?>"><?= ($estudiante['enfermedad'] == 'Si') ? 'X' : '' ?></span>
    <span class="check-label">Sí</span>
    <span class="check <?= ($estudiante['enfermedad'] != 'Si') ? 'marcado' : '' ?>"><?= ($estudiante['enfermedad'] != 'Si') ? 'X' : '' ?></span>
    <span class="check-label" style="margin-right:10px;">No</span>
    <span class="label">Cuál:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['enfermedad_cual']) ?></span>
    <span class="sep"></span>
    <span class="label">Puede Realizar Educación Física:</span>
    <span class="check <?= ($estudiante['educacion_fisica'] == 'Si') ? 'marcado' : '' ?>"><?= ($estudiante['educacion_fisica'] == 'Si') ? 'X' : '' ?></span>
    <span class="check-label">Sí</span>
</div>

<!-- Fila 6: No, Porque, Es alérgico, Cuál -->
<div class="fila">
    <span class="check <?= ($estudiante['educacion_fisica'] != 'Si') ? 'marcado' : '' ?>"><?= ($estudiante['educacion_fisica'] != 'Si') ? 'X' : '' ?></span>
    <span class="check-label" style="margin-right:10px;">No</span>
    <span class="label">Porque:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['educacion_fisica_porque']) ?></span>
    <span class="sep"></span>
    <span class="label">Es alérgico a algún medicamento:</span>
    <span class="check <?= ($estudiante['alergia'] == 'Si') ? 'marcado' : '' ?>"><?= ($estudiante['alergia'] == 'Si') ? 'X' : '' ?></span>
    <span class="check-label">Sí</span>
    <span class="check <?= ($estudiante['alergia'] != 'Si') ? 'marcado' : '' ?>"><?= ($estudiante['alergia'] != 'Si') ? 'X' : '' ?></span>
    <span class="check-label" style="margin-right:10px;">No</span>
    <span class="label">Cuál:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['alergia_cual']) ?></span>
</div>

<!-- ========== DATOS DEL REPRESENTANTE ========== -->
<div class="seccion" style="margin-top:12px;">DATOS DEL REPRESENTANTE</div>

<!-- Fila 1: Cédula de Identidad, Nombres y Apellidos -->
<div class="fila">
    <span class="label">Cédula de Identidad:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_cedula']) ?></span>
    <span class="sep"></span>
    <span class="label">Nombres y Apellidos:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_nombre']) ?></span>
</div>

<!-- Fila 2: Fecha Nacimiento, Estado Civil, Afinidad, Teléfono -->
<div class="fila">
    <span class="label">Fecha Nacimiento:</span>
    <span class="dato"><?= $estudiante['rep_fecha_nac'] ? date('d/m/Y', strtotime($estudiante['rep_fecha_nac'])) : '' ?></span>
    <span class="sep"></span>
    <span class="label">Estado Civil:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_estado_civil']) ?></span>
    <span class="sep"></span>
    <span class="label">Afinidad:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['afinidad']) ?></span>
    <span class="sep"></span>
    <span class="label">Teléfono:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_telefono']) ?></span>
</div>

<!-- Fila 3: Sexo, País de Nacimiento, Estado de Nacimiento, Nacionalidad -->
<div class="fila">
    <span class="label">Sexo:</span>
    <span class="dato"><?= ($estudiante['rep_sexo'] == 'V') ? 'Varón' : 'Hembra' ?></span>
    <span class="sep"></span>
    <span class="label">País de Nacimiento:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_pais_nac'] ?? 'Venezuela') ?></span>
    <span class="sep"></span>
    <span class="label">Estado de Nacimiento:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_estado_nac']) ?></span>
    <span class="sep"></span>
    <span class="label">Nacionalidad:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_nacionalidad'] ?? 'Venezolana') ?></span>
</div>

<!-- Fila 4: Dirección, Estado de Residencia, Municipio -->
<div class="fila">
    <span class="label">Dirección:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_direccion']) ?></span>
    <span class="sep"></span>
    <span class="label">Estado de Residencia:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_estado_res']) ?></span>
    <span class="sep"></span>
    <span class="label">Municipio:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_municipio']) ?></span>
</div>

<!-- Fila 5: Parroquia, Ciudad -->
<div class="fila">
    <span class="label">Parroquia:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_parroquia']) ?></span>
    <span class="sep"></span>
    <span class="label">Ciudad:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['rep_ciudad']) ?></span>
</div>

<!-- ========== DATOS DE LOS PADRES ========== -->
<div class="seccion" style="margin-top:12px;">DATOS DE LOS PADRES</div>

<!-- Fila 1: Madre, Cédula, Teléfono -->
<div class="fila">
    <span class="label">Nombres y Apellidos de la Madre:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['madre_nombre']) ?></span>
    <span class="sep"></span>
    <span class="label">Cédula:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['madre_cedula']) ?></span>
    <span class="sep"></span>
    <span class="label">Teléfono:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['madre_telefono']) ?></span>
</div>

<!-- Fila 2: Padre, Cédula, Teléfono -->
<div class="fila">
    <span class="label">Nombre y Apellido del Padre:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['padre_nombre']) ?></span>
    <span class="sep"></span>
    <span class="label">Cédula:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['padre_cedula']) ?></span>
    <span class="sep"></span>
    <span class="label">Teléfono:</span>
    <span class="dato"><?= htmlspecialchars($estudiante['padre_telefono']) ?></span>
</div>

<!-- ========== GRADO Y SECCIÓN ========== -->
<table>
    <thead>
        <tr>
            <th style="width:9%;">Año<br>Escolar</th>
            <th style="width:10%;">Grado y<br>Sección</th>
            <th style="width:5%;">Reg.</th>
            <th style="width:5%;">Rep</th>
            <th style="width:4%;">C</th>
            <th style="width:4%;">F</th>
            <th style="width:4%;">P</th>
            <th style="width:6%;">Peso</th>
            <th style="width:6%;">Talla</th>
            <th style="width:16%;">Firma<br>Representante</th>
            <th style="width:14%;">Fecha<br>Inscripción</th>
            <th style="width:12%;">Funcionario</th>
        </tr>
    </thead>
    <tbody>
        <?php for ($i = 0; $i < 8; $i++): ?>
            <?php 
                $ins = $inscripciones[$i] ?? null; 
                $ano_escolar_text = $ins ? htmlspecialchars($ins['ano_escolar']) : '20__ - 20__';
            ?>
            <tr>
                <td><?= $ano_escolar_text ?></td>
                <td><?= $ins ? htmlspecialchars($ins['grado_seccion']) : '' ?></td>
                <td><?= $ins ? htmlspecialchars($ins['registro']) : '' ?></td>
                <td><?= $ins ? htmlspecialchars($ins['repite']) : '' ?></td>
                <td><?= $ins ? htmlspecialchars($ins['c']) : '' ?></td>
                <td><?= $ins ? htmlspecialchars($ins['f']) : '' ?></td>
                <td><?= $ins ? htmlspecialchars($ins['p']) : '' ?></td>
                <td><?= $ins ? htmlspecialchars($ins['peso']) : '' ?></td>
                <td><?= $ins ? htmlspecialchars($ins['talla']) : '' ?></td>
                <td><span class="firma-linea"></span></td>
                <td><?= $ins ? htmlspecialchars($ins['fecha_inscripcion']) : '' ?></td>
                <td><?= $ins ? htmlspecialchars($ins['funcionario']) : '' ?></td>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Times New Roman');
$dompdf = new Dompdf($options);
$dompdf->setPaper('letter', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$nombre_archivo = "Ficha_Inscripcion_" . htmlspecialchars($estudiante['nombre']) . "_" . htmlspecialchars($estudiante['apellido']) . ".pdf";
$dompdf->stream($nombre_archivo, array('Attachment' => 0));
?>