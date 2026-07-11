<?php
session_start();

if (!isset($_SESSION['estudiante'])) {
    // header("Location: index.php");
    // exit();
}

require_once '../estadisticas/dompdf/autoload.inc.php';
require_once '../config/conexion.php';   // <-- CONEXIÓN A BD

use Dompdf\Dompdf;
use Dompdf\Options;

// Recuperar datos de la sesión
$estudiante = htmlspecialchars($_SESSION['estudiante'] ?? '');
$ce = htmlspecialchars($_SESSION['ce'] ?? '');
$grupo = htmlspecialchars($_SESSION['grupo'] ?? '');
$ano_escolar = htmlspecialchars($_SESSION['ano_escolar'] ?? '2025 / 2026');
$docente = htmlspecialchars($_SESSION['docente'] ?? '');
$representante = htmlspecialchars($_SESSION['representante'] ?? '');
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

// ================================================================
// LOGO - CONVERSIÓN A BASE64
// ================================================================
$logo_path = 'C:/xampp/htdocs/Servicio-comunitario/includes/image/logo1.png';
$logo_base64 = '';
if (file_exists($logo_path)) {
    $logo_data = file_get_contents($logo_path);
    $logo_base64 = 'data:image/png;base64,' . base64_encode($logo_data);
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
        /* CONFIGURACIÓN DE HOJA CARTA HORIZONTAL */
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

        /* ======== HOJA 1: CARA EXTERIOR ======== */
        
        /* COL 1: OBSERVACIÓN */
        .caja-observacion-fija {
            height: 220px; /* Espacio fijo que no se amplía */
            overflow: hidden;
            text-align: justify;
            margin-top: 5px;
        }
        .sello-recuadro {
            text-align: center;
            font-size: 12pt;
            height: 100px;
            line-height: 100px; /* Centra el texto verticalmente */
        }
        .firmas-observacion {
            margin-top: 200px; /* Empuja las firmas hacia abajo */
            width: 100%;
            text-align: center;
        }
        .linea-firma {
            border-top: 1px solid #000;
            width: 90%;
            margin: 0 auto;
            padding-top: 5px;
        }
        /* Nueva clase para líneas al lado del texto */
        .linea-firma-inline {
            display: inline-block;
            border-bottom: 1px solid #000; /* Línea negra inferior */
            height: 0px;
            vertical-align: bottom; /* Alinea la línea con la base del texto */
            margin-left: 5px; /* Espacio entre la palabra y la línea */
        }

        /* COL 2: CITAS */
        .citas-container {
            text-align: center;
        }
        .bloque-cita-arriba { margin-top: 20px; }
        .bloque-cita-medio { margin-top: 200px; } /* Empuja al centro */
        .bloque-cita-abajo { margin-top: 200px; } /* Empuja al fondo */
        .cita-texto { margin-bottom: 5px; font-style: italic; }
        .cita-autor { font-weight: bold; }

        /* COL 3: PORTADA */
        .header-portada {
            text-align: center;
            font-size: 10pt;
            line-height: 1.3;
        }
        /* ===== LOGO ===== */
        .logo-portada {
            width: 75px;
            height: auto;
            margin: 20px auto;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-portada img {
            width: 75px;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .logo-portada .logo-texto {
            font-weight: bold;
            font-size: 12pt;
        }
        .titulos-portada {
            text-align: center;
            margin-bottom: 10px;
        }
        .titulos-portada h1 { 
            font-size: 13pt; 
            margin-top: 30px; 
        }
        .titulos-portada h2 { 
            font-size: 11pt; 
            margin: 10px 0 0 0; 
            font-weight: normal; 
        }
        /* DATOS DEL ESTUDIANTE - SIN LÍNEAS (ESTILO PRIMARIA) */
        .datos-estudiante {
            margin-top: 150px;
            width: 100%;
            font-size: 10.5pt;
            border-collapse: collapse;
            margin-left: 50px;
        }
        .datos-estudiante td { 
            padding: 6px 0; 
        }
        .datos-estudiante .etiqueta {
            font-weight: bold;
            width: 35%;
        }
        
        /* ======== HOJA 2: CARA INTERIOR ======== */
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
        
        /* ALTURAS FIJAS ESTRICTAS PARA CADA ÁREA */
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
            margin-bottom: 25px; /* Separación con las firmas del fondo */
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
                    <div class="cita-texto">"Pocos conocen su utlidad"</div>
                    <div class="cita-autor">Frase de simon Rodriguez</div>
                </div>
                
                <div class="bloque-cita-abajo">
                    <div class="cita-texto">"La enseñanza de las buenas costumbres o habitos sociales es tan esencial como la instrucción"</div>
                    <div class="cita-autor">Pensamiento de simon bolivar</div>
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
            
            <!-- ===== LOGO ===== -->
            <div class="logo-portada">
                <?php if ($logo_base64): ?>
                    <img src="<?php echo $logo_base64; ?>" alt="Logo">
                <?php else: ?>
                    <span class="logo-texto">LOGO</span>
                <?php endif; ?>
            </div>
            
            <div class="titulos-portada">
                <h1>BOLETIN INFORMATIVO<br>INICIAL</h1>
                <h2>AÑO ESCOLAR <?php echo $ano_escolar; ?></h2>
            </div>
            
            <!-- ===== DATOS DEL ESTUDIANTE - SIN LÍNEAS ===== -->
            <table class="datos-estudiante">
                <tr>
                    <td class="etiqueta">Estudiante:</td>
                    <td><?php echo $estudiante; ?></td>
                </tr>
                <tr>
                    <td class="etiqueta">C.E:</td>
                    <td><?php echo $ce; ?></td>
                </tr>
                <tr>
                    <td class="etiqueta">Grupo:</td>
                    <td><?php echo $grupo; ?></td>
                </tr>
                <tr>
                    <td class="etiqueta">Docente:</td>
                    <td><?php echo $docente; ?></td>
                </tr>
                <tr>
                    <td class="etiqueta">Representante:</td>
                    <td><?php echo $representante; ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

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

$dompdf->setPaper('letter', 'landscape');
$dompdf->loadHtml($html);
$dompdf->render();

// ========== GUARDAR BOLETÍN EN TABLA boletines ==========
$estudiante_id = $_SESSION['estudiante_id'] ?? 0;
if (!$estudiante_id && isset($_SESSION['estudiante'])) {
    $nombre_est = $_SESSION['estudiante'];
    $ce_est = $_SESSION['ce'];
    $stmt_buscar = $conexion->prepare("SELECT id FROM estudiantes WHERE CONCAT(nombre, ' ', apellido) = ? AND cedula_escolar = ? LIMIT 1");
    $stmt_buscar->bind_param("ss", $nombre_est, $ce_est);
    $stmt_buscar->execute();
    $res_buscar = $stmt_buscar->get_result();
    if ($fila = $res_buscar->fetch_assoc()) {
        $estudiante_id = $fila['id'];
        $_SESSION['estudiante_id'] = $estudiante_id;
    }
    $stmt_buscar->close();
}

if ($estudiante_id) {
    $tipo = $_SESSION['tipo_boletin'] ?? 'inicial';
    $periodo_escolar = $_SESSION['ano_escolar'] ?? date('Y') . '-' . (date('Y')+1);
    
    // Eliminar boletín anterior del mismo estudiante y período
    $stmt_del = $conexion->prepare("DELETE FROM boletines WHERE estudiante_id = ? AND periodo = ?");
    $stmt_del->bind_param("is", $estudiante_id, $periodo_escolar);
    $stmt_del->execute();
    $stmt_del->close();
    
    // Asignar valores de sesión (con null si no existen)
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
    
    $tipos = 'i' . str_repeat('s', 15); // 1 entero + 15 strings
    $stmt_bol->bind_param($tipos, 
        $estudiante_id, $periodo_escolar, $tipo,
        $obs, $m1_proy, $m1_form, $m1_rel, $m1_sug,
        $m2_proy, $m2_form, $m2_rel, $m2_sug,
        $m3_proy, $m3_form, $m3_rel, $m3_sug
    );
    $stmt_bol->execute();
    $stmt_bol->close();
}

$dompdf->stream("boletin_{$estudiante}.pdf", array('Attachment' => 0));
?>