<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once '../config/conexion.php';

$boletin_id = isset($_GET['boletin_id']) ? intval($_GET['boletin_id']) : 0;
if ($boletin_id <= 0) die('ID de boletín no válido');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proyecto = trim($_POST['m3_proyecto'] ?? '');
    $analisis = trim($_POST['m3_formacion'] ?? '');
    $sugerencias = trim($_POST['m3_sugerencias'] ?? '');
    $literal = trim($_POST['literal_final'] ?? '');
    
    // Determinar resultado y aprobado según literal
    if (in_array($literal, ['A','B','C','D'])) {
        $resultado = 'Promovido';
        $aprobado = 'SI';
    } elseif ($literal == 'E') {
        $resultado = 'Aplazado';
        $aprobado = 'NO';
    } else {
        $resultado = '';
        $aprobado = '';
    }
    
    $sql = "UPDATE boletines SET 
            m3_proyecto=?, m3_formacion=?, m3_sugerencias=?, 
            resultado_final=?, literal_final=?, aprobado=? 
            WHERE id=? AND tipo_boletin='primaria'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ssssssi', $proyecto, $analisis, $sugerencias, $resultado, $literal, $aprobado, $boletin_id);
    if ($stmt->execute()) {
        $_SESSION['l3_proyecto'] = $proyecto;
        $_SESSION['l3_analisis'] = $analisis;
        $_SESSION['l3_sugerencias'] = $sugerencias;
        $_SESSION['resultado_final'] = $resultado;
        $_SESSION['literal_final'] = $literal;
        $_SESSION['mensaje_exito'] = 'Lapso 3 y resultado final actualizados correctamente.';
    } else {
        $_SESSION['mensaje_error'] = 'Error al guardar: ' . $conexion->error;
    }
    header('Location: panel_boletin_primaria.php?editar_id=' . $boletin_id);
    exit;
}

$stmt = $conexion->prepare("SELECT m3_proyecto, m3_formacion, m3_sugerencias, resultado_final, literal_final FROM boletines WHERE id = ?");
$stmt->bind_param('i', $boletin_id);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
if (!$datos) die('Boletín no encontrado');

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Lapso 3 y Resultado - Primaria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-edit"></i> Editar Lapso 3 y Resultado Final - Primaria</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i> 
                <strong>Importante:</strong> 
                Seleccione primero el <strong>Literal Final</strong> para que el sistema determine automáticamente 
                la situación académica del estudiante (Promovido o Aplazado).
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label for="m3_proyecto" class="form-label">Proyectos de Aprendizaje</label>
                    <textarea class="form-control" id="m3_proyecto" name="m3_proyecto" rows="3"><?= htmlspecialchars($datos['m3_proyecto'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="m3_formacion" class="form-label">Análisis Cualitativo</label>
                    <textarea class="form-control" id="m3_formacion" name="m3_formacion" rows="3"><?= htmlspecialchars($datos['m3_formacion'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="m3_sugerencias" class="form-label">Sugerencias</label>
                    <textarea class="form-control" id="m3_sugerencias" name="m3_sugerencias" rows="3"><?= htmlspecialchars($datos['m3_sugerencias'] ?? '') ?></textarea>
                </div>
                <hr>
                <h5>Resultado Final</h5>
                <div class="mb-3">
                    <label for="resultado_final" class="form-label">Situación del Estudiante (se calcula automáticamente)</label>
                    <input type="text" id="resultado_final_display" class="form-control" readonly 
                           value="<?= htmlspecialchars($datos['resultado_final'] ?? '') ?>" 
                           style="background: #f8f9fa; font-weight: bold; color: #1a237e;">
                    <input type="hidden" name="resultado_final" id="resultado_final" value="<?= htmlspecialchars($datos['resultado_final'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="literal_final" class="form-label">Literal Final <span class="text-danger">*</span></label>
                    <select name="literal_final" id="literal_final" required 
                            class="form-select" 
                            title="Seleccione el literal para calcular el resultado final (A-D = Promovido, E = Aplazado)">
                        <option value="">Seleccione</option>
                        <option value="A" <?= ($datos['literal_final'] ?? '') == 'A' ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= ($datos['literal_final'] ?? '') == 'B' ? 'selected' : '' ?>>B</option>
                        <option value="C" <?= ($datos['literal_final'] ?? '') == 'C' ? 'selected' : '' ?>>C</option>
                        <option value="D" <?= ($datos['literal_final'] ?? '') == 'D' ? 'selected' : '' ?>>D</option>
                        <option value="E" <?= ($datos['literal_final'] ?? '') == 'E' ? 'selected' : '' ?>>E</option>
                    </select>
                    <div class="form-text text-muted">
                        <i class="fas fa-question-circle"></i> 
                        Literales A-D: <strong>Promovido</strong> | Literal E: <strong>Aplazado</strong>
                    </div>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
                <a href="panel_boletin_primaria.php?editar_id=<?= $boletin_id ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
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

    actualizarResultado();
    selectLiteral.addEventListener('change', actualizarResultado);

    document.querySelector('form').addEventListener('submit', function(e) {
        if (selectLiteral.value === '') {
            e.preventDefault();
            alert('Debe seleccionar una literal para el resultado final.');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>