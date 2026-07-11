<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';
require_once "config_db.php";

// Filtros
$periodo_filtro = $_GET['periodo'] ?? '';
$grado_filtro = $_GET['grado'] ?? '';
$motivo_filtro = $_GET['motivo'] ?? '';

// Construir consulta con filtros
$sql = "
    SELECT e.*, em.apellido, em.nombre, em.ci, em.fecha_nacimiento, em.nacionalidad,
           s.nombre as seccion_nombre
    FROM egresos e
    JOIN estudiantes_matricula em ON e.estudiante_id = em.id
    LEFT JOIN secciones s ON e.seccion_id = s.id
    WHERE em.estado = 'egresado'
";

$params = [];
$tipos = "";

if ($periodo_filtro) {
    $sql .= " AND DATE_FORMAT(e.fecha_egreso, '%Y-%m') = ?";
    $params[] = $periodo_filtro;
    $tipos .= "s";
}

if ($grado_filtro) {
    $sql .= " AND e.sala = ?";
    $params[] = $grado_filtro;
    $tipos .= "s";
}

if ($motivo_filtro) {
    $sql .= " AND e.motivo = ?";
    $params[] = $motivo_filtro;
    $tipos .= "s";
}

$sql .= " ORDER BY e.fecha_egreso DESC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$egresados = $stmt->get_result();
$stmt->close();

// Obtener motivos únicos para el filtro
$stmt_motivos = $conexion->prepare("SELECT DISTINCT motivo FROM egresos WHERE motivo IS NOT NULL AND motivo != '' ORDER BY motivo");
$stmt_motivos->execute();
$motivos = $stmt_motivos->get_result();
$stmt_motivos->close();

// Obtener grados para el filtro
$stmt_grados = $conexion->prepare("SELECT DISTINCT sala FROM egresos ORDER BY sala");
$stmt_grados->execute();
$grados = $stmt_grados->get_result();
$stmt_grados->close();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-warning">
                <i class="fas fa-user-times"></i> Historial de Estudiantes Egresados
            </h2>
            <p class="text-muted">Registro de estudiantes que han salido de la institución</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Período</label>
                    <input type="month" name="periodo" class="form-control" value="<?= htmlspecialchars($periodo_filtro) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grado</label>
                    <select name="grado" class="form-select">
                        <option value="">Todos los grados</option>
                        <?php while($g = $grados->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($g['sala']) ?>" <?= $grado_filtro == $g['sala'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['sala']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Motivo</label>
                    <select name="motivo" class="form-select">
                        <option value="">Todos los motivos</option>
                        <?php while($m = $motivos->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($m['motivo']) ?>" <?= $motivo_filtro == $m['motivo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['motivo']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="egresados.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Egresados -->
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Listado de Egresos</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Estudiante</th>
                            <th>CI</th>
                            <th>Género</th>
                            <th>Grado/Sección</th>
                            <th>F. Nacimiento</th>
                            <th>F. Egreso</th>
                            <th>Motivo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($egresados->num_rows > 0): 
                            $contador = 1;
                            while($egr = $egresados->fetch_assoc()): ?>
                            <tr>
                                <td><?= $contador++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($egr['apellido'] . ' ' . $egr['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </td>
                                <td><?= htmlspecialchars($egr['ci'] ?? 'No registrada', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge <?= $egr['genero'] === 'V' ? 'bg-primary' : 'bg-danger' ?>">
                                        <?= $egr['genero'] === 'V' ? '👦 Varón' : '👧 Hembra' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($egr['sala'] . ' - ' . ($egr['seccion_nombre'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $egr['fecha_nacimiento'] ? date('d/m/Y', strtotime($egr['fecha_nacimiento'])) : 'No registrada' ?></td>
                                <td>
                                    <span class="badge bg-dark">
                                        <?= date('d/m/Y', strtotime($egr['fecha_egreso'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($egr['motivo'] ?? 'No especificado', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" title="Ver detalles" 
                                            onclick="verDetalles(<?= $egr['estudiante_id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" title="Reincorporar" 
                                            onclick="reincorporarEstudiante(<?= $egr['estudiante_id'] ?>)">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; 
                        else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                                    <p>No se encontraron egresos con los filtros seleccionados</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalles(id) {
    window.location.href = `ver_estudiante.php?id=${id}`;
}

function reincorporarEstudiante(id) {
    if (confirm('¿Está seguro de reincorporar a este estudiante? Volverá a estar activo en el sistema.')) {
        fetch('reincorporar_estudiante.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Estudiante reincorporado exitosamente');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al reincorporar estudiante');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>