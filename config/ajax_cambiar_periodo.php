<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/configuracion.php';

$nuevo_periodo = trim($_POST['periodo'] ?? '');
if (empty($nuevo_periodo)) {
    echo json_encode(['success' => false, 'error' => 'Periodo no válido']);
    exit();
}

// Validar formato (YYYY-YYYY)
if (!preg_match('/^\d{4}-\d{4}$/', $nuevo_periodo)) {
    echo json_encode(['success' => false, 'error' => 'Formato inválido. Use AAAA-AAAA']);
    exit();
}

// Validar que el año inicio sea menor que el año fin
$parts = explode('-', $nuevo_periodo);
if ($parts[0] >= $parts[1]) {
    echo json_encode(['success' => false, 'error' => 'El año de inicio debe ser menor que el año de fin']);
    exit();
}

$resultado = actualizarPeriodoEscolar($nuevo_periodo);

if ($resultado) {
    // Auditoría (si existe la función)
    if (function_exists('registrarAuditoria')) {
        global $conexion;
        $usuario_id = $_SESSION['usuario_id'] ?? 0;
        $detalles = "Periodo escolar actualizado a: $nuevo_periodo";
        registrarAuditoria($conexion, $usuario_id, 'CAMBIAR_PERIODO_ESCOLAR', 'configuracion', 0, $detalles);
    }
    echo json_encode(['success' => true, 'mensaje' => 'Periodo actualizado correctamente']);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar el periodo']);
}
?>