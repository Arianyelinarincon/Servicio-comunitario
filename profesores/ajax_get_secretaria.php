<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'super_admin') {
    http_response_code(403);
    exit('No autorizado');
}

require_once '../config/conexion.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido');
}

$stmt = $conexion->prepare("SELECT id, nombre, usuario, rol, telefono, email, estatus FROM secretaria WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    header('Content-Type: application/json');
    echo json_encode($row);
} else {
    http_response_code(404);
    exit('Usuario no encontrado');
}
$stmt->close();
?>