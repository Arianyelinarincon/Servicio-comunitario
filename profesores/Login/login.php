<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once '../../config/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña.';
    } else {
        $sql = "SELECT id, nombre, usuario, password, rol, estatus FROM secretaria WHERE usuario = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['estatus'] !== 'Activo') {
                $error = 'Cuenta inactiva. Contacte al administrador.';
            } elseif (password_verify($password, $row['password'])) {
                $_SESSION['usuario_id'] = $row['id'];
                $_SESSION['usuario'] = $row['usuario'];
                $_SESSION['nombre_profesor'] = $row['nombre'];
                $_SESSION['rol'] = $row['rol'];
                $_SESSION['tipo_usuario'] = 'secretaria';
                
                header('Location: /servicio-comunitario/index.php');
                exit();
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'Usuario no encontrado.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Gestión Educativa - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background-color: #e0e0e0; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-container { border-top: 4px solid #003366; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); width: 350px; text-align: center; }
        .logo { max-width: 200px; height: auto; margin-bottom: 5px; }
        .input-group { position: relative; margin-bottom: 15px; text-align: left; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .input-group i { position: absolute; left: 10px; top: 38px; color: #555; }
        .input-group input { width: 100%; padding: 10px 35px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        #togglePassword { position: absolute; right: 10px; top: 38px; cursor: pointer; color: #555; }
        .btn-entrar { width: 100%; padding: 10px; background-color: #004a99; border: none; color: white; font-weight: bold; border-radius: 4px; cursor: pointer; }
        .btn-entrar:hover { background-color: #003366; }
        .error-msg { background: #ffe6e6; color: #d8000c; padding: 10px; margin-bottom: 15px; border: 1px solid #d8000c; border-radius: 4px; font-size: 14px; }
    </style>
</head>
<body>

<img src="../../includes/image/logo1.png" alt="Logo" class="logo">
<div class="login-container">
    <h2>INICIO DE SESIÓN</h2>

    <?php if ($error): ?>
        <div id="mensaje-error" class="error-msg"><?= htmlspecialchars($error) ?></div>
        <script>
            setTimeout(function() {
                var err = document.getElementById('mensaje-error');
                err.style.transition = 'opacity 0.5s ease';
                err.style.opacity = '0';
                setTimeout(function() { err.style.display = 'none'; }, 3000);
            }, 3000);
        </script>
    <?php endif; ?>

    <form action="" method="POST">
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