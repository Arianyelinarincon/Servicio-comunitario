<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "control_estudios_uebn";
$port = 3306;

$conexion = mysqli_connect($host, $user, $pass, $db, $port);

// Definimos la ruta base del proyecto
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/Servicio-comunitario/');
}
?>