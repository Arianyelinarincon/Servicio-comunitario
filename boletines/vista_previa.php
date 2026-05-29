<?php
// Recolección de datos enviados desde index.php
$estudiante = $_POST['estudiante'] ?? '';
$grado_seccion = $_POST['grado_seccion'] ?? '';

// Notas de Lenguaje
$len_m1 = $_POST['len_m1'] ?? '';
$len_m2 = $_POST['len_m2'] ?? '';
$len_m3 = $_POST['len_m3'] ?? '';

// Notas de Matemática
$mat_m1 = $_POST['mat_m1'] ?? '';
$mat_m2 = $_POST['mat_m2'] ?? '';
$mat_m3 = $_POST['mat_m3'] ?? '';

// Notas de Ciencias
$cie_m1 = $_POST['cie_m1'] ?? '';
$cie_m2 = $_POST['cie_m2'] ?? '';
$cie_m3 = $_POST['cie_m3'] ?? '';

// Textos
$observaciones = $_POST['observaciones'] ?? '';
$frase_especial = $_POST['frase_especial'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Boletín</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #e0e0e0;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        
        /* Contenedor principal que simula las dos hojas */
        .hoja-doble {
            background: white;
            width: 1000px;
            min-height: 650px;
            display: flex;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .pagina {
            flex: 1;
            padding: 50px 40px;
            box-sizing: border-box;
        }

        .pagina-izquierda {
            border-right: 1px solid #ccc;
        }

        .encabezado {
            text-align: center;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 30px;
        }

        .titulo-principal {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            text-decoration: underline;
            margin-bottom: 40px;
        }

        .info-estudiante {
            margin-bottom: 40px;
            font-size: 14px;
            font-weight: bold;
        }

        .info-estudiante span {
            font-weight: normal;
            border-bottom: 1px solid black;
            display: inline-block;
            min-width: 250px;
            padding-left: 5px;
        }

        .titulo-observaciones {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .caja-observaciones {
            border: 1px solid black;
            height: 250px;
            padding: 10px;
            font-size: 14px;
        }

        .tabla-notas {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            text-align: center;
        }

        .tabla-notas th, .tabla-notas td {
            border: 1px solid black;
            padding: 10px;
        }

        /* Estilo para la frase especial */
        .caja-frase {
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            font-style: italic;
            font-size: 14px;
            border-top: 2px dotted #aaa;
        }

        /* Controles flotantes que no se imprimen */
        .panel-impresion {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-imprimir {
            background: #1a237e; color: white; padding: 10px 15px; border: none; cursor: pointer; display: block; width: 100%; margin-bottom: 10px; font-weight: bold; text-align: center; text-decoration: none;
        }

        .btn-regresar {
            color: #1a237e; text-decoration: underline; text-align: center; display: block; font-size: 14px;
        }

        /* Reglas de impresión */
        @media print {
            body { background: white; padding: 0; }
            .panel-impresion { display: none; }
            .hoja-doble { width: 100%; box-shadow: none; }
        }
    </style>
</head>
<body>

    <!-- Botones de Acción -->
    <div class="panel-impresion no-print">
        <button class="btn-imprimir" onclick="window.print()">IMPRIMIR BOLETÍN</button>
        <a href="index.php" class="btn-regresar">Regresar al formulario</a>
    </div>

    <!-- Diseño del Boletín -->
    <div class="hoja-doble">
        
        <!-- PÁGINA IZQUIERDA -->
        <div class="pagina pagina-izquierda">
            <div class="encabezado">
                REPÚBLICA BOLIVARIANA DE VENEZUELA<br>
                E.B.N "JUAN PABLO PÉREZ ALFONZO"
            </div>
            
            <div class="titulo-principal">
                BOLETÍN INFORMATIVO INICIAL
            </div>

            <div class="info-estudiante">
                <div style="margin-bottom: 15px;">ESTUDIANTE: <span><?php echo htmlspecialchars($estudiante); ?></span></div>
                <div>GRADO/SECCIÓN: <span><?php echo htmlspecialchars($grado_seccion); ?></span></div>
            </div>

            <div class="titulo-observaciones">OBSERVACIONES GENERALES:</div>
            <div class="caja-observaciones">
                <?php echo nl2br(htmlspecialchars($observaciones)); ?>
            </div>
        </div>

        <!-- PÁGINA DERECHA -->
        <div class="pagina">
            <div class="titulo-principal" style="text-decoration: none; margin-top: 15px;">
                REGISTRO DE EVALUACIÓN POR MOMENTOS
            </div>

            <table class="tabla-notas">
                <thead>
                    <tr>
                        <th style="width: 40%;">ÁREAS DE APRENDIZAJE /<br>PROYECTO</th>
                        <th style="width: 20%;">1er<br>MOMENTO</th>
                        <th style="width: 20%;">2do<br>MOMENTO</th>
                        <th style="width: 20%;">3er<br>MOMENTO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left; font-weight: bold;">LENGUAJE Y COMUNICACIÓN</td>
                        <td><?php echo htmlspecialchars($len_m1); ?></td>
                        <td><?php echo htmlspecialchars($len_m2); ?></td>
                        <td><?php echo htmlspecialchars($len_m3); ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: bold;">MATEMÁTICA</td>
                        <td><?php echo htmlspecialchars($mat_m1); ?></td>
                        <td><?php echo htmlspecialchars($mat_m2); ?></td>
                        <td><?php echo htmlspecialchars($mat_m3); ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: bold;">CIENCIAS Y TECNOLOGÍA</td>
                        <td><?php echo htmlspecialchars($cie_m1); ?></td>
                        <td><?php echo htmlspecialchars($cie_m2); ?></td>
                        <td><?php echo htmlspecialchars($cie_m3); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Apartado Dinámico para la Frase -->
            <?php if (!empty($frase_especial)): ?>
            <div class="caja-frase">
                "<?php echo nl2br(htmlspecialchars($frase_especial)); ?>"
            </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>