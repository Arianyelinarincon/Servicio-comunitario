<?php
session_start();
require_once('../config/conexion.php');

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

// Obtener historial escolar (inscripciones)
$stmt_ins = $conexion->prepare("SELECT * FROM inscripciones WHERE estudiante_id = ? ORDER BY ano_escolar DESC");
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
        $seccion_id = intval($_POST['sala']);

        // ========== OBTENER NOMBRE DE LA SALA DESDE EL ID DE SECCIÓN ==========
        $stmt_sec = $conexion->prepare("SELECT sala FROM secciones WHERE id = ?");
        $stmt_sec->bind_param("i", $seccion_id);
        $stmt_sec->execute();
        $result_sec = $stmt_sec->get_result();
        $row_sec = $result_sec->fetch_assoc();
        $sala_nombre = $row_sec['sala'] ?? '';
        $stmt_sec->close();

        // ========== ACTUALIZAR ESTUDIANTE ==========
        $stmt_upd = $conexion->prepare("UPDATE estudiantes SET 
            nombre=?, apellido=?, fecha_nacimiento=?, genero=?, orden_nacimiento=?,
            nacionalidad=?, pais_nacimiento=?, estado_nacimiento=?, direccion=?,
            estado_residencia=?, municipio=?, parroquia=?, ciudad=?,
            enfermedad=?, enfermedad_cual=?, educacion_fisica=?, educacion_fisica_porque=?,
            alergia=?, alergia_cual=?, madre_nombre=?, madre_cedula=?, madre_telefono=?,
            padre_nombre=?, padre_cedula=?, padre_telefono=?,
            sala=?, seccion_id=?
            WHERE id=?");
        $types = str_repeat('s', 26) . 'ii';
        $stmt_upd->bind_param($types,
            $nombre, $apellido, $fecha_nac, $genero, $orden_nacimiento,
            $nacionalidad, $pais_nac, $estado_nac, $direccion,
            $estado_res, $municipio, $parroquia, $ciudad,
            $enfermedad, $enfermedad_cual, $educacion_fisica, $educacion_fisica_porque,
            $alergia, $alergia_cual, $madre_nombre, $madre_cedula, $madre_telefono,
            $padre_nombre, $padre_cedula, $padre_telefono,
            $sala_nombre, $seccion_id, $id);
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
        $stmt_rep->bind_param("sssssssssssssssi",
            $rep_nombre, $rep_cedula, $rep_telefono, $rep_fecha_nac, $rep_estado_civil,
            $rep_afinidad, $rep_sexo, $rep_pais_nac, $rep_estado_nac, $rep_nacionalidad,
            $rep_direccion, $rep_estado_res, $rep_municipio, $rep_parroquia, $rep_ciudad,
            $rep_id);
        $stmt_rep->execute();
        $stmt_rep->close();

        // ========== ACTUALIZAR INSCRIPCIONES (historial escolar) ==========
        $stmt_del = $conexion->prepare("DELETE FROM inscripciones WHERE estudiante_id = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();
        $stmt_del->close();

        $ano_escolar_arr = $_POST['ano_escolar'] ?? [];
        $grado_seccion_arr = $_POST['grado_seccion'] ?? [];
        $registro_arr = $_POST['registro'] ?? [];
        $repite_arr = $_POST['repite'] ?? [];
        $c_arr = $_POST['c'] ?? [];
        $f_arr = $_POST['f'] ?? [];
        $p_arr = $_POST['p'] ?? [];
        $peso_arr = $_POST['peso'] ?? [];
        $talla_arr = $_POST['talla'] ?? [];

        $fecha_inscripcion = date('Y-m-d');
        $funcionario = $_SESSION['nombre_profesor'] ?? $_SESSION['usuario'];

        $stmt_ins = $conexion->prepare("INSERT INTO inscripciones 
            (estudiante_id, ano_escolar, grado_seccion, registro, repite, c, f, p, peso, talla, 
             firma_representante, fecha_inscripcion, funcionario) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?)");

        for ($i = 0; $i < count($ano_escolar_arr); $i++) {
            $stmt_ins->bind_param("isssssssddss", 
                $id, $ano_escolar_arr[$i], $grado_seccion_arr[$i], $registro_arr[$i], $repite_arr[$i],
                $c_arr[$i], $f_arr[$i], $p_arr[$i], $peso_arr[$i], $talla_arr[$i],
                $fecha_inscripcion, $funcionario);
            $stmt_ins->execute();
        }
        $stmt_ins->close();

        $conexion->commit();
        header("Location: editar_estudiantes.php?id=$id&msg=success");
        exit();
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje = "Error al guardar: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

include('../includes/header.php'); 

// ========== OBTENER LISTA DE SECCIONES PARA EL SELECT (CON ID) ==========
$secciones = $conexion->query("SELECT id, sala, nombre FROM secciones ORDER BY sala, nombre");
$opciones_secciones = '<option value="">Seleccione</option>';
while($sec = $secciones->fetch_assoc()) {
    $selected = ($estudiante['seccion_id'] == $sec['id']) ? 'selected' : '';
    $opciones_secciones .= '<option value="' . $sec['id'] . '" ' . $selected . '>' . htmlspecialchars($sec['sala'] . ' - Sección ' . $sec['nombre']) . '</option>';
}
?>

<div class="container mt-4 mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-navy text-white rounded-top">
            <h4 class="mb-0"><i class="fas fa-edit me-2"></i> Editar Inscripción Completa</h4>
        </div>
        <div class="card-body p-4">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show"><?= $mensaje ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show">¡Datos actualizados con éxito!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <form method="POST">
                <!-- ================= DATOS DEL ALUMNO ================= -->
                <h5 class="border-start border-4 border-navy ps-3 mb-4">DATOS DEL ALUMNO</h5>
                <div class="row g-3">
                    <div class="col-md-6"><label>Nombres *</label><input type="text" name="nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['nombre']) ?>" required></div>
                    <div class="col-md-6"><label>Apellidos *</label><input type="text" name="apellido" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['apellido']) ?>" required></div>
                    <div class="col-md-3"><label>Fecha Nacimiento *</label><input type="date" name="fecha_nacimiento" class="form-control" value="<?= $estudiante['fecha_nacimiento'] ?>" required></div>
                    <div class="col-md-3"><label>Sexo *</label><select name="genero" class="form-select" required><option value="V" <?= ($estudiante['genero']=='V')?'selected':'' ?>>Varón</option><option value="H" <?= ($estudiante['genero']=='H')?'selected':'' ?>>Hembra</option></select></div>
                    <div class="col-md-3"><label>Orden nacimiento</label><input type="number" name="orden_nacimiento" class="form-control" value="<?= $estudiante['orden_nacimiento'] ?>" min="1" max="9"></div>
                    <div class="col-md-3"><label>Cédula Escolar (solo lectura)</label><input type="text" class="form-control bg-light" value="<?= htmlspecialchars($estudiante['cedula_escolar']) ?>" readonly></div>
                    <div class="col-md-4"><label>Nacionalidad</label><input type="text" name="nacionalidad" class="form-control" value="<?= htmlspecialchars($estudiante['nacionalidad']) ?>"></div>
                    <div class="col-md-4"><label>País de Nacimiento</label><input type="text" name="pais_nacimiento" class="form-control" value="<?= htmlspecialchars($estudiante['pais_nacimiento']) ?>"></div>
                    <div class="col-md-4"><label>Estado de Nacimiento</label><input type="text" name="estado_nacimiento" class="form-control" value="<?= htmlspecialchars($estudiante['estado_nacimiento']) ?>"></div>
                    <div class="col-md-6"><label>Dirección</label><textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($estudiante['direccion']) ?></textarea></div>
                    <div class="col-md-3"><label>Estado Residencia</label><input type="text" name="estado_residencia" class="form-control" value="<?= htmlspecialchars($estudiante['estado_residencia']) ?>"></div>
                    <div class="col-md-3"><label>Municipio</label><input type="text" name="municipio" class="form-control" value="<?= htmlspecialchars($estudiante['municipio']) ?>"></div>
                    <div class="col-md-3"><label>Parroquia</label><input type="text" name="parroquia" class="form-control" value="<?= htmlspecialchars($estudiante['parroquia']) ?>"></div>
                    <div class="col-md-3"><label>Ciudad</label><input type="text" name="ciudad" class="form-control" value="<?= htmlspecialchars($estudiante['ciudad']) ?>"></div>
                    <div class="col-md-4"><label>¿Sufre enfermedad?</label><select name="enfermedad" class="form-select"><option value="No" <?= ($estudiante['enfermedad']=='No')?'selected':'' ?>>No</option><option value="Si" <?= ($estudiante['enfermedad']=='Si')?'selected':'' ?>>Sí</option></select></div>
                    <div class="col-md-8"><label>¿Cuál enfermedad?</label><input type="text" name="enfermedad_cual" class="form-control" value="<?= htmlspecialchars($estudiante['enfermedad_cual']) ?>"></div>
                    <div class="col-md-4"><label>¿Puede hacer Educación Física?</label><select name="educacion_fisica" class="form-select"><option value="Si" <?= ($estudiante['educacion_fisica']=='Si')?'selected':'' ?>>Sí</option><option value="No" <?= ($estudiante['educacion_fisica']=='No')?'selected':'' ?>>No</option></select></div>
                    <div class="col-md-8"><label>¿Por qué no puede?</label><input type="text" name="educacion_fisica_porque" class="form-control" value="<?= htmlspecialchars($estudiante['educacion_fisica_porque']) ?>"></div>
                    <div class="col-md-4"><label>¿Alergia a medicamentos?</label><select name="alergia" class="form-select"><option value="No" <?= ($estudiante['alergia']=='No')?'selected':'' ?>>No</option><option value="Si" <?= ($estudiante['alergia']=='Si')?'selected':'' ?>>Sí</option></select></div>
                    <div class="col-md-8"><label>¿Cuál(es) alergias?</label><input type="text" name="alergia_cual" class="form-control" value="<?= htmlspecialchars($estudiante['alergia_cual']) ?>"></div>
                    <div class="col-md-6"><label>Madre (nombre)</label><input type="text" name="madre_nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['madre_nombre']) ?>"></div>
                    <div class="col-md-3"><label>Cédula madre</label><input type="text" name="madre_cedula" class="form-control" value="<?= htmlspecialchars($estudiante['madre_cedula']) ?>"></div>
                    <div class="col-md-3"><label>Teléfono madre</label><input type="text" name="madre_telefono" class="form-control" value="<?= htmlspecialchars($estudiante['madre_telefono']) ?>"></div>
                    <div class="col-md-6"><label>Padre (nombre)</label><input type="text" name="padre_nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['padre_nombre']) ?>"></div>
                    <div class="col-md-3"><label>Cédula padre</label><input type="text" name="padre_cedula" class="form-control" value="<?= htmlspecialchars($estudiante['padre_cedula']) ?>"></div>
                    <div class="col-md-3"><label>Teléfono padre</label><input type="text" name="padre_telefono" class="form-control" value="<?= htmlspecialchars($estudiante['padre_telefono']) ?>"></div>
                    
                    <!-- ========== SELECCIÓN DE SALA ACTUAL ========== -->
                    <div class="col-md-4"><label>Sala / Grado actual</label>
                        <select name="sala" class="form-select" required>
                            <?= $opciones_secciones ?>
                        </select>
                    </div>
                </div>

                <!-- ================= DATOS DEL REPRESENTANTE ================= -->
                <h5 class="border-start border-4 border-navy ps-3 mb-4 mt-5">DATOS DEL REPRESENTANTE</h5>
                <div class="row g-3">
                    <div class="col-md-6"><label>Cédula *</label><input type="text" name="rep_cedula" class="form-control" value="<?= htmlspecialchars($estudiante['rep_cedula']) ?>" required></div>
                    <div class="col-md-6"><label>Nombres y Apellidos *</label><input type="text" name="rep_nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($estudiante['rep_nombre']) ?>" required></div>
                    <div class="col-md-3"><label>Fecha Nacimiento</label><input type="date" name="rep_fecha_nacimiento" class="form-control" value="<?= $estudiante['rep_fecha_nac'] ?>"></div>
                    <div class="col-md-3"><label>Estado Civil</label><input type="text" name="rep_estado_civil" class="form-control" value="<?= htmlspecialchars($estudiante['rep_estado_civil']) ?>"></div>
                    <div class="col-md-3"><label>Afinidad</label><input type="text" name="rep_afinidad" class="form-control" value="<?= htmlspecialchars($estudiante['afinidad']) ?>"></div>
                    <div class="col-md-3"><label>Teléfono *</label><input type="text" name="rep_telefono" class="form-control" value="<?= htmlspecialchars($estudiante['rep_telefono']) ?>" required></div>
                    <div class="col-md-3"><label>Sexo</label><select name="rep_sexo" class="form-select"><option value="V" <?= ($estudiante['rep_sexo']=='V')?'selected':'' ?>>Varón</option><option value="H" <?= ($estudiante['rep_sexo']=='H')?'selected':'' ?>>Hembra</option></select></div>
                    <div class="col-md-3"><label>País Nacimiento</label><input type="text" name="rep_pais_nacimiento" class="form-control" value="<?= htmlspecialchars($estudiante['rep_pais_nac']) ?>"></div>
                    <div class="col-md-3"><label>Estado Nacimiento</label><input type="text" name="rep_estado_nacimiento" class="form-control" value="<?= htmlspecialchars($estudiante['rep_estado_nac']) ?>"></div>
                    <div class="col-md-3"><label>Nacionalidad</label><input type="text" name="rep_nacionalidad" class="form-control" value="<?= htmlspecialchars($estudiante['rep_nacionalidad']) ?>"></div>
                    <div class="col-md-12"><label>Dirección</label><textarea name="rep_direccion" class="form-control" rows="2"><?= htmlspecialchars($estudiante['rep_direccion']) ?></textarea></div>
                    <div class="col-md-3"><label>Estado Residencia</label><input type="text" name="rep_estado_residencia" class="form-control" value="<?= htmlspecialchars($estudiante['rep_estado_res']) ?>"></div>
                    <div class="col-md-3"><label>Municipio</label><input type="text" name="rep_municipio" class="form-control" value="<?= htmlspecialchars($estudiante['rep_municipio']) ?>"></div>
                    <div class="col-md-3"><label>Parroquia</label><input type="text" name="rep_parroquia" class="form-control" value="<?= htmlspecialchars($estudiante['rep_parroquia']) ?>"></div>
                    <div class="col-md-3"><label>Ciudad</label><input type="text" name="rep_ciudad" class="form-control" value="<?= htmlspecialchars($estudiante['rep_ciudad']) ?>"></div>
                </div>

                <!-- ================= HISTORIAL ESCOLAR ================= -->
                <h5 class="border-start border-4 border-navy ps-3 mb-4 mt-5">HISTORIAL ESCOLAR (Años cursados)</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="tablaHistorial">
                        <thead class="table-light">
                            <tr><th>Año Escolar</th><th>Grado y Sección</th><th>Reg.</th><th>Rep.</th><th>C</th><th>F</th><th>P</th><th>Peso(kg)</th><th>Talla(cm)</th><th>Acción</th></tr>
                        </thead>
                        <tbody id="historial-body">
                            <?php if (count($inscripciones) > 0): ?>
                                <?php foreach ($inscripciones as $ins): ?>
                                    <tr class="fila-historial">
                                        <td>
                                            <select name="ano_escolar[]" class="form-select form-select-sm" required>
                                                <option value="">Seleccione</option>
                                                <?php for ($a = 2000; $a <= 2049; $a++): 
                                                    $periodo = $a . '-' . ($a + 1);
                                                    $selected = ($ins['ano_escolar'] == $periodo) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= htmlspecialchars($periodo) ?>" <?= $selected ?>><?= htmlspecialchars($periodo) ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </td>
                                        <td><select name="grado_seccion[]" class="form-select form-select-sm" required><?= str_replace('value="' . htmlspecialchars($ins['grado_seccion']) . '"', 'value="' . htmlspecialchars($ins['grado_seccion']) . '" selected', $opciones_secciones) ?></select></td>
                                        <td><input type="text" name="registro[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ins['registro']) ?>"></td>
                                        <td><select name="repite[]" class="form-select form-select-sm"><option value="No" <?= ($ins['repite']=='No')?'selected':'' ?>>No</option><option value="Si" <?= ($ins['repite']=='Si')?'selected':'' ?>>Si</option></select></td>
                                        <td><input type="text" name="c[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ins['c']) ?>"></td>
                                        <td><input type="text" name="f[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ins['f']) ?>"></td>
                                        <td><input type="text" name="p[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ins['p']) ?>"></td>
                                        <td><input type="number" step="0.1" name="peso[]" class="form-control form-control-sm" value="<?= $ins['peso'] ?>"></td>
                                        <td><input type="number" step="0.1" name="talla[]" class="form-control form-control-sm" value="<?= $ins['talla'] ?>"></td>
                                        <td class="text-center"><button type="button" class="btn btn-danger btn-sm eliminar-fila">✖</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="fila-historial">
                                    <td>
                                        <select name="ano_escolar[]" class="form-select form-select-sm" required>
                                            <option value="">Seleccione</option>
                                            <?php for ($a = 2000; $a <= 2049; $a++): 
                                                $periodo = $a . '-' . ($a + 1);
                                            ?>
                                                <option value="<?= htmlspecialchars($periodo) ?>"><?= htmlspecialchars($periodo) ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-start mt-2">
                    <button type="button" class="btn btn-secondary btn-sm" id="agregarFila"><i class="fas fa-plus me-1"></i> Agregar otro año</button>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i> Guardar Cambios</button>
                    <a href="listado.php" class="btn btn-secondary px-4">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('agregarFila')?.addEventListener('click', function() {
    const tbody = document.getElementById('historial-body');
    const newRow = tbody.querySelector('.fila-historial').cloneNode(true);
    newRow.querySelectorAll('input, select').forEach(inp => {
        if (inp.type === 'text' || inp.type === 'number') inp.value = '';
        if (inp.tagName === 'SELECT') inp.selectedIndex = 0;
    });
    tbody.appendChild(newRow);
});
document.getElementById('historial-body')?.addEventListener('click', function(e) {
    if (e.target.classList.contains('eliminar-fila')) {
        if (document.querySelectorAll('.fila-historial').length > 1) e.target.closest('tr').remove();
        else alert('Debe haber al menos un registro escolar.');
    }
});
</script>

<style>
    .bg-navy { background-color: #003366 !important; }
    .btn-primary { background-color: #003366; border-color: #003366; }
    .btn-primary:hover { background-color: #002244; }
</style>

<?php include('../includes/footer.php'); ?>