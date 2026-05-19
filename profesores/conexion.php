<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "control_estudios_uebn (1)"; 

$conexion = mysqli_connect($host, $user, $pass, $db);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>