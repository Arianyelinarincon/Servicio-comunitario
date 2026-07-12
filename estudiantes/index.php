<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';
require_once "../estadisticas/config_db.php";

// Obtener estadísticas de egresados
$total_egresados = 0;
$egresados_recientes = [];

$stmt_total = $conexion->prepare("
    SELECT COUNT(*) as total 
    FROM egresos e
    JOIN estudiantes_matricula em ON e.estudiante_id = em.id
    WHERE em.estado = 'egresado'
");
$stmt_total->execute();
$result_total = $stmt_total->get_result();
if ($row = $result_total->fetch_assoc()) {
    $total_egresados = $row['total'];
}
$stmt_total->close();

// Obtener últimos 5 egresados
$stmt_recientes = $conexion->prepare("
    SELECT e.fecha_egreso, e.motivo, em.apellido, em.nombre, em.ci, em.genero,
           s.nombre as seccion_nombre, e.sala
    FROM egresos e
    JOIN estudiantes_matricula em ON e.estudiante_id = em.id
    LEFT JOIN secciones s ON e.seccion_id = s.id
    WHERE em.estado = 'egresado'
    ORDER BY e.fecha_egreso DESC
    LIMIT 5
");
$stmt_recientes->execute();
$result_recientes = $stmt_recientes->get_result();
while ($row = $result_recientes->fetch_assoc()) {
    $egresados_recientes[] = $row;
}
$stmt_recientes->close();

// Obtener egresados por mes (últimos 6 meses)
$egresados_por_mes = [];
$stmt_meses = $conexion->prepare("
    SELECT DATE_FORMAT(e.fecha_egreso, '%Y-%m') as mes,
           COUNT(*) as cantidad
    FROM egresos e
    JOIN estudiantes_matricula em ON e.estudiante_id = em.id
    WHERE em.estado = 'egresado'
    AND e.fecha_egreso >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(e.fecha_egreso, '%Y-%m')
    ORDER BY mes DESC
");
$stmt_meses->execute();
$result_meses = $stmt_meses->get_result();
while ($row = $result_meses->fetch_assoc()) {
    $egresados_por_mes[] = $row;
}
$stmt_meses->close();

$meses_es = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Gestión de Estudiantes</h2>
            <p class="text-muted">Panel de control para inscripciones y listado de alumnos</p>
        </div>
    </div>

    <!-- Tarjetas principales -->
    <div class="row g-4 mb-4">
        <!-- Tarjeta: Ver Estudiantes -->
        <div class="col-md-4">
            <a href="listado.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center h-100">
                    <div class="icon-box text-primary">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Ver Estudiantes</h5>
                    <p class="text-muted">Listado completo de estudiantes inscritos, con filtros y acciones</p>
                </div>
            </a>
        </div>

        <!-- Tarjeta: Inscribir Estudiante -->
        <div class="col-md-4">
            <a href="inscripcion.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center h-100">
                    <div class="icon-box text-success">
                        <i class="fas fa-user-plus fa-3x"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Inscribir Estudiante</h5>
                    <p class="text-muted">Registrar un nuevo estudiante en el sistema</p>
                </div>
            </a>
        </div>

        <!-- Tarjeta: Estudiantes Egresados (NUEVA) -->
        <div class="col-md-4">
            <a href="../estadisticas/egresados.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center h-100 border-warning">
                    <div class="icon-box text-warning">
                        <i class="fas fa-user-times fa-3x"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Estudiantes Egresados</h5>
                    <p class="text-muted">
                        Total de egresos: <span class="badge bg-warning text-dark fs-6"><?= $total_egresados ?></span>
                    </p>
                    <p class="text-muted small">Ver historial de estudiantes que han salido de la institución</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Sección de Estadísticas de Egresos -->
    <div class="row g-4">
        <!-- Tabla de Últimos Egresados -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-sign-out-alt"></i> Últimos Egresos Registrados</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($egresados_recientes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>CI</th>
                                        <th>Grado/Sección</th>
                                        <th>Fecha Egreso</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($egresados_recientes as $egresado): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($egresado['apellido'] . ' ' . $egresado['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?= $egresado['genero'] === 'V' ? '👦 Varón' : '👧 Hembra' ?>
                                                </small>
                                            </td>
                                            <td><?= htmlspecialchars($egresado['ci'] ?? 'No registrada', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($egresado['sala'] . ' - ' . ($egresado['seccion_nombre'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?= date('d/m/Y', strtotime($egresado['fecha_egreso'])) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($egresado['motivo'] ?? 'No especificado', ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <p>No hay egresos registrados recientemente</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($total_egresados > 5): ?>
                    <div class="card-footer text-center">
                        <a href="../estadisticas/egresados.php" class="btn btn-outline-warning btn-sm">
                            Ver todos los egresados (<?= $total_egresados ?>)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumen Mensual de Egresos -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Egresos por Mes</h5>
                </div>
                <div class="card-body">
                    <?php if (count($egresados_por_mes) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($egresados_por_mes as $mes_data): 
                                $mes_num = (int)date('m', strtotime($mes_data['mes'] . '-01'));
                                $anio = date('Y', strtotime($mes_data['mes'] . '-01'));
                                $nombre_mes = $meses_es[$mes_num];
                            ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= $nombre_mes ?></strong>
                                        <small class="text-muted d-block"><?= $anio ?></small>
                                    </div>
                                    <span class="badge bg-info rounded-pill">
                                        <?= $mes_data['cantidad'] ?> egreso(s)
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3 text-muted">
                            <p>No hay datos de egresos en los últimos 6 meses</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Total acumulado: <?= $total_egresados ?> estudiantes egresados
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="listado.php?filtro=activos" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user-check"></i> Ver Activos
                            </a>
                        </div>
                        
                        
                        <div class="col-md-3">
                            <a href="../estadisticas/reporte_egresos.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-file-pdf"></i> Reporte de Egresos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .dashboard-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }
    .icon-box {
        margin-bottom: 15px;
    }
    .card-header {
        border-bottom: 2px solid rgba(0,0,0,0.1);
    }
    .table-hover tbody tr:hover {
        background-color: #fff3cd;
    }
    .badge {
        font-size: 0.85rem;
    }
    .list-group-item {
        border-left: none;
        border-right: none;
    }
    .border-warning {
        border: 2px solid #ffc107 !important;
    }
</style>

<?php include '../includes/footer.php'; ?>