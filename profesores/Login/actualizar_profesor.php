<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

include_once('../../config/conexion.php');
$mensaje = "";
$profesor_editar = null;

if (isset($_GET['editar_id'])) {
    $id_editar = intval($_GET['editar_id']);
    $sql_busca = "SELECT * FROM profesores WHERE id = ?";
    $stmt = $conexion->prepare($sql_busca);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $profesor_editar = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $id_profesor = intval($_POST['id_profesor']);
    $nombre = strtoupper(trim($_POST['nombre']));
    $seccion_id = intval($_POST['seccion']);
    $sala = trim($_POST['sala']);
    $estatus = trim($_POST['estatus']);

    $sql_update = "UPDATE profesores SET nombre = ?, seccion = ?, sala = ?, estatus = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql_update);
    $stmt->bind_param("sissi", $nombre, $seccion_id, $sala, $estatus, $id_profesor);

    if ($stmt->execute()) {
        $mensaje = "<div class='alert success'><i class='fas fa-sync-alt'></i> Información actualizada.</div>";
        $profesor_editar = null;
    } else {
        $mensaje = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error al actualizar.</div>";
    }
}

$sql_lista = "SELECT p.id, p.nombre, s.nombre AS seccion_nombre, p.sala, p.estatus 
              FROM profesores p 
              LEFT JOIN secciones s ON p.seccion = s.id 
              WHERE p.rol != 'administrador'";
$resultado_lista = $conexion->query($sql_lista);
$sql_secciones = "SELECT * FROM secciones ORDER BY sala, nombre";
$resultado_secciones = $conexion->query($sql_secciones);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Profesores - UEBN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        
        .header-admin { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { flex: 1; padding: 40px; max-width: 1000px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .content-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        /* Formulario */
        .edit-card { background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 30px; border-left: 5px solid #003366; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        
        /* Tabla */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #ddd; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .badge { padding: 5px 10px; border-radius: 4px; color: white; font-size: 0.8em; }
        .bg-activo { background: #28a745; }
        .bg-inactivo { background: #dc3545; }
        
        .btn-save { background: #003366; color: white; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <div class="header-admin">
        <div><h2><i class="fas fa-user-edit"></i> Gestión de Docentes</h2></div>
        <a href="../../index.php" style="color: white; text-decoration: none;"><i class="fas fa-home"></i>Inicio</a>
    </div>

    <div class="container">
        <div class="content-box">
            <?php echo $mensaje; ?>

            <?php if ($profesor_editar): ?>
            <div class="edit-card">
                <h3><i class="fas fa-edit"></i> Editando a: <?php echo htmlspecialchars($profesor_editar['nombre']); ?></h3>
                <form method="POST">
                    <input type="hidden" name="id_profesor" value="<?php echo $profesor_editar['id']; ?>">
                    <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" value="<?php echo htmlspecialchars($profesor_editar['nombre']); ?>" required></div>
                    <div class="form-group"><label>Grado:</label><input type="text" name="sala" value="<?php echo htmlspecialchars($profesor_editar['sala']); ?>" required></div>
                    <div class="form-group"><label>Sección:</label>
                        <select name="seccion">
                            <?php 
                            $resultado_secciones->data_seek(0);
                            while($sec = $resultado_secciones->fetch_assoc()) {
                                $sel = ($sec['id'] == $profesor_editar['seccion']) ? 'selected' : '';
                                echo "<option value='".$sec['id']."' $sel>".$sec['sala']." - ".$sec['nombre']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Estatus:</label>
                        <select name="estatus">
                            <option value="Activo" <?php if($profesor_editar['estatus']=='Activo') echo 'selected'; ?>>Activo</option>
                            <option value="Inactivo" <?php if($profesor_editar['estatus']=='Inactivo') echo 'selected'; ?>>Inactivo</option>
                        </select>
                    </div>
                    <button type="submit" name="actualizar" class="btn-save">Guardar Cambios</button>
                    <a href="actualizar_profesor.php" style="margin-left: 15px; color: #666;">Cancelar</a>
                </form>
            </div>
            <?php endif; ?>

            <table>
                <thead><tr><th>Nombre</th><th>Grado</th><th>Sección</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php while($row = $resultado_lista->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($row['sala']); ?></td>
                        <td><?php echo htmlspecialchars($row['seccion_nombre'] ?? 'N/A'); ?></td>
                        <td><span class="badge <?php echo ($row['estatus'] == 'Activo') ? 'bg-activo' : 'bg-inactivo'; ?>"><?php echo $row['estatus']; ?></span></td>
                        <td><a href="actualizar_profesor.php?editar_id=<?php echo $row['id']; ?>" style="color:#003366;"><i class="fas fa-edit"></i> Editar</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>