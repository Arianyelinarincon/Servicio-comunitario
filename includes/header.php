<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

$rol_raw = $_SESSION['rol'] ?? '';
$rol_normalizado = trim(preg_replace('/[^a-zA-Z_]/', '', $rol_raw));
$rol_normalizado = strtolower($rol_normalizado);

// Determinar la clase del sidebar ANTES de enviar el HTML
$sidebar_class = '';
if (isset($_COOKIE['sidebarStatus']) && $_COOKIE['sidebarStatus'] === 'hidden') {
    $sidebar_class = 'active';
} else {
    // También revisamos localStorage, pero para evitar flickering, usamos cookie
    // que se sincroniza con JS. Por defecto, visible.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.G.E - Pérez Alfonzo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include "estilos.php"; ?>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar" class="shadow <?php echo $sidebar_class; ?>">
        <div class="sidebar-header text-center py-4">
            <h3 class="fw-bold text-white">S.G.E.</h3>
            <small class="text-white-50">U.E.B.N. Juan Pablo Pérez Alfonzo</small>
        </div>
        
        <ul class="list-unstyled components mt-2">
            <?php if ($rol_normalizado === 'administrador' || $rol_normalizado === 'super_admin'): ?>
                <li><a href="/servicio-comunitario/index.php"><i class="fas fa-home me-2"></i> Inicio</a></li>
                <?php if ($rol_normalizado === 'super_admin'): ?>
                    <li class="bg-primary bg-opacity-10">
                        <a href="/servicio-comunitario/profesores/panel_super_admin.php" class="text-primary fw-bold">
                            <i class="fas fa-user-cog me-2"></i> Panel Directora
                        </a>
                    </li>
                <?php endif; ?>
                <li><a href="/servicio-comunitario/estudiantes/index.php"><i class="fas fa-user-plus me-2"></i> Estudiantes</a></li>
                <li><a href="/servicio-comunitario/estadisticas/index.php"><i class="fas fa-calendar-check me-2"></i> Asistencia Global</a></li>
                <li><a href="/servicio-comunitario/boletines/index.php"><i class="fas fa-file-alt me-2"></i> Boletines</a></li>
                <li><a href="/servicio-comunitario/rendimientofinal/rendimientofinalindex.php"><i class="fas fa-chart-line me-2"></i> Rendimiento Final</a></li>
                <li><a href="/servicio-comunitario/profesores/panel_profesor.php"><i class="fas fa-users me-2"></i> Profesores</a></li>
                <li><a href="/servicio-comunitario/gestionar_permisos.php"><i class="fas fa-shield-alt me-2"></i> Seguridad</a></li>
                <?php if ($rol_normalizado === 'super_admin'): ?>
                    <li><a href="/servicio-comunitario/profesores/gestionar_usuarios.php"><i class="fas fa-users-cog me-2"></i> Gestionar Usuarios del Sistema</a></li>
                <?php endif; ?>
            <?php endif; ?>
            <li class="mt-4"><a href="/servicio-comunitario/logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
        </ul>
    </nav>

    <div id="content" style="width: 100%;">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3 px-4">
            <button type="button" id="sidebarCollapse" class="btn btn-primary" style="background-color: #002d54;">
                <i class="fas fa-align-left"></i>
            </button>
            <div class="ms-3 fw-bold text-muted">
                <?php echo ($rol_normalizado === 'super_admin') ? 'PANEL DE DIRECTORA' : 'PANEL DE SECRETARÍA'; ?>
            </div>
        </nav>
        <div class="p-4">