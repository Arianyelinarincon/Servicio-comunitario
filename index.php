<?php
session_start();

$nombre_profesor = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Administrador';


// Ahora (permite acceso si eres Super Admin O Administrador)
if ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'super_admin') { 
    header("Location: Login/login.php"); 
    exit(); 
}  


$nombre_usuario = $_SESSION['nombre_profesor'] ?? 'super_admin';
// INICIO DE LA INTEGRACIÓN
include('includes/header.php'); // <--- Llamamos al nuevo header
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - UEBN Juan Pablo Pérez Alfonzo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* Encabezado Profesional */
        .header-admin { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header-admin a { color: #ffcccc; text-decoration: none; font-weight: bold; }

        .container { flex: 1; padding: 40px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .content-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        /* Tarjetas de Acción */
        .grid-acciones { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 40px; }
        .btn-accion { background: #fff; border: 2px solid #eef2f7; padding: 40px; border-radius: 15px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s ease; display: block; }
        .btn-accion:hover { border-color: #003366; transform: translateY(-10px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .btn-accion i { font-size: 3.5em; margin-bottom: 20px; display: block; }
        
        /* Colores */
        .btn-add { border-top: 5px solid #28a745; }
        .btn-add i { color: #28a745; }
        .btn-update { border-top: 5px solid #ffc107; }
        .btn-update i { color: #ffc107; }
        .btn-delete { border-top: 5px solid #dc3545; }
        .btn-delete i { color: #dc3545; }
    </style>
</head>
<body>

    <div class="container">
        <div class="content-box">
            <h1>Gestión de Personal Docente</h1>
            <p>Desde aquí puedes administrar el acceso y la información de los profesores del sistema.</p>

            <div class="grid-acciones">
                <a href="profesores/login/agregar_profesor.php" class="btn-accion btn-add">
                    <i class="fas fa-user-plus"></i>
                    <h3>Agregar Profesor</h3>
                </a>
                <a href="profesores/login/actualizar_profesor.php" class="btn-accion btn-update">
                    <i class="fas fa-user-edit"></i>
                    <h3>Actualizar Info</h3>
                </a>
                <a href="profesores/login/eliminar_profesor.php" class="btn-accion btn-delete">
                    <i class="fas fa-user-minus"></i>
                    <h3>Eliminar Profesor</h3>
                </a>

                <a href="gestionar_permisos.php" class="btn-accion" style="border-top: 5px solid #6f42c1;">
                    <i class="fas fa-user-shield" style="color: #6f42c1;"></i>
                    <h3>Control de Permisos</h3>
                </a>
            </div>
        </div>
    </div>

     <?php include('includes/footer.php'); // <--- Llamamos al footer para los scripts ?>

</body>
</html>