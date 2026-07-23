<?php
session_start();
if (!isset($_SESSION['estudiante'])) {
    header('Location: paso1_portada_primaria.php');
    exit;
}

require_once '../config/conexion.php';

$check = $conexion->query("SHOW TABLES LIKE 'boletines'");
if (!$check || $check->num_rows == 0) {
    die('<div style="color:red;text-align:center;padding:50px;">Error: La tabla boletines no existe.</div>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar en sesión
    $_SESSION['l3_proyecto'] = htmlspecialchars($_POST['l3_proyecto']);
    $_SESSION['l3_analisis'] = htmlspecialchars($_POST['l3_analisis']);
    $_SESSION['l3_sugerencias'] = htmlspecialchars($_POST['l3_sugerencias']);
    
    // El campo 'resultado_final' ahora se determina automáticamente, pero si viene por POST (por si se desactivó JS), lo usamos
    $literal = htmlspecialchars($_POST['literal_final'] ?? '');
    // Determinar resultado según literal
    if (in_array($literal, ['A','B','C','D'])) {
        $resultado = 'Promovido';
    } elseif ($literal == 'E') {
        $resultado = 'Aplazado';
    } else {
        $resultado = $_POST['resultado_final'] ?? ''; // por si vino manual
    }
    
    // Si es Aplazado, forzar literal vacío
    if ($resultado === 'Aplazado') {
        $literal = '';
    }
    
    $_SESSION['resultado_final'] = $resultado;
    $_SESSION['literal_final'] = $literal;
    
    $aprobado = ($resultado === 'Promovido') ? 'SI' : 'NO';
    
    $estudiante_id = $_SESSION['estudiante_id'];
    $periodo = $_SESSION['ano_escolar'] ?? '2025-2026';
    $tipo = 'primaria';
    
    // Verificar si existe
    $stmt = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
    if (!$stmt) die("Error en prepare: " . $conexion->error);
    $stmt->bind_param("iss", $estudiante_id, $periodo, $tipo);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existe) {
        $stmt = $conexion->prepare("UPDATE boletines SET 
            m3_proyecto = ?, m3_formacion = ?, m3_sugerencias = ?,
            resultado_final = ?, literal_final = ?, aprobado = ?
            WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        if (!$stmt) die("Error en prepare (UPDATE): " . $conexion->error);
        $stmt->bind_param("ssssssiss", 
            $_SESSION['l3_proyecto'], 
            $_SESSION['l3_analisis'],
            $_SESSION['l3_sugerencias'],
            $resultado, 
            $literal,
            $aprobado,
            $estudiante_id, $periodo, $tipo);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conexion->prepare("INSERT INTO boletines 
            (estudiante_id, periodo, tipo_boletin, observacion,
             m3_proyecto, m3_formacion, m3_sugerencias,
             resultado_final, literal_final, aprobado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) die("Error en prepare (INSERT): " . $conexion->error);
        $observacion = $_SESSION['observacion'] ?? '';
        $stmt->bind_param("isssssssss", 
            $estudiante_id, $periodo, $tipo, $observacion,
            $_SESSION['l3_proyecto'],
            $_SESSION['l3_analisis'],
            $_SESSION['l3_sugerencias'],
            $resultado, 
            $literal,
            $aprobado);
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
        <h2 style="color: #28a745; text-align: center;">Editar Tercer Lapso + Resultado Final - Primaria</h2>
        <p style="text-align: center; color: #666;">Estudiante: <strong><?php echo htmlspecialchars($_SESSION['estudiante']); ?></strong></p>
        
        <form method="POST" id="formLapso3">
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
                    <input type="text" id="resultado_final_display" readonly 
                           style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; font-size: 14px; background: #f8f9fa; font-weight: bold; color: #1a237e;">
                    <input type="hidden" name="resultado_final" id="resultado_final" value="<?php echo htmlspecialchars($_SESSION['resultado_final'] ?? ''); ?>">
                </div>
                <div>
                    <p style="font-weight: bold; font-size: 14px;">Literal Final:</p>
                    <select name="literal_final" id="literal_final" required style="width: 100%; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px; font-size: 14px;">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectLiteral = document.getElementById('literal_final');
    const displayResultado = document.getElementById('resultado_final_display');
    const hiddenResultado = document.getElementById('resultado_final');

    function actualizarResultado() {
        const literal = selectLiteral.value;
        let resultado = '';
        if (['A','B','C','D'].includes(literal)) {
            resultado = 'Promovido';
        } else if (literal === 'E') {
            resultado = 'Aplazado';
        }
        displayResultado.value = resultado;
        hiddenResultado.value = resultado;
    }

    // Ejecutar al cargar para mostrar el valor guardado
    actualizarResultado();

    // Actualizar al cambiar el literal
    selectLiteral.addEventListener('change', actualizarResultado);

    // Validar antes de enviar: si está vacío, alertar
    document.getElementById('formLapso3').addEventListener('submit', function(e) {
        if (selectLiteral.value === '') {
            e.preventDefault();
            alert('Debe seleccionar una literal para el resultado final.');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>