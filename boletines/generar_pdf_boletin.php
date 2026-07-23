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

// Si no tenemos estudiante_id, intentar obtenerlo
if (!$estudiante_id && isset($_SESSION['estudiante'])) {
    $nombre_est = $_SESSION['estudiante'];
    $ce_est = $_SESSION['ce'];
    $stmt_buscar = $conexion->prepare("SELECT id FROM estudiantes WHERE CONCAT(nombre, ' ', apellido) = ? AND cedula_escolar = ? LIMIT 1");
    if ($stmt_buscar) {
        $stmt_buscar->bind_param("ss", $nombre_est, $ce_est);
        $stmt_buscar->execute();
        $res = $stmt_buscar->get_result();
        if ($fila = $res->fetch_assoc()) {
            $estudiante_id = $fila['id'];
            $_SESSION['estudiante_id'] = $estudiante_id;
        }
        $stmt_buscar->close();
    }
}

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

// ===== FORMATO DEL GRUPO CON SECCIÓN ENTRE COMILLAS =====
$grupo_formateado = $grado_legible . ' "' . $seccion . '"';

// ========== VARIABLES DE CONTENIDO ==========
$estudiante = htmlspecialchars($estudiante_nombre);
$ce = htmlspecialchars($ce);
$ano_escolar = htmlspecialchars($ano_escolar);
$docente = htmlspecialchars($docente);
$representante = htmlspecialchars($representante);
$observacion = nl2br(htmlspecialchars($_SESSION['observacion'] ?? ''));

$m1_proy = htmlspecialchars($_SESSION['m1_proyecto'] ?? '');
$m1_form = nl2br(htmlspecialchars($_SESSION['m1_formacion'] ?? ''));
$m1_rel = nl2br(htmlspecialchars($_SESSION['m1_relacion'] ?? ''));
$m1_sug = nl2br(htmlspecialchars($_SESSION['m1_sugerencias'] ?? ''));

$m2_proy = htmlspecialchars($_SESSION['m2_proyecto'] ?? '');
$m2_form = nl2br(htmlspecialchars($_SESSION['m2_formacion'] ?? ''));
$m2_rel = nl2br(htmlspecialchars($_SESSION['m2_relacion'] ?? ''));
$m2_sug = nl2br(htmlspecialchars($_SESSION['m2_sugerencias'] ?? ''));

$m3_proy = htmlspecialchars($_SESSION['m3_proyecto'] ?? '');
$m3_form = nl2br(htmlspecialchars($_SESSION['m3_formacion'] ?? ''));
$m3_rel = nl2br(htmlspecialchars($_SESSION['m3_relacion'] ?? ''));
$m3_sug = nl2br(htmlspecialchars($_SESSION['m3_sugerencias'] ?? ''));

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
    <title>Boletín Informativo Inicial</title>
    <style>
        @page {
            size: 279.4mm 215.9mm; 
            margin: 10mm; 
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt; 
            line-height: 1.2;
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
        .texto-negrita { font-weight: bold; }
        .caja-observacion-fija {
            height: 220px;
            overflow: hidden;
            text-align: justify;
            margin-top: 5px;
        }
        .sello-recuadro {
            text-align: center;
            font-size: 12pt;
            height: 100px;
            line-height: 100px;
        }
        .firmas-observacion {
            margin-top: 200px;
            width: 100%;
            text-align: center;
        }
        .linea-firma {
            border-top: 1px solid #000;
            width: 90%;
            margin: 0 auto;
            padding-top: 5px;
        }
        .linea-firma-inline {
            display: inline-block;
            border-bottom: 1px solid #000;
            height: 0px;
            vertical-align: bottom;
            margin-left: 5px;
        }
        .citas-container { text-align: center; }
        .bloque-cita-arriba { margin-top: 20px; }
        .bloque-cita-medio { margin-top: 200px; }
        .bloque-cita-abajo { margin-top: 200px; }
        .cita-texto { margin-bottom: 5px; font-style: italic; }
        .cita-autor { font-weight: bold; }
        .header-portada {
            text-align: center;
            font-size: 10pt;
            line-height: 1.3;
        }
        .logo-portada {
            text-align: center;
            margin: 20px auto;
        }
        .titulos-portada {
            text-align: center;
            margin-bottom: 10px;
        }
        .titulos-portada h1 { font-size: 13pt; margin-top: 30px; }
        .titulos-portada h2 { font-size: 11pt; margin: 10px 0 0 0; font-weight: normal; }
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
            height: 160px;
            border-bottom: 1px solid #000000;
            overflow: hidden;
            display: block;
            margin-bottom: 10px;
        }
        .espacio-sugerencia {
            height: 70px;
            overflow: hidden;
            display: block;
            margin-bottom: 25px;
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
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

<!-- PRIMERA PÁGINA (CARA EXTERIOR) -->
<table class="tabla-triptico">
    <tr>
        <td class="columna-triptico">
            <div class="texto-negrita">Observacion:</div>
            <div class="caja-observacion-fija">
                <?php echo $observacion; ?>
            </div>
            <div class="sello-recuadro">SELLO</div>
            <table class="firmas-observacion">
                <tr>
                    <td style="width: 50%;">
                        <div class="linea-firma"></div>
                        Docente
                    </td>
                    <td style="width: 50%;">
                        <div class="linea-firma"></div>
                        Director(a)
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 50px;">
                        <div class="linea-firma" style="width: 60%;"></div>
                        Vocera de investigacion y formacion
                    </td>
                </tr>
            </table>
        </td>

        <td class="columna-triptico">
            <div class="citas-container">
                <div class="bloque-cita-arriba">
                    <div class="cita-texto">Instruye al niño en su camino (Dios).<br>Y ni aun de viejo se apartara de el.</div>
                    <div class="cita-autor">Proverbios 22:6</div>
                </div>
                <div class="bloque-cita-medio">
                    <div class="cita-texto">"Pocos conocen su utilidad"</div>
                    <div class="cita-autor">Frase de Simón Rodríguez</div>
                </div>
                <div class="bloque-cita-abajo">
                    <div class="cita-texto">"La enseñanza de las buenas costumbres o hábitos sociales es tan esencial como la instrucción"</div>
                    <div class="cita-autor">Pensamiento de Simón Bolívar</div>
                </div>
            </div>
        </td>

        <td class="columna-triptico">
            <div class="header-portada">
                REPUBLICA BOLIVARIANA DE VENEZUELA<br>
                MINISTERIO DEL PODER POPULAR PARA LA EDUCACION<br>
                <span class="texto-negrita">E.B.N "JUAN PABLO PEREZ ALFONZO"</span><br>
                CODIGO DEA OD02912313<br>
                MARACAIBO ZULIA
            </div>
            
            <div class="logo-portada">
                <?php echo $logo_html; ?>
            </div>
            
            <div class="titulos-portada">
                <h1>BOLETIN INFORMATIVO<br>INICIAL</h1>
                <h2>AÑO ESCOLAR <?php echo $ano_escolar; ?></h2>
            </div>

            <!-- ===== SECCIÓN MODIFICADA: DATOS DEL ESTUDIANTE SIN LÍNEAS Y CON GRUPO FORMATEADO ===== -->
            

<!-- ===== SECCIÓN MODIFICADA: DATOS DEL ESTUDIANTE SIN LÍNEAS Y CON GRUPO EN UNA SOLA LÍNEA ===== -->
<div style="font-size: 10.5pt; line-height: 2.0; text-align: left; padding: 0 10px; margin-top: 140px;">
    <div><strong>Estudiante:</strong> <?php echo $estudiante; ?></div>
    <div>
        <span style="display: inline-block; width: 50%;"><strong>C.E:</strong> <?php echo $ce; ?></span>
        <span style="display: inline-block; width: 45%; white-space: nowrap;"><strong>Grupo:</strong> <?php echo $grupo_formateado; ?></span>
    </div>
    <div><strong>Docente:</strong> <?php echo $docente; ?></div>
    <div><strong>Representante:</strong> <?php echo $representante; ?></div>
</div>
        </td>
    </tr>
</table>

<!-- SEGUNDA PÁGINA (CARA INTERIOR) -->
<div class="page-break"></div>

<table class="tabla-triptico">
    <tr>
        <td class="columna-triptico">
            <div class="titulo-momento">PRIMER MOMENTO DE EVALUACION</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $m1_proy; ?></div>
            <div class="etiqueta-area">AREAS DE APRENDIZAJE</div>
            <div style="margin-top: 9px; font-weight: bold; font-size: 10pt;">Formacion personal, social y comunicación</div>
            <div class="espacio-aprendizaje"><?php echo $m1_form; ?></div>
            <div style="margin-top: 9px; font-weight: bold; font-size: 10pt;">Relacion entre los componentes del ambiente</div>
            <div class="espacio-aprendizaje"><?php echo $m1_rel; ?></div>
            <div class="etiqueta-area">SUGERENCIAS</div>
            <div class="espacio-sugerencia"><?php echo $m1_sug; ?></div>
            <table class="firmas-momento">
                <tr>
                    <td style="text-align: left;">
                        Director(a): <span class="linea-firma-inline" style="width: 60px;"></span>
                    </td>
                    <td style="text-align: left;">
                        Docente: <span class="linea-firma-inline" style="width: 60px;"></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 60%; text-align: left;">
                                    Representante: <span class="linea-firma-inline" style="width: 70px;"></span>
                                </td>
                                <td style="width: 40%; text-align: left;">
                                    Fecha: <span class="linea-firma-inline" style="width: 40px;"></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>

        <td class="columna-triptico">
            <div class="titulo-momento">SEGUNDO MOMENTO DE EVALUACION</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $m2_proy; ?></div>
            <div class="etiqueta-area">AREAS DE APRENDIZAJE</div>
            <div style="margin-top: 9px; font-weight: bold; font-size: 10pt;">Formacion personal, social y comunicación</div>
            <div class="espacio-aprendizaje"><?php echo $m2_form; ?></div>
            <div style="margin-top: 9px; font-weight: bold; font-size: 10pt;">Relacion entre los componentes del ambiente</div>
            <div class="espacio-aprendizaje"><?php echo $m2_rel; ?></div>
            <div class="etiqueta-area">SUGERENCIAS</div>
            <div class="espacio-sugerencia"><?php echo $m2_sug; ?></div>
            <table class="firmas-momento">
                <tr>
                    <td style="text-align: left;">
                        Director(a): <span class="linea-firma-inline" style="width: 60px;"></span>
                    </td>
                    <td style="text-align: left;">
                        Docente: <span class="linea-firma-inline" style="width: 60px;"></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 60%; text-align: left;">
                                    Representante: <span class="linea-firma-inline" style="width: 70px;"></span>
                                </td>
                                <td style="width: 40%; text-align: left;">
                                    Fecha: <span class="linea-firma-inline" style="width: 40px;"></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>

        <td class="columna-triptico">
            <div class="titulo-momento">TERCER MOMENTO DE EVALUACION</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $m3_proy; ?></div>
            <div class="etiqueta-area">AREAS DE APRENDIZAJE</div>
            <div style="margin-top: 9px; font-weight: bold; font-size: 10pt;">Formacion personal, social y comunicación</div>
            <div class="espacio-aprendizaje"><?php echo $m3_form; ?></div>
            <div style="margin-top: 9px; font-weight: bold; font-size: 10pt;">Relacion entre los componentes del ambiente</div>
            <div class="espacio-aprendizaje"><?php echo $m3_rel; ?></div>
            <div class="etiqueta-area">SUGERENCIAS</div>
            <div class="espacio-sugerencia"><?php echo $m3_sug; ?></div>
            <table class="firmas-momento">
                <tr>
                    <td style="text-align: left;">
                        Director(a): <span class="linea-firma-inline" style="width: 60px;"></span>
                    </td>
                    <td style="text-align: left;">
                        Docente: <span class="linea-firma-inline" style="width: 60px;"></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 15px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 60%; text-align: left;">
                                    Representante: <span class="linea-firma-inline" style="width: 70px;"></span>
                                </td>
                                <td style="width: 40%; text-align: left;">
                                    Fecha: <span class="linea-firma-inline" style="width: 40px;"></span>
                                </td>
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

// ========== GUARDAR BOLETÍN EN BD ==========
if ($estudiante_id > 0) {
    $tipo = $_SESSION['tipo_boletin'] ?? 'inicial';
    $periodo_escolar = $_SESSION['ano_escolar'] ?? $periodo_escolar_actual;
    
    $check = $conexion->query("SHOW TABLES LIKE 'boletines'");
    if ($check && $check->num_rows > 0) {
        $stmt_del = $conexion->prepare("DELETE FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        if ($stmt_del) {
            $stmt_del->bind_param("iss", $estudiante_id, $periodo_escolar, $tipo);
            $stmt_del->execute();
            $stmt_del->close();
        }
        
        $obs = $_SESSION['observacion'] ?? '';
        $m1_proy = $_SESSION['m1_proyecto'] ?? '';
        $m1_form = $_SESSION['m1_formacion'] ?? '';
        $m1_rel = $_SESSION['m1_relacion'] ?? '';
        $m1_sug = $_SESSION['m1_sugerencias'] ?? '';
        $m2_proy = $_SESSION['m2_proyecto'] ?? '';
        $m2_form = $_SESSION['m2_formacion'] ?? '';
        $m2_rel = $_SESSION['m2_relacion'] ?? '';
        $m2_sug = $_SESSION['m2_sugerencias'] ?? '';
        $m3_proy = $_SESSION['m3_proyecto'] ?? '';
        $m3_form = $_SESSION['m3_formacion'] ?? '';
        $m3_rel = $_SESSION['m3_relacion'] ?? '';
        $m3_sug = $_SESSION['m3_sugerencias'] ?? '';
        
        $stmt_bol = $conexion->prepare("INSERT INTO boletines 
            (estudiante_id, periodo, tipo_boletin, observacion, 
             m1_proyecto, m1_formacion, m1_relacion, m1_sugerencias,
             m2_proyecto, m2_formacion, m2_relacion, m2_sugerencias,
             m3_proyecto, m3_formacion, m3_relacion, m3_sugerencias)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_bol) {
            $tipos = 'i' . str_repeat('s', 15);
            $stmt_bol->bind_param($tipos, 
                $estudiante_id, $periodo_escolar, $tipo, $obs,
                $m1_proy, $m1_form, $m1_rel, $m1_sug,
                $m2_proy, $m2_form, $m2_rel, $m2_sug,
                $m3_proy, $m3_form, $m3_rel, $m3_sug
            );
            $stmt_bol->execute();
            $stmt_bol->close();
        }
    }
}

$dompdf->setPaper('letter', 'landscape');
$dompdf->loadHtml($html);
$dompdf->render();

$nombre_archivo = "boletin_inicial_" . str_replace(' ', '_', $estudiante) . ".pdf";
$dompdf->stream($nombre_archivo, array('Attachment' => 0));
?>