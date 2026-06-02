<?php
// --- SEGURIDAD: EVITAR CACHÉ ---
// Estas líneas obligan al navegador a consultar siempre al servidor
// y no mostrar la página desde su memoria local (lo que evita el "atrás")
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 01 Jan 1997 00:00:00 GMT");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validación de sesión robusta
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'super_admin')) { 
    header("Location: Login/login.php"); 
    exit(); 
}   

// Incluimos el header
include('includes/header.php'); 
?>

<div class="content-wrapper" style="padding: 20px;">
    
    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h2 style="color: #333; margin-bottom: 10px;">Gestión de Personal Docente</h2>
        <p style="color: #666; margin-bottom: 30px;">Desde aquí puedes administrar el acceso y la información de los profesores del sistema.</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
            
            <a href="profesores/login/agregar_profesor.php" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 25px; border-radius: 12px; text-decoration: none; color: #28a745; text-align: center; transition: 0.3s;">
                <i class="fas fa-user-plus" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Agregar Profesor</h4>
            </a>

            <a href="profesores/login/actualizar_profesor.php" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 25px; border-radius: 12px; text-decoration: none; color: #ffc107; text-align: center; transition: 0.3s;">
                <i class="fas fa-user-edit" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Actualizar Info</h4>
            </a>

            <a href="profesores/login/eliminar_profesor.php" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 25px; border-radius: 12px; text-decoration: none; color: #dc3545; text-align: center; transition: 0.3s;">
                <i class="fas fa-user-minus" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Eliminar Profesor</h4>
            </a>

            <a href="gestionar_permisos.php" style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 25px; border-radius: 12px; text-decoration: none; color: #6f42c1; text-align: center; transition: 0.3s;">
                <i class="fas fa-user-shield" style="font-size: 2.5em; margin-bottom: 15px;"></i>
                <h4 style="margin: 0;">Control de Permisos</h4>
            </a>
            
        </div>
    </div>

</div>

<?php 
include('includes/footer.php'); 
?>