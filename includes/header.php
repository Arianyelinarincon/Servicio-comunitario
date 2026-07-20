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

require_once __DIR__ . '/../config/configuracion.php';
require_once __DIR__ . '/../config/conexion.php';

// ========== OBTENER PERIODO ESCOLAR ==========
$periodo_escolar_actual = obtenerPeriodoEscolar();

// ========== NORMALIZAR ROL ==========
$rol_raw = $_SESSION['rol'] ?? '';
$rol_normalizado = trim(preg_replace('/[^a-zA-Z_]/', '', $rol_raw));
$rol_normalizado = strtolower($rol_normalizado);

// ========== DEFINIR ROLES PERMITIDOS ==========
$roles_admin = ['administrador', 'admin', 'super_admin', 'directiva', 'superadmin'];
$es_admin = in_array($rol_normalizado, $roles_admin);

// ========== SIDEBAR COLLAPSIBLE ==========
$sidebar_class = '';
if (isset($_COOKIE['sidebarStatus']) && $_COOKIE['sidebarStatus'] === 'hidden') {
    $sidebar_class = 'active';
}

// ========== DETERMINAR TÍTULO DE LA BARRA SUPERIOR ==========
$panel_titulo = 'PANEL DE SECRETARÍA';
if ($rol_normalizado === 'super_admin' || $rol_normalizado === 'superadmin' || $rol_normalizado === 'directiva') {
    $panel_titulo = 'PANEL DE DIRECTIVA';
}

// ========== ASEGURAR QUE USUARIO_ID EXISTA ==========
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_id'] == 0) {
    if (isset($_SESSION['usuario'])) {
        $sql_user = "SELECT id FROM secretaria WHERE usuario = ?";
        $stmt_user = $conexion->prepare($sql_user);
        if ($stmt_user) {
            $stmt_user->bind_param('s', $_SESSION['usuario']);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            if ($row_user = $result_user->fetch_assoc()) {
                $_SESSION['usuario_id'] = intval($row_user['id']);
                $_SESSION['tipo_usuario'] = 'secretaria';
            } else {
                $sql_user = "SELECT id FROM profesores WHERE usuario = ?";
                $stmt_user = $conexion->prepare($sql_user);
                if ($stmt_user) {
                    $stmt_user->bind_param('s', $_SESSION['usuario']);
                    $stmt_user->execute();
                    $result_user = $stmt_user->get_result();
                    if ($row_user = $result_user->fetch_assoc()) {
                        $_SESSION['usuario_id'] = intval($row_user['id']);
                        $_SESSION['tipo_usuario'] = 'profesor';
                    }
                    $stmt_user->close();
                }
            }
            $stmt_user->close();
        }
    }
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

<div class="d-flex" style="min-height: 100vh;">
    <!-- SIDEBAR -->
    <nav id="sidebar" class="shadow <?php echo $sidebar_class; ?>">
        <div class="sidebar-header text-center py-4">
            <h3 class="fw-bold text-white">S.G.E.</h3>
            <small class="text-white-50">U.E.B.N. Juan Pablo Pérez Alfonzo</small>
        </div>
        
        <ul class="list-unstyled components mt-2">
            <?php if ($es_admin): ?>
                <!-- INICIO -->
                <li><a href="/servicio-comunitario/index.php"><i class="fas fa-home me-2"></i> Inicio</a></li>
                
                <!-- ESTUDIANTES -->
                <li><a href="/servicio-comunitario/estudiantes/index.php"><i class="fas fa-user-plus me-2"></i> Estudiantes</a></li>
                
                <!-- ASISTENCIA GLOBAL -->
                <li><a href="/servicio-comunitario/estadisticas/index.php"><i class="fas fa-calendar-check me-2"></i> Asistencia Global</a></li>
                
                <!-- BOLETINES -->
                <li><a href="/servicio-comunitario/boletines/index.php"><i class="fas fa-file-alt me-2"></i> Boletines</a></li>
                
                <!-- RENDIMIENTO FINAL -->
                <li><a href="/servicio-comunitario/rendimientofinal/rendimientofinalindex.php"><i class="fas fa-chart-line me-2"></i> Rendimiento Final</a></li>
                
                <!-- PROFESORES -->
                <li><a href="/servicio-comunitario/profesores/panel_profesor.php"><i class="fas fa-users me-2"></i> Profesores</a></li>
                
                <!-- SEGURIDAD (para super_admin y directiva) -->
                <?php if ($rol_normalizado === 'super_admin' || $rol_normalizado === 'superadmin' || $rol_normalizado === 'directiva'): ?>
                    <li><a href="/servicio-comunitario/profesores/gestionar_permisos.php"><i class="fas fa-shield-alt me-2"></i> Seguridad</a></li>
                <?php endif; ?>
                
            <?php else: ?>
                <li><a href="/servicio-comunitario/index.php"><i class="fas fa-home me-2"></i> Inicio</a></li>
            <?php endif; ?>
            
            <!-- CERRAR SESIÓN -->
            <li class="mt-4"><a href="/servicio-comunitario/logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
        </ul>
    </nav>

    <!-- CONTENIDO PRINCIPAL -->
    <div id="content" style="width: 100%;">
        <nav id="navbar-top" class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3 px-4">
            <button type="button" id="sidebarCollapse" class="btn btn-primary" style="background-color: #002d54;">
                <i class="fas fa-align-left"></i>
            </button>
            <div class="ms-3 fw-bold text-muted">
                <?php echo $panel_titulo; ?>
            </div>
            
            <!-- ========== BOTÓN DE PERIODO ESCOLAR ========== -->
            <div class="ms-auto d-flex align-items-center gap-3">
                <?php if ($es_admin): ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small fw-bold">
                            <i class="fas fa-calendar-alt me-1"></i> Periodo:
                        </span>
                        <span class="badge bg-primary" id="periodo-actual-label">
                            <?= htmlspecialchars($periodo_escolar_actual) ?>
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnCambiarPeriodo" title="Cambiar periodo escolar">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <span class="text-muted small">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <?= htmlspecialchars($periodo_escolar_actual) ?>
                    </span>
                <?php endif; ?>
            </div>
        </nav>
        
        <div class="p-4" id="main-content">