<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once('../config/conexion.php');

// MODIFICACIÓN: Se añade el WHERE para excluir administradores y mostrar solo profesores
$sql = "SELECT * FROM administradores WHERE rol = 'profesor' ORDER BY id ASC";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Personal - UEBN</title>
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
        
        .badge { padding: 5px 10px; border-radius: 4px; color: white; font-weight: bold; text-transform: uppercase; font-size: 0.8em; }
        .bg-activo { background: #28a745; }
        .bg-inactivo { background: #dc3545; }
        
        a { color: #003366; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="header-admin">
    <div><h2><i class="fas fa-users-cog"></i> Gestión de Docentes</h2></div>
    <a href="panel_profesor.php" style="color: white; text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
</div>

<div class="container">
    <div class="content-box">
        <div class="search-container">
            <input type="text" id="buscador" class="search-input" placeholder="Buscar docente por nombre o cédula..." onkeyup="filtrarTabla()">
        </div>

        <table id="tablaPersonal">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cédula</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($resultado->num_rows > 0) {
                    $contador = 1; // 1. Inicializamos un contador manual
                    while($row = $resultado->fetch_assoc()) {
                        $estatusClass = ($row['estatus'] == 'Activo') ? 'bg-activo' : 'bg-inactivo';
                        echo "<tr>
                                <td>" . $contador . "</td> <td>" . htmlspecialchars($row['cedula'] ?? 'N/A') . "</td>
                                <td><a href='detalle_profesor.php?id=" . $row['id'] . "'>" . htmlspecialchars($row['nombre_profesores']) . "</a></td>
                                <td>" . htmlspecialchars($row['usuario'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['rol']) . "</td>
                                <td><span class='badge $estatusClass'>" . htmlspecialchars($row['estatus']) . "</span></td>
                              </tr>";
                        $contador++; // 3. Incrementamos el contador por cada fila
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center;'>No hay docentes registrados.</td></tr>";
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
    let table = document.getElementById("tablaPersonal");
    let tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        let tdCedula = tr[i].getElementsByTagName("td")[1];
        let tdNombre = tr[i].getElementsByTagName("td")[2];
        
        if (tdCedula || tdNombre) {
            let valCedula = tdCedula.textContent || tdCedula.innerText;
            let valNombre = tdNombre.textContent || tdNombre.innerText;
            
            if (valCedula.toUpperCase().indexOf(filter) > -1 || valNombre.toUpperCase().indexOf(filter) > -1) {
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