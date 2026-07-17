<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'admin', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Eliminación (baja lógica)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error CSRF");
    }
    $id = intval($_POST['id']);

    $stmt_nombre = $conexion->prepare("SELECT nombre, apellido FROM estudiantes WHERE id = ?");
    $stmt_nombre->bind_param("i", $id);
    $stmt_nombre->execute();
    $result_nombre = $stmt_nombre->get_result();
    $estudiante = $result_nombre->fetch_assoc();
    $stmt_nombre->close();
    $nombre_estudiante = $estudiante ? $estudiante['nombre'] . ' ' . $estudiante['apellido'] : 'ID: ' . $id;

    $stmt = $conexion->prepare("UPDATE estudiantes SET estatus = 'Inactivo' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    if ($usuario_id > 0) {
        registrarAuditoria($conexion, $usuario_id, 'ELIMINAR_ESTUDIANTE', 'estudiantes', $id, "Baja lógica (estatus -> Inactivo) del estudiante: $nombre_estudiante");
    }

    header("Location: listado.php?msg=deleted");
    exit();
}

// ========== FILTROS Y PAGINACIÓN ==========
$sala_filtro = isset($_GET['sala']) ? trim($_GET['sala']) : '';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Consulta con JOIN para obtener sección y profesor
$sql_count = "
    SELECT COUNT(DISTINCT e.id) as total 
    FROM estudiantes e
    WHERE e.estatus = 'Activo'
      AND EXISTS (SELECT 1 FROM inscripciones i WHERE i.estudiante_id = e.id)
";

$sql = "
    SELECT DISTINCT e.*, 
           r.nombre_completo AS rep_nombre,
           s.nombre AS seccion_nombre,
           p.nombre AS profesor_nombre,
           (SELECT ano_escolar FROM inscripciones WHERE estudiante_id = e.id ORDER BY fecha_inscripcion DESC LIMIT 1) AS ano_escolar_actual
    FROM estudiantes e
    LEFT JOIN representantes r ON e.representante_id = r.id
    LEFT JOIN secciones s ON e.seccion_id = s.id
    LEFT JOIN profesores p ON p.seccion = s.id AND p.estatus = 'Activo'
    WHERE e.estatus = 'Activo'
      AND EXISTS (SELECT 1 FROM inscripciones i WHERE i.estudiante_id = e.id)
";

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

$total_registros = $conexion->query($sql_count)->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

$sql .= " ORDER BY e.sala, e.nombre, e.apellido LIMIT $offset, $registros_por_pagina";
$result = $conexion->query($sql);
$salas = $conexion->query("SELECT DISTINCT sala FROM secciones ORDER BY sala");

include '../includes/header.php';
?>

<!-- ===== ESTILOS MODERNOS (como egresados.php) ===== -->
<style>
    :root { --navy: #002d54; --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%); }
    .page-header {
        background: var(--primary-gradient);
        color: white;
        border-radius: 12px;
        padding: 20px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,45,84,0.2);
    }
    .card { border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 24px; }
    .card-header { background: var(--primary-gradient) !important; color: white; border-radius: 12px 12px 0 0 !important; padding: 14px 20px; font-weight: 600; }
    .table-estudiantes { font-size: 0.875rem; vertical-align: middle; }
    .table-estudiantes thead th { background-color: #f0f4f8; color: var(--navy); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; border-bottom: 2px solid #002d54; }
    .table-estudiantes tbody tr:hover { background-color: #e8f4f8; }
    .badge-sala { background-color: #e9ecef; color: var(--navy); font-weight: 600; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; }
    .btn-filtro { background: var(--primary-gradient); border: none; color: white; font-weight: 500; }
    .btn-filtro:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,45,84,0.3); color: white; }
    .btn-limpiar { background: white; border: 1.5px solid #6c757d; color: #6c757d; font-weight: 500; }
    .btn-limpiar:hover { background: #6c757d; color: white; }
    .pagination .page-link { border-radius: 8px; margin: 0 3px; color: var(--navy); font-weight: 500; }
    .pagination .page-item.active .page-link { background: var(--primary-gradient); border-color: transparent; }
    .estado-vacio { padding: 50px 20px; text-align: center; color: #6c757d; }
    .estado-vacio i { font-size: 3rem; color: #adb5bd; margin-bottom: 15px; }
</style>

<div class="container-fluid px-4">
    
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-user-graduate me-2"></i> Listado de Estudiantes</h4>
            <small class="opacity-75"><i class="fas fa-check-circle me-1"></i> Solo estudiantes con inscripción completada</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="inscripcion.php" class="btn btn-light fw-bold"><i class="fas fa-user-plus me-2"></i> Nueva Inscripción</a>
            <a href="index.php" class="btn btn-light fw-bold"><i class="fas fa-arrow-left me-2"></i> Volver</a>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show">Estudiante eliminado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- ===== FILTRO CON BUSCADOR EN TIEMPO REAL (AJAX sin perder foco) ===== -->
    <div class="card">
        <div class="card-body p-4">
            <form method="GET" action="listado.php" class="row g-3 align-items-end" id="filtroForm" autocomplete="off">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted"><i class="fas fa-graduation-cap"></i> Sala / Grado</label>
                    <select name="sala" id="filtro-sala" class="form-select shadow-none" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <?php while($row = $salas->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['sala']) ?>" <?= ($sala_filtro == $row['sala']) ? 'selected' : '' ?>><?= ucfirst($row['sala']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold text-muted"><i class="fas fa-search"></i> Buscar estudiante</label>
                    <input type="text" name="busqueda" id="filtro-busqueda" class="form-control shadow-none" placeholder="Nombre, apellido o cédula..." value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-4 text-end">
                    <!-- Sin botones, el filtro es automático -->
                </div>
            </form>
        </div>
    </div>

    <!-- ===== TABLA MODERNA ===== -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Registro de Estudiantes <span class="badge bg-light text-dark ms-2"><?= $total_registros ?> registro(s)</span></h6>
        </div>
        <div class="card-body p-0">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-estudiantes mb-0">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Cédula Escolar</th>
                                <th>Sala</th>
                                <th>Sección</th>
                                <th>Profesor</th>
                                <th>Representante</th>
                                <th>Año Escolar</th>
                                <th style="width: 15%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-estudiantes-body">
                            <?php while($e = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?></strong></td>
                                <td><span class="font-monospace"><?= htmlspecialchars($e['cedula_escolar']) ?></span></td>
                                <td><span class="badge-sala"><?= htmlspecialchars($e['sala']) ?></span></td>
                                <td><?= htmlspecialchars($e['seccion_nombre'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($e['profesor_nombre'] ?? 'Sin asignar') ?></td>
                                <td><?= htmlspecialchars($e['rep_nombre'] ?? 'No asignado') ?></td>
                                <td><?= htmlspecialchars($e['ano_escolar_actual'] ?? 'No registrado') ?></td>
                                <td class="text-nowrap">
                                    <a href="ver_ficha.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info" target="_blank" title="Ver ficha">
                                        <i class="fas fa-id-card"></i>
                                    </a>
                                    <a href="editar_estudiantes.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este estudiante?')">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_paginas > 1): ?>
                    <div class="d-flex justify-content-center py-3 border-top">
                        <nav><ul class="pagination mb-0">
                            <?php $query_params = $_GET; unset($query_params['pagina']); $base_url = 'listado.php?' . http_build_query($query_params); if (!empty($query_params)) $base_url .= '&'; ?>
                            <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="<?= $base_url ?>pagina=<?= $pagina_actual - 1 ?>"><i class="fas fa-chevron-left"></i></a></li>
                            <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                                <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>"><a class="page-link" href="<?= $base_url ?>pagina=<?= $i ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>"><a class="page-link" href="<?= $base_url ?>pagina=<?= $pagina_actual + 1 ?>"><i class="fas fa-chevron-right"></i></a></li>
                        </ul></nav>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="estado-vacio"><i class="fas fa-inbox"></i><h5 class="fw-bold">No se encontraron estudiantes</h5><p class="mb-0"><?= (!empty($sala_filtro) || !empty($busqueda)) ? 'No hay estudiantes que coincidan con los filtros aplicados. <a href="listado.php" class="text-primary">Limpiar filtros</a>' : 'Aún no hay estudiantes inscritos.' ?></p></div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-clock me-1"></i> Última actualización: <?= date('d/m/Y H:i:s') ?></span>
            <span class="text-muted small">Mostrando <?= min($registros_por_pagina, $total_registros - $offset) ?> de <?= $total_registros ?> estudiantes</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const busquedaInput = document.getElementById('filtro-busqueda');
    const salaSelect = document.getElementById('filtro-sala');
    let timeoutId = null;

    function realizarBusqueda() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(function() {
            const termino = busquedaInput.value.trim();
            const sala = salaSelect.value;
            // Redirigir con los parámetros actuales sin perder el foco
            let url = 'listado.php?';
            if (sala) url += 'sala=' + encodeURIComponent(sala) + '&';
            if (termino) url += 'busqueda=' + encodeURIComponent(termino);
            // Conservar la página actual si existe
            const paginaActual = new URLSearchParams(window.location.search).get('pagina');
            if (paginaActual) url += '&pagina=' + paginaActual;
            window.location.href = url;
        }, 400); // Espera 400ms después de la última pulsación
    }

    busquedaInput.addEventListener('input', realizarBusqueda);
});
</script>

<?php include '../includes/footer.php'; ?>