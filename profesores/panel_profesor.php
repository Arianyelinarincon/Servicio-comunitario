<?php
session_start();
require_once __DIR__ . '/../config/conexion.php'; 

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

$id_profesor = $_SESSION['id_usuario'];
$query = $conexion->prepare("SELECT permiso_editar_perfil FROM profesores WHERE id = ?");
$query->bind_param("i", $id_profesor);
$query->execute();
$resultado = $query->get_result();
$datos_profesor = $resultado->fetch_assoc();

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
           <h2 class="fw-bold">Bienvenido, <?php echo isset($_SESSION['nombre_profesor']) ? $_SESSION['nombre_profesor'] : 'Quedo pendiete con esto!!'; ?></h2>
            <p class="text-muted">Panel de control administrativo</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <a href="mis_estudiantes.php" class="card-link">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-primary"><i class="fas fa-user-graduate"></i></div>
                    <h5 class="fw-bold">Estudiantes</h5>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="../boletines" class="card-link">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-purple" style="color: #6f42c1;"><i class="fas fa-file-alt"></i></div>
                    <h5 class="fw-bold">Gestión Boletines</h5>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="gestionar_profesores.php" class="card-link">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-info" style="color: #17a2b8;"><i class="fas fa-users"></i></div>
                    <h5 class="fw-bold">Gestionar Personal</h5>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <?php if ($datos_profesor && $datos_profesor['permiso_editar_perfil'] == 1): ?>
           
            <?php else: ?>
               
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>