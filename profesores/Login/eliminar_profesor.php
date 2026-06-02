<?php
session_start();
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

include_once('../../config/conexion.php');
$mensaje = "";

// Lógica para inactivar
if (isset($_GET['inactivar_id'])) {
    $id_profesor = intval($_GET['inactivar_id']);
    $sql_update = "UPDATE administradores SET estatus = 'Inactivo' WHERE id = ?";
    $stmt = $conexion->prepare($sql_update);
    $stmt->bind_param("i", $id_profesor);
    
    if ($stmt->execute()) {
        $mensaje = "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:6px; margin-bottom:20px;'><i class='fas fa-check-circle'></i> Estado actualizado correctamente.</div>";
    } else {
        $mensaje = "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:6px; margin-bottom:20px;'><i class='fas fa-exclamation-triangle'></i> Error al procesar.</div>";
    }
}

$sql_profesores = "SELECT p.id, p.nombre_profesores, s.nombre AS seccion_nombre, p.sala, p.estatus 
                   FROM administradores p 
                   LEFT JOIN secciones s ON p.seccion = s.id";
$resultado = $conexion->query($sql_profesores);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Docentes - UEBN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; }
        .header-top { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        
        /* Contenedor expandido */
        .container { margin: 20px 40px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .search-box { margin-bottom: 25px; }
        .search-box input { width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; background: #000; color: #fff; font-size: 16px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #eee; color: #555; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        
        .badge { padding: 5px 12px; border-radius: 4px; color: white; font-size: 0.85em; font-weight: bold; }
        .badge-activo { background-color: #28a745; }
        .badge-inactivo { background-color: #dc3545; }
        .btn-action { background: #dc3545; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

<div class="header-top">
    <h2><i class="fas fa-users"></i> Gestión de Docentes (Inactivación)</h2>
    <a href="../../index.php" style="color: white; text-decoration: none;"><i class="fas fa-home"></i> Inicio</a>
</div>

<div class="container">
    <?php echo $mensaje; ?>
    
    <div class="search-box">
        <input type="text" id="buscador" onkeyup="filtrarProfesores()" placeholder="🔍 Buscar docente por nombre o ID...">
    </div>

    <table id="tablaProfesores">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Sala</th>
                <th>Sección</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($row['nombre_profesores']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['sala']); ?></td>
                <td><?php echo htmlspecialchars($row['seccion_nombre'] ?? 'N/A'); ?></td>
                <td>
                    <span class="badge <?php echo ($row['estatus'] == 'Activo') ? 'badge-activo' : 'badge-inactivo'; ?>">
                        <?php echo htmlspecialchars($row['estatus']); ?>
                    </span>
                </td>
                <td>
                    <?php if ($row['estatus'] == 'Activo'): ?>
                        <a href="?inactivar_id=<?php echo $row['id']; ?>" class="btn-action" onclick="return confirm('¿Inactivar a este docente?');">
                            <i class="fas fa-ban"></i> Inactivar
                        </a>
                    <?php else: ?>
                        <span style="color:#999; font-size:12px;">N/A</span>
                    <?php endif; ?>
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
            if (txtValue.toUpperCase().indexOf(filter) > -1 || idValue.indexOf(filter) > -1) {
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