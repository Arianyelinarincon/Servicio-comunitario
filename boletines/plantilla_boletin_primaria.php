<?php
session_start();
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

// Extraer todos los datos guardados en el recorrido
$estudiante = htmlspecialchars($_SESSION['estudiante'] ?? '');
$ce = htmlspecialchars($_SESSION['ce'] ?? '');
$grupo = htmlspecialchars($_SESSION['grupo'] ?? '');
$ano_escolar = htmlspecialchars($_SESSION['ano_escolar'] ?? '2025 / 2026');
$docente = htmlspecialchars($_SESSION['docente'] ?? '');
$representante = htmlspecialchars($_SESSION['representante'] ?? '');

$observacion = nl2br(htmlspecialchars($_SESSION['observacion'] ?? ''));

// Variables de Asistencia y Antropometría (Si las vas a llenar desde el sistema)
$dias_habiles = htmlspecialchars($_SESSION['dias_habiles'] ?? '');
$inasistencias = htmlspecialchars($_SESSION['inasistencias'] ?? '');
$talla = htmlspecialchars($_SESSION['talla'] ?? '');
$peso = htmlspecialchars($_SESSION['peso'] ?? '');

// Primer Momento
$m1_proy = htmlspecialchars($_SESSION['m1_proyecto'] ?? '');
$m1_leng = nl2br(htmlspecialchars($_SESSION['m1_lenguaje'] ?? ''));
$m1_mat  = nl2br(htmlspecialchars($_SESSION['m1_matematica'] ?? ''));
$m1_soc  = nl2br(htmlspecialchars($_SESSION['m1_ciencias_soc'] ?? ''));
$m1_fis  = nl2br(htmlspecialchars($_SESSION['m1_educacion_fisica'] ?? ''));

// Segundo Momento
$m2_proy = htmlspecialchars($_SESSION['m2_proyecto'] ?? '');
$m2_leng = nl2br(htmlspecialchars($_SESSION['m2_lenguaje'] ?? ''));
$m2_mat  = nl2br(htmlspecialchars($_SESSION['m2_matematica'] ?? ''));
$m2_soc  = nl2br(htmlspecialchars($_SESSION['m2_ciencias_soc'] ?? ''));
$m2_fis  = nl2br(htmlspecialchars($_SESSION['m2_educacion_fisica'] ?? ''));

// Tercer Momento
$m3_proy = htmlspecialchars($_SESSION['m3_proyecto'] ?? '');
$m3_leng = nl2br(htmlspecialchars($_SESSION['m3_lenguaje'] ?? ''));
$m3_mat  = nl2br(htmlspecialchars($_SESSION['m3_matematica'] ?? ''));
$m3_soc  = nl2br(htmlspecialchars($_SESSION['m3_ciencias_soc'] ?? ''));
$m3_fis  = nl2br(htmlspecialchars($_SESSION['m3_educacion_fisica'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Boletín Primaria</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e0e0e0; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; font-size: 11px; }
        .controles { background: white; padding: 15px; width: 279mm; margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; }
        .btn-imprimir { background: rgb(26, 35, 126); color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 14px; font-weight: bold; border-radius: 4px; }
        .ocultar-impresion { width: 100%; }

        /* Distribución base estricta para Dompdf */
        table.triptico {
            background: white;
            width: 279mm;
            height: 215.9mm;
            border-collapse: collapse;
            table-layout: fixed;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            margin-bottom: 20mm;
        }
        
        .page-break { page-break-after: always; }

        table.triptico td {
            width: 33.33%;
            padding: 10mm;
            vertical-align: top;
        }

        .col-borde { border-right: 1px dashed transparent; }

        /* Estilos de Textos y Títulos */
        .titulo-seccion { text-align: center; font-weight: bold; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; }
        .sub-titulo { font-weight: bold; font-size: 10px; margin-top: 8px; margin-bottom: 3px; text-transform: uppercase; }
        .membrete { text-align: center; font-weight: bold; font-size: 10px; line-height: 1.3; margin-bottom: 15px; }
        
        /* Cajas (Bordes idénticos a la foto) */
        .caja-texto { border: 1px solid #000; padding: 8px; min-height: 55px; margin-bottom: 10px; text-align: justify; font-size: 10px; }
        .caja-observacion { border: 1px solid #000; padding: 10px; height: 450px; text-align: justify; }
        .caja-proyecto { border: 1px solid #000; padding: 6px; min-height: 25px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        
        /* Tablas de control en la cara central */
        .tabla-control { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .tabla-control td { border: 1px solid #000; padding: 6px; font-size: 11px; }

        /* Datos de portada */
        .datos-estudiante { margin-top: 30px; line-height: 2.2; font-size: 11px; font-weight: bold; }
        .linea { border-bottom: 1px solid #000; display: inline-block; padding-left: 5px; font-weight: normal; }

        /* Firmas */
        .momento-firmas { margin-top: 30px; }
        .firma-item { margin-bottom: 15px; font-size: 11px; font-weight: bold; }
        .linea-corta { border-bottom: 1px solid #000; display: inline-block; margin-left: 5px; }

        @media print {
            @page { size: letter landscape; margin: 0; }
            body { background: white; margin: 0; padding: 0; display: block; }
            .controles, .ocultar-impresion { display: none !important; }
            table.triptico { 
                box-shadow: none; 
                margin: 0; 
                border: none; 
                width: 279mm; 
                height: 215.9mm; 
                padding: 10mm;
            }
        }
    </style>
</head>
<body>

    <div class="ocultar-impresion">
        <?php include '../includes/header.php'; ?>
    </div>

    <div class="controles">
        <button class="btn-imprimir" onclick="window.print()">IMPRIMIR BOLETÍN PRIMARIA (Selecciona Horizontal al imprimir)</button>
        <br><br>
        <a href="paso1_portada.php?tipo=primaria" style="color: rgb(26,35,126); text-decoration: none; font-weight: bold;">Crear otro boletín</a>
    </div>

    <table class="triptico page-break">
        <tr>
            <td class="col-borde">
                <div class="titulo-seccion">OBSERVACIÓN GENERAL</div>
                <div class="caja-observacion">
                    <?php echo $observacion; ?>
                </div>
            </td>

            <td class="col-borde" style="padding-right: 20px; padding-left: 20px;">
                <div class="titulo-seccion">CONTROL DE ASISTENCIA</div>
                <table class="tabla-control">
                    <tr>
                        <td style="font-weight: bold; width: 60%;">Días Hábiles:</td>
                        <td style="text-align: center; width: 40%;"><?php echo $dias_habiles; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Inasistencias:</td>
                        <td style="text-align: center;"><?php echo $inasistencias; ?></td>
                    </tr>
                </table>

                <div class="titulo-seccion">DATOS ANTROPOMÉTRICOS</div>
                <table class="tabla-control">
                    <tr>
                        <td style="text-align:center; font-weight: bold; width: 50%;">Talla (cm)</td>
                        <td style="text-align:center; font-weight: bold; width: 50%;">Peso (kg)</td>
                    </tr>
                    <tr>
                        <td style="text-align:center; height: 35px;"><?php echo $talla; ?></td>
                        <td style="text-align:center; height: 35px;"><?php echo $peso; ?></td>
                    </tr>
                </table>
            </td>

            <td>
                <div class="membrete">
                    REPÚBLICA BOLIVARIANA DE VENEZUELA<br>
                    MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN<br>
                    E.B.N "JUAN PABLO PÉREZ ALFONZO"<br>
                    CÓDIGO DEA OD02912313<br>
                    MARACAIBO - ESTADO ZULIA
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <div style="width: 100px; height: 100px; border: 1px solid #000; margin: 0 auto; line-height: 100px; color: #555; border-radius: 50%; font-size: 10px;">LOGO</div>
                </div>
                
                <div class="titulo-seccion" style="font-size: 14px; margin-top: 20px;">
                    BOLETÍN INFORMATIVO<br>EDUCACIÓN PRIMARIA
                </div>
                
                <div class="datos-estudiante">
                    <div>Estudiante: <span class="linea" style="width: 71%;"><?php echo $estudiante; ?></span></div>
                    <div>C.E o C.I: <span class="linea" style="width: 75%;"><?php echo $ce; ?></span></div>
                    <div>Grado y Sección: <span class="linea" style="width: 53%;"><?php echo $grupo; ?></span></div>
                    <div>Año Escolar: <span class="linea" style="width: 66%;"><?php echo $ano_escolar; ?></span></div>
                    <div>Docente: <span class="linea" style="width: 76%;"><?php echo $docente; ?></span></div>
                    <div>Representante: <span class="linea" style="width: 59%;"><?php echo $representante; ?></span></div>
                </div>
            </td>
        </tr>
    </table>

    <table class="triptico">
        <tr>
            <td class="col-borde">
                <div class="titulo-seccion">1ER MOMENTO</div>
                
                <div class="sub-titulo">PROYECTO DE APRENDIZAJE:</div>
                <div class="caja-proyecto"><?php echo $m1_proy; ?></div>
                
                <div class="titulo-seccion" style="margin-top: 15px; text-decoration: underline;">ÁREAS DE APRENDIZAJES</div>
                
                <div class="sub-titulo">LENGUAJE, COMUNICACIÓN Y CULTURA</div>
                <div class="caja-texto"><?php echo $m1_leng; ?></div>
                
                <div class="sub-titulo">MATEMÁTICA, CIENCIAS NATURALES Y SOCIEDAD</div>
                <div class="caja-texto"><?php echo $m1_mat; ?></div>
                
                <div class="sub-titulo">CIENCIAS SOCIALES CIUDADANÍA E IDENTIDAD</div>
                <div class="caja-texto"><?php echo $m1_soc; ?></div>
                
                <div class="sub-titulo">EDUCACIÓN FÍSICA Y RECREACIÓN</div>
                <div class="caja-texto"><?php echo $m1_fis; ?></div>
            </td>

            <td class="col-borde">
                <div class="titulo-seccion">2DO MOMENTO</div>
                
                <div class="sub-titulo">PROYECTO DE APRENDIZAJE:</div>
                <div class="caja-proyecto"><?php echo $m2_proy; ?></div>
                
                <div class="titulo-seccion" style="margin-top: 15px; text-decoration: underline;">ÁREAS DE APRENDIZAJES</div>
                
                <div class="sub-titulo">LENGUAJE, COMUNICACIÓN Y CULTURA</div>
                <div class="caja-texto"><?php echo $m2_leng; ?></div>
                
                <div class="sub-titulo">MATEMÁTICA, CIENCIAS NATURALES Y SOCIEDAD</div>
                <div class="caja-texto"><?php echo $m2_mat; ?></div>
                
                <div class="sub-titulo">CIENCIAS SOCIALES CIUDADANÍA E IDENTIDAD</div>
                <div class="caja-texto"><?php echo $m2_soc; ?></div>
                
                <div class="sub-titulo">EDUCACIÓN FÍSICA Y RECREACIÓN</div>
                <div class="caja-texto"><?php echo $m2_fis; ?></div>
            </td>

            <td>
                <div class="titulo-seccion">3ER MOMENTO</div>
                
                <div class="sub-titulo">PROYECTO DE APRENDIZAJE:</div>
                <div class="caja-proyecto"><?php echo $m3_proy; ?></div>
                
                <div class="titulo-seccion" style="margin-top: 15px; text-decoration: underline;">ÁREAS DE APRENDIZAJES</div>
                
                <div class="sub-titulo">LENGUAJE, COMUNICACIÓN Y CULTURA</div>
                <div class="caja-texto"><?php echo $m3_leng; ?></div>
                
                <div class="sub-titulo">MATEMÁTICA, CIENCIAS NATURALES Y SOCIEDAD</div>
                <div class="caja-texto"><?php echo $m3_mat; ?></div>
                
                <div class="sub-titulo">CIENCIAS SOCIALES CIUDADANÍA E IDENTIDAD</div>
                <div class="caja-texto"><?php echo $m3_soc; ?></div>
                
                <div class="sub-titulo">EDUCACIÓN FÍSICA Y RECREACIÓN</div>
                <div class="caja-texto"><?php echo $m3_fis; ?></div>
                
                <div class="momento-firmas">
                    <div class="firma-item">Director (a): <span class="linea-corta" style="width: 120px;"></span></div>
                    <div class="firma-item">Docente: <span class="linea-corta" style="width: 135px;"></span></div>
                    <div class="firma-item">Representante: <span class="linea-corta" style="width: 100px;"></span></div>
                    <div class="firma-item" style="margin-top: 10px;">Fecha: ______/______/________</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="ocultar-impresion">
        <?php include '../includes/footer.php'; ?>
    </div>

</body>
</html>