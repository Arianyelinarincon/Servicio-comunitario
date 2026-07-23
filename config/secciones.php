<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once 'conexion.php';
include '../includes/header.php';

// ========== PROCESAR ACCIONES ==========
$mensaje = '';
$tipo_mensaje = '';

// Agregar sección
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar') {
    $sala = trim($_POST['sala']);
    $nombre = strtoupper(trim($_POST['nombre']));
    
    if (empty($sala) || empty($nombre)) {
        $mensaje = 'Sala y nombre son obligatorios.';
        $tipo_mensaje = 'danger';
    } else {
        $stmt = $conexion->prepare("INSERT INTO secciones (sala, nombre) VALUES (?, ?)");
        $stmt->bind_param("ss", $sala, $nombre);
        if ($stmt->execute()) {
            $mensaje = 'Sección agregada correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al agregar: ' . $conexion->error;
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// Eliminar sección
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar') {
    $id = intval($_POST['id']);
    $check = $conexion->prepare("SELECT COUNT(*) as total FROM estudiantes WHERE seccion_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $estudiantes = $check->get_result()->fetch_assoc()['total'];
    $check->close();
    
    if ($estudiantes > 0) {
        $mensaje = 'No se puede eliminar la sección porque tiene estudiantes asignados.';
        $tipo_mensaje = 'danger';
    } else {
        $stmt = $conexion->prepare("DELETE FROM secciones WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensaje = 'Sección eliminada correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar: ' . $conexion->error;
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// ========== OBTENER LISTADO ==========
$sql = "SELECT * FROM secciones ORDER BY sala, nombre";
$result = $conexion->query($sql);
$secciones = [];
while ($row = $result->fetch_assoc()) {
    $secciones[] = $row;
}
?>

<style>
    .page-header { background: linear-gradient(135deg, #002d54 0%, #004a7c 100%); color: white; border-radius: 12px; padding: 20px 28px; margin-bottom: 24px; }
    .card-secciones { border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
    .card-secciones .card-header { background: linear-gradient(135deg, #002d54 0%, #004a7c 100%) !important; color: white; border-radius: 12px 12px 0 0 !important; padding: 14px 20px; font-weight: 600; }
    .table-secciones { font-size: 0.9rem; vertical-align: middle; }
    .table-secciones thead th { background-color: #f0f4f8; color: #002d54; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #002d54; }
    .badge-sala { background-color: #e9ecef; color: #002d54; font-weight: 600; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; }
</style>

<div class="container-fluid px-4">
    
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-layer-group me-2"></i> Administrar Secciones</h4>
            <small class="opacity-75">Gestiona las secciones disponibles para cada sala/grado</small>
        </div>
        <div>
            <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#modalAgregarSeccion">
                <i class="fas fa-plus-circle me-2"></i> Nueva Sección
            </button>
            <a href="/servicio-comunitario/index.php" class="btn btn-light fw-bold ms-2">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show"><?= $mensaje ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card card-secciones">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Secciones Registradas</h6>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($secciones)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-secciones mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sala / Grado</th>
                            <th>Sección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($secciones as $sec): ?>
                        <tr>
                            <td><?= $sec['id'] ?></td>
                            <td><span class="badge-sala"><?= htmlspecialchars($sec['sala']) ?></span></td>
                            <td><strong><?= htmlspecialchars($sec['nombre']) ?></strong></td>
                            <td>
                                <?php if ($sec['nombre'] !== 'U'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta sección? Se eliminarán todos los registros asociados.')">
                                    <input type="hidden" name="action" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $sec['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted"><i class="fas fa-lock"></i> Sección por defecto</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted">No hay secciones registradas.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Agregar Sección -->
<div class="modal fade" id="modalAgregarSeccion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Nueva Sección</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="agregar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sala / Grado <span class="text-danger">*</span></label>
                        <select name="sala" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="sala4">Sala 4 Años</option>
                            <option value="sala5">Sala 5 Años</option>
                            <option value="1ro">1er Grado</option>
                            <option value="2do">2do Grado</option>
                            <option value="3ro">3er Grado</option>
                            <option value="4to">4to Grado</option>
                            <option value="5to">5to Grado</option>
                            <option value="6to">6to Grado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la Sección <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control text-uppercase" placeholder="Ej: A, B, C, D" required maxlength="10">
                        <small class="text-muted">Ejemplos: A, B, C, D, Única, etc.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar Sección</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>