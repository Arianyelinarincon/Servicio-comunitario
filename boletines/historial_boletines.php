<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';
require_once '../config/conexion.php';

$mensaje_alerta = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_boletin'])) {
    $id_boletin = intval($_POST['id_boletin']);
    $momento_elegido = $_POST['momento_elegido'];
    
    $query_update = "UPDATE boletines SET observacion = ?";
    $params_update = [$_POST['observacion'] ?? ''];
    $types_update = "s";
    
    if ($momento_elegido === '1' || $momento_elegido === 'todos') {
        $query_update .= ", m1_proyecto=?, m1_formacion=?, m1_relacion=?, m1_sugerencias=?";
        array_push($params_update, $_POST['m1_proyecto'] ?? '', $_POST['m1_formacion'] ?? '', $_POST['m1_relacion'] ?? '', $_POST['m1_sugerencias'] ?? '');
        $types_update .= "ssss";
    }
    if ($momento_elegido === '2' || $momento_elegido === 'todos') {
        $query_update .= ", m2_proyecto=?, m2_formacion=?, m2_relacion=?, m2_sugerencias=?";
        array_push($params_update, $_POST['m2_proyecto'] ?? '', $_POST['m2_formacion'] ?? '', $_POST['m2_relacion'] ?? '', $_POST['m2_sugerencias'] ?? '');
        $types_update .= "ssss";
    }
    if ($momento_elegido === '3' || $momento_elegido === 'todos') {
        $query_update .= ", m3_proyecto=?, m3_formacion=?, m3_relacion=?, m3_sugerencias=?";
        array_push($params_update, $_POST['m3_proyecto'] ?? '', $_POST['m3_formacion'] ?? '', $_POST['m3_relacion'] ?? '', $_POST['m3_sugerencias'] ?? '');
        $types_update .= "ssss";
    }
    
    $query_update .= " WHERE id = ?";
    $params_update[] = $id_boletin;
    $types_update .= "i";
    
    $stmt_upd = $conexion->prepare($query_update);
    $stmt_upd->bind_param($types_update, ...$params_update);
    if ($stmt_upd->execute()) {
        $mensaje_alerta = "<div class='alert alert-success mt-3'><b>¡Éxito!</b> El boletín fue modificado correctamente.</div>";
    } else {
        $mensaje_alerta = "<div class='alert alert-danger mt-3'><b>Error al modificar:</b> " . $conexion->error . "</div>";
    }
    $stmt_upd->close();
}

$buscar_estudiante = trim($_GET['estudiante'] ?? '');
$periodo = trim($_GET['periodo'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');

$sql = "SELECT b.*, 
               CONCAT(e.nombre, ' ', e.apellido) AS nombre_estudiante,
               e.cedula_escolar
        FROM boletines b
        JOIN estudiantes e ON b.estudiante_id = e.id
        WHERE 1=1";
$params = [];
$types = "";

if ($buscar_estudiante) {
    $sql .= " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.cedula_escolar LIKE ?)";
    $like = "%$buscar_estudiante%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}
if ($periodo) {
    $sql .= " AND b.periodo = ?";
    $params[] = $periodo;
    $types .= "s";
}
if ($tipo) {
    $sql .= " AND b.tipo_boletin = ?";
    $params[] = $tipo;
    $types .= "s";
}
$sql .= " ORDER BY b.fecha_emision DESC";

$stmt = $conexion->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4"><i class="fas fa-history"></i> Historial de Boletines</h2>
    
    <?= $mensaje_alerta ?>

    <?php if (isset($_GET['editar_id'])): 
        $id_editar = intval($_GET['editar_id']);
        $stmt_ed = $conexion->prepare("SELECT b.*, CONCAT(e.nombre, ' ', e.apellido) AS nombre_estudiante FROM boletines b JOIN estudiantes e ON b.estudiante_id = e.id WHERE b.id = ?");
        $stmt_ed->bind_param("i", $id_editar);
        $stmt_ed->execute();
        $bol_editar = $stmt_ed->get_result()->fetch_assoc();
        $stmt_ed->close();
        if ($bol_editar):
    ?>
        <div class="card mb-4 border-primary shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-edit"></i> Modificar Boletín - <?= htmlspecialchars($bol_editar['nombre_estudiante']) ?></h4>
            </div>
            <div class="card-body">
                <form action="historial_boletines.php" method="POST">
                    <input type="hidden" name="id_boletin" value="<?= $bol_editar['id'] ?>">
                    <input type="hidden" name="modificar_boletin" value="1">
                    
                    <div class="mb-4 p-3 bg-light border border-info rounded">
                        <label class="form-label fw-bold text-primary">Seleccione el Momento Académico a modificar:</label>
                        <select name="momento_elegido" id="momento_elegido" class="form-select border-primary" onchange="seleccionarMomento(this.value)">
                            <option value="todos">Modificar Todos los Momentos</option>
                            <option value="1">Solo 1er Momento</option>
                            <option value="2">Solo 2do Momento</option>
                            <option value="3">Solo 3er Momento</option>
                        </select>
                        <small class="text-muted d-block mt-2"><b>Nota:</b> Los datos de los momentos que queden ocultos no se alterarán al guardar.</small>
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold">Observación General:</label>
                        <textarea name="observacion" rows="3" class="form-control"><?= htmlspecialchars($bol_editar['observacion'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4" id="bloque_m1">
                            <div class="border p-3 rounded mb-3 bg-white shadow-sm h-100">
                                <h5 class="text-primary border-bottom pb-2">Primer Momento</h5>
                                <label class="fw-bold mt-2">Proyecto de Aprendizaje:</label>
                                <input type="text" name="m1_proyecto" class="form-control mb-2" value="<?= htmlspecialchars($bol_editar['m1_proyecto'] ?? '') ?>">
                                <label class="fw-bold mt-2">Formación personal, social y comunicación:</label>
                                <textarea name="m1_formacion" rows="3" class="form-control mb-2"><?= htmlspecialchars($bol_editar['m1_formacion'] ?? '') ?></textarea>
                                <label class="fw-bold mt-2">Relación entre los Componentes del Ambiente:</label>
                                <textarea name="m1_relacion" rows="3" class="form-control mb-2"><?= htmlspecialchars($bol_editar['m1_relacion'] ?? '') ?></textarea>
                                <label class="fw-bold mt-2">Sugerencias:</label>
                                <textarea name="m1_sugerencias" rows="3" class="form-control mb-2"><?= htmlspecialchars($bol_editar['m1_sugerencias'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-4" id="bloque_m2">
                            <div class="border p-3 rounded mb-3 bg-white shadow-sm h-100">
                                <h5 class="text-primary border-bottom pb-2">Segundo Momento</h5>
                                <label class="fw-bold mt-2">Proyecto de Aprendizaje:</label>
                                <input type="text" name="m2_proyecto" class="form-control mb-2" value="<?= htmlspecialchars($bol_editar['m2_proyecto'] ?? '') ?>">
                                <label class="fw-bold mt-2">Formación personal, social y comunicación:</label>
                                <textarea name="m2_formacion" rows="3" class="form-control mb-2"><?= htmlspecialchars($bol_editar['m2_formacion'] ?? '') ?></textarea>
                                <label class="fw-bold mt-2">Relación entre los Componentes del Ambiente:</label>
                                <textarea name="m2_relacion" rows="3" class="form-control mb-2"><?= htmlspecialchars($bol_editar['m2_relacion'] ?? '') ?></textarea>
                                <label class="fw-bold mt-2">Sugerencias:</label>
                                <textarea name="m2_sugerencias" rows="3" class="form-control mb-2"><?= htmlspecialchars($bol_editar['m2_sugerencias'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-4" id="bloque_m3">
                            <div class="border p-3 rounded mb-3 bg-white shadow-sm h-100">
                                <h5 class="text-primary border-bottom pb-2">Tercer Momento</h5>
                                <label class="fw-bold mt-2">Proyecto de Aprendizaje:</label>
                                <input type="text" name="m3_proyecto" class="form-control mb-2" value="<?= htmlspecialchars($bol_editar['m3_proyecto'] ?? '') ?>">
                                <label class="fw-bold mt-2">Formación personal, social y comunicación:</label>
                                <textarea name="m3_formacion" rows="3" class="form-control mb-2"><?= htmlspecialchars($bol_editar['m3_formacion'] ?? '') ?></textarea>
                                <label class="fw-bold mt-2">Relación entre los Componentes del Ambiente:</label>
                                <textarea name="m3_relacion" rows="3" class="form-control mb-2"><?= htmlspecialchars($bol_editar['m3_relacion'] ?? '') ?></textarea>
                                <label class="fw-bold mt-2">Sugerencias:</label>
                                <textarea name="m3_sugerencias" rows="3" class="form-control mb-2"><?= htmlspecialchars($bol_editar['m3_sugerencias'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-success px-4 py-2 fw-bold"><i class="fas fa-save"></i> Guardar Cambios</button>
                        <a href="historial_boletines.php" class="btn btn-secondary px-4 py-2 fw-bold">Cancelar / Volver</a>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function seleccionarMomento(val) {
            document.getElementById('bloque_m1').style.display = (val === '1' || val === 'todos') ? 'block' : 'none';
            document.getElementById('bloque_m2').style.display = (val === '2' || val === 'todos') ? 'block' : 'none';
            document.getElementById('bloque_m3').style.display = (val === '3' || val === 'todos') ? 'block' : 'none';
        }
        seleccionarMomento(document.getElementById('momento_elegido').value);
        </script>
    <?php endif; endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            
            <form method="GET" class="row mb-4">
                <div class="col-md-4">
                    <label>Buscar Estudiante / C.E</label>
                    <input type="text" name="estudiante" class="form-control" value="<?= htmlspecialchars($buscar_estudiante) ?>" placeholder="Nombre o cédula escolar...">
                </div>
                <div class="col-md-3">
                    <label>Período Escolar</label>
                    <select name="periodo" class="form-select">
                        <option value="">Todos</option>
                        <option value="2025 / 2026" <?= $periodo === '2025 / 2026' ? 'selected' : '' ?>>2025 / 2026</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Tipo de Boletín</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="inicial" <?= $tipo === 'inicial' ? 'selected' : '' ?>>Inicial</option>
                        <option value="primaria" <?= $tipo === 'primaria' ? 'selected' : '' ?>>Primaria</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Buscar</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Estudiante</th>
                            <th>C.E. Escolar</th>
                            <th>Año Escolar</th>
                            <th>Tipo</th>
                            <th>Fecha Emisión</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nombre_estudiante']) ?></td>
                                <td><?= htmlspecialchars($row['cedula_escolar'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['periodo']) ?></td>
                                <td><?= ucfirst($row['tipo_boletin']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['fecha_emision'])) ?></td>
                                <td>
                                    <a href="ver_boletin_guardado.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info text-white" target="_blank" style="margin-right: 5px;"><i class="fas fa-eye"></i> Ver Boletín</a>
                                    
                                    <a href="historial_boletines.php?editar_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Editar</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No hay boletines guardados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>