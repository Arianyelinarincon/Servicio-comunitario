<?php
require_once "config_db.php";
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// ========== DETECTAR SI VIENE DEL HISTORIAL (GET) O DEL FORMULARIO (POST) ==========
$desde_historial = isset($_GET['id']) && !empty($_GET['id']);

if ($desde_historial) {
    // ========== CARGAR DATOS DESDE EL HISTORIAL ==========
    $id_resumen = (int)$_GET['id'];
    
    $stmt = $conexion->prepare("SELECT * FROM resumen_estadistico WHERE id = ?");
    $stmt->bind_param("i", $id_resumen);
    $stmt->execute();
    $resumen = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$resumen) {
        die("Resumen no encontrado");
    }
    
    // Asignar variables desde la base de datos
    $periodo = $resumen['periodo'];
    $sala = $resumen['sala'];
    $seccion_id = $resumen['seccion_id'];
    $docente_id = $resumen['docente_id'];
    $mat_v = (int)$resumen['matricula_v'];
    $mat_h = (int)$resumen['matricula_h'];
    $mat_total = $mat_v + $mat_h;
    $total_v = (int)$resumen['total_asistencia_v'];
    $total_h = (int)$resumen['total_asistencia_h'];
    $total_asistencia = $total_v + $total_h;
    $porcentaje_total = (int)$resumen['porcentaje_asistencia'];
    $observaciones = $resumen['observaciones'];
    
    // Decodificar JSONs
    $datos_clasificacion = json_decode($resumen['datos_clasificacion'], true);
    $ingresos_display = json_decode($resumen['ingresos'], true);
    $egresos_display = json_decode($resumen['egresos'], true);
    
    // Obtener nombre del docente
    $nombre_docente = 'No definido';
    $stmt = $conexion->prepare("SELECT nombre FROM profesores WHERE id = ?");
    $stmt->bind_param("i", $docente_id);
    $stmt->execute();
    $prof_data = $stmt->get_result()->fetch_assoc();
    if ($prof_data) {
        $nombre_docente = $prof_data['nombre'];
    }
    $stmt->close();
    
    $turno = 'Mañana';
    $porc_v = 0;
    $porc_h = 0;
    $porc_total = 100;
    
    // Reconstruir arrays de clasificación
    $venezolano_v = [];
    $venezolano_h = [];
    $extranjero_v = [];
    $extranjero_h = [];
    
    if ($datos_clasificacion) {
        foreach ($datos_clasificacion as $edad => $data) {
            $venezolano_v[$edad] = $data['venezolanos']['V'] ?? 0;
            $venezolano_h[$edad] = $data['venezolanos']['H'] ?? 0;
            $extranjero_v[$edad] = $data['extranjeros']['V'] ?? 0;
            $extranjero_h[$edad] = $data['extranjeros']['H'] ?? 0;
        }
    }
    
    // Cargar asistencia diaria desde la BD
    $anio = (int)date('Y', strtotime($periodo));
    $mes_num = (int)date('m', strtotime($periodo));
    $dias_en_mes = (int)cal_days_in_month(CAL_GREGORIAN, $mes_num, $anio);
    
    $asistencia_v = array_fill(1, $dias_en_mes, 0);
    $asistencia_h = array_fill(1, $dias_en_mes, 0);
    
    for ($d = 1; $d <= $dias_en_mes; $d++) {
        $fecha = "$anio-$mes_num-" . str_pad($d, 2, '0', STR_PAD_LEFT);
        
        $stmt = $conexion->prepare("SELECT cantidad FROM asistencia_diaria WHERE fecha = ? AND sala = ? AND seccion_id = ? AND genero = 'V'");
        $stmt->bind_param("ssi", $fecha, $sala, $seccion_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $asistencia_v[$d] = isset($res['cantidad']) ? (int)$res['cantidad'] : 0;
        $stmt->close();

        $stmt = $conexion->prepare("SELECT cantidad FROM asistencia_diaria WHERE fecha = ? AND sala = ? AND seccion_id = ? AND genero = 'H'");
        $stmt->bind_param("ssi", $fecha, $sala, $seccion_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $asistencia_h[$d] = isset($res['cantidad']) ? (int)$res['cantidad'] : 0;
        $stmt->close();
    }
    
    // Días hábiles
    $dias_habiles = 0;
    for ($d = 1; $d <= $dias_en_mes; $d++) {
        $n_dia = (int)date('w', strtotime("$anio-$mes_num-$d"));
        if ($n_dia != 0 && $n_dia != 6) $dias_habiles++;
    }
    
    // Recalcular porcentajes
    if ($mat_total > 0 && $dias_habiles > 0) {
        $porcentaje_total = (int)round(($total_asistencia / ($mat_total * $dias_habiles)) * 100);
    }
    $porc_v = $total_asistencia > 0 ? (int)round(($total_v / $total_asistencia) * 100) : 0;
    $porc_h = $total_asistencia > 0 ? (int)round(($total_h / $total_asistencia) * 100) : 0;
    
    // Ausencias
    $capacidad_v = (int)($mat_v * $dias_habiles);
    $capacidad_h = (int)($mat_h * $dias_habiles);
    $ausencias_v = (int)($capacidad_v - $total_v);
    $ausencias_h = (int)($capacidad_h - $total_h);
    $ausencias_total = $ausencias_v + $ausencias_h;
    
} else {
    // ========== RECIBIR DATOS DESDE POST (FORMULARIO) ==========
    $periodo = $_POST['periodo'] ?? date('Y-m');
    $sala = $_POST['sala'] ?? '';
    $seccion_id = (int)($_POST['seccion'] ?? 0);
    $nombre_docente = $_POST['nombre_docente'] ?? '';
    $turno = $_POST['turno'] ?? 'Mañana';
    $observaciones = $_POST['observaciones'] ?? '';
    
    $profesor_id = isset($_POST['profesor']) ? (int)$_POST['profesor'] : 0;
    $docente_id = $profesor_id;
    
    $mat_v = isset($_POST['mat_v']) ? (int)$_POST['mat_v'] : 0;
    $mat_h = isset($_POST['mat_h']) ? (int)$_POST['mat_h'] : 0;
    $mat_total = $mat_v + $mat_h;
    
    $porc_v = isset($_POST['porcentaje_v']) ? (int)$_POST['porcentaje_v'] : 0;
    $porc_h = isset($_POST['porcentaje_h']) ? (int)$_POST['porcentaje_h'] : 0;
    $porc_total = isset($_POST['porcentaje_total']) ? (int)$_POST['porcentaje_total'] : 100;
    
    $anio = (int)date('Y', strtotime($periodo));
    $mes_num = (int)date('m', strtotime($periodo));
    $dias_en_mes = (int)cal_days_in_month(CAL_GREGORIAN, $mes_num, $anio);
    
    // CLASIFICACIÓN
    $venezolano_v = $_POST['venezolano_v'] ?? [];
    $venezolano_h = $_POST['venezolano_h'] ?? [];
    $extranjero_v = $_POST['extranjero_v'] ?? [];
    $extranjero_h = $_POST['extranjero_h'] ?? [];
    
    // INGRESOS / EGRESOS
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
    
    // ASISTENCIA DIARIA
    $asistencia_v = array_fill(1, $dias_en_mes, 0);
    $asistencia_h = array_fill(1, $dias_en_mes, 0);
    
    for ($d = 1; $d <= $dias_en_mes; $d++) {
        $fecha = "$anio-$mes_num-" . str_pad($d, 2, '0', STR_PAD_LEFT);
        
        $stmt = $conexion->prepare("SELECT cantidad FROM asistencia_diaria WHERE fecha = ? AND sala = ? AND seccion_id = ? AND genero = 'V'");
        $stmt->bind_param("ssi", $fecha, $sala, $seccion_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $asistencia_v[$d] = isset($res['cantidad']) ? (int)$res['cantidad'] : 0;
        $stmt->close();

        $stmt = $conexion->prepare("SELECT cantidad FROM asistencia_diaria WHERE fecha = ? AND sala = ? AND seccion_id = ? AND genero = 'H'");
        $stmt->bind_param("ssi", $fecha, $sala, $seccion_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $asistencia_h[$d] = isset($res['cantidad']) ? (int)$res['cantidad'] : 0;
        $stmt->close();
    }
    
    $total_v = (int)array_sum($asistencia_v);
    $total_h = (int)array_sum($asistencia_h);
    $total_asistencia = $total_v + $total_h;
    
    // Días hábiles
    $dias_habiles = 0;
    for ($d = 1; $d <= $dias_en_mes; $d++) {
        $n_dia = (int)date('w', strtotime("$anio-$mes_num-$d"));
        if ($n_dia != 0 && $n_dia != 6) $dias_habiles++;
    }
    
    // Recalcular porcentaje
    if ($mat_total > 0 && $dias_habiles > 0) {
        $porcentaje_total = (int)round(($total_asistencia / ($mat_total * $dias_habiles)) * 100);
    }
    
    // Ausencias
    $capacidad_v = (int)($mat_v * $dias_habiles);
    $capacidad_h = (int)($mat_h * $dias_habiles);
    $ausencias_v = (int)($capacidad_v - $total_v);
    $ausencias_h = (int)($capacidad_h - $total_h);
    $ausencias_total = $ausencias_v + $ausencias_h;
}

// ========== PERÍODO ESCOLAR ==========
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
$nombre_mes_es = $meses_es[str_pad($mes_num, 2, '0', STR_PAD_LEFT)] . ' ' . $anio;

// ========== EDADES ==========
$sala_limpia = strtolower(trim($sala));
if ($sala_limpia === 'sala4' || $sala_limpia === 'sala5') {
    $edades = range(4, 6);
} else {
    $edades = range(6, 15);
}

// ========== FUNCIONES AUXILIARES ==========
function mostrarValor($v) {
    if ($v === null || $v === '' || $v === 0) return '';
    return $v;
}

// ========== GENERAR HTML ==========
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
        <col style="width: 5%;">
        <?php for ($i = 1; $i <= $dias_en_mes; $i++) echo '<col style="width: 2.5%;">'; ?>
        <col style="width: 5%;">
        <col style="width: 5%;">
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
                $valor = isset($asistencia_v[$d]) && $asistencia_v[$d] != 0 ? $asistencia_v[$d] : '';
            ?>
                <td class="<?= $clase ?>"><?= $valor ?></td>
            <?php endfor; ?>
            <td><strong><?= ($total_v != 0) ? $total_v : '' ?></strong></td>
            <td><?= $porc_v ?>%</td>
        </tr>
        <!-- H -->
        <tr>
            <th>H</th>
            <?php for ($d = 1; $d <= $dias_en_mes; $d++):
                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                $clase = ($n_dia == 0 || $n_dia == 6) ? 'fin-semana' : '';
                $valor = isset($asistencia_h[$d]) && $asistencia_h[$d] != 0 ? $asistencia_h[$d] : '';
            ?>
                <td class="<?= $clase ?>"><?= $valor ?></td>
            <?php endfor; ?>
            <td><strong><?= ($total_h != 0) ? $total_h : '' ?></strong></td>
            <td><?= $porc_h ?>%</td>
        </tr>
        <!-- Total por día -->
        <tr>
            <th>Total</th>
            <?php for ($d = 1; $d <= $dias_en_mes; $d++):
                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                $clase = ($n_dia == 0 || $n_dia == 6) ? 'fin-semana' : '';
                $total_dia = ($asistencia_v[$d] ?? 0) + ($asistencia_h[$d] ?? 0);
                $valor = ($total_dia != 0) ? $total_dia : '';
            ?>
                <td class="<?= $clase ?>"><?= $valor ?></td>
            <?php endfor; ?>
            <td><strong><?= ($total_asistencia != 0) ? $total_asistencia : '' ?></strong></td>
            <td><strong><?= $porc_total ?>%</strong></td>
        </tr>
    </tbody>
</table>

<!-- TABLA DE CLASIFICACIÓN + INGRESOS/EGRESOS -->
<table style="margin-bottom: 5px;">
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
        $max_filas = max(count($edades), count($ingresos_display ?? []), count($egresos_display ?? []));
        for ($i = 0; $i < $max_filas; $i++) {
            $edad = $edades[$i] ?? '';
            $ven_v = ($edad && isset($venezolano_v[$edad]) && $venezolano_v[$edad] != 0) ? $venezolano_v[$edad] : '';
            $ven_h = ($edad && isset($venezolano_h[$edad]) && $venezolano_h[$edad] != 0) ? $venezolano_h[$edad] : '';
            $ven_total = ($ven_v != '' || $ven_h != '') ? ((int)$ven_v + (int)$ven_h) : '';
            $ext_v = ($edad && isset($extranjero_v[$edad]) && $extranjero_v[$edad] != 0) ? $extranjero_v[$edad] : '';
            $ext_h = ($edad && isset($extranjero_h[$edad]) && $extranjero_h[$edad] != 0) ? $extranjero_h[$edad] : '';
            $ext_total = ($ext_v != '' || $ext_h != '') ? ((int)$ext_v + (int)$ext_h) : '';
            
            $ing = isset($ingresos_display[$i]) ? $ingresos_display[$i] : null;
            $eg = isset($egresos_display[$i]) ? $egresos_display[$i] : null;
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
        <td style="width: 18%; vertical-align: top; border: none;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr><th colspan="2" style="border: 1px solid #000; text-align:center;">Resumen General</th></tr>
                <tr><td style="border: 1px solid #000; text-align:left; padding: 2px 5px;">V</td><td style="border: 1px solid #000; text-align:center; padding: 2px 5px;"><?= $mat_v ?></td></tr>
                <tr><td style="border: 1px solid #000; text-align:left; padding: 2px 5px;">H</td><td style="border: 1px solid #000; text-align:center; padding: 2px 5px;"><?= $mat_h ?></td></tr>
                <tr><td style="border: 1px solid #000; text-align:left; padding: 2px 5px; font-weight:bold;">Total</td><td style="border: 1px solid #000; text-align:center; padding: 2px 5px; font-weight:bold;"><?= $mat_total ?></td></tr>
            </table>
        </td>
        <td style="width: 67%; vertical-align: top; border: none;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr><th style="border: 1px solid #000; text-align:center;">Observaciones Relevantes</th></tr>
                <tr><td class="obs" style="border: 1px solid #000; text-align:left; padding: 4px 6px; height: 60px;"><?= nl2br(htmlspecialchars($observaciones)) ?></td></tr>
            </table>
        </td>
        <td style="width: 15%; vertical-align: bottom; text-align: center; border: none;">
            <br><br><br><br>
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

// ========== SI VIENE DEL HISTORIAL, NO VOLVER A GUARDAR ==========
if (!$desde_historial) {
    // Solo guardar si viene del formulario (POST)
    $periodo_sql = date("Y-m-01", strtotime($periodo ?? date('Y-m')));
    
    // Construir JSON de clasificación por edad
    $clasificacion = [];
    if (isset($edades) && is_array($edades)) {
        foreach ($edades as $edad) {
            $ven_v = isset($venezolano_v[$edad]) ? (int)$venezolano_v[$edad] : 0;
            $ven_h = isset($venezolano_h[$edad]) ? (int)$venezolano_h[$edad] : 0;
            $ext_v = isset($extranjero_v[$edad]) ? (int)$extranjero_v[$edad] : 0;
            $ext_h = isset($extranjero_h[$edad]) ? (int)$extranjero_h[$edad] : 0;
            $clasificacion[$edad] = [
                'venezolanos' => ['V' => $ven_v, 'H' => $ven_h, 'total' => $ven_v + $ven_h],
                'extranjeros' => ['V' => $ext_v, 'H' => $ext_h, 'total' => $ext_v + $ext_h]
            ];
        }
    }
    $datos_clasificacion_json = json_encode($clasificacion, JSON_UNESCAPED_UNICODE);
    $ingresos_json = json_encode($ingresos_display ?? [], JSON_UNESCAPED_UNICODE);
    $egresos_json = json_encode($egresos_display ?? [], JSON_UNESCAPED_UNICODE);
    
    // Verificar que la tabla existe
    $check_table = $conexion->query("SHOW TABLES LIKE 'resumen_estadistico'");
    if ($check_table->num_rows === 0) {
        $conexion->query("CREATE TABLE IF NOT EXISTS resumen_estadistico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            periodo DATE NOT NULL,
            sala VARCHAR(20) NOT NULL,
            seccion_id INT NOT NULL,
            docente_id INT NOT NULL,
            matricula_v INT DEFAULT 0,
            matricula_h INT DEFAULT 0,
            total_asistencia_v INT DEFAULT 0,
            total_asistencia_h INT DEFAULT 0,
            porcentaje_asistencia INT DEFAULT 0,
            datos_clasificacion JSON,
            ingresos JSON,
            egresos JSON,
            observaciones TEXT,
            UNIQUE KEY unique_resumen (periodo, sala, seccion_id, docente_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
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
}

$dompdf->stream($nombre_archivo, array('Attachment' => 0));
?>