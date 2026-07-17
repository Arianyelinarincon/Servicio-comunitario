<?php
session_start();
// ========== RUTA CORREGIDA ==========
require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'super_admin' && $_SESSION['rol'] !== 'admin')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: gestionar_profesores.php");
    exit();
}

// ========== Lógica para guardar cambios ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt_check = $conexion->prepare("SELECT cedula, nombre, seccion, sala, estatus, telefono, direccion FROM profesores WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $db_data = $stmt_check->get_result()->fetch_assoc();

    if (
        $_POST['cedula'] == $db_data['cedula'] &&
        $_POST['nombre'] == $db_data['nombre'] &&
        $_POST['seccion'] == $db_data['seccion'] &&
        $_POST['sala'] == $db_data['sala'] &&
        $_POST['estatus'] == $db_data['estatus'] &&
        $_POST['telefono'] == $db_data['telefono'] &&
        $_POST['direccion'] == $db_data['direccion']
    ) {
        header("Location: editar_profesor.php?id=$id&status=no_change");
        exit();
    } else {
        $sql_update = "UPDATE profesores SET cedula=?, nombre=?, seccion=?, sala=?, estatus=?, telefono=?, direccion=? WHERE id=?";
        $stmt = $conexion->prepare($sql_update);
        $stmt->bind_param("ssissssi", $_POST['cedula'], $_POST['nombre'], $_POST['seccion'], $_POST['sala'], $_POST['estatus'], $_POST['telefono'], $_POST['direccion'], $id);
        
        if ($stmt->execute()) {
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            if ($usuario_id > 0 && function_exists('registrarAuditoria')) {
                $detalles = "Editado profesor ID $id. Datos anteriores: Cedula: {$db_data['cedula']}, Nombre: {$db_data['nombre']}, Sección: {$db_data['seccion']}, Sala: {$db_data['sala']}, Estatus: {$db_data['estatus']}";
                registrarAuditoria($conexion, $usuario_id, 'EDITAR_PROFESOR', 'profesores', $id, $detalles);
            }
            header("Location: editar_profesor.php?id=$id&status=success");
            exit();
        }
    }
}

// ========== Cargar datos ==========
$stmt = $conexion->prepare("SELECT * FROM profesores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$profesor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profesor) {
    echo "Profesor no encontrado.";
    exit();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="content-wrapper" style="padding: 20px;">
    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 800px; margin: auto;">
        
        <?php if(isset($_GET['status'])): ?>
            <?php if($_GET['status'] == 'success'): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold;">
                    <i class="fas fa-check-circle"></i> ¡Datos modificados con éxito!
                </div>
            <?php elseif($_GET['status'] == 'no_change'): ?>
                <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold;">
                    <i class="fas fa-info-circle"></i> No se han realizado cambios.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h2 style="color: #333; margin-bottom: 20px;"><i class="fas fa-user-edit"></i> Editar Profesor: <?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?></h2>
        
        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <input type="hidden" name="id" value="<?php echo $profesor['id']; ?>">
            
            <?php 
            $campos = [
                'nombre' => 'Nombre *',
                'cedula' => 'Cédula *',
                'sala' => 'Sala',
                'seccion' => 'Sección (ID)',
                'telefono' => 'Teléfono',
                'direccion' => 'Dirección'
            ];
            
            foreach ($campos as $campo => $label) {
                $valor = htmlspecialchars($profesor[$campo] ?? '');
                echo "<div style='display: flex; flex-direction: column;'>
                        <label style='font-weight: bold; margin-bottom: 5px; color: #555;'>$label</label>
                        <input type='text' name='$campo' value='$valor' style='padding: 10px; border: 1px solid #ddd; border-radius: 6px;'>
                      </div>";
            }
            ?>

            <div style='display: flex; flex-direction: column;'>
                <label style='font-weight: bold; margin-bottom: 5px; color: #555;'>Estatus</label>
                <select name='estatus' style='padding: 10px; border: 1px solid #ddd; border-radius: 6px;'>
                    <option value='Activo' <?php echo ($profesor['estatus'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                    <option value='Inactivo' <?php echo ($profesor['estatus'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
            
            <div style="grid-column: span 2; margin-top: 20px;">
                <button type="submit" style="background: #003366; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    Guardar Cambios
                </button>
                <a href="gestionar_profesores.php" style="margin-left: 15px; background: #dc3545; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: bold;">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>