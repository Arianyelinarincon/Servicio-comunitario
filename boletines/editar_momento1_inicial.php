<?php
session_start();
if (!isset($_SESSION['estudiante'])) {
    header('Location: paso1_portada.php?tipo=inicial');
    exit;
}

require_once '../config/conexion.php';

// Guardar momento 1
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['m1_proyecto'] = htmlspecialchars($_POST['m1_proyecto']);
    $_SESSION['m1_formacion'] = htmlspecialchars($_POST['m1_formacion']);
    $_SESSION['m1_relacion'] = htmlspecialchars($_POST['m1_relacion']);
    $_SESSION['m1_sugerencias'] = htmlspecialchars($_POST['m1_sugerencias']);
    
    // Guardar en BD
    $estudiante_id = $_SESSION['estudiante_id'];
    $periodo = $_SESSION['ano_escolar'] ?? '2025 / 2026';
    $tipo = 'inicial';
    
    // Verificar si ya existe
    $stmt = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
    $stmt->bind_param("iss", $estudiante_id, $periodo, $tipo);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existe) {
        // Actualizar
        $stmt = $conexion->prepare("UPDATE boletines SET 
            m1_proyecto = ?, m1_formacion = ?, m1_relacion = ?, m1_sugerencias = ? 
            WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        $stmt->bind_param("ssssiss", 
            $_SESSION['m1_proyecto'], $_SESSION['m1_formacion'], 
            $_SESSION['m1_relacion'], $_SESSION['m1_sugerencias'],
            $estudiante_id, $periodo, $tipo);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insertar nuevo
        $stmt = $conexion->prepare("INSERT INTO boletines 
            (estudiante_id, periodo, tipo_boletin, 
             m1_proyecto, m1_formacion, m1_relacion, m1_sugerencias) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", 
            $estudiante_id, $periodo, $tipo,
            $_SESSION['m1_proyecto'], $_SESSION['m1_formacion'], 
            $_SESSION['m1_relacion'], $_SESSION['m1_sugerencias']);
        $stmt->execute();
        $stmt->close();
    }
    
    header('Location: panel_boletin_inicial.php');
    exit;
}

include '../includes/header.php';
?>
<div style="font-family: Arial, sans-serif; background: rgb(240, 242, 245); padding: 20px;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 900px; margin: 0 auto;">
        <h2 style="color: rgb(26, 35, 126); text-align: center;">Editar Primer Momento</h2>
        <p style="text-align: center; color: #666;">Estudiante: <strong><?php echo htmlspecialchars($_SESSION['estudiante']); ?></strong></p>
        
        <form method="POST">
            <p style="font-weight: bold;">Proyecto de Aprendizaje:</p>
            <input type="text" name="m1_proyecto" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;" value="<?php echo htmlspecialchars($_SESSION['m1_proyecto'] ?? ''); ?>">
            
            <p style="font-weight: bold;">Formación personal, social y comunicación:</p>
            <textarea name="m1_formacion" rows="4" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;"><?php echo htmlspecialchars($_SESSION['m1_formacion'] ?? ''); ?></textarea>
            
            <p style="font-weight: bold;">Relación entre los Componentes del Ambiente:</p>
            <textarea name="m1_relacion" rows="4" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;"><?php echo htmlspecialchars($_SESSION['m1_relacion'] ?? ''); ?></textarea>
            
            <p style="font-weight: bold;">Sugerencias:</p>
            <textarea name="m1_sugerencias" rows="3" required style="width: 100%; padding: 8px; box-sizing: border-box;"><?php echo htmlspecialchars($_SESSION['m1_sugerencias'] ?? ''); ?></textarea>

            <br><br>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="submit" style="background: rgb(26, 35, 126); color: white; padding: 15px 30px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold;">
                    💾 Guardar Momento 1
                </button>
                <a href="panel_boletin_inicial.php" style="background: #6c757d; color: white; padding: 15px 30px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block;">
                    ⬅️ Cancelar / Volver
                </a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>