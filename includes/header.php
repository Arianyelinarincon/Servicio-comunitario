<?php
// Asegurar que la sesión esté iniciada para poder validar los roles
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar el rol actual (por defecto si no existe, se puede tratar como invitado o redirigir)
$rol_usuario = $_SESSION['rol'] ?? '';
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
    <nav id="sidebar" class="shadow">
        <div class="sidebar-header text-center py-4">
            <h3 class="fw-bold text-white">S.G.E.</h3>
            <small class="text-white-50">U.E.B.N. Juan Pablo Pérez Alfonzo</small>
        </div>
        
        <ul class="list-unstyled components mt-2">
            <?php if ($rol_usuario === 'administrador' || $rol_usuario === 'super_admin'): ?>
                <li>
                    <a href="/servicio-comunitario/index.php">
                        <i class="fas fa-home me-2"></i> Inicio
                    </a>
                </li>
                
                <li>
                    <a href="/servicio-comunitario/estudiantes/index.php">
                        <i class="fas fa-user-plus me-2"></i> Inscripción
                    </a>
                </li>
                
                <li>
                    <a href="/servicio-comunitario/estadisticas/index.php">
                        <i class="fas fa-calendar-check me-2"></i> Asistencia Global
                    </a>
                </li>
                
                <li>
                    <a href="/servicio-comunitario/boletines/index.php">
                        <i class="fas fa-file-alt me-2"></i> Boletines
                    </a>
                </li>
                
                <li>
                    <a href="/servicio-comunitario/rendimiento_final.php">
                        <i class="fas fa-chart-line me-2"></i> Rendimiento Final
                    </a>
                </li>
                
                <li>
                    <a href="/servicio-comunitario/profesores/panel_profesor.php">
                        <i class="fas fa-users me-2"></i> Profesores
                    </a>
                </li>
                
                <li>
                    <a href="/servicio-comunitario/gestionar_permisos.php">
                        <i class="fas fa-shield-alt me-2"></i> Seguridad
                    </a>
                </li>

            <?php elseif ($rol_usuario === 'profesor'): ?>
                <li>
                    <a href="/servicio-comunitario/profesores/panel_profesor.php">
                        <i class="fas fa-home me-2"></i> Mi Panel
                    </a>
                </li>
                <li>
                    <a href="/servicio-comunitario/profesores/mis_estudiantes.php">
                        <i class="fas fa-user-graduate me-2"></i> Mis Estudiantes
                    </a>
                </li>
                <li>
                    <a href="/servicio-comunitario/estadisticas/index.php">
                        <i class="fas fa-calendar-check me-2"></i> Cargar Asistencia
                    </a>
                </li>
                <li>
                    <a href="/servicio-comunitario/boletines/index.php">
                        <i class="fas fa-file-alt me-2"></i> Gestionar Boletines
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="mt-4">
                <a href="/servicio-comunitario/logout.php" class="text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>

    <div id="content" style="width: 100%;">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3 px-4">
            <button type="button" id="sidebarCollapse" class="btn btn-primary" style="background-color: #002d54;">
                <i class="fas fa-align-left"></i>
            </button>
            <div class="ms-3 fw-bold text-muted">
                <?php 
                    // Título dinámico del topbar según el privilegio del usuario
                    if ($rol_usuario === 'administrador' || $rol_usuario === 'super_admin') {
                        echo "PANEL DE ADMINISTRACIÓN";
                    } else {
                        echo "PANEL DOCENTE - " . htmlspecialchars($_SESSION['sala'] ?? 'AULA');
                    }
                ?>
            </div>
        </nav>
        
        <div class="p-4">
          

