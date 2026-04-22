<?php
session_start();


if (!isset($_SESSION['usuario'])) {
    header("Location: Login/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Principal - UNEFA</title>
    <link rel="stylesheet" href="Login/Estilo/estilo.css"> <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; padding: 50px; }
        .welcome-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); display: inline-block; }
        .btn-logout { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #d9534f; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>

    <div class="welcome-box">
        <h1>¡Bienvenido, <?php echo $_SESSION['usuario']; ?>!</h1>
        <p>Has ingresado correctamente al Sistema de Gestión Educativa.</p>
        <p>Proyecto de Servicio Comunitario - UNEFA</p>
        
        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>

</body>
</html>