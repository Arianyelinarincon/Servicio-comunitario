<?php 
require_once "config_db.php"; 
// ========== AJAX HANDLER ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
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

include "../includes/header.php"; 

<<<<<<< HEAD
// Recibir filtros (si vienen por GET)
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : date('Y-m');
$sala_seleccionada = isset($_GET['sala']) ? $_GET['sala'] : '';
$seccion_id = isset($_GET['seccion']) ? (int)$_GET['seccion'] : 0;
$profesor_id = isset($_GET['profesor']) ? (int)$_GET['profesor'] : 0;

// Cálculos de fecha
$anio = date('Y', strtotime($periodo));
$mes_num = date('m', strtotime($periodo));
$dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes_num, $anio);
=======

// 1. Manejo de variables de filtrado con sanitización
$sala_seleccionada = isset($_GET['sala']) ? mysqli_real_escape_string($conexion, $_GET['sala']) : '';
$seccion_seleccionada = isset($_GET['seccion']) ? mysqli_real_escape_string($conexion, $_GET['seccion']) : '';
$profesor_id = isset($_GET['profesor']) ? mysqli_real_escape_string($conexion, $_GET['profesor']) : '';
$periodo = isset($_GET['periodo']) ? mysqli_real_escape_string($conexion, $_GET['periodo']) : date('Y-m');

// Solo mostrar tabla si tenemos GRADO + DOCENTE
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
>>>>>>> 3651f81d024a6558485b8b7a904ae9dfeacbc23e

$meses_es = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombre_mes = isset($meses_es[$mes_num]) ? $meses_es[$mes_num] : '';

// Valores por defecto
$m_v = 0; $m_h = 0;
$nombre_profesor = 'No seleccionado';
<<<<<<< HEAD
$dias_habiles = 0;

// Si tenemos sala y profesor seleccionados, calculamos matrícula y días hábiles
if ($sala_seleccionada && $profesor_id) {
    // Matrícula desde estudiantes
    $stmt_mat = mysqli_prepare($conexion, "SELECT 
        SUM(CASE WHEN genero = 'V' THEN 1 ELSE 0 END) as v,
        SUM(CASE WHEN genero = 'H' THEN 1 ELSE 0 END) as h
        FROM estudiantes WHERE sala = ? AND estatus = 'Activo'");
    mysqli_stmt_bind_param($stmt_mat, "s", $sala_seleccionada);
    mysqli_stmt_execute($stmt_mat);
    $res_mat = mysqli_stmt_get_result($stmt_mat);
    if ($row = mysqli_fetch_assoc($res_mat)) {
        $m_v = $row['v'] ?? 0;
        $m_h = $row['h'] ?? 0;
    }
    mysqli_stmt_close($stmt_mat);

    // Nombre del profesor
    $stmt_prof = mysqli_prepare($conexion, "SELECT nombre FROM profesores WHERE id = ? AND estatus = 'Activo'");
    mysqli_stmt_bind_param($stmt_prof, "i", $profesor_id);
    mysqli_stmt_execute($stmt_prof);
    $res_prof = mysqli_stmt_get_result($stmt_prof);
    if ($row = mysqli_fetch_assoc($res_prof)) {
        $nombre_profesor = $row['nombre'];
    }
    mysqli_stmt_close($stmt_prof);

    // Días hábiles del mes
    for ($d = 1; $d <= $dias_en_mes; $d++) {
        $n_dia_num = date('w', strtotime("$anio-$mes_num-$d"));
        if ($n_dia_num != 0 && $n_dia_num != 6) $dias_habiles++;
=======
$d_hab = 0;

// Consultas solo si hay datos
if ($sala_seleccionada) {
    $stmt_mat = $conexion->prepare("SELECT 
        SUM(CASE WHEN genero = 'V' THEN 1 ELSE 0 END) as v,
        SUM(CASE WHEN genero = 'H' THEN 1 ELSE 0 END) as h
        FROM estudiantes WHERE sala = ? AND estatus = 'Activo'");
    $stmt_mat->bind_param("s", $sala_seleccionada);
    $stmt_mat->execute();
    $res_mat = $stmt_mat->get_result()->fetch_assoc();
    if ($res_mat) {
        $m_v = (int)$res_mat['v']; 
        $m_h = (int)$res_mat['h'];
    }
    $stmt_mat->close();
}

if ($profesor_id) {
    $stmt_prof = $conexion->prepare("SELECT nombre FROM profesores WHERE id = ?");
    $stmt_prof->bind_param("s", $profesor_id);
    $stmt_prof->execute();
    $prof_data = $stmt_prof->get_result()->fetch_assoc();
    if ($prof_data) {
        $nombre_profesor = htmlspecialchars($prof_data['nombre']);
>>>>>>> 3651f81d024a6558485b8b7a904ae9dfeacbc23e
    }
    $stmt_prof->close();
}
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
    .loading { opacity: 0.6; pointer-events: none; }
</style>

<div class="container-fluid py-4">
    <!-- Tarjeta de filtros -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">GRADO</label>
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

<<<<<<< HEAD
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">SECCIÓN</label>
                    <select name="seccion" id="seccion" class="form-select shadow-none" <?= empty($sala_seleccionada) ? 'disabled' : '' ?> required>
                        <option value="">Primero seleccione grado...</option>
                        <?php 
                        if ($sala_seleccionada) {
                            $stmt_sec = mysqli_prepare($conexion, "SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
                            mysqli_stmt_bind_param($stmt_sec, "s", $sala_seleccionada);
                            mysqli_stmt_execute($stmt_sec);
                            $res_sec = mysqli_stmt_get_result($stmt_sec);
                            while ($sec = mysqli_fetch_assoc($res_sec)) {
                                $selected = ($seccion_id == $sec['id']) ? 'selected' : '';
                                echo "<option value='{$sec['id']}' $selected>{$sec['nombre']}</option>";
                            }
                            mysqli_stmt_close($stmt_sec);
=======
                <div class="col-md-3" id="seccion-seccion" style="display:<?= $sala_seleccionada ? 'block' : 'none' ?>;">
                    <label class="small fw-bold text-muted">SECCIÓN</label>
                    <select name="seccion" id="select-seccion" class="form-select shadow-none" onchange="pasoSeccion()">
                        <option value="">Seleccione sección...</option>
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
>>>>>>> 3651f81d024a6558485b8b7a904ae9dfeacbc23e
                        }
                        ?>
                    </select>
                </div>

<<<<<<< HEAD
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">PROFESOR / DOCENTE</label>
                    <select name="profesor" id="profesor" class="form-select shadow-none" <?= empty($seccion_id) ? 'disabled' : '' ?> required>
                        <option value="">Seleccione profesor...</option>
                        <?php 
                        if ($seccion_id) {
                            $stmt_prof = mysqli_prepare($conexion, "SELECT id, nombre FROM profesores WHERE seccion = ? AND estatus = 'Activo' ORDER BY nombre ASC");
                            mysqli_stmt_bind_param($stmt_prof, "i", $seccion_id);
                            mysqli_stmt_execute($stmt_prof);
                            $res_prof = mysqli_stmt_get_result($stmt_prof);
                            while ($prof = mysqli_fetch_assoc($res_prof)) {
                                $selected = ($profesor_id == $prof['id']) ? 'selected' : '';
                                echo "<option value='{$prof['id']}' $selected>{$prof['nombre']}</option>";
                            }
                            mysqli_stmt_close($stmt_prof);
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold text-muted">MES</label>
                    <input type="month" name="periodo" class="form-control shadow-none" value="<?= $periodo ?>">
                </div>

                <div class="col-md-2 d-flex justify-content-center align-items-end pb-1">
                    <button type="submit" class="btn btn-primary w-100 fw-bold bg-navy border-0">CARGAR FORMATO</button>
=======
                <div class="col-md-3" id="seccion-docente" style="display:<?= $seccion_seleccionada ? 'block' : 'none' ?>;">
                    <label class="small fw-bold text-muted">DOCENTE</label>
                    <select name="profesor" id="select-docente" class="form-select shadow-none" required onchange="pasoDocente()">
                        <option value="">Seleccione docente...</option>
                        <?php 
                        if($seccion_seleccionada) {
                            // Usar columna 'seccion' en lugar de 'seccion_id'
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

                <div class="col-md-2" id="seccion-mes" style="display:<?= $profesor_id ? 'block' : 'none' ?>;">
                    <label class="small fw-bold text-muted">MES</label>
                    <input type="month" name="periodo" id="select-mes" class="form-control shadow-none" value="<?= htmlspecialchars($periodo) ?>">
                </div>

                <div class="col-md-1" id="seccion-boton" style="display:<?= ($sala_seleccionada && $profesor_id) ? 'block' : 'none' ?>;">
                    <button type="submit" class="btn btn-primary w-100 fw-bold bg-navy border-0" style="padding: 7px 0;">CARGAR</button>
>>>>>>> 3651f81d024a6558485b8b7a904ae9dfeacbc23e
                </div>
            </form>
        </div>
    </div>
</div>

<<<<<<< HEAD
    <?php if ($sala_seleccionada && $profesor_id): ?>
    <!-- Formulario de Asistencia (integrado, igual que tu original) -->
    <form action="generar_pdf.php" method="POST" target="_blank" id="formAsistencia">
        <!-- Campos ocultos para enviar al PDF -->
        <input type="hidden" name="periodo" value="<?= $periodo ?>">
        <input type="hidden" name="sala" value="<?= $sala_seleccionada ?>">
        <input type="hidden" name="seccion_id" value="<?= $seccion_id ?>">
        <input type="hidden" name="profesor_id" value="<?= $profesor_id ?>">
        <input type="hidden" name="docente" value="<?= htmlspecialchars($nombre_profesor) ?>">
        <input type="hidden" name="grado" value="<?= htmlspecialchars($sala_seleccionada) ?>">
        <input type="hidden" name="mes" value="<?= $nombre_mes . ' ' . $anio ?>">
        <input type="hidden" name="dias_habiles" id="dias_hab_val" value="<?= $dias_habiles ?>">
        <input type="hidden" name="matricula_v" id="mat_v_hidden" value="<?= $m_v ?>">
        <input type="hidden" name="matricula_h" id="mat_h_hidden" value="<?= $m_h ?>">
        
        <!-- Totalizadores para PDF -->
        <input type="hidden" name="total_asistencia_v" id="total_asistencia_v">
        <input type="hidden" name="total_asistencia_h" id="total_asistencia_h">
        <input type="hidden" name="porcentaje_v" id="porcentaje_v">
        <input type="hidden" name="porcentaje_h" id="porcentaje_h">
        <input type="hidden" name="porcentaje_total" id="porcentaje_total">
        <input type="hidden" name="promedio_asistencia" id="promedio_asistencia">
=======
<?php if ($mostrar_tabla): 
    // Calcular días hábiles
    $d_hab = 0;
    for($d=1; $d<=$dias_en_mes; $d++) {
        $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
        if($n_dia != 0 && $n_dia != 6) $d_hab++;
    }
?>
<form action="generar_pdf.php" method="POST" target="_blank" id="form-tabla">
    <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
    <input type="hidden" name="sala" value="<?= htmlspecialchars($sala_seleccionada) ?>">
    <input type="hidden" name="seccion" value="<?= htmlspecialchars($seccion_seleccionada) ?>">
    <input type="hidden" name="nombre_docente" value="<?= $nombre_profesor ?>">
    <input type="hidden" name="resumen_v_1" id="input_tV" value="0">
    <input type="hidden" name="resumen_h_1" id="input_tH" value="0">
    <input type="hidden" name="total_general" id="input_tG" value="0">
    <input type="hidden" name="porcentaje_total" id="input_pT" value="0">
>>>>>>> 3651f81d024a6558485b8b7a904ae9dfeacbc23e

    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="m-0 fw-bold text-uppercase">CONTROL DE ASISTENCIA: <?= strtoupper($nombre_mes) ?> <?= $anio ?></h6>
                <small class="opacity-75">Docente: <?= strtoupper($nombre_profesor) ?> | <?= strtoupper(htmlspecialchars($sala_seleccionada)) ?> <?= $seccion_seleccionada ? '- ' . strtoupper(htmlspecialchars($seccion_seleccionada)) : '' ?></small>
            </div>
<<<<<<< HEAD
            
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 table-asistencia">
                    <thead class="bg-light">
                        <tr>
                            <th rowspan="2" width="50">SEXO</th>
                            <?php 
                            $dias_habiles_local = 0;
                            for($d=1; $d<=$dias_en_mes; $d++) {
                                $n_dia_num = date('w', strtotime("$anio-$mes_num-$d"));
                                $es_fin = ($n_dia_num == 0 || $n_dia_num == 6);
                                if(!$es_fin) $dias_habiles_local++;
                                $letra = ['D','L','M','M','J','V','S'][$n_dia_num];
                                echo "<th class='".($es_fin ? 'weekend' : '')."'>$letra</th>";
                            }
                            ?>
                            <th rowspan="2" class="bg-primary text-white">TOTAL</th>
                            <th rowspan="2" class="bg-success text-white">%</th>
                        </tr>
                        <tr>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): 
                                $n_dia_num = date('w', strtotime("$anio-$mes_num-$d")); ?>
                                <th class="<?= ($n_dia_num == 0 || $n_dia_num == 6) ? 'weekend' : '' ?>"><?= $d ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold bg-light">V</td>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): 
                                $es_fin = (date('w', strtotime("$anio-$mes_num-$d")) % 6 == 0); ?>
                                <td class="<?= $es_fin ? 'weekend-cell' : 'p-0' ?>">
                                    <?php if(!$es_fin): ?>
                                        <input type="number" name="asist_v[]" class="asist-input in-v" data-dia="<?= $d ?>" oninput="recalcular()">
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td id="res_total_v" class="fw-bold">0</td>
                            <td id="res_porc_v" class="fw-bold text-primary">0%</td>
                        </tr>
                        <tr>
                            <td class="fw-bold bg-light">H</td>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): 
                                $es_fin = (date('w', strtotime("$anio-$mes_num-$d")) % 6 == 0); ?>
                                <td class="<?= $es_fin ? 'weekend-cell' : 'p-0' ?>">
                                    <?php if(!$es_fin): ?>
                                        <input type="number" name="asist_h[]" class="asist-input in-h" data-dia="<?= $d ?>" oninput="recalcular()">
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td id="res_total_h" class="fw-bold">0</td>
                            <td id="res_porc_h" class="fw-bold text-primary">0%</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="bg-navy text-white">TOTAL</td>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): ?>
                                <td id="total_dia_<?= $d ?>" class="bg-navy text-white"></td>
                            <?php endfor; ?>
                            <td id="gran_total_asist" class="bg-dark text-white">0</td>
                            <td id="gran_total_porc" class="bg-dark text-white">0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                <span class="text-muted small">Días Hábiles: <b><?= $dias_habiles_local ?? 0 ?></b></span>
                <span class="h6 mb-0 text-navy">Promedio Diario: <b id="promedio_total">0.0</b></span>
                <button type="submit" class="btn btn-success fw-bold px-5">GENERAR PDF</button>
=======
            <div class="d-flex gap-3 align-items-center">
                <button type="button" class="btn btn-sm btn-danger fw-bold" onclick="limpiarTodo()">LIMPIAR</button>
                <div class="bg-white text-dark px-3 py-1 rounded small fw-bold">
                    Matrícula: V <input type="number" name="mat_v" id="mat_v" value="<?= $m_v ?>" style="width:35px; border:none; text-align:center; font-weight:bold; background:transparent;" readonly> 
                    H <input type="number" name="mat_h" id="mat_h" value="<?= $m_h ?>" style="width:35px; border:none; text-align:center; font-weight:bold; background:transparent;" readonly>
                </div>
>>>>>>> 3651f81d024a6558485b8b7a904ae9dfeacbc23e
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
                                    <input type="number" min="0" max="99" name="asist_v[<?= $d ?>]" class="asist-input in-v" data-dia="<?= $d ?>" value="0" oninput="recalcular()">
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
                                    <input type="number" min="0" max="99" name="asist_h[<?= $d ?>]" class="asist-input in-h" data-dia="<?= $d ?>" value="0" oninput="recalcular()">
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
            <button type="submit" class="btn btn-danger fw-bold px-5 shadow">🖨️ GENERAR PDF</button>
        </div>
    </div>
</form>
<?php endif; ?>
</div>

<script>
<<<<<<< HEAD
// Función para recalcular totales y porcentajes (igual que tu original, pero actualiza campos hidden)
function recalcular() {
    const dHabiles = parseInt(document.getElementById('dias_hab_val').value) || 0;
    const mV = parseInt(document.getElementById('mat_v').value) || 0;
    const mH = parseInt(document.getElementById('mat_h').value) || 0;
    const mTotal = mV + mH;

    let tV = 0, tH = 0;
    // Recorrer hasta 31 días (máximo)
    for (let d = 1; d <= 31; d++) {
        const v = parseInt(document.querySelector(`.in-v[data-dia="${d}"]`)?.value) || 0;
        const h = parseInt(document.querySelector(`.in-h[data-dia="${d}"]`)?.value) || 0;
        tV += v; tH += h;
        const col = document.getElementById(`total_dia_${d}`);
        if (col) col.innerText = (v + h > 0) ? (v + h) : '';
    }
=======
let diasHabiles = 0;
let matV = 0;
let matH = 0;

// ========== FUNCIONES GLOBALES ==========
function pasoGrado() {
    const sala = document.getElementById('select-grado').value;
    const seccionSelect = document.getElementById('select-seccion');
    const docenteSelect = document.getElementById('select-docente');
>>>>>>> 3651f81d024a6558485b8b7a904ae9dfeacbc23e

    seccionSelect.innerHTML = '<option value="">Seleccione sección...</option>';
    docenteSelect.innerHTML = '<option value="">Seleccione docente...</option>';
    document.getElementById('seccion-docente').style.display = 'none';
    document.getElementById('seccion-mes').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';

<<<<<<< HEAD
    const calcP = (a, m) => (m > 0 && dHabiles > 0) ? ((a / (m * dHabiles)) * 100).toFixed(1) : "0.0";
    const pV = calcP(tV, mV);
    const pH = calcP(tH, mH);
    const pTotal = calcP(totalAsist, mTotal);
    
    document.getElementById('res_porc_v').innerText = pV + '%';
    document.getElementById('res_porc_h').innerText = pH + '%';
    document.getElementById('gran_total_porc').innerText = pTotal + '%';
    document.getElementById('promedio_total').innerText = (dHabiles > 0) ? (totalAsist / dHabiles).toFixed(1) : "0.0";

    // Actualizar campos hidden para enviar al PDF
    document.getElementById('total_asistencia_v').value = tV;
    document.getElementById('total_asistencia_h').value = tH;
    document.getElementById('porcentaje_v').value = pV;
    document.getElementById('porcentaje_h').value = pH;
    document.getElementById('porcentaje_total').value = pTotal;
    document.getElementById('promedio_asistencia').value = pTotal + '%';
}
=======
    if (sala !== "") {
        const formData = new FormData();
        formData.append('action', 'cargar_secciones');
        formData.append('sala', sala);
>>>>>>> 3651f81d024a6558485b8b7a904ae9dfeacbc23e

        fetch('index.php?ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.secciones && data.secciones.length > 0) {
                data.secciones.forEach(sec => {
                    seccionSelect.innerHTML += `<option value="${sec.id}">${sec.nombre}</option>`;
                });
                document.getElementById('seccion-seccion').style.display = 'block';
            } else {
                // Si no hay secciones, mostrar mensaje
                seccionSelect.innerHTML = '<option value="">No hay secciones disponibles</option>';
                document.getElementById('seccion-seccion').style.display = 'block';
            }
        })
        .catch(error => console.error('Error:', error));
    } else {
        document.getElementById('seccion-seccion').style.display = 'none';
    }
}

<<<<<<< HEAD
// Cascada AJAX para cargar secciones al cambiar Sala
document.getElementById('sala').addEventListener('change', function() {
    const sala = this.value;
    const seccionSelect = document.getElementById('seccion');
    const profesorSelect = document.getElementById('profesor');
    
    seccionSelect.innerHTML = '<option value="">Cargando...</option>';
    seccionSelect.disabled = true;
    profesorSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
    profesorSelect.disabled = true;

    if (!sala) {
        seccionSelect.innerHTML = '<option value="">Primero seleccione grado...</option>';
        return;
    }

    fetch('ajax_get_secciones.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'sala=' + encodeURIComponent(sala)
    })
    .then(response => response.json())
    .then(data => {
        seccionSelect.innerHTML = '<option value="">Seleccione sección...</option>';
        if (data.length > 0) {
            data.forEach(sec => {
                seccionSelect.innerHTML += `<option value="${sec.id}">${sec.nombre}</option>`;
            });
            seccionSelect.disabled = false;
        } else {
            seccionSelect.innerHTML = '<option value="">No hay secciones</option>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        seccionSelect.innerHTML = '<option value="">Error al cargar</option>';
    });
});

// Cargar profesor al cambiar Sección
document.getElementById('seccion').addEventListener('change', function() {
    const seccion = this.value;
    const profesorSelect = document.getElementById('profesor');
    
    profesorSelect.innerHTML = '<option value="">Cargando...</option>';
    profesorSelect.disabled = true;

    if (!seccion) {
        profesorSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
        return;
    }

    fetch('ajax_get_profesor.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'seccion=' + encodeURIComponent(seccion)
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.id) {
            profesorSelect.innerHTML = `<option value="${data.id}">${data.nombre}</option>`;
            profesorSelect.disabled = false;
        } else {
            profesorSelect.innerHTML = '<option value="">No hay profesor asignado</option>';
            profesorSelect.disabled = true;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        profesorSelect.innerHTML = '<option value="">Error al cargar</option>';
    });
});

// Al cargar la página, si ya hay sala seleccionada, forzamos la carga de secciones y profesor (para que funcione al volver de GET)
document.addEventListener('DOMContentLoaded', function() {
    const salaActual = document.getElementById('sala').value;
    const seccionActual = document.getElementById('seccion').value;
    if (salaActual && !seccionActual) {
        // Disparar evento change para cargar secciones
        document.getElementById('sala').dispatchEvent(new Event('change'));
    }
    if (seccionActual) {
        // Si ya hay sección seleccionada (por GET), cargar profesor
        document.getElementById('seccion').dispatchEvent(new Event('change'));
    }
    // Inicializar cálculos si existe la tabla
    if (typeof recalcular === 'function') recalcular();
=======
function pasoSeccion() {
    const seccion = document.getElementById('select-seccion').value;
    const docenteSelect = document.getElementById('select-docente');

    docenteSelect.innerHTML = '<option value="">Seleccione docente...</option>';
    document.getElementById('seccion-mes').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';

    if (seccion !== "") {
        const formData = new FormData();
        formData.append('action', 'cargar_docentes');
        formData.append('seccion', seccion);

        fetch('index.php?ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.docentes && data.docentes.length > 0) {
                data.docentes.forEach(d => {
                    docenteSelect.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
                });
                document.getElementById('seccion-docente').style.display = 'block';
            } else {
                docenteSelect.innerHTML = '<option value="">No hay docentes asignados</option>';
                document.getElementById('seccion-docente').style.display = 'block';
            }
        })
        .catch(error => console.error('Error:', error));
    } else {
        document.getElementById('seccion-docente').style.display = 'none';
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
    // Limpiar selects dinámicos
    document.getElementById('select-seccion').innerHTML = '<option value="">Seleccione sección...</option>';
    document.getElementById('select-docente').innerHTML = '<option value="">Seleccione docente...</option>';
    document.getElementById('seccion-seccion').style.display = 'none';
    document.getElementById('seccion-docente').style.display = 'none';
    document.getElementById('seccion-mes').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';
    // Limpiar tabla si existe
    if (document.getElementById('form-tabla')) {
        document.querySelectorAll('.in-v, .in-h').forEach(input => {
            input.value = '0';
        });
        window.recalcular();
    }
};

window.recalcular = function() {
    const diasHabilesEl = document.getElementById('dias_hab_val');
    const matVEl = document.getElementById('mat_v');
    const matHEl = document.getElementById('mat_h');
    
    const diasHabilesVal = diasHabilesEl ? parseInt(diasHabilesEl.value) || 0 : 0;
    const matVVal = matVEl ? parseInt(matVEl.value) || 0 : 0;
    const matHVal = matHEl ? parseInt(matHEl.value) || 0 : 0;
    
    let totalV = 0, totalH = 0;
    const totalesDia = {};
    
    document.querySelectorAll('.in-v').forEach(input => {
        const val = parseInt(input.value) || 0;
        totalV += val;
        const dia = input.dataset.dia;
        totalesDia[dia] = (totalesDia[dia] || 0) + val;
    });
    
    document.querySelectorAll('.in-h').forEach(input => {
        const val = parseInt(input.value) || 0;
        totalH += val;
        const dia = input.dataset.dia;
        totalesDia[dia] = (totalesDia[dia] || 0) + val;
    });
    
    document.getElementById('res_total_v').textContent = totalV;
    document.getElementById('res_total_h').textContent = totalH;
    document.getElementById('gran_total_asist').textContent = totalV + totalH;
    
    const porcV = matVVal > 0 ? Math.round((totalV / (diasHabilesVal * matVVal)) * 100) : 0;
    const porcH = matHVal > 0 ? Math.round((totalH / (diasHabilesVal * matHVal)) * 100) : 0;
    const matTotal = matVVal + matHVal;
    const granTotal = totalV + totalH;
    const porcTotal = matTotal > 0 ? Math.round((granTotal / (diasHabilesVal * matTotal)) * 100) : 0;
    
    document.getElementById('res_porc_v').textContent = porcV + '%';
    document.getElementById('res_porc_h').textContent = porcH + '%';
    document.getElementById('gran_total_porc').textContent = porcTotal + '%';
    
    const promedioTotalEl = document.getElementById('promedio_total');
    if (promedioTotalEl) {
        const promedio = diasHabilesVal > 0 ? (granTotal / diasHabilesVal).toFixed(1) : '0.0';
        promedioTotalEl.textContent = promedio;
    }
    
    for(let d = 1; d <= 31; d++) {
        const td = document.getElementById('total_dia_' + d);
        if (td) {
            td.textContent = totalesDia[d] || '-';
        }
    }
    
    // Actualizar hidden inputs
    document.getElementById('input_tV').value = totalV;
    document.getElementById('input_tH').value = totalH;
    document.getElementById('input_tG').value = granTotal;
    document.getElementById('input_pT').value = porcTotal;
};

// Inicializar si hay parámetros en URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sala')) {
        document.getElementById('seccion-seccion').style.display = 'block';
    }
    if (urlParams.has('seccion')) {
        document.getElementById('seccion-docente').style.display = 'block';
    }
    if (urlParams.has('profesor')) {
        document.getElementById('seccion-mes').style.display = 'block';
        document.getElementById('seccion-boton').style.display = 'block';
    }
    if (document.getElementById('form-tabla')) {
        window.recalcular();
    }
>>>>>>> 3651f81d024a6558485b8b7a904ae9dfeacbc23e
});
</script>

<?php include "../includes/footer.php"; ?>
