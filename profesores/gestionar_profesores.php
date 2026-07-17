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
    .table-profesores {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .table-profesores thead th {
        background-color: #f0f4f8;
        color: #002d54;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #002d54;
    }
    .table-profesores tbody tr:hover {
        background-color: #e8f4f8;
    }
    .badge-estado {
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .badge-activo { background-color: #28a745; color: white; }
    .badge-inactivo { background-color: #dc3545; color: white; }
    .btn-accion {
        font-size: 0.78rem;
        padding: 4px 10px;
        margin: 1px;
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
</style>

<div class="container-fluid px-4">
    
    <!-- Cabecera -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-users me-2"></i> Gestionar Profesores</h4>
            <small class="opacity-75"><i class="fas fa-user-tie me-1"></i> Listado completo de docentes (activos e inactivos)</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="agregar_profesor.php" class="btn btn-light fw-bold me-2"><i class="fas fa-user-plus me-2"></i> Agregar Profesor</a>
            <a href="panel_profesor.php" class="btn btn-light fw-bold"><i class="fas fa-arrow-left me-2"></i> Volver</a>
        </div>
    </div>

    <!-- Mensajes -->
    <?php
    if (isset($_GET['msg'])) {
        $msg = $_GET['msg'];
        if ($msg === 'added') echo '<div class="alert alert-success alert-dismissible fade show">✅ Profesor agregado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        elseif ($msg === 'updated') echo '<div class="alert alert-success alert-dismissible fade show">✅ Profesor actualizado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        elseif ($msg === 'deleted') echo '<div class="alert alert-success alert-dismissible fade show">🗑️ Profesor eliminado permanentemente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        elseif ($msg === 'error') echo '<div class="alert alert-danger alert-dismissible fade show">❌ Ocurrió un error al procesar la solicitud.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    ?>

    <!-- Filtros con buscador en tiempo real -->
    <div class="card card-filtros">
        <div class="card-body p-4">
            <form method="GET" action="gestionar_profesores.php" id="filtroForm" autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted"><i class="fas fa-search me-1"></i> Buscar por nombre o cédula</label>
                        <input type="text" name="buscar" id="busquedaInput" class="form-control shadow-none" placeholder="Nombre, apellido o cédula..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted"><i class="fas fa-filter me-1"></i> Estado</label>
                        <select name="estado" class="form-select shadow-none" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="Activo" <?= isset($_GET['estado']) && $_GET['estado'] === 'Activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= isset($_GET['estado']) && $_GET['estado'] === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-5 text-end">
                        <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> La búsqueda es automática</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card card-tabla">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Lista de Profesores <span class="badge bg-light text-dark ms-2" id="contador-total">0</span></h6>
            <small class="opacity-75"><i class="fas fa-sync-alt me-1"></i> Actualizado automáticamente</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-profesores mb-0">
                    <thead>
                        <tr>
                            <th style="width:5%">ID</th>
                            <th style="width:10%">Cédula</th>
                            <th style="width:18%">Nombre</th>
                            <th style="width:18%">Apellido</th>
                            <th style="width:10%">Sala</th>
                            <th style="width:8%">Sección</th>
                            <th style="width:12%">Estado</th>
                            <th style="width:19%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-body">
                        <?php
                        // ========== CONSULTA CON FILTROS ==========
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
                            <td class="text-center fw-bold text-muted"><?= $row['id'] ?></td>
                            <td><span class="font-monospace"><?= htmlspecialchars($row['cedula'] ?? '-') ?></span></td>
                            <td><strong><?= htmlspecialchars($row['nombre'] ?? '-') ?></strong></td>
                            <td><?= htmlspecialchars($row['apellido'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['sala'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['seccion_nombre'] ?? 'Sin asignar') ?></td>
                            <td>
                                <span class="badge badge-estado <?= $estado_clase ?>"><?= $row['estatus'] ?></span>
                                <?= $rol_label ?>
                            </td>
                            <td class="text-nowrap">
                                <a href="editar_profesor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary btn-accion" title="Editar datos del profesor"><i class="fas fa-edit"></i></a>
                                <a href="detalle_profesor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info btn-accion" title="Ver detalles completos del profesor"><i class="fas fa-eye"></i></a>
                                <a href="../estudiantes/listado.php?sala=<?= urlencode($row['sala']) ?>&seccion=<?= $row['seccion'] ?>" class="btn btn-sm btn-success btn-accion" title="Ver estudiantes asignados" target="_blank"><i class="fas fa-user-graduate"></i></a>
                                <?php if ($_SESSION['rol'] === 'super_admin' || $_SESSION['rol'] === 'administrador'): ?>
                                <button class="btn btn-sm btn-danger btn-accion" onclick="eliminarProfesor(<?= $row['id'] ?>, '<?= addslashes($row['nombre'] . ' ' . $row['apellido']) ?>')" title="Eliminar permanentemente"><i class="fas fa-trash-alt"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center py-4"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>No hay profesores registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> Los profesores inactivos aparecen con fondo gris. Al eliminar, se borran permanentemente.</span>
            <span class="text-muted small"><i class="fas fa-sync-alt me-1"></i> Filtro automático</span>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Función para eliminar profesor
function eliminarProfesor(id, nombre) {
    document.getElementById('nombre-profesor-eliminar').textContent = nombre;
    document.getElementById('eliminar-id').value = id;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}

// ===== BUSCADOR EN TIEMPO REAL (sin perder foco) =====
document.addEventListener('DOMContentLoaded', function() {
    const busquedaInput = document.getElementById('busquedaInput');
    let timeoutId = null;

    if (busquedaInput) {
        busquedaInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                document.getElementById('filtroForm').submit();
            }, 400);
        });

        // Mantener el foco después de recargar
        busquedaInput.focus();
        const length = busquedaInput.value.length;
        busquedaInput.setSelectionRange(length, length);
    }
});
</script>

<?php include('../includes/footer.php'); ?>