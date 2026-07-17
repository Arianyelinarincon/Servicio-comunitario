<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'super_admin') {
    header("Location: /servicio-comunitario/index.php");
    exit();
}
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-navy text-white">
            <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Agregar Secretaria</h5>
        </div>
        <div class="card-body p-4">
            <form action="procesar_secretaria.php" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control text-uppercase" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario <span class="text-danger">*</span></label>
                        <input type="text" name="usuario" class="form-control" required>
                        <small class="text-muted">Ej: secretaria1, doris, etc.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                        <small class="text-muted">Mínimo 4 caracteres</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirmar contraseña <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" placeholder="0414-0000000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                        <select name="rol" class="form-select" required>
                            <option value="admin">Admin (Secretaria)</option>
                            <option value="super_admin">Super Admin (Directora)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="estatus" class="form-select">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fas fa-save me-2"></i>Guardar
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