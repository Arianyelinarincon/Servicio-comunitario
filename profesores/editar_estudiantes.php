<?php
session_start();
require_once('../config/conexion.php');

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador')) {
    header("Location: ../profesores/Login/login.php");
    exit();
}

$id = intval($_GET['id']);
$mensaje = "";
$tipo_mensaje = ""; // 'success' o 'info'

// Obtener datos actuales para comparar
$stmt_select = $conexion->prepare("SELECT * FROM estudiantes WHERE id = ?");
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$estudiante = $stmt_select->get_result()->fetch_assoc();

// Procesar guardado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Comprobar si hubo cambios
    if ($_POST['nombre'] == $estudiante['nombre'] && 
        $_POST['apellido'] == $estudiante['apellido'] && 
        $_POST['cedula'] == $estudiante['cedula'] && 
        $_POST['sala'] == $estudiante['sala'] && 
        $_POST['estatus'] == $estudiante['estatus']) {
        
        $mensaje = "No se han realizado cambios en los datos.";
        $tipo_mensaje = "info";
    } else {
        // Ejecutar actualización
        $sql = "UPDATE estudiantes SET nombre=?, apellido=?, cedula=?, sala=?, estatus=? WHERE id=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssi", $_POST['nombre'], $_POST['apellido'], $_POST['cedula'], $_POST['sala'], $_POST['estatus'], $id);
        
        if ($stmt->execute()) {
            $mensaje = "¡Datos modificados con éxito!";
            $tipo_mensaje = "success";
            // Refrescar los datos del estudiante después de guardar
            $estudiante = $_POST;
        }
    }
}

include('../includes/header.php'); 
?>

<div class="content-wrapper" style="padding: 20px;">
    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 800px; margin: auto;">
        
        <?php if ($mensaje != ""): ?>
            <div style="padding: 15px; margin-bottom: 20px; border-radius: 6px; text-align: center; font-weight: bold; 
                 <?php echo ($tipo_mensaje == 'success') ? 'background: #d4edda; color: #155724;' : 'background: #fff3cd; color: #856404;'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <h2 style="color: #333; margin-bottom: 20px;"><i class="fas fa-user-edit"></i> Editar Estudiante: <?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></h2>
        
        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div style='display: flex; flex-direction: column;'>
                <label style='font-weight: bold; margin-bottom: 5px; color: #555;'>Nombre</label>
                <input type='text' name='nombre' value='<?php echo htmlspecialchars($estudiante['nombre']); ?>' style='padding: 10px; border: 1px solid #ddd; border-radius: 6px;'>
            </div>
            
            <div style='display: flex; flex-direction: column;'>
                <label style='font-weight: bold; margin-bottom: 5px; color: #555;'>Apellido</label>
                <input type='text' name='apellido' value='<?php echo htmlspecialchars($estudiante['apellido']); ?>' style='padding: 10px; border: 1px solid #ddd; border-radius: 6px;'>
            </div>
            
            <div style='display: flex; flex-direction: column;'>
                <label style='font-weight: bold; margin-bottom: 5px; color: #555;'>Cédula</label>
                <input type='text' name='cedula' value='<?php echo htmlspecialchars($estudiante['cedula']); ?>' style='padding: 10px; border: 1px solid #ddd; border-radius: 6px;'>
            </div>
            
            <div style='display: flex; flex-direction: column;'>
                <label style='font-weight: bold; margin-bottom: 5px; color: #555;'>Sala</label>
                <input type='text' name='sala' value='<?php echo htmlspecialchars($estudiante['sala']); ?>' style='padding: 10px; border: 1px solid #ddd; border-radius: 6px;'>
            </div>

            <div style='display: flex; flex-direction: column;'>
                <label style='font-weight: bold; margin-bottom: 5px; color: #555;'>Estatus</label>
                <select name='estatus' style='padding: 10px; border: 1px solid #ddd; border-radius: 6px;'>
                    <option value='Activo' <?php if($estudiante['estatus']=='Activo') echo 'selected'; ?>>Activo</option>
                    <option value='Inactivo' <?php if($estudiante['estatus']=='Inactivo') echo 'selected'; ?>>Inactivo</option>
                </select>
            </div>
            
            <div style="grid-column: span 2; margin-top: 20px;">
                <button type="submit" style="background: #003366; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    Guardar Cambios
                </button>
                <a href="mis_estudiantes.php" style="margin-left: 15px; background: #dc3545; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: bold;">
                    Atras
                </a>
            </div>
        </form>
    </div>
</div>

<?php include('../includes/footer.php'); ?>