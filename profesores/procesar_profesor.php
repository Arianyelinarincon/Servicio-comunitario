<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once __DIR__ . '/../config/conexion.php';

// ========== RECIBIR DATOS ==========
$nombre = strtoupper(trim($_POST['nombre'] ?? ''));
$apellido = strtoupper(trim($_POST['apellido'] ?? ''));
$cedula = trim($_POST['cedula'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$sala = $_POST['sala'] ?? '';
$seccion = intval($_POST['seccion'] ?? 0);

// ========== VALIDAR ==========
if (empty($nombre) || empty($apellido) || empty($cedula) || empty($sala) || $seccion <= 0) {
    header("Location: agregar_profesor.php?error=campos_requeridos");
    exit();
}

// ========== VERIFICAR SI LA CÉDULA YA EXISTE ==========
$stmt = $conexion->prepare("SELECT id FROM profesores WHERE cedula = ?");
$stmt->bind_param("s", $cedula);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: agregar_profesor.php?error=cedula_duplicada");
    exit();
}
$stmt->close();

// ========== INSERTAR PROFESOR ==========
$sql = "INSERT INTO profesores (nombre, apellido, cedula, telefono, direccion, sala, seccion, estatus, rol) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo', 'profesor')";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssssssi", $nombre, $apellido, $cedula, $telefono, $direccion, $sala, $seccion);

if ($stmt->execute()) {
    // ========== AUDITORÍA ==========
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    if ($usuario_id > 0 && function_exists('registrarAuditoria')) {
        registrarAuditoria($conexion, $usuario_id, 'AGREGAR_PROFESOR', 'profesores', $conexion->insert_id, "Profesor: $nombre $apellido (Cédula: $cedula, Sala: $sala, Sección: $seccion)");
    }
    $stmt->close();
    header("Location: gestionar_profesores.php?msg=added");
} else {
    $stmt->close();
    header("Location: agregar_profesor.php?error=db_error");
}
exit();
?>