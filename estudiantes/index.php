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
        <!-- Ya NO hay botón "Registrar Ingreso" aquí -->
    </div>

    <div class="row g-4 mb-4">
        <!-- Tarjeta: Ver Estudiantes -->
        <div class="col-md-3">
            <a href="listado.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center h-100">
                    <div class="icon-box text-primary">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Ver Estudiantes</h5>
                    <p class="text-muted">Listado completo de estudiantes inscritos</p>
                </div>
            </a>
        </div>

        <!-- Tarjeta: Inscribir Estudiante -->
        <div class="col-md-3">
            <a href="inscripcion.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center h-100">
                    <div class="icon-box text-success">
                        <i class="fas fa-user-plus fa-3x"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Inscribir Estudiante</h5>
                    <p class="text-muted">Registrar un nuevo estudiante en el sistema</p>
                </div>
            </a>
        </div>

        <!-- Tarjeta: Ver Ingresos (NUEVA) -->
        <div class="col-md-3">
            <a href="../estadisticas/ingresos.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center h-100 border-info">
                    <div class="icon-box text-info">
                        <i class="fas fa-sign-in-alt fa-3x"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Ver Ingresos</h5>
                    <p class="text-muted">Estudiantes en proceso de inscripción</p>
                </div>
            </a>
        </div>

        <!-- Tarjeta: Ver Egresados -->
        <div class="col-md-3">
            <a href="../estadisticas/egresados.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center h-100 border-warning">
                    <div class="icon-box text-warning">
                        <i class="fas fa-user-times fa-3x"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Ver Egresados</h5>
                    <p class="text-muted">Historial de estudiantes que han salido</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Sección "Acciones Rápidas" ELIMINADA completamente -->
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
    .border-info {
        border: 2px solid #17a2b8 !important;
    }
    .border-warning {
        border: 2px solid #ffc107 !important;
    }
</style>

<?php include '../includes/footer.php'; ?>