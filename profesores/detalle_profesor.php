<?php
// --- SEGURIDAD ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

include "../includes/header.php";
include "../config/conexion.php";

$id = $_GET['id']; 

$sql_profesor = "SELECT * FROM profesores WHERE id = $id";
$res_profesor = mysqli_query($conexion, $sql_profesor);
$prof = mysqli_fetch_assoc($res_profesor);

// 2. Consulta incluyendo el ID del estudiante
$sql_datos = "SELECT estudiantes.id, secciones.nombre AS nombre_seccion, estudiantes.sala, estudiantes.nombre, estudiantes.apellido 
              FROM estudiantes 
              JOIN secciones ON estudiantes.sala = secciones.sala
              JOIN profesores ON secciones.id = profesores.seccion
              WHERE profesores.id = $id";

$res_datos = mysqli_query($conexion, $sql_datos);
?>

<div class="container mt-4">
    <div class="card shadow p-4">
        <h2>Detalles de: <?php echo $prof['nombre']; ?></h2>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Usuario:</strong> <?php echo $prof['usuario']; ?></p>
            </div>
        </div>

        <h4>Carga Académica</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th> <th>Sección</th>
                    <th>Sala</th>
                    <th>Estudiante</th>
                    <th>Estatus Notas</th>
                </tr>
            </thead>
            <tbody>
                <?php if($res_datos && mysqli_num_rows($res_datos) > 0) { ?>
                    <?php while($row = mysqli_fetch_assoc($res_datos)) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td> <td><?php echo $row['nombre_seccion']; ?></td>
                        <td><?php echo ucfirst($row['sala']); ?></td>
                        <td><?php echo $row['nombre'] . ' ' . $row['apellido']; ?></td>
                        <td>
                            <span class="badge bg-warning text-dark">Pendiente</span>
                        </td>
                    </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay estudiantes asignados.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <a href="gestionar_profesores.php" class="btn btn-secondary">Volver</a>
    </div>
</div>
<?php include "../includes/footer.php"; ?>