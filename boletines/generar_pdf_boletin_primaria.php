<?php
session_start();
// if (!isset($_SESSION['estudiante'])) {
//     header("Location: index.php");
//     exit();
// }

require_once '../estadisticas/dompdf/autoload.inc.php';
require_once '../config/conexion.php';
require_once '../config/configuracion.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ========== OBTENER PERIODO ESCOLAR ==========
$periodo_escolar_actual = obtenerPeriodoEscolar();

// ========== OBTENER DATOS DEL ESTUDIANTE DESDE BD ==========
$estudiante_id = $_SESSION['estudiante_id'] ?? 0;
$estudiante_nombre = $_SESSION['estudiante'] ?? '';
$ce = $_SESSION['ce'] ?? '';
$ano_escolar = $_SESSION['ano_escolar'] ?? $periodo_escolar_actual;
$docente = $_SESSION['docente'] ?? 'No asignado';
$representante = $_SESSION['representante'] ?? 'No registrado';
$seccion = 'U';
$sala_codigo = '';
$grado_legible = '';

// Nombres legibles para salas / grados
$nombres_salas = [
    'sala4' => 'Sala 4 Años',
    'sala5' => 'Sala 5 Años',
    '1ro'   => '1er Grado',
    '2do'   => '2do Grado',
    '3ro'   => '3er Grado',
    '4to'   => '4to Grado',
    '5to'   => '5to Grado',
    '6to'   => '6to Grado'
];

if ($estudiante_id > 0) {
    $stmt = $conexion->prepare("SELECT e.sala, s.nombre AS seccion_nombre
                                FROM estudiantes e
                                LEFT JOIN secciones s ON e.seccion_id = s.id
                                WHERE e.id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $estudiante_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $sala_codigo = $row['sala'] ?? '';
            $seccion = $row['seccion_nombre'] ?? 'U';
            $grado_legible = $nombres_salas[$sala_codigo] ?? $sala_codigo;
        }
        $stmt->close();
    }
}

// Si no se pudo obtener de la BD, usar lo que haya en sesión
if (empty($grado_legible)) {
    $sala_codigo = $_SESSION['sala_codigo'] ?? '';
    $grado_legible = $nombres_salas[$sala_codigo] ?? $sala_codigo;
    $seccion = $_SESSION['seccion'] ?? 'U';
}

// ===== FORMATO DEL GRADO CON SECCIÓN ENTRE COMILLAS =====
$grado_formateado = $grado_legible . ' "' . $seccion . '"';

// ========== VARIABLES DE CONTENIDO (PRIMARIA) ==========
$estudiante = htmlspecialchars($estudiante_nombre);
$ce = htmlspecialchars($ce);
$ano_escolar = htmlspecialchars($ano_escolar);
$docente = htmlspecialchars($docente);
$representante = htmlspecialchars($representante);
$observacion = nl2br(htmlspecialchars($_SESSION['observacion'] ?? ''));

// LAPSO 1
$l1_proyecto = htmlspecialchars($_SESSION['l1_proyecto'] ?? '');
$l1_analisis = nl2br(htmlspecialchars($_SESSION['l1_analisis'] ?? ''));
$l1_sugerencias = nl2br(htmlspecialchars($_SESSION['l1_sugerencias'] ?? ''));

// LAPSO 2
$l2_proyecto = htmlspecialchars($_SESSION['l2_proyecto'] ?? '');
$l2_analisis = nl2br(htmlspecialchars($_SESSION['l2_analisis'] ?? ''));
$l2_sugerencias = nl2br(htmlspecialchars($_SESSION['l2_sugerencias'] ?? ''));

// LAPSO 3
$l3_proyecto = htmlspecialchars($_SESSION['l3_proyecto'] ?? '');
$l3_analisis = nl2br(htmlspecialchars($_SESSION['l3_analisis'] ?? ''));
$l3_sugerencias = nl2br(htmlspecialchars($_SESSION['l3_sugerencias'] ?? ''));

// RESULTADO FINAL
$resultado_final = htmlspecialchars($_SESSION['resultado_final'] ?? '');
$literal_final = htmlspecialchars($_SESSION['literal_final'] ?? '');

// ========== LOGO ==========
$logo_path = 'C:/xampp/htdocs/Servicio-comunitario/includes/image/logo1.png';
$logo_html = '';
if (extension_loaded('gd')) {
    if (file_exists($logo_path)) {
        try {
            $logo_data = file_get_contents($logo_path);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $logo_path);
            finfo_close($finfo);
            if ($mime_type && strpos($mime_type, 'image/') === 0) {
                $logo_base64 = 'data:' . $mime_type . ';base64,' . base64_encode($logo_data);
                $logo_html = '<img src="' . $logo_base64 . '" style="width:75px; height:auto; display:block; margin:0 auto;" alt="Logo">';
            } else {
                $logo_html = '<div style="text-align:center; font-weight:bold; font-size:14pt;">LOGO</div>';
            }
        } catch (Exception $e) {
            $logo_html = '<div style="text-align:center; font-weight:bold; font-size:14pt;">LOGO</div>';
        }
    } else {
        $logo_html = '<div style="text-align:center; font-weight:bold; font-size:14pt;">LOGO</div>';
    }
} else {
    $logo_html = '<div style="text-align:center; font-weight:bold; font-size:14pt;">LOGO</div>';
}

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boletín Informativo Primaria</title>
    <style>
        @page {
            size: 279.4mm 215.9mm;
            margin: 10mm; 
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt; 
            line-height: 2;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .tabla-triptico {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; 
        }
        .columna-triptico {
            width: 33.33%;
            padding: 0 15px; 
            vertical-align: top;
        }
        .titulo-momento {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 11pt;
        }
        .etiqueta-area {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        .espacio-proyecto {
            height: 50px;
            border-bottom: 1px solid #000000;
            overflow: hidden;
            display: block;
            margin-bottom: 10px;
        }
        .espacio-aprendizaje {
            height: 330px; 
            border-bottom: 1px solid #000000;
            overflow: hidden;
            display: block;
            margin-bottom: 10px;
            text-align: justify;
        }
        .espacio-sugerencia {
            height: 70px;
            overflow: hidden;
            display: block;
            margin-bottom: 25px;
            text-align: justify;
        }
        .firmas-momento {
            width: 100%;
            font-size: 9pt;
            border-collapse: collapse;
        }
        .firmas-momento td {
            text-align: center;
            vertical-align: bottom;
            height: 30px;
            padding-bottom: 5px;
        }
        .linea-firma-inline {
            display: inline-block;
            border-bottom: 1px solid #000; 
            height: 0px;
            vertical-align: bottom; 
            margin-left: 5px; 
        }
        .header-portada {
            text-align: center;
            font-size: 10pt;
            line-height: 1.3;
        }
        .texto-negrita { font-weight: bold; }
        .page-break { page-break-before: always; }
        .logo-portada {
            text-align: center !important;
            margin: 15px auto !important;
            width: 100%;
        }
        .logo-portada img {
            display: inline-block !important;
            margin: 0 auto !important;
            width: 75px;
            height: auto;
        }
        .casilla {
            display: inline-block;
            width: 26px;
            height: 26px;
            border: 2px solid #555;
            border-radius: 4px;
            text-align: center;
            line-height: 26px;
            font-size: 18pt;
            font-weight: bold;
            background: #fff;
            color: #000;
        }
        .casilla-x {
            display: inline-block;
            width: 26px;
            height: 26px;
            border: 2px solid #000;
            border-radius: 4px;
            text-align: center;
            line-height: 26px;
            font-size: 18pt;
            font-weight: bold;
            background: #fff;
            color: #000;
        }
    </style>
</head>
<body>

<!-- CARA EXTERIOR -->
<table class="tabla-triptico">
    <tr>
        <td class="columna-triptico" style="padding-right: 25px;">
            <div style="font-weight: bold; font-size: 10.5pt; margin-bottom: 15px;">ARTICULO 15 Y 16</div>
            <div style="text-align: justify; font-size: 9.5pt; margin-bottom: 20px;">
                Gaceta oficial de la República Bolivariana de Venezuela N° 5428 de 2002 escala alfabética para la interpretación de los resultados de rendimiento estudiantil.
            </div>
            <table style="width: 100%; text-align: justify; font-size: 9.5pt; border-collapse: collapse;">
                <tr><td style="font-size: 16pt; font-weight: bold; vertical-align: top; width: 35px; padding-bottom: 15px;">A</td><td style="padding-bottom: 15px;">El alumno alcanzo todas las competencias y en algunos supero las expectativas para el grado.</td></tr>
                <tr><td style="font-size: 16pt; font-weight: bold; vertical-align: top; padding-bottom: 15px;">B</td><td style="padding-bottom: 15px;">El alumno alcanzo la mayoría de las competencias previstas para el grado.</td></tr>
                <tr><td style="font-size: 16pt; font-weight: bold; vertical-align: top; padding-bottom: 15px;">C</td><td style="padding-bottom: 15px;">El alumno alcanzo algunas competencias previstas para el grado.</td></tr>
                <tr><td style="font-size: 16pt; font-weight: bold; vertical-align: top; padding-bottom: 15px;">D</td><td style="padding-bottom: 15px;">El alumno alcanzo algunas competencias previstas para el grado, pero requiere de un proceso de nivelación.</td></tr>
                <tr><td style="font-size: 16pt; font-weight: bold; vertical-align: top; padding-bottom: 15px;">E</td><td style="padding-bottom: 15px;">El alumno no alcanzo las competencias mínimas requeridas para ser promovido al grado inmediato superior.</td></tr>
            </table>
            <div style="margin-top: 15px; text-align: center; font-size: 9.5pt;">
                "La enseñanza de las buenas costumbres<br>
                o hábitos sociales es tan esencial<br>
                como la instrucción."<br>
                <span style="font-weight: bold; margin-top: 5px; display: inline-block;">Pensamiento de Simón Bolívar</span>
            </div>
        </td>

        <td class="columna-triptico" style="padding: 0 20px;">
            <div style="text-align: justify; font-weight: bold; font-size: 10pt; margin-bottom: 30px;">
                DE ACUERDO AL RESULTADO FINAL DEL RENDIMIENTO ESTUDIANTIL EL ALUMNO SE ENCUENTRA EN SITUACIÓN DE:
            </div>
            <div style="text-align: center; margin-bottom: 40px;">
                <div style="display: inline-block; width: 45%; text-align: center;">
                    <div style="display: block; font-weight: bold; font-size: 10.5pt; margin-bottom: 5px;">PROMOVIDO</div>
                    <div style="display: inline-block; width: 26px; height: 26px; border: 2px solid #000; border-radius: 4px; text-align: center; line-height: 26px; font-size: 18pt; font-weight: bold; background: #fff; color: #000;">
                        <?php echo ($resultado_final == 'Promovido') ? 'X' : ''; ?>
                    </div>
                </div>
                <div style="display: inline-block; width: 45%; text-align: center;">
                    <div style="display: block; font-weight: bold; font-size: 10.5pt; margin-bottom: 5px;">APLAZADO</div>
                    <div style="display: inline-block; width: 26px; height: 26px; border: 2px solid #000; border-radius: 4px; text-align: center; line-height: 26px; font-size: 18pt; font-weight: bold; background: #fff; color: #000;">
                        <?php echo ($resultado_final == 'Aplazado') ? 'X' : ''; ?>
                    </div>
                </div>
            </div>
            <div style="text-align: center; margin-bottom: 1px; font-weight: bold; font-size: 11pt;">
                Al Grado: <span style="font-weight: normal; margin-left: 5px;"><?php echo $grado_formateado; ?></span>
            </div>
            <div style="text-align: center; margin-bottom: 140px; font-weight: bold; font-size: 11pt;">
                Con el Literal
                <div style="width: 45px; height: 45px; border: 2px solid #333; border-radius: 4px; margin: 10px auto 0 auto; text-align: center; line-height: 45px; font-size: 22pt; font-weight: bold; background: #f8f9fa;">
                    <?php echo $literal_final; ?>
                </div>
            </div>
            <div style="text-align: center; margin: 50px 0 60px 0; font-size: 11pt; font-weight: bold;">SELLO</div>
            <table style="width: 100%; text-align: center; font-size: 9.5pt; border-collapse: collapse; margin-top: 30px;">
                <tr>
                    <td style="width: 45%; border-top: 1px solid #000; padding-top: 5px;">Docente</td>
                    <td style="width: 10%;"></td>
                    <td style="width: 45%; border-top: 1px solid #000; padding-top: 5px;">Director(a)</td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-top: 45px;">
                        <div style="width: 70%; margin: 0 auto; border-top: 1px solid #000; padding-top: 5px;">
                            Vocera investigación y formación
                        </div>
                    </td>
                </tr>
            </table>
        </td>

        <td class="columna-triptico" style="padding-left: 25px;">
            <div class="header-portada">
                REPUBLICA BOLIVARIANA DE VENEZUELA<br>
                MINISTERIO DEL PODER POPULAR PARA LA EDUCACION<br>
                <span class="texto-negrita">E.B.N "JUAN PABLO PEREZ ALFONZO"</span><br>
                CODIGO DEA OD02912313<br>
                MARACAIBO ZULIA
            </div>
            <div class="logo-portada"><?php echo $logo_html; ?></div>
            <div style="text-align: center; margin-bottom: 50px; font-weight: bold; line-height: 1.1;">
                <span style="font-size: 14pt; display: block;">BOLETÍN INFORMATIVO</span>
                <span style="font-size: 14pt; display: block;">PRIMARIA</span>
            </div>
            <div style="text-align: center; font-size: 11pt; margin-bottom: 35px;">
                AÑO ESCOLAR <?php echo $ano_escolar; ?>
            </div>

            <!-- ===== SECCIÓN DE DATOS DEL ESTUDIANTE ===== -->
            <div style="font-size: 10.5pt; line-height: 2.0; text-align: left; padding: 0 10px;">
                <div><strong>Estudiante:</strong> <?php echo $estudiante; ?></div>
                <div>
                    <span style="display: inline-block; width: 50%;"><strong>CE o CI:</strong> <?php echo $ce; ?></span>
                    <span style="display: inline-block; width: 45%; white-space: nowrap;"><strong>Grado:</strong> <?php echo $grado_formateado; ?></span>
                </div>
                <div><strong>Docente:</strong> <?php echo $docente; ?></div>
                <div><strong>Representante:</strong> <?php echo $representante; ?></div>
            </div>

            <div style="text-align: center; margin-top: 40px; font-size: 9.5pt;">
                "Instruye al niño en su camino (Dios),<br>
                y ni aun de viejo se apartará de él."<br>
                <span style="display: inline-block; margin-top: 5px;">Proverbios 22:6</span>
            </div>
        </td>
    </tr>
</table>

<!-- CARA INTERIOR (LAPSOS) -->
<div class="page-break"></div>

<table class="tabla-triptico">
    <tr>
        <td class="columna-triptico">
            <div class="titulo-momento">PRIMER LAPSO</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $l1_proyecto; ?></div>
            <div class="etiqueta-area" style="text-align: center; margin-bottom: 5px;">ANÁLISIS CUALITATIVO</div>
            <div class="espacio-aprendizaje"><?php echo $l1_analisis; ?></div>
            <div class="etiqueta-area">SUGERENCIAS</div>
            <div class="espacio-sugerencia"><?php echo $l1_sugerencias; ?></div>
            <table class="firmas-momento">
                <tr>
                    <td style="text-align: left;">Director(a): <span class="linea-firma-inline" style="width: 60px;"></span></td>
                    <td style="text-align: left;">Docente: <span class="linea-firma-inline" style="width: 60px;"></span></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 60%; text-align: left;">Representante: <span class="linea-firma-inline" style="width: 70px;"></span></td>
                                <td style="width: 40%; text-align: left;">Fecha: <span class="linea-firma-inline" style="width: 40px;"></span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>

        <td class="columna-triptico">
            <div class="titulo-momento">SEGUNDO LAPSO</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $l2_proyecto; ?></div>
            <div class="etiqueta-area" style="text-align: center; margin-bottom: 5px;">ANÁLISIS CUALITATIVO</div>
            <div class="espacio-aprendizaje"><?php echo $l2_analisis; ?></div>
            <div class="etiqueta-area">SUGERENCIAS</div>
            <div class="espacio-sugerencia"><?php echo $l2_sugerencias; ?></div>
            <table class="firmas-momento">
                <tr>
                    <td style="text-align: left;">Director(a): <span class="linea-firma-inline" style="width: 60px;"></span></td>
                    <td style="text-align: left;">Docente: <span class="linea-firma-inline" style="width: 60px;"></span></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 60%; text-align: left;">Representante: <span class="linea-firma-inline" style="width: 70px;"></span></td>
                                <td style="width: 40%; text-align: left;">Fecha: <span class="linea-firma-inline" style="width: 40px;"></span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>

        <td class="columna-triptico">
            <div class="titulo-momento">TERCER LAPSO</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $l3_proyecto; ?></div>
            <div class="etiqueta-area" style="text-align: center; margin-bottom: 5px;">ANÁLISIS CUALITATIVO</div>
            <div class="espacio-aprendizaje"><?php echo $l3_analisis; ?></div>
            <div class="etiqueta-area">SUGERENCIAS</div>
            <div class="espacio-sugerencia"><?php echo $l3_sugerencias; ?></div>
            <table class="firmas-momento">
                <tr>
                    <td style="text-align: left;">Director(a): <span class="linea-firma-inline" style="width: 60px;"></span></td>
                    <td style="text-align: left;">Docente: <span class="linea-firma-inline" style="width: 60px;"></span></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 60%; text-align: left;">Representante: <span class="linea-firma-inline" style="width: 70px;"></span></td>
                                <td style="width: 40%; text-align: left;">Fecha: <span class="linea-firma-inline" style="width: 40px;"></span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
<?php
$html = ob_get_clean();

// ========== GUARDAR BOLETÍN EN BD (CORREGIDO: UPDATE si existe, INSERT si no) ==========
if ($estudiante_id > 0) {
    $tipo = 'primaria';
    $periodo_escolar = $_SESSION['ano_escolar'] ?? $periodo_escolar_actual;
    
    $check = $conexion->query("SHOW TABLES LIKE 'boletines'");
    if ($check && $check->num_rows > 0) {
        // Verificar si ya existe un boletín para este estudiante, periodo y tipo
        $stmt_check = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("iss", $estudiante_id, $periodo_escolar, $tipo);
            $stmt_check->execute();
            $existe = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();
            
            $obs = $_SESSION['observacion'] ?? '';
            $l1_proy = $_SESSION['l1_proyecto'] ?? '';
            $l1_form = $_SESSION['l1_analisis'] ?? '';
            $l1_sug = $_SESSION['l1_sugerencias'] ?? '';
            $l2_proy = $_SESSION['l2_proyecto'] ?? '';
            $l2_form = $_SESSION['l2_analisis'] ?? '';
            $l2_sug = $_SESSION['l2_sugerencias'] ?? '';
            $l3_proy = $_SESSION['l3_proyecto'] ?? '';
            $l3_form = $_SESSION['l3_analisis'] ?? '';
            $l3_sug = $_SESSION['l3_sugerencias'] ?? '';
            $res_final = $_SESSION['resultado_final'] ?? '';
            $lit_final = $_SESSION['literal_final'] ?? '';
            
            if ($existe) {
                // ACTUALIZAR el boletín existente
                $stmt_update = $conexion->prepare("UPDATE boletines SET 
                    observacion = ?, 
                    m1_proyecto = ?, m1_formacion = ?, m1_sugerencias = ?,
                    m2_proyecto = ?, m2_formacion = ?, m2_sugerencias = ?,
                    m3_proyecto = ?, m3_formacion = ?, m3_sugerencias = ?,
                    resultado_final = ?, literal_final = ?,
                    fecha_emision = NOW()
                    WHERE id = ?");
                if ($stmt_update) {
                    $stmt_update->bind_param("ssssssssssssi", 
                        $obs,
                        $l1_proy, $l1_form, $l1_sug,
                        $l2_proy, $l2_form, $l2_sug,
                        $l3_proy, $l3_form, $l3_sug,
                        $res_final, $lit_final,
                        $existe['id']
                    );
                    $stmt_update->execute();
                    $stmt_update->close();
                }
            } else {
                // INSERTAR nuevo boletín
                $stmt_insert = $conexion->prepare("INSERT INTO boletines 
                    (estudiante_id, periodo, tipo_boletin, observacion, 
                     m1_proyecto, m1_formacion, m1_sugerencias,
                     m2_proyecto, m2_formacion, m2_sugerencias,
                     m3_proyecto, m3_formacion, m3_sugerencias,
                     resultado_final, literal_final,
                     fecha_emision)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("issssssssssssss", 
                        $estudiante_id, $periodo_escolar, $tipo, $obs,
                        $l1_proy, $l1_form, $l1_sug,
                        $l2_proy, $l2_form, $l2_sug,
                        $l3_proy, $l3_form, $l3_sug,
                        $res_final, $lit_final
                    );
                    $stmt_insert->execute();
                    $stmt_insert->close();
                }
            }
        }
    }
}

$dompdf->setPaper('letter', 'landscape');
$dompdf->loadHtml($html);
$dompdf->render();

$nombre_archivo = "boletin_primaria_" . str_replace(' ', '_', $estudiante) . ".pdf";
$dompdf->stream($nombre_archivo, array('Attachment' => 0));
?>