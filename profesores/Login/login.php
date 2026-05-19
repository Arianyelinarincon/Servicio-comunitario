<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Educativa - Login</title>
    
    <link rel="stylesheet" href="Estilo//estilo.css">
</head>
<body>

    <div class="header-title">
        <h1>SISTEMA DE GESTIÓN EDUCATIVA - UNEFA</h1>
        <p>SERVICIO COMUNITARIO</p>
    </div>

    <div class="login-container">
        <img src="Estilo/image//descarga.png" alt="Logo" class="logo"">
        
        <h2>INICIO DE SESIÓN</h2>

        <form action="../procesos.php" method="POST">
            <label for="usuario">USUARIO</label>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autocomplete="off">
            </div>
            
            <label for="password">CONTRASEÑA</label>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" name="login" class="btn-entrar">ENTRAR</button>
        </form>
    </div>

    <div class="footer-credits">
        Desarrollado por Ingenieros de Sistemas UNEFA - Servicio Comunitario
    </div>

</body>
</html>