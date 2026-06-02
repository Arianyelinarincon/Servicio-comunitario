<?php
// 1. SEGURIDAD: Evitar caché y forzar validación de sesión
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Iniciamos sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Incluir conexión (ruta ajustada para salir de 'profesores')
require_once '../config/conexion.php'; 

// 3. Validación estricta: solo super_admin puede entrar
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'super_admin') {
    header("Location: ../Login/login.php");
    exit();
}

// 4. Consultas para los indicadores
$res_prof = $conexion->query("SELECT COUNT(*) as total FROM administradores");
$total_profesores = $res_prof->fetch_assoc()['total'];
$acciones_hoy = 0; // Inicializamos contador

// 5. Incluir el diseño (ruta ajustada)
include('../includes/header.php'); 
?>

<div class="content-wrapper" style="padding: 20px;">
    <div class="container-fluid">
        <h2 class="mb-4">Dashboard Super Admin</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: #003366; color: white; padding: 20px; border-radius: 10px;">
                <h3>Total Profesores</h3>
                <p style="font-size: 2em;"><?php echo htmlspecialchars($total_profesores); ?></p>
            </div>
            <div style="background: #28a745; color: white; padding: 20px; border-radius: 10px;">
                <h3>Acciones del Día</h3>
                <p style="font-size: 2em;"><?php echo htmlspecialchars($acciones_hoy); ?></p>
            </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3><i class="fas fa-history"></i> Auditoría del Sistema</h3>
            <table class="table table-striped table-hover mt-3">
                <thead class="table-light">
                    <tr>
                        <th>Administrador</th>
                        <th>Acción</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="3" class="text-center">No hay registros recientes.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// 6. Incluir footer (ruta ajustada)
include('../includes/footer.php'); 
?>