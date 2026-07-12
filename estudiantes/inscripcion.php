<?php
session_start();
require_once '../config/conexion.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';

// Captura de datos para precarga desde "Terminar Inscripción"
$prefill = $_GET['prefill'] ?? '';
$prefill_data = [];
if ($prefill === '1') {
    $prefill_data = [
        'apellido' => $_GET['apellido'] ?? '',
        'nombre' => $_GET['nombre'] ?? '',
        'genero' => $_GET['genero'] ?? '',
        'nacionalidad' => $_GET['nacionalidad'] ?? '',
        'ci' => $_GET['ci'] ?? '',
        'fn' => $_GET['fn'] ?? '',
        'fi' => $_GET['fi'] ?? '',
    ];
}

// ========== Mostrar mensajes de error ==========
$error_tipo = $_GET['error'] ?? '';
$mensaje_error = '';

if ($error_tipo === 'duplicado' && isset($_GET['mensaje'])) {
    $mensaje_error = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <strong>¡Estudiante duplicado!</strong><br>
        ' . htmlspecialchars(urldecode($_GET['mensaje'])) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
} elseif ($error_tipo === 'campos_requeridos') {
    $mensaje_error = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <strong>Campos requeridos faltantes.</strong><br>
        Por favor complete todos los campos obligatorios marcados con <span class="text-danger">*</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
} elseif ($error_tipo === '1') {
    $mensaje_error = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <strong>Error al guardar.</strong><br>
        Ocurrió un error al registrar el estudiante. Por favor intente nuevamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// Obtener secciones para el historial
$secciones = $conexion->query("SELECT id, sala, nombre FROM secciones ORDER BY sala, nombre");
$opciones_secciones = '<option value="">Seleccione</option>';
while($sec = $secciones->fetch_assoc()) {
    $opciones_secciones .= '<option value="' . htmlspecialchars($sec['sala'] . ' - Sección ' . $sec['nombre']) . '">' . htmlspecialchars($sec['sala'] . ' - Sección ' . $sec['nombre']) . '</option>';
}
?>

<div class="container mt-4 mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-navy text-white rounded-top">
            <h4 class="mb-0"><i class="fas fa-edit me-2"></i> FICHA DE INSCRIPCIÓN (Formato Institucional)</h4>
        </div>
        <div class="card-body p-4">
            
            <?php if ($mensaje_error): ?>
                <?= $mensaje_error ?>
            <?php endif; ?>

            <?php if (!empty($prefill_data)): ?>
                <div class="alert alert-success alert-dismissible fade show small" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <strong>Datos del ingreso cargados.</strong> Complete los campos restantes y guarde la inscripción.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="progress mb-4" style="height: 6px;">
                <div id="progressBar" class="progress-bar bg-success" style="width: 25%;"></div>
            </div>

            <form id="wizardForm" action="procesar_inscripcion.php" method="POST">
                <!-- Campo oculto para el procesador -->
                <input type="hidden" name="madre_cedula_temp" id="madre_cedula_temp">
                <!-- Campo oculto opcional para la fecha de ingreso desde prefill -->
                <input type="hidden" name="fecha_ingreso_prefill" id="fecha_ingreso_prefill" value="<?= htmlspecialchars($prefill_data['fi'] ?? '') ?>">
                
                <ul class="nav nav-tabs nav-justified mb-4 border-0" id="stepTabs">
                    <li class="nav-item"><a class="nav-link active rounded-0" href="#step1" data-step="1">1. Datos del Alumno</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step2" data-step="2">2. Datos del Representante</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step3" data-step="3">3. Datos de los Padres</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step4" data-step="4">4. Historial Escolar</a></li>
                </ul>

                <!-- STEP 1 -->
                <div id="step1" class="step p-3 bg-light rounded-3 mb-3">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">DATOS DEL ALUMNO</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control text-uppercase" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellido" class="form-control text-uppercase" required>
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
                            <label class="form-label fw-semibold">Orden nacimiento en el año <span class="text-danger">*</span></label>
                            <input type="number" name="orden_nacimiento" id="orden_nacimiento" class="form-control" value="1" min="1" max="9">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cédula de referencia (para generar Cédula Escolar) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="tipo_cedula_base" id="tipo_cedula_base" class="form-select" style="max-width: 140px;">
                                    <option value="madre">Madre</option>
                                    <option value="padre">Padre</option>
                                    <option value="representante">Representante</option>
                                </select>
                                <input type="text" name="cedula_base" id="cedula_base" class="form-control" placeholder="Ej: 09799555" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cédula Escolar (automática)</label>
                            <input type="text" id="cedula_escolar_auto" class="form-control bg-light" readonly>
                            <input type="hidden" name="cedula_escolar" id="cedula_escolar_hidden">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">País de Nacimiento</label>
                            <select name="pais_nacimiento" id="pais_nacimiento" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                            <input type="text" id="input_pais_nacimiento" name="pais_nacimiento_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el país..." style="display:none;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado de Nacimiento</label>
                            <select name="estado_nacimiento" id="estado_nacimiento" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                            <input type="text" id="input_estado_nacimiento" name="estado_nacimiento_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el estado..." style="display:none;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nacionalidad</label>
                            <input type="text" name="nacionalidad" class="form-control" value="Venezolana">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Dirección del Alumno</label>
                            <textarea name="direccion" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado Residencia</label>
                            <select name="estado_residencia" id="estado_residencia" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                            <input type="text" id="input_estado_residencia" name="estado_residencia_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el estado..." style="display:none;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Municipio</label>
                            <select name="municipio" id="municipio" class="form-select" disabled>
                                <option value="">Primero seleccione un estado</option>
                            </select>
                            <input type="text" id="input_municipio" name="municipio_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el municipio..." style="display:none;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Parroquia</label>
                            <select name="parroquia" id="parroquia" class="form-select" disabled>
                                <option value="">Primero seleccione un municipio</option>
                            </select>
                            <input type="text" id="input_parroquia" name="parroquia_otro" class="form-control text-uppercase mt-1" placeholder="Escriba la parroquia..." style="display:none;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Ciudad</label>
                            <input type="text" name="ciudad" class="form-control text-uppercase" placeholder="Ciudad...">
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

                <!-- Los steps 2, 3 y 4 se mantienen exactamente igual que en tu código original, sin cambios. -->
                <!-- ... (copiar aquí el resto del formulario) ... -->
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS y dependencias (por si no están en el footer) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ============================================================================
// SCRIPT UNIFICADO: PRECARGA + WIZARD + FUNCIONES GEOGRÁFICAS
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // ---------- PRECARGA DE DATOS DESDE "Terminar Inscripción" ----------
    <?php if (!empty($prefill_data)): ?>
        // Asignar valores a los campos
        const apellidoField = document.querySelector('input[name="apellido"]');
        const nombreField = document.querySelector('input[name="nombre"]');
        const generoField = document.querySelector('select[name="genero"]');
        const nacionalidadField = document.querySelector('input[name="nacionalidad"]');
        const cedulaBaseField = document.querySelector('input[name="cedula_base"]');
        const fechaNacField = document.querySelector('input[name="fecha_nacimiento"]');
        
        if (apellidoField) apellidoField.value = '<?= htmlspecialchars($prefill_data['apellido']) ?>';
        if (nombreField) nombreField.value = '<?= htmlspecialchars($prefill_data['nombre']) ?>';
        if (generoField) generoField.value = '<?= $prefill_data['genero'] ?>';
        if (nacionalidadField) nacionalidadField.value = '<?= htmlspecialchars($prefill_data['nacionalidad']) ?>';
        if (cedulaBaseField) cedulaBaseField.value = '<?= htmlspecialchars($prefill_data['ci']) ?>';
        if (fechaNacField) fechaNacField.value = '<?= $prefill_data['fn'] ?>';
        
        // Disparar eventos para que se generen la cédula escolar y sincronización
        if (cedulaBaseField) {
            cedulaBaseField.dispatchEvent(new Event('input', { bubbles: true }));
            cedulaBaseField.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (fechaNacField) {
            fechaNacField.dispatchEvent(new Event('input', { bubbles: true }));
            fechaNacField.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (generoField) {
            generoField.dispatchEvent(new Event('change', { bubbles: true }));
        }
    <?php endif; ?>

    // ---------- TOOLTIPS ----------
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

    // ---------- GENERAR CÉDULA ESCOLAR ----------
    function generarCedulaEscolar() {
        const cedulaBaseRaw = document.getElementById('cedula_base')?.value.trim();
        const fechaNac = document.getElementById('fecha_nacimiento')?.value;
        const orden = parseInt(document.getElementById('orden_nacimiento')?.value) || 1;
        
        document.getElementById('madre_cedula_temp').value = cedulaBaseRaw;

        if (!cedulaBaseRaw || !fechaNac) {
            document.getElementById('cedula_escolar_auto').value = '';
            document.getElementById('cedula_escolar_hidden').value = '';
            return;
        }
        const año = new Date(fechaNac).getFullYear().toString();
        const año2Dig = año.slice(-2);
        let cedulaLimpia = cedulaBaseRaw.replace(/\D/g, '');
        if (cedulaLimpia.length < 8) {
            cedulaLimpia = cedulaLimpia.padStart(8, '0');
        } else if (cedulaLimpia.length > 8) {
            cedulaLimpia = cedulaLimpia.slice(-8);
        }
        const ce = orden.toString() + año2Dig + cedulaLimpia;
        document.getElementById('cedula_escolar_auto').value = ce;
        document.getElementById('cedula_escolar_hidden').value = ce;
    }

    // ---------- SINCRONIZAR CÉDULA A LOS PADRES ----------
    function sincronizarCedula() {
        const tipo = document.getElementById('tipo_cedula_base')?.value;
        const cedula = document.getElementById('cedula_base')?.value;
        const inputMadre = document.getElementById('madre_cedula');
        const inputPadre = document.getElementById('padre_cedula');
        if (inputMadre && inputPadre) {
            if (tipo === 'madre') {
                inputMadre.value = cedula;
                if (inputPadre.value === cedula) inputPadre.value = '';
            } else if (tipo === 'padre') {
                inputPadre.value = cedula;
                if (inputMadre.value === cedula) inputMadre.value = '';
            }
        }
    }

    // ---------- EVENTOS ----------
    document.getElementById('cedula_base')?.addEventListener('input', function() {
        generarCedulaEscolar();
        sincronizarCedula();
    });
    document.getElementById('tipo_cedula_base')?.addEventListener('change', sincronizarCedula);
    document.getElementById('fecha_nacimiento')?.addEventListener('change', generarCedulaEscolar);
    document.getElementById('orden_nacimiento')?.addEventListener('input', generarCedulaEscolar);
    document.getElementById('madre_cedula')?.addEventListener('input', function() {
        const madreStep3 = this.value.trim();
        const cedulaBase = document.getElementById('cedula_base');
        const tipoCedula = document.getElementById('tipo_cedula_base');
        if (cedulaBase && tipoCedula && tipoCedula.value === 'madre' && cedulaBase.value !== madreStep3) {
            cedulaBase.value = madreStep3;
            generarCedulaEscolar();
        }
    });

    // Campos condicionales
    function toggleEnfermedad() { document.getElementById('div_enfermedad_cual').style.display = document.getElementById('enfermedad').value === 'Si' ? 'block' : 'none'; }
    function toggleEducacionFisica() { document.getElementById('div_educacion_fisica_porque').style.display = document.getElementById('educacion_fisica').value === 'No' ? 'block' : 'none'; }
    function toggleAlergia() { document.getElementById('div_alergia_cual').style.display = document.getElementById('alergia').value === 'Si' ? 'block' : 'none'; }
    document.getElementById('enfermedad')?.addEventListener('change', toggleEnfermedad);
    document.getElementById('educacion_fisica')?.addEventListener('change', toggleEducacionFisica);
    document.getElementById('alergia')?.addEventListener('change', toggleAlergia);
    toggleEnfermedad(); toggleEducacionFisica(); toggleAlergia();

    // ---------- WIZARD ----------
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
    
    function nextHandler() { 
        const currentStepEl = steps[currentStep];
        const inputs = currentStepEl.querySelectorAll('input, select, textarea');
        let isValid = true;
        for (let i = 0; i < inputs.length; i++) {
            if (!inputs[i].checkValidity()) {
                inputs[i].reportValidity();
                isValid = false;
                break;
            }
        }
        if (isValid && currentStep < totalSteps-1) showStep(currentStep+1); 
    }
    
    function prevHandler() { if(currentStep > 0) showStep(currentStep-1); }
    nextBtns.forEach(btn=>{ btn.removeEventListener('click',nextHandler); btn.addEventListener('click',nextHandler); });
    prevBtns.forEach(btn=>{ btn.removeEventListener('click',prevHandler); btn.addEventListener('click',prevHandler); });
    showStep(0);

    // Validación final
    const wizardForm = document.getElementById('wizardForm');
    if (wizardForm) {
        wizardForm.addEventListener('submit', function(e) {
            generarCedulaEscolar();
            if (!this.checkValidity()) {
                e.preventDefault();
                const primerInvalido = this.querySelector(':invalid');
                if (primerInvalido) {
                    const stepPadre = primerInvalido.closest('.step');
                    const stepIndex = Array.from(steps).indexOf(stepPadre);
                    if (stepIndex !== -1) {
                        showStep(stepIndex);
                        setTimeout(() => primerInvalido.reportValidity(), 100);
                    }
                }
            }
        });
    }

    // Tabla dinámica
    const agregarBtn = document.getElementById('agregarFila');
    const historialBody = document.getElementById('historial-body');
    function agregarFilaHistorial() {
        const originalRow = historialBody.querySelector('.fila-historial');
        const newRow = originalRow.cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(inp=>{
            if(inp.type==='text' || inp.type==='number') inp.value='';
            if(inp.tagName==='SELECT') inp.selectedIndex=0;
        });
        historialBody.appendChild(newRow);
        newRow.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }
    agregarBtn?.addEventListener('click', agregarFilaHistorial);
    historialBody?.addEventListener('click', e=>{
        if(e.target.classList.contains('eliminar-fila')) {
            if(document.querySelectorAll('.fila-historial').length>1) e.target.closest('tr').remove();
            else alert('Debe haber al menos un registro escolar.');
        }
    });

    // ---------- CARGA DE DATOS GEOGRÁFICOS ----------
    function cargarSelectArcGIS(url, selectId, valorDefault, callback) {
        fetch(url)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById(selectId);
                if (!select) return;
                const firstOption = select.options[0];
                select.innerHTML = '';
                select.appendChild(firstOption);
                if (data.length > 0) {
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.nombre;
                        option.textContent = item.nombre;
                        select.appendChild(option);
                    });
                }
                const optionOtro = document.createElement('option');
                optionOtro.value = 'OTRO';
                optionOtro.textContent = '--- OTRO ---';
                optionOtro.style.fontWeight = 'bold';
                optionOtro.style.color = '#dc3545';
                select.appendChild(optionOtro);
                if (valorDefault) select.value = valorDefault;
                if (callback) callback();
            })
            .catch(error => {
                console.error('Error:', error);
                const select = document.getElementById(selectId);
                if (select) select.innerHTML = '<option value="">Error al cargar datos</option>';
            });
    }

    function manejarOtro(selectId, inputId) {
        const select = document.getElementById(selectId);
        const input = document.getElementById(inputId);
        if (!select || !input) return;
        select.addEventListener('change', function() {
            if (this.value === 'OTRO') {
                this.style.display = 'none';
                input.style.display = 'block';
                input.value = '';
                input.focus();
                input.required = true;
                input.dataset.valorAnterior = this.dataset.valorAnterior || '';
            } else {
                this.style.display = 'block';
                input.style.display = 'none';
                input.value = '';
                input.required = false;
                this.dataset.valorAnterior = this.value;
            }
        });
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                select.style.display = 'block';
                this.style.display = 'none';
                this.required = false;
                if (select.dataset.valorAnterior) select.value = select.dataset.valorAnterior;
                else select.value = '';
            }
        });
    }

    function crearInputOtro(selectId, inputId, placeholder) {
        let input = document.getElementById(inputId);
        if (!input) {
            input = document.createElement('input');
            input.type = 'text';
            input.id = inputId;
            input.name = inputId.replace('input_', '');
            input.className = 'form-control text-uppercase mt-1';
            input.placeholder = placeholder || 'Escriba aquí...';
            input.style.display = 'none';
            const select = document.getElementById(selectId);
            if (select) select.parentNode.appendChild(input);
        }
        return input;
    }

    function cargarPaises() {
        // Código para cargar países (Venezuela por defecto + opción OTRO)
        const selectPais = document.getElementById('pais_nacimiento');
        selectPais.innerHTML = '<option value="">Seleccione...</option>';
        const optionVen = document.createElement('option');
        optionVen.value = 'Venezuela';
        optionVen.textContent = 'Venezuela';
        selectPais.appendChild(optionVen);
        const optionOtro = document.createElement('option');
        optionOtro.value = 'OTRO';
        optionOtro.textContent = '--- OTRO ---';
        optionOtro.style.fontWeight = 'bold';
        optionOtro.style.color = '#dc3545';
        selectPais.appendChild(optionOtro);
        selectPais.value = 'Venezuela';
        crearInputOtro('pais_nacimiento', 'input_pais_nacimiento', 'Escriba el país...');
        manejarOtro('pais_nacimiento', 'input_pais_nacimiento');

        const selectRepPais = document.getElementById('rep_pais_nacimiento');
        selectRepPais.innerHTML = '<option value="">Seleccione...</option>';
        const optionVen2 = document.createElement('option');
        optionVen2.value = 'Venezuela';
        optionVen2.textContent = 'Venezuela';
        selectRepPais.appendChild(optionVen2);
        const optionOtro2 = document.createElement('option');
        optionOtro2.value = 'OTRO';
        optionOtro2.textContent = '--- OTRO ---';
        optionOtro2.style.fontWeight = 'bold';
        optionOtro2.style.color = '#dc3545';
        selectRepPais.appendChild(optionOtro2);
        selectRepPais.value = 'Venezuela';
        crearInputOtro('rep_pais_nacimiento', 'input_rep_pais_nacimiento', 'Escriba el país...');
        manejarOtro('rep_pais_nacimiento', 'input_rep_pais_nacimiento');
    }

    function cargarEstados() {
        const url = 'ajax_geografico.php?action=get_estados';
        ['estado_nacimiento', 'estado_residencia', 'rep_estado_nacimiento', 'rep_estado_residencia'].forEach(id => {
             cargarSelectArcGIS(url, id, '', function() {
                crearInputOtro(id, 'input_' + id, 'Escriba el estado...');
                manejarOtro(id, 'input_' + id);
            });
        });
    }

    function cargarMunicipios(estado, selectMunicipioId, selectParroquiaId, valorDefault) {
        if (!estado || estado === 'OTRO') return;
        const url = 'ajax_geografico.php?action=get_municipios&estado=' + encodeURIComponent(estado);
        cargarSelectArcGIS(url, selectMunicipioId, valorDefault, function() {
            crearInputOtro(selectMunicipioId, 'input_' + selectMunicipioId, 'Escriba el municipio...');
            manejarOtro(selectMunicipioId, 'input_' + selectMunicipioId);
            document.getElementById(selectMunicipioId).disabled = false;
        });
    }

    function cargarParroquias(municipio, selectParroquiaId, valorDefault) {
        if (!municipio || municipio === 'OTRO') return;
        const url = 'ajax_geografico.php?action=get_parroquias&municipio=' + encodeURIComponent(municipio);
        cargarSelectArcGIS(url, selectParroquiaId, valorDefault, function() {
            crearInputOtro(selectParroquiaId, 'input_' + selectParroquiaId, 'Escriba la parroquia...');
            manejarOtro(selectParroquiaId, 'input_' + selectParroquiaId);
            document.getElementById(selectParroquiaId).disabled = false;
        });
    }

    // Listeners geográficos
    document.getElementById('estado_nacimiento')?.addEventListener('change', function() { cargarMunicipios(this.value, 'municipio', 'parroquia', ''); });
    document.getElementById('estado_residencia')?.addEventListener('change', function() { cargarMunicipios(this.value, 'municipio', 'parroquia', ''); });
    document.getElementById('municipio')?.addEventListener('change', function() { cargarParroquias(this.value, 'parroquia', ''); });
    document.getElementById('rep_estado_nacimiento')?.addEventListener('change', function() { cargarMunicipios(this.value, 'rep_municipio', 'rep_parroquia', ''); });
    document.getElementById('rep_estado_residencia')?.addEventListener('change', function() { cargarMunicipios(this.value, 'rep_municipio', 'rep_parroquia', ''); });
    document.getElementById('rep_municipio')?.addEventListener('change', function() { cargarParroquias(this.value, 'rep_parroquia', ''); });

    cargarPaises();
    cargarEstados();

    // Pequeño retraso para asegurar que todo se haya renderizado antes de generar cédula
    setTimeout(generarCedulaEscolar, 100);
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