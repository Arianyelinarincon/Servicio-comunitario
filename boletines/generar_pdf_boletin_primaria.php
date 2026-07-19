<?php
session_start();

if (!isset($_SESSION['estudiante'])) {
    // header("Location: index.php");
    // exit();
}

require_once '../estadisticas/dompdf/autoload.inc.php';
require_once '../config/conexion.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Recuperar datos de la sesión y forzar MAYÚSCULAS
$estudiante = mb_strtoupper(htmlspecialchars($_SESSION['estudiante'] ?? ''), 'UTF-8');
$ce = mb_strtoupper(htmlspecialchars($_SESSION['ce'] ?? ''), 'UTF-8');
$grado = mb_strtoupper(htmlspecialchars($_SESSION['grado'] ?? ''), 'UTF-8');
$ano_escolar = mb_strtoupper(htmlspecialchars($_SESSION['ano_escolar'] ?? '2025/2026.'), 'UTF-8');
$docente = mb_strtoupper(htmlspecialchars($_SESSION['docente'] ?? ''), 'UTF-8');
$representante = mb_strtoupper(htmlspecialchars($_SESSION['representante'] ?? ''), 'UTF-8');
$observacion = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['observacion'] ?? ''), 'UTF-8'));

// === LAPSO 1 ===
$l1_proyecto = mb_strtoupper(htmlspecialchars($_SESSION['l1_proyecto'] ?? ''), 'UTF-8');
$l1_formacion = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['l1_formacion'] ?? ''), 'UTF-8'));
$l1_relacion = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['l1_relacion'] ?? ''), 'UTF-8'));
$l1_sugerencias = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['l1_sugerencias'] ?? ''), 'UTF-8'));

// === LAPSO 2 ===
$l2_proyecto = mb_strtoupper(htmlspecialchars($_SESSION['l2_proyecto'] ?? ''), 'UTF-8');
$l2_formacion = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['l2_formacion'] ?? ''), 'UTF-8'));
$l2_relacion = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['l2_relacion'] ?? ''), 'UTF-8'));
$l2_sugerencias = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['l2_sugerencias'] ?? ''), 'UTF-8'));

// === LAPSO 3 ===
$l3_proyecto = mb_strtoupper(htmlspecialchars($_SESSION['l3_proyecto'] ?? ''), 'UTF-8');
$l3_formacion = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['l3_formacion'] ?? ''), 'UTF-8'));
$l3_relacion = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['l3_relacion'] ?? ''), 'UTF-8'));
$l3_sugerencias = nl2br(mb_strtoupper(htmlspecialchars($_SESSION['l3_sugerencias'] ?? ''), 'UTF-8'));

// === RESULTADO FINAL ===
$resultado_final = htmlspecialchars($_SESSION['resultado_final'] ?? '');
$literal_final = mb_strtoupper(htmlspecialchars($_SESSION['literal_final'] ?? ''), 'UTF-8');

// ========== LOGO - RUTA ABSOLUTA ==========
$logo_path = 'C:/xampp/htdocs/Servicio-comunitario/includes/image/logo1.png';
$logo_html = '';

// Verificar si la extensión GD está cargada
if (extension_loaded('gd')) {
    if (file_exists($logo_path)) {
        try {
            $logo_data = file_get_contents($logo_path);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $logo_path);
            finfo_close($finfo);
            
            if ($mime_type && strpos($mime_type, 'image/') === 0) {
                $logo_base64 = 'data:' . $mime_type . ';base64,' . base64_encode($logo_data);
                $logo_html = '<img src="' . $logo_base64 . '" class="logo-img" alt="Logo">';
            } else {
                $logo_html = '<div style="width:75px; height:75px; border:2px solid #000; margin:0 auto; text-align:center; line-height:75px; font-size:8pt; font-weight:bold;">LOGO</div>';
            }
        } catch (Exception $e) {
            $logo_html = '<div style="width:75px; height:75px; border:2px solid #000; margin:0 auto; text-align:center; line-height:75px; font-size:8pt; font-weight:bold;">LOGO</div>';
        }
    } else {
        $logo_html = '<div style="width:75px; height:75px; border:2px solid #000; margin:0 auto; text-align:center; line-height:75px; font-size:8pt; font-weight:bold;">LOGO</div>';
    }
} else {
    $logo_html = '<div style="width:75px; height:75px; border:2px solid #000; margin:0 auto; text-align:center; line-height:75px; font-size:8pt; font-weight:bold;">LOGO</div>';
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

        .logo-img {
            width: 75px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .casilla-container {
            display: inline-block;
            text-align: center;
            width: 45%;
        }
        .casilla-label {
            display: block;
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 5px;
        }
        .casilla {
            display: inline-block;
            width: 26px;
            height: 26px;
            border: 2px solid #555;
            border-radius: 4px;
            margin: 0 auto;
            text-align: center;
            line-height: 26px;
            font-size: 18pt;
            font-weight: bold;
            background: #fff;
            color: #333;
            transition: all 0.3s ease;
        }
       .casilla-x {
            display: inline-block;
            width: 26px;
            height: 26px;
            border: 2px solid #000;
            border-radius: 4px;
            margin: 0 auto;
            text-align: center;
            line-height: 26px;
            font-size: 18pt;
            font-weight: bold;
            background: #fff;
            color: #000;
        }
        .casilla-x .x-symbol {
            display: inline-block;
            font-family: 'Arial', sans-serif;
            font-weight: bold;
            font-size: 20pt;
            line-height: 26px;
            color: #000;
        }
        .casilla .x-symbol {
            display: inline-block;
            font-family: 'Arial', sans-serif;
            font-weight: bold;
            font-size: 20pt;
            line-height: 26px;
            color: #aaa;
        }
    </style>
</head>
<body>

<table class="tabla-triptico">
    <tr>
        <td class="columna-triptico" style="padding-right: 25px;">
            <div style="font-weight: bold; font-size: 10.5pt; margin-bottom: 15px;">ARTICULO 15 Y 16</div>
            <div style="text-align: justify; font-size: 9.5pt; margin-bottom: 20px;">
                Gaceta oficial de la República Bolivariana de Venezuela N° 5428 de 2002 escala alfabética para la interpretación de los resultados de rendimiento estudiantil.
            </div>
            
            <table style="width: 100%; text-align: justify; font-size: 9.5pt; border-collapse: collapse;">
                <tr>
                    <td style="font-size: 16pt; font-weight: bold; vertical-align: top; width: 35px; padding-bottom: 15px;">A</td>
                    <td style="padding-bottom: 15px;">El alumno alcanzo todas las competencias y en algunos supero las expectativas para el grado.</td>
                </tr>
                <tr>
                    <td style="font-size: 16pt; font-weight: bold; vertical-align: top; padding-bottom: 15px;">B</td>
                    <td style="padding-bottom: 15px;">El alumno alcanzo la mayoría de las competencias previstas para el grado.</td>
                </tr>
                <tr>
                    <td style="font-size: 16pt; font-weight: bold; vertical-align: top; padding-bottom: 15px;">C</td>
                    <td style="padding-bottom: 15px;">El alumno alcanzo algunas competencias previstas para el grado.</td>
                </tr>
                <tr>
                    <td style="font-size: 16pt; font-weight: bold; vertical-align: top; padding-bottom: 15px;">D</td>
                    <td style="padding-bottom: 15px;">El alumno alcanzo algunas competencias previstas para el grado, pero requiere de un proceso de nivelación.</td>
                </tr>
                <tr>
                    <td style="font-size: 16pt; font-weight: bold; vertical-align: top; padding-bottom: 15px;">E</td>
                    <td style="padding-bottom: 15px;">El alumno no alcanzo las competencias mínimas requeridas para ser promovido al grado inmediato superior.</td>
                </tr>
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
                <div class="casilla-container" style="display: inline-block; width: 45%; text-align: center;">
                    <div class="casilla-label">PROMOVIDO</div>
                    <div class="<?php echo ($resultado_final == 'Promovido') ? 'casilla-x' : 'casilla'; ?>">
                        <span class="x-symbol"><?php echo ($resultado_final == 'Promovido') ? 'X' : ''; ?></span>
                    </div>
                </div>
                <div class="casilla-container" style="display: inline-block; width: 45%; text-align: center;">
                    <div class="casilla-label">APLAZADO</div>
                    <div class="<?php echo ($resultado_final == 'Aplazado') ? 'casilla-x' : 'casilla'; ?>">
                        <span class="x-symbol"><?php echo ($resultado_final == 'Aplazado') ? 'X' : ''; ?></span>
                    </div>
                </div>
            </div>
            <div style="text-align: center; margin-bottom: 1px; font-weight: bold; font-size: 11pt;">
                Al Grado: <span style="font-weight: normal; margin-left: 5px;"><?php echo $grado; ?></span>
            </div>

            <div style="text-align: center; margin-bottom: 140px; font-weight: bold; font-size: 11pt;">
                Con el Literal
                <div style="width: 45px; height: 45px; border: 2px solid #333; border-radius: 4px; margin: 10px auto 0 auto; text-align: center; line-height: 45px; font-size: 22pt; font-weight: bold; background: #f8f9fa;">
                    <?php echo $literal_final; ?>
                </div>
            </div>

            <div style="text-align: center; margin: 50px 0 60px 0; font-size: 11pt; font-weight: bold;">
                SELLO
            </div>

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

            <!-- ===== LOGO ===== -->
            <div style="text-align: center; margin: 25px 0;">
                <?php echo $logo_html; ?>
            </div>

            <div style="text-align: center; margin-bottom: 50px; font-weight: bold; line-height: 1.1;">
                <span style="font-size: 14pt; display: block;">BOLETÍN INFORMATIVO</span>
                <span style="font-size: 14pt; display: block;">PRIMARIA</span>
            </div>
            
            <div style="text-align: center; font-size: 11pt; margin-bottom: 35px;">
                AÑO ESCOLAR <?php echo $ano_escolar; ?>
            </div>

            <div style="font-size: 10.5pt; line-height: 2.2; text-align: left; padding: 0 10px;">
                <div><strong>Estudiante:</strong> <?php echo $estudiante; ?></div>
                <div>
                    <span style="display: inline-block; width: 55%;"><strong>CE o CI:</strong> <?php echo $ce; ?></span>
                    <span style="display: inline-block; width: 40%;"><strong>Grado:</strong> <?php echo $grado; ?></span>
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

<div class="page-break"></div>

<table class="tabla-triptico">
    <tr>
        <td class="columna-triptico">
            <div class="titulo-momento">PRIMER LAPSO</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $l1_proyecto; ?></div>
            <div class="etiqueta-area" style="text-align: center; margin-bottom: 5px;">ANÁLISIS CUALITATIVO</div>
            <div class="espacio-aprendizaje">
                <?php echo $l1_formacion; ?>
                <?php if($l1_relacion) echo '<br><br>' . $l1_relacion; ?>
            </div>
            <div class="etiqueta-area">SUGERENCIAS</div>
            <div class="espacio-sugerencia"><?php echo $l1_sugerencias; ?></div>
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
            <div class="titulo-momento">SEGUNDO LAPSO</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $l2_proyecto; ?></div>
            <div class="etiqueta-area" style="text-align: center; margin-bottom: 5px;">ANÁLISIS CUALITATIVO</div>
            <div class="espacio-aprendizaje">
                <?php echo $l2_formacion; ?>
                <?php if($l2_relacion) echo '<br><br>' . $l2_relacion; ?>
            </div>
            <div class="etiqueta-area">SUGERENCIAS</div>
            <div class="espacio-sugerencia"><?php echo $l2_sugerencias; ?></div>
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
            <div class="titulo-momento">TERCER LAPSO</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $l3_proyecto; ?></div>
            <div class="etiqueta-area" style="text-align: center; margin-bottom: 5px;">ANÁLISIS CUALITATIVO</div>
            <div class="espacio-aprendizaje">
                <?php echo $l3_formacion; ?>
                <?php if($l3_relacion) echo '<br><br>' . $l3_relacion; ?>
            </div>
            <div class="etiqueta-area">SUGERENCIAS</div>
            <div class="espacio-sugerencia"><?php echo $l3_sugerencias; ?></div>
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

$dompdf->setPaper('letter', 'landscape');
$dompdf->loadHtml($html);
$dompdf->render();

// ========== GUARDAR BOLETÍN EN TABLA boletines ==========
$estudiante_id = $_SESSION['estudiante_id'] ?? 0;
if (!$estudiante_id && isset($_SESSION['estudiante'])) {
    $nombre_est = $_SESSION['estudiante'];
    $ce_est = $_SESSION['ce'];
    $stmt_buscar = $conexion->prepare("SELECT id FROM estudiantes WHERE CONCAT(nombre, ' ', apellido) = ? AND cedula_escolar = ? LIMIT 1");
    if ($stmt_buscar) {
        $stmt_buscar->bind_param("ss", $nombre_est, $ce_est);
        $stmt_buscar->execute();
        $res_buscar = $stmt_buscar->get_result();
        if ($fila = $res_buscar->fetch_assoc()) {
            $estudiante_id = $fila['id'];
            $_SESSION['estudiante_id'] = $estudiante_id;
        }
        $stmt_buscar->close();
    }
}

if ($estudiante_id) {
    $tipo = 'primaria';
    $periodo_escolar = $_SESSION['ano_escolar'] ?? date('Y') . '-' . (date('Y') + 1);
    
    $stmt_del = $conexion->prepare("DELETE FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
    if ($stmt_del) {
        $stmt_del->bind_param("iss", $estudiante_id, $periodo_escolar, $tipo);
        $stmt_del->execute();
        $stmt_del->close();
    }
    
    $obs = $_SESSION['observacion'] ?? '';
    
    $l1_proyecto_db = $_SESSION['l1_proyecto'] ?? '';
    $l1_formacion_db = $_SESSION['l1_formacion'] ?? '';
    $l1_relacion_db = $_SESSION['l1_relacion'] ?? '';
    $l1_sugerencias_db = $_SESSION['l1_sugerencias'] ?? '';
    
    $l2_proyecto_db = $_SESSION['l2_proyecto'] ?? '';
    $l2_formacion_db = $_SESSION['l2_formacion'] ?? '';
    $l2_relacion_db = $_SESSION['l2_relacion'] ?? '';
    $l2_sugerencias_db = $_SESSION['l2_sugerencias'] ?? '';
    
    $l3_proyecto_db = $_SESSION['l3_proyecto'] ?? '';
    $l3_formacion_db = $_SESSION['l3_formacion'] ?? '';
    $l3_relacion_db = $_SESSION['l3_relacion'] ?? '';
    $l3_sugerencias_db = $_SESSION['l3_sugerencias'] ?? '';
    
    $resultado_final_db = $_SESSION['resultado_final'] ?? '';
    $literal_final_db = $_SESSION['literal_final'] ?? '';
    
    $stmt_bol = $conexion->prepare("INSERT INTO boletines 
        (estudiante_id, periodo, tipo_boletin, observacion, 
         m1_proyecto, m1_formacion, m1_relacion, m1_sugerencias,
         m2_proyecto, m2_formacion, m2_relacion, m2_sugerencias,
         m3_proyecto, m3_formacion, m3_relacion, m3_sugerencias,
         resultado_final, literal_final)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
    if ($stmt_bol) {
        $tipos = 'i' . str_repeat('s', 17);
        $stmt_bol->bind_param($tipos, 
            $estudiante_id, $periodo_escolar, $tipo, $obs,
            $l1_proyecto_db, $l1_formacion_db, $l1_relacion_db, $l1_sugerencias_db,
            $l2_proyecto_db, $l2_formacion_db, $l2_relacion_db, $l2_sugerencias_db,
            $l3_proyecto_db, $l3_formacion_db, $l3_relacion_db, $l3_sugerencias_db,
            $resultado_final_db, $literal_final_db
        );
        $stmt_bol->execute();
        $stmt_bol->close();
    }
}

$nombre_archivo = "boletin_primaria_" . str_replace(' ', '_', $estudiante) . ".pdf";
$dompdf->stream($nombre_archivo, array('Attachment' => 0));
?>