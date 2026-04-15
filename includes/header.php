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
            <li><a href="../index.php"><i class="fas fa-home me-2"></i> Inicio</a></li>
            <li><a href="../inscripcion/index.php"><i class="fas fa-user-plus me-2"></i> Inscripción</a></li>
            <li><a href="../matricula/index.php"><i class="fas fa-address-book me-2"></i> Matrícula</a></li>
            <li class="active-item"><a href="../estadisticas/index.php"><i class="fas fa-chart-bar me-2"></i> Estadísticas</a></li>
            <li><a href="../usuario/index.php"><i class="fas fa-users me-2"></i> Usuario</a></li>
            <li><a href="../auditoria/index.php"><i class="fas fa-clipboard-list me-2"></i> Auditoría</a></li>
            <li><a href="../seguridad/index.php"><i class="fas fa-shield-alt me-2"></i> Seguridad</a></li>
            <li class="mt-4"><a href="../../logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Salir</a></li>
        </ul>
    </nav>

    <div id="content" style="width: 100%;">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3 px-4">
            <button type="button" id="sidebarCollapse" class="btn btn-primary" style="background-color: #002d54;">
                <i class="fas fa-align-left"></i>
            </button>
            <div class="ms-3 fw-bold text-muted">PANEL DE ADMINISTRACIÓN</div>
        </nav>
        
        <div class="p-4">