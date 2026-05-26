<?php
// 1. Iniciar la sesión para poder manipularla
session_start();

// 2. Vaciar el arreglo de sesión (eliminar todas las variables)
$_SESSION = array();

// 3. Destruir la cookie de la sesión (si existe)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión en el servidor
session_destroy();

// 5. Redirigir al usuario a la página de login
header("Location: /Servicio-comunitario/profesores/Login/login.php");
exit();
?>