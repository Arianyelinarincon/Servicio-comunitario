<?php
require_once "config_db.php";
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// ========== RECIBIR DATOS ==========
$periodo = $_POST['periodo'] ?? date('Y-m');
$sala = $_POST['sala'] ?? '';
$seccion_id = $_POST['seccion'] ?? 0;
$nombre_docente = $_POST['nombre_docente'] ?? '';
$turno = $_POST['turno'] ?? 'Mañana';
$observaciones = $_POST['observaciones'] ?? '';

// MATRÍCULA INICIAL DESDE EL FORMULARIO
$mat_v = (int)($_POST['mat_v'] ?? 0);
$mat_h = (int)($_POST['mat_h'] ?? 0);
$mat_total = $mat_v + $mat_h;
 // PORCENTAJES DE MATRÍCULA ENVIADOS DESDE INDEX.PHP
$porc_v = (int)($_POST['porcentaje_v'] ?? 0);
$porc_h = (int)($_POST['porcentaje_h'] ?? 0);
$porc_total = (int)($_POST['porcentaje_total'] ?? 100);

$anio = date('Y', strtotime($periodo));
$mes_num = date('m', strtotime($periodo));
$dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes_num, $anio);

// ========== CLASIFICACIÓN (desde POST) ==========
$venezolano_v = $_POST['venezolano_v'] ?? [];
$venezolano_h = $_POST['venezolano_h'] ?? [];
$extranjero_v = $_POST['extranjero_v'] ?? [];
$extranjero_h = $_POST['extranjero_h'] ?? [];

// ========== INGRESOS / EGRESOS ==========
$ingreso_apellido = $_POST['ingreso_apellido'] ?? [];
$ingreso_nombre = $_POST['ingreso_nombre'] ?? [];
$ingreso_genero = $_POST['ingreso_genero'] ?? [];
$ingreso_ci = $_POST['ingreso_ci'] ?? [];
$ingreso_fn = $_POST['ingreso_fn'] ?? [];
$ingreso_fi = $_POST['ingreso_fi'] ?? [];

$egreso_apellido = $_POST['egreso_apellido'] ?? [];
$egreso_nombre = $_POST['egreso_nombre'] ?? [];
$egreso_genero = $_POST['egreso_genero'] ?? [];
$egreso_ci = $_POST['egreso_ci'] ?? [];
$egreso_fn = $_POST['egreso_fn'] ?? [];
$egreso_fi = $_POST['egreso_fi'] ?? [];

// Combinar nombres
$ingresos_display = [];
foreach ($ingreso_apellido as $idx => $apellido) {
    if (trim($apellido) === '') continue;
    $nombre_completo = trim(($ingreso_nombre[$idx] ?? '') . ' ' . $apellido);
    $ingresos_display[] = [
        'nombre' => $nombre_completo,
        'genero' => $ingreso_genero[$idx] ?? '',
        'ci' => $ingreso_ci[$idx] ?? '',
        'fn' => $ingreso_fn[$idx] ?? '',
        'fi' => $ingreso_fi[$idx] ?? ''
    ];
}
$egresos_display = [];
foreach ($egreso_apellido as $idx => $apellido) {
    if (trim($apellido) === '') continue;
    $nombre_completo = trim(($egreso_nombre[$idx] ?? '') . ' ' . $apellido);
    $egresos_display[] = [
        'nombre' => $nombre_completo,
        'genero' => $egreso_genero[$idx] ?? '',
        'ci' => $egreso_ci[$idx] ?? '',
        'fn' => $egreso_fn[$idx] ?? '',
        'fi' => $egreso_fi[$idx] ?? ''
    ];
}

// ========== ASISTENCIA DIARIA (desde BD) ==========
$asistencia_v = array_fill(1, $dias_en_mes, 0);
$asistencia_h = array_fill(1, $dias_en_mes, 0);
for ($d = 1; $d <= $dias_en_mes; $d++) {
    $fecha = "$anio-$mes_num-" . str_pad($d, 2, '0', STR_PAD_LEFT);
    $stmt = $conexion->prepare("SELECT cantidad FROM asistencia_diaria WHERE fecha = ? AND sala = ? AND seccion_id = ? AND genero = 'V'");
    $stmt->bind_param("ssi", $fecha, $sala, $seccion_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $asistencia_v[$d] = (int)($res['cantidad'] ?? 0);
    $stmt->close();

    $stmt = $conexion->prepare("SELECT cantidad FROM asistencia_diaria WHERE fecha = ? AND sala = ? AND seccion_id = ? AND genero = 'H'");
    $stmt->bind_param("ssi", $fecha, $sala, $seccion_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $asistencia_h[$d] = (int)($res['cantidad'] ?? 0);
    $stmt->close();
}
$total_v = array_sum($asistencia_v);
$total_h = array_sum($asistencia_h);
$total_asistencia = $total_v + $total_h;

// ========== DÍAS HÁBILES ==========
$dias_habiles = 0;
for ($d = 1; $d <= $dias_en_mes; $d++) {
    $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
    if ($n_dia != 0 && $n_dia != 6) $dias_habiles++;
}

// Ausencias (con valores de matrícula del formulario)
$capacidad_v = $mat_v * $dias_habiles;
$capacidad_h = $mat_h * $dias_habiles;
$ausencias_v = $capacidad_v - $total_v;
$ausencias_h = $capacidad_h - $total_h;
$ausencias_total = $ausencias_v + $ausencias_h;

$porcentaje_total = $mat_total > 0 ? round(($total_asistencia / ($mat_total * $dias_habiles)) * 100) : 0;

// ========== PERÍODO ESCOLAR DINÁMICO ==========
$mes_actual_num = (int)$mes_num;
if ($mes_actual_num >= 1 && $mes_actual_num <= 8) {
    $periodo_inicio = $anio - 1;
    $periodo_fin = $anio;
} else {
    $periodo_inicio = $anio;
    $periodo_fin = $anio + 1;
}

// ========== MES EN ESPAÑOL ==========
$meses_es = [
    "01" => "Enero", "02" => "Febrero", "03" => "Marzo", "04" => "Abril",
    "05" => "Mayo", "06" => "Junio", "07" => "Julio", "08" => "Agosto",
    "09" => "Septiembre", "10" => "Octubre", "11" => "Noviembre", "12" => "Diciembre"
];
$nombre_mes_es = $meses_es[$mes_num] . ' ' . $anio;

// ========== RANGO DE EDADES SEGÚN GRADO (CORREGIDO) ==========
$sala_limpia = strtolower(trim($sala));
if ($sala_limpia === 'sala4') {
    $edades = range(4, 6);
} elseif ($sala_limpia === 'sala5') {
    $edades = range(4, 6);
} else {
    // Primaria
    $edades = range(6, 15);
}

// ========== FUNCIONES AUXILIARES ==========
function mostrarValor($v) {
    if ($v === null || $v === '' || $v === 0) return '';
    return $v;
}

function porcentaje($numerador, $denominador, $dias) {
    if ($denominador <= 0 || $dias <= 0) return '0%';
    return round(($numerador / ($denominador * $dias)) * 100) . '%';
}

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: letter landscape; margin: 8mm; }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; text-align: center; vertical-align: middle; padding: 3px; }
        th { font-size: 11px; background-color: #f2f2f2; }
        .header-title { font-size: 14px; font-weight: bold; line-height: 1.3; }
        .linea { border-bottom: 1px solid #000; display: inline-block; min-width: 70px; height: 18px; }
        .text-left { text-align: left; }
        .no-border td, .no-border { border: none !important; }
        .fin-semana { background-color: #e8f4ff; border-bottom: 2px solid #0080ff; }
        .tabla-clasificacion td, .tabla-clasificacion th { font-size: 10px; }
        .resumen-general td, .resumen-general th { font-size: 10px; padding: 2px; }
        .header-box { border: 1px solid #000; text-align: center; font-size: 11px; line-height: 1.3; height: auto; padding: 4px; }
        .header-right { font-size: 10px; line-height: 1.8; padding-left: 10px; }
        .titulo { text-align: center; font-weight: bold; font-size: 13px; border-top: 1px solid #000; border-bottom: 1px solid #000; margin: 5px 0; padding: 3px; }
        .obs { text-align: left; padding: 4px; vertical-align: top; }
    </style>
</head>
<body>

<!-- ENCABEZADO INSTITUCIONAL + DATOS DOCENTE -->
<table style="margin-bottom: 3px;">
    <tr>
        <td style="width: 50%; border: none;">
            <div class="header-box">
                <strong>República Bolivariana de Venezuela</strong><br>
                Ministerio del Poder Popular para la Educación<br>
                E.B.N. "Juan Pablo Pérez Alfonzo"<br>
                Maracaibo Estado Zulia<br>
                Periodo Escolar <?= $periodo_inicio ?> - <?= $periodo_fin ?>
            </div>
        </td>
        <td style="width: 50%; border: none;">
            <div class="header-right">
                Docente: <span class="line" style="min-width: 180px;"><?= htmlspecialchars($nombre_docente) ?></span><br>
                Grado: <span class="line"><?= htmlspecialchars($sala) ?></span>
                Sección: <span class="line"><?= htmlspecialchars($seccion_id) ?></span><br>
                Turno: <span class="line"><?= htmlspecialchars($turno) ?></span>
                Días Hábiles: <span class="line"><?= $dias_habiles ?></span><br>
                Promedio de Asistencia: <span class="line"><?= $porcentaje_total ?>%</span><br>
                Mes: <span class="line"><?= $nombre_mes_es ?></span><br>
                Matrícula Inicial: V <span class="line"><?= $mat_v ?></span>
                H <span class="line"><?= $mat_h ?></span>
                Total <span class="line"><?= $mat_total ?></span>
            </div>
        </td>
    </tr>
</table>

<div class="titulo">RESUMEN ESTADÍSTICO MENSUAL</div>

<!-- TABLA DE ASISTENCIA -->
<?php
$letras_dias = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
?>
<table style="table-layout: fixed; margin-bottom: 5px;">
    <colgroup>
    <col style="width: 5%;"> <!-- Columna "SEXO" -->
    <?php for ($i = 1; $i <= $dias_en_mes; $i++) echo '<col style="width: 2.5%;">'; ?>
    <col style="width: 5%;"> <!-- TOTAL -->
    <col style="width: 5%;"> <!-- % -->
</colgroup>
    <thead>
        <tr>
            <th>Nº</th>
            <?php for ($i = 1; $i <= $dias_en_mes; $i++) echo "<th>$i</th>"; ?>
            <th>Total</th><th>%</th>
        </tr>
    </thead>
    <tbody>
        <!-- Letras de los días -->
        <tr>
            <th>D</th>
            <?php for ($d = 1; $d <= $dias_en_mes; $d++):
                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                $clase = ($n_dia == 0 || $n_dia == 6) ? 'fin-semana' : '';
            ?>
                <td class="<?= $clase ?>"><?= $letras_dias[$n_dia] ?></td>
            <?php endfor; ?>
            <td colspan="2"></td>
        </tr>
        <!-- V -->
        <tr>
            <th>V</th>
            <?php for ($d = 1; $d <= $dias_en_mes; $d++):
                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                $clase = ($n_dia == 0 || $n_dia == 6) ? 'fin-semana' : '';
                $valor = ($asistencia_v[$d] != 0) ? $asistencia_v[$d] : '';
            ?>
                <td class="<?= $clase ?>"><?= $valor ?></td>
            <?php endfor; ?>
            <td><?= ($total_v != 0) ? $total_v : '' ?></td>
           <td><?= $porc_v ?>%</td>
        </tr>
        <!-- H -->
        <tr>
            <th>H</th>
            <?php for ($d = 1; $d <= $dias_en_mes; $d++):
                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                $clase = ($n_dia == 0 || $n_dia == 6) ? 'fin-semana' : '';
                $valor = ($asistencia_h[$d] != 0) ? $asistencia_h[$d] : '';
            ?>
                <td class="<?= $clase ?>"><?= $valor ?></td>
            <?php endfor; ?>
            <td><?= ($total_h != 0) ? $total_h : '' ?></td>
            <td><?= $porc_h ?>%</td>
        </tr>
        <!-- Total por día -->
        <tr>
            <th>Total</th>
            <?php for ($d = 1; $d <= $dias_en_mes; $d++):
                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                $clase = ($n_dia == 0 || $n_dia == 6) ? 'fin-semana' : '';
                $total_dia = $asistencia_v[$d] + $asistencia_h[$d];
                $valor = ($total_dia != 0) ? $total_dia : '';
            ?>
                <td class="<?= $clase ?>"><?= $valor ?></td>
            <?php endfor; ?>
            <td><?= ($total_asistencia != 0) ? $total_asistencia : '' ?></td>
            <td><strong><?= $porc_total ?>%</strong></td>
        </tr>
    </tbody>
</table>

<!-- TABLA DE CLASIFICACIÓN + INGRESOS/EGRESOS -->
<table style="margin-bottom: 5px;">
   <colgroup>
    <col style="width: 5%;"> <!-- Columna "SEXO" -->
    <?php for ($i = 1; $i <= $dias_en_mes; $i++) echo '<col style="width: 2.5%;">'; ?>
    <col style="width: 5%;"> <!-- TOTAL -->
    <col style="width: 5%;"> <!-- % -->
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
            <th rowspan="2">Apellido y Nombre</th><th rowspan="2">V</th><th rowspan="2">H</th>
            <th rowspan="2">CI o CE</th><th rowspan="2">F.N</th><th rowspan="2">F.I</th>
            <th rowspan="2">Apellido y Nombre</th><th rowspan="2">V</th><th rowspan="2">H</th>
            <th rowspan="2">CI o CE</th><th rowspan="2">F.N</th><th rowspan="2">F.I</th>
        </tr>
        <tr>
            <th>Edad</th><th>V</th><th>H</th><th>Total</th>
            <th>Edad</th><th>V</th><th>H</th><th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $max_filas = max(count($edades), count($ingresos_display), count($egresos_display));
        for ($i = 0; $i < $max_filas; $i++) {
            $edad = $edades[$i] ?? '';
            $ven_v = ($edad && isset($venezolano_v[$edad]) && $venezolano_v[$edad] != 0) ? $venezolano_v[$edad] : '';
            $ven_h = ($edad && isset($venezolano_h[$edad]) && $venezolano_h[$edad] != 0) ? $venezolano_h[$edad] : '';
            $ven_total = ($ven_v != '' || $ven_h != '') ? ((int)$ven_v + (int)$ven_h) : '';
            $ext_v = ($edad && isset($extranjero_v[$edad]) && $extranjero_v[$edad] != 0) ? $extranjero_v[$edad] : '';
            $ext_h = ($edad && isset($extranjero_h[$edad]) && $extranjero_h[$edad] != 0) ? $extranjero_h[$edad] : '';
            $ext_total = ($ext_v != '' || $ext_h != '') ? ((int)$ext_v + (int)$ext_h) : '';
            
            $ing = $ingresos_display[$i] ?? null;
            $eg = $egresos_display[$i] ?? null;
        ?>
        <tr>
            <td><?= $edad ?></td>
            <td><?= mostrarValor($ven_v) ?></td>
            <td><?= mostrarValor($ven_h) ?></td>
            <td><?= mostrarValor($ven_total) ?></td>
            <td><?= $edad ?></td>
            <td><?= mostrarValor($ext_v) ?></td>
            <td><?= mostrarValor($ext_h) ?></td>
            <td><?= mostrarValor($ext_total) ?></td>

            <td class="text-left"><?= $ing ? htmlspecialchars($ing['nombre']) : '' ?></td>
            <td><?= $ing ? ($ing['genero'] == 'V' ? 'X' : '') : '' ?></td>
            <td><?= $ing ? ($ing['genero'] == 'H' ? 'X' : '') : '' ?></td>
            <td><?= $ing ? htmlspecialchars($ing['ci']) : '' ?></td>
            <td><?= $ing ? htmlspecialchars($ing['fn']) : '' ?></td>
            <td><?= $ing ? htmlspecialchars($ing['fi']) : '' ?></td>

            <td class="text-left"><?= $eg ? htmlspecialchars($eg['nombre']) : '' ?></td>
            <td><?= $eg ? ($eg['genero'] == 'V' ? 'X' : '') : '' ?></td>
            <td><?= $eg ? ($eg['genero'] == 'H' ? 'X' : '') : '' ?></td>
            <td><?= $eg ? htmlspecialchars($eg['ci']) : '' ?></td>
            <td><?= $eg ? htmlspecialchars($eg['fn']) : '' ?></td>
            <td><?= $eg ? htmlspecialchars($eg['fi']) : '' ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<!-- SECCIÓN INFERIOR: RESUMEN GENERAL + OBSERVACIONES + FIRMA -->
<table style="width: 100%; margin-top: 4px; border-collapse: collapse;">
    <tr>
        <td style="width: 20%; vertical-align: top; border: none;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr><th colspan="2" style="border: 1px solid #000;">Resumen General</th></tr>
                <tr><td style="border: 1px solid #000; text-align:left;">V</td><td style="border: 1px solid #000; text-align:center;"><?= $mat_v ?></td></tr>
                <tr><td style="border: 1px solid #000; text-align:left;">H</td><td style="border: 1px solid #000; text-align:center;"><?= $mat_h ?></td></tr>
                <tr><td style="border: 1px solid #000; text-align:left;">Total</td><td style="border: 1px solid #000; text-align:center;"><?= $mat_total ?></td></tr>
            </table>
        </td>
        <td style="width: 65%; vertical-align: top; border: none;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr><th style="border: 1px solid #000;">Observaciones Relevantes</th></tr>
                <tr><td class="obs" style="border: 1px solid #000; text-align:left;"><?= nl2br(htmlspecialchars($observaciones)) ?></td></tr>
            </table>
        </td>
        <td style="width: 15%; vertical-align: bottom; text-align: center; border: none;">
            <br><br><br><br><br>
            _________________<br>
            <strong>Director(a)</strong>
        </td>
    </tr>
</table>

</body>
</html>

<?php
$html = ob_get_clean();

require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

$nombre_sala = str_replace(['sala4','sala5','1ro','2do','3ro','4to','5to','6to'],
                           ['Sala4','Sala5','1ro','2do','3ro','4to','5to','6to'], $sala);
$nombre_seccion = $seccion_id;
$nombre_docente_limpio = preg_replace('/[^a-zA-Z0-9]/', '_', $nombre_docente);
$mes_ano = date('m-Y', strtotime($periodo));
$nombre_archivo = "asistencia_{$nombre_sala}_{$nombre_seccion}_{$nombre_docente_limpio}_{$mes_ano}.pdf";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'landscape');
$dompdf->render();
// ========== GUARDAR RESUMEN ESTADÍSTICO EN LA BD ==========
// Asegurar que las variables tengan valor por defecto si no existen
$periodo_sql = date("Y-m-01", strtotime($periodo ?? date('Y-m')));
$sala = $sala ?? '';
$seccion_id = $seccion_id ?? 0;
$docente_id = $profesor_id ?? 0; // En tu código puede llamarse $profesor_id
$mat_v = $mat_v ?? 0;
$mat_h = $mat_h ?? 0;
$total_v = $total_v ?? 0;
$total_h = $total_h ?? 0;
$porcentaje_total = $porcentaje_total ?? 0;
$observaciones = $observaciones ?? '';

// Construir JSON de clasificación por edad
$clasificacion = [];
if (isset($edades) && is_array($edades)) {
    foreach ($edades as $edad) {
        $ven_v = $venezolano_v[$edad] ?? 0;
        $ven_h = $venezolano_h[$edad] ?? 0;
        $ext_v = $extranjero_v[$edad] ?? 0;
        $ext_h = $extranjero_h[$edad] ?? 0;
        $clasificacion[$edad] = [
            'venezolanos' => ['V' => $ven_v, 'H' => $ven_h, 'total' => $ven_v + $ven_h],
            'extranjeros' => ['V' => $ext_v, 'H' => $ext_h, 'total' => $ext_v + $ext_h]
        ];
    }
}
$datos_clasificacion_json = json_encode($clasificacion, JSON_UNESCAPED_UNICODE);

// Ingresos y egresos a JSON
$ingresos_json = json_encode($ingresos_display ?? [], JSON_UNESCAPED_UNICODE);
$egresos_json = json_encode($egresos_display ?? [], JSON_UNESCAPED_UNICODE);

// Preparar consulta (la tabla ya debe existir)
$stmt_guardar = $conexion->prepare("INSERT INTO resumen_estadistico 
    (periodo, sala, seccion_id, docente_id, matricula_v, matricula_h, 
     total_asistencia_v, total_asistencia_h, porcentaje_asistencia, 
     datos_clasificacion, ingresos, egresos, observaciones)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    matricula_v = VALUES(matricula_v), 
    matricula_h = VALUES(matricula_h),
    total_asistencia_v = VALUES(total_asistencia_v),
    total_asistencia_h = VALUES(total_asistencia_h),
    porcentaje_asistencia = VALUES(porcentaje_asistencia),
    datos_clasificacion = VALUES(datos_clasificacion),
    ingresos = VALUES(ingresos),
    egresos = VALUES(egresos),
    observaciones = VALUES(observaciones)");

$stmt_guardar->bind_param("ssiiiiiddssss", 
    $periodo_sql, $sala, $seccion_id, $docente_id, $mat_v, $mat_h,
    $total_v, $total_h, $porcentaje_total,
    $datos_clasificacion_json, $ingresos_json, $egresos_json, $observaciones);
$stmt_guardar->execute();
$stmt_guardar->close();
$dompdf->stream($nombre_archivo, array('Attachment' => 0));
?>