<?php
session_start();
if (!isset($_SESSION['estudiante'])) {
    header('Location: paso1_portada.php?tipo=inicial');
    exit;
}

require_once '../config/conexion.php';

// Guardar momento 3
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['m3_proyecto'] = htmlspecialchars($_POST['m3_proyecto']);
    $_SESSION['m3_formacion'] = htmlspecialchars($_POST['m3_formacion']);
    $_SESSION['m3_relacion'] = htmlspecialchars($_POST['m3_relacion']);
    $_SESSION['m3_sugerencias'] = htmlspecialchars($_POST['m3_sugerencias']);
    
    // Guardar en BD
    $estudiante_id = $_SESSION['estudiante_id'];
    $periodo = $_SESSION['ano_escolar'] ?? '2025 / 2026';
    $tipo = 'inicial';
    
    $stmt = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
    $stmt->bind_param("iss", $estudiante_id, $periodo, $tipo);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existe) {
        $stmt = $conexion->prepare("UPDATE boletines SET 
            m3_proyecto = ?, m3_formacion = ?, m3_relacion = ?, m3_sugerencias = ? 
            WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        $stmt->bind_param("ssssiss", 
            $_SESSION['m3_proyecto'], $_SESSION['m3_formacion'], 
            $_SESSION['m3_relacion'], $_SESSION['m3_sugerencias'],
            $estudiante_id, $periodo, $tipo);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conexion->prepare("INSERT INTO boletines 
            (estudiante_id, periodo, tipo_boletin, 
             m3_proyecto, m3_formacion, m3_relacion, m3_sugerencias) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", 
            $estudiante_id, $periodo, $tipo,
            $_SESSION['m3_proyecto'], $_SESSION['m3_formacion'], 
            $_SESSION['m3_relacion'], $_SESSION['m3_sugerencias']);
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
        <h2 style="color: rgb(26, 35, 126); text-align: center;">Editar Tercer Momento</h2>
        <p style="text-align: center; color: #666;">Estudiante: <strong><?php echo htmlspecialchars($_SESSION['estudiante']); ?></strong></p>
        
        <form method="POST">
            <p style="font-weight: bold;">Proyecto de Aprendizaje:</p>
            <input type="text" name="m3_proyecto" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;" value="<?php echo htmlspecialchars($_SESSION['m3_proyecto'] ?? ''); ?>">
            
            <p style="font-weight: bold;">Formación personal, social y comunicación:</p>
            <textarea name="m3_formacion" rows="4" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;"><?php echo htmlspecialchars($_SESSION['m3_formacion'] ?? ''); ?></textarea>
            
            <p style="font-weight: bold;">Relación entre los Componentes del Ambiente:</p>
            <textarea name="m3_relacion" rows="4" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;"><?php echo htmlspecialchars($_SESSION['m3_relacion'] ?? ''); ?></textarea>
            
            <p style="font-weight: bold;">Sugerencias:</p>
            <textarea name="m3_sugerencias" rows="3" required style="width: 100%; padding: 8px; box-sizing: border-box;"><?php echo htmlspecialchars($_SESSION['m3_sugerencias'] ?? ''); ?></textarea>

            <br><br>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="submit" style="background: rgb(26, 35, 126); color: white; padding: 15px 30px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold;">
                    💾 Guardar Momento 3
                </button>
                <a href="panel_boletin_inicial.php" style="background: #6c757d; color: white; padding: 15px 30px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block;">
                    ⬅️ Cancelar / Volver
                </a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>