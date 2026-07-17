<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

// ========== VERIFICAR AUTENTICACIÓN ==========
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'super_admin' && $_SESSION['rol'] !== 'admin')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

include('../includes/header.php');
?>

<style>
    .table-profesores { font-size: 0.9rem; }
    .table-profesores th { background-color: #f8f9fa; color: #002d54; }
    .btn-accion { margin: 2px; }
    .badge-estado {
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .badge-activo { background-color: #28a745; color: white; }
    .badge-inactivo { background-color: #dc3545; color: white; }
    .dashboard-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.12);
    }
    .icon-box { font-size: 2rem; margin-bottom: 10px; }
    .bg-navy { background-color: #002d54 !important; }
    .btn-navy { background-color: #002d54; color: white; }
    .btn-navy:hover { background-color: #004080; color: white; }
</style>

<div class="container-fluid mt-4">
    <!-- ===== CABECERA ===== -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-users me-2"></i>Gestionar Profesores</h2>
            <p class="text-muted">Listado completo de docentes (activos e inactivos)</p>
        </div>
        <div>
            <a href="agregar_profesor.php" class="btn btn-success">
                <i class="fas fa-user-plus me-2"></i> Agregar Profesor
            </a>
            <a href="panel_profesor.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
        </div>
    </div>

    <!-- ===== MENSAJES ===== -->
    <?php
    if (isset($_GET['msg'])) {
        $msg = $_GET['msg'];
        if ($msg === 'added') echo '<div class="alert alert-success alert-dismissible fade show">✅ Profesor agregado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        elseif ($msg === 'updated') echo '<div class="alert alert-success alert-dismissible fade show">✅ Profesor actualizado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        elseif ($msg === 'deleted') echo '<div class="alert alert-success alert-dismissible fade show">🗑️ Profesor eliminado permanentemente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        elseif ($msg === 'error') echo '<div class="alert alert-danger alert-dismissible fade show">❌ Ocurrió un error al procesar la solicitud.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    ?>

    <!-- ===== TARJETA DE BÚSQUEDA (Opcional) ===== -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Buscar por nombre o cédula</label>
                    <input type="text" name="buscar" class="form-control" placeholder="Nombre, apellido o cédula..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="Activo" <?= isset($_GET['estado']) && $_GET['estado'] === 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Inactivo" <?= isset($_GET['estado']) && $_GET['estado'] === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-navy w-100"><i class="fas fa-search me-2"></i>Buscar</button>
                </div>
                <div class="col-md-3">
                    <a href="gestionar_profesores.php" class="btn btn-secondary w-100"><i class="fas fa-undo me-2"></i>Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== TABLA DE PROFESORES ===== -->
    <div class="card shadow-sm">
        <div class="card-header bg-navy text-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i> Lista de Profesores</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-profesores mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Sala</th>
                            <th>Sección</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ========== CONSULTA: TODOS LOS PROFESORES ==========
                        $sql = "SELECT p.*, s.nombre AS seccion_nombre 
                                FROM profesores p
                                LEFT JOIN secciones s ON p.seccion = s.id
                                WHERE 1=1";
                        $params = [];
                        $types = "";

                        if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
                            $buscar = '%' . $_GET['buscar'] . '%';
                            $sql .= " AND (p.nombre LIKE ? OR p.apellido LIKE ? OR p.cedula LIKE ?)";
                            $params[] = $buscar;
                            $params[] = $buscar;
                            $params[] = $buscar;
                            $types .= "sss";
                        }
                        if (isset($_GET['estado']) && !empty($_GET['estado'])) {
                            $sql .= " AND p.estatus = ?";
                            $params[] = $_GET['estado'];
                            $types .= "s";
                        }

                        $sql .= " ORDER BY p.estatus DESC, p.nombre ASC";

                        $stmt = $conexion->prepare($sql);
                        if (!empty($params)) {
                            $stmt->bind_param($types, ...$params);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result && $result->num_rows > 0):
                            while($row = $result->fetch_assoc()):
                                $rol_label = '';
                                if ($row['rol'] === 'super_admin') $rol_label = '<span class="badge bg-danger">Super Admin</span>';
                                elseif ($row['rol'] === 'administrador') $rol_label = '<span class="badge bg-primary">Admin</span>';
                                else $rol_label = '<span class="badge bg-secondary">Profesor</span>';

                                $estado_clase = ($row['estatus'] === 'Activo') ? 'badge-activo' : 'badge-inactivo';
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['cedula'] ?? '-') ?></td>
                            <td><strong><?= htmlspecialchars($row['nombre'] ?? '-') ?></strong></td>
                            <td><?= htmlspecialchars($row['apellido'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['sala'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['seccion_nombre'] ?? 'Sin asignar') ?></td>
                            <td>
                                <span class="badge badge-estado <?= $estado_clase ?>"><?= $row['estatus'] ?></span>
                                <?= $rol_label ?>
                            </td>
                            <td class="text-nowrap">
                                <!-- Editar -->
                                <a href="editar_profesor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary btn-accion" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <!-- Ver Detalle -->
                                <a href="detalle_profesor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info btn-accion" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <!-- Ver Estudiantes (NUEVO) -->
                                <a href="../estudiantes/listado.php?sala=<?= urlencode($row['sala']) ?>&seccion=<?= $row['seccion'] ?>" class="btn btn-sm btn-success btn-accion" title="Ver estudiantes asignados" target="_blank">
                                    <i class="fas fa-user-graduate"></i>
                                </a>
                                <!-- Eliminar (Físico) -->
                                <?php if ($_SESSION['rol'] === 'super_admin' || $_SESSION['rol'] === 'administrador'): ?>
                                <button class="btn btn-sm btn-danger btn-accion" onclick="eliminarProfesor(<?= $row['id'] ?>, '<?= addslashes($row['nombre'] . ' ' . $row['apellido']) ?>')" title="Eliminar permanentemente">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center py-4">No hay profesores registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            <i class="fas fa-info-circle me-1"></i> Los profesores inactivos aparecen con fondo gris. Al eliminar, se borran permanentemente.
        </div>
    </div>
</div>

<!-- ===== MODAL CONFIRMAR ELIMINACIÓN ===== -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de <strong>eliminar permanentemente</strong> al profesor <span id="nombre-profesor-eliminar" class="fw-bold"></span>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Esta acción es irreversible.</strong> Se eliminarán todos los datos asociados a este profesor (incluyendo auditoría).
                </div>
            </div>
            <div class="modal-footer">
                <form action="eliminar_profesor.php" method="POST" id="form-eliminar">
                    <input type="hidden" name="id" id="eliminar-id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt me-2"></i>Sí, eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function eliminarProfesor(id, nombre) {
    document.getElementById('nombre-profesor-eliminar').textContent = nombre;
    document.getElementById('eliminar-id').value = id;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include('../includes/footer.php'); ?>