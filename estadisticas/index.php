<?php
require_once "config_db.php";

// ========== AJAX HANDLER ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['usuario'])) {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action == 'cargar_secciones') {
        $sala = $_POST['sala'] ?? '';
        $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
        $stmt->bind_param("s", $sala);
        $stmt->execute();
        $result = $stmt->get_result();
        $secciones = [];
        while($row = $result->fetch_assoc()) {
            $secciones[] = ['id' => $row['id'], 'nombre' => $row['nombre']];
        }
        echo json_encode(['secciones' => $secciones]);
        $stmt->close();
        exit;
    }
    
    if ($action == 'cargar_docentes') {
        $seccion = (int)$_POST['seccion'];
        $stmt = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? ORDER BY nombre ASC");
        $stmt->bind_param("i", $seccion);
        $stmt->execute();
        $result = $stmt->get_result();
        $docentes = [];
        while($row = $result->fetch_assoc()) {
            $docentes[] = ['id' => $row['id'], 'nombre' => $row['nombre']];
        }
        echo json_encode(['docentes' => $docentes]);
        $stmt->close();
        exit;
    }
    exit;
}

// ========== PROCESAR GUARDADO DE DATOS ==========
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_y_pdf'])) {
    $periodo = $_POST['periodo'];
    $sala = $_POST['sala'];
    $seccion_id = (int)$_POST['seccion'];
    $profesor_id = $_POST['profesor'];
    $nombre_docente = $_POST['nombre_docente'];
    $asist_v = $_POST['asist_v'] ?? [];
    $asist_h = $_POST['asist_h'] ?? [];
    $anio = date('Y', strtotime($periodo));
    $mes = date('m', strtotime($periodo));
    $dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
    
    //$conexion->query("CREATE TABLE IF NOT EXISTS asistencia_diaria (
   //     id INT AUTO_INCREMENT PRIMARY KEY,
   //     fecha DATE NOT NULL,
   //     sala VARCHAR(10) NOT NULL,
   //     seccion_id INT NOT NULL,
   //     genero ENUM('V','H') NOT NULL,
   //     cantidad INT DEFAULT 0,
  //      UNIQUE KEY unique_asistencia (fecha, sala, seccion_id, genero)
 //   )");
    
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
    
    // Guardar ingresos/egresos, etc. (sin cambios)...
    // Al final:
    include "generar_pdf.php";
    exit;
}

include "../includes/header.php";

// Filtros
$sala_seleccionada = isset($_GET['sala']) ? mysqli_real_escape_string($conexion, $_GET['sala']) : '';
$seccion_seleccionada = isset($_GET['seccion']) ? mysqli_real_escape_string($conexion, $_GET['seccion']) : '';
$profesor_id = isset($_GET['profesor']) ? mysqli_real_escape_string($conexion, $_GET['profesor']) : '';
$periodo = isset($_GET['periodo']) ? mysqli_real_escape_string($conexion, $_GET['periodo']) : date('Y-m');

$mostrar_tabla = ($sala_seleccionada && $profesor_id);

if ($mostrar_tabla) {
    $anio = date('Y', strtotime($periodo));
    $mes_num = date('m', strtotime($periodo));
    $dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes_num, $anio);
} else {
    $anio = date('Y');
    $mes_num = date('m');
    $dias_en_mes = 0;
}

$meses_es = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombre_mes = isset($meses_es[$mes_num]) ? $meses_es[$mes_num] : '';

$nombre_profesor = 'No seleccionado';
if ($profesor_id) {
    $stmt_prof = $conexion->prepare("SELECT nombre FROM profesores WHERE id = ?");
    $stmt_prof->bind_param("s", $profesor_id);
    $stmt_prof->execute();
    $prof_data = $stmt_prof->get_result()->fetch_assoc();
    if ($prof_data) {
        $nombre_profesor = htmlspecialchars($prof_data['nombre']);
    }
    $stmt_prof->close();
}

$es_inicial = in_array($sala_seleccionada, ['sala4', 'sala5']);
$edades = $es_inicial ? [4,5,6] : range(6,15);
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
    
    .btn-pdf-final {
        background-color: #dc3545;
        color: white;
        font-weight: bold;
        padding: 10px 30px;
        font-size: 1.1rem;
        border-radius: 30px;
        transition: all 0.3s;
        border: none;
    }
    .btn-pdf-final:hover {
        background-color: #b02a37;
        transform: scale(1.02);
    }
</style>

<div class="container-fluid py-4">


    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <a href="historial_resumenes.php" class="btn btn-info">
        <i class="fas fa-chart-line"></i> Ver Historial de Resúmenes
    </a>
</div>

    <div class="card mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">SALA / GRADO</label>
                    <select name="sala" id="select-grado" class="form-select shadow-none" required onchange="pasoGrado()">
                        <option value="">Seleccione grado...</option>
                        <optgroup label="Educación Inicial">
                            <option value="sala4" <?= ($sala_seleccionada == 'sala4') ? 'selected' : '' ?>>Sala 4 Años</option>
                            <option value="sala5" <?= ($sala_seleccionada == 'sala5') ? 'selected' : '' ?>>Sala 5 Años</option>
                        </optgroup>
                        <optgroup label="Educación Primaria">
                            <?php for($i=1; $i<=6; $i++): 
                                $val = ($i==1) ? "1ro" : (($i==2) ? "2do" : (($i==3) ? "3ro" : $i."to")); ?>
                                <option value="<?= htmlspecialchars($val) ?>" <?= ($sala_seleccionada == $val) ? 'selected' : '' ?>><?= $i ?>° Grado</option>
                            <?php endfor; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-2" id="seccion-seccion">
                    <label class="small fw-bold text-muted">SECCIÓN</label>
                    <select name="seccion" id="select-seccion" class="form-select shadow-none" <?= empty($sala_seleccionada) ? 'disabled' : '' ?> onchange="pasoSeccion()">
                        <option value="">Primero seleccione grado...</option>
                        <?php 
                        if($sala_seleccionada) {
                            $stmt_sec = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
                            $stmt_sec->bind_param("s", $sala_seleccionada);
                            $stmt_sec->execute();
                            $result_sec = $stmt_sec->get_result();
                            while($sec = $result_sec->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($sec['id']) ?>" <?= ($seccion_seleccionada == $sec['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sec['nombre']) ?></option>
                            <?php endwhile;
                            $stmt_sec->close();
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3" id="seccion-docente">
                    <label class="small fw-bold text-muted">PROFESOR / DOCENTE</label>
                    <select name="profesor" id="select-docente" class="form-select shadow-none" <?= empty($seccion_seleccionada) ? 'disabled' : '' ?> required onchange="pasoDocente()">
                        <option value="">Primero seleccione sección...</option>
                        <?php 
                        if($seccion_seleccionada) {
                            $stmt_p = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? ORDER BY nombre ASC");
                            $stmt_p->bind_param("s", $seccion_seleccionada);
                            $stmt_p->execute();
                            $result_p = $stmt_p->get_result();
                            while($p = $result_p->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($p['id']) ?>" <?= ($profesor_id == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre']) ?></option>
                            <?php endwhile; 
                            $stmt_p->close();
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3" id="seccion-mes" style="display:<?= $profesor_id ? 'block' : 'none' ?>;">
                    <label class="small fw-bold text-muted">MES</label>
                    <input type="month" name="periodo" id="select-mes" class="form-control shadow-none" value="<?= htmlspecialchars($periodo) ?>">
                </div>

                <div class="col-md-1" id="seccion-boton" style="display:<?= ($sala_seleccionada && $profesor_id) ? 'block' : 'none' ?>;">
                    <button type="submit" class="btn btn-primary w-100 fw-bold bg-navy border-0" style="padding: 7px 0;">CARGAR</button>
                </div>
            </form>
        </div>
    </div>

<?php if ($mostrar_tabla): 
    $d_hab = 0;
    for($d=1; $d<=$dias_en_mes; $d++) {
        $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
        if($n_dia != 0 && $n_dia != 6) $d_hab++;
    }
?>
<form method="POST" id="form-tabla" target="_blank">
    <input type="hidden" name="guardar_y_pdf" value="1">
    <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
    <input type="hidden" name="sala" value="<?= htmlspecialchars($sala_seleccionada) ?>">
    <input type="hidden" name="seccion" value="<?= htmlspecialchars($seccion_seleccionada) ?>">
    <input type="hidden" name="profesor" value="<?= htmlspecialchars($profesor_id) ?>">
    <input type="hidden" name="nombre_docente" value="<?= $nombre_profesor ?>">
    <input type="hidden" id="tipo_reporte" name="tipo_reporte" value="">
    
    <!-- Campos ocultos para enviar los porcentajes de matrícula al PDF -->
    <input type="hidden" name="porcentaje_v" id="porcentaje_v" value="0">
    <input type="hidden" name="porcentaje_h" id="porcentaje_h" value="0">
    <input type="hidden" name="porcentaje_total" id="porcentaje_total" value="100">

    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="m-0 fw-bold text-uppercase">CONTROL DE ASISTENCIA: <?= strtoupper($nombre_mes) ?> <?= $anio ?></h6>
                <small class="opacity-75">Docente: <?= strtoupper($nombre_profesor) ?> | <?= strtoupper(htmlspecialchars($sala_seleccionada)) ?> <?= $seccion_seleccionada ? '- ' . strtoupper(htmlspecialchars($seccion_seleccionada)) : '' ?></small>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <button type="button" class="btn btn-sm btn-danger fw-bold" onclick="limpiarTodo()">LIMPIAR</button>
                <div class="bg-white text-dark px-3 py-1 rounded small fw-bold matricula-box">
                    Matrícula: V <input type="number" min="0" name="mat_v" id="mat_v" value="" oninput="recalcular()"> 
                    H <input type="number" min="0" name="mat_h" id="mat_h" value="" oninput="recalcular()">
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 table-asistencia">
                <thead class="bg-light">
                    <tr>
                        <th rowspan="2" width="50">SEXO</th>
                        <?php 
                        for($d=1; $d<=$dias_en_mes; $d++) {
                            $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                            $es_fin = ($n_dia == 0 || $n_dia == 6);
                            $letra = ['D','L','M','M','J','V','S'][$n_dia];
                            echo "<th class='".($es_fin ? 'weekend' : '')."' style='font-size:0.7rem;'>$letra<br><small>".$d."</small></th>";
                        }
                        ?>
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
                                    <input type="number" min="0" name="asist_v[<?= $d ?>]" class="asist-input in-v" data-dia="<?= $d ?>" value="" oninput="recalcular()" onblur="limpiarCero(this)">
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td id="res_total_v" class="fw-bold bg-primary text-white">0</td>
                        <td id="res_porc_v" class="fw-bold text-primary">0%</td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">H</td>
                        <?php for($d=1; $d<=$dias_en_mes; $d++): 
                            $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                            $es_fin = ($n_dia == 0 || $n_dia == 6); ?>
                            <td class="<?= $es_fin ? 'weekend-cell' : 'p-0' ?>">
                                <?php if(!$es_fin): ?>
                                    <input type="number" min="0" name="asist_h[<?= $d ?>]" class="asist-input in-h" data-dia="<?= $d ?>" value="" oninput="recalcular()" onblur="limpiarCero(this)">
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td id="res_total_h" class="fw-bold bg-primary text-white">0</td>
                        <td id="res_porc_h" class="fw-bold text-primary">0%</td>
                    </tr>
                </tbody>
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td class="bg-navy text-white">TOTAL</td>
                        <?php for($d=1; $d<=$dias_en_mes; $d++): ?>
                            <td id="total_dia_<?= $d ?>" class="bg-navy text-white">-</td>
                        <?php endfor; ?>
                        <td id="gran_total_asist" class="bg-dark text-white fw-bold">0</td>
                        <td id="gran_total_porc" class="bg-dark text-white fw-bold">0%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <input type="hidden" id="dias_hab_val" value="<?= $d_hab ?>">
            <span class="text-muted small">Días Hábiles: <b><?= $d_hab ?></b></span>
            <span class="h6 mb-0 text-navy">Promedio Diario: <b id="promedio_total">0.0</b></span>
        </div>
    </div>

    <!-- Clasificación editable -->
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
                        <?php foreach($edades as $edad): ?>
                        <tr class="fila-edad">
                            <td><?= $edad ?></td>
                            <td><input type="number" class="form-control form-control-sm text-center ven-v" name="venezolano_v[<?= $edad ?>]" data-edad="<?= $edad ?>" value="" oninput="calcularTotalFila(this, 'venezolano')" onblur="limpiarCero(this)"></td>
                            <td><input type="number" class="form-control form-control-sm text-center ven-h" name="venezolano_h[<?= $edad ?>]" data-edad="<?= $edad ?>" value="" oninput="calcularTotalFila(this, 'venezolano')" onblur="limpiarCero(this)"></td>
                            <td><input type="text" class="form-control form-control-sm text-center ven-total" name="venezolano_total[<?= $edad ?>]" readonly></td>
                            <td><?= $edad ?></td>
                            <td><input type="number" class="form-control form-control-sm text-center ext-v" name="extranjero_v[<?= $edad ?>]" data-edad="<?= $edad ?>" value="" oninput="calcularTotalFila(this, 'extranjero')" onblur="limpiarCero(this)"></td>
                            <td><input type="number" class="form-control form-control-sm text-center ext-h" name="extranjero_h[<?= $edad ?>]" data-edad="<?= $edad ?>" value="" oninput="calcularTotalFila(this, 'extranjero')" onblur="limpiarCero(this)"></td>
                            <td><input type="text" class="form-control form-control-sm text-center ext-total" name="extranjero_total[<?= $edad ?>]" readonly></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ingreso/Egreso -->
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
                            <td><input type="date" name="ingreso_fn[]" class="form-control form-control-sm"></td>
                            <td><input type="date" name="ingreso_fi[]" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></td>
                            <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button></td>
                        </tr>
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
                            <td><input type="date" name="egreso_fn[]" class="form-control form-control-sm"></td>
                            <td><input type="date" name="egreso_fi[]" class="form-control form-control-sm"></td>
                            <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button></td>
                        </tr>
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
            <textarea name="observaciones" class="form-control" rows="4" placeholder="Escriba aquí las observaciones..."></textarea>
            <div class="text-end mt-2"><small>Director(a)</small></div>
        </div>
    </div>

    <!-- Botón GENERAR PDF -->
    <div class="text-center mt-4 mb-4">
        <button type="submit" class="btn btn-pdf-final px-5 py-2">
            🖨️ GENERAR PDF
        </button>
    </div>
</form>
<?php endif; ?>
</div>

<script>
function setTipoReporte() {
    const sala = document.getElementById('select-grado').value;
    const tipo = (sala === 'sala4' || sala === 'sala5') ? 'inicial' : 'regular';
    const tipoInput = document.getElementById('tipo_reporte');
    if (tipoInput) tipoInput.value = tipo;
}

function pasoGrado() {
    const sala = document.getElementById('select-grado').value;
    const seccionSelect = document.getElementById('select-seccion');
    const docenteSelect = document.getElementById('select-docente');
    seccionSelect.innerHTML = '<option value="">Primero seleccione grado...</option>';
    seccionSelect.disabled = true;
    docenteSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
    docenteSelect.disabled = true;
    document.getElementById('seccion-mes').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';

    if (sala !== "") {
        seccionSelect.innerHTML = '<option value="">Cargando secciones...</option>';
        const formData = new FormData();
        formData.append('action', 'cargar_secciones');
        formData.append('sala', sala);
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                seccionSelect.innerHTML = '<option value="">Seleccione sección...</option>';
                if (data.secciones && data.secciones.length > 0) {
                    data.secciones.forEach(sec => {
                        seccionSelect.innerHTML += `<option value="${sec.id}">${sec.nombre}</option>`;
                    });
                    seccionSelect.disabled = false;
                } else {
                    seccionSelect.innerHTML = '<option value="">No hay secciones disponibles</option>';
                }
            })
            .catch(() => seccionSelect.innerHTML = '<option value="">Error al cargar</option>');
    }
}

function pasoSeccion() {
    const seccion = document.getElementById('select-seccion').value;
    const docenteSelect = document.getElementById('select-docente');
    docenteSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
    docenteSelect.disabled = true;
    document.getElementById('seccion-mes').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';

    if (seccion !== "") {
        docenteSelect.innerHTML = '<option value="">Cargando docentes...</option>';
        const formData = new FormData();
        formData.append('action', 'cargar_docentes');
        formData.append('seccion', seccion);
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                docenteSelect.innerHTML = '<option value="">Seleccione docente...</option>';
                if(data.docentes && data.docentes.length > 0) {
                    data.docentes.forEach(d => {
                        docenteSelect.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
                    });
                    docenteSelect.disabled = false;
                } else {
                    docenteSelect.innerHTML = '<option value="">No hay docentes asignados</option>';
                }
            })
            .catch(() => docenteSelect.innerHTML = '<option value="">Error al cargar</option>');
    }
}

window.pasoDocente = function() {
    const profesor = document.getElementById('select-docente').value;
    const mesDiv = document.getElementById('seccion-mes');
    const botonDiv = document.getElementById('seccion-boton');
    if (profesor !== "") {
        mesDiv.style.display = 'block';
        botonDiv.style.display = 'block';
    } else {
        if (mesDiv) mesDiv.style.display = 'none';
        if (botonDiv) botonDiv.style.display = 'none';
    }
};

window.limpiarTodo = function() {
    const form = document.getElementById('filtroForm');
    if (form) form.reset();
    document.getElementById('select-seccion').innerHTML = '<option value="">Primero seleccione grado...</option>';
    document.getElementById('select-seccion').disabled = true;
    document.getElementById('select-docente').innerHTML = '<option value="">Primero seleccione sección...</option>';
    document.getElementById('select-docente').disabled = true;
    document.getElementById('seccion-mes').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';
    if (document.getElementById('form-tabla')) {
        document.querySelectorAll('.in-v, .in-h').forEach(input => input.value = '');
        window.recalcular();
    }
};

function limpiarCero(input) {
    if (input.value === '0') {
        input.value = '';
    }
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
    // Porcentajes sobre el total de asistencias (V+H)
    const porcV = totalGeneral ? Math.round((totalV / totalGeneral) * 100) : 0;
    const porcH = totalGeneral ? Math.round((totalH / totalGeneral) * 100) : 0;
    
    document.getElementById('res_porc_v').textContent = porcV + '%';
    document.getElementById('res_porc_h').textContent = porcH + '%';
    // La fila TOTAL siempre 100%
    document.getElementById('gran_total_porc').textContent = '100%';
    
    // Actualizar campos ocultos para el PDF
    document.getElementById('porcentaje_v').value = porcV;
    document.getElementById('porcentaje_h').value = porcH;
    document.getElementById('porcentaje_total').value = 100;
    
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
    const hoy = new Date().toISOString().slice(0,10);
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
            <td><input type="date" name="ingreso_fn[]" class="form-control form-control-sm"></td>
            <td><input type="date" name="ingreso_fi[]" class="form-control form-control-sm" value="${hoy}"></td>
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
            <td><input type="date" name="egreso_fn[]" class="form-control form-control-sm"></td>
            <td><input type="date" name="egreso_fi[]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button></td>
        `;
    }
    tbody.appendChild(fila);
}

document.getElementById('form-tabla')?.addEventListener('submit', setTipoReporte);
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('form-tabla')) {
        window.recalcular();
        document.querySelectorAll('.ven-v, .ven-h').forEach(inp => calcularTotalFila(inp, 'venezolano'));
        document.querySelectorAll('.ext-v, .ext-h').forEach(inp => calcularTotalFila(inp, 'extranjero'));
    }
});
</script>

<?php include "../includes/footer.php"; ?>