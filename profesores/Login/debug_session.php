<?php
session_start();
echo "<h2>Depuración de Sesión</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<h3>Verificación de usuario 'directiva' en BD</h3>";

require_once '../config/conexion.php';

$sql = "SELECT id, nombre, usuario, rol, estatus FROM secretaria WHERE usuario = 'directiva'";
$result = $conexion->query($sql);
$row = $result->fetch_assoc();

echo "<pre>";
print_r($row);
echo "</pre>";

if ($row && $row['rol'] === 'super_admin') {
    echo "<h3 style='color:green;'>✅ El usuario 'directiva' tiene rol 'super_admin' en la base de datos.</h3>";
} else {
    echo "<h3 style='color:red;'>❌ El rol de 'directiva' es: " . ($row['rol'] ?? 'NULL') . "</h3>";
}
?>