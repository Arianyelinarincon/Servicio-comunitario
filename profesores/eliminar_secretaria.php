<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'super_admin') {
    header("Location: /servicio-comunitario/index.php");
    exit();
}
require_once '../config/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header("Location: gestionar_usuarios.php");
    exit();
}

// Proteger usuarios críticos
$stmt = $conexion->prepare("SELECT usuario FROM secretaria WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user && ($user['usuario'] === 'doris_admin' || $user['usuario'] === 'directora')) {
    header("Location: gestionar_usuarios.php?error=usuario_protegido");
    exit();
}

// Obtener datos para auditoría
$stmt = $conexion->prepare("SELECT nombre, usuario FROM secretaria WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Eliminar (físico o lógico según prefieras, aquí hacemos físico)
$stmt = $conexion->prepare("DELETE FROM secretaria WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Auditoría
if (!function_exists('registrarAuditoria')) {
    function registrarAuditoria($conexion, $usuario_id, $accion, $tabla, $registro_id, $detalles = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $conexion->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalles, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ississ", $usuario_id, $accion, $tabla, $registro_id, $detalles, $ip, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
    }
}
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$detalles = "Eliminó secretaria: {$data['nombre']} (Usuario: {$data['usuario']})";
registrarAuditoria($conexion, $usuario_id, 'ELIMINAR_SECRETARIA', 'secretaria', $id, $detalles);

header("Location: gestionar_usuarios.php?msg=deleted");
exit();