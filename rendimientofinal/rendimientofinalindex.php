<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Rendimiento Final</h2>
            <p class="text-muted">Nómina de Alumnos - Rendimiento Final</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tarjeta: Estudiantes de Inicial (antes "Pre Inicial") -->
        <div class="col-md-6 mb-4">
            <a href="pre-inicial.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-primary">
                        <i class="fas fa-baby fa-3x"></i> 
                    </div>
                    <h5 class="fw-bold mt-3">Estudiantes de Inicial</h5>
                    <p class="text-muted">Gestión y registro de alumnos de etapa inicial</p>
                </div>
            </a>
        </div>

        <!-- Tarjeta: Estudiantes de Primaria -->
        <div class="col-md-6 mb-4">
            <a href="primaria.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-primary">
                        <i class="fas fa-child fa-3x"></i> 
                    </div>
                    <h5 class="fw-bold mt-3">Estudiantes de Primaria</h5>
                    <p class="text-muted">Gestión y registro de alumnos de educación básica</p>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    .dashboard-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .dashboard-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }
    .icon-box {
        margin-bottom: 15px;
    }
</style>

<?php include '../includes/footer.php'; ?>