<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("ID de estudiante no válido.");
}

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

$stmt_ins = $conexion->prepare("SELECT * FROM inscripciones WHERE estudiante_id = ? ORDER BY ano_escolar DESC");
$stmt_ins->bind_param("i", $id);
$stmt_ins->execute();
$inscripciones = $stmt_ins->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_ins->close();

function check_si_no($val, $option) {
    return (strtolower($val ?? '') === strtolower($option)) ? '<b>[X]</b>' : '[&nbsp;&nbsp;]';
}

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: letter; margin: 10mm 15mm; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.15; color: #000; margin: 0; padding: 0; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .header-table td { border: none; padding: 0; }
        .titulo-principal { font-size: 15px; font-weight: bold; text-align: center; margin: 0; letter-spacing: 0.5px; }
        .subtitulo { font-size: 9px; text-align: center; margin: 2px 0 0 0; color: #444; }
        .foto-box { width: 62px; height: 75px; border: 1px solid #000; text-align: center; font-size: 7px; vertical-align: middle; display: inline-block; }
        .seccion-titulo { font-weight: bold; font-size: 10px; text-transform: uppercase; margin-top: 8px; margin-bottom: 3px; border-bottom: 1px solid #000; padding-bottom: 2px; }
        
        /* Estructura en tablas para Dompdf */
        .form-table { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
        .form-table td { padding: 3px 2px; font-size: 10px; vertical-align: bottom; }
        .label { font-weight: normal; color: #000; white-space: nowrap; }
        .val { font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #000; padding-left: 3px; }
        
        .tabla-historial { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 8px; }
        .tabla-historial th, .tabla-historial td { border: 1px solid #000; padding: 3px 2px; text-align: center; }
        .tabla-historial th { font-weight: bold; background-color: #f2f2f2; font-size: 8.5px; }
        .tabla-historial td { font-weight: bold; }
        .dato-line { display: inline-block; min-width: 15px; text-align: center; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 80%; vertical-align: middle;">
                <h3 class="titulo-principal">FICHA DE INSCRIPCIÓN</h3>
                <p class="subtitulo"><b>E.B.N. "Juan Pablo Pérez Alfonzo"</b></p>
                <p class="subtitulo" style="font-weight: bold;">Año Escolar: ' . date('Y') . '-' . (date('Y') + 1) . '</p>
            </td>
            <td style="width: 20%; text-align: right; vertical-align: top;">
                <div class="foto-box" style="margin-right: 4px;"><br><br><br>FOTO 3x4</div>
                <div class="foto-box"><br><br><br>HUELLA</div>
            </td>
        </tr>
    </table>

    <div class="seccion-titulo">DATOS DEL ALUMNO</div>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 15%;">Nombres y Apellidos:</td>
            <td class="val" style="width: 45%;">' . htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) . '</td>
            <td class="label" style="width: 15%; text-align: right;">Fecha Nac.:</td>
            <td class="val" style="width: 15%; text-align: center;">' . (!empty($estudiante['fecha_nacimiento']) ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) : '') . '</td>
            <td class="label" style="width: 5%; text-align: right;">Sexo:</td>
            <td class="val" style="width: 5%; text-align: center;">' . htmlspecialchars($estudiante['genero'] ?? '') . '</td>
        </tr>
    </table>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 12%;">Cedula Escolar:</td>
            <td class="val" style="width: 23%;">' . htmlspecialchars($estudiante['cedula_escolar'] ?? '') . '</td>
            <td class="label" style="width: 10%; text-align: right;">Nacionalidad:</td>
            <td class="val" style="width: 20%;">' . htmlspecialchars($estudiante['nacionalidad'] ?? 'VENEZOLANA') . '</td>
            <td class="label" style="width: 15%; text-align: right;">Pais Nacimiento:</td>
            <td class="val" style="width: 20%;">' . htmlspecialchars($estudiante['pais_nacimiento'] ?? '') . '</td>
        </tr>
    </table>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 15%;">Estado de Nacimiento:</td>
            <td class="val" style="width: 25%;">' . htmlspecialchars($estudiante['estado_nacimiento'] ?? '') . '</td>
            <td class="label" style="width: 10%; text-align: right;">Dirección:</td>
            <td class="val" style="width: 50%;">' . htmlspecialchars($estudiante['direccion'] ?? '') . '</td>
        </tr>
    </table>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 15%;">Estado Residencia:</td>
            <td class="val" style="width: 15%;">' . htmlspecialchars($estudiante['estado_residencia'] ?? '') . '</td>
            <td class="label" style="width: 10%; text-align: right;">Municipio:</td>
            <td class="val" style="width: 20%;">' . htmlspecialchars($estudiante['municipio'] ?? '') . '</td>
            <td class="label" style="width: 10%; text-align: right;">Parroquia:</td>
            <td class="val" style="width: 15%;">' . htmlspecialchars($estudiante['parroquia'] ?? '') . '</td>
            <td class="label" style="width: 5%; text-align: right;">Ciudad:</td>
            <td class="val" style="width: 10%;">' . htmlspecialchars($estudiante['ciudad'] ?? '') . '</td>
        </tr>
    </table>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 18%;">Sufre Alguna Enfermedad:</td>
            <td style="width: 15%; font-weight: bold;">
                Si ' . check_si_no($estudiante['enfermedad'], 'Si') . ' &nbsp; No ' . check_si_no($estudiante['enfermedad'], 'No') . '
            </td>
            <td class="label" style="width: 5%; text-align: right;">Cual:</td>
            <td class="val" style="width: 22%;">' . htmlspecialchars($estudiante['enfermedad_cual'] ?? '') . '</td>
            <td class="label" style="width: 22%; text-align: right;">Realiza Educ. Física:</td>
            <td style="width: 18%; font-weight: bold;">
                Si ' . check_si_no($estudiante['educacion_fisica'], 'Si') . ' &nbsp; No ' . check_si_no($estudiante['educacion_fisica'], 'No') . '
            </td>
        </tr>
    </table>

    <div class="seccion-titulo">DATOS DEL REPRESENTANTE</div>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 15%;">Cédula de Identidad:</td>
            <td class="val" style="width: 25%;">' . htmlspecialchars($estudiante['rep_cedula'] ?? '') . '</td>
            <td class="label" style="width: 15%; text-align: right;">Nombres y Apellidos:</td>
            <td class="val" style="width: 45%;">' . htmlspecialchars($estudiante['rep_nombre'] ?? '') . '</td>
        </tr>
    </table>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 12%;">Fecha Nacimiento:</td>
            <td class="val" style="width: 18%; text-align: center;">' . (!empty($estudiante['rep_fecha_nac']) ? date('d/m/Y', strtotime($estudiante['rep_fecha_nac'])) : '') . '</td>
            <td class="label" style="width: 10%; text-align: right;">Estado Civil:</td>
            <td class="val" style="width: 15%;">' . htmlspecialchars($estudiante['rep_estado_civil'] ?? '') . '</td>
            <td class="label" style="width: 10%; text-align: right;">Afinidad:</td>
            <td class="val" style="width: 15%;">' . htmlspecialchars($estudiante['afinidad'] ?? '') . '</td>
            <td class="label" style="width: 8%; text-align: right;">Teléfono:</td>
            <td class="val" style="width: 12%;">' . htmlspecialchars($estudiante['rep_telefono'] ?? '') . '</td>
        </tr>
    </table>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 8%;">Direccion:</td>
            <td class="val" style="width: 42%;">' . htmlspecialchars($estudiante['rep_direccion'] ?? '') . '</td>
            <td class="label" style="width: 18%; text-align: right;">Estado de Residencia:</td>
            <td class="val" style="width: 12%;">' . htmlspecialchars($estudiante['rep_estado_res'] ?? '') . '</td>
            <td class="label" style="width: 10%; text-align: right;">Municipio:</td>
            <td class="val" style="width: 10%;">' . htmlspecialchars($estudiante['rep_municipio'] ?? '') . '</td>
        </tr>
    </table>

    <div class="seccion-titulo">DATOS DE LOS PADRES</div>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 20%;">Nombres y Apellidos Madre:</td>
            <td class="val" style="width: 40%;">' . htmlspecialchars($estudiante['madre_nombre'] ?? '') . '</td>
            <td class="label" style="width: 8%; text-align: right;">Cédula:</td>
            <td class="val" style="width: 14%;">' . htmlspecialchars($estudiante['madre_cedula'] ?? '') . '</td>
            <td class="label" style="width: 8%; text-align: right;">Teléfono:</td>
            <td class="val" style="width: 10%;">' . htmlspecialchars($estudiante['madre_telefono'] ?? '') . '</td>
        </tr>
    </table>
    <table class="form-table">
        <tr>
            <td class="label" style="width: 20%;">Nombre y Apellido Padre:</td>
            <td class="val" style="width: 40%;">' . htmlspecialchars($estudiante['padre_nombre'] ?? '') . '</td>
            <td class="label" style="width: 8%; text-align: right;">Cédula:</td>
            <td class="val" style="width: 14%;">' . htmlspecialchars($estudiante['padre_cedula'] ?? '') . '</td>
            <td class="label" style="width: 8%; text-align: right;">Teléfono:</td>
            <td class="val" style="width: 10%;">' . htmlspecialchars($estudiante['padre_telefono'] ?? '') . '</td>
        </tr>
    </table>

    <table class="tabla-historial">
        <thead>
            <tr>
                <th style="width: 12%;">Año Escolar</th>
                <th style="width: 20%;">Grado y Sección</th>
                <th style="width: 5%;">Reg.</th>
                <th style="width: 5%;">Rep</th>
                <th style="width: 4%;">C</th>
                <th style="width: 4%;">F</th>
                <th style="width: 4%;">P</th>
                <th style="width: 8%;">Peso</th>
                <th style="width: 8%;">Talla</th>
                <th style="width: 12%;">Firma Rep.</th>
                <th style="width: 11%;">Fecha Insc.</th>
                <th style="width: 11%;">Funcionario</th>
            </tr>
        </thead>
        <tbody>';

        for ($i = 0; $i < 8; $i++) {
            $ins = $inscripciones[$i] ?? null;
            if ($ins) {
                $anos = explode('-', $ins['ano_escolar']);
                $ano1 = isset($anos[0]) ? substr(trim($anos[0]), -2) : '';
                $ano2 = isset($anos[1]) ? substr(trim($anos[1]), -2) : '';
                
                $html .= '
                <tr>
                    <td>20<span class="dato-line">' . $ano1 . '</span> - 20<span class="dato-line">' . $ano2 . '</span></td>
                    <td>' . htmlspecialchars($ins['grado_seccion'] ?? '') . '</td>
                    <td>' . htmlspecialchars($ins['registro'] ?? '') . '</td>
                    <td>' . htmlspecialchars($ins['repite'] ?? '') . '</td>
                    <td>' . htmlspecialchars($ins['c'] ?? '') . '</td>
                    <td>' . htmlspecialchars($ins['f'] ?? '') . '</td>
                    <td>' . htmlspecialchars($ins['p'] ?? '') . '</td>
                    <td>' . htmlspecialchars($ins['peso'] ?? '') . '</td>
                    <td>' . htmlspecialchars($ins['talla'] ?? '') . '</td>
                    <td></td> 
                    <td>' . (!empty($ins['fecha_inscripcion']) ? date('d/m/Y', strtotime($ins['fecha_inscripcion'])) : '') . '</td>
                    <td>' . htmlspecialchars($ins['funcionario'] ?? '') . '</td>
                </tr>';
            } else {
                $html .= '
                <tr>
                    <td>20<span class="dato-line"></span> - 20<span class="dato-line"></span></td>
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>';
            }
        }

$html .= '
        </tbody>
    </table>
</body>
</html>';

if (file_exists(__DIR__ . '/../estadisticas/dompdf/autoload.inc.php')) {
    require_once __DIR__ . '/../estadisticas/dompdf/autoload.inc.php';
} else {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$nombre_base = 'ficha_' . $estudiante['apellido'] . '_' . $estudiante['nombre'];
$nombre_archivo = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nombre_base) . '.pdf';

$dompdf->stream($nombre_archivo, array('Attachment' => 1));
?>