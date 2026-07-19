<?php
session_start();
require_once('../config/conexion.php');

// ========== FUNCIÓN PARA VERIFICAR CAMPOS COMPLETOS ==========
function verificarCamposCompletos($datos) {
    $obligatorios = [
        'nombre', 'apellido', 'fecha_nacimiento', 'genero', 'cedula_escolar',
        'rep_nombre', 'rep_cedula', 'rep_telefono', 'sala', 'seccion_id'
    ];
    foreach ($obligatorios as $campo) {
        if (empty($datos[$campo])) {
            return false;
        }
    }
    if (empty($datos['madre_nombre']) && empty($datos['padre_nombre'])) {
        return false;
    }
    if (!empty($datos['enfermedad']) && $datos['enfermedad'] === 'Si' && empty($datos['enfermedad_cual'])) {
        return false;
    }
    if (!empty($datos['educacion_fisica']) && $datos['educacion_fisica'] === 'No' && empty($datos['educacion_fisica_porque'])) {
        return false;
    }
    if (!empty($datos['alergia']) && $datos['alergia'] === 'Si' && empty($datos['alergia_cual'])) {
        return false;
    }
    return true;
}

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva'])) {
    header("Location: ../profesores/Login/login.php");
    exit();
}

$id = intval($_GET['id']);
$mensaje = "";
$tipo_mensaje = "";

// Obtener datos del estudiante con su representante y padres
$stmt = $conexion->prepare("
    SELECT e.*, 
           r.id AS rep_id, r.nombre_completo AS rep_nombre, r.cedula AS rep_cedula, r.telefono AS rep_telefono,
           r.fecha_nacimiento AS rep_fecha_nac, r.estado_civil AS rep_estado_civil, r.afinidad,
           r.sexo AS rep_sexo, r.pais_nacimiento AS rep_pais_nac, r.estado_nacimiento AS rep_estado_nac,
           r.nacionalidad AS rep_nacionalidad, r.direccion AS rep_direccion,
           r.estado_residencia AS rep_estado_res, r.municipio AS rep_municipio,
           r.parroquia AS rep_parroquia, r.ciudad AS rep_ciudad
    FROM estudiantes e 
    LEFT JOIN representantes r ON e.representante_id = r.id 
    WHERE e.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header("Location: listado.php");
    exit();
}

// Obtener historial escolar (inscripciones) - ORDEN ASC (más antiguo primero)
$stmt_ins = $conexion->prepare("SELECT * FROM inscripciones WHERE estudiante_id = ? ORDER BY ano_escolar ASC");
$stmt_ins->bind_param("i", $id);
$stmt_ins->execute();
$inscripciones = $stmt_ins->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_ins->close();

// Procesar guardado de cambios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conexion->begin_transaction();
    try {
        // ========== DATOS DEL ESTUDIANTE ==========
        $nombre = strtoupper(trim($_POST['nombre']));
        $apellido = strtoupper(trim($_POST['apellido']));
        $fecha_nac = $_POST['fecha_nacimiento'];
        $genero = $_POST['genero'];
        $orden_nacimiento = intval($_POST['orden_nacimiento']);
        $nacionalidad = $_POST['nacionalidad'];
        $pais_nac = $_POST['pais_nacimiento'];
        $estado_nac = $_POST['estado_nacimiento'];
        $direccion = $_POST['direccion'];
        $estado_res = $_POST['estado_residencia'];
        $municipio = $_POST['municipio'];
        $parroquia = $_POST['parroquia'];
        $ciudad = $_POST['ciudad'];
        $enfermedad = $_POST['enfermedad'];
        $enfermedad_cual = $_POST['enfermedad_cual'];
        $educacion_fisica = $_POST['educacion_fisica'];
        $educacion_fisica_porque = $_POST['educacion_fisica_porque'];
        $alergia = $_POST['alergia'];
        $alergia_cual = $_POST['alergia_cual'];
        $madre_nombre = $_POST['madre_nombre'];
        $madre_cedula = $_POST['madre_cedula'];
        $madre_telefono = $_POST['madre_telefono'];
        $padre_nombre = $_POST['padre_nombre'];
        $padre_cedula = $_POST['padre_cedula'];
        $padre_telefono = $_POST['padre_telefono'];
        
        // ========== OBTENER DATOS DEL HISTORIAL ESCOLAR ==========
        $ano_escolar_arr = $_POST['ano_escolar'] ?? [];
        $grado_seccion_arr = $_POST['grado_seccion'] ?? [];
        $registro_arr = $_POST['registro'] ?? [];
        $repite_arr = $_POST['repite'] ?? [];
        $c_arr = $_POST['c'] ?? [];
        $f_arr = $_POST['f'] ?? [];
        $p_arr = $_POST['p'] ?? [];
        $peso_arr = $_POST['peso'] ?? [];
        $talla_arr = $_POST['talla'] ?? [];
        $fecha_inscripcion_arr = $_POST['fecha_inscripcion'] ?? [];

        // ========== DETERMINAR EL AÑO ACTUAL (ÚLTIMA FILA) ==========
        $ultimo_indice = count($ano_escolar_arr) - 1;
        $grado_actual = $grado_seccion_arr[$ultimo_indice] ?? '';
        $peso_actual = !empty($peso_arr[$ultimo_indice]) ? floatval($peso_arr[$ultimo_indice]) : null;
        $talla_actual = !empty($talla_arr[$ultimo_indice]) ? floatval($talla_arr[$ultimo_indice]) : null;
        $ano_escolar_actual = $ano_escolar_arr[$ultimo_indice] ?? '';

        // Extraer sala y sección del grado_seccion (ej: "sala5 - U")
        $sala_nombre = '';
        $seccion_letra = '';
        if (!empty($grado_actual)) {
            $partes = explode(' - ', $grado_actual);
            $sala_nombre = $partes[0] ?? '';
            $seccion_letra = $partes[1] ?? '';
        }

        // Obtener el ID de la sección
        $seccion_id_actual = 0;
        if (!empty($sala_nombre) && !empty($seccion_letra)) {
            $stmt_sec = $conexion->prepare("SELECT id FROM secciones WHERE sala = ? AND nombre = ?");
            $stmt_sec->bind_param("ss", $sala_nombre, $seccion_letra);
            $stmt_sec->execute();
            $row_sec = $stmt_sec->get_result()->fetch_assoc();
            $seccion_id_actual = $row_sec['id'] ?? 0;
            $stmt_sec->close();
        }

        // ========== ACTUALIZAR ESTUDIANTE CON LOS DATOS DEL AÑO ACTUAL ==========
        $stmt_upd = $conexion->prepare("UPDATE estudiantes SET 
            nombre=?, apellido=?, fecha_nacimiento=?, genero=?, orden_nacimiento=?,
            nacionalidad=?, pais_nacimiento=?, estado_nacimiento=?, direccion=?,
            estado_residencia=?, municipio=?, parroquia=?, ciudad=?,
            enfermedad=?, enfermedad_cual=?, educacion_fisica=?, educacion_fisica_porque=?,
            alergia=?, alergia_cual=?, madre_nombre=?, madre_cedula=?, madre_telefono=?,
            padre_nombre=?, padre_cedula=?, padre_telefono=?,
            sala=?, seccion_id=?, peso=?, talla=?
            WHERE id=?");

        // Convertir a string para evitar problemas de referencia
        $p1 = (string)$nombre;
        $p2 = (string)$apellido;
        $p3 = (string)$fecha_nac;
        $p4 = (string)$genero;
        $p5 = (string)$orden_nacimiento;
        $p6 = (string)$nacionalidad;
        $p7 = (string)$pais_nac;
        $p8 = (string)$estado_nac;
        $p9 = (string)$direccion;
        $p10 = (string)$estado_res;
        $p11 = (string)$municipio;
        $p12 = (string)$parroquia;
        $p13 = (string)$ciudad;
        $p14 = (string)$enfermedad;
        $p15 = (string)$enfermedad_cual;
        $p16 = (string)$educacion_fisica;
        $p17 = (string)$educacion_fisica_porque;
        $p18 = (string)$alergia;
        $p19 = (string)$alergia_cual;
        $p20 = (string)$madre_nombre;
        $p21 = (string)$madre_cedula;
        $p22 = (string)$madre_telefono;
        $p23 = (string)$padre_nombre;
        $p24 = (string)$padre_cedula;
        $p25 = (string)$padre_telefono;
        $p26 = (string)$sala_nombre;
        $p27 = (string)$seccion_id_actual;
        $p28 = (string)$peso_actual;
        $p29 = (string)$talla_actual;
        $p30 = (string)$id;

        $types_upd = str_repeat('s', 30);
        $stmt_upd->bind_param(
            $types_upd,
            $p1, $p2, $p3, $p4, $p5,
            $p6, $p7, $p8, $p9, $p10,
            $p11, $p12, $p13, $p14, $p15,
            $p16, $p17, $p18, $p19, $p20,
            $p21, $p22, $p23, $p24, $p25,
            $p26, $p27, $p28, $p29, $p30
        );
        $stmt_upd->execute();
        $stmt_upd->close();

        // ========== ACTUALIZAR REPRESENTANTE ==========
        $rep_id = $estudiante['rep_id'];
        $rep_nombre = $_POST['rep_nombre'];
        $rep_cedula = $_POST['rep_cedula'];
        $rep_telefono = $_POST['rep_telefono'];
        $rep_fecha_nac = $_POST['rep_fecha_nacimiento'] ?: null;
        $rep_estado_civil = $_POST['rep_estado_civil'];
        $rep_afinidad = $_POST['rep_afinidad'];
        $rep_sexo = $_POST['rep_sexo'];
        $rep_pais_nac = $_POST['rep_pais_nacimiento'];
        $rep_estado_nac = $_POST['rep_estado_nacimiento'];
        $rep_nacionalidad = $_POST['rep_nacionalidad'];
        $rep_direccion = $_POST['rep_direccion'];
        $rep_estado_res = $_POST['rep_estado_residencia'];
        $rep_municipio = $_POST['rep_municipio'];
        $rep_parroquia = $_POST['rep_parroquia'];
        $rep_ciudad = $_POST['rep_ciudad'];

        $stmt_rep = $conexion->prepare("UPDATE representantes SET
            nombre_completo=?, cedula=?, telefono=?, fecha_nacimiento=?, estado_civil=?,
            afinidad=?, sexo=?, pais_nacimiento=?, estado_nacimiento=?, nacionalidad=?,
            direccion=?, estado_residencia=?, municipio=?, parroquia=?, ciudad=?
            WHERE id=?");

        $types_rep = str_repeat('s', 16);
        $stmt_rep->bind_param(
            $types_rep,
            $rep_nombre,
            $rep_cedula,
            $rep_telefono,
            $rep_fecha_nac,
            $rep_estado_civil,
            $rep_afinidad,
            $rep_sexo,
            $rep_pais_nac,
            $rep_estado_nac,
            $rep_nacionalidad,
            $rep_direccion,
            $rep_estado_res,
            $rep_municipio,
            $rep_parroquia,
            $rep_ciudad,
            $rep_id
        );
        $stmt_rep->execute();
        $stmt_rep->close();

        // ========== ACTUALIZAR INSCRIPCIONES ==========
        $stmt_del = $conexion->prepare("DELETE FROM inscripciones WHERE estudiante_id = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();
        $stmt_del->close();

        $funcionario = $_SESSION['nombre_profesor'] ?? $_SESSION['usuario'] ?? 'Sistema';

        $stmt_ins = $conexion->prepare("INSERT INTO inscripciones 
            (estudiante_id, ano_escolar, grado_seccion, registro, repite, c, f, p, peso, talla, 
             firma_representante, fecha_inscripcion, funcionario) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?)");

        for ($i = 0; $i < count($ano_escolar_arr); $i++) {
            $fecha_ins = !empty($fecha_inscripcion_arr[$i]) ? $fecha_inscripcion_arr[$i] : date('Y-m-d');
            $peso_val = !empty($peso_arr[$i]) ? floatval($peso_arr[$i]) : null;
            $talla_val = !empty($talla_arr[$i]) ? floatval($talla_arr[$i]) : null;

            // Asignar a variables limpias para bind_param
            $ano_esc   = $ano_escolar_arr[$i] ?? '';
            $grado_sec = $grado_seccion_arr[$i] ?? '';
            $registro  = $registro_arr[$i] ?? '';
            $repite    = $repite_arr[$i] ?? 'No';
            $c_val     = $c_arr[$i] ?? '';
            $f_val     = $f_arr[$i] ?? '';
            $p_val     = $p_arr[$i] ?? '';

            $stmt_ins->bind_param("isssssssddss", 
                $id, 
                $ano_esc, 
                $grado_sec, 
                $registro, 
                $repite,
                $c_val, 
                $f_val, 
                $p_val, 
                $peso_val, 
                $talla_val,
                $fecha_ins, 
                $funcionario);
            $stmt_ins->execute();
        }
        $stmt_ins->close();

        // ========== VERIFICAR SI LA INSCRIPCIÓN ESTÁ COMPLETA ==========
        $datos_estudiante = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'fecha_nacimiento' => $fecha_nac,
            'genero' => $genero,
            'cedula_escolar' => $estudiante['cedula_escolar'] ?? '',
            'rep_nombre' => $rep_nombre,
            'rep_cedula' => $rep_cedula,
            'rep_telefono' => $rep_telefono,
            'sala' => $sala_nombre,
            'seccion_id' => $seccion_id_actual,
            'madre_nombre' => $madre_nombre,
            'padre_nombre' => $padre_nombre,
            'enfermedad' => $enfermedad,
            'enfermedad_cual' => $enfermedad_cual,
            'educacion_fisica' => $educacion_fisica,
            'educacion_fisica_porque' => $educacion_fisica_porque,
            'alergia' => $alergia,
            'alergia_cual' => $alergia_cual,
        ];
        $inscripcion_completa = verificarCamposCompletos($datos_estudiante) ? 1 : 0;

        $stmt_completa = $conexion->prepare("UPDATE estudiantes SET inscripcion_completa = ? WHERE id = ?");
        $stmt_completa->bind_param("ii", $inscripcion_completa, $id);
        $stmt_completa->execute();
        $stmt_completa->close();

        $conexion->commit();

        if ($inscripcion_completa) {
            header("Location: editar_estudiantes.php?id=$id&msg=success");
        } else {
            header("Location: editar_estudiantes.php?id=$id&msg=incompleta");
        }
        exit();
        
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje = "Error al guardar: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

include('../includes/header.php'); 

// ========== OPCIONES PARA GRADO-SECCIÓN (CORREGIDO) ==========
$opciones_historial = '<option value="">Seleccione</option>';
$secciones_hist = $conexion->query("SELECT id, sala, nombre FROM secciones ORDER BY sala, nombre");
while($sec = $secciones_hist->fetch_assoc()) {
    $valor = $sec['sala'] . ' - ' . $sec['nombre'];
    $opciones_historial .= '<option value="' . htmlspecialchars($valor) . '">' . htmlspecialchars($valor) . '</option>';
}
?>

<style>
    .bg-navy { background-color: #003366 !important; }
    .btn-primary { background-color: #003366; border-color: #003366; }
    .btn-primary:hover { background-color: #002244; }
    .nav-link.active { background-color: #003366 !important; color: white !important; }
    .nav-link.completed { background-color: #28a745 !important; color: white !important; }
    .table-sm th, .table-sm td { padding: 0.3rem; vertical-align: middle; }
    .progress-bar { transition: width 0.3s ease; }
    .step { 
        display: none; 
        animation: fadeIn 0.3s ease;
    }
    .step.active { display: block; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #tablaHistorial th:first-child,
    #tablaHistorial td:first-child {
        min-width: 130px;
        white-space: nowrap;
    }
</style>

<div class="container mt-4 mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-navy text-white rounded-top">
            <h4 class="mb-0"><i class="fas fa-edit me-2"></i> Editar Ficha de Inscripción</h4>
        </div>
        <div class="card-body p-4">
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show"><?= $mensaje ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i> ¡Datos actualizados con éxito! La inscripción está completa.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'incompleta'): ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i> <strong>Inscripción incompleta.</strong> Faltan campos obligatorios.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Progress Bar -->
            <div class="progress mb-4" style="height: 6px;">
                <div id="progressBar" class="progress-bar bg-success" style="width: 25%;"></div>
            </div>

            <form method="POST" id="wizardForm">
                
                <!-- Tabs -->
                <ul class="nav nav-tabs nav-justified mb-4 border-0" id="stepTabs">
                    <li class="nav-item"><a class="nav-link active rounded-0" href="#step1" data-step="1">1. Datos del Alumno</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step2" data-step="2">2. Datos del Representante</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step3" data-step="3">3. Datos de los Padres</a></li>
                    <li class="nav-item"><a class="nav-link disabled rounded-0" href="#step4" data-step="4">4. Historial Escolar</a></li>
                </ul>

                <!-- STEP 1 -->
                <div id="step1" class="step active p-3 bg-light rounded-3 mb-3">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">DATOS DEL ALUMNO</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellido" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['apellido']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Fecha Nacimiento <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= $estudiante['fecha_nacimiento'] ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Sexo <span class="text-danger">*</span></label>
                            <select name="genero" class="form-select" required>
                                <option value="">--</option>
                                <option value="V" <?= ($estudiante['genero']=='V')?'selected':'' ?>>Varón</option>
                                <option value="H" <?= ($estudiante['genero']=='H')?'selected':'' ?>>Hembra</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Orden nacimiento</label>
                            <input type="number" name="orden_nacimiento" class="form-control" value="<?= $estudiante['orden_nacimiento'] ?: 1 ?>" min="1" max="9">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Cédula Escolar</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($estudiante['cedula_escolar']) ?>" readonly>
                            <input type="hidden" name="cedula_escolar" value="<?= htmlspecialchars($estudiante['cedula_escolar']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nacionalidad</label>
                            <input type="text" name="nacionalidad" class="form-control" value="<?= htmlspecialchars($estudiante['nacionalidad']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">País de Nacimiento</label>
                            <input type="text" name="pais_nacimiento" class="form-control" value="<?= htmlspecialchars($estudiante['pais_nacimiento']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado de Nacimiento</label>
                            <input type="text" name="estado_nacimiento" class="form-control" value="<?= htmlspecialchars($estudiante['estado_nacimiento']) ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($estudiante['direccion']) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado Residencia</label>
                            <input type="text" name="estado_residencia" class="form-control" value="<?= htmlspecialchars($estudiante['estado_residencia']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Municipio</label>
                            <input type="text" name="municipio" class="form-control" value="<?= htmlspecialchars($estudiante['municipio']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Parroquia</label>
                            <input type="text" name="parroquia" class="form-control" value="<?= htmlspecialchars($estudiante['parroquia']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Ciudad</label>
                            <input type="text" name="ciudad" class="form-control" value="<?= htmlspecialchars($estudiante['ciudad']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">¿Sufre enfermedad?</label>
                            <select name="enfermedad" id="enfermedad" class="form-select">
                                <option value="No" <?= ($estudiante['enfermedad']=='No')?'selected':'' ?>>No</option>
                                <option value="Si" <?= ($estudiante['enfermedad']=='Si')?'selected':'' ?>>Sí</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="div_enfermedad_cual" style="display: <?= ($estudiante['enfermedad']=='Si') ? 'block' : 'none' ?>;">
                            <label class="form-label fw-semibold">¿Cuál enfermedad?</label>
                            <input type="text" name="enfermedad_cual" class="form-control" value="<?= htmlspecialchars($estudiante['enfermedad_cual']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">¿Puede hacer Educación Física?</label>
                            <select name="educacion_fisica" id="educacion_fisica" class="form-select">
                                <option value="Si" <?= ($estudiante['educacion_fisica']=='Si')?'selected':'' ?>>Sí</option>
                                <option value="No" <?= ($estudiante['educacion_fisica']=='No')?'selected':'' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="div_educacion_fisica_porque" style="display: <?= ($estudiante['educacion_fisica']=='No') ? 'block' : 'none' ?>;">
                            <label class="form-label fw-semibold">¿Por qué no puede?</label>
                            <input type="text" name="educacion_fisica_porque" class="form-control" value="<?= htmlspecialchars($estudiante['educacion_fisica_porque']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">¿Alergia a medicamentos?</label>
                            <select name="alergia" id="alergia" class="form-select">
                                <option value="No" <?= ($estudiante['alergia']=='No')?'selected':'' ?>>No</option>
                                <option value="Si" <?= ($estudiante['alergia']=='Si')?'selected':'' ?>>Sí</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="div_alergia_cual" style="display: <?= ($estudiante['alergia']=='Si') ? 'block' : 'none' ?>;">
                            <label class="form-label fw-semibold">¿Cuál(es) alergias?</label>
                            <input type="text" name="alergia_cual" class="form-control" value="<?= htmlspecialchars($estudiante['alergia_cual']) ?>">
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
                            <input type="text" name="rep_nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['rep_nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cédula del Representante <span class="text-danger">*</span></label>
                            <input type="text" name="rep_cedula" class="form-control" value="<?= htmlspecialchars($estudiante['rep_cedula']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Teléfono <span class="text-danger">*</span></label>
                            <input type="text" name="rep_telefono" class="form-control" value="<?= htmlspecialchars($estudiante['rep_telefono']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sexo</label>
                            <select name="rep_sexo" class="form-select">
                                <option value="">Seleccione...</option>
                                <option value="V" <?= ($estudiante['rep_sexo']=='V')?'selected':'' ?>>Varón</option>
                                <option value="H" <?= ($estudiante['rep_sexo']=='H')?'selected':'' ?>>Hembra</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha Nacimiento</label>
                            <input type="date" name="rep_fecha_nacimiento" class="form-control" value="<?= $estudiante['rep_fecha_nac'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado Civil</label>
                            <select name="rep_estado_civil" class="form-select">
                                <option value="">Seleccione...</option>
                                <option value="Soltero/a" <?= ($estudiante['rep_estado_civil']=='Soltero/a')?'selected':'' ?>>Soltero/a</option>
                                <option value="Casado/a" <?= ($estudiante['rep_estado_civil']=='Casado/a')?'selected':'' ?>>Casado/a</option>
                                <option value="Divorciado/a" <?= ($estudiante['rep_estado_civil']=='Divorciado/a')?'selected':'' ?>>Divorciado/a</option>
                                <option value="Viudo/a" <?= ($estudiante['rep_estado_civil']=='Viudo/a')?'selected':'' ?>>Viudo/a</option>
                                <option value="Unión libre" <?= ($estudiante['rep_estado_civil']=='Unión libre')?'selected':'' ?>>Unión libre</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Afinidad</label>
                            <input type="text" name="rep_afinidad" class="form-control" value="<?= htmlspecialchars($estudiante['afinidad']) ?>" placeholder="Ej: Hermana, Primo, etc.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">País de Nacimiento</label>
                            <input type="text" name="rep_pais_nacimiento" class="form-control" value="<?= htmlspecialchars($estudiante['rep_pais_nac']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado Nacimiento</label>
                            <input type="text" name="rep_estado_nacimiento" class="form-control" value="<?= htmlspecialchars($estudiante['rep_estado_nac']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nacionalidad</label>
                            <input type="text" name="rep_nacionalidad" class="form-control" value="<?= htmlspecialchars($estudiante['rep_nacionalidad']) ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Dirección del Representante</label>
                            <textarea name="rep_direccion" class="form-control" rows="2"><?= htmlspecialchars($estudiante['rep_direccion']) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estado Residencia</label>
                            <input type="text" name="rep_estado_residencia" class="form-control" value="<?= htmlspecialchars($estudiante['rep_estado_res']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Municipio</label>
                            <input type="text" name="rep_municipio" class="form-control" value="<?= htmlspecialchars($estudiante['rep_municipio']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Parroquia</label>
                            <input type="text" name="rep_parroquia" class="form-control" value="<?= htmlspecialchars($estudiante['rep_parroquia']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Ciudad</label>
                            <input type="text" name="rep_ciudad" class="form-control" value="<?= htmlspecialchars($estudiante['rep_ciudad']) ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div id="step3" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">DATOS DE LOS PADRES <span class="text-danger">*</span></h5>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-2"></i> Debe completar al menos el nombre de la madre o del padre.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-primary">Madre</h6>
                            <label class="form-label fw-semibold">Nombre completo</label>
                            <input type="text" name="madre_nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['madre_nombre']) ?>">
                            <label class="form-label fw-semibold mt-2">Cédula</label>
                            <input type="text" name="madre_cedula" class="form-control" value="<?= htmlspecialchars($estudiante['madre_cedula']) ?>">
                            <label class="form-label fw-semibold mt-2">Teléfono</label>
                            <input type="text" name="madre_telefono" class="form-control" value="<?= htmlspecialchars($estudiante['madre_telefono']) ?>">
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Padre</h6>
                            <label class="form-label fw-semibold">Nombre completo</label>
                            <input type="text" name="padre_nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['padre_nombre']) ?>">
                            <label class="form-label fw-semibold mt-2">Cédula</label>
                            <input type="text" name="padre_cedula" class="form-control" value="<?= htmlspecialchars($estudiante['padre_cedula']) ?>">
                            <label class="form-label fw-semibold mt-2">Teléfono</label>
                            <input type="text" name="padre_telefono" class="form-control" value="<?= htmlspecialchars($estudiante['padre_telefono']) ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- STEP 4: HISTORIAL ESCOLAR -->
                <div id="step4" class="step p-3 bg-light rounded-3 mb-3" style="display:none;">
                    <h5 class="border-start border-4 border-navy ps-3 mb-4">HISTORIAL ESCOLAR</h5>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instrucciones:</strong> La <strong>última fila (la de abajo)</strong> es el año actual del estudiante. 
                        Al guardar, se actualizará automáticamente su grado, sección, peso y talla.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="tablaHistorial">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:130px;">Año Escolar</th>
                                    <th>Grado y Sección</th>
                                    <th>Reg.</th>
                                    <th>Rep.</th>
                                    <th>C</th>
                                    <th>F</th>
                                    <th>P</th>
                                    <th>Peso(kg)</th>
                                    <th>Talla(cm)</th>
                                    <th>Fecha Inscripción</th>
                                    <th style="width:100px;">Año Actual</th>
                                    <th style="width:50px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="historial-body">
                                <?php 
                                $total_filas = count($inscripciones);
                                $indice = 0;
                                
                                if ($total_filas > 0): 
                                    foreach ($inscripciones as $ins): 
                                        $es_ultima = ($indice == $total_filas - 1);
                                ?>
                                        <tr class="fila-historial" data-ano="<?= htmlspecialchars($ins['ano_escolar']) ?>" data-es-ultima="<?= $es_ultima ? '1' : '0' ?>">
                                            <td>
                                                <select name="ano_escolar[]" class="form-select form-select-sm" required>
                                                    <option value="">Seleccione</option>
                                                    <?php 
                                                    $ano_actual = date('Y');
                                                    for ($a = $ano_actual - 1; $a >= 2015; $a--): 
                                                        $periodo = $a . '-' . ($a + 1);
                                                    ?>
                                                        <option value="<?= htmlspecialchars($periodo) ?>" <?= ($ins['ano_escolar'] == $periodo) ? 'selected' : '' ?>><?= htmlspecialchars($periodo) ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="grado_seccion[]" class="form-select form-select-sm grado-seccion-select" required>
                                                    <?php 
                                                    $grado_guardado = htmlspecialchars($ins['grado_seccion']);
                                                    echo str_replace('value="' . $grado_guardado . '"', 'value="' . $grado_guardado . '" selected', $opciones_historial);
                                                    ?>
                                                </select>
                                            </td>
                                            <td><input type="text" name="registro[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ins['registro']) ?>" placeholder="Reg."></td>
                                            <td>
                                                <select name="repite[]" class="form-select form-select-sm">
                                                    <option value="No" <?= ($ins['repite']=='No')?'selected':'' ?>>No</option>
                                                    <option value="Si" <?= ($ins['repite']=='Si')?'selected':'' ?>>Sí</option>
                                                </select>
                                            </td>
                                            <td><input type="text" name="c[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ins['c']) ?>" placeholder="C"></td>
                                            <td><input type="text" name="f[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ins['f']) ?>" placeholder="F"></td>
                                            <td><input type="text" name="p[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ins['p']) ?>" placeholder="P"></td>
                                            <td><input type="number" step="0.01" name="peso[]" class="form-control form-control-sm peso-input" value="<?= $ins['peso'] ?>" placeholder="Peso"></td>
                                            <td><input type="number" step="0.01" name="talla[]" class="form-control form-control-sm talla-input" value="<?= $ins['talla'] ?>" placeholder="Talla"></td>
                                            <td><input type="date" name="fecha_inscripcion[]" class="form-control form-control-sm" value="<?= $ins['fecha_inscripcion'] ?>"></td>
                                            <td class="text-center">
                                                <?php if ($es_ultima): ?>
                                                    <span class="badge bg-success" style="font-size:0.75rem;">
                                                        <i class="fas fa-check-circle me-1"></i> Actual
                                                    </span>
                                                    <input type="hidden" name="es_actual[]" value="1">
                                                <?php else: ?>
                                                    <span class="badge bg-secondary" style="font-size:0.7rem;">
                                                        <i class="fas fa-history me-1"></i> Histórico
                                                    </span>
                                                    <input type="hidden" name="es_actual[]" value="0">
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($total_filas > 1 && !$es_ultima): ?>
                                                    <button type="button" class="btn btn-sm btn-danger eliminar-fila" title="Eliminar fila">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted" title="Año actual - no se puede eliminar">
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php 
                                        $indice++;
                                    endforeach; 
                                else: 
                                ?>
                                    <tr class="fila-historial" data-es-ultima="1">
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
                                        <td>
                                            <select name="grado_seccion[]" class="form-select form-select-sm grado-seccion-select" required>
                                                <?= $opciones_historial ?>
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
                                        <td><input type="number" step="0.01" name="peso[]" class="form-control form-control-sm peso-input" placeholder="Peso"></td>
                                        <td><input type="number" step="0.01" name="talla[]" class="form-control form-control-sm talla-input" placeholder="Talla"></td>
                                        <td><input type="date" name="fecha_inscripcion[]" class="form-control form-control-sm"></td>
                                        <td class="text-center">
                                            <span class="badge bg-success" style="font-size:0.75rem;">
                                                <i class="fas fa-check-circle me-1"></i> Actual
                                            </span>
                                            <input type="hidden" name="es_actual[]" value="1">
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted" title="Año actual - no se puede eliminar">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-start mt-2">
                        <button type="button" class="btn btn-secondary btn-sm" id="agregarFila">
                            <i class="fas fa-plus me-1"></i> Agregar año anterior
                        </button>
                        <span class="text-muted ms-2 small">
                            <i class="fas fa-info-circle"></i> El nuevo año se agrega al final (abajo) y pasa a ser el actual
                        </span>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i> Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ---------- TOGGLES CONDICIONALES ----------
    function toggleEnfermedad() {
        const show = document.getElementById('enfermedad').value === 'Si';
        document.getElementById('div_enfermedad_cual').style.display = show ? 'block' : 'none';
    }
    function toggleEducacionFisica() {
        const show = document.getElementById('educacion_fisica').value === 'No';
        document.getElementById('div_educacion_fisica_porque').style.display = show ? 'block' : 'none';
    }
    function toggleAlergia() {
        const show = document.getElementById('alergia').value === 'Si';
        document.getElementById('div_alergia_cual').style.display = show ? 'block' : 'none';
    }
    
    document.getElementById('enfermedad')?.addEventListener('change', toggleEnfermedad);
    document.getElementById('educacion_fisica')?.addEventListener('change', toggleEducacionFisica);
    document.getElementById('alergia')?.addEventListener('change', toggleAlergia);

    // ---------- WIZARD ----------
    const steps = document.querySelectorAll('.step');
    const nextBtns = document.querySelectorAll('.next-step');
    const prevBtns = document.querySelectorAll('.prev-step');
    const tabs = document.querySelectorAll('#stepTabs .nav-link');
    const progressBar = document.getElementById('progressBar');
    let currentStep = 0;
    const totalSteps = steps.length;
    
    function updateProgress() {
        progressBar.style.width = ((currentStep + 1) / totalSteps * 100) + '%';
    }
    
    function showStep(step) {
        steps.forEach((s, i) => {
            s.style.display = i === step ? 'block' : 'none';
        });
        tabs.forEach((tab, i) => {
            if (i === step) {
                tab.classList.add('active');
                tab.classList.remove('disabled');
            } else if (i < step) {
                tab.classList.remove('active', 'disabled');
                tab.classList.add('completed');
            } else {
                tab.classList.remove('active', 'completed');
                tab.classList.add('disabled');
            }
        });
        currentStep = step;
        updateProgress();
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
        if (isValid && currentStep < totalSteps - 1) {
            showStep(currentStep + 1);
        }
    }
    
    function prevHandler() {
        if (currentStep > 0) showStep(currentStep - 1);
    }
    
    nextBtns.forEach(btn => {
        btn.removeEventListener('click', nextHandler);
        btn.addEventListener('click', nextHandler);
    });
    prevBtns.forEach(btn => {
        btn.removeEventListener('click', prevHandler);
        btn.addEventListener('click', prevHandler);
    });
    
    showStep(0);

    // ---------- TABLA DINÁMICA ----------
    const agregarBtn = document.getElementById('agregarFila');
    const historialBody = document.getElementById('historial-body');

    function agregarFilaHistorial() {
        const filas = historialBody.querySelectorAll('.fila-historial');
        const ultimaFila = filas[filas.length - 1];
        
        if (!ultimaFila) return;
        
        const newRow = ultimaFila.cloneNode(true);
        
        newRow.querySelectorAll('input, select').forEach(inp => {
            if (inp.type === 'text' || inp.type === 'number' || inp.type === 'date') {
                inp.value = '';
            }
            if (inp.tagName === 'SELECT') {
                inp.selectedIndex = 0;
            }
        });
        
        // Antigua última fila → HISTÓRICO
        const celdaActualUltima = ultimaFila.querySelector('td:nth-child(11)');
        if (celdaActualUltima) {
            celdaActualUltima.innerHTML = `
                <span class="badge bg-secondary" style="font-size:0.7rem;">
                    <i class="fas fa-history me-1"></i> Histórico
                </span>
                <input type="hidden" name="es_actual[]" value="0">
            `;
            ultimaFila.dataset.esUltima = '0';
        }
        
        const celdaAccionUltima = ultimaFila.querySelector('td:last-child');
        if (celdaAccionUltima) {
            celdaAccionUltima.innerHTML = `
                <button type="button" class="btn btn-sm btn-danger eliminar-fila" title="Eliminar fila">
                    <i class="fas fa-times"></i>
                </button>
            `;
        }
        
        // Nueva fila → ACTUAL
        const celdaActualNueva = newRow.querySelector('td:nth-child(11)');
        if (celdaActualNueva) {
            celdaActualNueva.innerHTML = `
                <span class="badge bg-success" style="font-size:0.75rem;">
                    <i class="fas fa-check-circle me-1"></i> Actual
                </span>
                <input type="hidden" name="es_actual[]" value="1">
            `;
        }
        
        const celdaAccionNueva = newRow.querySelector('td:last-child');
        if (celdaAccionNueva) {
            celdaAccionNueva.innerHTML = `
                <span class="text-muted" title="Año actual - no se puede eliminar">
                    <i class="fas fa-lock"></i>
                </span>
            `;
        }
        
        newRow.dataset.esUltima = '1';
        historialBody.appendChild(newRow);
        
        const todasLasFilas = historialBody.querySelectorAll('.fila-historial');
        todasLasFilas.forEach((f, idx) => {
            f.dataset.esUltima = (idx === todasLasFilas.length - 1) ? '1' : '0';
        });
        
        mostrarMensaje('Nuevo año agregado. Complete los datos del nuevo año actual (la última fila).');
    }

    agregarBtn?.addEventListener('click', agregarFilaHistorial);

    // ---------- ELIMINAR FILA ----------
    document.getElementById('historial-body')?.addEventListener('click', function(e) {
        const btnEliminar = e.target.closest('.eliminar-fila');
        if (btnEliminar) {
            const fila = btnEliminar.closest('.fila-historial');
            const totalFilas = document.querySelectorAll('#historial-body .fila-historial').length;
            
            if (totalFilas > 1) {
                if (fila.dataset.esUltima === '1') {
                    alert('No se puede eliminar el año actual. Agregue un nuevo año primero.');
                    return;
                }
                fila.remove();
                
                const filasRestantes = document.querySelectorAll('#historial-body .fila-historial');
                filasRestantes.forEach((f, idx) => {
                    f.dataset.esUltima = (idx === filasRestantes.length - 1) ? '1' : '0';
                });
                
                const ultimaFila = filasRestantes[filasRestantes.length - 1];
                if (ultimaFila) {
                    const celdaActual = ultimaFila.querySelector('td:nth-child(11)');
                    if (celdaActual) {
                        celdaActual.innerHTML = `
                            <span class="badge bg-success" style="font-size:0.75rem;">
                                <i class="fas fa-check-circle me-1"></i> Actual
                            </span>
                            <input type="hidden" name="es_actual[]" value="1">
                        `;
                    }
                    const celdaAccion = ultimaFila.querySelector('td:last-child');
                    if (celdaAccion) {
                        celdaAccion.innerHTML = `
                            <span class="text-muted" title="Año actual - no se puede eliminar">
                                <i class="fas fa-lock"></i>
                            </span>
                        `;
                    }
                    ultimaFila.dataset.esUltima = '1';
                }
            } else {
                alert('Debe haber al menos un registro escolar (el año actual).');
            }
        }
    });

    // ---------- VALIDACIÓN FINAL ----------
    const wizardForm = document.getElementById('wizardForm');
    if (wizardForm) {
        wizardForm.addEventListener('submit', function(e) {
            const actuales = document.querySelectorAll('input[name="es_actual[]"][value="1"]');
            if (actuales.length === 0) {
                e.preventDefault();
                alert('Error: Debe haber al menos un año escolar marcado como "Actual".');
                showStep(3);
                return;
            }
            
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

    function mostrarMensaje(texto) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show mt-2';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <strong>${texto}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const tabla = document.getElementById('tablaHistorial');
        const container = tabla.parentElement;
        const existingAlert = container.querySelector('.alert-success');
        if (existingAlert) existingAlert.remove();
        container.insertBefore(alertDiv, tabla.nextSibling);
        
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, 5000);
    }
});
</script>

<?php include('../includes/footer.php'); ?>