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
            <h2 class="fw-bold">Generar Boletín Informativo</h2>
            <p class="text-muted">Seleccione el tipo de boletín a generar</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- BOLETÍN PARA INICIAL -->
        <div class="col-md-3 mb-4">
            <a href="paso1_portada.php?tipo=inicial" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-primary"><i class="fas fa-baby fa-3x"></i></div>
                    <h5 class="fw-bold mt-3">Boletín para Inicial</h5>
                    <p class="text-muted">Para Sala 4 y 5 años</p>
                </div>
            </a>
        </div>
        
        <!-- BOLETÍN PARA PRIMARIA -->
        <div class="col-md-3 mb-4">
            <a href="paso1_portada_primaria.php?tipo=primaria" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-primary"><i class="fas fa-child fa-3x"></i></div>
                    <h5 class="fw-bold mt-3">Boletín para Primaria</h5>
                    <p class="text-muted">Para 1° a 6° grado</p>
                </div>
            </a>
        </div>
        
        <!-- HISTORIAL DE BOLETINES -->
        <div class="col-md-3 mb-4">
            <a href="historial_boletines.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center">
                    <div class="icon-box text-primary"><i class="fas fa-history fa-3x"></i></div>
                    <h5 class="fw-bold mt-3">Historial de Boletines</h5>
                    <p class="text-muted">Consultar boletines generados anteriormente</p>
                </div>
            </a>
        </div>

        <!-- REPORTE DE RENDIMIENTO FINAL -->
        <div class="col-md-3 mb-4">
            <a href="reporte_rendimiento_final.php" class="text-decoration-none">
                <div class="card p-4 dashboard-card text-center border-success">
                    <div class="icon-box text-success"><i class="fas fa-clipboard-check fa-3x"></i></div>
                    <h5 class="fw-bold mt-3">Rendimiento Final</h5>
                    <p class="text-muted">Reporte de aprobados con boletín completo</p>
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
        min-height: 180px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .dashboard-card:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 15px 30px rgba(0,0,0,0.15); 
    }
    .icon-box { margin-bottom: 15px; }
    .border-success { border: 2px solid #28a745 !important; }
</style>

<?php include '../includes/footer.php'; ?>