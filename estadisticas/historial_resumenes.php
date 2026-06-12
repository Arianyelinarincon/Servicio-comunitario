<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';

// Filtros
$periodo_filtro = $_GET['periodo'] ?? '';
$sala_filtro = $_GET['sala'] ?? '';
$docente_filtro = $_GET['docente'] ?? '';

// Paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Consulta para contar total
$sql_count = "SELECT COUNT(*) as total FROM resumen_estadistico WHERE 1=1";
if ($periodo_filtro) {
    $sql_count .= " AND DATE_FORMAT(periodo, '%Y-%m') = '" . mysqli_real_escape_string($conexion, $periodo_filtro) . "'";
}
if ($sala_filtro) {
    $sql_count .= " AND sala = '" . mysqli_real_escape_string($conexion, $sala_filtro) . "'";
}
if ($docente_filtro) {
    $sql_count .= " AND docente_id = " . intval($docente_filtro);
}
$total_registros = $conexion->query($sql_count)->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta principal con joins
$sql = "SELECT r.*, p.nombre AS docente_nombre, s.nombre AS seccion_nombre
        FROM resumen_estadistico r
        LEFT JOIN profesores p ON r.docente_id = p.id
        LEFT JOIN secciones s ON r.seccion_id = s.id
        WHERE 1=1";
if ($periodo_filtro) {
    $sql .= " AND DATE_FORMAT(r.periodo, '%Y-%m') = '" . mysqli_real_escape_string($conexion, $periodo_filtro) . "'";
}
if ($sala_filtro) {
    $sql .= " AND r.sala = '" . mysqli_real_escape_string($conexion, $sala_filtro) . "'";
}
if ($docente_filtro) {
    $sql .= " AND r.docente_id = " . intval($docente_filtro);
}
$sql .= " ORDER BY r.periodo DESC, r.sala, r.seccion_id LIMIT $offset, $registros_por_pagina";
$result = $conexion->query($sql);

// Obtener listas para filtros (usando valores existentes en la tabla)
$salas = $conexion->query("SELECT DISTINCT sala FROM resumen_estadistico ORDER BY sala");
$docentes = $conexion->query("SELECT DISTINCT p.id, p.nombre 
                              FROM resumen_estadistico r 
                              JOIN profesores p ON r.docente_id = p.id 
                              ORDER BY p.nombre");

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <h2 class="fw-bold mb-4"><i class="fas fa-chart-line"></i> Historial de Resúmenes Estadísticos</h2>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Mes / Año</label>
                    <input type="month" name="periodo" class="form-control" value="<?= htmlspecialchars($periodo_filtro) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sala / Grado</label>
                    <select name="sala" class="form-select">
                        <option value="">Todas</option>
                        <?php while($row = $salas->fetch_assoc()): ?>
                            <option value="<?= $row['sala'] ?>" <?= ($sala_filtro == $row['sala']) ? 'selected' : '' ?>><?= ucfirst($row['sala']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Docente</label>
                    <select name="docente" class="form-select">
                        <option value="">Todos</option>
                        <?php while($row = $docentes->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= ($docente_filtro == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
                </div>
                <div class="col-md-12 text-end">
                    <a href="historial_resumenes.php" class="btn btn-secondary btn-sm">Limpiar filtros</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de resultados -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Mes / Año</th>
                            <th>Sala</th>
                            <th>Sección</th>
                            <th>Docente</th>
                            <th>Matrícula</th>
                            <th>Total Asistencia</th>
                            <th>% Asistencia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('F Y', strtotime($row['periodo'])) ?> (<?= $row['periodo'] ?>)</td>
                                <td><?= ucfirst($row['sala']) ?></td>
                                <td><?= htmlspecialchars($row['seccion_nombre'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['docente_nombre'] ?? 'N/A') ?></td>
                                <td>V: <?= $row['matricula_v'] ?> / H: <?= $row['matricula_h'] ?> / Total: <?= $row['matricula_v'] + $row['matricula_h'] ?></td>
                                <td>V: <?= $row['total_asistencia_v'] ?> / H: <?= $row['total_asistencia_h'] ?> / Total: <?= $row['total_asistencia_v'] + $row['total_asistencia_h'] ?></td>
                                <td><strong><?= $row['porcentaje_asistencia'] ?>%</strong></td>
                                <td class="text-nowrap">
                                    <a href="ver_resumen.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" target="_blank">Ver Detalle</a>
                                    <a href="generar_pdf.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">Regenerar PDF</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No hay resúmenes guardados.<?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Paginación -->
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