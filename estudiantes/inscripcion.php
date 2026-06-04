<?php
session_start();
require_once '../config/conexion.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';

$secciones = $conexion->query("SELECT id, sala, nombre FROM secciones ORDER BY sala, nombre");
$opciones_secciones = '<option value="">Seleccione</option>';
while($sec = $secciones->fetch_assoc()) {
    $opciones_secciones .= '<option value="' . htmlspecialchars($sec['sala'] . ' - Sección ' . $sec['nombre']) . '">' . htmlspecialchars($sec['sala'] . ' - Sección ' . $sec['nombre']) . '</option>';
}
?>

<div class="container mt-4 mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-navy text-white rounded-top">
            <h4 class="mb-0"><i class="fas fa-edit me-2"></i> Inscripción de Estudiante</h4>
        </div>
        <div class="card-body p-4">
            <div class="progress mb-4" style="height: 6px;">
                <div id="progressBar" class="progress-bar bg-success" style="width: 25%;"></div>
            </div>

            <form id="wizardForm" action="procesar_inscripcion.php" method="POST">
                <!-- Indicador de pasos -->
                <ul class="nav nav-tabs nav-justified mb-4 border-0" id="stepTabs">
                    <li class="nav-item"><a class="nav-link active rounded-0" href="#step1" data-step="1">1. Datos del Alumno</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step2" data-step="2">2. Datos del Representante</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step3" data-step="3">3. Datos de los Padres</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step4" data-step="4">4. Historial Escolar</a></li>
                </ul>

                <!-- ================= PASO 1 ================= -->
                <div id="step1" class="step p-3 bg-light rounded-3 mb-3">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">Datos del Alumno</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombres y Apellidos <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Nombre completo del estudiante (tal como aparece en la cédula escolar)"></i>
                            </label>
                            <input type="text" name="nombre" class="form-control text-uppercase" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Fecha Nacimiento <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Sexo <span class="text-danger">*</span></label>
                            <select name="genero" class="form-select" required><option value="">--</option><option value="V">Varón</option><option value="H">Hembra</option></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Orden nacimiento en el año
                                <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Número de orden si hay más de un hijo nacido en el mismo año (1,2,3...). Por defecto 1."></i>
                            </label>
                            <input type="number" name="orden_nacimiento" id="orden_nacimiento" class="form-control" value="1" min="1" max="9">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cédula Escolar (automática)
                                <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Se genera automáticamente con la cédula del representante y la fecha de nacimiento"></i>
                            </label>
                            <input type="text" id="cedula_escolar_auto" class="form-control bg-light" readonly>
                            <input type="hidden" name="cedula_escolar" id="cedula_escolar_hidden">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nacionalidad</label>
                            <input type="text" name="nacionalidad" class="form-control" value="Venezolana">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">País de Nacimiento</label>
                            <input type="text" name="pais_nacimiento" class="form-control" value="Venezuela">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado de Nacimiento</label>
                            <input type="text" name="estado_nacimiento" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dirección del Alumno</label>
                            <textarea name="direccion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Estado Residencia</label>
                            <input type="text" name="estado_residencia" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Municipio</label>
                            <input type="text" name="municipio" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Parroquia</label>
                            <input type="text" name="parroquia" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Ciudad</label>
                            <input type="text" name="ciudad" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">¿Sufre enfermedad?</label>
                            <select name="enfermedad" id="enfermedad" class="form-select">
                                <option value="No">No</option><option value="Si">Sí</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="div_enfermedad_cual" style="display: none;">
                            <label class="form-label fw-semibold">¿Cuál enfermedad?</label>
                            <input type="text" name="enfermedad_cual" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">¿Puede hacer Educación Física?</label>
                            <select name="educacion_fisica" id="educacion_fisica" class="form-select">
                                <option value="Si">Sí</option><option value="No">No</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="div_educacion_fisica_porque" style="display: none;">
                            <label class="form-label fw-semibold">¿Por qué no puede?</label>
                            <input type="text" name="educacion_fisica_porque" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">¿Alergia a medicamentos?</label>
                            <select name="alergia" id="alergia" class="form-select">
                                <option value="No">No</option><option value="Si">Sí</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="div_alergia_cual" style="display: none;">
                            <label class="form-label fw-semibold">¿Cuál(es) alergias?</label>
                            <input type="text" name="alergia_cual" class="form-control">
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-primary px-4 next-step">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- ================= PASO 2 (Representante) con tooltips ================= -->
                <div id="step2" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">Datos del Representante</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cédula <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Cédula del representante. Se usará para generar la Cédula Escolar del alumno."></i>
                            </label>
                            <input type="text" name="rep_cedula" id="rep_cedula" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombres y Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="rep_nombre" class="form-control text-uppercase" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Fecha Nacimiento</label>
                            <input type="date" name="rep_fecha_nacimiento" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Estado Civil</label>
                            <input type="text" name="rep_estado_civil" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Afinidad</label>
                            <input type="text" name="rep_afinidad" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Teléfono <span class="text-danger">*</span></label>
                            <input type="text" name="rep_telefono" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Sexo</label>
                            <select name="rep_sexo" class="form-select"><option value="">--</option><option value="V">Varón</option><option value="H">Hembra</option></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">País de Nacimiento</label>
                            <input type="text" name="rep_pais_nacimiento" class="form-control" value="Venezuela">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Estado de Nacimiento</label>
                            <input type="text" name="rep_estado_nacimiento" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Nacionalidad</label>
                            <input type="text" name="rep_nacionalidad" class="form-control" value="Venezolana">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Dirección del Representante</label>
                            <textarea name="rep_direccion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Estado Residencia</label>
                            <input type="text" name="rep_estado_residencia" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Municipio</label>
                            <input type="text" name="rep_municipio" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Parroquia</label>
                            <input type="text" name="rep_parroquia" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Ciudad</label>
                            <input type="text" name="rep_ciudad" class="form-control">
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- ================= PASO 3 ================= -->
                <div id="step3" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">Datos de los Padres</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><label>Nombres y Apellidos de la Madre</label><input type="text" name="madre_nombre" class="form-control text-uppercase"></div>
                        <div class="col-md-3"><label>Cédula</label><input type="text" name="madre_cedula" class="form-control"></div>
                        <div class="col-md-3"><label>Teléfono</label><input type="text" name="madre_telefono" class="form-control"></div>
                        <div class="col-md-6"><label>Nombre y Apellido del Padre</label><input type="text" name="padre_nombre" class="form-control text-uppercase"></div>
                        <div class="col-md-3"><label>Cédula</label><input type="text" name="padre_cedula" class="form-control"></div>
                        <div class="col-md-3"><label>Teléfono</label><input type="text" name="padre_telefono" class="form-control"></div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- ================= PASO 4 ================= -->
                <div id="step4" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">Historial Escolar</h5>
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle me-2"></i> Registre los años escolares. Puede agregar varias filas. Los campos con <i class="fas fa-question-circle"></i> tienen ayuda.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="tablaHistorial">
                            <thead class="table-light">
                                <tr>
                                    <th>Año Escolar <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" title="Ejemplo: 2024-2025"></i></th>
                                    <th>Grado y Sección <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" title="Seleccione de la lista desplegable"></i></th>
                                    <th>Reg. <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" title="Número de registro del estudiante"></i></th>
                                    <th>Rep. <i class="fas fa-question-circle text-muted" data-bs-toggle="tooltip" title="¿Repite el año?"></i></th>
                                    <th>C</th><th>F</th><th>P</th>
                                    <th>Peso (kg)</th><th>Talla (cm)</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="historial-body">
                                <tr class="fila-historial">
                                    <td><input type="text" name="ano_escolar[]" class="form-control form-control-sm" placeholder="2024-2025" required></td>
                                    <td><select name="grado_seccion[]" class="form-select form-select-sm" required><?= $opciones_secciones ?></select></td>
                                    <td><input type="text" name="registro[]" class="form-control form-control-sm"></td>
                                    <td><select name="repite[]" class="form-select form-select-sm"><option value="No">No</option><option value="Si">Si</option></select></td>
                                    <td><input type="text" name="c[]" class="form-control form-control-sm"></td>
                                    <td><input type="text" name="f[]" class="form-control form-control-sm"></td>
                                    <td><input type="text" name="p[]" class="form-control form-control-sm"></td>
                                    <td><input type="number" step="0.1" name="peso[]" class="form-control form-control-sm"></td>
                                    <td><input type="number" step="0.1" name="talla[]" class="form-control form-control-sm"></td>
                                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm eliminar-fila">✖</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-start mt-2">
                        <button type="button" class="btn btn-secondary btn-sm" id="agregarFila"><i class="fas fa-plus me-1"></i> Agregar otro año</button>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i> Guardar Inscripción</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

    // ========== CÉDULA ESCOLAR AUTOMÁTICA ==========
    function generarCedulaEscolar() {
        const repCedula = document.getElementById('rep_cedula')?.value.trim();
        const fechaNac = document.getElementById('fecha_nacimiento')?.value;
        const orden = parseInt(document.getElementById('orden_nacimiento')?.value) || 1;
        if (!repCedula || !fechaNac) {
            document.getElementById('cedula_escolar_auto').value = '';
            document.getElementById('cedula_escolar_hidden').value = '';
            return;
        }
        const año = new Date(fechaNac).getFullYear().toString();
        const año2Dig = año.slice(-2);
        let cedulaLimpia = repCedula.replace(/\D/g, '');
        if (cedulaLimpia.length < 8) cedulaLimpia = cedulaLimpia.padStart(8, '0');
        else if (cedulaLimpia.length > 8) cedulaLimpia = cedulaLimpia.slice(-8);
        const ce = orden.toString() + año2Dig + cedulaLimpia;
        document.getElementById('cedula_escolar_auto').value = ce;
        document.getElementById('cedula_escolar_hidden').value = ce;
    }
    document.getElementById('rep_cedula')?.addEventListener('input', generarCedulaEscolar);
    document.getElementById('fecha_nacimiento')?.addEventListener('change', generarCedulaEscolar);
    document.getElementById('orden_nacimiento')?.addEventListener('input', generarCedulaEscolar);
    generarCedulaEscolar();

    // ========== CAMPOS CONDICIONALES (Sí/No) ==========
    function toggleEnfermedad() {
        const val = document.getElementById('enfermedad').value;
        document.getElementById('div_enfermedad_cual').style.display = val === 'Si' ? 'block' : 'none';
    }
    function toggleEducacionFisica() {
        const val = document.getElementById('educacion_fisica').value;
        document.getElementById('div_educacion_fisica_porque').style.display = val === 'No' ? 'block' : 'none';
    }
    function toggleAlergia() {
        const val = document.getElementById('alergia').value;
        document.getElementById('div_alergia_cual').style.display = val === 'Si' ? 'block' : 'none';
    }
    document.getElementById('enfermedad')?.addEventListener('change', toggleEnfermedad);
    document.getElementById('educacion_fisica')?.addEventListener('change', toggleEducacionFisica);
    document.getElementById('alergia')?.addEventListener('change', toggleAlergia);
    toggleEnfermedad(); toggleEducacionFisica(); toggleAlergia();

    // ========== WIZARD (siguiente/anterior) ==========
    const steps = document.querySelectorAll('.step');
    const nextBtns = document.querySelectorAll('.next-step');
    const prevBtns = document.querySelectorAll('.prev-step');
    const tabs = document.querySelectorAll('#stepTabs .nav-link');
    const progressBar = document.getElementById('progressBar');
    let currentStep = 0;
    const totalSteps = steps.length;
    function updateProgress() { progressBar.style.width = ((currentStep+1)/totalSteps*100)+'%'; }
    function showStep(step) {
        steps.forEach((s,i)=>s.style.display = i===step ? 'block' : 'none');
        tabs.forEach((tab,i)=>{
            if(i===step) { tab.classList.add('active'); tab.classList.remove('disabled'); }
            else { tab.classList.remove('active'); tab.classList.add('disabled'); }
        });
        currentStep = step; updateProgress();
    }
    function nextHandler() { if(currentStep < totalSteps-1) showStep(currentStep+1); }
    function prevHandler() { if(currentStep > 0) showStep(currentStep-1); }
    nextBtns.forEach(btn=>{ btn.removeEventListener('click',nextHandler); btn.addEventListener('click',nextHandler); });
    prevBtns.forEach(btn=>{ btn.removeEventListener('click',prevHandler); btn.addEventListener('click',prevHandler); });
    tabs.forEach((tab,idx)=>tab.addEventListener('click',(e)=>{ e.preventDefault(); if(idx <= currentStep+1) showStep(idx); }));
    showStep(0);

    // Tabla dinámica (Historial Escolar)
    const agregarBtn = document.getElementById('agregarFila');
    const historialBody = document.getElementById('historial-body');
    function agregarFila() {
        const originalRow = historialBody.querySelector('.fila-historial');
        const newRow = originalRow.cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(inp=>{
            if(inp.type==='text' || inp.type==='number') inp.value='';
            if(inp.tagName==='SELECT') inp.selectedIndex=0;
        });
        historialBody.appendChild(newRow);
        // Reinicializar tooltips en la nueva fila
        newRow.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }
    agregarBtn?.addEventListener('click', agregarFila);
    historialBody?.addEventListener('click', e=>{
        if(e.target.classList.contains('eliminar-fila')) {
            if(document.querySelectorAll('.fila-historial').length>1) e.target.closest('tr').remove();
            else alert('Debe haber al menos un registro escolar.');
        }
    });
});
</script>

<style>
    .bg-navy { background-color: #003366 !important; }
    .btn-primary { background-color: #003366; border-color: #003366; }
    .btn-primary:hover { background-color: #002244; }
    .nav-link.active { background-color: #003366 !important; color: white !important; }
    .table-sm th, .table-sm td { padding: 0.3rem; vertical-align: middle; }
    .progress-bar { transition: width 0.3s ease; }
</style>

<?php include '../includes/footer.php'; ?>