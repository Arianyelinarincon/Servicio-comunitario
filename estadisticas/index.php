<?php 
require_once "../config/conexion.php"; 
include "../includes/header.php"; 

// 1. Manejo de variables de filtrado
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : date('Y-m');
$sala_seleccionada = isset($_GET['sala']) ? $_GET['sala'] : '';
$profesor_id = isset($_GET['profesor']) ? $_GET['profesor'] : '';

// 2. Cálculos de fecha y mes
$anio = date('Y', strtotime($periodo));
$mes_num = date('m', strtotime($periodo));
$dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes_num, $anio);

$meses_es = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombre_mes = $meses_es[$mes_num];

$m_v = 0; $m_h = 0;
$nombre_profesor = 'No seleccionado';

// 3. Consultas a la base de datos
if ($sala_seleccionada) {
    // Obtener matrícula (Varones y Hembras)
    $q_mat = mysqli_query($conexion, "SELECT 
        SUM(CASE WHEN genero = 'V' THEN 1 ELSE 0 END) as v,
        SUM(CASE WHEN genero = 'H' THEN 1 ELSE 0 END) as h
        FROM estudiantes WHERE sala = '$sala_seleccionada' AND estatus = 'Activo'");
    if ($res_mat = mysqli_fetch_assoc($q_mat)) {
        $m_v = $res_mat['v'] ?? 0; 
        $m_h = $res_mat['h'] ?? 0;
    }
}

if ($profesor_id) {
    // Obtener nombre del docente seleccionado
    $q_prof = mysqli_query($conexion, "SELECT nombre FROM profesores WHERE id = '$profesor_id'");
    if ($q_prof && $prof_data = mysqli_fetch_assoc($q_prof)) {
        $nombre_profesor = $prof_data['nombre'];
    }
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
    .text-navy { color: var(--navy) !important; }
</style>

<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">SALA / GRADO</label>
                    <select name="sala" id="sala" class="form-select shadow-none" required>
                        <option value="">Seleccione...</option>
                        <optgroup label="Educación Inicial">
                            <option value="sala4" <?= ($sala_seleccionada == 'sala4') ? 'selected' : '' ?>>Sala 4 Años</option>
                            <option value="sala5" <?= ($sala_seleccionada == 'sala5') ? 'selected' : '' ?>>Sala 5 Años</option>
                        </optgroup>
                        <optgroup label="Educación Primaria">
                            <?php for($i=1; $i<=6; $i++): 
                                $val = ($i==1) ? "1ro" : (($i==2) ? "2do" : (($i==3) ? "3ro" : $i."to")); ?>
                                <option value="<?= $val ?>" <?= ($sala_seleccionada == $val) ? 'selected' : '' ?>><?= $i ?>° Grado</option>
                            <?php endfor; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">PROFESOR / DOCENTE</label>
                    <select name="profesor" id="profesor" class="form-select shadow-none" required>
                        <option value="">Seleccione profesor...</option>
                        <?php 
                        // Cargamos todos los profesores de la tabla
                        $q_p = mysqli_query($conexion, "SELECT id, nombre FROM profesores ORDER BY nombre ASC");
                        while($p = mysqli_fetch_assoc($q_p)): ?>
                            <option value="<?= $p['id'] ?>" <?= ($profesor_id == $p['id']) ? 'selected' : '' ?>><?= $p['nombre'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">MES</label>
                    <input type="month" name="periodo" class="form-control shadow-none" value="<?= $periodo ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold bg-navy border-0">CARGAR FORMATO</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($sala_seleccionada && $profesor_id): ?>
    <form action="generar_pdf.php" method="POST" target="_blank">
        <input type="hidden" name="periodo" value="<?= $periodo ?>">
        <input type="hidden" name="sala" value="<?= $sala_seleccionada ?>">
        <input type="hidden" name="nombre_docente" value="<?= $nombre_profesor ?>">

        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="m-0 fw-bold text-uppercase">CONTROL DE ASISTENCIA: <?= $nombre_mes ?> <?= $anio ?></h6>
                    <small class="opacity-75">Docente: <?= strtoupper($nombre_profesor) ?> | Sala: <?= strtoupper($sala_seleccionada) ?></small>
                </div>
                <div class="d-flex gap-3">
                    <button type="button" class="btn btn-sm btn-danger fw-bold" onclick="limpiarTodo()">LIMPIAR</button>
                    <div class="bg-white text-dark px-3 py-1 rounded small fw-bold">
                        Matrícula: V <input type="number" name="mat_v" id="mat_v" value="<?= $m_v ?>" style="width:35px; border:none; text-align:center;" oninput="recalcular()"> 
                        H <input type="number" name="mat_h" id="mat_h" value="<?= $m_h ?>" style="width:35px; border:none; text-align:center;" oninput="recalcular()">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 table-asistencia">
                    <thead class="bg-light">
                        <tr>
                            <th rowspan="2" width="50">SEXO</th>
                            <?php 
                            $dias_habiles = 0;
                            for($d=1; $d<=$dias_en_mes; $d++) {
                                $n_dia_num = date('w', strtotime("$anio-$mes_num-$d"));
                                $es_fin = ($n_dia_num == 0 || $n_dia_num == 6);
                                if(!$es_fin) $dias_habiles++;
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
                                        <input type="number" name="asist_v[<?= $d ?>]" class="asist-input in-v" data-dia="<?= $d ?>" oninput="recalcular()">
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
                                        <input type="number" name="asist_h[<?= $d ?>]" class="asist-input in-h" data-dia="<?= $d ?>" oninput="recalcular()">
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
                <input type="hidden" id="dias_hab_val" value="<?= $dias_habiles ?>">
                <span class="text-muted small">Días Hábiles: <b><?= $dias_habiles ?></b></span>
                <span class="h6 mb-0 text-navy">Promedio Diario: <b id="promedio_total">0.0</b></span>
                <button type="submit" class="btn btn-success fw-bold px-5">GENERAR PDF</button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function recalcular() {
    const dHabiles = parseInt(document.getElementById('dias_hab_val').value) || 0;
    const mV = parseInt(document.getElementById('mat_v').value) || 0;
    const mH = parseInt(document.getElementById('mat_h').value) || 0;
    const mTotal = mV + mH;

    let tV = 0, tH = 0;
    for (let d = 1; d <= 31; d++) {
        const v = parseInt(document.querySelector(`.in-v[data-dia="${d}"]`)?.value) || 0;
        const h = parseInt(document.querySelector(`.in-h[data-dia="${d}"]`)?.value) || 0;
        tV += v; tH += h;
        const col = document.getElementById(`total_dia_${d}`);
        if (col) col.innerText = (v + h > 0) ? (v + h) : '';
    }

    const totalAsist = tV + tH;
    document.getElementById('res_total_v').innerText = tV;
    document.getElementById('res_total_h').innerText = tH;
    document.getElementById('gran_total_asist').innerText = totalAsist;

    const calcP = (a, m) => (m > 0 && dHabiles > 0) ? ((a / (m * dHabiles)) * 100).toFixed(1) : "0.0";
    document.getElementById('res_porc_v').innerText = calcP(tV, mV) + '%';
    document.getElementById('res_porc_h').innerText = calcP(tH, mH) + '%';
    document.getElementById('gran_total_porc').innerText = calcP(totalAsist, mTotal) + '%';
    document.getElementById('promedio_total').innerText = (dHabiles > 0) ? (totalAsist / dHabiles).toFixed(1) : "0.0";
}

function limpiarTodo() {
    if(confirm('¿Desea limpiar todos los datos de la tabla?')) {
        document.querySelectorAll('.asist-input').forEach(i => i.value = '');
        recalcular();
    }
}
</script>

<?php include "../includes/footer.php"; ?>