<?php
require_once "../config/conexion.php";

$sala = $_POST['sala'] ?? '';
$response = [];

if ($sala) {
    $stmt = mysqli_prepare($conexion, "SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
    mysqli_stmt_bind_param($stmt, "s", $sala);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $response[] = $row;
    }
    mysqli_stmt_close($stmt);
}

header('Content-Type: application/json');
echo json_encode($response);
?>