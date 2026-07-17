<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once '../config/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header("Location: gestionar_profesores.php");
    exit();
}

// Obtener datos del profesor
$stmt = $conexion->prepare("SELECT * FROM profesores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$profesor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profesor) {
    header("Location: gestionar_profesores.php");
    exit();
}

// Obtener estudiantes asignados (opcional, si hay relación)
$sql_estudiantes = "SELECT e.id, e.nombre, e.apellido, e.sala, e.cedula_escolar 
                    FROM estudiantes e 
                    WHERE e.sala = ? 
                    ORDER BY e.nombre ASC";
$stmt2 = $conexion->prepare($sql_estudiantes);
$stmt2->bind_param("s", $profesor['sala']);
$stmt2->execute();
$estudiantes = $stmt2->get_result();
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Profesor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef2f7; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h2 { color: #003366; border-bottom: 2px solid #003366; padding-bottom: 10px; }
        .info-group { margin-bottom: 15px; }
        .info-label { font-weight: bold; width: 150px; display: inline-block; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f2f2f2; }
        .btn { display: inline-block; padding: 10px 15px; background: #003366; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #002244; }
    </style>
</head>
<body>
<div class="container">
    <h2><i class="fas fa-chalkboard-user"></i> Detalle del Profesor</h2>
    <div class="info-group"><span class="info-label">ID:</span> <?php echo $profesor['id']; ?></div>
    <div class="info-group"><span class="info-label">Nombre:</span> <?php echo htmlspecialchars($profesor['nombre']); ?></div>
    <div class="info-group"><span class="info-label">Apellido:</span> <?php echo htmlspecialchars($profesor['apellido']); ?></div>
    <div class="info-group"><span class="info-label">Cédula:</span> <?php echo htmlspecialchars($profesor['cedula'] ?? 'N/A'); ?></div>
    <div class="info-group"><span class="info-label">Teléfono:</span> <?php echo htmlspecialchars($profesor['telefono'] ?? 'N/A'); ?></div>
    <div class="info-group"><span class="info-label">Dirección:</span> <?php echo htmlspecialchars($profesor['direccion'] ?? 'N/A'); ?></div>
    <div class="info-group"><span class="info-label">Sala/Grado:</span> <?php echo htmlspecialchars($profesor['sala']); ?></div>
    <div class="info-group"><span class="info-label">Sección ID:</span> <?php echo htmlspecialchars($profesor['seccion']); ?></div>
    <div class="info-group"><span class="info-label">Estatus:</span> 
        <span style="background: <?php echo ($profesor['estatus'] == 'Activo') ? '#28a745' : '#dc3545'; ?>; color: white; padding: 2px 8px; border-radius: 12px;">
            <?php echo $profesor['estatus']; ?>
        </span>
    </div>
    <div class="info-group"><span class="info-label">Rol:</span> <?php echo htmlspecialchars($profesor['rol']); ?></div>

    <h3>Estudiantes asignados (misma sala)</h3>
    <?php if ($estudiantes->num_rows > 0): ?>
        <table class="table">
            <thead><tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Cédula Escolar</th></tr></thead>
            <tbody>
            <?php while($row = $estudiantes->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                <td><?php echo htmlspecialchars($row['cedula_escolar']); ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay estudiantes asignados a esta sala.</p>
    <?php endif; ?>

    <a href="gestionar_profesores.php" class="btn"><i class="fas fa-arrow-left"></i> Volver</a>
</div>
</body>
</html>