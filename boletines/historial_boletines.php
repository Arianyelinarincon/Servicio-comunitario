<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';
require_once '../config/conexion.php';

// Obtener filtros
$buscar_estudiante = trim($_GET['estudiante'] ?? '');
$periodo = trim($_GET['periodo'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');

// Construir consulta
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
$sql .= " ORDER BY b.fecha_emision DESC, b.periodo DESC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid mt-4">
    <h2 class="fw-bold mb-4"><i class="fas fa-history"></i> Historial de Boletines</h2>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Estudiante (nombre, apellido o cédula escolar)</label>
                    <input type="text" name="estudiante" class="form-control" value="<?= htmlspecialchars($buscar_estudiante) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Año Escolar</label>
                    <input type="text" name="periodo" class="form-control" placeholder="ej: 2024-2025" value="<?= htmlspecialchars($periodo) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="inicial" <?= $tipo == 'inicial' ? 'selected' : '' ?>>Inicial</option>
                        <option value="primaria" <?= $tipo == 'primaria' ? 'selected' : '' ?>>Primaria</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
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
                            <th>Estudiante</th>
                            <th>Cédula Escolar</th>
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
                                <td><?= htmlspecialchars($row['cedula_escolar']) ?></td>
                                <td><?= htmlspecialchars($row['periodo']) ?></td>
                                <td><?= ucfirst($row['tipo_boletin']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['fecha_emision'])) ?></td>
                                <td><a href="ver_boletin_guardado.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" target="_blank"><i class="fas fa-eye"></i> Ver Boletín</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No hay boletines guardados.<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>