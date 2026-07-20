<?php
session_start();

// ===== VERIFICAR AUTENTICACIÓN =====
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once '../config/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: listado.php?error=ID inválido');
    exit;
}

// Obtener información del estudiante
$sql = "SELECT e.*, 
        CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
        r.nombre_completo AS representante_nombre
        FROM estudiantes e
        LEFT JOIN representantes r ON e.representante_id = r.id
        WHERE e.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header('Location: listado.php?error=Estudiante no encontrado');
    exit;
}

// Contar registros asociados
$registros = [];

$sqls = [
    'boletines' => "SELECT COUNT(*) as total FROM boletines WHERE estudiante_id = $id",
    'asistencia' => "SELECT COUNT(*) as total FROM asistencia WHERE estudiante_id = $id",
    'inscripciones' => "SELECT COUNT(*) as total FROM inscripciones WHERE estudiante_id = $id",
    'rendimiento' => "SELECT COUNT(*) as total FROM rendimiento_estudiantil WHERE estudiante_id = $id"
];

foreach ($sqls as $tabla => $sql) {
    $result = $conexion->query($sql);
    if ($result) {
        $registros[$tabla] = $result->fetch_assoc()['total'];
    } else {
        $registros[$tabla] = 0;
    }
}

$total_registros = array_sum($registros);

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmacion = isset($_POST['confirmacion']) ? trim($_POST['confirmacion']) : '';
    $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
    $eliminar_registros = isset($_POST['eliminar_registros']) ? true : false;
    
    // Validar confirmación
    if ($confirmacion !== 'ELIMINAR') {
        $error = 'Debes escribir "ELIMINAR" para confirmar la eliminación.';
    } elseif (empty($motivo)) {
        $error = 'Debes especificar un motivo para la eliminación.';
    } elseif ($total_registros > 0 && !$eliminar_registros) {
        $error = 'Debes confirmar que deseas eliminar los registros asociados.';
    } else {
        $conexion->begin_transaction();
        try {
            // ===== OBTENER USUARIO_ID CORRECTAMENTE =====
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            
            // Si no tiene usuario_id, intentar obtenerlo desde secretaria o profesores
            if ($usuario_id == 0 && isset($_SESSION['usuario'])) {
                $sql_user = "SELECT id FROM secretaria WHERE usuario = ?";
                $stmt_user = $conexion->prepare($sql_user);
                if ($stmt_user) {
                    $stmt_user->bind_param('s', $_SESSION['usuario']);
                    $stmt_user->execute();
                    $result_user = $stmt_user->get_result();
                    if ($row_user = $result_user->fetch_assoc()) {
                        $usuario_id = intval($row_user['id']);
                        $_SESSION['usuario_id'] = $usuario_id;
                    } else {
                        $sql_user = "SELECT id FROM profesores WHERE usuario = ?";
                        $stmt_user = $conexion->prepare($sql_user);
                        if ($stmt_user) {
                            $stmt_user->bind_param('s', $_SESSION['usuario']);
                            $stmt_user->execute();
                            $result_user = $stmt_user->get_result();
                            if ($row_user = $result_user->fetch_assoc()) {
                                $usuario_id = intval($row_user['id']);
                                $_SESSION['usuario_id'] = $usuario_id;
                            }
                            $stmt_user->close();
                        }
                    }
                    $stmt_user->close();
                }
            }
            
            $detalles = "Eliminación de estudiante: " . $estudiante['nombre_completo'] . " (ID: $id). Motivo: $motivo. Registros eliminados: " . json_encode($registros);
            
            // ===== USAR LA FUNCIÓN registrarAuditoria =====
            if (function_exists('registrarAuditoria')) {
                registrarAuditoria($conexion, $usuario_id, 'ELIMINAR_ESTUDIANTE', 'estudiantes', $id, $detalles);
            } else {
                // Si no existe la función, insertar directamente
                $detalles_escaped = mysqli_real_escape_string($conexion, $detalles);
                $auditoria_sql = "INSERT INTO auditoria 
                    (usuario_id, accion, tabla_afectada, registro_id, detalles, ip, user_agent, fecha) 
                    VALUES ($usuario_id, 'ELIMINAR_ESTUDIANTE', 'estudiantes', $id, '$detalles_escaped', '127.0.0.1', 'Sistema', NOW())";
                $conexion->query($auditoria_sql);
            }
            
            // Eliminar registros asociados si se confirmó
            if ($eliminar_registros) {
                foreach (array_keys($sqls) as $tabla) {
                    $conexion->query("DELETE FROM $tabla WHERE estudiante_id = $id");
                }
            }
            
            // Eliminar el estudiante (baja física)
            $conexion->query("DELETE FROM estudiantes WHERE id = $id");
            
            $conexion->commit();
            
            header('Location: listado.php?msg=eliminado');
            exit;
            
        } catch (Exception $e) {
            $conexion->rollback();
            $error = 'Error al eliminar: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0"><i class="fas fa-trash-alt me-2"></i> Eliminar Estudiante</h4>
        </div>
        <div class="card-body">
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> ¿Está seguro de eliminar este estudiante?</h5>
                <p><strong>Estudiante:</strong> <?= htmlspecialchars($estudiante['nombre_completo'] ?? $estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></p>
                <p><strong>Cédula Escolar:</strong> <?= htmlspecialchars($estudiante['cedula_escolar'] ?? 'N/A') ?></p>
                <p><strong>Representante:</strong> <?= htmlspecialchars($estudiante['representante_nombre'] ?? 'N/A') ?></p>
            </div>
            
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">Registros Asociados</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-2 border rounded">
                                <h5><?= $registros['boletines'] ?></h5>
                                <small class="text-muted">Boletines</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-2 border rounded">
                                <h5><?= $registros['asistencia'] ?></h5>
                                <small class="text-muted">Registros de Asistencia</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-2 border rounded">
                                <h5><?= $registros['inscripciones'] ?></h5>
                                <small class="text-muted">Inscripciones</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-2 border rounded">
                                <h5><?= $registros['rendimiento'] ?></h5>
                                <small class="text-muted">Rendimiento</small>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <span class="badge bg-warning text-dark">Total: <?= $total_registros ?> registros</span>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Motivo de eliminación <span class="text-danger">*</span></label>
                    <textarea name="motivo" class="form-control" rows="2" required placeholder="Ej: Retiro, traslado, duplicado, etc."></textarea>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="eliminar_registros" name="eliminar_registros" <?= $total_registros > 0 ? '' : 'disabled' ?>>
                        <label class="form-check-label" for="eliminar_registros">
                            <strong>Eliminar todos los registros asociados</strong>
                            <?php if ($total_registros > 0): ?>
                                <span class="badge bg-danger"><?= $total_registros ?> registros</span>
                            <?php else: ?>
                                <span class="badge bg-success">Sin registros asociados</span>
                            <?php endif; ?>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirme escribiendo <strong class="text-danger">ELIMINAR</strong> <span class="text-danger">*</span></label>
                    <input type="text" name="confirmacion" class="form-control" placeholder="Escriba ELIMINAR" required pattern="ELIMINAR">
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="ver_ficha.php?id=<?= $id ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Eliminar Permanentemente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>