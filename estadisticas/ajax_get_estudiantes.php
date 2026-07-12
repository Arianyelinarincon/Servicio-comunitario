<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once "../config/conexion.php";

$sala = $_GET['sala'] ?? '';
$seccion = (int)($_GET['seccion'] ?? 0);

$response = ['estudiantes' => []];

if ($sala && $seccion) {
    $stmt = $conexion->prepare("
        SELECT id, apellido, nombre, cedula, cedula_escolar, genero, fecha_nacimiento
        FROM estudiantes
        WHERE sala = ? AND seccion_id = ? AND estatus = 'Activo'
        ORDER BY apellido ASC, nombre ASC
    ");
    $stmt->bind_param("si", $sala, $seccion);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['estudiantes'][] = [
            'id' => (int)$row['id'],
            'apellido' => $row['apellido'],
            'nombre' => $row['nombre'],
            'cedula' => $row['cedula'] ?? $row['cedula_escolar'] ?? '',
            'genero' => $row['genero'],
            'fecha_nacimiento' => $row['fecha_nacimiento'] ? date('Y-m-d', strtotime($row['fecha_nacimiento'])) : ''
        ];
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
?>