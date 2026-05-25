<?php
session_start();
// Control de seguridad: solo Super Admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

include_once('../../config/conexion.php');

$mensaje = "";

// PROCESAR EL FORMULARIO CUANDO SE ENVÍA
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = strtoupper(trim($_POST['nombre'])); // Guardamos en mayúsculas como en tu volcado SQL
    $seccion_id = intval($_POST['seccion']);
    $sala = trim($_POST['sala']);

    if (!empty($nombre) && !empty($sala) && !empty($seccion_id)) {
        // Insertar el nuevo profesor (por defecto el estatus es 'Activo')
        $sql_insert = "INSERT INTO profesores (nombre, seccion, sala, estatus) VALUES (?, ?, ?, 'Activo')";
        $stmt = $conexion->prepare($sql_insert);
        $stmt->bind_param("sis", $nombre, $seccion_id, $sala);

        if ($stmt->execute()) {
            $mensaje = "<div class='alert success'><i class='fas fa-check-circle'></i> ¡Profesor registrado con éxito! Ya puede iniciar sesión.</div>";
        } else {
            $mensaje = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Error al registrar en la base de datos.</div>";
        }
    } else {
        $mensaje = "<div class='alert error'><i class='fas fa-exclamation-triangle'></i> Todos los campos son obligatorios.</div>";
    }
}

// OBTENER LAS SECCIONES PARA EL MENÚ DESPLEGABLE
$sql_secciones = "SELECT * FROM secciones ORDER BY sala, nombre";
$resultado_secciones = $conexion->query($sql_secciones);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Nuevo Profesor</title>
    <link rel="stylesheet" href="Estilo/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: var(--bg-principal, #f4f4f4); padding: 30px; }
        .container { max-width: 550px; margin: 0 auto; background: var(--bg-caja, white); padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-top: 5px solid #28a745; }
        h2 { color: #28a745; margin-bottom: 20px; text-transform: uppercase; font-size: 20px; }
        .btn-back { display: inline-block; margin-bottom: 20px; color: #004b93; text-decoration: none; font-weight: bold; }
        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: bold; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; font-size: 16px; cursor: pointer; transition: background 0.2s; }
        .btn-submit:hover { background: #218838; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn-back {
    display: inline-block;
    margin-bottom: 20px;
    color: #004b93;
    text-decoration: none;
    font-weight: bold;
    padding: 8px 12px;
    border-radius: 4px;
    background: #e6f0fa;
    transition: background 0.2s;
}
.btn-back:hover {
    background: #ccdff2;
    color: #003366;
}
    </style>
</head>
<body>

    <div class="container">
        <a href="../../index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        
        <h2><i class="fas fa-user-plus"></i> Registrar Profesor</h2>
        
        <?php echo $mensaje; ?>

        <form action="agregar_profesor.php" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre Completo del Profesor (Será su Usuario):</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej. PEDRO PEREZ" required>
            </div>

            <div class="form-group">
                <label for="sala">Sala / Grado:</label>
                <select id="sala" name="sala" required>
                    <option value="">-- Selecciona una opción --</option>
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
                <label for="seccion">Sección Asignada:</label>
                <select id="seccion" name="seccion" required>
                    <option value="">-- Selecciona la Sección --</option>
                    <?php 
                    if($resultado_secciones && $resultado_secciones->num_rows > 0) {
                        while($sec = $resultado_secciones->fetch_assoc()) {
                            echo "<option value='".$sec['id']."'>".$sec['sala']." - Sección ".$sec['nombre']."</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Guardar Profesor</button>
        </form>
    </div>

</body>
</html>