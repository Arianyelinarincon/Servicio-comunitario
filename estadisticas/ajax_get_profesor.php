<?php
require_once "../config/conexion.php";

$seccion = $_POST['seccion'] ?? '';
$response = [];

if ($seccion) {
    $stmt = mysqli_prepare($conexion, "SELECT id, nombre FROM profesores WHERE seccion = ? AND estatus = 'Activo' ORDER BY nombre ASC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $seccion);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $response = $row;
    }
    mysqli_stmt_close($stmt);
}

header('Content-Type: application/json');
echo json_encode($response);
?>