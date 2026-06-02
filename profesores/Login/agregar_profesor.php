<?php
session_start();
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'profesor' && $_SESSION['rol'] !== 'administrador')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

include_once('../../config/conexion.php');
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = strtoupper(trim($_POST['nombre']));
    $cedula = trim($_POST['cedula']); // Nuevo campo
    $seccion = trim($_POST['seccion']); // Coincide con la columna 'seccion' (tipo INT en tu BD)
    $sala = trim($_POST['sala']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $permiso_editar = 1; 

    // Validamos solo campos que existen físicamente en 'administradores'
    if (!empty($nombre) && !empty($cedula) && !empty($sala) && !empty($seccion)) {
        
        // SQL ajustado a los nombres reales de columnas y tabla
        $sql_insert = "INSERT INTO administradores (nombre_profesores, cedula, seccion, sala, telefono, direccion, permiso_editar_perfil, estatus, rol) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo', 'profesor')";
        
        $stmt = $conexion->prepare($sql_insert);
        // Ajustado a 6 parámetros: s (nombre), i (seccion), s (sala), s (telefono), s (direccion), i (permiso)
        $stmt->bind_param("sissssi", $nombre, $cedula, $seccion, $sala, $telefono, $direccion, $permiso_editar);

        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'><i class='fas fa-check-circle'></i> ¡Profesor registrado con éxito!</div>";
        } else {
            $mensaje = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error en BD: " . $stmt->error . "</div>";
        }
    } else {
        $mensaje = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Nombre, Sala y Sección son obligatorios.</div>";
    }
}

// Asegúrate de que la tabla 'secciones' exista para que esto funcione
$sql_secciones = "SELECT * FROM secciones ORDER BY sala, nombre";
$resultado_secciones = $conexion->query($sql_secciones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Profesor - UEBN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
        .header-admin { background: #003366; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { flex: 1; padding: 40px; display: flex; justify-content: center; align-items: center; }
        .form-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 500px; }
        h2 { color: #0401c5; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #444; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 2px solid #eef2f7; border-radius: 8px; box-sizing: border-box; }
        .btn-submit { background: #003366; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div class="header-admin">
        <div><h2><i class="fas fa-shield-alt"></i> Panel Administrativo</h2></div>
        <a href="../../index.php" style="color: white; text-decoration: none;"><i class="fas fa-home"></i> Inicio</a>
    </div>

    <div class="container">
        <div class="form-box">
            <h2><i class="fas fa-user-plus"></i> Registrar Profesor</h2>
            <?php echo $mensaje; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
    <label>Cédula:</label>
    <input type="text" name="cedula" maxlength="12" pattern="[0-9-]+" title="Solo números y guiones" required>
</div>
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" name="telefono">
                </div>
                <div class="form-group">
                    <label>Dirección:</label>
                    <input type="text" name="direccion">
                </div>
                <div class="form-group">
                    <label>Sala / Grado:</label>
                    <select name="sala" required>
                        <option value="">-- Selecciona --</option>
                        <option value="sala4">Sala de 4 años</option>
                        <option value="sala5">Sala de 5 años</option>
                        <option value="1ro">1er Grado</option>
                        <option value="2do">2do Grado</option>
                        <option value="3ro">3er Grado</option>
                        <option value="4to">4to Grado</option>
                        <option value="5to">5to Grado</option>
                        <option value="6to">6to Grado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sección Asignada:</label>
                    <select name="seccion" required>
                        <option value="">-- Selecciona Sección --</option>
                        <?php 
                        if($resultado_secciones && $resultado_secciones->num_rows > 0) {
                            while($sec = $resultado_secciones->fetch_assoc()) {
                                echo "<option value='".$sec['id']."'>".$sec['sala']." - Sección ".$sec['nombre']."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Guardar Registro</button>
            </form>
        </div>
    </div>
</body>
</html>