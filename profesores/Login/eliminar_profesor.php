<?php
session_start();
// Verificar que solo el Super Admin pueda entrar a este archivo
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos (subiendo dos niveles para llegar a config/)
include_once('../../config/conexion.php');

$mensaje = "";

// PROCESAR LA ELIMINACIÓN LÓGICA (INACTIVACIÓN)
if (isset($_GET['inactivar_id'])) {
    $id_profesor = intval($_GET['inactivar_id']);
    
    // Cambiamos el estatus a 'Inactivo' en lugar de borrar la fila
    $sql_delete = "UPDATE profesores SET estatus = 'Inactivo' WHERE id = ?";
    $stmt = $conexion->prepare($sql_delete);
    $stmt->bind_param("i", $id_profesor);
    
    if ($stmt->execute()) {
        $mensaje = "<div class='alert success'>Profesor dado de baja correctamente. Ya no podrá acceder al sistema.</div>";
    } else {
        $mensaje = "<div class='alert error'>Error al procesar la solicitud.</div>";
    }
}

// OBTENER TODOS LOS PROFESORES QUE ESTÁN ACTIVOS ACTUALMENTE
$sql_profesores = "SELECT p.id, p.nombre, s.nombre AS seccion_nombre, p.sala 
                   FROM profesores p 
                   LEFT JOIN secciones s ON p.seccion = s.id 
                   WHERE p.estatus = 'Activo'";
$resultado = $conexion->query($sql_profesores);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Profesor (Baja del Sistema)</title>
    <link rel="stylesheet" href="Estilo/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: var(--bg-principal, #f4f4f4); padding: 30px; }
        .container { max-width: 800px; margin: 0 auto; background: var(--bg-caja, white); padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #dc3545; }
        h2 { color: #dc3545; margin-bottom: 20px; text-transform: uppercase; font-size: 22px; }
        .btn-back { display: inline-block; margin-bottom: 20px; color: #004b93; text-decoration: none; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; color: #333; font-weight: bold; }
        .btn-action-delete { background: #dc3545; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; }
        .btn-action-delete:hover { background: #c82333; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div class="container">
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        
        <h2><i class="fas fa-user-minus"></i> Dar de Baja Profesores</h2>
        <p style="color: #666; margin-bottom: 20px;">Selecciona el docente que deseas remover. Al hacerlo, su estado pasará a ser Inactivo y no podrá loguearse en el sistema.</p>

        <?php echo $mensaje; ?>

        <table>
            <thead>
                <tr>
                    <th>Nombre del Docente</th>
                    <th>Sala / Grado</th>
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
                            <td><?php echo htmlspecialchars($row['seccion_nombre'] ?? 'Ninguna'); ?></td>
                            <td>
                                <a href="eliminar_profesor.php?inactivar_id=<?php echo $row['id']; ?>" 
                                   class="btn-action-delete" 
                                   onclick="return confirm('¿Estás seguro de que deseas deshabilitar a este profesor? Perderá acceso inmediato al sistema.');">
                                    <i class="fas fa-ban"></i> Inactivar
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #999;">No hay profesores activos registrados en este momento.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>