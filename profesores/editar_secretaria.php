<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'super_admin') {
    header("Location: /servicio-comunitario/index.php");
    exit();
}
include '../includes/header.php';
require_once '../config/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header("Location: gestionar_usuarios.php");
    exit();
}

// Obtener datos de la secretaria
$stmt = $conexion->prepare("SELECT * FROM secretaria WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$secretaria = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$secretaria) {
    header("Location: gestionar_usuarios.php");
    exit();
}
?>

<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-navy text-white">
            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Secretaria</h5>
        </div>
        <div class="card-body p-4">
            <form action="procesar_secretaria.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $id ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($secretaria['nombre']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario <span class="text-danger">*</span></label>
                        <input type="text" name="usuario" class="form-control" value="<?= htmlspecialchars($secretaria['usuario']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nueva Contraseña (dejar vacío para mantener)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirmar contraseña</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($secretaria['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($secretaria['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                        <?php if ($secretaria['usuario'] === 'doris_admin' || $secretaria['usuario'] === 'directora'): ?>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($secretaria['rol']) ?>" readonly disabled>
                            <input type="hidden" name="rol" value="<?= $secretaria['rol'] ?>">
                            <small class="text-muted">Rol protegido para este usuario</small>
                        <?php else: ?>
                            <select name="rol" class="form-select" required>
                                <option value="admin" <?= $secretaria['rol'] == 'admin' ? 'selected' : '' ?>>Admin (Secretaria)</option>
                                <option value="super_admin" <?= $secretaria['rol'] == 'super_admin' ? 'selected' : '' ?>>Super Admin (Directora)</option>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="estatus" class="form-select">
                            <option value="Activo" <?= $secretaria['estatus'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= $secretaria['estatus'] == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                    <a href="gestionar_usuarios.php" class="btn btn-secondary px-4">
                        <i class="fas fa-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>