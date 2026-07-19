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
                                <input type="text" name="cedula_base" id="cedula_base" class="form-control" placeholder="Ej: 123456789" required>
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

                <!-- STEP 2 -->
                <div id="step2" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">DATOS DEL REPRESENTANTE</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre del Representante <span class="text-danger">*</span></label>
                            <input type="text" name="rep_nombre" class="form-control text-uppercase" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cédula del Representante <span class="text-danger">*</span></label>
                            <input type="text" name="rep_cedula" id="rep_cedula" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Teléfono <span class="text-danger">*</span></label>
                            <input type="text" name="rep_telefono" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Parentesco <span class="text-danger">*</span></label>
                            <select name="rep_parentesco" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="Madre">Madre</option>
                                <option value="Padre">Padre</option>
                                <option value="Abuelo/a">Abuelo/a</option>
                                <option value="Tío/a">Tío/a</option>
                                <option value="Tutor Legal">Tutor Legal</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sexo</label>
                            <select name="rep_sexo" class="form-select">
                                <option value="">Seleccione...</option>
                                <option value="V">Varón</option>
                                <option value="H">Hembra</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha Nacimiento</label>
                            <input type="date" name="rep_fecha_nacimiento" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado Civil</label>
                            <select name="rep_estado_civil" class="form-select">
                                <option value="">Seleccione...</option>
                                <option value="Soltero/a">Soltero/a</option>
                                <option value="Casado/a">Casado/a</option>
                                <option value="Divorciado/a">Divorciado/a</option>
                                <option value="Viudo/a">Viudo/a</option>
                                <option value="Unión libre">Unión libre</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Afinidad</label>
                            <input type="text" name="rep_afinidad" class="form-control" placeholder="Ej: Hermana, Primo, etc.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">País de Nacimiento</label>
                            <select name="rep_pais_nacimiento" id="rep_pais_nacimiento" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                            <input type="text" id="input_rep_pais_nacimiento" name="rep_pais_nacimiento_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el país..." style="display:none;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado Nacimiento</label>
                            <select name="rep_estado_nacimiento" id="rep_estado_nacimiento" class="form-select">
                                <option value="">Seleccione...</option>
                            </select>
                            <input type="text" id="input_rep_estado_nacimiento" name="rep_estado_nacimiento_otro" class="form-control text-uppercase mt-1" placeholder="Escriba el estado..." style="display:none;">
                        </div>
                        <div class="col-md-4">
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
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div id="step3" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">DATOS DE LOS PADRES</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-primary">Madre</h6>
                            <label class="form-label fw-semibold">Nombre completo</label>
                            <input type="text" name="madre_nombre" id="madre_nombre" class="form-control text-uppercase">
                            <label class="form-label fw-semibold mt-2">Cédula</label>
                            <input type="text" name="madre_cedula" id="madre_cedula" class="form-control">
                            <label class="form-label fw-semibold mt-2">Teléfono</label>
                            <input type="text" name="madre_telefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Padre</h6>
                            <label class="form-label fw-semibold">Nombre completo</label>
                            <input type="text" name="padre_nombre" id="padre_nombre" class="form-control text-uppercase">
                            <label class="form-label fw-semibold mt-2">Cédula</label>
                            <input type="text" name="padre_cedula" id="padre_cedula" class="form-control">
                            <label class="form-label fw-semibold mt-2">Teléfono</label>
                            <input type="text" name="padre_telefono" class="form-control">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- STEP 4: HISTORIAL ESCOLAR (SIN columna "Funcionario") -->
                <div id="step4" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">GRADO Y SECCIÓN</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Año Escolar</th>
                                    <th>Reg.</th>
                                    <th>Rep</th>
                                    <th>C</th>
                                    <th>F</th>
                                    <th>P</th>
                                    <th>Peso</th>
                                    <th>Talla</th>
                                    <th>Fecha Inscripción</th>
                                    <th style="width:50px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="historial-body">
                                <tr class="fila-historial">
                                    <td>
                                        <select name="ano_escolar[]" class="form-select form-select-sm" required>
                                            <option value="">Seleccione</option>
                                            <?php 
                                            $ano_actual = date('Y');
                                            for ($a = $ano_actual - 1; $a >= 2015; $a--): 
                                                $periodo = $a . '-' . ($a + 1);
                                            ?>
                                                <option value="<?= htmlspecialchars($periodo) ?>"><?= htmlspecialchars($periodo) ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="registro[]" class="form-control form-control-sm" placeholder="Reg."></td>
                                    <td>
                                        <select name="repite[]" class="form-select form-select-sm">
                                            <option value="No">No</option>
                                            <option value="Si">Sí</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="c[]" class="form-control form-control-sm" placeholder="C"></td>
                                    <td><input type="text" name="f[]" class="form-control form-control-sm" placeholder="F"></td>
                                    <td><input type="text" name="p[]" class="form-control form-control-sm" placeholder="P"></td>
                                    <td><input type="number" step="0.01" name="peso[]" class="form-control form-control-sm" placeholder="Peso"></td>
                                    <td><input type="number" step="0.01" name="talla[]" class="form-control form-control-sm" placeholder="Talla"></td>
                                    <td><input type="date" name="fecha_inscripcion[]" class="form-control form-control-sm"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger eliminar-fila" title="Eliminar fila">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="agregarFila">
                        <i class="fas fa-plus me-1"></i> Agregar fila
                    </button>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i> Guardar Inscripción</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ============================================================================
// SCRIPT UNIFICADO: PRECARGA + WIZARD + FUNCIONES GEOGRÁFICAS CON FALLBACK LOCAL
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ---------- PRECARGA DE DATOS DESDE "Terminar Inscripción" ----------
    <?php if (!empty($prefill_data)): ?>
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

    // Tabla dinámica (sin campo "Funcionario")
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

    // ============================================================================
    // DATOS GEOGRÁFICOS LOCALES (FALLBACK)
    // ============================================================================

    const paisesLista = [
        'Venezuela', 'Argentina', 'Bolivia', 'Brasil', 'Chile', 'Colombia',
        'Costa Rica', 'Cuba', 'Ecuador', 'El Salvador', 'España', 'Estados Unidos',
        'Guatemala', 'Honduras', 'México', 'Nicaragua', 'Panamá', 'Paraguay',
        'Perú', 'Portugal', 'Puerto Rico', 'República Dominicana', 'Uruguay'
    ];

    const estadosVenezuela = [
        'Amazonas', 'Anzoátegui', 'Apure', 'Aragua', 'Barinas', 'Bolívar',
        'Carabobo', 'Cojedes', 'Delta Amacuro', 'Distrito Capital', 'Falcón',
        'Guárico', 'La Guaira', 'Lara', 'Mérida', 'Miranda', 'Monagas',
        'Nueva Esparta', 'Portuguesa', 'Sucre', 'Táchira', 'Trujillo', 'Yaracuy', 'Zulia'
    ];

    const municipiosPorEstado = {
        'Zulia': ['Maracaibo', 'San Francisco', 'Cabimas', 'Santa Rita', 'Jesús Enrique Lossada', 'La Cañada de Urdaneta'],
        'Miranda': ['Baruta', 'Carrizal', 'Chacao', 'El Hatillo', 'Guaicaipuro', 'Los Salias', 'Sucre'],
        'Carabobo': ['Valencia', 'Puerto Cabello', 'Guacara', 'Los Guayos', 'Naguanagua', 'San Diego'],
        'Lara': ['Barquisimeto', 'Cabudare', 'Carora', 'El Tocuyo', 'Quíbor'],
        'Bolívar': ['Ciudad Bolívar', 'Ciudad Guayana', 'Upata'],
        'Táchira': ['San Cristóbal', 'La Fría', 'Rubio', 'Táriba'],
        'Mérida': ['Mérida', 'Ejido', 'Tovar'],
        'Falcón': ['Coro', 'Punto Fijo', 'La Vela de Coro'],
        'Anzoátegui': ['Barcelona', 'Puerto La Cruz', 'Lechería', 'El Tigre'],
        'Monagas': ['Maturín', 'Punta de Mata'],
        'Sucre': ['Cumaná', 'Carúpano'],
        'Portuguesa': ['Guanare', 'Acarigua'],
        'Barinas': ['Barinas', 'Sabaneta'],
        'Apure': ['San Fernando de Apure', 'Guasdualito'],
        'Guárico': ['Calabozo', 'Valle de la Pascua', 'San Juan de los Morros'],
        'Cojedes': ['San Carlos', 'Tinaquillo'],
        'Aragua': ['Maracay', 'El Limón', 'La Victoria', 'Cagua', 'Turmero'],
        'Nueva Esparta': ['Porlamar', 'La Asunción', 'Juan Griego'],
        'Delta Amacuro': ['Tucupita'],
        'Amazonas': ['Puerto Ayacucho'],
        'Distrito Capital': ['Caracas']
    };

    const parroquiasPorMunicipio = {
        'Maracaibo': ['Bolívar', 'Cecilio Acosta', 'Chiquinquirá', 'Coquivacoa', 'Idelfonso Vásquez', 'Juana de Ávila', 'San Isidro', 'Santa Lucía'],
        'San Francisco': ['Domitila Flores', 'El Bajo', 'Francisco Ochoa', 'José Domínguez', 'Los Cortijos'],
        'Cabimas': ['Ambrosio', 'Carmen Herrera', 'Germán Ríos Linares', 'La Rosa', 'Punta Gorda', 'Rómulo Betancourt'],
        'Caracas': ['Catedral', 'El Valle', 'La Candelaria', 'La Pastora', 'San Agustín', 'San Bernardino', 'San José', 'San Juan', 'Santa Teresa', 'Sucre'],
        'Valencia': ['Candelaria', 'El Socorro', 'Miguel Peña', 'Rafael Urdaneta', 'San Blas', 'San José']
    };

    // ============================================================================
    // FUNCIONES GEOGRÁFICAS CON FALLBACK LOCAL
    // ============================================================================

    function cargarSelectConOpciones(selectId, opciones, valorDefault) {
        const select = document.getElementById(selectId);
        if (!select) return;
        select.innerHTML = '<option value="">Seleccione...</option>';
        opciones.forEach(op => {
            const opt = document.createElement('option');
            opt.value = op;
            opt.textContent = op;
            select.appendChild(opt);
        });
        const optOtro = document.createElement('option');
        optOtro.value = 'OTRO';
        optOtro.textContent = '--- OTRO ---';
        optOtro.style.fontWeight = 'bold';
        optOtro.style.color = '#dc3545';
        select.appendChild(optOtro);
        if (valorDefault) select.value = valorDefault;
        select.disabled = false;
    }

    function cargarSelectDesdeEndpoint(url, selectId, valorDefault, fallbackOpciones) {
        const select = document.getElementById(selectId);
        if (!select) return;
        select.innerHTML = '<option value="">Cargando...</option>';
        select.disabled = true;
        
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('HTTP error ' + response.status);
                return response.json();
            })
            .then(data => {
                if (data && data.length > 0) {
                    const opciones = data.map(item => typeof item === 'string' ? item : item.nombre || item);
                    cargarSelectConOpciones(selectId, opciones, valorDefault);
                } else {
                    console.warn('No hay datos del endpoint para ' + selectId + ', usando fallback local');
                    if (fallbackOpciones) cargarSelectConOpciones(selectId, fallbackOpciones, valorDefault);
                }
            })
            .catch(error => {
                console.error('Error al cargar ' + selectId + ':', error);
                if (fallbackOpciones) {
                    console.warn('Usando fallback local para ' + selectId);
                    cargarSelectConOpciones(selectId, fallbackOpciones, valorDefault);
                } else {
                    select.innerHTML = '<option value="">Error al cargar</option>';
                    select.disabled = true;
                }
            });
    }

    function configurarOtro(selectId, inputId) {
        const select = document.getElementById(selectId);
        let input = document.getElementById(inputId);
        if (!select || !input) return;
        
        if (!input.id) {
            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.id = inputId;
            newInput.name = inputId.replace('input_', '') + '_otro';
            newInput.className = 'form-control text-uppercase mt-1';
            newInput.placeholder = 'Escriba aquí...';
            newInput.style.display = 'none';
            select.parentNode.appendChild(newInput);
            input = newInput;
        }
        
        select.addEventListener('change', function() {
            if (this.value === 'OTRO') {
                this.style.display = 'none';
                input.style.display = 'block';
                input.value = '';
                input.focus();
                input.required = true;
            } else {
                this.style.display = 'block';
                input.style.display = 'none';
                input.value = '';
                input.required = false;
            }
        });
        
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                select.style.display = 'block';
                this.style.display = 'none';
                this.required = false;
                select.value = '';
            }
        });
    }

    // ============================================================================
    // INICIALIZACIÓN GEOGRÁFICA
    // ============================================================================

    function inicializarGeografia() {
        // PAÍSES
        const selectPais = document.getElementById('pais_nacimiento');
        if (selectPais) {
            cargarSelectDesdeEndpoint('ajax_geografico.php?action=get_paises', 'pais_nacimiento', 'Venezuela', paisesLista);
            configurarOtro('pais_nacimiento', 'input_pais_nacimiento');
        }
        
        const selectRepPais = document.getElementById('rep_pais_nacimiento');
        if (selectRepPais) {
            cargarSelectDesdeEndpoint('ajax_geografico.php?action=get_paises', 'rep_pais_nacimiento', 'Venezuela', paisesLista);
            configurarOtro('rep_pais_nacimiento', 'input_rep_pais_nacimiento');
        }
        
        // ESTADOS
        const estadoSelects = [
            { id: 'estado_nacimiento', inputId: 'input_estado_nacimiento', fallback: estadosVenezuela },
            { id: 'estado_residencia', inputId: 'input_estado_residencia', fallback: estadosVenezuela },
            { id: 'rep_estado_nacimiento', inputId: 'input_rep_estado_nacimiento', fallback: estadosVenezuela },
            { id: 'rep_estado_residencia', inputId: 'input_rep_estado_residencia', fallback: estadosVenezuela }
        ];
        
        estadoSelects.forEach(({ id, inputId, fallback }) => {
            const select = document.getElementById(id);
            if (select) {
                cargarSelectDesdeEndpoint('ajax_geografico.php?action=get_estados', id, '', fallback);
                configurarOtro(id, inputId);
            }
        });
        
        // MUNICIPIOS
        const municipioSelect = document.getElementById('municipio');
        if (municipioSelect) {
            municipioSelect.disabled = true;
            configurarOtro('municipio', 'input_municipio');
        }
        
        const repMunicipioSelect = document.getElementById('rep_municipio');
        if (repMunicipioSelect) {
            repMunicipioSelect.disabled = true;
            configurarOtro('rep_municipio', 'input_rep_municipio');
        }
        
        // PARROQUIAS
        const parroquiaSelect = document.getElementById('parroquia');
        if (parroquiaSelect) {
            parroquiaSelect.disabled = true;
            configurarOtro('parroquia', 'input_parroquia');
        }
        
        const repParroquiaSelect = document.getElementById('rep_parroquia');
        if (repParroquiaSelect) {
            repParroquiaSelect.disabled = true;
            configurarOtro('rep_parroquia', 'input_rep_parroquia');
        }
        
        // LISTENERS PARA MUNICIPIOS/PARROQUIAS
        function actualizarMunicipios(estadoId, municipioId, parroquiaId) {
            const estadoSelect = document.getElementById(estadoId);
            const municipioSelect = document.getElementById(municipioId);
            const parroquiaSelect = document.getElementById(parroquiaId);
            if (!estadoSelect || !municipioSelect || !parroquiaSelect) return;
            
            const estado = estadoSelect.value;
            if (!estado || estado === 'OTRO') {
                municipioSelect.innerHTML = '<option value="">Primero seleccione un estado</option>';
                municipioSelect.disabled = true;
                parroquiaSelect.innerHTML = '<option value="">Primero seleccione un municipio</option>';
                parroquiaSelect.disabled = true;
                return;
            }
            
            const url = 'ajax_geografico.php?action=get_municipios&estado=' + encodeURIComponent(estado);
            const fallback = municipiosPorEstado[estado] || [];
            cargarSelectDesdeEndpoint(url, municipioId, '', fallback);
            municipioSelect.disabled = false;
            
            parroquiaSelect.innerHTML = '<option value="">Primero seleccione un municipio</option>';
            parroquiaSelect.disabled = true;
        }
        
        function actualizarParroquias(municipioId, parroquiaId) {
            const municipioSelect = document.getElementById(municipioId);
            const parroquiaSelect = document.getElementById(parroquiaId);
            if (!municipioSelect || !parroquiaSelect) return;
            
            const municipio = municipioSelect.value;
            if (!municipio || municipio === 'OTRO') {
                parroquiaSelect.innerHTML = '<option value="">Primero seleccione un municipio</option>';
                parroquiaSelect.disabled = true;
                return;
            }
            
            const url = 'ajax_geografico.php?action=get_parroquias&municipio=' + encodeURIComponent(municipio);
            const fallback = parroquiasPorMunicipio[municipio] || [];
            cargarSelectDesdeEndpoint(url, parroquiaId, '', fallback);
            parroquiaSelect.disabled = false;
        }
        
        // Asignar listeners
        document.getElementById('estado_nacimiento')?.addEventListener('change', function() {
            actualizarMunicipios('estado_nacimiento', 'municipio', 'parroquia');
        });
        document.getElementById('estado_residencia')?.addEventListener('change', function() {
            actualizarMunicipios('estado_residencia', 'municipio', 'parroquia');
        });
        document.getElementById('municipio')?.addEventListener('change', function() {
            actualizarParroquias('municipio', 'parroquia');
        });
        
        document.getElementById('rep_estado_nacimiento')?.addEventListener('change', function() {
            actualizarMunicipios('rep_estado_nacimiento', 'rep_municipio', 'rep_parroquia');
        });
        document.getElementById('rep_estado_residencia')?.addEventListener('change', function() {
            actualizarMunicipios('rep_estado_residencia', 'rep_municipio', 'rep_parroquia');
        });
        document.getElementById('rep_municipio')?.addEventListener('change', function() {
            actualizarParroquias('rep_municipio', 'rep_parroquia');
        });
        
        // Disparar eventos iniciales si hay valores preseleccionados
        setTimeout(function() {
            ['estado_nacimiento', 'estado_residencia', 'rep_estado_nacimiento', 'rep_estado_residencia'].forEach(id => {
                const select = document.getElementById(id);
                if (select && select.value && select.value !== 'OTRO' && select.value !== '') {
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }, 500);
    }

    // ============================================================================
    // INICIALIZAR TODO AL CARGAR LA PÁGINA
    // ============================================================================
    inicializarGeografia();
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