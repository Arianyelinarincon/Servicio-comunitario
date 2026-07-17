<?php
// estadisticas/config_db.php
// Ruta absoluta desde la raíz del proyecto
$root_path = dirname(__DIR__);
require_once $root_path . '/config/conexion.php';

if (!isset($conexion) || $conexion->connect_error) {
    die("Error de conexión a la base de datos: " . ($conexion->connect_error ?? "Variable \$conexion no definida"));
}

$conexion->set_charset("utf8mb4");
?>