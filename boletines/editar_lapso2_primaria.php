<?php
session_start();
if (!isset($_SESSION['estudiante'])) {
    header('Location: paso1_portada_primaria.php');
    exit;
}

require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar en sesión
    $_SESSION['l2_proyecto'] = htmlspecialchars($_POST['l2_proyecto']);
    $_SESSION['l2_analisis'] = htmlspecialchars($_POST['l2_analisis']);
    $_SESSION['l2_sugerencias'] = htmlspecialchars($_POST['l2_sugerencias']);
    
    // Guardar en BD
    $estudiante_id = $_SESSION['estudiante_id'];
    $periodo = $_SESSION['ano_escolar'] ?? '2025 / 2026';
    $tipo = 'primaria';
    
    $stmt = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
    $stmt->bind_param("iss", $estudiante_id, $periodo, $tipo);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existe) {
        $stmt = $conexion->prepare("UPDATE boletines SET 
            m2_proyecto = ?, m2_formacion = ?, m2_sugerencias = ?
            WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        $stmt->bind_param("sssiss", 
            $_SESSION['l2_proyecto'], 
            $_SESSION['l2_analisis'],
            $_SESSION['l2_sugerencias'],
            $estudiante_id, $periodo, $tipo);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conexion->prepare("INSERT INTO boletines 
            (estudiante_id, periodo, tipo_boletin, observacion,
             m2_proyecto, m2_formacion, m2_sugerencias)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $observacion = $_SESSION['observacion'] ?? '';
        $stmt->bind_param("issssss", 
            $estudiante_id, $periodo, $tipo, $observacion,
            $_SESSION['l2_proyecto'],
            $_SESSION['l2_analisis'],
            $_SESSION['l2_sugerencias']);
        $stmt->execute();
        $stmt->close();
    }
    
    header('Location: panel_boletin_primaria.php');
    exit;
}

include '../includes/header.php';
?>
<div style="font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; padding: 20px;">
    <div style="background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <h2 style="color: #17a2b8; text-align: center;">Editar Segundo Lapso - Primaria</h2>
        <p style="text-align: center; color: #666;">Estudiante: <strong><?php echo htmlspecialchars($_SESSION['estudiante']); ?></strong></p>
        
        <form method="POST">
            <div style="border-left: 4px solid #17a2b8; padding-left: 15px; margin-bottom: 20px;">
                <p style="font-size: 13px; color: #555; margin: 0;">
                    <i class="fas fa-info-circle" style="color: #17a2b8;"></i> 
                    Complete los campos para el <strong>Segundo Lapso</strong> de evaluación.
                </p>
            </div>
            
            <p style="font-weight: bold; font-size: 14px; margin-top: 20px;">Proyecto de Aprendizaje:</p>
            <textarea name="l2_proyecto" rows="2" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; box-sizing: border-box; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['l2_proyecto'] ?? ''); ?></textarea>
            
            <p style="font-weight: bold; font-size: 14px; margin-top: 20px;">Análisis Cualitativo:</p>
            <textarea name="l2_analisis" rows="5" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; box-sizing: border-box; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['l2_analisis'] ?? ''); ?></textarea>
            
            <p style="font-weight: bold; font-size: 14px; margin-top: 20px;">Sugerencias:</p>
            <textarea name="l2_sugerencias" rows="3" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; box-sizing: border-box; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['l2_sugerencias'] ?? ''); ?></textarea>

            <br><br>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="submit" style="background: #17a2b8; color: white; padding: 12px 30px; border: none; cursor: pointer; border-radius: 6px; font-size: 16px; font-weight: bold;">
                    Guardar Segundo Lapso
                </button>
                <a href="panel_boletin_primaria.php" style="background: #6c757d; color: white; padding: 12px 30px; border: none; cursor: pointer; border-radius: 6px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block;">
                    Cancelar / Volver
                </a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>