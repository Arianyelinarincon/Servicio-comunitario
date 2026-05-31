<?php
// auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    // Si no hay sesión, los sacas a patadas al login
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}
?>