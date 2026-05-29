<?php
session_start();
// 1. Incluir conexión (ajusta la ruta según tu estructura de carpetas)
require_once __DIR__ . '/../config/conexion.php'; 

// Validación de seguridad
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

// 2. Obtener el estado del permiso desde la base de datos
$id_profesor = $_SESSION['id_usuario'];
$query = $conexion->prepare("SELECT permiso_editar_perfil FROM profesores WHERE id = ?");
$query->bind_param("i", $id_profesor);
$query->execute();
$resultado = $query->get_result();
$datos_profesor = $resultado->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente - UNEFA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        .header-docente { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header-docente a { color: #ffcccc; text-decoration: none; font-weight: bold; }
        .container { flex: 1; padding: 40px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .content-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .grid-acciones { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 40px; }
        .btn-accion { background: #fff; border: 2px solid #eef2f7; padding: 40px; border-radius: 15px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s ease; display: block; }
        .btn-accion:hover { border-color: #003366; transform: translateY(-10px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .btn-accion i { font-size: 3.5em; margin-bottom: 20px; display: block; }
        .btn-estudiantes i { color: #007bff; }
        .btn-boletines i { color: #6f42c1; }
        .btn-perfil i { color: #28a745; }
    </style>
</head>
<body>

    <div class="header-docente">
        <div><h2><i class="fas fa-chalkboard-teacher"></i> Sistema Docente</h2></div>
        <div>
            <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre_profesor'] ?? 'Profesor'); ?></span> | 
            <a href="../logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <div class="content-box">
            <h1>Bienvenido Docente</h1>
            <p>Selecciona una de las opciones a continuación para gestionar tus responsabilidades académicas.</p>

            <div class="grid-acciones">
                <a href="mis_estudiantes.php" class="btn-accion btn-estudiantes">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Mis Estudiantes</h3>
                </a>
                
                <a href="../boletines" class="btn-accion btn-boletines">
                    <i class="fas fa-file-alt"></i>
                    <h3>Gestión de Boletines</h3>
                </a>
                
                <?php if ($datos_profesor && $datos_profesor['permiso_editar_perfil'] == 1): ?>
                    <a href="actualizar_perfil.php" class="btn-accion btn-perfil">
                        <i class="fas fa-user-edit"></i>
                        <h3>Actualizar Perfil</h3>
                    </a>
                <?php else: ?>
                    <div class="btn-accion" style="opacity: 0.6; cursor: not-allowed;">
                        <i class="fas fa-user-lock"></i>
                        <h3>Perfil Bloqueado</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>