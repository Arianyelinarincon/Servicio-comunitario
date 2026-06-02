<?php
session_start();
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

include_once('../../config/conexion.php');

// Consulta de lista
$sql_lista = "SELECT id, nombre_profesores, sala, estatus FROM administradores";
$resultado_lista = $conexion->query($sql_lista);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Docente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; }
        .header-top { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { margin: 20px 40px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .search-box { margin-bottom: 25px; }
        .search-box input { width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; background-color: #000; color: #fff; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #eee; color: #555; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .badge { padding: 5px 12px; border-radius: 4px; color: white; font-size: 0.85em; font-weight: bold; text-transform: uppercase; }
        .bg-activo { background: #28a745; }
        .bg-inactivo { background: #dc3545; }
    </style>
</head>
<body>

<div class="header-top">
    <h2><i class="fas fa-user-edit"></i> Actualizar Docentes</h2>
    <a href="../../index.php" style="color: white; text-decoration: none;"><i class="fas fa-home"></i> Inicio</a>
</div>

<div class="container">
    <div class="search-box">
        <input type="text" id="buscador" onkeyup="filtrarProfesores()" placeholder="Buscar docente por nombre o cédula...">
    </div>

    <table id="tablaProfesores">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Sala</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $resultado_lista->fetch_assoc()): 
                // Usamos strtolower para comparar sin importar si está en mayúsculas o minúsculas
                $estado = trim($row['estatus']);
                $esActivo = (strtolower($estado) == 'activo');
            ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['nombre_profesores']); ?></td>
                <td><?php echo htmlspecialchars($row['sala']); ?></td>
                <td>
                    <span class="badge <?php echo $esActivo ? 'bg-activo' : 'bg-inactivo'; ?>">
                        <?php echo htmlspecialchars($estado); ?>
                    </span>
                </td>
                <td>
                    <a href="actualizar_profesor.php?editar_id=<?php echo $row['id']; ?>" style="color:#003366; font-weight:bold;">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function filtrarProfesores() {
    let input = document.getElementById("buscador");
    let filter = input.value.toUpperCase();
    let table = document.getElementById("tablaProfesores");
    let tr = table.getElementsByTagName("tr");
    for (let i = 1; i < tr.length; i++) {
        let tdId = tr[i].getElementsByTagName("td")[0];
        let tdNombre = tr[i].getElementsByTagName("td")[1];
        if (tdNombre || tdId) {
            let txtValue = tdNombre.textContent || tdNombre.innerText;
            let idValue = tdId.textContent || tdId.innerText;
            tr[i].style.display = (txtValue.toUpperCase().indexOf(filter) > -1 || idValue.indexOf(filter) > -1) ? "" : "none";
        }
    }
}
</script>
</body>
</html>