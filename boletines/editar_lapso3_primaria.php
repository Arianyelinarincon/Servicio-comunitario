<?php
session_start();
if (!isset($_SESSION['estudiante'])) {
    header('Location: paso1_portada_primaria.php');
    exit;
}

require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar en sesión
    $_SESSION['l3_proyecto'] = htmlspecialchars($_POST['l3_proyecto']);
    $_SESSION['l3_analisis'] = htmlspecialchars($_POST['l3_analisis']);
    $_SESSION['l3_sugerencias'] = htmlspecialchars($_POST['l3_sugerencias']);
    $_SESSION['resultado_final'] = htmlspecialchars($_POST['resultado_final']);
    $_SESSION['literal_final'] = htmlspecialchars($_POST['literal_final']);
    
    // Guardar en BD
    $estudiante_id = $_SESSION['estudiante_id'];
    $periodo = $_SESSION['ano_escolar'] ?? '2025 / 2026';
    $tipo = 'primaria';
    
    // Verificar si existe
    $stmt = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
    if (!$stmt) {
        die("Error en prepare: " . $conexion->error);
    }
    $stmt->bind_param("iss", $estudiante_id, $periodo, $tipo);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existe) {
        $stmt = $conexion->prepare("UPDATE boletines SET 
            m3_proyecto = ?, m3_formacion = ?, m3_sugerencias = ?,
            resultado_final = ?, literal_final = ?
            WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        if (!$stmt) {
            die("Error en prepare (UPDATE): " . $conexion->error);
        }
        $stmt->bind_param("sssssiss", 
            $_SESSION['l3_proyecto'], 
            $_SESSION['l3_analisis'],
            $_SESSION['l3_sugerencias'],
            $_SESSION['resultado_final'], 
            $_SESSION['literal_final'],
            $estudiante_id, $periodo, $tipo);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conexion->prepare("INSERT INTO boletines 
            (estudiante_id, periodo, tipo_boletin, observacion,
             m3_proyecto, m3_formacion, m3_sugerencias,
             resultado_final, literal_final)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Error en prepare (INSERT): " . $conexion->error);
        }
        $observacion = $_SESSION['observacion'] ?? '';
        $stmt->bind_param("issssssss", 
            $estudiante_id, $periodo, $tipo, $observacion,
            $_SESSION['l3_proyecto'],
            $_SESSION['l3_analisis'],
            $_SESSION['l3_sugerencias'],
            $_SESSION['resultado_final'], 
            $_SESSION['literal_final']);
        $stmt->execute();
        $stmt->close();
    }
    
    header('Location: panel_boletin_primaria.php');
    exit;
}

include '../includes/header.php';
?>
<!-- HTML del formulario (sin cambios) -->
<div style="font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; padding: 20px;">
    <div style="background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <h2 style="color: #28a745; text-align: center;">Editar Tercer Lapso + Resultado Final - Primaria</h2>
        <p style="text-align: center; color: #666;">Estudiante: <strong><?php echo htmlspecialchars($_SESSION['estudiante']); ?></strong></p>
        
        <form method="POST">
            <div style="border-left: 4px solid #28a745; padding-left: 15px; margin-bottom: 20px;">
                <p style="font-size: 13px; color: #555; margin: 0;">
                    <i class="fas fa-info-circle" style="color: #28a745;"></i> 
                    Complete los campos para el <strong>Tercer Lapso</strong> de evaluación y el <strong>Resultado Final</strong>.
                </p>
            </div>
            
            <p style="font-weight: bold; font-size: 14px; margin-top: 20px;">Proyecto de Aprendizaje:</p>
            <textarea name="l3_proyecto" rows="2" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; box-sizing: border-box; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['l3_proyecto'] ?? ''); ?></textarea>
            
            <p style="font-weight: bold; font-size: 14px; margin-top: 20px;">Análisis Cualitativo:</p>
            <textarea name="l3_analisis" rows="5" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; box-sizing: border-box; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['l3_analisis'] ?? ''); ?></textarea>
            
            <p style="font-weight: bold; font-size: 14px; margin-top: 20px;">Sugerencias:</p>
            <textarea name="l3_sugerencias" rows="3" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; box-sizing: border-box; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['l3_sugerencias'] ?? ''); ?></textarea>

            <hr style="margin: 25px 0; border-top: 2px dashed #dee2e6;">

            <h4 style="color: #1a237e; text-align: center; margin-bottom: 20px;">RESULTADO FINAL</h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <p style="font-weight: bold; font-size: 14px;">Situación del Estudiante:</p>
                    <select name="resultado_final" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; font-size: 14px;">
                        <option value="">Seleccione</option>
                        <option value="Promovido" <?php echo (($_SESSION['resultado_final'] ?? '') == 'Promovido') ? 'selected' : ''; ?>>Promovido</option>
                        <option value="Aplazado" <?php echo (($_SESSION['resultado_final'] ?? '') == 'Aplazado') ? 'selected' : ''; ?>>Aplazado</option>
                    </select>
                </div>
                <div>
                    <p style="font-weight: bold; font-size: 14px;">Literal Final:</p>
                    <select name="literal_final" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; font-size: 14px;">
                        <option value="">Seleccione</option>
                        <option value="A" <?php echo (($_SESSION['literal_final'] ?? '') == 'A') ? 'selected' : ''; ?>>A</option>
                        <option value="B" <?php echo (($_SESSION['literal_final'] ?? '') == 'B') ? 'selected' : ''; ?>>B</option>
                        <option value="C" <?php echo (($_SESSION['literal_final'] ?? '') == 'C') ? 'selected' : ''; ?>>C</option>
                        <option value="D" <?php echo (($_SESSION['literal_final'] ?? '') == 'D') ? 'selected' : ''; ?>>D</option>
                        <option value="E" <?php echo (($_SESSION['literal_final'] ?? '') == 'E') ? 'selected' : ''; ?>>E</option>
                    </select>
                </div>
            </div>

            <br><br>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="submit" style="background: #28a745; color: white; padding: 12px 30px; border: none; cursor: pointer; border-radius: 6px; font-size: 16px; font-weight: bold;">
                    Guardar Tercer Lapso y Resultado
                </button>
                <a href="panel_boletin_primaria.php" style="background: #6c757d; color: white; padding: 12px 30px; border: none; cursor: pointer; border-radius: 6px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block;">
                    Cancelar / Volver
                </a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>