<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Gestión de Estudiantes</h2>
            <p class="text-muted">Panel de control para inscripciones y listado de alumnos</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tarjeta: Ver Estudiantes (apunta a listado.php) -->
        <div class="col-md-6">
            <a href="listado.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-primary">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Ver Estudiantes</h5>
                    <p class="text-muted">Listado completo de estudiantes inscritos, con filtros y acciones</p>
                </div>
            </a>
        </div>

        <!-- Tarjeta: Inscribir Estudiante -->
        <div class="col-md-6">
            <a href="inscripcion.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-success">
                        <i class="fas fa-user-plus fa-3x"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Inscribir Estudiante</h5>
                    <p class="text-muted">Registrar un nuevo estudiante en el sistema</p>
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