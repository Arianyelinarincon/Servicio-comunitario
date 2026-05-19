<?php
require_once "../config/conexion.php";

header('Content-Type: application/json');

$sala = $_GET['sala'] ?? '';

if (!$sala) {
    echo json_encode(['secciones' => [], 'profesores' => []]);
    exit;
}

$response = ['secciones' => [], 'profesores' => []];

// Cargar secciones por sala (ajusta según tu estructura de BD)
$q_secciones = mysqli_query($conexion, "SELECT id, nombre FROM secciones WHERE sala = '$sala' ORDER BY nombre");
while($sec = mysqli_fetch_assoc($q_secciones)) {
    $response['secciones'][] = $sec;
}

// Cargar profesores por sala
$q_profesores = mysqli_query($conexion, "SELECT id, nombre FROM profesores WHERE sala = '$sala' ORDER BY nombre");
while($prof = mysqli_fetch_assoc($q_profesores)) {
    $response['profesores'][] = $prof;
}

echo json_encode($response);
?>