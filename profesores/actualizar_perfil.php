<?php
session_start();
include_once('../config/conexion.php'); 

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header("Location: ../Login/login.php");
    exit();
}

$id = $_SESSION['id_usuario'];
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE profesores SET telefono = ?, direccion = ?, password = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssi", $telefono, $direccion, $password, $id);
    } else {
        $sql = "UPDATE profesores SET telefono = ?, direccion = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssi", $telefono, $direccion, $id);
    }
    
    if ($stmt->execute()) {
        $mensaje = "<div class='alert success'><i class='fas fa-check-circle'></i> Datos actualizados correctamente.</div>";
    } else {
        $mensaje = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Error al actualizar: " . $stmt->error . "</div>";
    }
}

$stmt_select = $conexion->prepare("SELECT * FROM profesores WHERE id = ?");
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$datos = $stmt_select->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Perfil - UNEFA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #eef2f7; margin: 0; min-height: 100vh; padding: 40px; box-sizing: border-box; }
        .container { max-width: 800px; margin: auto; }
        .content-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h2 { color: #003366; margin-bottom: 25px; }
        
        .form-group { margin-bottom: 20px; position: relative; } /* Posicionamiento relativo para el icono */
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        input, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 16px; }
        textarea { height: 100px; resize: vertical; }
        
        /* Estilo para el icono del ojo */
        .toggle-password { position: absolute; right: 15px; top: 40px; cursor: pointer; color: #777; }
        
        button { background: #003366; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; width: 100%; transition: 0.3s; }
        button:hover { background: #002244; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .back-link { display: inline-block; margin-top: 20px; color: #003366; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="content-box">
        <h2><i class="fas fa-user-edit"></i> Actualizar Perfil</h2>
        <?php echo $mensaje; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Teléfono:</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($datos['telefono'] ?? ''); ?>" placeholder="Ej: 0412-1234567">
            </div>
            
            <div class="form-group">
                <label>Dirección:</label>
                <textarea name="direccion" placeholder="Ingresa tu dirección completa..."><?php echo htmlspecialchars($datos['direccion'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Nueva Contraseña (opcional):</label>
                <input type="password" name="password" id="password" placeholder="Solo si deseas cambiarla">
                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
            </div>
            
            <button type="submit"><i class="fas fa-save"></i> Guardar Cambios</button>
        </form>
        
        <a href="panel_profesor.php" class="back-link"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const passwordInput = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
        // Cambiar el tipo de input
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Cambiar el icono del ojo
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>