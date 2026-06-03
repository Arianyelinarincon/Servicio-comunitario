<?php
session_start();
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once('../config/conexion.php');

$sql = "SELECT * FROM estudiantes ORDER BY sala, nombre, apellido";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Estudiantes - UEBN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        .header-admin { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { flex: 1; padding: 20px; width: 95%; margin: 0 auto; box-sizing: border-box; }
        .content-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        .search-container { margin-bottom: 20px; }
        .search-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 16px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9em; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #ddd; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        tr:hover { background-color: #f9f9f9; }
        .badge { padding: 5px 10px; border-radius: 4px; color: white; font-weight: bold; }
        .bg-activo { background: #28a745; }
    </style>
</head>
<body>

<div class="header-admin">
    <div><h2><i class="fas fa-user-graduate"></i> Listado General de Estudiantes</h2></div>
    <a href="panel_profesor.php" style="color: white; text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="container">
    <div class="content-box">
        <div class="search-container">
            <input type="text" id="buscador" class="search-input" placeholder="Buscar estudiante por nombre o apellido..." onkeyup="filtrarTabla()">
        </div>

        <table id="tablaEstudiantes">
            <thead>
                <tr>
                    <th>N°</th> <th>Cédula</th>
                    <th>Nombre y Apellido</th>
                    <th>Género</th>
                    <th>F. Nacimiento</th>
                    <th>Sala/Grado</th>
                    <th>Representante</th>
                    <th>Estatus</th>
                    <th>Modificación</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($resultado->num_rows > 0) {
                    $contador = 1; // Inicializamos el contador
                    while($row = $resultado->fetch_assoc()) {
                        echo "<tr>
                        <td>" . $contador . "</td>
                        <td>" . htmlspecialchars($row['cedula'] ?? 'N/A') . "</td>
                        <td><strong>" . htmlspecialchars($row['nombre'] . " " . $row['apellido']) . "</strong></td>
                        <td>" . htmlspecialchars($row['genero']) . "</td>
                        <td>" . htmlspecialchars($row['fecha_nacimiento']) . "</td>
                        <td>" . htmlspecialchars($row['sala']) . "</td>
                        <td>" . htmlspecialchars($row['rep_nombre'] ?? 'N/A') . "</td>
                        <td><span class='badge bg-activo'>" . htmlspecialchars($row['estatus']) . "</span></td>
                        <td>
                        <a href='editar_estudiantes.php?id=" . $row['id'] . "' style='background:#003366; color:white; padding:5px 10px; border-radius:4px; text-decoration:none; font-size:12px;'>
                            <i class='fas fa-edit'></i> Editar
                        </a>
                    </td>
                </tr>";
                        $contador++; // Incrementamos el contador
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center;'>No hay estudiantes registrados.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filtrarTabla() {
    let input = document.getElementById("buscador");
    let filter = input.value.toUpperCase();
    let table = document.getElementById("tablaEstudiantes");
    let tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        // Buscamos en la columna 2, que sigue siendo Nombre y Apellido
        let td = tr[i].getElementsByTagName("td")[2]; 
        if (td) {
            let txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

</body>
</html>