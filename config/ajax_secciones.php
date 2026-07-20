<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$sala = isset($_GET['sala']) ? trim($_GET['sala']) : '';

if (empty($sala)) {
    echo json_encode([]);
    exit();
}

$sql = "SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $sala);
$stmt->execute();
$result = $stmt->get_result();

$secciones = [];
while ($row = $result->fetch_assoc()) {
    $secciones[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre']
    ];
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($secciones);
?>