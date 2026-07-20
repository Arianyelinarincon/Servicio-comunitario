<?php
// plantilla_boletin_primaria.php
// Variables esperadas: $estudiante, $boletin, $periodo, $tipo, $grado, $seccion, $docente, $representante
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletín Primaria</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #fff;
            color: #000;
            font-size: 11pt;
            padding: 20px;
        }
        .boletin-container {
            max-width: 21.59cm;
            margin: 0 auto;
            padding: 0.8cm 1cm;
            border: 1px solid #ccc;
            background: #fff;
        }

        /* ===== LOGO CENTRADO ===== */
        .logo-container {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo-container img {
            max-width: 80px;
            height: auto;
            display: inline-block;
        }

        /* ===== ENCABEZADO ===== */
        .header-institucional {
            text-align: center;
            font-size: 9pt;
            line-height: 1.4;
            margin-bottom: 5px;
        }
        .header-institucional strong {
            font-size: 10pt;
        }
        .header-institucional .codigo {
            font-size: 8pt;
            color: #555;
        }

        .titulo-boletin {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 5px 0 3px 0;
            text-transform: uppercase;
        }
        .subtitulo-boletin {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .periodo-escolar {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 12px;
        }

        /* ===== DATOS DEL ESTUDIANTE ===== */
        .datos-estudiante {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10.5pt;
        }
        .datos-estudiante td {
            padding: 3px 5px;
            vertical-align: middle;
        }
        .datos-estudiante .label {
            font-weight: bold;
            width: 18%;
        }
        .datos-estudiante .valor {
            border-bottom: 1px solid #000;
            padding: 3px 8px;
            font-weight: bold;
        }
        .datos-estudiante .valor-sin-linea {
            font-weight: bold;
        }

        /* ===== TABLA DE LAPSOS ===== */
        .tabla-lapsos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 9pt;
        }
        .tabla-lapsos th,
        .tabla-lapsos td {
            border: 1px solid #000;
            padding: 4px 3px;
            vertical-align: top;
            text-align: left;
        }
        .tabla-lapsos th {
            background-color: #f0f4f8;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
        }
        .tabla-lapsos td {
            font-size: 8.5pt;
            line-height: 1.3;
        }
        .tabla-lapsos .label-lapso {
            font-weight: bold;
            text-align: center;
            width: 10%;
            background-color: #f8f9fa;
        }
        .tabla-lapsos .label-campo {
            font-weight: bold;
            width: 15%;
            background-color: #fafafa;
        }
        .tabla-lapsos .contenido {
            width: 55%;
        }

        /* ===== RESULTADO FINAL ===== */
        .resultado-final {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11pt;
        }
        .resultado-final td {
            padding: 5px 8px;
            border: 1px solid #000;
            vertical-align: middle;
        }
        .resultado-final .label-resultado {
            font-weight: bold;
            text-align: center;
            background-color: #f0f4f8;
            width: 30%;
        }
        .resultado-final .casilla {
            width: 40px;
            height: 30px;
            border: 2px solid #000;
            display: inline-block;
            text-align: center;
            line-height: 30px;
            font-size: 16pt;
            font-weight: bold;
            margin-right: 5px;
        }
        .resultado-final .casilla.marcada {
            background-color: #000;
            color: #fff;
        }
        .resultado-final .literal-box {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 2px solid #000;
            text-align: center;
            line-height: 40px;
            font-size: 22pt;
            font-weight: bold;
            background: #f8f9fa;
        }

        /* ===== ARTÍCULOS ===== */
        .articulos {
            margin-top: 10px;
            font-size: 8pt;
            line-height: 1.3;
        }
        .articulos .titulo-articulo {
            font-weight: bold;
            text-decoration: underline;
            font-size: 9pt;
        }
        .articulos .escala {
            margin: 2px 0;
            padding-left: 5px;
        }
        .articulos .escala strong {
            font-weight: bold;
        }

        /* ===== PIE DE PÁGINA ===== */
        .footer-boletin {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #000;
            font-size: 9pt;
        }
        .footer-boletin .firma {
            display: inline-block;
            width: 30%;
            text-align: center;
            margin-top: 15px;
        }
        .footer-boletin .firma .linea {
            border-top: 1px solid #000;
            width: 80%;
            margin: 0 auto;
            padding-top: 5px;
        }
        .footer-boletin .firma .cargo {
            font-size: 8pt;
            font-weight: bold;
        }
        .footer-boletin .pensamiento {
            text-align: center;
            font-style: italic;
            font-size: 9pt;
            margin-top: 8px;
            color: #555;
        }
        .footer-boletin .versiculo {
            text-align: center;
            font-style: italic;
            font-size: 8.5pt;
            margin-top: 5px;
            color: #555;
        }

        @media print {
            body {
                padding: 0;
            }
            .boletin-container {
                border: none;
                padding: 0.8cm 1cm;
            }
            .tabla-lapsos th {
                background-color: #f0f4f8 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .resultado-final .label-resultado {
                background-color: #f0f4f8 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .resultado-final .casilla.marcada {
                background-color: #000 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .tabla-lapsos .label-lapso {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .tabla-lapsos .label-campo {
                background-color: #fafafa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="boletin-container">

    <!-- ===== LOGO CENTRADO ===== -->
    <div class="logo-container">
        <img src="../includes/image/logo1.png" alt="Logo U.E.B.N. Juan Pablo Pérez Alfonzo">
    </div>

    <!-- ===== ENCABEZADO INSTITUCIONAL ===== -->
    <div class="header-institucional">
        <strong>REPUBLICA BOLIVARIANA DE VENEZUELA</strong><br>
        MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN<br>
        E.B.N "JUAN PABLO PEREZ ALFONZO"<br>
        <span class="codigo">CODIGO DEA 0D02912313</span><br>
        MARACAIBO ZULIA
    </div>

    <!-- ===== TÍTULO ===== -->
    <div class="titulo-boletin">BOLETÍN INFORMATIVO</div>
    <div class="subtitulo-boletin">PRIMARIA</div>
    <div class="periodo-escolar">AÑO ESCOLAR <?= htmlspecialchars($periodo ?? '2025-2026') ?></div>

    <!-- ===== DATOS DEL ESTUDIANTE ===== -->
    <table class="datos-estudiante">
        <tr>
            <td class="label">Estudiante:</td>
            <td class="valor" colspan="3"><?= htmlspecialchars(strtoupper($estudiante['nombre'] ?? '')) ?> <?= htmlspecialchars(strtoupper($estudiante['apellido'] ?? '')) ?></td>
        </tr>
        <tr>
            <td class="label">CE o CI:</td>
            <td class="valor" style="width:30%;"><?= htmlspecialchars($estudiante['cedula_escolar'] ?? $estudiante['cedula'] ?? 'N/A') ?></td>
            <td class="label" style="width:15%;">Grado:</td>
            <td class="valor" style="width:37%;"><?= htmlspecialchars($grado ?? $estudiante['sala'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">Docente:</td>
            <td class="valor"><?= htmlspecialchars($docente ?? 'No asignado') ?></td>
            <td class="label">Representante:</td>
            <td class="valor"><?= htmlspecialchars($representante ?? 'No registrado') ?></td>
        </tr>
    </table>

    <!-- ===== TABLA DE LAPSOS ===== -->
    <table class="tabla-lapsos">
        <thead>
            <tr>
                <th style="width:8%;">Lapso</th>
                <th style="width:20%;">Proyectos de Aprendizajes</th>
                <th style="width:30%;">Análisis Cualitativo</th>
                <th style="width:20%;">Sugerencias</th>
            </tr>
        </thead>
        <tbody>
            <!-- Lapso 1 -->
            <tr>
                <td class="label-lapso"><strong>1</strong></td>
                <td><?= nl2br(htmlspecialchars($boletin['m1_proyecto'] ?? '')) ?></td>
                <td><?= nl2br(htmlspecialchars($boletin['m1_formacion'] ?? '')) ?></td>
                <td><?= nl2br(htmlspecialchars($boletin['m1_sugerencias'] ?? '')) ?></td>
            </tr>
            <!-- Lapso 2 -->
            <tr>
                <td class="label-lapso"><strong>2</strong></td>
                <td><?= nl2br(htmlspecialchars($boletin['m2_proyecto'] ?? '')) ?></td>
                <td><?= nl2br(htmlspecialchars($boletin['m2_formacion'] ?? '')) ?></td>
                <td><?= nl2br(htmlspecialchars($boletin['m2_sugerencias'] ?? '')) ?></td>
            </tr>
            <!-- Lapso 3 -->
            <tr>
                <td class="label-lapso"><strong>3</strong></td>
                <td><?= nl2br(htmlspecialchars($boletin['m3_proyecto'] ?? '')) ?></td>
                <td><?= nl2br(htmlspecialchars($boletin['m3_formacion'] ?? '')) ?></td>
                <td><?= nl2br(htmlspecialchars($boletin['m3_sugerencias'] ?? '')) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- ===== OBSERVACIÓN GENERAL ===== -->
    <?php if (!empty($boletin['observacion'])): ?>
    <div style="margin-top: 6px; font-size: 9pt;">
        <strong>Observación General:</strong><br>
        <?= nl2br(htmlspecialchars($boletin['observacion'])) ?>
    </div>
    <?php endif; ?>

    <!-- ===== RESULTADO FINAL ===== -->
    <table class="resultado-final">
        <tr>
            <td class="label-resultado" style="width:25%;">
                DE ACUERDO AL RESULTADO FINAL DEL RENDIMIENTO ESTUDIANTIL<br>
                EL ALUMNO SE ENCUENTRA EN SITUACIÓN DE:
            </td>
            <td style="width:35%; text-align:center;">
                <span class="casilla <?= ($boletin['resultado_final'] == 'Promovido') ? 'marcada' : '' ?>">X</span>
                <strong>PROMOVIDO</strong>
                <br>
                <span class="casilla <?= ($boletin['resultado_final'] == 'Aplazado') ? 'marcada' : '' ?>">X</span>
                <strong>APLAZADO</strong>
            </td>
            <td style="width:20%; text-align:center;">
                <strong>Al Grado:</strong><br>
                <span style="border-bottom:1px solid #000; padding:0 10px; font-weight:bold;"><?= htmlspecialchars($grado ?? $estudiante['sala'] ?? '') ?></span>
            </td>
            <td style="width:20%; text-align:center;">
                <strong>Con el Literal</strong><br>
                <span class="literal-box"><?= htmlspecialchars($boletin['literal_final'] ?? '') ?></span>
            </td>
        </tr>
    </table>

    <!-- ===== ARTÍCULOS 15 Y 16 ===== -->
    <div class="articulos">
        <div class="titulo-articulo">ARTICULO 15 Y 16</div>
        <div style="font-size:7.5pt; color:#555; margin-bottom:3px;">
            Gaceta oficial de la República Bolivariana de Venezuela N° 5428 de 2002 escala alfabética para la interpretación de los resultados de rendimiento estudiantil.
        </div>
        <div class="escala"><strong>A</strong> El alumno alcanzó todas las competencias y en algunos superó las expectativas para el grado.</div>
        <div class="escala"><strong>B</strong> El alumno alcanzó la mayoría de las competencias previstas para el grado.</div>
        <div class="escala"><strong>C</strong> El alumno alcanzó algunas competencias previstas para el grado.</div>
        <div class="escala"><strong>D</strong> El alumno alcanzó algunas competencias previstas para el grado, pero requiere de un proceso de nivelación.</div>
        <div class="escala"><strong>E</strong> El alumno no alcanzó las competencias mínimas requeridas para ser promovido al grado inmediato superior.</div>
        <div style="margin-top:4px; font-style:italic; font-size:8.5pt; text-align:center; color:#555;">
            "La enseñanza de las buenas costumbres o hábitos sociales es tan esencial como la instrucción."<br>
            <strong>Pensamiento de Simón Bolívar</strong>
        </div>
    </div>

    <!-- ===== PIE DE PÁGINA - FIRMAS ===== -->
    <div class="footer-boletin">
        <div style="display:flex; justify-content:space-around; text-align:center;">
            <div class="firma">
                <div class="linea"></div>
                <div class="cargo">Docente</div>
            </div>
            <div class="firma">
                <div class="linea"></div>
                <div class="cargo">Director(a)</div>
            </div>
            <div class="firma">
                <div class="linea"></div>
                <div class="cargo">Vocera investigación y formación</div>
            </div>
        </div>
        <div style="text-align:center; margin-top:8px;">
            <span style="border:1px solid #000; padding:2px 15px; font-size:9pt;">SELLO</span>
        </div>
        <div class="pensamiento">
            "Instruye al niño en su camino (Dios), y ni aun de viejo se apartará de él."<br>
            Proverbios 22:6
        </div>
    </div>

</div>

</body>
</html>