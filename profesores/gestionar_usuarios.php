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

// Agregar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar') {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $rol = $_POST['rol'] ?? 'admin';
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
                $mensaje = 'Usuario agregado correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al agregar: ' . $conexion->error;
                $tipo_mensaje = 'danger';
            }
            $stmt->close();
        }
    }
}

// Editar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $rol = $_POST['rol'] ?? 'admin';
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $estatus = $_POST['estatus'] ?? 'Activo';
    $nueva_password = trim($_POST['nueva_password'] ?? '');
    
    // ===== PROTECCIÓN: Directiva solo puede cambiar contraseña =====
    $es_directiva = ($usuario === 'directiva');
    
    if ($es_directiva) {
        // Solo permitir cambiar contraseña, mantener el resto igual
        if (empty($nueva_password)) {
            $mensaje = 'Para Directiva solo se permite cambiar la contraseña.';
            $tipo_mensaje = 'warning';
        } else {
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE secretaria SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password_hash, $id);
            if ($stmt->execute()) {
                registrarAuditoria($conexion, $_SESSION['usuario_id'], 'EDITAR_SECRETARIA', 'secretaria', $id, "Cambio de contraseña de Directiva");
                $mensaje = 'Contraseña de Directiva actualizada correctamente.';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al actualizar: ' . $conexion->error;
                $tipo_mensaje = 'danger';
            }
            $stmt->close();
        }
    } else {
        // Edición normal para otros usuarios
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
                    $mensaje = 'Usuario actualizado correctamente.';
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

// Eliminar usuario (no permite eliminar a directiva ni a sí mismo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar') {
    $id = intval($_POST['id']);
    
    // Obtener usuario para verificar si es directiva
    $stmt = $conexion->prepare("SELECT usuario FROM secretaria WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($id == $_SESSION['usuario_id']) {
        $mensaje = 'No puedes eliminar tu propia cuenta.';
        $tipo_mensaje = 'danger';
    } elseif ($user && $user['usuario'] === 'directiva') {
        $mensaje = 'No se puede eliminar al usuario Directiva.';
        $tipo_mensaje = 'danger';
    } else {
        $stmt = $conexion->prepare("DELETE FROM secretaria WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            registrarAuditoria($conexion, $_SESSION['usuario_id'], 'ELIMINAR_SECRETARIA', 'secretaria', $id, "Eliminado usuario ID $id");
            $mensaje = 'Usuario eliminado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al eliminar: ' . $conexion->error;
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// ========== OBTENER LISTADO ==========
$sql = "SELECT * FROM secretaria ORDER BY rol DESC, nombre ASC";
$result = $conexion->query($sql);

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-users-cog me-2"></i>Gestionar Usuarios</h2>
            <p class="text-muted">Administrar secretarias y directiva del sistema</p>
        </div>
        <a href="gestionar_permisos.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Volver a Seguridad
        </a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modalAgregar">
        <i class="fas fa-user-plus me-2"></i> Agregar Usuario
    </button>

    <!-- ===== TABLA DE USUARIOS ===== -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i> Usuarios del Sistema</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
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
                                    <?php if ($row['id'] != $_SESSION['usuario_id'] && !$es_directiva): ?>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(<?= $row['id'] ?>, '<?= addslashes($row['nombre']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($es_directiva): ?>
                                        <span class="badge bg-warning text-dark" title="Usuario Directiva no se puede eliminar">🔒</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-3">No hay usuarios registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL AGREGAR ===== -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Agregar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                            <option value="admin">Admin (Secretaria)</option>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div id="campos_editables">
                        <div class="mb-2">
                            <label class="form-label">Nombre completo *</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Usuario *</label>
                            <input type="text" name="usuario" id="edit_usuario" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Rol</label>
                            <select name="rol" id="edit_rol" class="form-select">
                                <option value="admin">Admin (Secretaria)</option>
                                <option value="super_admin">Super Admin (Directiva)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="edit_telefono" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Estado</label>
                            <select name="estatus" id="edit_estatus" class="form-select">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nueva Contraseña (dejar vacío para no cambiar)</label>
                        <input type="password" name="nueva_password" class="form-control" placeholder="Ingrese nueva contraseña...">
                        <small class="text-muted" id="mensaje_directiva" style="display:none;">Para Directiva solo se puede cambiar la contraseña.</small>
                    </div>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

<script>
function editarUsuario(id, esDirectiva) {
    fetch('ajax_get_secretaria.php?id=' + id)
        .then(res => {
            if (!res.ok) throw new Error('Error al cargar los datos');
            return res.json();
        })
        .then(data => {
            document.getElementById('edit_id').value = data.id;
            
            if (esDirectiva) {
                // Ocultar campos que no se pueden editar para Directiva
                document.getElementById('campos_editables').style.display = 'none';
                document.getElementById('mensaje_directiva').style.display = 'block';
                // Forzar valores para que no se envíen vacíos
                document.getElementById('edit_nombre').value = data.nombre;
                document.getElementById('edit_usuario').value = data.usuario;
                document.getElementById('edit_rol').value = data.rol;
                document.getElementById('edit_telefono').value = data.telefono || '';
                document.getElementById('edit_email').value = data.email || '';
                document.getElementById('edit_estatus').value = data.estatus || 'Activo';
            } else {
                document.getElementById('campos_editables').style.display = 'block';
                document.getElementById('mensaje_directiva').style.display = 'none';
                document.getElementById('edit_nombre').value = data.nombre;
                document.getElementById('edit_usuario').value = data.usuario;
                document.getElementById('edit_rol').value = data.rol;
                document.getElementById('edit_telefono').value = data.telefono || '';
                document.getElementById('edit_email').value = data.email || '';
                document.getElementById('edit_estatus').value = data.estatus || 'Activo';
            }
            
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