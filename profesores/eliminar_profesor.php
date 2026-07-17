<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

// ========== VERIFICAR AUTENTICACIÓN ==========
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'super_admin' && $_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'admin')) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

// ========== OBTENER ID ==========
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: gestionar_profesores.php?msg=error");
    exit();
}

// ========== OBTENER DATOS DEL PROFESOR PARA AUDITORÍA ==========
$stmt = $conexion->prepare("SELECT nombre, apellido FROM profesores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$prof = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prof) {
    header("Location: gestionar_profesores.php?msg=error");
    exit();
}

$nombre_prof = $prof['nombre'] . ' ' . $prof['apellido'];

// ========== ELIMINAR REGISTROS DE AUDITORÍA RELACIONADOS (opcional) ==========
// Si la clave foránea está en ON DELETE CASCADE, esto no es necesario.
// Pero lo dejamos por seguridad.
$stmt_del_audit = $conexion->prepare("DELETE FROM auditoria WHERE usuario_id = ?");
$stmt_del_audit->bind_param("i", $id);
$stmt_del_audit->execute();
$stmt_del_audit->close();

// ========== ELIMINAR PROFESOR (FÍSICO) ==========
$stmt = $conexion->prepare("DELETE FROM profesores WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // ========== AUDITORÍA (usando función centralizada) ==========
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    if ($usuario_id > 0 && function_exists('registrarAuditoria')) {
        registrarAuditoria($conexion, $usuario_id, 'ELIMINAR_PROFESOR', 'profesores', $id, "Profesor eliminado físicamente: $nombre_prof");
    }
    $stmt->close();
    header("Location: gestionar_profesores.php?msg=deleted");
} else {
    $stmt->close();
    header("Location: gestionar_profesores.php?msg=error");
}
exit();
?>