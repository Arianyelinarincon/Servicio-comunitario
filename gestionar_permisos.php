<?php
session_start();
require_once 'config/conexion.php'; 

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: Login/login.php");
    exit();
}

// Lógica de cambio de permiso
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $stmt = $conexion->prepare("UPDATE administradores SET permiso_editar_perfil = NOT permiso_editar_perfil WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: gestionar_permisos.php");
    exit();
}

// Consultamos los datos
$profesores = $conexion->query("SELECT id, nombre_profesores, telefono, direccion, permiso_editar_perfil FROM administradores");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Permisos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; }
        .header-top { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { margin: 20px 40px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .search-box { margin-bottom: 25px; }
        .search-box input { width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; background: #000; color: #fff; font-size: 16px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #eee; color: #555; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        
        .btn-toggle { text-decoration: none; padding: 8px 15px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; }
        .activado { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .desactivado { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: inline-block; margin-top: 20px; color: #003366; font-weight: bold; }
    </style>
</head>
<body>

<div class="header-top">
    <h2><i class="fas fa-user-shield"></i> Gestión de Permisos de Edición</h2>
    <a href="index.php" style="color: white; text-decoration: none;"><i class="fas fa-home"></i> Inicio</a>
</div>

<div class="container">
    <div class="search-box">
        <input type="text" id="buscador" onkeyup="filtrarPermisos()" placeholder="🔍 Buscar profesor por nombre o ID...">
    </div>
        
    <table id="tablaPermisos">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Estado del Permiso</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $profesores->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($row['nombre_profesores']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['telefono'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['direccion'] ?? 'N/A'); ?></td>
                <td>
                    <a href="gestionar_permisos.php?toggle_id=<?php echo $row['id']; ?>" 
                       class="btn-toggle <?php echo $row['permiso_editar_perfil'] == 1 ? 'activado' : 'desactivado'; ?>">
                       <?php echo $row['permiso_editar_perfil'] == 1 ? "✅ Activado" : "❌ Desactivado"; ?>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
</div>

<script>
function filtrarPermisos() {
    let input = document.getElementById("buscador");
    let filter = input.value.toUpperCase();
    let table = document.getElementById("tablaPermisos");
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