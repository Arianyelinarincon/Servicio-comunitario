<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

include_once('../../config/conexion.php');

// ==================== GENERAR TOKEN CSRF ====================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = "";

// ==================== PROCESAR INACTIVACIÓN POR POST ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inactivar_id'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:6px; margin-bottom:20px;'>Error de seguridad: token inválido.</div>";
    } else {
        $id_profesor = intval($_POST['inactivar_id']);
        $sql_update = "UPDATE profesores SET estatus = 'Inactivo' WHERE id = ?";
        $stmt = $conexion->prepare($sql_update);
        $stmt->bind_param("i", $id_profesor);
        if ($stmt->execute()) {
            $mensaje = "<div style='background:#d4edda; color:#155724; padding:10px; border-radius:6px; margin-bottom:20px;'><i class='fas fa-check-circle'></i> Docente inactivado correctamente.</div>";
        } else {
            $mensaje = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:6px; margin-bottom:20px;'>Error al inactivar.</div>";
        }
        $stmt->close();
    }
}

// Consulta de lista (sin cambios)
$sql_lista = "SELECT p.id, p.nombre AS nombre_profesor, p.apellido AS apellido_profesor, p.sala, p.estatus, s.nombre AS nombre_seccion
              FROM profesores p 
              LEFT JOIN secciones s ON p.seccion = s.id
              WHERE p.rol != 'administrador' AND p.rol != 'super_admin'";
              
$resultado_lista = $conexion->query($sql_lista);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Docentes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; }
        .header-top { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .container { margin: 20px 40px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .badge { padding: 5px 10px; border-radius: 4px; color: white; font-size: 0.8em; font-weight: bold; text-transform: uppercase; }
        .bg-activo { background: #28a745; }
        .bg-inactivo { background: #dc3545; }
        .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; color: white; display: inline-block; }
        .btn-editar { background: #003366; }
        .btn-inactivar { background: #dc3545; border: none; cursor: pointer; }
        .btn-inactivar:hover { background: #c82333; }
    </style>
</head>
<body>

<div class="header-top">
    <h2><i class="fas fa-users-cog"></i> Actualización de Datos</h2>
    <a href="../../index.php" style="color: white;"><i class="fas fa-home"></i> Inicio</a>
</div>

<div class="container">
    <?php echo $mensaje; ?>
    <table id="tablaProfesores">
        <thead>
            <tr>
                <th>Nº</th> <th>Nombre</th>
                <th>Apellido</th>
                <th>Sala</th>
                <th>Sección</th>
                <th>Estado</th>
                <th>Modificación</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $contador = 1;
            while($row = $resultado_lista->fetch_assoc()): 
                $esActivo = (strtolower(trim($row['estatus'])) == 'activo');
            ?>
            <tr>
                <td><?php echo $contador++; ?></td>
                <td><?php echo htmlspecialchars($row['nombre_profesor']); ?></td>
                <td><?php echo htmlspecialchars($row['apellido_profesor']); ?></td>
                <td><?php echo htmlspecialchars($row['sala']); ?></td>
                <td><?php echo htmlspecialchars($row['nombre_seccion'] ?? 'N/A'); ?></td>
                <td>
                    <span class="badge <?php echo $esActivo ? 'bg-activo' : 'bg-inactivo'; ?>">
                        <?php echo htmlspecialchars($row['estatus']); ?>
                    </span>
                 </a>
                <td>
                    <a href="editar_profesor.php?id=<?php echo $row['id']; ?>" class="btn btn-editar">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <?php if ($esActivo): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Inactivar este profesor?');">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="inactivar_id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-inactivar">
                            <i class="fas fa-ban"></i> Inactivar
                        </button>
                    </form>
                    <?php endif; ?>
                 </a>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>