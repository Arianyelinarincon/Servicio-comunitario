<?php
$estudiante = (string)filter_input(INPUT_POST, 'estudiante');
$grado_seccion = (string)filter_input(INPUT_POST, 'grado_seccion');

$len_m1 = (float)filter_input(INPUT_POST, 'len_m1');
$len_m2 = (float)filter_input(INPUT_POST, 'len_m2');
$len_m3 = (float)filter_input(INPUT_POST, 'len_m3');

$mat_m1 = (float)filter_input(INPUT_POST, 'mat_m1');
$mat_m2 = (float)filter_input(INPUT_POST, 'mat_m2');
$mat_m3 = (float)filter_input(INPUT_POST, 'mat_m3');

$cie_m1 = (float)filter_input(INPUT_POST, 'cie_m1');
$cie_m2 = (float)filter_input(INPUT_POST, 'cie_m2');
$cie_m3 = (float)filter_input(INPUT_POST, 'cie_m3');

$observaciones = (string)filter_input(INPUT_POST, 'observaciones');
$frase_especial = (string)filter_input(INPUT_POST, 'frase_especial');

$promedio_m1 = ($len_m1 + $mat_m1 + $cie_m1) / 3;
$promedio_m2 = ($len_m2 + $mat_m2 + $cie_m2) / 3;
$promedio_m3 = ($len_m3 + $mat_m3 + $cie_m3) / 3;

$promedio_formato_m1 = round($promedio_m1, 2);
$promedio_formato_m2 = round($promedio_m2, 2);
$promedio_formato_m3 = round($promedio_m3, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Boletin</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; background: rgb(224, 224, 224); margin: 0; padding: 20px; display: flex; justify-content: center; flex-direction: column; align-items: center; }
        .hoja-doble { background: white; width: 1000px; min-height: 650px; display: flex; box-shadow: 0 4px 8px rgb(150,150,150); margin-top: 20px; }
        .pagina { flex: 1; padding: 50px 40px; box-sizing: border-box; }
        .pagina-izquierda { border-right: 1px solid rgb(200, 200, 200); }
        .encabezado { text-align: center; font-size: 14px; margin-bottom: 30px; }
        .titulo-principal { text-align: center; font-weight: bold; font-size: 18px; text-decoration: underline; margin-bottom: 40px; }
        .info-estudiante { margin-bottom: 40px; font-size: 14px; font-weight: bold; }
        .linea-datos { font-weight: normal; border-bottom: 1px solid black; display: inline-block; min-width: 250px; padding-left: 5px; }
        .caja-observaciones { border: 1px solid black; height: 250px; padding: 10px; font-size: 14px; }
        .tabla-notas { width: 100%; border-collapse: collapse; font-size: 12px; text-align: center; margin-top: 20px; }
        .celda-tabla { border: 1px solid black; padding: 10px; }
        .caja-frase { margin-top: 40px; padding: 20px; text-align: center; font-style: italic; font-size: 14px; border-top: 2px dotted rgb(170, 170, 170); }
        .panel-impresion { background: white; padding: 15px; border: 1px solid rgb(221, 221, 221); width: 1000px; box-sizing: border-box; }
        .btn-imprimir { background: rgb(26, 35, 126); color: white; padding: 10px 15px; border: none; cursor: pointer; display: block; width: 100%; margin-bottom: 10px; font-weight: bold; }
        .enlace-volver { color: rgb(26, 35, 126); text-decoration: underline; text-align: center; display: block; font-size: 14px; }
        .ocultar-impresion { width: 100%; }
        @media print {
            body { background: white; padding: 0; display: block; }
            .panel-impresion, .ocultar-impresion { display: none; }
            .hoja-doble { width: 100%; box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="ocultar-impresion">
        <?php include '../includes/header.php'; ?>
    </div>

    <div class="panel-impresion">
        <button class="btn-imprimir" onclick="window.print()">IMPRIMIR BOLETÍN</button>
        <a href="calificaciones.php" class="enlace-volver">Regresar al formulario</a>
    </div>

    <div class="hoja-doble">
        
        <div class="pagina pagina-izquierda">
            <div class="encabezado">
                REPÚBLICA BOLIVARIANA DE VENEZUELA<br>
                E.B.N "JUAN PABLO PÉREZ ALFONZO"
            </div>
            
            <div class="titulo-principal">
                BOLETÍN INFORMATIVO INICIAL
            </div>

            <div class="info-estudiante">
                <div style="margin-bottom: 15px;">ESTUDIANTE: <span class="linea-datos"><?php echo htmlspecialchars($estudiante); ?></span></div>
                <div>GRADO/SECCIÓN: <span class="linea-datos"><?php echo htmlspecialchars($grado_seccion); ?></span></div>
            </div>

            <div style="font-weight: bold; margin-bottom: 10px;">OBSERVACIONES GENERALES:</div>
            <div class="caja-observaciones">
                <?php echo nl2br(htmlspecialchars($observaciones)); ?>
            </div>
        </div>

        <div class="pagina">
            <div class="titulo-principal" style="text-decoration: none; margin-top: 15px;">
                REGISTRO DE EVALUACIÓN POR MOMENTOS
            </div>

            <table class="tabla-notas">
                <thead>
                    <tr>
                        <th class="celda-tabla" style="width: 40%;">ÁREAS DE APRENDIZAJE /<br>PROYECTO</th>
                        <th class="celda-tabla" style="width: 20%;">1er<br>MOMENTO</th>
                        <th class="celda-tabla" style="width: 20%;">2do<br>MOMENTO</th>
                        <th class="celda-tabla" style="width: 20%;">3er<br>MOMENTO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="celda-tabla" style="text-align: left; font-weight: bold;">LENGUAJE Y COMUNICACIÓN</td>
                        <td class="celda-tabla"><?php echo htmlspecialchars((string)$len_m1); ?></td>
                        <td class="celda-tabla"><?php echo htmlspecialchars((string)$len_m2); ?></td>
                        <td class="celda-tabla"><?php echo htmlspecialchars((string)$len_m3); ?></td>
                    </tr>
                    <tr>
                        <td class="celda-tabla" style="text-align: left; font-weight: bold;">MATEMÁTICA</td>
                        <td class="celda-tabla"><?php echo htmlspecialchars((string)$mat_m1); ?></td>
                        <td class="celda-tabla"><?php echo htmlspecialchars((string)$mat_m2); ?></td>
                        <td class="celda-tabla"><?php echo htmlspecialchars((string)$mat_m3); ?></td>
                    </tr>
                    <tr>
                        <td class="celda-tabla" style="text-align: left; font-weight: bold;">CIENCIAS Y TECNOLOGÍA</td>
                        <td class="celda-tabla"><?php echo htmlspecialchars((string)$cie_m1); ?></td>
                        <td class="celda-tabla"><?php echo htmlspecialchars((string)$cie_m2); ?></td>
                        <td class="celda-tabla"><?php echo htmlspecialchars((string)$cie_m3); ?></td>
                    </tr>
                    <tr>
                        <td class="celda-tabla" style="text-align: left; font-weight: bold; background: rgb(240,240,240);">PROMEDIO DEL MOMENTO</td>
                        <td class="celda-tabla" style="font-weight: bold; background: rgb(240,240,240);"><?php echo $promedio_formato_m1; ?></td>
                        <td class="celda-tabla" style="font-weight: bold; background: rgb(240,240,240);"><?php echo $promedio_formato_m2; ?></td>
                        <td class="celda-tabla" style="font-weight: bold; background: rgb(240,240,240);"><?php echo $promedio_formato_m3; ?></td>
                    </tr>
                </tbody>
            </table>

            <?php if (!empty($frase_especial)): ?>
            <div class="caja-frase">
                "<?php echo nl2br(htmlspecialchars($frase_especial)); ?>"
            </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="ocultar-impresion">
        <?php include '../includes/footer.php'; ?>
    </div>

</body>
</html>