<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Procesar eliminación por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error CSRF");
    }
    $id = intval($_POST['id']);
    $stmt = $conexion->prepare("UPDATE estudiantes SET estatus = 'Inactivo' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: listado.php?msg=deleted");
    exit();
}

// Filtros
$sala_filtro = $_GET['sala'] ?? '';
$ano_filtro = $_GET['ano'] ?? '';

// Obtener estudiantes con su último año escolar (de la tabla inscripciones)
$sql = "SELECT e.*, r.nombre_completo AS rep_nombre,
               (SELECT ano_escolar FROM inscripciones WHERE estudiante_id = e.id ORDER BY fecha_inscripcion DESC LIMIT 1) AS ano_escolar_actual
        FROM estudiantes e
        LEFT JOIN representantes r ON e.representante_id = r.id
        WHERE e.estatus = 'Activo'";

if ($sala_filtro) {
    $sql .= " AND e.sala = '" . mysqli_real_escape_string($conexion, $sala_filtro) . "'";
}
if ($ano_filtro) {
    // Filtro por año escolar (ej: 2024 coincide con "2024-2025")
    $sql .= " AND EXISTS (SELECT 1 FROM inscripciones WHERE estudiante_id = e.id AND ano_escolar LIKE '$ano_filtro%')";
}
$sql .= " ORDER BY e.sala, e.apellido, e.nombre";

$result = $conexion->query($sql);
$salas = $conexion->query("SELECT DISTINCT sala FROM secciones ORDER BY sala");

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h2 class="mt-4 mb-4">Listado de Estudiantes</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show">Estudiante eliminado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_GET['inscripcion']) && $_GET['inscripcion'] === 'exito'): ?>
        <div class="alert alert-success alert-dismissible fade show">¡Inscripción registrada con éxito!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-4">
                    <label class="form-label">Sala / Grado</label>
                    <select name="sala" id="filtro_sala" class="form-select">
                        <option value="">Todas</option>
                        <?php while($row = $salas->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['sala']) ?>" <?= ($sala_filtro == $row['sala']) ? 'selected' : '' ?>><?= ucfirst($row['sala']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Año Escolar</label>
                    <select name="ano" class="form-select">
                        <option value="">Todos</option>
                        <?php for($y = 2020; $y <= date('Y')+1; $y++): ?>
                            <option value="<?= $y ?>" <?= ($ano_filtro == $y) ? 'selected' : '' ?>><?= $y ?> - <?= $y+1 ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="listado.php" class="btn btn-secondary w-100">Limpiar</a>
                </div>
                <div class="col-md-3 text-end">
                    <a href="inscripcion.php" class="btn btn-primary w-100">+ Nueva Inscripción</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Buscador rápido -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Buscar:</label>
                    <input type="text" id="buscadorTabla" class="form-control" placeholder="Nombre, cédula o representante...">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="tablaEstudiantes">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Cédula Escolar</th>
                            <th>Sala</th>
                            <th>Representante</th>
                            <th>Año Escolar</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaBody">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($e = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?></td>
                                <td><?= htmlspecialchars($e['cedula_escolar']) ?></td>
                                <td><?= htmlspecialchars($e['sala']) ?></td>
                                <td><?= htmlspecialchars($e['rep_nombre'] ?? 'No asignado') ?></td>
                                <td><?= htmlspecialchars($e['ano_escolar_actual'] ?? 'No registrado') ?></td>
                                <td class="text-nowrap">
                                    <a href="ver_ficha.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info" target="_blank">Ver Ficha</a>
                                    <a href="editar_estudiantes.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar?')">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                 </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No hay estudiantes registrados.<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('filtro_sala').addEventListener('change', function() {
        document.getElementById('filtroForm').submit();
    });
    // Buscador (filtro en tabla)
    document.getElementById('buscadorTabla').addEventListener('input', function() {
        let filter = this.value.toUpperCase();
        let rows = document.querySelectorAll('#tablaBody tr');
        rows.forEach(row => {
            let text = row.innerText.toUpperCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>