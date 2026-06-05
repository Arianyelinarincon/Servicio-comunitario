<?php
// --- SEGURIDAD: EVITAR CACHÉ ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 01 Jan 1997 00:00:00 GMT");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validación de sesión: SOLO administrador y super_admin
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) { 
    header("Location: profesores/Login/login.php"); 
    exit(); 
}   

include('includes/header.php'); 
?>

<style>
    .btn-gestion {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 30px;
        border-radius: 12px;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 250px;
    }
    .btn-gestion:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        border-color: #007bff;
    }
    .btn-agregar { color: #28a745; }
    .btn-actualizar { color: #ffc107; }
    .welcome-message {
        background: #e9f7ff;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        border-left: 5px solid #007bff;
    }
</style>

<div class="content-wrapper" style="padding: 20px;">
    
    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <div class="welcome-message">
            <h3>Bienvenida, <?php echo htmlspecialchars($_SESSION['nombre_profesor'] ?? $_SESSION['usuario']); ?> </h3>
            <p>Rol: <strong><?php echo ucfirst($_SESSION['rol']); ?></strong></p>
        </div>

        <h2 style="color: #333; margin-bottom: 10px;">Gestión del Sistema Educativo</h2>
        <p style="color: #666; margin-bottom: 30px;">Desde aquí puedes administrar estudiantes, asistencia, boletines y usuarios.</p>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <a href="estudiantes/index.php" class="btn-gestion btn-agregar">
                <i class="fas fa-user-graduate" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Gestión de Estudiantes</h4>
            </a>

            <a href="estadisticas/index.php" class="btn-gestion btn-actualizar">
                <i class="fas fa-calendar-check" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Control de Asistencia</h4>
            </a>

            <a href="boletines/paso1_portada.php" class="btn-gestion" style="color: #17a2b8;">
                <i class="fas fa-file-alt" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Generar Boletines</h4>
            </a>

            <?php if ($_SESSION['rol'] === 'super_admin'): ?>
            <a href="profesores/gestionar_profesores.php" class="btn-gestion" style="color: #6f42c1;">
                <i class="fas fa-users-cog" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Gestionar Usuarios</h4>
            </a>
            <?php endif; ?>
            
        </div>
    </div>

</div>

<?php 
include('includes/footer.php'); 
?>