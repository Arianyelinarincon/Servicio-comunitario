<?php
session_start();

// --- BLINDAJE DE SEGURIDAD ---
// Si no existe la sesión de usuario, lo enviamos al login de inmediato.
// Esto evita que se quede "pegado" en la página sin sesión.
if (!isset($_SESSION['usuario'])) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

include "../includes/header.php"; // Ajusta la ruta según donde guardes este archivo
include "../config/conexion.php"; // Asegúrate de tener tu conexión a BD aquí

// Consulta para obtener todos los profesores (que están en la tabla administradores)
$query = "SELECT * FROM administradores";
$resultado = mysqli_query($conexion, $query);

?>

<div class="container mt-4">
    <h2 class="mb-4">Lista de Profesores / Administradores</h2>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($resultado)) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['nombre_profesores']; ?></td>
                <td><?php echo $row['usuario']; ?></td>
                <td><?php echo $row['rol']; ?></td>
                <td><?php echo $row['estatus']; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include "../includes/footer.php"; ?>