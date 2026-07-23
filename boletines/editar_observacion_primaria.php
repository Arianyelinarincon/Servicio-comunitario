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
    $observacion = trim($_POST['observacion'] ?? '');
    $sql = "UPDATE boletines SET observacion = ? WHERE id = ? AND tipo_boletin = 'primaria'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('si', $observacion, $boletin_id);
    if ($stmt->execute()) {
        $_SESSION['observacion'] = $observacion;
        $_SESSION['mensaje_exito'] = 'Observación actualizada correctamente.';
    } else {
        $_SESSION['mensaje_error'] = 'Error al guardar: ' . $conexion->error;
    }
    header('Location: panel_boletin_primaria.php?editar_id=' . $boletin_id);
    exit;
}

$stmt = $conexion->prepare("SELECT observacion FROM boletines WHERE id = ?");
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
    <title>Editar Observación - Primaria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-edit"></i> Editar Observación General - Primaria</h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="observacion" class="form-label">Observación General</label>
                    <textarea class="form-control" id="observacion" name="observacion" rows="5"><?= htmlspecialchars($datos['observacion'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
                <a href="panel_boletin_primaria.php?editar_id=<?= $boletin_id ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php include '../includes/footer.php'; ?>