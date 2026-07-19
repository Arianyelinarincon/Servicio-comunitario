<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}
require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit();
}

// Verificar CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit();
}

try {
    $stmt = $conexion->prepare("DELETE FROM boletines WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $filas = $stmt->affected_rows;
    $stmt->close();

    if ($filas > 0) {
        echo json_encode(['success' => true, 'mensaje' => 'Boletín eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró el boletín']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}