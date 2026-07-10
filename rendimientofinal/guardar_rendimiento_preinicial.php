<?php
session_start();
// ========== CORRECCIÓN 1: Ruta de conexión unificada ==========
// ANTES: require_once '../config/conexion.php';
// AHORA: Usamos config_db.php que tiene la configuración completa
require_once '../estadisticas/config_db.php';

// ========== CORRECCIÓN 2: Verificar que la conexión existe ==========
if (!isset($conexion) || $conexion->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit();
}

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

// ========== CORRECCIÓN 3: Validar formato del período ==========
if (!preg_match('/^\d{4}-\d{4}$/', $periodo)) {
    echo json_encode(['success' => false, 'message' => 'Formato de período inválido. Use AAAA-AAAA']);
    exit();
}

// ========== CORRECCIÓN 4: Validar que $sala sea un valor permitido ==========
$salas_permitidas = ['sala4', 'sala5', '1ro', '2do', '3ro', '4to', '5to', '6to'];
if (!in_array($sala, $salas_permitidas)) {
    echo json_encode(['success' => false, 'message' => 'Sala/Grado inválido']);
    exit();
}

$conexion->begin_transaction();
try {
    // ========== CORRECCIÓN 5: Verificar que la tabla existe ==========
    $check_table = $conexion->query("SHOW TABLES LIKE 'rendimiento_estudiantil'");
    if ($check_table->num_rows === 0) {
        // Crear la tabla si no existe
        $conexion->query("CREATE TABLE IF NOT EXISTS rendimiento_estudiantil (
            id INT AUTO_INCREMENT PRIMARY KEY,
            estudiante_id INT NOT NULL,
            periodo VARCHAR(10) NOT NULL,
            aprobado ENUM('SI','NO') DEFAULT 'SI',
            observacion TEXT,
            UNIQUE KEY unique_registro (estudiante_id, periodo),
            INDEX idx_periodo (periodo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    foreach ($aprobados as $estudiante_id => $aprobado) {
        $estudiante_id = intval($estudiante_id);
        // ========== CORRECCIÓN 6: Sanitizar observación ==========
        $observacion = trim($observaciones[$estudiante_id] ?? '');
        $observacion = htmlspecialchars($observacion, ENT_QUOTES, 'UTF-8');
        
        // ========== CORRECCIÓN 7: Validar que aprobado sea SI o NO ==========
        $aprobado = strtoupper($aprobado);
        if (!in_array($aprobado, ['SI', 'NO'])) {
            $aprobado = 'SI';
        }
        
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