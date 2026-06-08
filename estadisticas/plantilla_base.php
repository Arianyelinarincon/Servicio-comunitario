<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* ===== CONFIGURACIÓN DE PÁGINA ===== */
        @page {
            size: letter landscape;
            margin: 4mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            margin: 0;
        }

        /* ===== TABLAS GENERALES ===== */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            padding: 2px;
        }

        th {
            font-size: 11px;
        }

        /* ===== ELEMENTOS DE TEXTO ===== */
        .header-title {
            font-size: 15px;
            font-weight: bold;
            line-height: 1.4;
        }

        .linea {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 70px;
            height: 18px;
        }

        .text-left {
            text-align: left;
        }

        /* ===== BORDES PERSONALIZADOS ===== */
        .no-border td,
        .no-border {
            border: none !important;
        }

        /* ===== AJUSTES DE ALTURA PARA TABLA DE ASISTENCIA (CALENDARIO) ===== */
        .tabla-asistencia td {
            height: 24px;
        }
        .tabla-asistencia th {
            height: 22px;
        }

        /* ===== AJUSTES DE ALTURA PARA TABLA DE CLASIFICACIÓN ===== */
        .tabla-clasificacion td {
            height: 27px;
        }
        .tabla-clasificacion th {
            height: 28px;
        }

        /* ===== RESALTADO DE FINES DE SEMANA (sábado y domingo) ===== */
        .fin-semana {
            background-color: #0088ff ;

        }

        /* ===== LÍNEAS DE OBSERVACIONES ===== */
        .linea-observacion {
            height: 20px;
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

    <!-- ==================== ENCABEZADO PRINCIPAL ==================== -->
    <table class="no-border" style="margin-bottom: 6px; width: 100%;">
        <tr>
            <td width="52%" class="text-center header-title">
                <strong>República Bolivariana de Venezuela</strong><br>
                Ministerio del Poder Popular para la Educación<br>
                E.B.N. "Juan Pablo Pérez Alfonzo"<br>
                Maracaibo Estado Zulia<br>
                Periodo Escolar 20<span class="linea"><?php echo $datos['periodo_inicio'] ?? '  '; ?></span> - 20<span class="linea"><?php echo $datos['periodo_fin'] ?? '  '; ?></span>
            </td>
            <td width="48%" style="padding-left:10px; font-size:12px; line-height:1.6;">
                Docente: <span class="linea" style="min-width: 150px;"><?php echo $datos['docente'] ?? '&nbsp;'; ?></span><br>
                Grado: <span class="linea"><?php echo $datos['grado'] ?? '&nbsp;'; ?></span>
                Sección: <span class="linea"><?php echo $datos['seccion'] ?? '&nbsp;'; ?></span>
                Turno: <span class="linea"><?php echo $datos['turno'] ?? '&nbsp;'; ?></span><br>
                Días Hábiles: <span class="linea"><?php echo $datos['dias_habiles'] ?? '&nbsp;'; ?></span>
                Promedio Asistencia: <span class="linea"><?php echo $datos['promedio_asistencia'] ?? '&nbsp;'; ?></span><br>
                Mes: <span class="linea" style="min-width: 70px;"><?php echo $datos['mes'] ?? '&nbsp;'; ?></span>
                Matrícula: V <span class="linea"><?php echo $datos['matricula_v'] ?? '&nbsp;'; ?></span>
                H <span class="linea"><?php echo $datos['matricula_h'] ?? '&nbsp;'; ?></span>
                Total <span class="linea"><?php echo $datos['matricula_total'] ?? '&nbsp;'; ?></span>
            </td>
        </tr>
    </table>

    <!-- ==================== TÍTULO PRINCIPAL ==================== -->
    <div style="text-align:center; font-size:12px; font-weight:bold; border-top:1px solid #000; border-bottom:1px solid #000; padding:1px; margin-bottom:12px; line-height:1.1;">
        RESUMEN ESTADÍSTICO MENSUAL
    </div>

    <!-- ==================== TABLA DE ASISTENCIA (CALENDARIO) ==================== -->
    <table class="tabla-asistencia" style="table-layout: fixed; margin-bottom: 6px; height: 135px;">
        <colgroup>
            <col style="width: 4%;">      <!-- Nº -->
            <?php for($i = 1; $i <= 31; $i++): ?>
                <col style="width: 2.55%;">  <!-- Días 1..31 -->
            <?php endfor; ?>
            <col style="width: 5%;">        <!-- Total -->
            <col style="width: 4%;">        <!-- % -->
        </colgroup>
        <thead>
            <tr>
                <th>Nº</th>
                <?php for($i = 1; $i <= 31; $i++): ?>
                    <th><?php echo $i; ?></th>
                <?php endfor; ?>
                <th>Total</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            <!-- FILA: Letras de los días (D, L, M, M, J, V, S) con resaltado de fines de semana -->
            <tr>
                <th>D</th>
                <?php
                $anio_actual = !empty($datos['mes']) ? date('Y', strtotime($datos['mes'])) : date('Y');
                $mes_actual = !empty($datos['mes']) ? date('m', strtotime($datos['mes'])) : date('m');
                for($i = 1; $i <= 31; $i++):
                    $letra = '';
                    $clase_ws = '';
                    if (checkdate((int)$mes_actual, $i, (int)$anio_actual)) {
                        $fecha_temp = "$anio_actual-$mes_actual-" . str_pad($i, 2, "0", STR_PAD_LEFT);
                        $n_dia = date('w', strtotime($fecha_temp));
                        $letra = ['D','L','M','M','J','V','S'][$n_dia];
                        if ($n_dia == 0 || $n_dia == 6) {
                            $clase_ws = 'fin-semana';
                        }
                    }
                ?>
                    <td class="<?php echo $clase_ws; ?>"><?php echo $letra; ?></td>
                <?php endfor; ?>
                <td><?php echo $datos['dias_total'] ?? ''; ?></td>
                <td><?php echo $datos['dias_porcentaje'] ?? ''; ?></td>
            </tr>

            <!-- FILA: Asistencia de Varones (V) con resaltado de fines de semana -->
            <tr>
                <th>V</th>
                <?php
                for($i = 1; $i <= 31; $i++):
                    $clase_ws = '';
                    if (checkdate((int)$mes_actual, $i, (int)$anio_actual)) {
                        $fecha_temp = "$anio_actual-$mes_actual-" . str_pad($i, 2, "0", STR_PAD_LEFT);
                        $n_dia = date('w', strtotime($fecha_temp));
                        if ($n_dia == 0 || $n_dia == 6) {
                            $clase_ws = 'fin-semana';
                        }
                    }
                ?>
                    <td class="<?php echo $clase_ws; ?>"><?php echo $datos['asistencia_v'][$i] ?? ''; ?></td>
                <?php endfor; ?>
                <td colspan="2">?</td>
            </tr>

            <!-- FILA: Asistencia de Hembras (H) con resaltado de fines de semana -->
            <tr>
                <th>H</th>
                <?php
                for($i = 1; $i <= 31; $i++):
                    $clase_ws = '';
                    if (checkdate((int)$mes_actual, $i, (int)$anio_actual)) {
                        $fecha_temp = "$anio_actual-$mes_actual-" . str_pad($i, 2, "0", STR_PAD_LEFT);
                        $n_dia = date('w', strtotime($fecha_temp));
                        if ($n_dia == 0 || $n_dia == 6) {
                            $clase_ws = 'fin-semana';
                        }
                    }
                ?>
                    <td class="<?php echo $clase_ws; ?>"><?php echo $datos['asistencia_h'][$i] ?? ''; ?></td>
                <?php endfor; ?>
                <td colspan="2">?</td>
            </tr>

            <!-- FILA: Total de Asistencia por día con resaltado de fines de semana -->
            <tr>
                <th>Total</th>
                <?php
                for($i = 1; $i <= 31; $i++):
                    $clase_ws = '';
                    if (checkdate((int)$mes_actual, $i, (int)$anio_actual)) {
                        $fecha_temp = "$anio_actual-$mes_actual-" . str_pad($i, 2, "0", STR_PAD_LEFT);
                        $n_dia = date('w', strtotime($fecha_temp));
                        if ($n_dia == 0 || $n_dia == 6) {
                            $clase_ws = 'fin-semana';
                        }
                    }
                ?>
                    <td class="<?php echo $clase_ws; ?>"><?php echo $datos['asistencia_total'][$i] ?? ''; ?></td>
                <?php endfor; ?>
                <td colspan="2">?</td>
            </tr>
        </tbody>
    </table>

    <!-- ==================== TABLA DE CLASIFICACIÓN ==================== -->
    <table class="tabla-clasificacion" style="margin-bottom: 6px; margin-top:15px;">
        <colgroup>
            <!-- Venezolanos -->
            <col style="width:4%;">
            <col style="width:3%;">
            <col style="width:3%;">
            <col style="width:4%;">

            <!-- Extranjeros -->
            <col style="width:4%;">
            <col style="width:3%;">
            <col style="width:3%;">
            <col style="width:4%;">

            <!-- Ingreso del Mes -->
            <col style="width:18%;">  <!-- Apellido y Nombre -->
            <col style="width:2%;">   <!-- V -->
            <col style="width:2%;">   <!-- H -->
            <col style="width:5%;">   <!-- CI o CE -->
            <col style="width:3%;">   <!-- F.N -->
            <col style="width:3%;">   <!-- F.I -->

            <!-- Egreso del Mes -->
            <col style="width:18%;">  <!-- Apellido y Nombre -->
            <col style="width:2%;">   <!-- V -->
            <col style="width:2%;">   <!-- H -->
            <col style="width:5%;">   <!-- CI o CE -->
            <col style="width:3%;">   <!-- F.N -->
            <col style="width:3%;">   <!-- F.I -->
        </colgroup>
        <thead>
            <tr>
                <th colspan="8">Clasificación por Nacionalidad, Edad y Sexo</th>
                <th colspan="6">Ingreso del Mes</th>
                <th colspan="6">Egreso del Mes</th>
            </tr>
            <tr>
                <th colspan="4">Venezolano</th>
                <th colspan="4">Extranjero</th>
                <th rowspan="2" style="font-size:10px;">Apellido y Nombre</th>
                <th rowspan="2">V</th><th rowspan="2">H</th>
                <th rowspan="2" style="font-size:10px;">CI o CE</th>
                <th rowspan="2">F.N</th><th rowspan="2">F.I</th>
                <th rowspan="2" style="font-size:10px;">Apellido y Nombre</th>
                <th rowspan="2">V</th><th rowspan="2">H</th>
                <th rowspan="2" style="font-size:10px;">CI o CE</th>
                <th rowspan="2">F.N</th><th rowspan="2">F.I</th>
            </tr>
            <tr>
                <th>Edad</th><th>V</th><th>H</th><th>Total</th>
                <th>Edad</th><th>V</th><th>H</th><th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!isset($rango_edades) || empty($rango_edades)) {
                $rango_edades = range(6, 15);
            }
            foreach ($rango_edades as $edad):
            ?>
            <tr>
                <!-- Venezolanos -->
                <td><?php echo $edad; ?></td>
                <td><?php echo $datos['venezolano_v'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['venezolano_h'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['venezolano_total'][$edad] ?? ''; ?></td>
                
                <!-- Extranjeros -->
                <td><?php echo $edad; ?></td>
                <td><?php echo $datos['extranjero_v'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['extranjero_h'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['extranjero_total'][$edad] ?? ''; ?></td>

                <!-- Ingreso -->
                <td class="text-left" style="padding-left: 2px;"><?php echo $datos['ingreso_apellido'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['ingreso_v'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['ingreso_h'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['ingreso_ci'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['ingreso_fn'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['ingreso_fi'][$edad] ?? ''; ?></td>

                <!-- Egreso -->
                <td class="text-left" style="padding-left: 2px;"><?php echo $datos['egreso_apellido'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['egreso_v'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['egreso_h'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['egreso_ci'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['egreso_fn'][$edad] ?? ''; ?></td>
                <td><?php echo $datos['egreso_fi'][$edad] ?? ''; ?></td>
            </tr>
            <?php endforeach; ?>

            <!-- ==================== RESUMEN GENERAL + OBSERVACIONES (con líneas de escritura) ==================== -->
            <tr>
                <th colspan="4">Resumen General</th>
                <th colspan="16" class="text-left" style="padding-left: 5px;">Observaciones Relevantes</th>
            </tr>
            <tr style="height: 95px;" valign="top">
                <!-- Resumen General: líneas para V, H, Total -->
                <td colspan="4" style="padding: 3px;">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="border: none; text-align: left;">
                                V ______________________
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; text-align: left;">
                                H ______________________
                             </td>
                        </tr>
                        <tr>
                            <td style="border: none; text-align: left;">
                                Total __________________
                             </td>
                        </tr>
                        <tr>
                            <td style="border: none;">&nbsp;</td>
                        </tr>
                    </table>
                </td>

                <!-- Observaciones Relevantes: múltiples líneas horizontales + firma -->
                <td colspan="16" style="vertical-align: top; text-align: left; padding: 3px;">
                    <div class="linea-observacion"></div>
                    <div class="linea-observacion"></div>
                    <div class="linea-observacion"></div>
                    <div class="linea-observacion"></div>
                    <div class="linea-observacion"></div>
                    <div class="linea-observacion"></div>
                    <div style="text-align: right; margin-top: 10px;">
                        <span class="linea" style="width: 120px;"></span><br>
                        <strong>Director(a)</strong>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>