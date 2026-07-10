<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';

// ========== SI VIENE CON ID ==========
$id_resumen = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_resumen == 0) {
    header("Location: historial_resumenes.php");
    exit();
}

// ========== OBTENER DATOS DEL RESUMEN ==========
$stmt = $conexion->prepare("SELECT * FROM resumen_estadistico WHERE id = ?");
$stmt->bind_param("i", $id_resumen);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resumen) {
    header("Location: historial_resumenes.php");
    exit();
}

// Obtener datos del docente
$stmt = $conexion->prepare("SELECT nombre FROM profesores WHERE id = ?");
$stmt->bind_param("i", $resumen['docente_id']);
$stmt->execute();
$docente = $stmt->get_result()->fetch_assoc();
$stmt->close();
$nombre_docente = $docente['nombre'] ?? 'No definido';

// Obtener nombre de la sección
$stmt = $conexion->prepare("SELECT nombre FROM secciones WHERE id = ?");
$stmt->bind_param("i", $resumen['seccion_id']);
$stmt->execute();
$seccion = $stmt->get_result()->fetch_assoc();
$stmt->close();
$nombre_seccion = $seccion['nombre'] ?? 'N/A';

// ========== OBTENER ASISTENCIA DIARIA ==========
$periodo = $resumen['periodo'];
$sala = $resumen['sala'];
$seccion_id = $resumen['seccion_id'];
$anio = date('Y', strtotime($periodo));
$mes_num = date('m', strtotime($periodo));
$dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes_num, $anio);

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

$mat_v = $resumen['matricula_v'];
$mat_h = $resumen['matricula_h'];
$total_v = $resumen['total_asistencia_v'];
$total_h = $resumen['total_asistencia_h'];
$porcentaje = $resumen['porcentaje_asistencia'];
$observaciones = $resumen['observaciones'] ?? '';

// Días hábiles
$dias_habiles = 0;
for ($d = 1; $d <= $dias_en_mes; $d++) {
    $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
    if ($n_dia != 0 && $n_dia != 6) $dias_habiles++;
}

$meses_es = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombre_mes = $meses_es[$mes_num] ?? '';

// ========== OBTENER CLASIFICACIÓN ==========
$datos_clasificacion = json_decode($resumen['datos_clasificacion'], true);
$ingresos_display = json_decode($resumen['ingresos'], true) ?: [];
$egresos_display = json_decode($resumen['egresos'], true) ?: [];

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

$sala_limpia = strtolower(trim($sala));
$edades = ($sala_limpia === 'sala4' || $sala_limpia === 'sala5') ? range(4, 6) : range(6, 15);

// ========== GUARDAR CAMBIOS ==========
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $periodo = $_POST['periodo'];
    $sala = $_POST['sala'];
    $seccion_id = (int)$_POST['seccion'];
    $docente_id = (int)$_POST['docente_id'];
    $observaciones = $_POST['observaciones'];
    $mat_v = (int)$_POST['mat_v'];
    $mat_h = (int)$_POST['mat_h'];
    $asist_v = $_POST['asist_v'] ?? [];
    $asist_h = $_POST['asist_h'] ?? [];
    
    $venezolano_v = $_POST['venezolano_v'] ?? [];
    $venezolano_h = $_POST['venezolano_h'] ?? [];
    $extranjero_v = $_POST['extranjero_v'] ?? [];
    $extranjero_h = $_POST['extranjero_h'] ?? [];
    
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
    
    $anio = date('Y', strtotime($periodo));
    $mes = date('m', strtotime($periodo));
    $dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
    
    // Guardar asistencia diaria
    for ($d = 1; $d <= $dias_en_mes; $d++) {
        $fecha = "$anio-$mes-" . str_pad($d, 2, '0', STR_PAD_LEFT);
        $v = (int)($asist_v[$d] ?? 0);
        $h = (int)($asist_h[$d] ?? 0);
        
        $stmt = $conexion->prepare("INSERT INTO asistencia_diaria (fecha, sala, seccion_id, genero, cantidad) VALUES (?, ?, ?, 'V', ?) ON DUPLICATE KEY UPDATE cantidad = ?");
        $stmt->bind_param("ssiii", $fecha, $sala, $seccion_id, $v, $v);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conexion->prepare("INSERT INTO asistencia_diaria (fecha, sala, seccion_id, genero, cantidad) VALUES (?, ?, ?, 'H', ?) ON DUPLICATE KEY UPDATE cantidad = ?");
        $stmt->bind_param("ssiii", $fecha, $sala, $seccion_id, $h, $h);
        $stmt->execute();
        $stmt->close();
    }
    
    $total_v = array_sum($asist_v);
    $total_h = array_sum($asist_h);
    $total_asistencia = $total_v + $total_h;
    
    $dias_habiles = 0;
    for ($d = 1; $d <= $dias_en_mes; $d++) {
        $n_dia = date('w', strtotime("$anio-$mes-$d"));
        if ($n_dia != 0 && $n_dia != 6) $dias_habiles++;
    }
    
    $porcentaje = ($mat_v + $mat_h) > 0 ? round(($total_asistencia / (($mat_v + $mat_h) * $dias_habiles)) * 100) : 0;
    
    // Construir JSON de clasificación
    $clasificacion = [];
    $edades = ($sala_limpia === 'sala4' || $sala_limpia === 'sala5') ? range(4, 6) : range(6, 15);
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
    
    $datos_clasificacion_json = json_encode($clasificacion, JSON_UNESCAPED_UNICODE);
    $ingresos_json = json_encode($ingresos_display ?? [], JSON_UNESCAPED_UNICODE);
    $egresos_json = json_encode($egresos_display ?? [], JSON_UNESCAPED_UNICODE);
    
    $stmt = $conexion->prepare("UPDATE resumen_estadistico SET 
        matricula_v = ?, 
        matricula_h = ?, 
        total_asistencia_v = ?, 
        total_asistencia_h = ?, 
        porcentaje_asistencia = ?,
        observaciones = ?,
        datos_clasificacion = ?,
        ingresos = ?,
        egresos = ?
        WHERE id = ?");
    $stmt->bind_param("iiiiissssi", $mat_v, $mat_h, $total_v, $total_h, $porcentaje, $observaciones, $datos_clasificacion_json, $ingresos_json, $egresos_json, $id_resumen);
    $stmt->execute();
    $stmt->close();
    
    $mensaje = '<div class="alert alert-success alert-dismissible fade show">✅ Datos actualizados correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    
    // Recargar
    $stmt = $conexion->prepare("SELECT * FROM resumen_estadistico WHERE id = ?");
    $stmt->bind_param("i", $id_resumen);
    $stmt->execute();
    $resumen = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Recargar asistencia
    $mat_v = $resumen['matricula_v'];
    $mat_h = $resumen['matricula_h'];
    $total_v = $resumen['total_asistencia_v'];
    $total_h = $resumen['total_asistencia_h'];
    $porcentaje = $resumen['porcentaje_asistencia'];
    $observaciones = $resumen['observaciones'] ?? '';
}

include '../includes/header.php';
?>

<style>
    :root { --navy: #002d54; --weekend-bg: #343a40; }
    .card { border-radius: 12px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .card-header { background: var(--navy) !important; color: white; }
    .table-asistencia { font-size: 0.75rem; text-align: center; }
    .table-asistencia th { vertical-align: middle; padding: 4px !important; border-color: #dee2e6; }
    .table-asistencia td { vertical-align: middle; padding: 4px !important; }
    .weekend { background-color: var(--weekend-bg) !important; color: white !important; }
    .weekend-cell { background-color: #212529 !important; cursor: not-allowed; }
    .asist-input { border: none !important; background: transparent; text-align: center; width: 100%; font-weight: bold; height: 32px; }
    .asist-input:focus { background-color: #fff9c4 !important; outline: none; }
    input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
    .bg-navy { background-color: var(--navy) !important; }
    .matricula-box input { width: 45px; border: 1px solid #ced4da; border-radius: 4px; text-align: center; font-weight: bold; background: #ffffff; color: #000000; }
    .btn-guardar { background-color: #28a745; color: white; font-weight: bold; padding: 10px 30px; font-size: 1.1rem; border-radius: 30px; transition: all 0.3s; border: none; }
    .btn-guardar:hover { background-color: #218838; transform: scale(1.02); }
    .btn-cancelar { background-color: #6c757d; color: white; font-weight: bold; padding: 10px 30px; font-size: 1.1rem; border-radius: 30px; transition: all 0.3s; border: none; }
    .btn-cancelar:hover { background-color: #5a6268; transform: scale(1.02); }
    
    .tabla-dinamica { width: 100%; table-layout: fixed; }
    .tabla-dinamica th, .tabla-dinamica td { vertical-align: middle; padding: 5px; }
    .tabla-dinamica input, .tabla-dinamica select { width: 100%; box-sizing: border-box; }
    .col-nombre { width: 27%; }
    .col-genero { width: 8%; }
    .col-nacionalidad { width: 10%; }
    .col-ci { width: 12%; min-width: 110px; }
    .col-fecha { width: 10%; }
    .col-accion { width: 5%; }
    
    .tabla-edades { table-layout: fixed; }
    .tabla-edades td, .tabla-edades th { vertical-align: middle; text-align: center; }
    .col-edad { width: 8%; }
    .col-pequeno { width: 6%; }
    .col-mediano { width: 10%; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-navy"><i class="fas fa-edit"></i> Editar Resumen - <?= $nombre_mes ?> <?= $anio ?></h4>
        <div>
            <a href="historial_resumenes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Historial
            </a>
            <a href="generar_pdf.php?id=<?= $id_resumen ?>" class="btn btn-success" target="_blank">
                <i class="fas fa-file-pdf"></i> Ver PDF
            </a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <?= $mensaje ?>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body bg-light">
            <div class="row">
                <div class="col-md-3"><strong>Docente:</strong> <?= htmlspecialchars($nombre_docente) ?></div>
                <div class="col-md-3"><strong>Grado:</strong> <?= htmlspecialchars($sala) ?></div>
                <div class="col-md-3"><strong>Sección:</strong> <?= htmlspecialchars($nombre_seccion) ?></div>
                <div class="col-md-3"><strong>Mes:</strong> <?= $nombre_mes ?> <?= $anio ?></div>
            </div>
        </div>
    </div>

    <form method="POST" id="form-editar">
        <input type="hidden" name="guardar" value="1">
        <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
        <input type="hidden" name="sala" value="<?= htmlspecialchars($sala) ?>">
        <input type="hidden" name="seccion" value="<?= htmlspecialchars($seccion_id) ?>">
        <input type="hidden" name="docente_id" value="<?= htmlspecialchars($resumen['docente_id']) ?>">
        <input type="hidden" id="tipo_reporte" name="tipo_reporte" value="regular">

        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="m-0 fw-bold text-uppercase">CONTROL DE ASISTENCIA: <?= strtoupper($nombre_mes) ?> <?= $anio ?></h6>
                    <small class="opacity-75">Docente: <?= strtoupper($nombre_docente) ?></small>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <button type="button" class="btn btn-sm btn-danger fw-bold" onclick="limpiarTodo()">LIMPIAR</button>
                    <div class="bg-white text-dark px-3 py-1 rounded small fw-bold matricula-box">
                        Matrícula: V <input type="number" min="0" name="mat_v" id="mat_v" value="<?= $mat_v ?>" oninput="recalcular()"> 
                        H <input type="number" min="0" name="mat_h" id="mat_h" value="<?= $mat_h ?>" oninput="recalcular()">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 table-asistencia">
                    <thead class="bg-light">
                        <tr>
                            <th rowspan="2" width="50">SEXO</th>
                            <?php for($d=1; $d<=$dias_en_mes; $d++) {
                                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                                $es_fin = ($n_dia == 0 || $n_dia == 6);
                                $letra = ['D','L','M','M','J','V','S'][$n_dia];
                                echo "<th class='".($es_fin ? 'weekend' : '')."' style='font-size:0.7rem;'>$letra<br><small>".$d."</small></th>";
                            } ?>
                            <th rowspan="2" class="bg-primary text-white">TOTAL</th>
                            <th rowspan="2" class="bg-success text-white">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold bg-light">V</td>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): 
                                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                                $es_fin = ($n_dia == 0 || $n_dia == 6); ?>
                                <td class="<?= $es_fin ? 'weekend-cell' : 'p-0' ?>">
                                    <?php if(!$es_fin): ?>
                                        <input type="number" min="0" name="asist_v[<?= $d ?>]" class="asist-input in-v" data-dia="<?= $d ?>" value="<?= $asistencia_v[$d] ?>" oninput="recalcular()" onblur="limpiarCero(this)">
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td id="res_total_v" class="fw-bold bg-primary text-white"><?= $total_v ?></td>
                            <td id="res_porc_v" class="fw-bold text-primary">0%</td>
                        </tr>
                        <tr>
                            <td class="fw-bold bg-light">H</td>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): 
                                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                                $es_fin = ($n_dia == 0 || $n_dia == 6); ?>
                                <td class="<?= $es_fin ? 'weekend-cell' : 'p-0' ?>">
                                    <?php if(!$es_fin): ?>
                                        <input type="number" min="0" name="asist_h[<?= $d ?>]" class="asist-input in-h" data-dia="<?= $d ?>" value="<?= $asistencia_h[$d] ?>" oninput="recalcular()" onblur="limpiarCero(this)">
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td id="res_total_h" class="fw-bold bg-primary text-white"><?= $total_h ?></td>
                            <td id="res_porc_h" class="fw-bold text-primary">0%</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="bg-navy text-white">TOTAL</td>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): ?>
                                <td id="total_dia_<?= $d ?>" class="bg-navy text-white">-</td>
                            <?php endfor; ?>
                            <td id="gran_total_asist" class="bg-dark text-white fw-bold"><?= $total_v + $total_h ?></td>
                            <td id="gran_total_porc" class="bg-dark text-white fw-bold"><?= $porcentaje ?>%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                <input type="hidden" id="dias_hab_val" value="<?= $dias_habiles ?>">
                <span class="text-muted small">Días Hábiles: <b><?= $dias_habiles ?></b></span>
                <span class="h6 mb-0 text-navy">Promedio Diario: <b id="promedio_total"><?= $dias_habiles > 0 ? round(($total_v + $total_h) / $dias_habiles, 1) : 0 ?></b></span>
            </div>
        </div>

        <!-- ========== CLASIFICACIÓN POR EDAD ========== -->
        <div class="card mb-4">
            <div class="card-header bg-navy text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Clasificación por Nacionalidad, Edad y Sexo</h6>
                <span class="small">Los totales se calculan automáticamente</span>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm tabla-edades" style="table-layout:fixed;">
                        <colgroup>
                            <col class="col-edad"><col class="col-pequeno"><col class="col-pequeno"><col class="col-mediano">
                            <col class="col-edad"><col class="col-pequeno"><col class="col-pequeno"><col class="col-mediano">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th colspan="4">Venezolano</th>
                                <th colspan="4">Extranjero</th>
                            </tr>
                            <tr>
                                <th>Edad</th><th>V</th><th>H</th><th>Total</th>
                                <th>Edad</th><th>V</th><th>H</th><th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($edades as $edad): 
                                $ven_v = $venezolano_v[$edad] ?? 0;
                                $ven_h = $venezolano_h[$edad] ?? 0;
                                $ext_v = $extranjero_v[$edad] ?? 0;
                                $ext_h = $extranjero_h[$edad] ?? 0;
                            ?>
                            <tr class="fila-edad">
                                <td><?= $edad ?></td>
                                <td><input type="number" class="form-control form-control-sm text-center ven-v" name="venezolano_v[<?= $edad ?>]" data-edad="<?= $edad ?>" value="<?= $ven_v ?>" oninput="calcularTotalFila(this, 'venezolano')" onblur="limpiarCero(this)"></td>
                                <td><input type="number" class="form-control form-control-sm text-center ven-h" name="venezolano_h[<?= $edad ?>]" data-edad="<?= $edad ?>" value="<?= $ven_h ?>" oninput="calcularTotalFila(this, 'venezolano')" onblur="limpiarCero(this)"></td>
                                <td><input type="text" class="form-control form-control-sm text-center ven-total" name="venezolano_total[<?= $edad ?>]" readonly></td>
                                <td><?= $edad ?></td>
                                <td><input type="number" class="form-control form-control-sm text-center ext-v" name="extranjero_v[<?= $edad ?>]" data-edad="<?= $edad ?>" value="<?= $ext_v ?>" oninput="calcularTotalFila(this, 'extranjero')" onblur="limpiarCero(this)"></td>
                                <td><input type="number" class="form-control form-control-sm text-center ext-h" name="extranjero_h[<?= $edad ?>]" data-edad="<?= $edad ?>" value="<?= $ext_h ?>" oninput="calcularTotalFila(this, 'extranjero')" onblur="limpiarCero(this)"></td>
                                <td><input type="text" class="form-control form-control-sm text-center ext-total" name="extranjero_total[<?= $edad ?>]" readonly></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== INGRESO/EGRESO ========== -->
        <div class="card mb-4">
            <div class="card-header bg-navy text-white">
                <h6 class="mb-0">Ingreso / Egreso del Mes</h6>
            </div>
            <div class="card-body p-3">
                <div class="mb-4">
                    <h6 class="text-primary">Ingresos</h6>
                    <table class="table table-sm table-bordered tabla-dinamica" id="tablaIngresos">
                        <colgroup>
                            <col class="col-nombre"><col class="col-genero"><col class="col-nacionalidad"><col class="col-ci"><col class="col-fecha"><col class="col-fecha"><col class="col-accion">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Apellido y Nombre</th><th>Género</th><th>Nacionalidad</th><th>CI o CE</th><th>F.N</th><th>F.I</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ingresos_display)): ?>
                                <?php foreach($ingresos_display as $ing): ?>
                                <tr class="fila-ingreso">
                                    <td>
                                        <input type="text" name="ingreso_apellido[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ing['nombre'] ?? '') ?>" placeholder="Apellido">
                                        <input type="text" name="ingreso_nombre[]" class="form-control form-control-sm mt-1" placeholder="Nombre">
                                    </td>
                                    <td>
                                        <select name="ingreso_genero[]" class="form-select form-select-sm">
                                            <option value="V" <?= ($ing['genero'] == 'V') ? 'selected' : '' ?>>Varón (V)</option>
                                            <option value="H" <?= ($ing['genero'] == 'H') ? 'selected' : '' ?>>Hembra (H)</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="ingreso_nacionalidad[]" class="form-select form-select-sm">
                                            <option value="Venezolana">Venezolana</option>
                                            <option value="Extranjera">Extranjera</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="ingreso_ci[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ing['ci'] ?? '') ?>" placeholder="Cédula" maxlength="11"></td>
                                    <td><input type="text" name="ingreso_fn[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ing['fn'] ?? '') ?>" placeholder="DD/MM/YYYY"></td>
                                    <td><input type="text" name="ingreso_fi[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ing['fi'] ?? '') ?>" placeholder="DD/MM/YYYY"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="fila-ingreso">
                                    <td>
                                        <input type="text" name="ingreso_apellido[]" class="form-control form-control-sm" placeholder="Apellido">
                                        <input type="text" name="ingreso_nombre[]" class="form-control form-control-sm mt-1" placeholder="Nombre">
                                    </td>
                                    <td>
                                        <select name="ingreso_genero[]" class="form-select form-select-sm">
                                            <option value="V">Varón (V)</option>
                                            <option value="H">Hembra (H)</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="ingreso_nacionalidad[]" class="form-select form-select-sm">
                                            <option value="Venezolana">Venezolana</option>
                                            <option value="Extranjera">Extranjera</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="ingreso_ci[]" class="form-control form-control-sm" placeholder="Cédula" maxlength="11"></td>
                                    <td><input type="text" name="ingreso_fn[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY"></td>
                                    <td><input type="text" name="ingreso_fi[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-light mt-2" onclick="agregarFila('ingreso')">+ Agregar Ingreso</button>
                </div>
                <div>
                    <h6 class="text-danger">Egresos</h6>
                    <table class="table table-sm table-bordered tabla-dinamica" id="tablaEgresos">
                        <colgroup>
                            <col class="col-nombre"><col class="col-genero"><col class="col-ci"><col class="col-fecha"><col class="col-fecha"><col class="col-accion">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Apellido y Nombre</th><th>Género</th><th>CI o CE</th><th>F.N</th><th>F.I</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($egresos_display)): ?>
                                <?php foreach($egresos_display as $eg): ?>
                                <tr class="fila-egreso">
                                    <td>
                                        <input type="text" name="egreso_apellido[]" class="form-control form-control-sm" value="<?= htmlspecialchars($eg['nombre'] ?? '') ?>" placeholder="Apellido">
                                        <input type="text" name="egreso_nombre[]" class="form-control form-control-sm mt-1" placeholder="Nombre">
                                    </td>
                                    <td>
                                        <select name="egreso_genero[]" class="form-select form-select-sm">
                                            <option value="V" <?= ($eg['genero'] == 'V') ? 'selected' : '' ?>>Varón (V)</option>
                                            <option value="H" <?= ($eg['genero'] == 'H') ? 'selected' : '' ?>>Hembra (H)</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="egreso_ci[]" class="form-control form-control-sm" value="<?= htmlspecialchars($eg['ci'] ?? '') ?>" placeholder="Cédula" maxlength="11"></td>
                                    <td><input type="text" name="egreso_fn[]" class="form-control form-control-sm" value="<?= htmlspecialchars($eg['fn'] ?? '') ?>" placeholder="DD/MM/YYYY"></td>
                                    <td><input type="text" name="egreso_fi[]" class="form-control form-control-sm" value="<?= htmlspecialchars($eg['fi'] ?? '') ?>" placeholder="DD/MM/YYYY"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="fila-egreso">
                                    <td>
                                        <input type="text" name="egreso_apellido[]" class="form-control form-control-sm" placeholder="Apellido">
                                        <input type="text" name="egreso_nombre[]" class="form-control form-control-sm mt-1" placeholder="Nombre">
                                    </td>
                                    <td>
                                        <select name="egreso_genero[]" class="form-select form-select-sm">
                                            <option value="V">Varón (V)</option>
                                            <option value="H">Hembra (H)</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="egreso_ci[]" class="form-control form-control-sm" placeholder="Cédula" maxlength="11"></td>
                                    <td><input type="text" name="egreso_fn[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY"></td>
                                    <td><input type="text" name="egreso_fi[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-light mt-2" onclick="agregarFila('egreso')">+ Agregar Egreso</button>
                </div>
            </div>
        </div>

        <!-- Observaciones -->
        <div class="card mb-4">
            <div class="card-header bg-navy text-white"><h6 class="mb-0">Observaciones Relevantes</h6></div>
            <div class="card-body">
                <textarea name="observaciones" class="form-control" rows="4" placeholder="Escriba aquí las observaciones..."><?= htmlspecialchars($observaciones) ?></textarea>
                <div class="text-end mt-2"><small>Director(a)</small></div>
            </div>
        </div>

        <div class="text-center mt-4 mb-4">
            <button type="submit" class="btn btn-guardar px-5 py-2">
                💾 GUARDAR CAMBIOS
            </button>
            <a href="historial_resumenes.php" class="btn btn-cancelar px-5 py-2">
                ❌ CANCELAR
            </a>
        </div>
    </form>
</div>

<script>
function limpiarCero(input) {
    if (input.value === '0') {
        input.value = '';
    }
}

function limpiarTodo() {
    document.querySelectorAll('.in-v, .in-h').forEach(input => input.value = '');
    window.recalcular();
}

window.recalcular = function() {
    const diasHabilesEl = document.getElementById('dias_hab_val');
    const matVEl = document.getElementById('mat_v');
    const matHEl = document.getElementById('mat_h');
    
    const diasHabilesVal = diasHabilesEl ? parseInt(diasHabilesEl.value) || 0 : 0;
    let matVVal = matVEl ? (parseInt(matVEl.value) || 0) : 0;
    let matHVal = matHEl ? (parseInt(matHEl.value) || 0) : 0;
    
    let totalV = 0, totalH = 0;
    const totalesDia = {};
    
    document.querySelectorAll('.in-v').forEach(input => {
        let val = parseInt(input.value);
        if (isNaN(val)) val = 0;
        totalV += val;
        const dia = input.dataset.dia;
        totalesDia[dia] = (totalesDia[dia] || 0) + val;
    });
    
    document.querySelectorAll('.in-h').forEach(input => {
        let val = parseInt(input.value);
        if (isNaN(val)) val = 0;
        totalH += val;
        const dia = input.dataset.dia;
        totalesDia[dia] = (totalesDia[dia] || 0) + val;
    });
    
    document.getElementById('res_total_v').textContent = totalV;
    document.getElementById('res_total_h').textContent = totalH;
    document.getElementById('gran_total_asist').textContent = totalV + totalH;
    
    const totalGeneral = totalV + totalH;
    const porcV = totalGeneral ? Math.round((totalV / totalGeneral) * 100) : 0;
    const porcH = totalGeneral ? Math.round((totalH / totalGeneral) * 100) : 0;
    
    document.getElementById('res_porc_v').textContent = porcV + '%';
    document.getElementById('res_porc_h').textContent = porcH + '%';
    document.getElementById('gran_total_porc').textContent = '100%';
    
    const promedio = diasHabilesVal > 0 ? (totalGeneral / diasHabilesVal).toFixed(1) : '0.0';
    const promedioTotalEl = document.getElementById('promedio_total');
    if (promedioTotalEl) promedioTotalEl.textContent = promedio;
    
    for(let d = 1; d <= 31; d++) {
        const td = document.getElementById('total_dia_' + d);
        if (td) td.textContent = (totalesDia[d] !== undefined) ? totalesDia[d] : '-';
    }
};

window.calcularTotalFila = function(input, tipo) {
    const fila = input.closest('tr');
    if (tipo === 'venezolano') {
        let v = parseInt(fila.querySelector('.ven-v')?.value);
        let h = parseInt(fila.querySelector('.ven-h')?.value);
        if (isNaN(v)) v = 0;
        if (isNaN(h)) h = 0;
        const total = v + h;
        fila.querySelector('.ven-total').value = total;
    } else if (tipo === 'extranjero') {
        let v = parseInt(fila.querySelector('.ext-v')?.value);
        let h = parseInt(fila.querySelector('.ext-h')?.value);
        if (isNaN(v)) v = 0;
        if (isNaN(h)) h = 0;
        const total = v + h;
        fila.querySelector('.ext-total').value = total;
    }
};

function agregarFila(tipo) {
    const tbody = document.querySelector(`#tabla${tipo === 'ingreso' ? 'Ingresos' : 'Egresos'} tbody`);
    const fila = document.createElement('tr');
    fila.className = `fila-${tipo}`;
    
    if (tipo === 'ingreso') {
        fila.innerHTML = `
            <td>
                <input type="text" name="ingreso_apellido[]" class="form-control form-control-sm" placeholder="Apellido">
                <input type="text" name="ingreso_nombre[]" class="form-control form-control-sm mt-1" placeholder="Nombre">
            </td>
            <td>
                <select name="ingreso_genero[]" class="form-select form-select-sm">
                    <option value="V">Varón (V)</option>
                    <option value="H">Hembra (H)</option>
                </select>
            </td>
            <td>
                <select name="ingreso_nacionalidad[]" class="form-select form-select-sm">
                    <option value="Venezolana">Venezolana</option>
                    <option value="Extranjera">Extranjera</option>
                </select>
            </td>
            <td><input type="text" name="ingreso_ci[]" class="form-control form-control-sm" placeholder="Cédula" maxlength="11"></td>
            <td><input type="text" name="ingreso_fn[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY"></td>
            <td><input type="text" name="ingreso_fi[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY"></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button></td>
        `;
    } else {
        fila.innerHTML = `
            <td>
                <input type="text" name="egreso_apellido[]" class="form-control form-control-sm" placeholder="Apellido">
                <input type="text" name="egreso_nombre[]" class="form-control form-control-sm mt-1" placeholder="Nombre">
            </td>
            <td>
                <select name="egreso_genero[]" class="form-select form-select-sm">
                    <option value="V">Varón (V)</option>
                    <option value="H">Hembra (H)</option>
                </select>
            </td>
            <td><input type="text" name="egreso_ci[]" class="form-control form-control-sm" placeholder="Cédula" maxlength="11"></td>
            <td><input type="text" name="egreso_fn[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY"></td>
            <td><input type="text" name="egreso_fi[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY"></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button></td>
        `;
    }
    tbody.appendChild(fila);
}

document.addEventListener('DOMContentLoaded', function() {
    window.recalcular();
    document.querySelectorAll('.ven-v, .ven-h').forEach(inp => calcularTotalFila(inp, 'venezolano'));
    document.querySelectorAll('.ext-v, .ext-h').forEach(inp => calcularTotalFila(inp, 'extranjero'));
});
</script>

<?php include '../includes/footer.php'; ?>