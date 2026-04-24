<?php 
// include_once "../profesores/Login/auth.php"; 
include_once "../config/conexion.php";
include_once "../includes/header.php"; 
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>LISTADO DE ESTUDIANTES</h3>
        <a href="gestion.php" class="btn btn-success">+ Nuevo Estudiante</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-striped" id="tablaEstudiantes">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>ID</th>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Género</th>
                        <th>Sala</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM estudiantes ORDER BY id DESC";
                    $resultado = mysqli_query($conexion, $query);

                    while ($fila = mysqli_fetch_assoc($resultado)) {
                        echo "<tr>";
                        echo "<td>{$fila['id']}</td>";
                        echo "<td>{$fila['cedula']}</td>";
                        echo "<td>{$fila['nombre']}</td>";
                        echo "<td>{$fila['genero']}</td>";
                        echo "<td>{$fila['sala']}</td>";
                        echo "<td>
                                <a href='ver_ficha.php?id={$fila['id']}' class='btn btn-info btn-sm'>Ver Ficha</a>
                                <a href='editar.php?id={$fila['id']}' class='btn btn-primary btn-sm'>Editar</a>
                                <a href='eliminar.php?id={$fila['id']}' class='btn btn-danger btn-sm'>Eliminar</a>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>