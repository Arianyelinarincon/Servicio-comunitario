<?php
session_start();

// 1. Validar seguridad: Si no hay sesión activa, mandarlo al login interno
if (!isset($_SESSION['usuario'])) {
    header("Location: login/login.php");
    exit();
}

// Capturar el rol y usuario
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'profesor';
$nombre_usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Profesores - UEBN Juan Pablo Pérez Alfonzo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }
        .welcome-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        h1 { color: #333; margin-bottom: 5px; font-size: 28px; }
        p { color: #666; margin-top: 5px; }
        .grid-acciones {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        .btn-accion {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-accion i { margin-bottom: 10px; }
        .btn-accion:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-add { background-color: #28a745; }
        .btn-update { background-color: #ffc107; color: #333; }
        .btn-delete { background-color: #dc3545; }
        .btn-logout {
            display: inline-block;
            margin-top: 20px;
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-logout:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="welcome-box">
        <h1>¡Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?>!</h1>
        <p>Has ingresado correctamente al Módulo de Profesores (UNEFA).</p>
        
        <?php if ($rol === 'superadmin'): ?>
            <div style="color: #004b93; font-weight: bold; margin-top: 20px;">
                <i class="fas fa-shield-alt"></i> Panel Administrativo Activo
            </div>
            
            <div class="grid-acciones">
                <a href="login/agregar_profesor.php" class="btn-accion btn-add">
                    <i class="fas fa-user-plus fa-2x"></i> Agregar Profesor
                </a>
                <a href="login/actualizar_profesor.php" class="btn-accion btn-update">
                    <i class="fas fa-user-edit fa-2x"></i> Actualizar Info
                </a>
                <a href="login/eliminar_profesor.php" class="btn-accion btn-delete">
                    <i class="fas fa-user-minus fa-2x"></i> Eliminar (Inactivar)
                </a>
            </div>
        <?php else: ?>
            <p style="margin-top: 25px; font-style: italic;">
                <i class="fas fa-info-circle"></i> Vista de docente. Puedes gestionar las notas y asistencias asignadas.
            </p>
        <?php endif; ?>

        <br>
        <a href="login/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>

</body>
</html>