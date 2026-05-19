<?php
// Usando la conexión de la carpeta config 
include_once "../config/conexion.php";

if (isset($_POST['registrar'])) {
    // Recibir datos del estudiante
    $nombre = strtoupper($_POST['nombre']);
    $cedula = $_POST['cedula'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $genero = $_POST['genero'];
    $sala = $_POST['sala'];
    $alergias_condiciones = $_POST['alergias_condiciones'];
    
    // Recibir datos del representante
    $rep_nombre = strtoupper($_POST['rep_nombre']);
    $rep_cedula = $_POST['rep_cedula'];
    $rep_telefono = $_POST['rep_telefono'];

    // 1. VALIDACIÓN: Buscar si la cédula ya existe 
    $query_verificar = "SELECT * FROM estudiantes WHERE cedula = '$cedula'";
    $resultado_verificar = mysqli_query($conexion, $query_verificar);

    if (mysqli_num_rows($resultado_verificar) > 0) {
        // La cédula ya existe, devolver con error
        header("Location: gestion.php?error=duplicado");
        exit();
    } else {
        // 2. INSERCIÓN: Si no existe, guardar en la base de datos
        $query_insert = "INSERT INTO estudiantes (nombre, cedula, fecha_nacimiento, genero, sala, alergias_condiciones, rep_nombre, rep_cedula, rep_telefono, estatus) 
                         VALUES ('$nombre', '$cedula', '$fecha_nacimiento', '$genero', '$sala', '$alergias_condiciones', '$rep_nombre', '$rep_cedula', '$rep_telefono', 'Activo')";
        
        if (mysqli_query($conexion, $query_insert)) {
            // Éxito: Redirigir al listado principal
            header("Location: index.php?mensaje=registrado");
        } else {
            // Error en BD
            echo "Error: " . mysqli_error($conexion);
        }
    }
}
?>