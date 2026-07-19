<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'admin', 'super_admin', 'directiva'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}
require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$resultados = [
    'boletines' => 0,
    'inscripciones' => 0,
    'rendimiento' => 0,
    'ingresos' => 0,
    'egresos' => 0
];

// Contar boletines
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM boletines WHERE estudiante_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultados['boletines'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Contar inscripciones
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM inscripciones WHERE estudiante_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultados['inscripciones'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Contar rendimiento_estudiantil
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM rendimiento_estudiantil WHERE estudiante_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultados['rendimiento'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Contar ingresos (tabla ingresos usa el mismo ID que estudiante)
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM ingresos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultados['ingresos'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Contar egresos
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM egresos WHERE estudiante_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultados['egresos'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

echo json_encode(['success' => true] + $resultados);
exit;
?>