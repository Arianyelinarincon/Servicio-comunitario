<?php
// --- SEGURIDAD: EVITAR CACHÉ ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 01 Jan 1997 00:00:00 GMT");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validación de sesión
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'super_admin')) { 
    header("Location: Login/login.php"); 
    exit(); 
}   

include('includes/header.php'); 
?>

<style>
    /* Estilos de botones organizados */
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
</style>

<div class="content-wrapper" style="padding: 20px;">
    
    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h2 style="color: #333; margin-bottom: 10px;">Gestión de Personal Docente</h2>
        <p style="color: #666; margin-bottom: 30px;">Desde aquí puedes administrar el acceso y la información de los profesores del sistema.</p>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <a href="profesores/login/agregar_profesor.php" class="btn-gestion btn-agregar">
                <i class="fas fa-user-plus" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Agregar Profesor</h4>
            </a>

            <a href="profesores/login/actualizar_profesor.php" class="btn-gestion btn-actualizar">
                <i class="fas fa-user-edit" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Actualizar Info</h4>
            </a>
            
        </div>
    </div>

</div>

<?php 
include('includes/footer.php'); 
?>