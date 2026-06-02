<?php
session_start();
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

include_once('../../config/conexion.php');
$mensaje = "";

// Lógica para inactivar (Apuntando a la tabla 'administradores')
if (isset($_GET['inactivar_id'])) {
    $id_profesor = intval($_GET['inactivar_id']);
    $sql_update = "UPDATE administradores SET estatus = 'Inactivo' WHERE id = ?";
    $stmt = $conexion->prepare($sql_update);
    $stmt->bind_param("i", $id_profesor);
    
    if ($stmt->execute()) {
        $mensaje = "<div class='alert success'><i class='fas fa-check-circle'></i> Estado actualizado correctamente.</div>";
    } else {
        $mensaje = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error al procesar la solicitud.</div>";
    }
}

// Consulta actualizada a la tabla 'administradores'
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
        body { font-family: 'Segoe UI', sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        .header-admin { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { flex: 1; padding: 40px; max-width: 1100px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .content-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #ddd; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-activo { background-color: #d4edda; color: #155724; }
        .badge-inactivo { background-color: #f8d7da; color: #721c24; }
        .btn-action { background: #dc3545; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <div class="header-admin">
        <h2><i class="fas fa-users"></i> Gestión de Docentes</h2>
        <a href="../../index.php" style="color: white; text-decoration: none;"><i class="fas fa-home"></i> Inicio</a>
    </div>

    <div class="container">
        <div class="content-box">
            <?php echo $mensaje; ?>
            <table>
                <thead>
                    <tr>
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
                                    <a href="?inactivar_id=<?php echo $row['id']; ?>" class="btn-action" onclick="return confirm('¿Inactivar docente?');">
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
    </div>
</body>
</html>