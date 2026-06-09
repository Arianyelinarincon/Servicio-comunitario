<?php
// migrar_passwords.php - CORREGIDO para tabla profesores
// EJECUTAR UNA SOLA VEZ Y LUEGO ELIMINAR

require_once 'config/conexion.php';

// Seleccionar todos los usuarios que tienen contraseña en texto plano
// (asumimos que las contraseñas hasheadas empiezan con '$2y$' y tienen más de 20 caracteres)
$sql = "SELECT id, password FROM profesores WHERE LENGTH(password) < 60 OR password NOT LIKE '$2y$%'";
$result = $conexion->query($sql);

if ($result->num_rows === 0) {
    echo "No hay contraseñas por migrar.<br>";
} else {
    $actualizadas = 0;
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $plain = $row['password'];
        // Generar hash
        $hash = password_hash($plain, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("UPDATE profesores SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $id);
        if ($stmt->execute()) {
            $actualizadas++;
            echo "Actualizado ID $id: '$plain' -> '$hash'<br>";
        } else {
            echo "Error actualizando ID $id: " . $stmt->error . "<br>";
        }
        $stmt->close();
    }
    echo "<hr><strong>Migración completada. Se actualizaron $actualizadas contraseñas.</strong><br>";
}

echo "<br><strong style='color:red'>¡ELIMINA ESTE ARCHIVO AHORA MISMO!</strong>";
?>