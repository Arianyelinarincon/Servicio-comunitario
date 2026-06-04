<?php
session_start();
// Validar sesión (opcional pero recomendable para que no lo llamen externos)
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once "../config/conexion.php";

header('Content-Type: application/json');

$sala = $_GET['sala'] ?? '';
$response = ['secciones' => [], 'profesores' => []];

if ($sala) {
    // Secciones
    $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
    $stmt->bind_param("s", $sala);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['secciones'][] = $row;
    }
    $stmt->close();

    // Profesores
    $stmt2 = $conexion->prepare("SELECT id, nombre FROM profesores WHERE sala = ? ORDER BY nombre");
    $stmt2->bind_param("s", $sala);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row2 = $result2->fetch_assoc()) {
        $response['profesores'][] = $row2;
    }
    $stmt2->close();
}

echo json_encode($response);
?>