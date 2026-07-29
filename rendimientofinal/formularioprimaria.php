<?php
require_once "../estadisticas/config_db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

// ========== VALIDAR PARÁMETROS ==========
$salas_permitidas = ['1ro', '2do', '3ro', '4to', '5to', '6to'];
$sala_seleccionada = $_GET['sala'] ?? '';
if (!in_array($sala_seleccionada, $salas_permitidas)) {
    $sala_seleccionada = '';
}

$seccion_id = isset($_GET['seccion']) ? intval($_GET['seccion']) : '';
$profesor_id = isset($_GET['profesor']) ? intval($_GET['profesor']) : '';
$periodo = isset($_GET['periodo']) ? trim($_GET['periodo']) : '2025-2026';
if (!preg_match('/^\d{4}-\d{4}$/', $periodo)) {
    $periodo = '2025-2026';
}

if (empty($sala_seleccionada) || empty($seccion_id)) {
    die("<div class='alert alert-danger'>Error: Faltan parámetros. Genere el formulario desde el panel principal.</div>");
}

$nombre_profesor = 'No definido';
$nombre_seccion = '';
$stmt_prof = $conexion->prepare("SELECT nombre FROM profesores WHERE id = ?");
$stmt_prof->bind_param("i", $profesor_id);
$stmt_prof->execute();
if ($row = $stmt_prof->get_result()->fetch_assoc()) {
    $nombre_profesor = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
}
$stmt_prof->close();

$stmt_sec = $conexion->prepare("SELECT nombre FROM secciones WHERE id = ?");
$stmt_sec->bind_param("i", $seccion_id);
$stmt_sec->execute();
if ($row = $stmt_sec->get_result()->fetch_assoc()) {
    $nombre_seccion = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
}
$stmt_sec->close();

// ========== CONSULTA CORREGIDA (sin inscripcion_completa) ==========
$ano_inicio = substr($periodo, 0, 4);
$query_est = "
    SELECT e.id, e.cedula, e.cedula_escolar, e.nombre, e.apellido, e.genero, 
           e.fecha_nacimiento, e.lugar_nacimiento,
           b.m1_proyecto, b.m1_formacion, b.m1_sugerencias,
           b.m2_proyecto, b.m2_formacion, b.m2_sugerencias,
           b.m3_proyecto, b.m3_formacion, b.m3_sugerencias,
           b.resultado_final,
           b.literal_final,
           b.aprobado,
           b.observacion AS observacion_boletin
    FROM estudiantes e
    INNER JOIN inscripciones i ON e.id = i.estudiante_id AND i.ano_escolar = ?
    INNER JOIN boletines b ON e.id = b.estudiante_id 
        AND b.tipo_boletin = 'primaria'
        AND b.periodo = ?
    WHERE e.sala = ? 
      AND e.seccion_id = ? 
      AND e.estatus = 'Activo'
    ORDER BY e.nombre ASC, e.apellido ASC
";

$stmt_est = $conexion->prepare($query_est);
$stmt_est->bind_param("sssi", $periodo, $periodo, $sala_seleccionada, $seccion_id);
$stmt_est->execute();
$result_est = $stmt_est->get_result();
$estudiantes = $result_est->fetch_all(MYSQLI_ASSOC);

$varones = 0;
$hembras = 0;
foreach ($estudiantes as $est) {
    $genero = strtoupper($est['genero'] ?? '');
    if ($genero == 'V' || $genero == 'M' || $genero == 'MASCULINO') $varones++;
    elseif ($genero == 'H' || $genero == 'F' || $genero == 'FEMENINO') $hembras++;
}
$total_alumnos = $varones + $hembras;

include "../includes/header.php";
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
    body { background: #fff; }
    .hoja-rendimiento { 
        background: white; 
        padding: 5px 15px; 
        margin: 0 auto; 
        font-family: 'Calibri', 'Arial', sans-serif;
        font-size: 11pt; }
    .encabezado-ministerio { 
        text-align: center; 
        font-weight: bold; 
        margin-bottom: 20px; }
    .tabla-rendimiento { 
        width: 100%;
        border-collapse: collapse; 
        table-layout: fixed; 
        font-size: 9pt; }
    .tabla-rendimiento th, .tabla-rendimiento td { 
        border: 1px solid #000; 
        padding: 0.01cm 0.04cm;
        text-align: center; 
        vertical-align: middle; 
        font-size: 9pt;
        word-wrap: break-word; 
        background-color: #fff; }
    .tabla-rendimiento th { font-weight: bold; text-transform: uppercase; background-color: #fff; white-space: nowrap; font-size: 9pt; padding: 2px 1px; }
    .text-left { text-align: left !important; }
    .tabla-rendimiento th:nth-child(1), .tabla-rendimiento td:nth-child(1) { width: 2%; }
    .tabla-rendimiento th:nth-child(2), .tabla-rendimiento td:nth-child(2) { width: 12%; }
    .tabla-rendimiento th:nth-child(3), .tabla-rendimiento td:nth-child(3) { width: 30%; }
    .tabla-rendimiento th:nth-child(4), .tabla-rendimiento td:nth-child(4) { width: 4%; }
    .tabla-rendimiento th:nth-child(5), .tabla-rendimiento td:nth-child(5) { width: 9.42%; }
    .tabla-rendimiento th:nth-child(6), .tabla-rendimiento td:nth-child(6) { width: 8%; }
    .tabla-rendimiento th:nth-child(7), .tabla-rendimiento td:nth-child(7) { width: 7%; }
    .tabla-rendimiento th:nth-child(8), .tabla-rendimiento td:nth-child(8) { width: 7%; }
    .tabla-rendimiento th:nth-child(9), .tabla-rendimiento td:nth-child(9) { width: 5%; }
    .tabla-rendimiento th:nth-child(10), .tabla-rendimiento td:nth-child(10) { width: 22%; }
    .x-mark { font-weight: bold; font-size: 12pt; color: #000; }
    .input-print, .select-print { border: none; background: transparent; width: 100%; text-align: center; font-size: 8.5pt; font-family: inherit; padding: 0; margin: 0; box-sizing: border-box; }
    .input-print:focus { background-color: #fff9c4; }
    #botones-accion { text-align: center; margin-top: 20px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .mensaje-flotante { position: fixed; top: 20px; right: 20px; z-index: 9999; }
    .btn-edit-obs { background: none; border: none; cursor: pointer; font-size: 10pt; margin-left: 5px; padding: 0 2px; color: #0056b3; }
    .obs-cell { display: flex; align-items: center; justify-content: center; gap: 4px; flex-wrap: nowrap; }
    .obs-cell input { flex: 1; min-width: 0; }
    @media print { body, .hoja-rendimiento { margin: 0; padding: 10px; } .btn, #botones-accion { display: none !important; } .btn-edit-obs { display: none !important; } .tabla-rendimiento { font-size: 7pt; } .tabla-rendimiento th, .tabla-rendimiento td { padding: 1px; } .obs-cell input { border: none; background: transparent; } .obs-cell .btn-edit-obs { display: none !important; } }
    .modo-exportacion .btn-edit-obs { display: none !important; }
</style>

<div class="container-fluid">
    <div class="hoja-rendimiento" id="documento-pdf">
        <div class="encabezado-ministerio">
             <h5>NOMINA DE ALUMNOS RENDIMIENTO FINAL PERÍODO ESCOLAR: <?= htmlspecialchars($periodo) ?></h5>
           
        </div>

        <div class="row mb-2">
            <div class="col-8">
                <strong>DOCENTE:</strong> <?= mb_strtoupper($nombre_profesor) ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <strong>GRADO:</strong> <?= htmlspecialchars($sala_seleccionada) ?> "<?= htmlspecialchars($nombre_seccion) ?>"
            </div>
            <div class="col-4">
                <strong>VARONES:</strong> <?= $varones ?>&nbsp; &nbsp;
                <strong>HEMBRAS:</strong> <?= $hembras ?> &nbsp;&nbsp;  
                <strong>TOTAL:</strong> <?= $total_alumnos ?> 
                <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
            </div>
        </div>

        <form id="form-rendimiento">
            <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
            <input type="hidden" name="sala" value="<?= htmlspecialchars($sala_seleccionada) ?>">
            <input type="hidden" name="seccion" value="<?= htmlspecialchars($seccion_id) ?>">
            <input type="hidden" name="profesor" value="<?= htmlspecialchars($profesor_id) ?>">

            <table class="tabla-rendimiento">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>CÉDULA ESCOLAR</th>
                        <th class="text-left">NOMBRE DEL ALUMNO</th>
                        <th>SEXO</th>
                        <th>LUGAR<br>NAC.</th>
                        <th>FECHA<br>NAC.</th>
                        <th>APROBADO</th>
                        <th>APLAZADO</th>
                        <th>LITERAL</th>
                        <th>OBSERVACIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($estudiantes)): ?>
                        <tr><td colspan="10" class="text-center">No hay estudiantes registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($estudiantes as $index => $est):
                            $fecha_nac = $est['fecha_nacimiento'] ? date('d/m/Y', strtotime($est['fecha_nacimiento'])) : '';
                            $cedula = !empty($est['cedula_escolar']) ? htmlspecialchars($est['cedula_escolar']) : (htmlspecialchars($est['cedula'] ?? 'S/C'));
                            $nombre_completo = mb_strtoupper(htmlspecialchars($est['nombre'] . ' ' . $est['apellido']));
                            $genero = mb_strtoupper(htmlspecialchars($est['genero'] ?? ''));
                            $lugar_nac = mb_strtoupper(htmlspecialchars($est['lugar_nacimiento'] ?? 'N/A'));

                            // ========== VERIFICAR LAPSOS COMPLETOS ==========
                            $lapso1_completo = !empty($est['m1_proyecto']) && !empty($est['m1_formacion']) && !empty($est['m1_sugerencias']);
                            $lapso2_completo = !empty($est['m2_proyecto']) && !empty($est['m2_formacion']) && !empty($est['m2_sugerencias']);
                            $lapso3_completo = !empty($est['m3_proyecto']) && !empty($est['m3_formacion']) && !empty($est['m3_sugerencias']);
                            $todos_lapsos_completos = $lapso1_completo && $lapso2_completo && $lapso3_completo;

                            // Variables para mostrar
                            $aprobado = '';
                            $aplazado = '';
                            $literal_final_mostrar = '';
                            $resultado_mostrar = '';

                            if ($todos_lapsos_completos) {
                                // Solo si todos los lapsos están completos, mostramos el literal y el estado
                                $literal_final_mostrar = htmlspecialchars($est['literal_final'] ?? '');
                                $resultado_mostrar = htmlspecialchars($est['resultado_final'] ?? '');
                                
                                // Determinar si es aprobado o aplazado según literal
                                if ($literal_final_mostrar == 'E') {
                                    $aplazado = 'X';
                                } elseif (in_array($literal_final_mostrar, ['A','B','C','D'])) {
                                    $aprobado = 'X';
                                } else {
                                    // Si no hay literal, usar campos de la BD
                                    if ($est['aprobado'] == 'SI' || in_array($resultado_mostrar, ['Promovido', 'Aprobado'])) {
                                        $aprobado = 'X';
                                    } elseif ($est['aprobado'] == 'NO' || in_array($resultado_mostrar, ['Aplazado', 'Reprobado'])) {
                                        $aplazado = 'X';
                                    }
                                }
                            }

                            $observacion_boletin = htmlspecialchars($est['observacion_boletin'] ?? '', ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= $cedula ?></td>
                            <td class="text-left"><?= $nombre_completo ?></td>
                            <td><?= $genero ?></td>
                            <td><?= $lugar_nac ?></td>
                            <td><?= $fecha_nac ?></td>
                            <td><span class="x-mark"><?= $aprobado ?></span></td>
                            <td><span class="x-mark"><?= $aplazado ?></span></td>
                            <td><span class="x-mark"><?= $literal_final_mostrar ?></span></td>
                            <td>
                                <div class="obs-cell">
                                    <input type="text" name="observacion[<?= $est['id'] ?>]" class="input-print obs-input" data-id="<?= $est['id'] ?>" value="<?= $observacion_boletin ?>">
                                    <button type="button" class="btn-edit-obs no-pdf" data-id="<?= $est['id'] ?>" data-nombre="<?= $nombre_completo ?>" data-obs="<?= $observacion_boletin ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>

        <div id="botones-accion" class="btn-accion">
            <button type="button" class="btn btn-dark" id="btnGuardar">GUARDAR DATOS</button>
            <button type="button" class="btn btn-outline-dark" id="btnDescargarPDF">VER PDF</button>
            <button type="button" class="btn btn-danger" id="btnLimpiarObservaciones">LIMPIAR OBSERVACIONES</button>
            <button type="button" class="btn btn-secondary" id="btnVolver">← VOLVER</button>
        </div>
    </div>
</div>

<!-- Modal Observación -->
<div class="modal fade" id="modalObservacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar observación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Estudiante:</strong> <span id="modalEstudianteNombre"></span></p>
                <div class="mb-3">
                    <label for="modalObservacionTexto" class="form-label">Observación</label>
                    <textarea class="form-control" id="modalObservacionTexto" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarObservacionBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodo = '<?= htmlspecialchars($periodo) ?>';
    const btnGuardar = document.getElementById('btnGuardar');
    const btnDescargar = document.getElementById('btnDescargarPDF');
    const btnLimpiar = document.getElementById('btnLimpiarObservaciones');
    const btnVolver = document.getElementById('btnVolver');
    const form = document.getElementById('form-rendimiento');

    function getStorageKey() { return 'obs_temp_' + periodo; }
    function guardarObservacionesEnLocalStorage() {
        const inputs = document.querySelectorAll('.obs-input');
        const observaciones = {};
        inputs.forEach(input => {
            const id = input.getAttribute('data-id');
            if (id) observaciones[id] = input.value;
        });
        localStorage.setItem(getStorageKey(), JSON.stringify(observaciones));
    }
    function cargarObservacionesDesdeLocalStorage() {
        const data = localStorage.getItem(getStorageKey());
        if (!data) return;
        try {
            const observaciones = JSON.parse(data);
            document.querySelectorAll('.obs-input').forEach(input => {
                const id = input.getAttribute('data-id');
                if (id && observaciones.hasOwnProperty(id)) {
                    input.value = observaciones[id];
                }
            });
        } catch(e) { console.warn(e); }
    }
    function limpiarObservaciones() {
        document.querySelectorAll('.obs-input').forEach(input => { input.value = ''; });
        localStorage.removeItem(getStorageKey());
        mostrarMensaje('Observaciones limpiadas', 'info');
    }
    function mostrarMensaje(msg, tipo = 'success') {
        const container = document.getElementById('mensaje-container');
        container.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show">${msg} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        setTimeout(() => container.innerHTML = '', 4000);
    }
    cargarObservacionesDesdeLocalStorage();
    document.querySelectorAll('.obs-input').forEach(input => {
        input.addEventListener('input', guardarObservacionesEnLocalStorage);
    });

    btnGuardar.addEventListener('click', function() {
        const formData = new FormData(form);
        fetch('guardar_rendimiento_preinicial.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                mostrarMensaje(data.message, data.success ? 'success' : 'danger');
                if (data.success) localStorage.removeItem(getStorageKey());
            })
            .catch(() => mostrarMensaje('Error de conexión', 'danger'));
    });

    btnLimpiar.addEventListener('click', limpiarObservaciones);
    btnVolver.addEventListener('click', function() { window.location.href = 'primaria.php'; });

    btnDescargar.addEventListener('click', function() {
        const elemento = document.getElementById('documento-pdf');
        const botones = document.getElementById('botones-accion');
        const botonesEdicion = elemento.querySelectorAll('.btn-edit-obs');
        botonesEdicion.forEach(btn => btn.style.display = 'none');
        botones.style.display = 'none';
        elemento.classList.add('modo-exportacion');

        const opciones = {
            margin: 0.05,
            filename: "rendimiento_final_primaria.pdf",
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, letterRendering: true, useCORS: true },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
        };

        html2pdf().set(opciones).from(elemento).output('blob').then(blob => {
            const url = URL.createObjectURL(blob);
            window.open(url, '_blank');
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            botonesEdicion.forEach(btn => btn.style.display = '');
            botones.style.display = 'flex';
            elemento.classList.remove('modo-exportacion');
        }).catch(err => {
            console.error(err);
            botonesEdicion.forEach(btn => btn.style.display = '');
            botones.style.display = 'flex';
            elemento.classList.remove('modo-exportacion');
            mostrarMensaje('Error al generar PDF', 'danger');
        });
    });

    // Modal observación
    const modalElement = document.getElementById('modalObservacion');
    let modal = null;
    if (modalElement) modal = new bootstrap.Modal(modalElement);
    let currentInput = null;

    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit-obs');
        if (!btn) return;
        const nombre = btn.getAttribute('data-nombre');
        const cell = btn.closest('.obs-cell');
        const inputObs = cell ? cell.querySelector('input') : null;
        if (inputObs) {
            currentInput = inputObs;
        }
        document.getElementById('modalEstudianteNombre').innerText = nombre;
        document.getElementById('modalObservacionTexto').value = currentInput ? currentInput.value : '';
        if (modal) modal.show();
    });

    document.getElementById('guardarObservacionBtn').addEventListener('click', function() {
        if (currentInput) {
            const nuevaObs = document.getElementById('modalObservacionTexto').value;
            currentInput.value = nuevaObs;
            guardarObservacionesEnLocalStorage();
        }
        if (modal) modal.hide();
    });

    const container = document.createElement('div');
    container.id = 'mensaje-container';
    container.className = 'mensaje-flotante';
    document.body.appendChild(container);
});
</script>
<?php include "../includes/footer.php"; ?>