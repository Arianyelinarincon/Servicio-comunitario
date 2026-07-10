<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Eliminación (baja lógica)
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

// Captura de filtros
$sala_filtro = isset($_GET['sala']) ? trim($_GET['sala']) : '';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Base de las consultas
$sql_count = "SELECT COUNT(*) as total FROM estudiantes e WHERE e.estatus = 'Activo'";
$sql = "SELECT e.*, r.nombre_completo AS rep_nombre,
               (SELECT ano_escolar FROM inscripciones WHERE estudiante_id = e.id ORDER BY fecha_inscripcion DESC LIMIT 1) AS ano_escolar_actual
        FROM estudiantes e
        LEFT JOIN representantes r ON e.representante_id = r.id
        WHERE e.estatus = 'Activo'";

// Aplicar filtros SQL
if ($sala_filtro) {
    $sala_esc = mysqli_real_escape_string($conexion, $sala_filtro);
    $sql_count .= " AND e.sala = '$sala_esc'";
    $sql .= " AND e.sala = '$sala_esc'";
}

if ($busqueda) {
    $busqueda_esc = mysqli_real_escape_string($conexion, $busqueda);
    $where_busqueda = " AND (e.nombre LIKE '%$busqueda_esc%' OR e.apellido LIKE '%$busqueda_esc%' OR e.cedula_escolar LIKE '%$busqueda_esc%')";
    $sql_count .= $where_busqueda;
    $sql .= $where_busqueda;
}

// Ejecutar conteo y consulta
$total_registros = $conexion->query($sql_count)->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

$sql .= " ORDER BY e.sala, e.nombre, e.apellido LIMIT $offset, $registros_por_pagina";
$result = $conexion->query($sql);
$salas = $conexion->query("SELECT DISTINCT sala FROM secciones ORDER BY sala");

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h2 class="fw-bold mb-0">Listado de Estudiantes</h2>
            <p class="text-muted">Panel de control para inscripciones y listado de alumnos</p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Estudiantes
        </a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show">Estudiante eliminado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <form method="GET" action="listado.php" class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Sala / Grado</label>
                    <select name="sala" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <?php while($row = $salas->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['sala']) ?>" <?= ($sala_filtro == $row['sala']) ? 'selected' : '' ?>><?= ucfirst($row['sala']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="busqueda" class="form-control" placeholder="Nombre, apellido o cédula..." value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
                <div class="col-md-3">
                    <a href="listado.php" class="btn btn-secondary w-100">Limpiar</a>
                    <a href="inscripcion.php" class="btn btn-success w-100 mt-2">+ Nueva Inscripción</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
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
                    <tbody>
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
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este estudiante?')">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No se encontraron estudiantes.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_paginas > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>