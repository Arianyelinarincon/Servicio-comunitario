<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';
require_once '../config/conexion.php';

// ========== FILTROS CON BUSCADOR EN TIEMPO REAL ==========
$buscar_estudiante = trim($_GET['estudiante'] ?? '');
$periodo = trim($_GET['periodo'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');

// ========== CONSULTA PRINCIPAL ==========
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

// ========== OBTENER PERÍODOS DISPONIBLES PARA FILTRO ==========
$periodos_disponibles = [];
$res_periodos = $conexion->query("SELECT DISTINCT periodo FROM boletines ORDER BY periodo DESC");
while ($row = $res_periodos->fetch_assoc()) {
    $periodos_disponibles[] = $row['periodo'];
}
?>

<style>
    :root { --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%); }
    .page-header {
        background: var(--primary-gradient);
        color: white;
        border-radius: 12px;
        padding: 20px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,45,84,0.2);
    }
    .card-filtros {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 24px;
    }
    .card-tabla {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .card-tabla .card-header {
        background: var(--primary-gradient) !important;
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 14px 20px;
        font-weight: 600;
    }
    .table-boletines {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .table-boletines thead th {
        background-color: #f0f4f8;
        color: #002d54;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #002d54;
    }
    .table-boletines tbody tr:hover {
        background-color: #e8f4f8;
    }
    .badge-tipo {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-inicial { background-color: #cce5ff; color: #004085; }
    .badge-primaria { background-color: #d4edda; color: #155724; }
    .btn-accion {
        margin: 2px;
        padding: 4px 10px;
        font-size: 0.78rem;
        border-radius: 6px;
    }
    .pagination .page-link {
        border-radius: 8px;
        margin: 0 3px;
        color: #002d54;
        font-weight: 500;
    }
    .pagination .page-item.active .page-link {
        background: var(--primary-gradient);
        border-color: transparent;
    }
    /* Buscador en tiempo real */
    #busquedaInput {
        transition: all 0.3s ease;
    }
    #busquedaInput:focus {
        border-color: #002d54;
        box-shadow: 0 0 0 3px rgba(0,45,84,0.15);
    }
</style>

<div class="container-fluid px-4">
    
    <!-- Cabecera -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-history me-2"></i> Historial de Boletines</h4>
            <small class="opacity-75"><i class="fas fa-file-alt me-1"></i> Consulta y administra los boletines generados</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="index.php" class="btn btn-light fw-bold"><i class="fas fa-arrow-left me-2"></i> Volver</a>
        </div>
    </div>

    <!-- Filtros con buscador en tiempo real (sin botones) -->
    <div class="card card-filtros">
        <div class="card-body p-4">
            <form method="GET" action="historial_boletines.php" id="filtroForm" autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted"><i class="fas fa-user-graduate me-1"></i> Buscar estudiante / C.E</label>
                        <input type="text" name="estudiante" id="busquedaInput" class="form-control shadow-none" placeholder="Nombre, apellido o cédula escolar..." value="<?= htmlspecialchars($buscar_estudiante) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted"><i class="fas fa-calendar-alt me-1"></i> Período Escolar</label>
                        <select name="periodo" class="form-select shadow-none" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <?php foreach ($periodos_disponibles as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= ($periodo == $p) ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted"><i class="fas fa-tag me-1"></i> Tipo de Boletín</label>
                        <select name="tipo" class="form-select shadow-none" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="inicial" <?= ($tipo == 'inicial') ? 'selected' : '' ?>>Inicial</option>
                            <option value="primaria" <?= ($tipo == 'primaria') ? 'selected' : '' ?>>Primaria</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> Filtro automático</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de boletines -->
    <div class="card card-tabla">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Registro de Boletines <span class="badge bg-light text-dark ms-2"><?= $result->num_rows ?> boletín(es)</span></h6>
            <small class="opacity-75"><i class="fas fa-clock me-1"></i> Filtro automático</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-boletines mb-0">
                    <thead>
                        <tr>
                            <th style="width:20%">Estudiante</th>
                            <th style="width:12%">C.E. Escolar</th>
                            <th style="width:12%">Año Escolar</th>
                            <th style="width:10%">Tipo</th>
                            <th style="width:15%">Fecha Emisión</th>
                            <th style="width:15%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): 
                                $tipo_clase = ($row['tipo_boletin'] == 'inicial') ? 'badge-inicial' : 'badge-primaria';
                                $tipo_nombre = ($row['tipo_boletin'] == 'inicial') ? 'Inicial' : 'Primaria';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nombre_estudiante']) ?></strong></td>
                                <td><span class="font-monospace"><?= htmlspecialchars($row['cedula_escolar'] ?? '') ?></span></td>
                                <td><?= htmlspecialchars($row['periodo']) ?></td>
                                <td><span class="badge-tipo <?= $tipo_clase ?>"><?= $tipo_nombre ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['fecha_emision'])) ?></td>
                                <td class="text-nowrap">
                                    <a href="ver_boletin_guardado.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info btn-accion" target="_blank" title="Ver boletín"><i class="fas fa-eye"></i></a>
                                    <?php if ($row['tipo_boletin'] == 'inicial'): ?>
                                        <a href="panel_boletin_inicial.php?editar_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning btn-accion" title="Editar boletín"><i class="fas fa-edit"></i></a>
                                    <?php else: ?>
                                        <a href="panel_boletin_primaria.php?editar_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning btn-accion" title="Editar boletín"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>No hay boletines guardados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-database me-1"></i> Total: <?= $result->num_rows ?> boletines</span>
            <span class="text-muted small"><i class="fas fa-sync-alt me-1"></i> Filtro automático</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const busquedaInput = document.getElementById('busquedaInput');
    let timeoutId = null;

    if (busquedaInput) {
        busquedaInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                document.getElementById('filtroForm').submit();
            }, 400);
            // Mantener el foco
            this.focus();
        });

        // Mantener el foco después de recargar
        busquedaInput.focus();
        const length = busquedaInput.value.length;
        busquedaInput.setSelectionRange(length, length);
    }
});
</script>

<?php include '../includes/footer.php'; ?>