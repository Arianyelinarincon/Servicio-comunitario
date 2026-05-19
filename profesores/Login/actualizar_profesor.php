<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

include_once('../../config/conexion.php');

$mensaje = "";
$profesor_editar = null;

// 1. SI SE SELECCIONÓ UN PROFESOR PARA EDITAR, CARGAMOS SUS DATOS
if (isset($_GET['editar_id'])) {
    $id_editar = intval($_GET['editar_id']);
    $sql_busca = "SELECT * FROM profesores WHERE id = ?";
    $stmt = $conexion->prepare($sql_busca);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $profesor_editar = $stmt->get_result()->fetch_assoc();
}

// 2. PROCESAR LA ACTUALIZACIÓN
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
        $mensaje = "<div class='alert success'><i class='fas fa-sync-alt'></i> Información del profesor actualizada correctamente.</div>";
        $profesor_editar = null; // Limpiamos la edición
    } else {
        $mensaje = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error al actualizar los datos.</div>";
    }
}

// OBTENER TODOS LOS PROFESORES REGISTRADOS
$sql_lista = "SELECT p.id, p.nombre, s.nombre AS seccion_nombre, p.sala, p.estatus 
              FROM profesores p 
              LEFT JOIN secciones s ON p.seccion = s.id";
$resultado_lista = $conexion->query($sql_lista);

// OBTENER LAS SECCIONES PARA EL FORMULARIO
$sql_secciones = "SELECT * FROM secciones ORDER BY sala, nombre";
$resultado_secciones = $conexion->query($sql_secciones);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Profesor</title>
    <link rel="stylesheet" href="Estilo/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: var(--bg-principal, #f4f4f4); padding: 30px; }
        .container { max-width: 900px; margin: 0 auto; background: var(--bg-caja, white); padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #ffc107; }
        h2 { color: #b58100; margin-bottom: 20px; text-transform: uppercase; font-size: 20px; }
        .btn-back { display: inline-block; margin-bottom: 20px; color: #004b93; text-decoration: none; font-weight: bold; }
        .form-inline { background: #fff3cd; padding: 20px; border-radius: 6px; margin-bottom: 25px; border: 1px solid #ffeeba; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background-color: #f8f9fa; }
        .btn-edit { background: #ffc107; color: #333; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .badge { padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; color: white; }
        .bg-activo { background-color: #28a745; }
        .bg-inactivo { background-color: #dc3545; }
    </style>
</head>
<body>

    <div class="container">
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        
        <h2><i class="fas fa-user-edit"></i> Gestión y Actualización de Profesores</h2>
        
        <?php echo $mensaje; ?>

        <?php if ($profesor_editar): ?>
            <div class="form-inline">
                <h3 style="margin-top:0; color: #856404;"><i class="fas fa-edit"></i> Modificando a: <?php echo htmlspecialchars($profesor_editar['nombre']); ?></h3>
                <form action="actualizar_profesor.php" method="POST">
                    <input type="hidden" name="id_profesor" value="<?php echo $profesor_editar['id']; ?>">
                    
                    <div class="form-group">
                        <label>Nombre del Docente:</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($profesor_editar['nombre']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Sala / Grado:</label>
                        <select name="sala" required>
                            <option value="sala4" <?php if($profesor_editar['sala']=='sala4') echo 'selected'; ?>>Sala de 4 años</option>
                            <option value="sala5" <?php if($profesor_editar['sala']=='sala5') echo 'selected'; ?>>Sala de 5 años</option>
                            <option value="1ro" <?php if($profesor_editar['sala']=='1ro') echo 'selected'; ?>>1er Grado</option>
                            <option value="2do" <?php if($profesor_editar['sala']=='2do') echo 'selected'; ?>>2do Grado</option>
                            <option value="3ro" <?php if($profesor_editar['sala']=='3ro') echo 'selected'; ?>>3er Grado</option>
                            <option value="4to" <?php if($profesor_editar['sala']=='4to') echo 'selected'; ?>>4to Grado</option>
                            <option value="5to" <?php if($profesor_editar['sala']=='5to') echo 'selected'; ?>>5to Grado</option>
                            <option value="6to" <?php if($profesor_editar['sala']=='6to') echo 'selected'; ?>>6to Grado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Sección:</label>
                        <select name="seccion" required>
                            <?php 
                            if($resultado_secciones && $resultado_secciones->num_rows > 0) {
                                // Reiniciamos el puntero de secciones
                                $resultado_secciones->data_seek(0);
                                while($sec = $resultado_secciones->fetch_assoc()) {
                                    $selected = ($sec['id'] == $profesor_editar['seccion']) ? 'selected' : '';
                                    echo "<option value='".$sec['id']."' $selected>".$sec['sala']." - Sección ".$sec['nombre']."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Estatus del Usuario:</label>
                        <select name="estatus" required>
                            <option value="Activo" <?php if($profesor_editar['estatus']=='Activo') echo 'selected'; ?>>Activo (Tiene Acceso)</option>
                            <option value="Inactivo" <?php if($profesor_editar['estatus']=='Inactivo') echo 'selected'; ?>>Inactivo (Acceso Bloqueado)</option>
                        </select>
                    </div>

                    <br>
                    <button type="submit" name="actualizar" class="btn-edit" style="background:#28a745; color:white; padding:10px 20px; font-size:14px; cursor:pointer; border:none; border-radius:4px;"><i class="fas fa-save"></i> Guardar Cambios</button>
                    <a href="actualizar_profesor.php" style="margin-left:10px; color:#666; text-decoration:none;">Cancelar</a>
                </form>
            </div>
        <?php endif; ?>

        <h3>Listado de Personal Docente</h3>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Grado/Sala</th>
                    <th>Sección</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_lista && $resultado_lista->num_rows > 0): ?>
                    <?php while($row = $resultado_lista->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['sala']); ?></td>
                            <td><?php echo htmlspecialchars($row['seccion_nombre'] ?? 'Sin asignar'); ?></td>
                            <td>
                                <span class="badge <?php echo ($row['estatus'] == 'Activo') ? 'bg-activo' : 'bg-inactivo'; ?>">
                                    <?php echo $row['estatus']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="actualizar_profesor.php?editar_id=<?php echo $row['id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No hay registros.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>