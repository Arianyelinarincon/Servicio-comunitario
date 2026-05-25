<?php
session_start();
// Validación estricta: solo profesores
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente - UNEFA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; margin: 0; padding: 40px; display: flex; justify-content: center; }
        .container { max-width: 900px; width: 100%; }
        
        .header-docente { background: #003366; color: white; padding: 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .content-box { background: white; padding: 40px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .grid-acciones { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 30px; }
        .btn-accion { background: #f8f9fa; border: 1px solid #dee2e6; padding: 30px; border-radius: 8px; text-decoration: none; color: #333; text-align: center; transition: 0.3s; }
        .btn-accion:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: #003366; }
        .btn-accion i { font-size: 2.5em; margin-bottom: 15px; display: block; }
        
        .btn-estudiantes i { color: #007bff; }
        .btn-boletines i { color: #6f42c1; }
        
        .logout-link { color: #ff4d4d; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-docente">
            <div>
                <h2><i class="fas fa-chalkboard-teacher"></i> Panel Docente</h2>
            </div>
            <div>
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['usuario']); ?></span> | 
                <a href="/Servicio-comunitario/profesores/logout.php" class="logout-link">Cerrar Sesión</a>
            </div>
        </div>

        <div class="content-box">
            <h1>Bienvenido(a), Profesor(a)</h1>
            <p>Desde este panel puedes gestionar la información académica de tus secciones asignadas.</p>

            <div class="grid-acciones">
                <a href="#" class="btn-accion btn-estudiantes">
                    <i class="fas fa-user-graduate"></i> Mis Estudiantes
                </a>
                <a href="#" class="btn-accion btn-boletines">
                    <i class="fas fa-file-alt"></i> Gestión de Boletines
                </a>
            </div>
        </div>
    </div>

</body>
</html>