<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Configuración estricta del papel */
        @page { size: letter landscape; margin: 8mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8px; margin: 0; padding: 0; color: #000; }
        
        /* Tablas preparadas para no desbordar Dompdf */
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid black; text-align: center; padding: 2px 1px; word-wrap: break-word; }
        th { background-color: #f2f2f2; font-weight: bold; }
        
        /* Utilidades */
        .no-border, .no-border th, .no-border td { border: none !important; background: transparent; text-align: left; }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        
        /* Líneas del encabezado dinámicas (sin píxeles fijos) */
        .linea { border-bottom: 1px solid black; padding: 0 5px; display: inline-block; }
    </style>
</head>
<body>

    <table class="no-border" style="margin-bottom: 10px; font-size: 10px; line-height: 1.4;">
        <tr>
            <td width="40%" class="text-center">
                <strong>República Bolivariana de Venezuela</strong><br>
                Ministerio del Poder Popular para la Educación<br>
                E.B.N. "Juan Pablo Pérez Alfonzo"<br>
                Maracaibo Estado Zulia<br>
                Periodo Escolar 20<span class="linea"><?php echo $datos['periodo_inicio'] ?? '  '; ?></span> - 20<span class="linea"><?php echo $datos['periodo_fin'] ?? '  '; ?></span>
            </td>
            <td width="60%" style="padding-left: 20px;">
                Docente: <span class="linea" style="min-width: 200px;"><?php echo $datos['docente'] ?? '&nbsp;'; ?></span><br>
                Grado: <span class="linea"><?php echo $datos['grado'] ?? '&nbsp;'; ?></span>
                Sección: <span class="linea"><?php echo $datos['seccion'] ?? '&nbsp;'; ?></span>
                Turno: <span class="linea"><?php echo $datos['turno'] ?? '&nbsp;'; ?></span><br>
                Días Hábiles: <span class="linea"><?php echo $datos['dias_habiles'] ?? '&nbsp;'; ?></span>
                Promedio Asistencia: <span class="linea"><?php echo $datos['promedio_asistencia'] ?? '&nbsp;'; ?></span><br>
                Mes: <span class="linea" style="min-width: 100px;"><?php echo $datos['mes'] ?? '&nbsp;'; ?></span> 
                Matrícula: V <span class="linea"><?php echo $datos['matricula_v'] ?? '&nbsp;'; ?></span> 
                H <span class="linea"><?php echo $datos['matricula_h'] ?? '&nbsp;'; ?></span> 
                Total <span class="linea"><?php echo $datos['matricula_total'] ?? '&nbsp;'; ?></span>
            </td>
        </tr>
    </table>

    <div style="text-align: center; border-top: 1px solid black; border-bottom: 1px solid black; padding: 3px 0; font-size: 11px; font-weight: bold; margin-bottom: 8px;">
        RESUMEN ESTADÍSTICO MENSUAL
    </div>

    <table>
        <colgroup>
            <col width="3%"> <?php for($i=1; $i<=31; $i++): ?><col width="2.7%"><?php endfor; ?> <col width="6%"> <col width="7.3%"> </colgroup>
        
        <tr>
            <th>Nº</th>
            <?php for($i=1; $i<=31; $i++): ?><th><?php echo $i; ?></th><?php endfor; ?>
            <th>Total</th><th>%</th>
        </tr>
        <tr>
            <th>D</th>
            <?php 
            $anio_actual = !empty($datos['mes']) ? date('Y', strtotime($datos['mes'])) : date('Y');
            $mes_actual = !empty($datos['mes']) ? date('m', strtotime($datos['mes'])) : date('m');
            
            for($i=1; $i<=31; $i++): 
                $letra = "";
                if(checkdate((int)$mes_actual, $i, (int)$anio_actual)){
                    $fecha_temp = "$anio_actual-$mes_actual-" . str_pad($i, 2, "0", STR_PAD_LEFT);
                    $n_dia = date('w', strtotime($fecha_temp));
                    $letra = ['D','L','M','M','J','V','S'][$n_dia];
                }
            ?>
                <td><?php echo $letra; ?></td>
            <?php endfor; ?>
            <td><?php echo $datos['dias_total'] ?? ''; ?></td>
            <td><?php echo $datos['dias_porcentaje'] ?? ''; ?></td>
        </tr>
        <tr>
            <th>V</th>
            <?php for($i=1; $i<=31; $i++): ?><td><?php echo $datos['asistencia_v'][$i] ?? ''; ?></td><?php endfor; ?>
            <td colspan="2"></td>
        </tr>
        <tr>
            <th>H</th>
            <?php for($i=1; $i<=31; $i++): ?><td><?php echo $datos['asistencia_h'][$i] ?? ''; ?></td><?php endfor; ?>
            <td colspan="2"></td>
        </tr>
        <tr>
            <th>Total</th>
            <?php for($i=1; $i<=31; $i++): ?><td><?php echo $datos['asistencia_total'][$i] ?? ''; ?></td><?php endfor; ?>
            <td colspan="2"></td>
        </tr>
    </table>

    <table>
        <colgroup>
            <col width="3%"><col width="3%"><col width="3%"><col width="3.5%"> <col width="3%"><col width="3%"><col width="3%"><col width="3.5%"> <col width="14%"><col width="3%"><col width="3%"><col width="5.5%"><col width="6%"><col width="6%"> <col width="14%"><col width="3%"><col width="3%"><col width="5.5%"><col width="6%"><col width="6%"> </colgroup>

        <tr>
            <th colspan="8">Clasificación por Nacionalidad, Edad y Sexo</th>
            <th colspan="6">Ingreso del Mes</th>
            <th colspan="6">Egreso del Mes</th>
        </tr>
        <tr>
            <th colspan="4">Venezolano</th>
            <th colspan="4">Extranjero</th>
            <th rowspan="2">Apellido y Nombre</th>
            <th rowspan="2">V</th><th rowspan="2">H</th><th rowspan="2">CI o CE</th><th rowspan="2">F.N</th><th rowspan="2">F.I</th>
            <th rowspan="2">Apellido y Nombre</th>
            <th rowspan="2">V</th><th rowspan="2">H</th><th rowspan="2">CI o CE</th><th rowspan="2">F.N</th><th rowspan="2">F.I</th>
        </tr>
        <tr>
            <th>Edad</th><th>V</th><th>H</th><th>Total</th>
            <th>Edad</th><th>V</th><th>H</th><th>Total</th>
        </tr>
        
        <?php 
        // Lógica de seguridad
        if (!isset($rango_edades) || empty($rango_edades)) {
            $rango_edades = range(7, 15);
        }
        foreach($rango_edades as $edad): 
        ?>
        <tr>
            <td><?php echo $edad; ?></td>
            <td><?php echo $datos['venezolano_v'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['venezolano_h'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['venezolano_total'][$edad] ?? ''; ?></td>
            
            <td><?php echo $edad; ?></td>
            <td><?php echo $datos['extranjero_v'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['extranjero_h'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['extranjero_total'][$edad] ?? ''; ?></td>
            
            <td class="text-left" style="padding-left: 2px;"><?php echo $datos['ingreso_apellido'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['ingreso_v'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['ingreso_h'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['ingreso_ci'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['ingreso_fn'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['ingreso_fi'][$edad] ?? ''; ?></td>
            
            <td class="text-left" style="padding-left: 2px;"><?php echo $datos['egreso_apellido'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['egreso_v'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['egreso_h'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['egreso_ci'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['egreso_fn'][$edad] ?? ''; ?></td>
            <td><?php echo $datos['egreso_fi'][$edad] ?? ''; ?></td>
        </tr>
        <?php endforeach; ?>

        <tr>
            <th colspan="4">Resumen General</th>
            <th colspan="16" class="text-left" style="padding-left: 5px;">Observaciones Relevantes</th>
        </tr>
        <tr>
            <th>V</th>
            <td><?php echo $datos['resumen_v_1'] ?? ''; ?></td>
            <td colspan="2"><?php echo $datos['resumen_v_2'] ?? ''; ?></td>
            <td colspan="16" rowspan="3" style="vertical-align: top; text-align: left; padding: 5px; background-color: #fff;">
                <?php echo nl2br(htmlspecialchars($datos['observaciones'] ?? '')); ?>
                <div style="text-align: right; margin-top: 15px; padding-right: 20px;"><strong>Director(a)</strong></div>
            </td>
        </tr>
        <tr>
            <th>H</th>
            <td><?php echo $datos['resumen_h_1'] ?? ''; ?></td>
            <td colspan="2"><?php echo $datos['resumen_h_2'] ?? ''; ?></td>
        </tr>
        <tr>
            <th>Total</th>
            <td><?php echo $datos['resumen_total_1'] ?? ''; ?></td>
            <td colspan="2"><?php echo $datos['resumen_total_2'] ?? ''; ?></td>
        </tr>
    </table>
</body>
</html>