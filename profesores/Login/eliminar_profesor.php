<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

include_once('../../config/conexion.php');
$mensaje = "";

if (isset($_GET['inactivar_id'])) {
    $id_profesor = intval($_GET['inactivar_id']);
    $sql_delete = "UPDATE profesores SET estatus = 'Inactivo' WHERE id = ?";
    $stmt = $conexion->prepare($sql_delete);
    $stmt->bind_param("i", $id_profesor);
    
    if ($stmt->execute()) {
        $mensaje = "<div class='alert success'><i class='fas fa-check-circle'></i> Profesor dado de baja correctamente.</div>";
    } else {
        $mensaje = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error al procesar la solicitud.</div>";
    }
}

$sql_profesores = "SELECT p.id, p.nombre, s.nombre AS seccion_nombre, p.sala 
                   FROM profesores p 
                   LEFT JOIN secciones s ON p.seccion = s.id 
                   WHERE p.estatus = 'Activo' AND p.rol != 'administrador'";
$resultado = $conexion->query($sql_profesores);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inactivar Docentes - UEBN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        
        .header-admin { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { flex: 1; padding: 40px; max-width: 1000px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .content-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #ddd; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        
        .btn-action-delete { background: #dc3545; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; }
        .btn-action-delete:hover { background: #c82333; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <div class="header-admin">
        <div><h2><i class="fas fa-user-minus"></i> Gestión de Bajas</h2></div>
        <a href="../../index.php" style="color: white; text-decoration: none;"><i class="fas fa-home"></i> Inicio</a>
    </div>

    <div class="container">
        <div class="content-box">
            <p>Selecciona el docente para inhabilitar su acceso al sistema de forma inmediata.</p>
            <?php echo $mensaje; ?>

            <table>
                <thead>
                    <tr>
                        <th>Nombre del Docente</th>
                        <th>Grado/Sala</th>
                        <th>Sección</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['sala']); ?></td>
                                <td><?php echo htmlspecialchars($row['seccion_nombre'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="eliminar_profesor.php?inactivar_id=<?php echo $row['id']; ?>" 
                                       class="btn-action-delete" 
                                       onclick="return confirm('¿Confirmas que deseas deshabilitar a este profesor?');">
                                       <i class="fas fa-ban"></i> Inactivar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #999;">No hay profesores activos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>