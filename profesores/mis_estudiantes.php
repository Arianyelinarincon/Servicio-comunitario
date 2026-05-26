<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('../config/conexion.php');

if (!isset($_SESSION['sala'])) {
    die("Error: No se ha definido la sala.");
}

$sala_profesor = $_SESSION['sala'];
$sql = "SELECT * FROM estudiantes WHERE sala = ? AND estatus = 'Activo'";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $sala_profesor);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Estudiantes - <?php echo $sala_profesor; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-bottom: 20px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #0056b3; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background-color: #f1f1f1; }
        .badge { padding: 5px 10px; border-radius: 4px; background: #e9ecef; font-size: 0.85em; }
    </style>
</head>
<body>

<div class="container">
    <h2>Estudiantes Asignados a la <?php echo $sala_profesor; ?></h2>
    
    <table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Género</th>
                <th>F. Nacimiento</th>
                <th>Representante</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($resultado->num_rows > 0) {
                while($row = $resultado->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $row['cedula'] . "</td>
                            <td>" . $row['nombre'] . "</td>
                            <td>" . $row['apellido'] . "</td>
                            <td>" . $row['genero'] . "</td>
                            <td>" . $row['fecha_nacimiento'] . "</td>
                            <td>" . $row['rep_nombre'] . "</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>No hay estudiantes registrados en esta sala.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>