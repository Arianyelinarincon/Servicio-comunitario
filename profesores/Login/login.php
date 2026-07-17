<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// ========== RUTA ABSOLUTA PARA CONEXIÓN ==========
require_once __DIR__ . '/../../config/conexion.php';

// ========== VERIFICAR CONEXIÓN ==========
if (!$conexion || $conexion->connect_error) {
    die("Error de conexión a la base de datos: " . ($conexion->connect_error ?? "variable \$conexion no definida"));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña.';
    } else {
        // ========== CONSULTA A TABLA secretaria ==========
        $sql = "SELECT id, nombre, usuario, password, rol, estatus FROM secretaria WHERE usuario = ?";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            die("Error en la preparación de la consulta: " . $conexion->error);
        }
        
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
            // ========== SI NO ESTÁ EN secretaria, BUSCAR EN profesores ==========
            $sql2 = "SELECT id, nombre, usuario, password, rol, estatus FROM profesores WHERE usuario = ?";
            $stmt2 = $conexion->prepare($sql2);
            if ($stmt2) {
                $stmt2->bind_param("s", $usuario);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if ($row2 = $result2->fetch_assoc()) {
                    if ($row2['estatus'] !== 'Activo') {
                        $error = 'Cuenta inactiva. Contacte al administrador.';
                    } elseif (password_verify($password, $row2['password']) || $password === $row2['password']) {
                        $_SESSION['usuario_id'] = $row2['id'];
                        $_SESSION['usuario'] = $row2['usuario'];
                        $_SESSION['nombre_profesor'] = $row2['nombre'];
                        $_SESSION['rol'] = $row2['rol'];
                        $_SESSION['tipo_usuario'] = 'profesor';
                        
                        header('Location: /servicio-comunitario/index.php');
                        exit();
                    } else {
                        $error = 'Contraseña incorrecta.';
                    }
                } else {
                    $error = 'Usuario no encontrado.';
                }
                $stmt2->close();
            } else {
                $error = 'Error al buscar en profesores.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Educativa - Login</title>
    <!-- ========== SIN CSP PARA EVITAR BLOQUEOS ========== -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e0e0e0 0%, #c0c0c0 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            border-top: 4px solid #003366;
            background: white;
            padding: 30px 35px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 380px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .login-container:hover {
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
        }
        .logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 10px;
        }
        .login-container h2 {
            color: #003366;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 22px;
            letter-spacing: 1px;
        }
        .input-group {
            position: relative;
            margin-bottom: 18px;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-group i {
            position: absolute;
            left: 12px;
            top: 38px;
            color: #777;
            font-size: 16px;
        }
        .input-group input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
            background: #fafafa;
        }
        .input-group input:focus {
            border-color: #003366;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,51,102,0.15);
            background: #ffffff;
        }
        #togglePassword {
            position: absolute;
            right: 12px;
            top: 38px;
            cursor: pointer;
            color: #777;
            font-size: 16px;
            transition: color 0.3s;
        }
        #togglePassword:hover {
            color: #003366;
        }
        .btn-entrar {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #004a99 0%, #003366 100%);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.3s;
            margin-top: 8px;
            letter-spacing: 1px;
        }
        .btn-entrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,51,102,0.3);
        }
        .btn-entrar:active {
            transform: translateY(0);
        }
        .error-msg {
            background: #ffe6e6;
            color: #d8000c;
            padding: 12px 15px;
            margin-bottom: 18px;
            border: 1px solid #d8000c;
            border-radius: 6px;
            font-size: 14px;
            text-align: left;
        }
        .error-msg i {
            margin-right: 8px;
        }
        .footer-text {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
        @media (max-width: 480px) {
            .login-container {
                padding: 20px;
            }
            .logo {
                max-width: 140px;
            }
        }
    </style>
</head>
<body>

    <img src="../../includes/image/logo1.png" alt="Logo" class="logo">
    <div class="login-container">
        <h2><i class="fas fa-school" style="color: #003366; margin-right: 10px;"></i>INICIO DE SESIÓN</h2>

        <?php if ($error): ?>
            <div id="mensaje-error" class="error-msg">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <script>
                // Ocultar automáticamente después de 5 segundos
                setTimeout(function() {
                    var err = document.getElementById('mensaje-error');
                    if (err) {
                        err.style.transition = 'opacity 0.5s ease';
                        err.style.opacity = '0';
                        setTimeout(function() { err.style.display = 'none'; }, 500);
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off">
            <div class="input-group">
                <label for="usuario"><i class="fas fa-user-circle" style="margin-right: 5px;"></i> USUARIO</label>
                <i class="fas fa-user"></i>
                <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autofocus>
            </div>
            <div class="input-group">
                <label for="password"><i class="fas fa-lock" style="margin-right: 5px;"></i> CONTRASEÑA</label>
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                <i class="fas fa-eye" id="togglePassword"></i>
            </div>
            <button type="submit" name="login" class="btn-entrar">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i> ENTRAR
            </button>
        </form>
        <div class="footer-text">
            Sistema de Gestión Educativa &copy; <?= date('Y') ?>
        </div>
    </div>

    <script>
        // ========== MOSTRAR/OCULTAR CONTRASEÑA ==========
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // ========== ENFOQUE AUTOMÁTICO EN EL CAMPO DE USUARIO ==========
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('usuario').focus();
        });
    </script>

</body>
</html>