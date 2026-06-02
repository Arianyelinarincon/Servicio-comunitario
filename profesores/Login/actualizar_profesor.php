<?php
session_start();
// Validación corregida
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

include_once('../../config/conexion.php');
$mensaje = "";
$profesor_editar = null;

// 1. Buscamos usando el ID
if (isset($_GET['editar_id'])) {
    $id_editar = intval($_GET['editar_id']);
    $sql_busca = "SELECT * FROM administradores WHERE id = ?";
    $stmt = $conexion->prepare($sql_busca);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $profesor_editar = $stmt->get_result()->fetch_assoc();
}

// 2. Procesar actualización (SIN campos inexistentes)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $id_profesor = intval($_POST['id_profesor']);
    $nombre = strtoupper(trim($_POST['nombre_profesores'])); 
    $seccion_id = intval($_POST['seccion']);
    $sala = trim($_POST['sala']);
    $estatus = trim($_POST['estatus']);

    // Actualizamos tabla administradores (Quitamos telefono/direccion por ahora porque no existen en tu DB)
    $sql_update = "UPDATE administradores SET nombre_profesores = ?, seccion = ?, sala = ?, estatus = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql_update);
    $stmt->bind_param("sissi", $nombre, $seccion_id, $sala, $estatus, $id_profesor);

    if ($stmt->execute()) {
        $mensaje = "<div class='alert success'><i class='fas fa-sync-alt'></i> Información actualizada correctamente.</div>";
        $profesor_editar = null;
    } else {
        $mensaje = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error al actualizar.</div>";
    }
}

// 3. Consulta de lista (Cambiado nombre a nombre_profesores)
$sql_lista = "SELECT p.id, p.nombre_profesores, p.sala, p.estatus, s.nombre AS seccion_nombre 
              FROM administradores p 
              LEFT JOIN secciones s ON p.seccion = s.id";
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
        .container { flex: 1; padding: 40px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .content-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .edit-card { background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 30px; border-left: 5px solid #003366; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; display: block; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
        th { background: #f8f9fa; padding: 15px; text-align: left; border-bottom: 2px solid #ddd; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .badge { padding: 5px 10px; border-radius: 4px; color: white; }
        .bg-activo { background: #28a745; }
        .bg-inactivo { background: #dc3545; }
        .btn-save { background: #003366; color: white; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>

<div class="header-admin">
    <div><h2><i class="fas fa-user-edit"></i> Gestión de Docentes</h2></div>
    <a href="../../index.php" style="color: white; text-decoration: none;"><i class="fas fa-home"></i> Inicio</a>
</div>

<div class="container">
    <div class="content-box">
        <?php echo $mensaje; ?>

        <?php if ($profesor_editar): ?>
        <div class="edit-card">
            <h3><i class="fas fa-edit"></i> Editando a: <?php echo htmlspecialchars($profesor_editar['nombre_profesores']); ?></h3>
            <form method="POST">
                <input type="hidden" name="id_profesor" value="<?php echo $profesor_editar['id']; ?>">
                <div class="form-row">
                    <div class="form-group"><label>Nombre:</label><input type="text" name="nombre_profesores" value="<?php echo htmlspecialchars($profesor_editar['nombre_profesores']); ?>" required></div>
                    <div class="form-group"><label>Sala (Grado):</label><input type="text" name="sala" value="<?php echo htmlspecialchars($profesor_editar['sala']); ?>" required></div>
                </div>
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
            </form>
        </div>
        <?php endif; ?>

        <table>
            <thead><tr><th>Nombre</th><th>Sala</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody>
                <?php while($row = $resultado_lista->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['nombre_profesores']); ?></td>
                    <td><?php echo htmlspecialchars($row['sala']); ?></td>
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