<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'super_admin') {
    header("Location: /servicio-comunitario/index.php");
    exit();
}

require_once '../config/conexion.php';

// ========== PROCESAR ACCIONES ==========
$mensaje = '';
$tipo_mensaje = '';

// Agregar secretaria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar') {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $rol = $_POST['rol'] ?? 'administrador';
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($nombre) || empty($usuario) || empty($password)) {
        $mensaje = 'Nombre, usuario y contraseña son obligatorios.';
        $tipo_mensaje = 'danger';
    } else {
        $stmt = $conexion->prepare("SELECT id FROM secretaria WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $mensaje = 'El usuario "' . htmlspecialchars($usuario) . '" ya existe.';
            $tipo_mensaje = 'danger';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("INSERT INTO secretaria (nombre, usuario, password, rol, telefono, email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $nombre, $usuario, $password_hash, $rol, $telefono, $email);
            if ($stmt->execute()) {
                $id = $conexion->insert_id;
                registrarAuditoria($conexion, $_SESSION['usuario_id'], 'CREAR_SECRETARIA', 'secretaria', $id, "Nueva secretaria: $nombre (Usuario: $usuario, Rol: $rol)");
                $mensaje = 'Secretaria agregada correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al agregar: ' . $conexion->error;
                $tipo_mensaje = 'danger';
            }
            $stmt->close();
        }
    }
}

// Editar secretaria (con restricción para directiva)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $id = intval($_POST['id']);
    $rol = $_POST['rol'] ?? 'administrador';
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $estatus = $_POST['estatus'] ?? 'Activo';
    $nueva_password = trim($_POST['nueva_password'] ?? '');
    
    // Obtener datos actuales
    $stmt = $conexion->prepare("SELECT nombre, usuario, rol FROM secretaria WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $es_directiva = ($current && $current['usuario'] === 'directiva');
    
    if ($es_directiva) {
        // Directiva: solo cambiar contraseña y estatus
        $sql = "UPDATE secretaria SET estatus = ?";
        $params = [$estatus];
        $types = "s";
        
        if (!empty($nueva_password)) {
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $params[] = $password_hash;
            $types .= "s";
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            registrarAuditoria($conexion, $_SESSION['usuario_id'], 'EDITAR_SECRETARIA', 'secretaria', $id, "Editada directiva (solo contraseña/estatus)");
            $mensaje = 'Directiva actualizada correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al actualizar: ' . $conexion->error;
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    } else {
        // Edición normal para otros usuarios
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        
        if (empty($nombre) || empty($usuario)) {
            $mensaje = 'Nombre y usuario son obligatorios.';
            $tipo_mensaje = 'danger';
        } else {
            $stmt = $conexion->prepare("SELECT id FROM secretaria WHERE usuario = ? AND id != ?");
            $stmt->bind_param("si", $usuario, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $mensaje = 'El usuario "' . htmlspecialchars($usuario) . '" ya está en uso.';
                $tipo_mensaje = 'danger';
            } else {
                $sql = "UPDATE secretaria SET nombre = ?, usuario = ?, rol = ?, telefono = ?, email = ?, estatus = ?";
                $params = [$nombre, $usuario, $rol, $telefono, $email, $estatus];
                $types = "ssssss";
                
                if (!empty($nueva_password)) {
                    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
                    $sql .= ", password = ?";
                    $params[] = $password_hash;
                    $types .= "s";
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $id;
                $types .= "i";
                
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    registrarAuditoria($conexion, $_SESSION['usuario_id'], 'EDITAR_SECRETARIA', 'secretaria', $id, "Editada secretaria ID $id: $nombre (Usuario: $usuario, Rol: $rol)");
                    $mensaje = 'Secretaria actualizada correctamente.';
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Error al actualizar: ' . $conexion->error;
                    $tipo_mensaje = 'danger';
                }
                $stmt->close();
            }
        }
    }
}

// Eliminar secretaria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar') {
    $id = intval($_POST['id']);
    
    $stmt = $conexion->prepare("SELECT nombre, usuario FROM secretaria WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $mensaje = 'Usuario no encontrado.';
        $tipo_mensaje = 'danger';
    } elseif ($user['usuario'] === 'directiva') {
        $mensaje = 'No se puede eliminar la cuenta de Directiva.';
        $tipo_mensaje = 'danger';
    } elseif ($id == $_SESSION['usuario_id']) {
        $mensaje = 'No puedes eliminar tu propia cuenta.';
        $tipo_mensaje = 'danger';
    } else {
        $stmt = $conexion->prepare("DELETE FROM secretaria WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            registrarAuditoria($conexion, $_SESSION['usuario_id'], 'ELIMINAR_SECRETARIA', 'secretaria', $id, "Eliminada secretaria: {$user['nombre']} (Usuario: {$user['usuario']})");
            $mensaje = 'Secretaria eliminada correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar: ' . $conexion->error;
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// ========== OBTENER DATOS ==========
$sql = "SELECT * FROM secretaria ORDER BY rol DESC, nombre ASC";
$result = $conexion->query($sql);

$sql_audit = "
    SELECT a.*, 
           COALESCE(s.nombre, 'Sistema') AS usuario_nombre
    FROM auditoria a
    LEFT JOIN secretaria s ON a.usuario_id = s.id
    WHERE a.tabla_afectada = 'secretaria'
    ORDER BY a.fecha DESC
    LIMIT 50
";
$result_audit = $conexion->query($sql_audit);

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-shield-alt me-2"></i>Seguridad</h2>
            <p class="text-muted">Administración de usuarios y auditoría del sistema</p>
        </div>
        <div>
            <span class="badge bg-danger me-2">👑 Super Admin</span>
            <span class="badge bg-secondary"><?= htmlspecialchars($_SESSION['nombre_profesor']) ?></span>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ===== BOTONES MODERNOS ===== -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center py-5">
                    <div class="icon-circle bg-primary mb-3">
                        <i class="fas fa-users-cog fa-3x text-white"></i>
                    </div>
                    <h4 class="fw-bold">Gestionar Usuarios</h4>
                    <p class="text-muted">Agregar, editar o eliminar secretarias del sistema</p>
                    <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#modalUsuarios">
                        <i class="fas fa-arrow-right me-2"></i> Acceder
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0 hover-card">
                <div class="card-body text-center py-5">
                    <div class="icon-circle bg-info mb-3">
                        <i class="fas fa-history fa-3x text-white"></i>
                    </div>
                    <h4 class="fw-bold">Historial de Movimientos</h4>
                    <p class="text-muted">Registro de todas las acciones realizadas en el sistema</p>
                    <a href="historial_auditoria.php" class="btn btn-info text-white px-4">
                        <i class="fas fa-arrow-right me-2"></i> Ver Historial
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL: GESTIONAR USUARIOS ===== -->
    <div class="modal fade" id="modalUsuarios" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-users-cog me-2"></i>Gestionar Usuarios</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modalAgregar" onclick="$('#modalUsuarios').modal('hide')">
                        <i class="fas fa-user-plus me-2"></i> Agregar Secretaria
                    </button>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): 
                                        $rol_label = $row['rol'] === 'super_admin' ? '<span class="badge bg-danger">Super Admin</span>' : '<span class="badge bg-primary">Admin</span>';
                                        $estatus_label = $row['estatus'] === 'Activo' ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';
                                        $es_directiva = ($row['usuario'] === 'directiva');
                                    ?>
                                    <tr>
                                        <td><?= $row['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($row['nombre']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($row['usuario']) ?></code></td>
                                        <td><?= $rol_label ?></td>
                                        <td><?= htmlspecialchars($row['telefono'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                                        <td><?= $estatus_label ?></td>
                                        <td class="text-nowrap">
                                            <button class="btn btn-sm btn-primary" onclick="editarUsuario(<?= $row['id'] ?>, <?= $es_directiva ? 'true' : 'false' ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (!$es_directiva && $row['id'] != $_SESSION['usuario_id']): ?>
                                            <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(<?= $row['id'] ?>, '<?= addslashes($row['nombre']) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center">No hay usuarios registrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL AGREGAR ===== -->
    <div class="modal fade" id="modalAgregar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Agregar Secretaria</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="agregar">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nombre completo *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Usuario *</label>
                            <input type="text" name="usuario" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Contraseña *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Rol</label>
                            <select name="rol" class="form-select">
                                <option value="administrador">Admin (Secretaria)</option>
                                <option value="super_admin">Super Admin (Directiva)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== MODAL EDITAR ===== -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="editar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body" id="edit_form_body">
                        <!-- Se llena con JS -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== MODAL ELIMINAR ===== -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de eliminar a <strong id="eliminar_nombre"></strong>?</p>
                    <p class="text-danger small">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id" id="eliminar_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
        border-radius: 15px !important;
    }
    .hover-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15) !important;
    }
    .icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #002d54, #004a7c);
    }
    .icon-circle.bg-primary {
        background: linear-gradient(135deg, #002d54, #004a7c);
    }
    .icon-circle.bg-info {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    }
    .modal-xl {
        max-width: 90%;
    }
</style>

<script>
function editarUsuario(id, esDirectiva) {
    fetch('ajax_get_secretaria.php?id=' + id)
        .then(res => {
            if (!res.ok) throw new Error('Error al cargar los datos');
            return res.json();
        })
        .then(data => {
            const body = document.getElementById('edit_form_body');
            if (esDirectiva) {
                body.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Directiva</strong> - Solo puedes cambiar la contraseña y el estado.
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" value="${data.nombre}" disabled>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" value="${data.usuario}" disabled>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nueva Contraseña (dejar vacío para no cambiar)</label>
                        <input type="password" name="nueva_password" class="form-control" placeholder="Ingrese nueva contraseña...">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Estado</label>
                        <select name="estatus" class="form-select">
                            <option value="Activo" ${data.estatus === 'Activo' ? 'selected' : ''}>Activo</option>
                            <option value="Inactivo" ${data.estatus === 'Inactivo' ? 'selected' : ''}>Inactivo</option>
                        </select>
                    </div>
                    <input type="hidden" name="nombre" value="${data.nombre}">
                    <input type="hidden" name="usuario" value="${data.usuario}">
                    <input type="hidden" name="rol" value="${data.rol}">
                    <input type="hidden" name="telefono" value="${data.telefono || ''}">
                    <input type="hidden" name="email" value="${data.email || ''}">
                `;
            } else {
                body.innerHTML = `
                    <div class="mb-2">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" name="nombre" class="form-control" value="${data.nombre}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Usuario *</label>
                        <input type="text" name="usuario" class="form-control" value="${data.usuario}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nueva Contraseña (dejar vacío para no cambiar)</label>
                        <input type="password" name="nueva_password" class="form-control" placeholder="Ingrese nueva contraseña...">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Rol</label>
                        <select name="rol" class="form-select">
                            <option value="administrador" ${data.rol === 'admin' ? 'selected' : ''}>Admin (Secretaria)</option>
                            <option value="super_admin" ${data.rol === 'super_admin' ? 'selected' : ''}>Super Admin (Directiva)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="${data.telefono || ''}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="${data.email || ''}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Estado</label>
                        <select name="estatus" class="form-select">
                            <option value="Activo" ${data.estatus === 'Activo' ? 'selected' : ''}>Activo</option>
                            <option value="Inactivo" ${data.estatus === 'Inactivo' ? 'selected' : ''}>Inactivo</option>
                        </select>
                    </div>
                `;
            }
            document.getElementById('edit_id').value = data.id;
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        })
        .catch(() => alert('Error al cargar los datos del usuario.'));
}

function eliminarUsuario(id, nombre) {
    document.getElementById('eliminar_id').value = id;
    document.getElementById('eliminar_nombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/footer.php'; ?>