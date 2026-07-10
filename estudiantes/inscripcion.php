<?php
session_start();
require_once '../config/conexion.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';

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

            <div class="progress mb-4" style="height: 6px;">
                <div id="progressBar" class="progress-bar bg-success" style="width: 25%;"></div>
            </div>

            <form id="wizardForm" action="procesar_inscripcion.php" method="POST">
                <ul class="nav nav-tabs nav-justified mb-4 border-0" id="stepTabs">
                    <li class="nav-item"><a class="nav-link active rounded-0" href="#step1" data-step="1">1. Datos del Alumno</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step2" data-step="2">2. Datos del Representante</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step3" data-step="3">3. Datos de los Padres</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step4" data-step="4">4. Historial Escolar</a></li>
                </ul>

                <!-- ================= PASO 1: ALUMNO ================= -->
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
                            <label class="form-label fw-semibold">Orden nacimiento en el año <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Indica el orden cronológico de nacimiento entre hermanos del mismo padre y madre (ej. 1 para el primero, 2 para el segundo). Este valor es el primer dígito utilizado para generar automáticamente la cédula estudiantil."></i>
                            </label>
                            <input type="number" name="orden_nacimiento" id="orden_nacimiento" class="form-control" value="1" min="1" max="9">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cédula de la Madre (para generar Cédula Escolar) <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Se usará para generar la Cédula Escolar de 11 dígitos"></i>
                            </label>
                            <input type="text" name="madre_cedula_temp" id="madre_cedula_temp" class="form-control" placeholder="Ej: 09799555" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cédula Escolar (automática)</label>
                            <input type="text" id="cedula_escolar_auto" class="form-control bg-light" readonly>
                            <input type="hidden" name="cedula_escolar" id="cedula_escolar_hidden">
                        </div>

                        <!-- ========== Ubicación estilo SAIME ========== -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">País de Nacimiento</label>
                            <select name="pais_nacimiento" id="pais_nacimiento" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                            <!-- Input oculto para "OTRO" -->
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
                        <!-- ========== FIN ubicación estilo SAIME ========== -->

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

                <!-- ================= PASO 2: REPRESENTANTE ================= -->
                <div id="step2" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">DATOS DEL REPRESENTANTE</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cédula <span class="text-danger">*</span></label>
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

                        <!-- ========== Ubicación estilo SAIME para Representante ========== -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">País de Nacimiento</label>
                            <select name="rep_pais_nacimiento" id="rep_pais_nacimiento" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                            <input type="text" id="input_rep_pais_nacimiento" name="rep_pais_nacimiento_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el país..." style="display:none;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Estado de Nacimiento</label>
                            <select name="rep_estado_nacimiento" id="rep_estado_nacimiento" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                            <input type="text" id="input_rep_estado_nacimiento" name="rep_estado_nacimiento_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el estado..." style="display:none;">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Nacionalidad</label>
                            <input type="text" name="rep_nacionalidad" class="form-control" value="Venezolana">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Dirección del Representante</label>
                            <textarea name="rep_direccion" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado Residencia</label>
                            <select name="rep_estado_residencia" id="rep_estado_residencia" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                            <input type="text" id="input_rep_estado_residencia" name="rep_estado_residencia_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el estado..." style="display:none;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Municipio</label>
                            <select name="rep_municipio" id="rep_municipio" class="form-select" disabled>
                                <option value="">Primero seleccione un estado</option>
                            </select>
                            <input type="text" id="input_rep_municipio" name="rep_municipio_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el municipio..." style="display:none;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Parroquia</label>
                            <select name="rep_parroquia" id="rep_parroquia" class="form-select" disabled>
                                <option value="">Primero seleccione un municipio</option>
                            </select>
                            <input type="text" id="input_rep_parroquia" name="rep_parroquia_otro" class="form-control text-uppercase mt-1" placeholder="Escriba la parroquia..." style="display:none;">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Ciudad</label>
                            <input type="text" name="rep_ciudad" class="form-control text-uppercase" placeholder="Ciudad...">
                        </div>
                        <!-- ========== FIN ubicación estilo SAIME ========== -->

                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- ================= PASO 3: PADRES ================= -->
                <div id="step3" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">DATOS DE LOS PADRES</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><label>Nombres y Apellidos de la Madre</label><input type="text" name="madre_nombre" id="madre_nombre" class="form-control text-uppercase"></div>
                        <div class="col-md-3"><label>Cédula</label><input type="text" name="madre_cedula" id="madre_cedula" class="form-control"></div>
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

                <!-- ================= PASO 4: HISTORIAL ESCOLAR ================= -->
                <div id="step4" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">GRADO Y SECCIÓN (Historial Escolar)</h5>
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle me-2"></i> Registre los años escolares. Puede agregar varias filas.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="tablaHistorial">
                            <thead class="table-light">
                                <tr>
                                    <th>Año Escolar</th>
                                    <th>Grado y Sección</th>
                                    <th>Reg.</th>
                                    <th>Rep.</th>
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
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

    // ========== GENERAR CÉDULA ESCOLAR ==========
    function generarCedulaEscolar() {
        const madreCedulaRaw = document.getElementById('madre_cedula_temp')?.value.trim();
        const fechaNac = document.getElementById('fecha_nacimiento')?.value;
        const orden = parseInt(document.getElementById('orden_nacimiento')?.value) || 1;
        if (!madreCedulaRaw || !fechaNac) {
            document.getElementById('cedula_escolar_auto').value = '';
            document.getElementById('cedula_escolar_hidden').value = '';
            return;
        }
        const año = new Date(fechaNac).getFullYear().toString();
        const año2Dig = año.slice(-2);
        let cedulaLimpia = madreCedulaRaw.replace(/\D/g, '');
        if (cedulaLimpia.length < 8) {
            cedulaLimpia = cedulaLimpia.padStart(8, '0');
        } else if (cedulaLimpia.length > 8) {
            cedulaLimpia = cedulaLimpia.slice(-8);
        }
        const ce = orden.toString() + año2Dig + cedulaLimpia;
        document.getElementById('cedula_escolar_auto').value = ce;
        document.getElementById('cedula_escolar_hidden').value = ce;
        
        const madreCedulaStep3 = document.getElementById('madre_cedula');
        if (madreCedulaStep3 && madreCedulaStep3.value !== madreCedulaRaw) {
            madreCedulaStep3.value = madreCedulaRaw;
        }
    }
    document.getElementById('madre_cedula_temp')?.addEventListener('input', generarCedulaEscolar);
    document.getElementById('fecha_nacimiento')?.addEventListener('change', generarCedulaEscolar);
    document.getElementById('orden_nacimiento')?.addEventListener('input', generarCedulaEscolar);
    generarCedulaEscolar();

    document.getElementById('madre_cedula')?.addEventListener('input', function() {
        const madreStep3 = this.value.trim();
        const madreTemp = document.getElementById('madre_cedula_temp');
        if (madreTemp && madreTemp.value !== madreStep3) {
            madreTemp.value = madreStep3;
            generarCedulaEscolar();
        }
    });

    // Mostrar/ocultar campos condicionales
    function toggleEnfermedad() { document.getElementById('div_enfermedad_cual').style.display = document.getElementById('enfermedad').value === 'Si' ? 'block' : 'none'; }
    function toggleEducacionFisica() { document.getElementById('div_educacion_fisica_porque').style.display = document.getElementById('educacion_fisica').value === 'No' ? 'block' : 'none'; }
    function toggleAlergia() { document.getElementById('div_alergia_cual').style.display = document.getElementById('alergia').value === 'Si' ? 'block' : 'none'; }
    document.getElementById('enfermedad')?.addEventListener('change', toggleEnfermedad);
    document.getElementById('educacion_fisica')?.addEventListener('change', toggleEducacionFisica);
    document.getElementById('alergia')?.addEventListener('change', toggleAlergia);
    toggleEnfermedad(); toggleEducacionFisica(); toggleAlergia();

    // Wizard
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

        // ========== CARGA DE DATOS GEOGRÁFICOS DESDE ARCGIS ==========
    
    // Función genérica para cargar selects desde ArcGIS
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
                
                if (valorDefault) {
                    select.value = valorDefault;
                }
                
                if (callback) callback();
            })
            .catch(error => {
                console.error('Error:', error);
                const select = document.getElementById(selectId);
                if (select) {
                    select.innerHTML = '<option value="">Error al cargar datos</option>';
                }
            });
    }

    // ========== Cargar Países (estáticos) ==========
    function cargarPaises() {
        fetch('ajax_geografico.php?action=get_paises')
            .then(response => response.json())
            .then(data => {
                // Paises
                const selectPais = document.getElementById('pais_nacimiento');
                selectPais.innerHTML = '<option value="">Seleccione...</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.nombre;
                    option.textContent = item.nombre;
                    selectPais.appendChild(option);
                });
                const optionOtro = document.createElement('option');
                optionOtro.value = 'OTRO';
                optionOtro.textContent = '--- OTRO ---';
                optionOtro.style.fontWeight = 'bold';
                optionOtro.style.color = '#dc3545';
                selectPais.appendChild(optionOtro);
                selectPais.value = 'Venezuela';
                manejarOtro('pais_nacimiento', 'input_pais_nacimiento');

                // Paises Representante
                const selectRepPais = document.getElementById('rep_pais_nacimiento');
                selectRepPais.innerHTML = '<option value="">Seleccione...</option>';
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.nombre;
                    option.textContent = item.nombre;
                    selectRepPais.appendChild(option);
                });
                const optionOtro2 = document.createElement('option');
                optionOtro2.value = 'OTRO';
                optionOtro2.textContent = '--- OTRO ---';
                optionOtro2.style.fontWeight = 'bold';
                optionOtro2.style.color = '#dc3545';
                selectRepPais.appendChild(optionOtro2);
                selectRepPais.value = 'Venezuela';
                manejarOtro('rep_pais_nacimiento', 'input_rep_pais_nacimiento');
            })
            .catch(error => console.error('Error cargando países:', error));
    }

    // ========== Cargar Estados desde ArcGIS ==========
    function cargarEstados() {
        cargarSelectArcGIS('ajax_geografico.php?action=get_estados', 'estado_nacimiento', '', function() {
            manejarOtro('estado_nacimiento', 'input_estado_nacimiento');
        });
        cargarSelectArcGIS('ajax_geografico.php?action=get_estados', 'estado_residencia', '', function() {
            manejarOtro('estado_residencia', 'input_estado_residencia');
        });
        cargarSelectArcGIS('ajax_geografico.php?action=get_estados', 'rep_estado_nacimiento', '', function() {
            manejarOtro('rep_estado_nacimiento', 'input_rep_estado_nacimiento');
        });
        cargarSelectArcGIS('ajax_geografico.php?action=get_estados', 'rep_estado_residencia', '', function() {
            manejarOtro('rep_estado_residencia', 'input_rep_estado_residencia');
        });
    }

    // ========== Cargar Municipios desde ArcGIS ==========
    function cargarMunicipios(estado, selectMunicipioId, selectParroquiaId, valorDefault) {
        if (!estado || estado === 'OTRO' || estado === '') {
            document.getElementById(selectMunicipioId).innerHTML = '<option value="">Primero seleccione un estado</option>';
            document.getElementById(selectMunicipioId).disabled = true;
            document.getElementById(selectParroquiaId).innerHTML = '<option value="">Primero seleccione un municipio</option>';
            document.getElementById(selectParroquiaId).disabled = true;
            
            const inputId = 'input_' + selectMunicipioId;
            const input = document.getElementById(inputId);
            if (input) { input.style.display = 'none'; }
            return;
        }
        
        fetch('ajax_geografico.php?action=get_municipios&estado=' + encodeURIComponent(estado))
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById(selectMunicipioId);
                select.innerHTML = '';
                
                const optionDefault = document.createElement('option');
                optionDefault.value = '';
                optionDefault.textContent = 'Seleccione un municipio...';
                select.appendChild(optionDefault);
                
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
                
                select.disabled = false;
                
                if (valorDefault) {
                    select.value = valorDefault;
                }
                
                document.getElementById(selectParroquiaId).innerHTML = '<option value="">Primero seleccione un municipio</option>';
                document.getElementById(selectParroquiaId).disabled = true;
                
                const inputId = 'input_' + selectMunicipioId;
                const input = document.getElementById(inputId);
                if (input) {
                    manejarOtro(selectMunicipioId, inputId);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // ========== Cargar Parroquias desde ArcGIS ==========
    function cargarParroquias(municipio, selectParroquiaId, valorDefault) {
        if (!municipio || municipio === 'OTRO' || municipio === '') {
            document.getElementById(selectParroquiaId).innerHTML = '<option value="">Primero seleccione un municipio</option>';
            document.getElementById(selectParroquiaId).disabled = true;
            
            const inputId = 'input_' + selectParroquiaId;
            const input = document.getElementById(inputId);
            if (input) { input.style.display = 'none'; }
            return;
        }
        
        fetch('ajax_geografico.php?action=get_parroquias&municipio=' + encodeURIComponent(municipio))
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById(selectParroquiaId);
                select.innerHTML = '';
                
                const optionDefault = document.createElement('option');
                optionDefault.value = '';
                optionDefault.textContent = 'Seleccione una parroquia...';
                select.appendChild(optionDefault);
                
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
                
                select.disabled = false;
                
                if (valorDefault) {
                    select.value = valorDefault;
                }
                
                const inputId = 'input_' + selectParroquiaId;
                const input = document.getElementById(inputId);
                if (input) {
                    manejarOtro(selectParroquiaId, inputId);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // ========== MANEJAR OPCIÓN "OTRO" REVERSIBLE ==========
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
            if (select.dataset.valorAnterior) {
                select.value = select.dataset.valorAnterior;
            } else {
                select.value = '';
            }
        }
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            select.style.display = 'block';
            this.style.display = 'none';
            this.value = '';
            this.required = false;
            if (select.dataset.valorAnterior) {
                select.value = select.dataset.valorAnterior;
            } else {
                select.value = '';
            }
        }
    });
}

// ========== FUNCIÓN PARA CREAR INPUT "OTRO" ==========
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
        if (select) {
            select.parentNode.appendChild(input);
        }
    }
    return input;
}

// ========== CARGAR SELECT DESDE AJAX ==========
function cargarSelectAjax(url, selectId, valorDefault, callback) {
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
            
            if (valorDefault) {
                select.value = valorDefault;
            }
            
            if (callback) callback();
        })
        .catch(error => {
            console.error('Error:', error);
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = '<option value="">Error al cargar datos</option>';
            }
        });
}

// ========== Cargar Países (SOLO VENEZUELA + OTRO) ==========
function cargarPaises() {
    // País - Alumno
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

    // País - Representante
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

// ========== Cargar Estados ==========
function cargarEstados() {
    cargarSelectAjax('ajax_geografico.php?action=get_estados', 'estado_nacimiento', '', function() {
        crearInputOtro('estado_nacimiento', 'input_estado_nacimiento', 'Escriba el estado...');
        manejarOtro('estado_nacimiento', 'input_estado_nacimiento');
    });
    cargarSelectAjax('ajax_geografico.php?action=get_estados', 'estado_residencia', '', function() {
        crearInputOtro('estado_residencia', 'input_estado_residencia', 'Escriba el estado...');
        manejarOtro('estado_residencia', 'input_estado_residencia');
    });
    cargarSelectAjax('ajax_geografico.php?action=get_estados', 'rep_estado_nacimiento', '', function() {
        crearInputOtro('rep_estado_nacimiento', 'input_rep_estado_nacimiento', 'Escriba el estado...');
        manejarOtro('rep_estado_nacimiento', 'input_rep_estado_nacimiento');
    });
    cargarSelectAjax('ajax_geografico.php?action=get_estados', 'rep_estado_residencia', '', function() {
        crearInputOtro('rep_estado_residencia', 'input_rep_estado_residencia', 'Escriba el estado...');
        manejarOtro('rep_estado_residencia', 'input_rep_estado_residencia');
    });
}

// ========== Cargar Municipios ==========
function cargarMunicipios(estado, selectMunicipioId, selectParroquiaId, valorDefault) {
    if (!estado || estado === 'OTRO' || estado === '') {
        document.getElementById(selectMunicipioId).innerHTML = '<option value="">Primero seleccione un estado</option>';
        document.getElementById(selectMunicipioId).disabled = true;
        document.getElementById(selectParroquiaId).innerHTML = '<option value="">Primero seleccione un municipio</option>';
        document.getElementById(selectParroquiaId).disabled = true;
        
        const inputId = 'input_' + selectMunicipioId;
        const input = document.getElementById(inputId);
        if (input) { input.style.display = 'none'; }
        return;
    }
    
    const url = 'ajax_geografico.php?action=get_municipios&estado=' + encodeURIComponent(estado);
    cargarSelectAjax(url, selectMunicipioId, valorDefault, function() {
        const inputId = 'input_' + selectMunicipioId;
        const input = document.getElementById(inputId);
        if (input) {
            crearInputOtro(selectMunicipioId, inputId, 'Escriba el municipio...');
            manejarOtro(selectMunicipioId, inputId);
        }
        document.getElementById(selectMunicipioId).disabled = false;
    });
}

// ========== Cargar Parroquias ==========
function cargarParroquias(municipio, selectParroquiaId, valorDefault) {
    if (!municipio || municipio === 'OTRO' || municipio === '') {
        document.getElementById(selectParroquiaId).innerHTML = '<option value="">Primero seleccione un municipio</option>';
        document.getElementById(selectParroquiaId).disabled = true;
        
        const inputId = 'input_' + selectParroquiaId;
        const input = document.getElementById(inputId);
        if (input) { input.style.display = 'none'; }
        return;
    }
    
    const url = 'ajax_geografico.php?action=get_parroquias&municipio=' + encodeURIComponent(municipio);
    cargarSelectAjax(url, selectParroquiaId, valorDefault, function() {
        const inputId = 'input_' + selectParroquiaId;
        const input = document.getElementById(inputId);
        if (input) {
            crearInputOtro(selectParroquiaId, inputId, 'Escriba la parroquia...');
            manejarOtro(selectParroquiaId, inputId);
        }
        document.getElementById(selectParroquiaId).disabled = false;
    });
}

// ========== EVENTOS EN CADENA - Alumno ==========
document.getElementById('estado_nacimiento')?.addEventListener('change', function() {
    cargarMunicipios(this.value, 'municipio', 'parroquia', '');
});

document.getElementById('estado_residencia')?.addEventListener('change', function() {
    cargarMunicipios(this.value, 'municipio', 'parroquia', '');
});

document.getElementById('municipio')?.addEventListener('change', function() {
    cargarParroquias(this.value, 'parroquia', '');
});

// ========== EVENTOS EN CADENA - Representante ==========
document.getElementById('rep_estado_nacimiento')?.addEventListener('change', function() {
    cargarMunicipios(this.value, 'rep_municipio', 'rep_parroquia', '');
});

document.getElementById('rep_estado_residencia')?.addEventListener('change', function() {
    cargarMunicipios(this.value, 'rep_municipio', 'rep_parroquia', '');
});

document.getElementById('rep_municipio')?.addEventListener('change', function() {
    cargarParroquias(this.value, 'rep_parroquia', '');
});

// ========== Cargar datos al iniciar ==========
cargarPaises();
cargarEstados();

    // ========== EVENTOS EN CADENA - Alumno ==========
    document.getElementById('estado_nacimiento')?.addEventListener('change', function() {
        cargarMunicipios(this.value, 'municipio', 'parroquia', '');
    });

    document.getElementById('estado_residencia')?.addEventListener('change', function() {
        cargarMunicipios(this.value, 'municipio', 'parroquia', '');
    });

    document.getElementById('municipio')?.addEventListener('change', function() {
        cargarParroquias(this.value, 'parroquia', '');
    });

    // ========== EVENTOS EN CADENA - Representante ==========
    document.getElementById('rep_estado_nacimiento')?.addEventListener('change', function() {
        cargarMunicipios(this.value, 'rep_municipio', 'rep_parroquia', '');
    });

    document.getElementById('rep_estado_residencia')?.addEventListener('change', function() {
        cargarMunicipios(this.value, 'rep_municipio', 'rep_parroquia', '');
    });

    document.getElementById('rep_municipio')?.addEventListener('change', function() {
        cargarParroquias(this.value, 'rep_parroquia', '');
    });

    // ========== Cargar datos al iniciar ==========
    cargarPaises();
    cargarEstados();
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