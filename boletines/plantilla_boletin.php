<?php
session_start();

$estudiante     = htmlspecialchars($_SESSION['estudiante'] ?? '');
$ce             = htmlspecialchars($_SESSION['ce'] ?? '');
$grupo          = htmlspecialchars($_SESSION['grupo'] ?? '');
$ano_escolar    = htmlspecialchars($_SESSION['ano_escolar'] ?? '2025 / 2026');
$docente        = htmlspecialchars($_SESSION['docente'] ?? '');
$representante  = htmlspecialchars($_SESSION['representante'] ?? '');
$observacion    = nl2br(htmlspecialchars($_SESSION['observacion'] ?? ''));

$m1_proy = htmlspecialchars($_SESSION['m1_proyecto'] ?? '');
$m1_form = nl2br(htmlspecialchars($_SESSION['m1_formacion'] ?? ''));
$m1_rel  = nl2br(htmlspecialchars($_SESSION['m1_relacion'] ?? ''));
$m1_sug  = nl2br(htmlspecialchars($_SESSION['m1_sugerencias'] ?? ''));

$m2_proy = htmlspecialchars($_SESSION['m2_proyecto'] ?? '');
$m2_form = nl2br(htmlspecialchars($_SESSION['m2_formacion'] ?? ''));
$m2_rel  = nl2br(htmlspecialchars($_SESSION['m2_relacion'] ?? ''));
$m2_sug  = nl2br(htmlspecialchars($_SESSION['m2_sugerencias'] ?? ''));

$m3_proy = htmlspecialchars($_SESSION['m3_proyecto'] ?? '');
$m3_form = nl2br(htmlspecialchars($_SESSION['m3_formacion'] ?? ''));
$m3_rel  = nl2br(htmlspecialchars($_SESSION['m3_relacion'] ?? ''));
$m3_sug  = nl2br(htmlspecialchars($_SESSION['m3_sugerencias'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boletín Informativo</title>
    <style>
        /* RESET para impresión */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Configuración de página: carta horizontal */
        @page {
            size: letter landscape;
            margin: 12mm 10mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            background: white;
            font-size: 12pt;
            line-height: 1.3;
        }

        /* Cada cara del tríptico ocupa una página completa */
        .cara {
            width: 100%;
            page-break-after: always;  /* fuerza salto entre cara exterior e interior */
        }
        .cara:last-child {
            page-break-after: auto;
        }

        /* Tabla de 3 columnas (paneles) */
        .paneles {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .panel {
            width: 33.33%;
            vertical-align: top;
            padding: 0 6px;
        }
        .borde-derecho {
            border-right: 1px dashed #aaa;
        }

        /* Contenido interno de cada panel (para alinear verticalmente) */
        .panel-contenido {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 245mm; /* altura útil dentro de la página */
        }
        .panel-top { flex: 1; }
        .panel-middle { flex: 0; }
        .panel-bottom { flex: 0; }

        /* Estilos generales */
        .titulo-seccion {
            font-weight: bold;
            margin: 4mm 0 2mm 0;
            font-size: 12pt;
        }
        .linea {
            border-bottom: 1px solid #000;
            margin: 3mm 0;
            width: 100%;
        }
        .linea-corta {
            border-bottom: 1px solid #000;
            width: 60%;
            margin: 2mm auto;
        }
        .centrado {
            text-align: center;
        }
        .cita {
            font-style: italic;
            font-size: 10pt;
            text-align: center;
            margin: 6mm 0;
        }
        .cita-autor {
            font-size: 9pt;
            font-weight: bold;
        }
        .logo {
            font-weight: bold;
            font-size: 14pt;
            text-align: center;
            margin: 8mm 0;
        }
        .encabezado-escuela {
            text-align: center;
            font-size: 10pt;
            line-height: 1.3;
            text-transform: uppercase;
            margin-bottom: 5mm;
        }
        .escudo {
            text-align: center;
            margin: 3mm 0;
        }
        .escudo-figura {
            display: inline-block;
            width: 18mm;
            height: 18mm;
            border: 1px solid #000;
            line-height: 18mm;
            font-size: 9pt;
        }
        .titulo-boletin {
            text-align: center;
            margin: 5mm 0;
        }
        .titulo-boletin strong {
            font-size: 16pt;
        }
        .dato-fila {
            margin: 3mm 0;
        }
        .dato-etiqueta {
            font-weight: bold;
            display: inline-block;
            width: 30mm;
        }
        .dato-texto {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 50mm;
        }
        .sello {
            border: 1px solid #000;
            width: 25mm;
            height: 25mm;
            margin: 5mm auto;
            text-align: center;
            line-height: 25mm;
            font-size: 10pt;
        }
        .firmas {
            margin-top: 8mm;
        }
        .firma-item {
            margin: 4mm 0;
            text-align: center;
        }
        .firma-linea {
            border-top: 1px solid #000;
            width: 70%;
            margin: 2mm auto 0 auto;
        }

        /* Estilos para los momentos (interior) */
        .momento {
            border: 1px solid #ccc;
            padding: 3mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .momento-titulo {
            background: #f0f0f0;
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            padding: 2mm;
            margin-bottom: 3mm;
        }
        .momento-contenido {
            flex: 1;
        }
        .momento-firmas {
            margin-top: 5mm;
            border-top: 1px solid #aaa;
            padding-top: 3mm;
        }
        .area {
            font-weight: bold;
            margin: 3mm 0 1mm 0;
        }
        .texto-area {
            margin-bottom: 3mm;
            border-bottom: 1px dotted #aaa;
            padding-bottom: 1mm;
        }

        /* Botones en pantalla (no se imprimen) */
        .botones {
            text-align: center;
            margin: 10mm 0;
        }
        @media print {
            .botones {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="botones">
    <button onclick="window.print()">🖨️ Guardar como PDF / Imprimir</button>
    <button onclick="window.location.href='index.php'">← Volver al inicio</button>
</div>

<!-- ==================== CARA EXTERIOR (PÁGINA 1) ==================== -->
<div class="cara">
    <table class="paneles">
        <tr>
            <!-- PANEL IZQUIERDO: Observación, sello, firmas -->
            <td class="panel borde-derecho">
                <div class="panel-contenido">
                    <div class="panel-top">
                        <div class="titulo-seccion">Observación:</div>
                        <div><?php echo $observacion ?: '(Sin observación)'; ?></div>
                        <div class="linea"></div>
                        <div class="sello">SELLO</div>
                    </div>
                    <div class="panel-bottom">
                        <div class="firmas">
                            <div class="firma-item">
                                <div>Docente</div>
                                <div class="firma-linea"></div>
                            </div>
                            <div class="firma-item">
                                <div>Director(a)</div>
                                <div class="firma-linea"></div>
                            </div>
                            <div class="firma-item">
                                <div>Vocera de investigación y formación</div>
                                <div class="firma-linea"></div>
                            </div>
                        </div>
                        <div class="cita">
                            "Instruye al niño en su camino,<br>y aun cuando fuere viejo no se apartará de él."
                            <div class="cita-autor">Proverbios 22:6</div>
                        </div>
                    </div>
                </div>
            </td>

            <!-- PANEL CENTRAL: Logo y citas -->
            <td class="panel borde-derecho">
                <div class="panel-contenido">
                    <div class="panel-top">
                        <div class="logo">E.B.N "JUAN PABLO<br>PÉREZ ALFONZO"</div>
                    </div>
                    <div class="panel-middle">
                        <div class="cita">
                            "Pocos conocen su utilidad"<br>
                            <span class="cita-autor">— Simón Rodríguez</span>
                        </div>
                        <div class="cita">
                            "La enseñanza de las buenas costumbres<br>o hábitos sociales es tan esencial<br>como la instrucción."<br>
                            <span class="cita-autor">— Simón Bolívar</span>
                        </div>
                    </div>
                    <div class="panel-bottom"></div>
                </div>
            </td>

            <!-- PANEL DERECHO: Datos del estudiante -->
            <td class="panel">
                <div class="panel-contenido">
                    <div class="panel-top">
                        <div class="encabezado-escuela">
                            REPÚBLICA BOLIVARIANA DE VENEZUELA<br>
                            MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN<br>
                            <strong>E.B.N "JUAN PABLO PÉREZ ALFONZO"</strong><br>
                            CÓDIGO DEA OD02912313<br>
                            MARACAIBO - ZULIA
                        </div>
                        <div class="escudo">
                            <div class="escudo-figura">ESCUDO</div>
                        </div>
                        <div class="titulo-boletin">
                            <strong>BOLETÍN INFORMATIVO INICIAL</strong><br>
                            AÑO ESCOLAR <?php echo $ano_escolar; ?>
                        </div>
                    </div>
                    <div class="panel-bottom">
                        <div class="dato-fila"><span class="dato-etiqueta">Estudiante:</span><span class="dato-texto"><?php echo $estudiante; ?></span></div>
                        <div class="dato-fila"><span class="dato-etiqueta">CE:</span><span class="dato-texto"><?php echo $ce; ?></span></div>
                        <div class="dato-fila"><span class="dato-etiqueta">Grupo:</span><span class="dato-texto"><?php echo $grupo; ?></span></div>
                        <div class="dato-fila"><span class="dato-etiqueta">Docente:</span><span class="dato-texto"><?php echo $docente; ?></span></div>
                        <div class="dato-fila"><span class="dato-etiqueta">Representante:</span><span class="dato-texto"><?php echo $representante; ?></span></div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ==================== CARA INTERIOR (PÁGINA 2) ==================== -->
<div class="cara">
    <table class="paneles">
        <tr>
            <!-- PRIMER MOMENTO -->
            <td class="panel borde-derecho">
                <div class="momento">
                    <div>
                        <div class="momento-titulo">PRIMER MOMENTO DE EVALUACIÓN</div>
                        <div class="area">PROYECTOS DE APRENDIZAJES:</div>
                        <div class="texto-area"><?php echo $m1_proy; ?></div>
                        <div class="area">ÁREAS DE APRENDIZAJE</div>
                        <div>Formación personal, social y comunicación</div>
                        <div class="texto-area"><?php echo $m1_form; ?></div>
                        <div>Relación entre los Componentes del Ambiente</div>
                        <div class="texto-area"><?php echo $m1_rel; ?></div>
                        <div class="area">SUGERENCIAS</div>
                        <div class="texto-area"><?php echo $m1_sug; ?></div>
                    </div>
                    <div class="momento-firmas">
                        <div class="firma-item">Director(a): <span class="linea-corta" style="display:inline-block;"></span></div>
                        <div class="firma-item">Docente: <span class="linea-corta" style="display:inline-block;"></span></div>
                        <div class="firma-item">Representante: <span class="linea-corta" style="display:inline-block;"></span></div>
                        <div class="firma-item">Fecha: ___ / ___ / _____</div>
                    </div>
                </div>
            </td>

            <!-- SEGUNDO MOMENTO -->
            <td class="panel borde-derecho">
                <div class="momento">
                    <div>
                        <div class="momento-titulo">SEGUNDO MOMENTO DE EVALUACIÓN</div>
                        <div class="area">PROYECTOS DE APRENDIZAJES:</div>
                        <div class="texto-area"><?php echo $m2_proy; ?></div>
                        <div class="area">ÁREAS DE APRENDIZAJE</div>
                        <div>Formación personal, social y comunicación</div>
                        <div class="texto-area"><?php echo $m2_form; ?></div>
                        <div>Relación entre los Componentes del Ambiente</div>
                        <div class="texto-area"><?php echo $m2_rel; ?></div>
                        <div class="area">SUGERENCIAS</div>
                        <div class="texto-area"><?php echo $m2_sug; ?></div>
                    </div>
                    <div class="momento-firmas">
                        <div class="firma-item">Director(a): <span class="linea-corta" style="display:inline-block;"></span></div>
                        <div class="firma-item">Docente: <span class="linea-corta" style="display:inline-block;"></span></div>
                        <div class="firma-item">Representante: <span class="linea-corta" style="display:inline-block;"></span></div>
                        <div class="firma-item">Fecha: ___ / ___ / _____</div>
                    </div>
                </div>
            </td>

            <!-- TERCER MOMENTO -->
            <td class="panel">
                <div class="momento">
                    <div>
                        <div class="momento-titulo">TERCER MOMENTO DE EVALUACIÓN</div>
                        <div class="area">PROYECTOS DE APRENDIZAJES:</div>
                        <div class="texto-area"><?php echo $m3_proy; ?></div>
                        <div class="area">ÁREAS DE APRENDIZAJE</div>
                        <div>Formación personal, social y comunicación</div>
                        <div class="texto-area"><?php echo $m3_form; ?></div>
                        <div>Relación entre los Componentes del Ambiente</div>
                        <div class="texto-area"><?php echo $m3_rel; ?></div>
                        <div class="area">SUGERENCIAS</div>
                        <div class="texto-area"><?php echo $m3_sug; ?></div>
                    </div>
                    <div class="momento-firmas">
                        <div class="firma-item">Director(a): <span class="linea-corta" style="display:inline-block;"></span></div>
                        <div class="firma-item">Docente: <span class="linea-corta" style="display:inline-block;"></span></div>
                        <div class="firma-item">Representante: <span class="linea-corta" style="display:inline-block;"></span></div>
                        <div class="firma-item">Fecha: ___ / ___ / _____</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>