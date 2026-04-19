<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen Estadístico Mensual</title>
    <style>
        /* Configuración para Dompdf y visualización web */
        @page { 
            size: letter landscape; /* Formato Carta Horizontal */
            margin: 10mm; 
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px; 
            margin: 0; 
            padding: 0; 
            background: #e0e0e0; /* Fondo gris para la web */
            display: flex; 
            flex-direction: column;
            align-items: center; 
        }
        /* Contenedor que simula la hoja física */
        .hoja { 
            background: white; 
            width: 279mm; 
            height: 215mm; 
            padding: 15px; 
            box-sizing: border-box; 
            box-shadow: 0 0 10px rgba(0,0,0,0.3); 
            margin: 20px 0; 
        }
        /* Estilos generales de tablas */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 5px; 
        }
        th, td { 
            border: 1px solid black; 
            text-align: center; 
            padding: 1px; 
            font-weight: normal; 
            height: 18px; /* Altura de celda para que quepa todo */
        }
        th { font-weight: bold; }
        .no-border, .no-border th, .no-border td { 
            border: none; 
            text-align: left; 
            padding: 2px;
        }
        
        /* Textos y cabeceras */
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        .header-title { font-size: 11px; line-height: 1.3; }
        
        /* Inputs mimetizados con el papel */
        .input-line { 
            border-bottom: 1px solid black; 
            border-top: none; 
            border-left: none; 
            border-right: none; 
            outline: none; 
            background: transparent; 
            font-size: 11px; 
            font-family: Arial, sans-serif; 
        }
        .cell-input { 
            width: 100%; 
            height: 100%; 
            border: none; 
            outline: none; 
            text-align: center; 
            font-size: 10px; 
            background: transparent; 
            box-sizing: border-box; 
        }
        .cell-input.text-left { text-align: left; padding-left: 4px; }
        .cell-textarea { 
            width: 100%; 
            height: 45px; 
            border: none; 
            outline: none; 
            resize: none; 
            font-size: 10px; 
            font-family: Arial, sans-serif; 
            background: transparent;
        }

        /* Estilo del botón */
        .btn-descargar {
            padding: 12px 24px; 
            font-size: 16px; 
            cursor: pointer; 
            background-color: #28a745; 
            color: white; 
            border: none; 
            border-radius: 5px;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-descargar:hover { background-color: #218838; }

        /* Ocultar sombras y botón al imprimir o generar PDF */
        @media print { 
            body { background: white; display: block; } 
            .hoja { box-shadow: none; margin: 0; padding: 0; width: 100%; height: 100%; } 
            .btn-descargar { display: none; }

        }
                @page { 
            size: letter landscape; 
            margin: 5mm; /* Reducimos el margen del papel al mínimo */
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px; /* Si sigue muy grande, bájalo a 9px */
            margin: 0; 
            padding: 0; 
            /* ELIMINA el display: flex y el justify-content de aquí */
        }
        .hoja { 
            width: 100%; /* En lugar de 279mm, usamos el 100% del espacio disponible */
            margin: 0; 
            padding: 0; 
            box-shadow: none; /* Quitamos la sombra para el PDF */
            }
            table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; /* Esto obliga a la tabla a no salirse de la hoja */
            margin-bottom: 5px; 
            }
            /* Opcional: Hacer que los inputs se ajusten perfectamente */
            .cell-input {
            width: 100%;
            box-sizing: border-box;
            }
            
            </style>
</head>
<body>

<form action="generar_pdf.php" method="POST" style="width: 100%; display: flex; flex-direction: column; align-items: center;">

    <div class="hoja">
        <table class="no-border" style="margin-bottom: 5px;">
            <tr>
                <td class="no-border header-title text-center" width="45%">
                    Republica Bolivariana de Venezuela<br>
                    Ministerio del Poder Popular para la Educacion<br>
                    E.B.N. "Juan Pablo Perez Alfonzo"<br>
                    Maracaibo Estado Zulia<br>
                    Periodo Escolar 20 <input type="text" name="periodo_inicio" class="input-line text-center" style="width:20px;"> - 20 <input type="text" name="periodo_fin" class="input-line text-center" style="width:20px;">
                </td>
                <td class="no-border" width="55%" style="font-size: 11px; line-height: 1.6; padding-left: 40px;">
                    Docente: <input type="text" name="docente" class="input-line" style="width: 280px;"><br>
                    Grado: <input type="text" name="grado" class="input-line" style="width: 80px;">
                    Sección: <input type="text" name="seccion" class="input-line" style="width: 80px;"><br>
                    Turno: <input type="text" name="turno" class="input-line" style="width: 120px;">
                    Dias Habiles: <input type="text" name="dias_habiles" class="input-line" style="width: 60px;"><br>
                    Promedio de Asistencia: <input type="text" name="promedio_asistencia" class="input-line" style="width: 100px;"><br>
                    Mes: <input type="text" name="mes" class="input-line" style="width: 150px;"><br>
                    Matricula Inicial: 
                    V <input type="text" name="matricula_v" class="input-line text-center" style="width: 30px;">
                    H <input type="text" name="matricula_h" class="input-line text-center" style="width: 30px;">
                    Total <input type="text" name="matricula_total" class="input-line text-center" style="width: 40px;">
                </td>
            </tr>
        </table>

        <div style="text-align: center; border-top: 1px solid black; border-bottom: 1px solid black; padding: 2px 0; font-size: 11px; font-weight: bold; margin-bottom: 5px;">
            Resumen Estadistico Mensual
        </div>

        <table>
            <tr>
                <th width="2%">Nº</th>
                <?php for($i=1; $i<=31; $i++): ?>
                    <th width="2.8%"><?php echo $i; ?></th>
                <?php endfor; ?>
                <th width="4%">Total</th>
                <th width="4%">%</th>
            </tr>
            <tr>
                <th>D</th>
                <?php for($i=1; $i<=31; $i++): ?>
                    <td><input type="text" name="dias_letra[<?php echo $i; ?>]" class="cell-input" maxlength="1"></td>
                <?php endfor; ?>
                <td><input type="text" name="dias_total" class="cell-input"></td>
                <td><input type="text" name="dias_porcentaje" class="cell-input"></td>
            </tr>
            <tr>
                <th>V</th>
                <?php for($i=1; $i<=33; $i++): ?>
                    <td><input type="text" name="asistencia_v[<?php echo $i; ?>]" class="cell-input"></td>
                <?php endfor; ?>
            </tr>
            <tr>
                <th>H</th>
                <?php for($i=1; $i<=33; $i++): ?>
                    <td><input type="text" name="asistencia_h[<?php echo $i; ?>]" class="cell-input"></td>
                <?php endfor; ?>
            </tr>
            <tr>
                <th>Total</th>
                <?php for($i=1; $i<=33; $i++): ?>
                    <td><input type="text" name="asistencia_total[<?php echo $i; ?>]" class="cell-input"></td>
                <?php endfor; ?>
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
                <th rowspan="2" width="3%">V</th>
                <th rowspan="2" width="3%">H</th>
                <th rowspan="2" width="7%">CI o CE</th>
                <th rowspan="2" width="3%">F.N</th>
                <th rowspan="2" width="3%">F.I</th>
                <th rowspan="2" width="16%">Apellido y Nombre</th>
                <th rowspan="2" width="3%">V</th>
                <th rowspan="2" width="3%">H</th>
                <th rowspan="2" width="7%">CI o CE</th>
                <th rowspan="2" width="3%">F.N</th>
                <th rowspan="2" width="3%">F.I</th>
            </tr>
            <tr>
                <th width="4%">Edad</th><th width="3%">V</th><th width="3%">H</th><th width="5%">Total</th>
                <th width="4%">Edad</th><th width="3%">V</th><th width="3%">H</th><th width="5%">Total</th>
            </tr>
            
            <?php for($edad=5; $edad<=15; $edad++): ?>
            <tr>
                <td><?php echo $edad; ?></td>
                <td><input type="text" name="venezolano_v[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="venezolano_h[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="venezolano_total[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><?php echo $edad; ?></td>
                <td><input type="text" name="extranjero_v[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="extranjero_h[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="extranjero_total[<?php echo $edad; ?>]" class="cell-input"></td>
                
                <td><input type="text" name="ingreso_apellido[<?php echo $edad; ?>]" class="cell-input text-left"></td>
                <td><input type="text" name="ingreso_v[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="ingreso_h[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="ingreso_ci[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="ingreso_fn[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="ingreso_fi[<?php echo $edad; ?>]" class="cell-input"></td>
                
                <td><input type="text" name="egreso_apellido[<?php echo $edad; ?>]" class="cell-input text-left"></td>
                <td><input type="text" name="egreso_v[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="egreso_h[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="egreso_ci[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="egreso_fn[<?php echo $edad; ?>]" class="cell-input"></td>
                <td><input type="text" name="egreso_fi[<?php echo $edad; ?>]" class="cell-input"></td>
            </tr>
            <?php endfor; ?>

            <tr>
                <th colspan="4">Resumen General</th>
                <th colspan="16" class="text-left" style="padding-left: 5px;">Observaciones Relevantes</th>
            </tr>
            <tr>
                <th>V</th>
                <td><input type="text" name="resumen_v_1" class="cell-input"></td>
                <td colspan="2"><input type="text" name="resumen_v_2" class="cell-input"></td>
                <td colspan="16" rowspan="3" style="vertical-align: top; text-align: left; padding: 5px; position: relative;">
                    <textarea name="observaciones" class="cell-textarea" placeholder="Escribe las observaciones aquí..."></textarea>
                    <div style="text-align: right; margin-top: 5px; padding-right: 20px;">Director(a)</div>
                </td>
            </tr>
            <tr>
                <th>H</th>
                <td><input type="text" name="resumen_h_1" class="cell-input"></td>
                <td colspan="2"><input type="text" name="resumen_h_2" class="cell-input"></td>
            </tr>
            <tr>
                <th>Total</th>
                <td><input type="text" name="resumen_total_1" class="cell-input"></td>
                <td colspan="2"><input type="text" name="resumen_total_2" class="cell-input"></td>
            </tr>
        </table>
    </div>

    <button type="submit" class="btn-descargar">Convertir a PDF</button>

</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Seleccionamos los campos que activarán el cálculo automático
    const inputsAsistencia = document.querySelectorAll('input[name^="asistencia_v"], input[name^="asistencia_h"]');
    const inputsConfiguracion = document.querySelectorAll('input[name="dias_habiles"], input[name="matricula_v"], input[name="matricula_h"]');

    function calcularTodo() {
        let totalGeneralV = 0;
        let totalGeneralH = 0;

        // 2. Extraer los datos base del encabezado
        let diasHabiles = parseFloat(document.querySelector('input[name="dias_habiles"]').value) || 0;
        let matriculaV = parseFloat(document.querySelector('input[name="matricula_v"]').value) || 0;
        let matriculaH = parseFloat(document.querySelector('input[name="matricula_h"]').value) || 0;
        let matriculaTotal = matriculaV + matriculaH;

        // Auto-completar la Matrícula Total en el encabezado
        if(matriculaTotal > 0) {
            document.querySelector('input[name="matricula_total"]').value = matriculaTotal;
        } else {
            document.querySelector('input[name="matricula_total"]').value = '';
        }

        // 3. Sumar los 31 días
        for(let i = 1; i <= 31; i++) {
            let valV = parseFloat(document.querySelector(`input[name="asistencia_v[${i}]"]`).value) || 0;
            let valH = parseFloat(document.querySelector(`input[name="asistencia_h[${i}]"]`).value) || 0;
            
            totalGeneralV += valV;
            totalGeneralH += valH;

            // Suma vertical diaria (Total de niños que fueron ese día específico)
            let sumaDia = valV + valH;
            if(sumaDia > 0) {
                document.querySelector(`input[name="asistencia_total[${i}]"]`).value = sumaDia;
            } else {
                document.querySelector(`input[name="asistencia_total[${i}]"]`).value = '';
            }
        }

        // 4. Escribir los Totales Acumulados del Mes en la columna "Total" (la penúltima)
        document.querySelector(`input[name="asistencia_v[32]"]`).value = totalGeneralV || '';
        document.querySelector(`input[name="asistencia_h[32]"]`).value = totalGeneralH || '';
        document.querySelector(`input[name="asistencia_total[32]"]`).value = (totalGeneralV + totalGeneralH) || '';

        // 5. Calcular los Porcentajes comparados a la Matrícula Inicial
        // IMPORTANTE: Solo se calcula si el docente ya escribió los "Días Hábiles" en el encabezado
        if (diasHabiles > 0) {
            
            // % Varones
            if (matriculaV > 0) {
                let porcV = ((totalGeneralV / (matriculaV * diasHabiles)) * 100).toFixed(2);
                document.querySelector(`input[name="asistencia_v[33]"]`).value = porcV + '%';
            }
            
            // % Hembras
            if (matriculaH > 0) {
                let porcH = ((totalGeneralH / (matriculaH * diasHabiles)) * 100).toFixed(2);
                document.querySelector(`input[name="asistencia_h[33]"]`).value = porcH + '%';
            }
            
            // % Total General del Mes
            if (matriculaTotal > 0) {
                let porcTotal = (((totalGeneralV + totalGeneralH) / (matriculaTotal * diasHabiles)) * 100).toFixed(2);
                document.querySelector(`input[name="asistencia_total[33]"]`).value = porcTotal + '%';
                
                // Actualizar automáticamente el espacio "Promedio de Asistencia" del encabezado
                document.querySelector('input[name="promedio_asistencia"]').value = porcTotal + '%';
            }
            
        } else {
            // Si borran los días hábiles, limpiamos los porcentajes para no mostrar errores
            document.querySelector(`input[name="asistencia_v[33]"]`).value = '';
            document.querySelector(`input[name="asistencia_h[33]"]`).value = '';
            document.querySelector(`input[name="asistencia_total[33]"]`).value = '';
        }
    }

    // 6. Activar la función cada vez que se teclee un número nuevo
    inputsAsistencia.forEach(input => input.addEventListener('input', calcularTodo));
    inputsConfiguracion.forEach(input => input.addEventListener('input', calcularTodo));
});
</script>                
  
</body>
</html>