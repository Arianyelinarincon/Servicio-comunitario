<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Gestión Educativa - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background-color: #e0e0e0; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .header-title { text-align: center; margin-bottom: 20px; }
        .login-container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); width: 350px; text-align: center; }
        .logo { max-width: 300px; height: auto; margin-bottom: 15px; }
        .input-group { position: relative; margin-bottom: 15px; text-align: left; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .input-group i.fa-user, .input-group i.fa-lock { position: absolute; left: 10px; top: 38px; color: #555; }
        .input-group input { width: 100%; padding: 10px 35px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        #togglePassword { position: absolute; right: 10px; top: 38px; cursor: pointer; color: #555; }
        .btn-entrar { width: 100%; padding: 10px; background-color: #004a99; border: none; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; }
        .btn-entrar:hover { background-color: #003366; }
    </style>
</head>
<body>

<div class="header-title">
    <h1>SISTEMA DE GESTIÓN EDUCATIVA - UNEFA</h1>
    <p>SERVICIO COMUNITARIO</p>
</div>

<div class="login-container">
    <img src="../../includes/image/logo1.png" alt="Logo" class="logo">
    <h2>INICIO DE SESIÓN</h2>

    <?php
    if (isset($_GET['error'])) {
        $mensaje = "";
        switch ($_GET['error']) {
            case 'usuario_incorrecto': $mensaje = "Usuario incorrecto."; break;
            case 'clave_incorrecta':   $mensaje = "Contraseña incorrecta."; break;
            case 'inactivo':           $mensaje = "Cuenta inactiva. Contacte al administrador."; break;
            default:                   $mensaje = "Error al ingresar.";
        }
        echo "<div id='mensaje-error' style='background: #ffe6e6; color: #d8000c; padding: 10px; margin-bottom: 15px; border: 1px solid #d8000c; border-radius: 4px; font-size: 14px; transition: opacity 0.5s ease;'>
                $mensaje
              </div>";
        echo "<script>
                setTimeout(function() {
                    var err = document.getElementById('mensaje-error');
                    err.style.opacity = '0';
                    setTimeout(function() { err.style.display = 'none'; }, 500);
                }, 3000);
              </script>";
    }
    ?>

    <form action="procesos.php" method="POST">
        <div class="input-group">
            <label for="usuario">USUARIO</label>
            <i class="fas fa-user"></i>
            <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autocomplete="off">
        </div>
        
        <div class="input-group">
            <label for="password">CONTRASEÑA</label>
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="off">
            <i class="fas fa-eye" id="togglePassword"></i>
        </div>
        
        <button type="submit" name="login" class="btn-entrar">ENTRAR</button>
    </form>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>
</body>
</html>