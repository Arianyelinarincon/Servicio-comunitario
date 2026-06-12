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

// Asignar variables igual que en la plantilla original (sin nl2br aquí, se aplicará en el HTML)
$estudiante = htmlspecialchars($boletin['nombre_estudiante']);
$ce = htmlspecialchars($boletin['cedula_escolar']);
$grupo = htmlspecialchars($boletin['sala']);
$ano_escolar = htmlspecialchars($boletin['periodo']);
$docente = ''; // No se guarda en boletines, puedes dejarlo vacío o buscarlo aparte
$representante = htmlspecialchars($boletin['representante'] ?? 'No registrado');
$observacion = htmlspecialchars($boletin['observacion'] ?? '');
$m1_proy = htmlspecialchars($boletin['m1_proyecto'] ?? '');
$m1_form = htmlspecialchars($boletin['m1_formacion'] ?? '');
$m1_rel = htmlspecialchars($boletin['m1_relacion'] ?? '');
$m1_sug = htmlspecialchars($boletin['m1_sugerencias'] ?? '');
$m2_proy = htmlspecialchars($boletin['m2_proyecto'] ?? '');
$m2_form = htmlspecialchars($boletin['m2_formacion'] ?? '');
$m2_rel = htmlspecialchars($boletin['m2_relacion'] ?? '');
$m2_sug = htmlspecialchars($boletin['m2_sugerencias'] ?? '');
$m3_proy = htmlspecialchars($boletin['m3_proyecto'] ?? '');
$m3_form = htmlspecialchars($boletin['m3_formacion'] ?? '');
$m3_rel = htmlspecialchars($boletin['m3_relacion'] ?? '');
$m3_sug = htmlspecialchars($boletin['m3_sugerencias'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boletín Informativo Inicial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .linea-inferior { border-bottom: 1px solid #000; }
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
        .logo-portada {
            width: 90px;
            height: 90px;
            margin: 20px auto;
            border: 2px solid #000;
            text-align: center;
            line-height: 90px;
            font-weight: bold;
            font-size: 12pt;
        }
        .titulos-portada {
            text-align: center;
            margin-bottom: 10px;
        }
        .titulos-portada h1 { font-size: 13pt; margin-top: 50px; }
        .titulos-portada h2 { font-size: 11pt; margin: 10px 0 0 0; font-weight: normal; }
        .datos-estudiante {
            margin-top: 140px; /* Lo ubica en la parte inferior */
            width: 100%;
            font-size: 11pt;
            border-collapse: collapse;
        }
        .datos-estudiante td { padding: 8px 0; }
        
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
     
        @media print {
    .text-center {
        display: none;
    }
}
    </style>
</head>
<body>

<div class="text-center my-3">
    <button onclick="window.print()" class="btn btn-primary me-2">
        <i class="fas fa-print"></i> Guardar como PDF / Imprimir
    </button>
    <button onclick="window.location.href='historial_boletines.php'" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver al historial
    </button>
</div>
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
            
            <div class="logo-portada">
                LOGO
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

</body>
</html>