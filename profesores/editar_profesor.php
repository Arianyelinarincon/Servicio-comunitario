<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once __DIR__ . '/../config/conexion.php';

// ========== OBTENER ID DEL PROFESOR ==========
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: gestionar_profesores.php?msg=error");
    exit();
}

// ========== OBTENER DATOS DEL PROFESOR ==========
$stmt = $conexion->prepare("SELECT * FROM profesores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$profesor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profesor) {
    header("Location: gestionar_profesores.php?msg=error");
    exit();
}

include('../includes/header.php');
?>

<style>
    .bg-navy { background-color: #002d54 !important; }
    .btn-navy { background-color: #002d54; color: white; }
    .btn-navy:hover { background-color: #004080; color: white; }
    .card { border-radius: 12px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
</style>

<div class="container-fluid py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-navy text-white">
            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Editar Profesor</h5>
        </div>
        <div class="card-body p-4">
            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'campos_requeridos'): ?>
                    <div class="alert alert-danger">Todos los campos obligatorios deben ser llenados.</div>
                <?php elseif ($_GET['error'] === 'cedula_duplicada'): ?>
                    <div class="alert alert-danger">Esta cédula ya está registrada por otro profesor.</div>
                <?php elseif ($_GET['error'] === 'db_error'): ?>
                    <div class="alert alert-danger">Error al guardar los cambios. Intente nuevamente.</div>
                <?php endif; ?>
            <?php endif; ?>

            <form action="procesar_profesor.php" method="POST">
                <!-- Campo oculto para indicar edición -->
                <input type="hidden" name="editar" value="1">
                <input type="hidden" name="profesor_id" value="<?= $profesor['id'] ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control text-uppercase" value="<?= htmlspecialchars($profesor['nombre']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Apellido <span class="text-danger">*</span></label>
                        <input type="text" name="apellido" class="form-control text-uppercase" value="<?= htmlspecialchars($profesor['apellido']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cédula <span class="text-danger">*</span></label>
                        <input type="text" name="cedula" class="form-control" value="<?= htmlspecialchars($profesor['cedula']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($profesor['telefono']) ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Dirección</label>
                        <textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($profesor['direccion']) ?></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Sección (Grado - Sección) <span class="text-danger">*</span></label>
                        <select name="seccion_id" id="seccion_id" class="form-select" required>
                            <option value="">Seleccione una sección...</option>
                            <?php
                            $secciones = $conexion->query("SELECT id, sala, nombre FROM secciones ORDER BY sala, nombre");
                            while ($sec = $secciones->fetch_assoc()) {
                                $sala_nombre = '';
                                switch ($sec['sala']) {
                                    case 'sala4': $sala_nombre = 'Sala 4 años'; break;
                                    case 'sala5': $sala_nombre = 'Sala 5 años'; break;
                                    case '1ro': $sala_nombre = '1er Grado'; break;
                                    case '2do': $sala_nombre = '2do Grado'; break;
                                    case '3ro': $sala_nombre = '3er Grado'; break;
                                    case '4to': $sala_nombre = '4to Grado'; break;
                                    case '5to': $sala_nombre = '5to Grado'; break;
                                    case '6to': $sala_nombre = '6to Grado'; break;
                                    default: $sala_nombre = $sec['sala'];
                                }
                                $selected = ($profesor['seccion'] == $sec['id']) ? 'selected' : '';
                                echo '<option value="' . $sec['id'] . '" ' . $selected . '>' . $sala_nombre . ' - Sección ' . $sec['nombre'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-navy px-4"><i class="fas fa-save me-2"></i>Guardar Cambios</button>
                    <a href="gestionar_profesores.php" class="btn btn-secondary px-4"><i class="fas fa-arrow-left me-2"></i>Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>