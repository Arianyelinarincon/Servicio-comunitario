<?php
session_start();
if (!isset($_SESSION['estudiante'])) {
    header('Location: paso1_portada_primaria.php');
    exit;
}

require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['observacion'] = htmlspecialchars($_POST['observacion']);
    
    $estudiante_id = $_SESSION['estudiante_id'];
    $periodo = $_SESSION['ano_escolar'] ?? '2025 / 2026';
    $tipo = 'primaria';
    
    $stmt = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
    $stmt->bind_param("iss", $estudiante_id, $periodo, $tipo);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existe) {
        $stmt = $conexion->prepare("UPDATE boletines SET observacion = ? WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        $stmt->bind_param("siss", $_SESSION['observacion'], $estudiante_id, $periodo, $tipo);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conexion->prepare("INSERT INTO boletines (estudiante_id, periodo, tipo_boletin, observacion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $estudiante_id, $periodo, $tipo, $_SESSION['observacion']);
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
        <h2 style="color: #1a237e; text-align: center;">Editar Observación General - Primaria</h2>
        <p style="text-align: center; color: #666;">Estudiante: <strong><?php echo htmlspecialchars($_SESSION['estudiante']); ?></strong></p>
        
        <form method="POST">
            <p style="font-weight: bold;">Observación General (Columna Izquierda de la hoja exterior):</p>
            <textarea name="observacion" rows="5" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; box-sizing: border-box; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['observacion'] ?? ''); ?></textarea>
            
            <br><br>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="submit" style="background: #1a237e; color: white; padding: 12px 30px; border: none; cursor: pointer; border-radius: 6px; font-size: 16px; font-weight: bold;">
                    💾 Guardar Observación
                </button>
                <a href="panel_boletin_primaria.php" style="background: #6c757d; color: white; padding: 12px 30px; border: none; cursor: pointer; border-radius: 6px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block;">
                    ⬅️ Cancelar / Volver
                </a>
            </div>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>