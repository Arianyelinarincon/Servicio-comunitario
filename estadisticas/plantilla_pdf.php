<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* 1. Configuración del papel */
    @page { 
        size: letter landscape; 
        margin: 8mm; /* Margen pequeño para aprovechar espacio */
    }

    body { 
        font-family: Arial, sans-serif; 
        font-size: 9px; /* Bajamos un punto el tamaño para asegurar espacio */
        margin: 0; 
        padding: 0; 
        background: white; 
    }

    /* 2. Contenedor principal sin altura fija */
    .hoja { 
        width: 100%; 
        margin: 0; 
        padding: 0; 
    }

    /* 3. Control estricto de tablas */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-bottom: 4px; 
        table-layout: fixed; 
        /* Evita que la tabla salte a otra página si no cabe un pedazo */
        page-break-inside: avoid; 
    }

    th, td { 
        border: 1px solid black; 
        text-align: center; 
        padding: 1px; 
        height: 14px; /* Altura más compacta para que quepan las 11 filas de edades */
        overflow: hidden;
    }

    /* 4. Clases de utilidad */
    .no-border, .no-border th, .no-border td { border: none; text-align: left; }
    .text-center { text-align: center !important; }
    .text-left { text-align: left !important; }
    
    .dato-linea { 
        border-bottom: 1px solid black; 
        display: inline-block; 
        text-align: center; 
    }

    /* Forzamos que las filas de las tablas no se estiren */
    tr { 
        page-break-inside: avoid; 
        page-break-after: auto; 
    }
    </style>
</head>
<body>

<div class="hoja">
    <table class="no-border" style="margin-bottom: 5px;">
        <tr>
            <td class="no-border text-center" width="45%" style="font-size: 11px; line-height: 1.3;">
                Republica Bolivariana de Venezuela<br>
                Ministerio del Poder Popular para la Educacion<br>
                E.B.N. "Juan Pablo Perez Alfonzo"<br>
                Maracaibo Estado Zulia<br>
                Periodo Escolar 20 <span class="dato-linea" style="width:20px;"><?php echo $datos['periodo_inicio'] ?? ''; ?></span> - 20 <span class="dato-linea" style="width:20px;"><?php echo $datos['periodo_fin'] ?? ''; ?></span>
            </td>
            <td class="no-border" width="55%" style="font-size: 11px; line-height: 1.6; padding-left: 40px;">
                Docente: <span class="dato-linea" style="width: 280px; text-align: left; padding-left: 5px;"><?php echo $datos['docente'] ?? ''; ?></span><br>
                Grado: <span class="dato-linea" style="width: 80px;"><?php echo $datos['grado'] ?? ''; ?></span>
                Sección: <span class="dato-linea" style="width: 80px;"><?php echo $datos['seccion'] ?? ''; ?></span><br>
                Turno: <span class="dato-linea" style="width: 120px;"><?php echo $datos['turno'] ?? ''; ?></span>
                Dias Habiles: <span class="dato-linea" style="width: 60px;"><?php echo $datos['dias_habiles'] ?? ''; ?></span><br>
                Promedio de Asistencia: <span class="dato-linea" style="width: 100px;"><?php echo $datos['promedio_asistencia'] ?? ''; ?></span><br>
                Mes: <span class="dato-linea" style="width: 150px; text-align: left; padding-left: 5px;"><?php echo $datos['mes'] ?? ''; ?></span><br>
                Matricula Inicial: 
                V <span class="dato-linea" style="width: 30px;"><?php echo $datos['matricula_v'] ?? ''; ?></span>
                H <span class="dato-linea" style="width: 30px;"><?php echo $datos['matricula_h'] ?? ''; ?></span>
                Total <span class="dato-linea" style="width: 40px;"><?php echo $datos['matricula_total'] ?? ''; ?></span>
            </td>
        </tr>
    </table>

    <div style="text-align: center; border-top: 1px solid black; border-bottom: 1px solid black; padding: 2px 0; font-size: 11px; font-weight: bold; margin-bottom: 5px;">
        Resumen Estadistico Mensual
    </div>

    <table>
        <tr>
            <th width="2%">Nº</th>
            <?php for($i=1; $i<=31; $i++): ?><th width="2.8%"><?php echo $i; ?></th><?php endfor; ?>
            <th width="4%">Total</th><th width="4%">%</th>
        </tr>
        <tr>
            <th>D</th>
            <?php 
            // LÓGICA PARA LOS DÍAS DE LA SEMANA
            $anio_actual = date('Y', strtotime($datos['mes']));
            $mes_actual = date('m', strtotime($datos['mes']));
            
            for($i=1; $i<=31; $i++): 
                $letra = "";
                if(checkdate($mes_actual, $i, $anio_actual)){
                    $fecha_temp = "$anio_actual-$mes_actual-" . str_pad($i, 2, "0", STR_PAD_LEFT);
                    $n_dia = date('w', strtotime($fecha_temp));
                    $letra = ['D','L','M','M','J','V','S'][$n_dia];
                }
            ?>
                <td><span class="dato-celda"><?php echo $letra; ?></span></td>
            <?php endfor; ?>
            <td><span class="dato-celda"><?php echo $datos['dias_total'] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['dias_porcentaje'] ?? ''; ?></span></td>
        </tr>
        <tr>
            <th>V</th>
            <?php for($i=1; $i<=31; $i++): ?><td><span class="dato-celda"><?php echo $datos['asistencia_v'][$i] ?? ''; ?></span></td><?php endfor; ?>
            <td colspan="2"></td>
        </tr>
        <tr>
            <th>H</th>
            <?php for($i=1; $i<=31; $i++): ?><td><span class="dato-celda"><?php echo $datos['asistencia_h'][$i] ?? ''; ?></span></td><?php endfor; ?>
            <td colspan="2"></td>
        </tr>
        <tr>
            <th>Total</th>
            <?php for($i=1; $i<=31; $i++): ?><td><span class="dato-celda"><?php echo $datos['asistencia_total'][$i] ?? ''; ?></span></td><?php endfor; ?>
            <td colspan="2"></td>
        </tr>
    </table>

    <table>
        <tr>
            <th colspan="8" width="30%">Clasificación por Nacionalidad, Edad y Sexo</th>
            <th colspan="6" width="35%">Ingreso del Mes</th>
            <th colspan="6" width="35%">Egreso del Mes</th>
        </tr>
        <tr>
            <th colspan="4">Venezolano</th>
            <th colspan="4">Extranjero</th>
            <th rowspan="2" width="16%">Apellido y Nombre</th>
            <th rowspan="2" width="3%">V</th><th rowspan="2" width="3%">H</th><th rowspan="2" width="7%">CI o CE</th><th rowspan="2" width="3%">F.N</th><th rowspan="2" width="3%">F.I</th>
            <th rowspan="2" width="16%">Apellido y Nombre</th>
            <th rowspan="2" width="3%">V</th><th rowspan="2" width="3%">H</th><th rowspan="2" width="7%">CI o CE</th><th rowspan="2" width="3%">F.N</th><th rowspan="2" width="3%">F.I</th>
        </tr>
        <tr>
            <th width="4%">Edad</th><th width="3%">V</th><th width="3%">H</th><th width="5%">Total</th>
            <th width="4%">Edad</th><th width="3%">V</th><th width="3%">H</th><th width="5%">Total</th>
        </tr>
        
        <?php for($edad=5; $edad<=15; $edad++): ?>
        <tr>
            <td><?php echo $edad; ?></td>
            <td><span class="dato-celda"><?php echo $datos['venezolano_v'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['venezolano_h'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['venezolano_total'][$edad] ?? ''; ?></span></td>
            <td><?php echo $edad; ?></td>
            <td><span class="dato-celda"><?php echo $datos['extranjero_v'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['extranjero_h'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['extranjero_total'][$edad] ?? ''; ?></span></td>
            
            <td class="text-left"><span class="dato-celda"><?php echo $datos['ingreso_apellido'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['ingreso_v'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['ingreso_h'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['ingreso_ci'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['ingreso_fn'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['ingreso_fi'][$edad] ?? ''; ?></span></td>
            
            <td class="text-left"><span class="dato-celda"><?php echo $datos['egreso_apellido'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['egreso_v'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['egreso_h'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['egreso_ci'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['egreso_fn'][$edad] ?? ''; ?></span></td>
            <td><span class="dato-celda"><?php echo $datos['egreso_fi'][$edad] ?? ''; ?></span></td>
        </tr>
        <?php endfor; ?>

        <tr>
            <th colspan="4">Resumen General</th>
            <th colspan="16" class="text-left" style="padding-left: 5px;">Observaciones Relevantes</th>
        </tr>
        <tr>
            <th>V</th>
            <td><span class="dato-celda"><?php echo $datos['resumen_v_1'] ?? ''; ?></span></td>
            <td colspan="2"><span class="dato-celda"><?php echo $datos['resumen_v_2'] ?? ''; ?></span></td>
            <td colspan="16" rowspan="3" style="vertical-align: top; text-align: left; padding: 5px;">
                <?php echo nl2br(htmlspecialchars($datos['observaciones'] ?? '')); ?>
                <div style="text-align: right; margin-top: 15px; padding-right: 20px;">Director(a)</div>
            </td>
        </tr>
        <tr>
            <th>H</th>
            <td><span class="dato-celda"><?php echo $datos['resumen_h_1'] ?? ''; ?></span></td>
            <td colspan="2"><span class="dato-celda"><?php echo $datos['resumen_h_2'] ?? ''; ?></span></td>
        </tr>
        <tr>
            <th>Total</th>
            <td><span class="dato-celda"><?php echo $datos['resumen_total_1'] ?? ''; ?></span></td>
            <td colspan="2"><span class="dato-celda"><?php echo $datos['resumen_total_2'] ?? ''; ?></span></td>
        </tr>
    </table>
</div>

</body>
</html>