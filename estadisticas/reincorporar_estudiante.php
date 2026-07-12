<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}
require_once "config_db.php";

$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    $conexion->begin_transaction();
    
    // Reactivar estudiante
    $stmt = $conexion->prepare("UPDATE estudiantes SET estatus = 'Activo' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Eliminar el registro de egreso
    $stmt_del = $conexion->prepare("DELETE FROM egresos WHERE estudiante_id = ?");
    $stmt_del->bind_param("i", $id);
    $stmt_del->execute();
    $stmt_del->close();
    
    $conexion->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}