<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$periodo = trim($_POST['periodo'] ?? '');
$sala = trim($_POST['sala'] ?? '');
$seccion_id = intval($_POST['seccion'] ?? 0);
$profesor_id = intval($_POST['profesor'] ?? 0);
$aprobados = $_POST['aprobado'] ?? [];
$observaciones = $_POST['observacion'] ?? [];

if (empty($periodo) || empty($sala) || empty($seccion_id)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos (período, grado o sección)']);
    exit();
}

if (!preg_match('/^\d{4}-\d{4}$/', $periodo)) {
    echo json_encode(['success' => false, 'message' => 'Formato de período inválido. Use AAAA-AAAA']);
    exit();
}

$conexion->begin_transaction();
try {
    foreach ($aprobados as $estudiante_id => $aprobado) {
        $estudiante_id = intval($estudiante_id);
        $observacion = trim($observaciones[$estudiante_id] ?? '');
        
        $stmt = $conexion->prepare("INSERT INTO rendimiento_estudiantil 
            (estudiante_id, periodo, aprobado, observacion) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            aprobado = VALUES(aprobado), 
            observacion = VALUES(observacion)");
        $stmt->bind_param("isss", $estudiante_id, $periodo, $aprobado, $observacion);
        $stmt->execute();
        $stmt->close();
    }
    $conexion->commit();
    echo json_encode(['success' => true, 'message' => 'Datos guardados correctamente']);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
}
?>