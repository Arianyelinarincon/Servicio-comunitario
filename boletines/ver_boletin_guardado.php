<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("ID de boletín no válido.");
}

// Obtener datos del boletín y del estudiante
$stmt = $conexion->prepare("
    SELECT b.*, 
           CONCAT(e.nombre, ' ', e.apellido) AS nombre_estudiante,
           e.cedula_escolar, e.sala,
           r.nombre_completo AS representante
    FROM boletines b
    JOIN estudiantes e ON b.estudiante_id = e.id
    LEFT JOIN representantes r ON e.representante_id = r.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$boletin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$boletin) {
    die("Boletín no encontrado.");
}

$tipo = $boletin['tipo_boletin'] ?? 'inicial';

// ========== VARIABLES COMUNES ==========
$estudiante = htmlspecialchars($boletin['nombre_estudiante']);
$ce = htmlspecialchars($boletin['cedula_escolar']);
$grupo = htmlspecialchars($boletin['sala']);
$ano_escolar = htmlspecialchars($boletin['periodo']);
$docente = ''; // No se guarda en boletines
$representante = htmlspecialchars($boletin['representante'] ?? 'No registrado');
$observacion = nl2br(htmlspecialchars($boletin['observacion'] ?? ''));

// ========== LOGO - IGUAL QUE EN LOS GENERADORES ==========
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

// ========== VARIABLES SEGÚN TIPO ==========
if ($tipo === 'inicial') {
    // Variables para inicial
    $m1_proy = htmlspecialchars($boletin['m1_proyecto'] ?? '');
    $m1_form = nl2br(htmlspecialchars($boletin['m1_formacion'] ?? ''));
    $m1_rel = nl2br(htmlspecialchars($boletin['m1_relacion'] ?? ''));
    $m1_sug = nl2br(htmlspecialchars($boletin['m1_sugerencias'] ?? ''));
    $m2_proy = htmlspecialchars($boletin['m2_proyecto'] ?? '');
    $m2_form = nl2br(htmlspecialchars($boletin['m2_formacion'] ?? ''));
    $m2_rel = nl2br(htmlspecialchars($boletin['m2_relacion'] ?? ''));
    $m2_sug = nl2br(htmlspecialchars($boletin['m2_sugerencias'] ?? ''));
    $m3_proy = htmlspecialchars($boletin['m3_proyecto'] ?? '');
    $m3_form = nl2br(htmlspecialchars($boletin['m3_formacion'] ?? ''));
    $m3_rel = nl2br(htmlspecialchars($boletin['m3_relacion'] ?? ''));
    $m3_sug = nl2br(htmlspecialchars($boletin['m3_sugerencias'] ?? ''));
    
    // Variables para primaria (se dejan vacías)
    $l1_proyecto = $l1_analisis = $l1_sugerencias = '';
    $l2_proyecto = $l2_analisis = $l2_sugerencias = '';
    $l3_proyecto = $l3_analisis = $l3_sugerencias = '';
    $resultado_final = $literal_final = '';
    
} else {
    // Variables para primaria
    $l1_proyecto = htmlspecialchars($boletin['m1_proyecto'] ?? '');
    $l1_analisis = nl2br(htmlspecialchars($boletin['m1_formacion'] ?? ''));
    $l1_sugerencias = nl2br(htmlspecialchars($boletin['m1_sugerencias'] ?? ''));
    
    $l2_proyecto = htmlspecialchars($boletin['m2_proyecto'] ?? '');
    $l2_analisis = nl2br(htmlspecialchars($boletin['m2_formacion'] ?? ''));
    $l2_sugerencias = nl2br(htmlspecialchars($boletin['m2_sugerencias'] ?? ''));
    
    $l3_proyecto = htmlspecialchars($boletin['m3_proyecto'] ?? '');
    $l3_analisis = nl2br(htmlspecialchars($boletin['m3_formacion'] ?? ''));
    $l3_sugerencias = nl2br(htmlspecialchars($boletin['m3_sugerencias'] ?? ''));
    
    $resultado_final = htmlspecialchars($boletin['resultado_final'] ?? '');
    $literal_final = htmlspecialchars($boletin['literal_final'] ?? '');
    
    // Variables para inicial (se dejan vacías)
    $m1_proy = $m1_form = $m1_rel = $m1_sug = '';
    $m2_proy = $m2_form = $m2_rel = $m2_sug = '';
    $m3_proy = $m3_form = $m3_rel = $m3_sug = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boletín Informativo <?php echo ($tipo === 'inicial') ? 'Inicial' : 'Primaria'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ===== CONFIGURACIÓN DE PÁGINA ===== */
        @page {
            size: 279.4mm 215.9mm; 
            margin: 10mm; 
        }
        
        /* ===== ELIMINAR ENCABEZADO Y PIE DE PÁGINA DEL NAVEGADOR ===== */
        @page {
            margin-top: 0.5cm;
            margin-bottom: 0.5cm;
            margin-left: 0.5cm;
            margin-right: 0.5cm;
        }
        
        /* ===== OCULTAR URL, FECHA Y HORA AL IMPRIMIR ===== */
        @media print {
            @page {
                margin: 0.5cm;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
            }
            .botones-control {
                display: none !important;
            }
            .tabla-triptico {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt; 
            line-height: 2;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        
        /* ===== ESTILOS COMUNES PARA AMBOS TIPOS ===== */
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
        .linea-inferior { border-bottom: 1px solid #000; }
        .texto-negrita { font-weight: bold; }
        .page-break { page-break-before: always; }
        
        /* ===== ESTILOS PARA INICIAL (idénticos a generar_pdf_boletin.php) ===== */
        <?php if ($tipo === 'inicial'): ?>
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
        .datos-estudiante {
            margin-top: 140px;
            width: 100%;
            font-size: 11pt;
            border-collapse: collapse;
        }
        .datos-estudiante td { padding: 8px 0; }
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
        <?php else: ?>
        /* ===== ESTILOS PARA PRIMARIA (idénticos a generar_pdf_boletin_primaria.php) ===== */
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
        .logo-portada {
            text-align: center;
            margin: 20px auto;
        }
        .titulos-portada {
            text-align: center;
            margin-bottom: 10px;
        }
        .datos-estudiante {
            margin-top: 140px;
            width: 100%;
            font-size: 11pt;
            border-collapse: collapse;
        }
        .datos-estudiante td { padding: 8px 0; }
        <?php endif; ?>
        
        /* ===== ESTILO PARA LOS BOTONES EN PANTALLA ===== */
        .botones-control {
            margin: 20px 0 30px 0;
        }
        
        /* ===== CORRECCIÓN DE TILDES ===== */
        .cita-autor, .cita-texto, .texto-negrita, .header-portada, .titulos-portada {
            text-transform: none;
        }
    </style>
</head>
<body>

<!-- Botones de control (solo visibles en pantalla, se ocultan al imprimir) -->
<div class="botones-control text-center">
    <button onclick="window.print()" class="btn btn-primary me-2">
        <i class="fas fa-print"></i> Guardar como PDF / Imprimir
    </button>
    <button onclick="window.location.href='historial_boletines.php'" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver al historial
    </button>
</div>

<?php if ($tipo === 'inicial'): ?>
<!-- ============================================================ -->
<!-- ====== VISTA PARA BOLETÍN INICIAL (igual a generar_pdf_boletin.php) ====== -->
<!-- ============================================================ -->
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
            
            <table class="datos-estudiante">
                <tr>
                    <td style="width: 25%;">Estudiante:</td>
                    <td style="width: 75%;" class="linea-inferior"><?php echo $estudiante; ?></td>
                </tr>
                <tr>
                    <td>C.E:</td>
                    <td class="linea-inferior" style="width: 35%;"><?php echo $ce; ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 20%;">Grupo:</td>
                                <td style="width: 80%;" class="linea-inferior"><?php echo $grupo; ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>Docente:</td>
                    <td class="linea-inferior"><?php echo $docente; ?></td>
                </tr>
                <tr>
                    <td>Representante:</td>
                    <td class="linea-inferior"><?php echo $representante; ?></td>
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

<?php else: ?>
<!-- ============================================================ -->
<!-- ====== VISTA PARA BOLETÍN PRIMARIA (igual a generar_pdf_boletin_primaria.php) ====== -->
<!-- ============================================================ -->

<!-- CARA EXTERIOR -->
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
                Al Grado: <span style="font-weight: normal; margin-left: 5px;"><?php echo $grupo; ?></span>
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

            <div class="logo-portada">
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
                    <span style="display: inline-block; width: 40%;"><strong>Grado:</strong> <?php echo $grupo; ?></span>
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

<!-- CARA INTERIOR (LAPSOS) -->
<table class="tabla-triptico">
    <tr>
        <td class="columna-triptico">
            <div class="titulo-momento">PRIMER LAPSO</div>
            <div class="etiqueta-area">PROYECTOS DE APRENDIZAJES:</div>
            <div class="espacio-proyecto"><?php echo $l1_proyecto; ?></div>
            <div class="etiqueta-area" style="text-align: center; margin-bottom: 5px;">ANÁLISIS CUALITATIVO</div>
            <div class="espacio-aprendizaje">
                <?php echo $l1_analisis; ?>
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
                <?php echo $l2_analisis; ?>
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
                <?php echo $l3_analisis; ?>
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

<?php endif; ?>

</body>
</html>