<?php
session_start();

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: listado.php?error=ID inválido');
    exit;
}

$sql = "SELECT e.*, s.nombre AS seccion_nombre 
        FROM estudiantes e 
        LEFT JOIN secciones s ON e.seccion_id = s.id 
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

$periodo_actual = obtenerPeriodoEscolar();
list($anio_inicio, $anio_fin) = explode('-', $periodo_actual);
$proximo_periodo = ($anio_inicio + 1) . '-' . ($anio_fin + 1);

function obtenerSiguientesSalas($sala_actual) {
    $mapa = [
        'sala4' => ['sala5'],
        'sala5' => ['1ro'],
        '1ro' => ['2do'],
        '2do' => ['3ro'],
        '3ro' => ['4to'],
        '4to' => ['5to'],
        '5to' => ['6to'],
        '6to' => ['6to']
    ];
    return $mapa[$sala_actual] ?? [$sala_actual];
}

$salas_siguientes = obtenerSiguientesSalas($estudiante['sala']);
$secciones_disponibles = [];
if (!empty($salas_siguientes)) {
    $placeholders = implode(',', array_fill(0, count($salas_siguientes), '?'));
    $sql = "SELECT id, nombre, sala FROM secciones WHERE sala IN ($placeholders) ORDER BY sala, nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($salas_siguientes)), ...$salas_siguientes);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $secciones_disponibles[] = $row;
    }
    $stmt->close();
}

// ========== PROCESAR PASE DE AÑO ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_sala = isset($_POST['nueva_sala']) ? trim($_POST['nueva_sala']) : '';
    $nueva_seccion_id = isset($_POST['nueva_seccion']) ? intval($_POST['nueva_seccion']) : 0;
    $limpiar_boletin = isset($_POST['limpiar_boletin']) ? true : false;
    $nuevo_periodo = isset($_POST['nuevo_periodo']) ? trim($_POST['nuevo_periodo']) : $proximo_periodo;
    
    if (empty($nueva_sala) || $nueva_seccion_id <= 0) {
        $error = 'Seleccione una sala y sección válidas.';
    } else {
        $conexion->begin_transaction();
        try {
            $fecha_actual = date('Y-m-d');
            $funcionario = $_SESSION['nombre_profesor'] ?? $_SESSION['usuario'] ?? 'Sistema';
            
            // ===== 1. GUARDAR EL AÑO ACTUAL EN EL HISTORIAL (si no existe) =====
            $sql_check = "SELECT id FROM inscripciones WHERE estudiante_id = ? AND ano_escolar = ?";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param('is', $id, $periodo_actual);
            $stmt_check->execute();
            $existe = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();
            
            if (!$existe) {
                $sql_historial = "INSERT INTO inscripciones 
                    (estudiante_id, ano_escolar, grado_seccion, registro, repite, 
                     c, f, p, peso, talla, fecha_inscripcion, funcionario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conexion->prepare($sql_historial);
                $grado_seccion_actual = $estudiante['sala'] . ' - ' . ($estudiante['seccion_nombre'] ?? '');
                
                // ========== CORRECCIÓN: usar variables en lugar de literales ==========
                $registro = '';
                $repite = 'No';
                $c = '';
                $f = '';
                $p = '';
                $peso_null = null;
                $talla_null = null;
                
                $stmt->bind_param(
                    'isssssssddss',
                    $id,
                    $periodo_actual,
                    $grado_seccion_actual,
                    $registro,
                    $repite,
                    $c,
                    $f,
                    $p,
                    $peso_null,
                    $talla_null,
                    $fecha_actual,
                    $funcionario
                );
                $stmt->execute();
                $stmt->close();
            }
            
            // ===== 2. ACTUALIZAR ESTUDIANTE A LA NUEVA SALA Y SECCIÓN =====
            $sql_update = "UPDATE estudiantes SET sala = ?, seccion_id = ? WHERE id = ?";
            $stmt = $conexion->prepare($sql_update);
            $stmt->bind_param('sii', $nueva_sala, $nueva_seccion_id, $id);
            $stmt->execute();
            $stmt->close();
            
            // ===== 3. CREAR NUEVO REGISTRO EN EL HISTORIAL PARA EL NUEVO PERIODO =====
            $stmt_sec = $conexion->prepare("SELECT nombre FROM secciones WHERE id = ?");
            $stmt_sec->bind_param("i", $nueva_seccion_id);
            $stmt_sec->execute();
            $sec_res = $stmt_sec->get_result()->fetch_assoc();
            $nuevo_grado_seccion = $nueva_sala . ' - ' . ($sec_res['nombre'] ?? '');
            $stmt_sec->close();
            
            $sql_nuevo_historial = "INSERT INTO inscripciones 
                (estudiante_id, ano_escolar, grado_seccion, registro, repite, 
                 c, f, p, peso, talla, fecha_inscripcion, funcionario) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql_nuevo_historial);
            
            // ========== CORRECCIÓN: usar variables en lugar de literales ==========
            $registro2 = '';
            $repite2 = 'No';
            $c2 = '';
            $f2 = '';
            $p2 = '';
            $peso_null2 = null;
            $talla_null2 = null;
            
            $stmt->bind_param(
                'isssssssddss',
                $id,
                $nuevo_periodo,
                $nuevo_grado_seccion,
                $registro2,
                $repite2,
                $c2,
                $f2,
                $p2,
                $peso_null2,
                $talla_null2,
                $fecha_actual,
                $funcionario
            );
            $stmt->execute();
            $stmt->close();
            
            // ===== 4. ACTUALIZAR EL BOLETÍN =====
            $sql_boletin = "SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ?";
            $stmt = $conexion->prepare($sql_boletin);
            $stmt->bind_param('is', $id, $periodo_actual);
            $stmt->execute();
            $boletin_existente = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $tipos_inicial = ['sala4', 'sala5'];
            $nuevo_tipo = in_array($nueva_sala, $tipos_inicial) ? 'inicial' : 'primaria';
            
            if ($boletin_existente) {
                if ($limpiar_boletin) {
                    $sql_clear = "UPDATE boletines SET 
                        observacion = NULL,
                        m1_proyecto = NULL, m1_formacion = NULL, m1_relacion = NULL, m1_sugerencias = NULL,
                        m2_proyecto = NULL, m2_formacion = NULL, m2_relacion = NULL, m2_sugerencias = NULL,
                        m3_proyecto = NULL, m3_formacion = NULL, m3_relacion = NULL, m3_sugerencias = NULL,
                        resultado_final = NULL, literal_final = NULL,
                        tipo_boletin = ?, periodo = ?
                        WHERE id = ?";
                    $stmt = $conexion->prepare($sql_clear);
                    $stmt->bind_param('ssi', $nuevo_tipo, $nuevo_periodo, $boletin_existente['id']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $sql_update_boletin = "UPDATE boletines SET periodo = ? WHERE id = ?";
                    $stmt = $conexion->prepare($sql_update_boletin);
                    $stmt->bind_param('si', $nuevo_periodo, $boletin_existente['id']);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $sql_new = "INSERT INTO boletines 
                    (estudiante_id, periodo, tipo_boletin, observacion, fecha_emision) 
                    VALUES (?, ?, ?, '', NOW())";
                $stmt = $conexion->prepare($sql_new);
                $stmt->bind_param('iss', $id, $nuevo_periodo, $nuevo_tipo);
                $stmt->execute();
                $stmt->close();
            }
            
            $conexion->commit();
            header("Location: ver_ficha.php?id=$id&msg=promovido&historial=pendiente");
            exit;
            
        } catch (Exception $e) {
            $conexion->rollback();
            $error = 'Error al promover el estudiante: ' . $e->getMessage();
            error_log("🚨 Error en pasar_anio.php: " . $e->getMessage());
        }
    }
}

include '../includes/header.php';

$mapa_salas = [
    'sala4' => 'Sala de 4 años',
    'sala5' => 'Sala de 5 años',
    '1ro' => '1er Grado',
    '2do' => '2do Grado',
    '3ro' => '3er Grado',
    '4to' => '4to Grado',
    '5to' => '5to Grado',
    '6to' => '6to Grado'
];
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-arrow-up me-2"></i> Pasar de Año - Promoción</h4>
        </div>
        <div class="card-body">
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Estudiante:</strong> <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Cédula Escolar:</strong> <?= htmlspecialchars($estudiante['cedula_escolar'] ?? 'N/A') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Sala actual:</strong> <?= htmlspecialchars($mapa_salas[$estudiante['sala']] ?? $estudiante['sala']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Sección actual:</strong> <?= htmlspecialchars($estudiante['seccion_nombre'] ?? 'N/A') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Periodo actual:</strong> <?= htmlspecialchars($periodo_actual) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Próximo periodo:</strong> <?= htmlspecialchars($proximo_periodo) ?>
                    </div>
                </div>
            </div>
            
            <form method="POST" onsubmit="return confirm('¿Está seguro de pasar de año a este estudiante? Esta acción:\n\n1. Guardará el año actual en el historial escolar\n2. Creará un nuevo registro para el nuevo periodo\n3. Actualizará el estudiante al nuevo grado\n4. Actualizará el año escolar en el boletín\n\nEl historial escolar quedará pendiente de completar (Reg., C, F, P, Peso, Talla).')">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nuevo Periodo Escolar <span class="text-danger">*</span></label>
                            <select name="nuevo_periodo" class="form-select" required>
                                <option value="<?= $proximo_periodo ?>"><?= $proximo_periodo ?></option>
                            </select>
                            <small class="text-muted">El periodo se actualiza automáticamente al siguiente año escolar.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nueva Sala / Grado <span class="text-danger">*</span></label>
                            <select name="nueva_sala" id="nueva_sala" class="form-select" required onchange="cargarSecciones(this.value)">
                                <option value="">Seleccione...</option>
                                <?php foreach ($salas_siguientes as $sala): 
                                    $nombre_sala = $mapa_salas[$sala] ?? $sala;
                                ?>
                                    <option value="<?= htmlspecialchars($sala) ?>"><?= htmlspecialchars($nombre_sala) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nueva Sección <span class="text-danger">*</span></label>
                            <select name="nueva_seccion" id="nueva_seccion" class="form-select" required>
                                <option value="">Primero seleccione una sala</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="limpiar_boletin" name="limpiar_boletin" checked>
                                <label class="form-check-label" for="limpiar_boletin">
                                    <strong>Limpiar boletín para el nuevo periodo</strong>
                                    <br><small class="text-muted">Reinicia el boletín actual para comenzar desde cero en el nuevo año escolar.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Importante:</strong> Después de pasar de año, deberá completar los datos del historial escolar 
                    (Reg., C, F, P, Peso, Talla) en la ficha del estudiante.
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="ver_ficha.php?id=<?= $id ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-arrow-up"></i> Pasar de Año
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cargarSecciones(sala) {
    const select = document.getElementById('nueva_seccion');
    select.innerHTML = '<option value="">Cargando...</option>';
    select.disabled = true;
    
    if (!sala) {
        select.innerHTML = '<option value="">Primero seleccione una sala</option>';
        select.disabled = false;
        return;
    }
    
    fetch('../config/ajax_secciones.php?sala=' + encodeURIComponent(sala))
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Seleccione...</option>';
            data.forEach(seccion => {
                const option = document.createElement('option');
                option.value = seccion.id;
                option.textContent = seccion.nombre;
                select.appendChild(option);
            });
            select.disabled = false;
        })
        .catch(() => {
            select.innerHTML = '<option value="">Error al cargar secciones</option>';
            select.disabled = false;
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const salaSelect = document.getElementById('nueva_sala');
    if (salaSelect && salaSelect.value) {
        cargarSecciones(salaSelect.value);
    }
});
</script>

<?php include '../includes/footer.php'; ?>