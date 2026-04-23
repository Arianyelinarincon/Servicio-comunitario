<?php
// estadisticas/config_db.php
// Conexión central para el módulo de estadísticas

// Ruta absoluta desde la raíz del proyecto (subir un nivel)
$root_path = dirname(__DIR__); // desde estadisticas/ -> Servicio-comunitario/

// Incluir el archivo original de conexión que está en ../config/conexion.php
require_once $root_path . '/config/conexion.php';

// Verificar que la conexión existe
if (!isset($conexion) || $conexion->connect_error) {
    die("Error de conexión a la base de datos: " . ($conexion->connect_error ?? "Variable \$conexion no definida"));
}

// Establecer charset
$conexion->set_charset("utf8mb4");

// Opcional: descomentar para depuración
// echo "Conexión exitosa a la base de datos";
?>