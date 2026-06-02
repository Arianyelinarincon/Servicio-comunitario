<?php
session_start();
require_once 'config/conexion.php'; 

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: Login/login.php");
    exit();
}

// Lógica de cambio de permiso
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    // Usamos la tabla correcta: administradores
    $stmt = $conexion->prepare("UPDATE administradores SET permiso_editar_perfil = NOT permiso_editar_perfil WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: gestionar_permisos.php");
    exit();
}

// Consultamos la tabla 'administradores' con los nombres de columna reales
$profesores = $conexion->query("SELECT id, nombre_profesores, telefono, direccion, permiso_editar_perfil FROM administradores");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Permisos - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; padding: 0; display: flex; flex-direction: column; }
        .container { flex: 1; padding: 40px; width: 95%; max-width: 1400px; margin: 0 auto; box-sizing: border-box; }
        .content-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; }
        h2 { color: #003366; margin-bottom: 25px; border-bottom: 2px solid #eef2f7; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #003366; color: white; padding: 18px; text-align: left; }
        td { padding: 15px; border-bottom: 1px solid #dee2e6; color: #333; }
        tr:hover { background-color: #f1f4f9; }
        .btn-toggle { text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; display: block; text-align: center; }
        .activado { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .desactivado { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: inline-block; margin-top: 20px; color: #003366; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="content-box">
        <h2><i class="fas fa-user-shield"></i> Gestión de Permisos de Edición</h2>
        <p>Administra los permisos de los profesores para actualizar su información personal:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Estado del Permiso</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $profesores->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['nombre_profesores']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['telefono'] ?? 'No registrado'); ?></td>
                    <td><?php echo htmlspecialchars($row['direccion'] ?? 'No registrado'); ?></td>
                    <td>
                        <a href="gestionar_permisos.php?toggle_id=<?php echo $row['id']; ?>" 
                           class="btn-toggle <?php echo $row['permiso_editar_perfil'] == 1 ? 'activado' : 'desactivado'; ?>">
                           <?php echo $row['permiso_editar_perfil'] == 1 ? "✅ Activado" : "❌ Desactivado"; ?>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
    </div>
</div>

</body>
</html>