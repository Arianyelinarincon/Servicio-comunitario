<?php
// migrar_passwords.php - EJECUTAR UNA SOLA VEZ Y LUEGO ELIMINAR
require_once 'config/conexion.php';

$sql = "SELECT id, password FROM administradores WHERE LENGTH(password) < 60 OR password NOT LIKE '$2y$%'";
$result = $conexion->query($sql);

if ($result->num_rows === 0) {
    echo "No hay contraseñas por migrar.";
    exit();
}

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $plain = $row['password'];
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $stmt = $conexion->prepare("UPDATE administradores SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $id);
    $stmt->execute();
    $stmt->close();
    echo "Actualizado ID $id: $plain -> $hash<br>";
}
echo "Migración completada. ELIMINA ESTE ARCHIVO.";
?>