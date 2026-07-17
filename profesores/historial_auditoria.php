<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'super_admin') {
    header("Location: /servicio-comunitario/index.php");
    exit();
}
include '../includes/header.php';
require_once '../config/conexion.php';

// Paginación
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Filtros
$filtro_accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';
$filtro_usuario = isset($_GET['usuario']) ? intval($_GET['usuario']) : 0;

// Consulta base
$sql = "SELECT a.*, u.nombre AS usuario_nombre 
        FROM auditoria a
        LEFT JOIN secretaria u ON a.usuario_id = u.id
        WHERE 1=1";
$params = [];
$types = "";

if ($filtro_accion) {
    $sql .= " AND a.accion LIKE ?";
    $params[] = "%$filtro_accion%";
    $types .= "s";
}
if ($filtro_usuario > 0) {
    $sql .= " AND a.usuario_id = ?";
    $params[] = $filtro_usuario;
    $types .= "i";
}

$sql_count = "SELECT COUNT(*) as total FROM auditoria a WHERE 1=1";
if ($filtro_accion) {
    $sql_count .= " AND a.accion LIKE ?";
}
if ($filtro_usuario > 0) {
    $sql_count .= " AND a.usuario_id = ?";
}

// Ejecutar conteo
$stmt = $conexion->prepare($sql_count);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_registros = $stmt->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Ejecutar consulta principal
$sql .= " ORDER BY a.fecha DESC LIMIT ? OFFSET ?";
$params[] = $registros_por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Obtener lista de usuarios para filtro
$usuarios = $conexion->query("SELECT id, nombre FROM secretaria ORDER BY nombre");
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-history me-2"></i>Historial de Cambios</h2>
        <a href="gestionar_permisos.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver a Seguridad
        </a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Acción</label>
                    <input type="text" name="accion" class="form-control" placeholder="Buscar acción..." value="<?= htmlspecialchars($filtro_accion) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Usuario</label>
                    <select name="usuario" class="form-select">
                        <option value="0">Todos</option>
                        <?php while($u = $usuarios->fetch_assoc()): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filtro_usuario == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
                <div class="col-md-2">
                    <a href="historial_auditoria.php" class="btn btn-secondary w-100">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Tabla</th>
                            <th>Registro ID</th>
                            <th>Detalles</th>
                            <th>IP</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): 
                            $contador = $offset + 1;
                            while($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= $contador++ ?></td>
                                <td><?= htmlspecialchars($row['usuario_nombre'] ?? 'Sistema') ?></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($row['accion']) ?></span></td>
                                <td><?= htmlspecialchars($row['tabla_afectada'] ?? 'N/A') ?></td>
                                <td><?= $row['registro_id'] ?? '-' ?></td>
                                <td style="max-width: 250px;"><?= htmlspecialchars($row['detalles'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['ip'] ?? '') ?></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($row['fecha'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No hay registros de auditoría.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_paginas > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>