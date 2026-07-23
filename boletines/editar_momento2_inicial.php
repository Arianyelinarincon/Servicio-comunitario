<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once '../config/conexion.php';

$boletin_id = isset($_GET['boletin_id']) ? intval($_GET['boletin_id']) : 0;
if ($boletin_id <= 0) die('ID de boletín no válido');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proyecto = trim($_POST['m2_proyecto'] ?? '');
    $formacion = trim($_POST['m2_formacion'] ?? '');
    $relacion = trim($_POST['m2_relacion'] ?? '');
    $sugerencias = trim($_POST['m2_sugerencias'] ?? '');
    $sql = "UPDATE boletines SET m2_proyecto=?, m2_formacion=?, m2_relacion=?, m2_sugerencias=? WHERE id=? AND tipo_boletin='inicial'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ssssi', $proyecto, $formacion, $relacion, $sugerencias, $boletin_id);
    if ($stmt->execute()) {
        $_SESSION['m2_proyecto'] = $proyecto;
        $_SESSION['m2_formacion'] = $formacion;
        $_SESSION['m2_relacion'] = $relacion;
        $_SESSION['m2_sugerencias'] = $sugerencias;
        $_SESSION['mensaje_exito'] = 'Momento 2 actualizado correctamente.';
    } else {
        $_SESSION['mensaje_error'] = 'Error al guardar: ' . $conexion->error;
    }
    header('Location: panel_boletin_inicial.php?editar_id=' . $boletin_id);
    exit;
}

$stmt = $conexion->prepare("SELECT m2_proyecto, m2_formacion, m2_relacion, m2_sugerencias FROM boletines WHERE id = ?");
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
    <title>Editar Momento 2 - Inicial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-edit"></i> Editar Momento 2 - Inicial</h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="m2_proyecto" class="form-label">Proyecto de Aprendizaje</label>
                    <textarea class="form-control" id="m2_proyecto" name="m2_proyecto" rows="3"><?= htmlspecialchars($datos['m2_proyecto'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="m2_formacion" class="form-label">Formación</label>
                    <textarea class="form-control" id="m2_formacion" name="m2_formacion" rows="3"><?= htmlspecialchars($datos['m2_formacion'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="m2_relacion" class="form-label">Relación</label>
                    <textarea class="form-control" id="m2_relacion" name="m2_relacion" rows="3"><?= htmlspecialchars($datos['m2_relacion'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="m2_sugerencias" class="form-label">Sugerencias</label>
                    <textarea class="form-control" id="m2_sugerencias" name="m2_sugerencias" rows="3"><?= htmlspecialchars($datos['m2_sugerencias'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
                <a href="panel_boletin_inicial.php?editar_id=<?= $boletin_id ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php include '../includes/footer.php'; ?>