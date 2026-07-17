<?php
session_start();
require_once __DIR__ . '/../config/conexion.php'; // <- $conexion disponible globalmente

// ========== VERIFICAR AUTENTICACIÓN ==========
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'super_admin' && $_SESSION['rol'] !== 'admin')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

// ========== CORRECCIÓN: Cambiar id_usuario por usuario_id ==========
$id_usuario = $_SESSION['usuario_id'] ?? 0;

// ========== Verificar si el usuario existe en profesores ==========
$permiso_editar_perfil = 0;
if ($id_usuario > 0) {
    $query = $conexion->prepare("SELECT permiso_editar_perfil FROM profesores WHERE id = ?");
    if ($query) {
        $query->bind_param("i", $id_usuario);
        $query->execute();
        $resultado = $query->get_result();
        $datos_profesor = $resultado->fetch_assoc();
        if ($datos_profesor) {
            $permiso_editar_perfil = $datos_profesor['permiso_editar_perfil'] ?? 0;
        }
        $query->close();
    }
}

include('../includes/header.php');
?>

<style>
    .dashboard-card { transition: all 0.3s ease; border: none; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .dashboard-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
    .icon-box { font-size: 2.5rem; margin-bottom: 15px; }
    .card-link { text-decoration: none; color: inherit; display: block; }
</style>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Bienvenido, <?php echo isset($_SESSION['nombre_profesor']) ? htmlspecialchars($_SESSION['nombre_profesor']) : 'Usuario'; ?></h2>
            <p class="text-muted">Panel de control administrativo</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tarjeta: Gestionar Personal -->
        <div class="col-md-3">
            <a href="gestionar_profesores.php" class="card-link">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-info" style="color: #17a2b8;"><i class="fas fa-users"></i></div>
                    <h5 class="fw-bold">Gestionar Personal</h5>
                </div>
            </a>
        </div>

        <!-- ===== SOLO SE MUESTRA SI EL USUARIO ESTÁ EN PROFESORES ===== -->
        <?php if ($permiso_editar_perfil == 1): ?>
        <div class="col-md-3">
            <a href="actualizar_perfil.php" class="card-link">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-success" style="color: #28a745;"><i class="fas fa-user-edit"></i></div>
                    <h5 class="fw-bold">Editar Perfil</h5>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include('../includes/footer.php'); ?>