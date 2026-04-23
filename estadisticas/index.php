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

$meses_es = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombre_mes = isset($meses_es[$mes_num]) ? $meses_es[$mes_num] : '';

$m_v = 0; $m_h = 0;
$nombre_profesor = 'No seleccionado';
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
                        }
                        ?>
                    </select>
                </div>

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
                </div>
            </form>
        </div>
    </div>
</div>

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

    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="m-0 fw-bold text-uppercase">CONTROL DE ASISTENCIA: <?= strtoupper($nombre_mes) ?> <?= $anio ?></h6>
                <small class="opacity-75">Docente: <?= strtoupper($nombre_profesor) ?> | <?= strtoupper(htmlspecialchars($sala_seleccionada)) ?> <?= $seccion_seleccionada ? '- ' . strtoupper(htmlspecialchars($seccion_seleccionada)) : '' ?></small>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <button type="button" class="btn btn-sm btn-danger fw-bold" onclick="limpiarTodo()">LIMPIAR</button>
                <div class="bg-white text-dark px-3 py-1 rounded small fw-bold">
                    Matrícula: V <input type="number" name="mat_v" id="mat_v" value="<?= $m_v ?>" style="width:35px; border:none; text-align:center; font-weight:bold; background:transparent;" readonly> 
                    H <input type="number" name="mat_h" id="mat_h" value="<?= $m_h ?>" style="width:35px; border:none; text-align:center; font-weight:bold; background:transparent;" readonly>
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
let diasHabiles = 0;
let matV = 0;
let matH = 0;

// ========== FUNCIONES GLOBALES ==========
function pasoGrado() {
    const sala = document.getElementById('select-grado').value;
    const seccionSelect = document.getElementById('select-seccion');
    const docenteSelect = document.getElementById('select-docente');

    seccionSelect.innerHTML = '<option value="">Seleccione sección...</option>';
    docenteSelect.innerHTML = '<option value="">Seleccione docente...</option>';
    document.getElementById('seccion-docente').style.display = 'none';
    document.getElementById('seccion-mes').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';

    if (sala !== "") {
        const formData = new FormData();
        formData.append('action', 'cargar_secciones');
        formData.append('sala', sala);

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
});
</script>

<?php include "../includes/footer.php"; ?>