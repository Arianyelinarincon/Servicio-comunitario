<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

include '../includes/header.php';
require_once '../config/conexion.php';
require_once '../config/configuracion.php';

$periodo_escolar_actual = obtenerPeriodoEscolar();

// ========== OBTENER SALAS ==========
$salas = ['1ro', '2do', '3ro', '4to', '5to', '6to'];
$nombres_salas = [
    '1ro' => 'Primer Grado',
    '2do' => 'Segundo Grado',
    '3ro' => 'Tercer Grado',
    '4to' => 'Cuarto Grado',
    '5to' => 'Quinto Grado',
    '6to' => 'Sexto Grado'
];

// ========== OBTENER PERIODOS ==========
$periodos = [];
$res_periodos = $conexion->query("SELECT DISTINCT periodo FROM boletines WHERE tipo_boletin = 'primaria' ORDER BY periodo DESC");
if ($res_periodos) {
    while ($row = $res_periodos->fetch_assoc()) {
        $periodos[] = $row['periodo'];
    }
}
if (empty($periodos)) {
    $periodos[] = $periodo_escolar_actual;
}

// ========== SI VIENE POR GET, MOSTRAR FORMULARIO ==========
$sala_seleccionada = isset($_GET['sala']) ? trim($_GET['sala']) : '';
$seccion_seleccionada = isset($_GET['seccion']) ? intval($_GET['seccion']) : 0;
$periodo_seleccionado = isset($_GET['periodo']) ? trim($_GET['periodo']) : $periodo_escolar_actual;

// ========== SI HAY SALA Y SECCIÓN, REDIRIGIR AL FORMULARIO ==========
if (isset($_GET['accion']) && $_GET['accion'] === 'abrir' && $sala_seleccionada && $seccion_seleccionada) {
    // Obtener el profesor automáticamente
    $stmt_prof = $conexion->prepare("SELECT id FROM profesores WHERE seccion = ? AND sala = ? AND estatus = 'Activo' LIMIT 1");
    $stmt_prof->bind_param("is", $seccion_seleccionada, $sala_seleccionada);
    $stmt_prof->execute();
    $profesor = $stmt_prof->get_result()->fetch_assoc();
    $profesor_id = $profesor['id'] ?? 0;
    $stmt_prof->close();
    
    header("Location: formularioprimaria.php?sala=" . urlencode($sala_seleccionada) . "&seccion=" . $seccion_seleccionada . "&profesor=" . $profesor_id . "&periodo=" . urlencode($periodo_seleccionado));
    exit();
}

// ========== OBTENER SECCIONES PARA LA SALA SELECCIONADA ==========
$secciones = [];
if ($sala_seleccionada) {
    $stmt_sec = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
    $stmt_sec->bind_param("s", $sala_seleccionada);
    $stmt_sec->execute();
    $result_sec = $stmt_sec->get_result();
    while ($row = $result_sec->fetch_assoc()) {
        $secciones[] = $row;
    }
    $stmt_sec->close();
}

// ========== OBTENER PROFESOR ASIGNADO PARA MOSTRAR ==========
$docente_asignado = 'No asignado';
if ($sala_seleccionada && $seccion_seleccionada) {
    $stmt_prof = $conexion->prepare("SELECT nombre FROM profesores WHERE seccion = ? AND sala = ? AND estatus = 'Activo' LIMIT 1");
    $stmt_prof->bind_param("is", $seccion_seleccionada, $sala_seleccionada);
    $stmt_prof->execute();
    $profesor = $stmt_prof->get_result()->fetch_assoc();
    if ($profesor) {
        $docente_asignado = $profesor['nombre'];
    }
    $stmt_prof->close();
}
?>

<style>
    :root { --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%); --navy: #002d54; }
    .page-header {
        background: var(--primary-gradient);
        color: white;
        border-radius: 12px;
        padding: 20px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,45,84,0.2);
    }
    .card-generar {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .card-generar .card-header {
        background: var(--primary-gradient) !important;
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 14px 20px;
        font-weight: 600;
    }
    .btn-abrir {
        background: var(--primary-gradient);
        color: white;
        border: none;
        font-weight: 500;
        padding: 12px 40px;
        border-radius: 8px;
        transition: all 0.3s;
        font-size: 1.1rem;
    }
    .btn-abrir:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,45,84,0.3);
        color: white;
    }
    .btn-volver {
        background: #6c757d;
        color: white;
        border: none;
        font-weight: 500;
        padding: 12px 40px;
        border-radius: 8px;
        transition: all 0.3s;
        font-size: 1.1rem;
    }
    .btn-volver:hover {
        background: #5a6268;
        color: white;
    }
    .docente-info {
        background: #e8f4f8;
        border-left: 4px solid #002d54;
        padding: 12px 20px;
        border-radius: 8px;
        margin-top: 10px;
        font-size: 1rem;
    }
    .docente-info strong {
        color: #002d54;
    }
</style>

<div class="container mt-4">
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-file-alt me-2"></i> Generar Formulario de Rendimiento - Primaria</h4>
            <small class="opacity-75"><i class="fas fa-graduation-cap me-1"></i> Seleccione grado, sección y periodo</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="rendimientofinalindex.php" class="btn btn-light fw-bold">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
        </div>
    </div>

    <div class="card card-generar">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-sliders-h me-2"></i> Seleccionar parámetros</h6>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="primaria.php" id="formGenerar">
                <input type="hidden" name="accion" value="abrir">
                <div class="row g-4">
                    <!-- Sala / Grado -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><i class="fas fa-graduation-cap me-1"></i> Grado</label>
                        <select name="sala" id="select-sala" class="form-select shadow-none" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($salas as $sala): 
                                $nombre_sala = $nombres_salas[$sala] ?? $sala;
                                $selected = ($sala_seleccionada == $sala) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($sala) ?>" <?= $selected ?>><?= htmlspecialchars($nombre_sala) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sección -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><i class="fas fa-layer-group me-1"></i> Sección</label>
                        <select name="seccion" id="select-seccion" class="form-select shadow-none" required <?= empty($sala_seleccionada) ? 'disabled' : '' ?>>
                            <option value="">Primero seleccione un grado</option>
                            <?php foreach ($secciones as $sec): 
                                $selected = ($seccion_seleccionada == $sec['id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $sec['id'] ?>" <?= $selected ?>><?= htmlspecialchars($sec['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Periodo -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><i class="fas fa-calendar-alt me-1"></i> Año Escolar</label>
                        <select name="periodo" class="form-select shadow-none" required>
                            <?php foreach ($periodos as $p): 
                                $selected = ($periodo_seleccionado == $p) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $selected ?>><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Docente Asignado (automático) -->
                <?php if ($sala_seleccionada && $seccion_seleccionada): ?>
                    <div class="docente-info mt-3">
                        <i class="fas fa-chalkboard-teacher me-2" style="color: #002d54;"></i>
                        <strong>Docente asignado:</strong> <?= htmlspecialchars($docente_asignado) ?>
                    </div>
                <?php endif; ?>

                <!-- Botones -->
                <div class="mt-4 d-flex gap-3">
                    <button type="submit" class="btn-abrir" <?= empty($sala_seleccionada) || empty($seccion_seleccionada) ? 'disabled' : '' ?>>
                        <i class="fas fa-file-alt me-2"></i> Abrir Formulario
                    </button>
                    <a href="primaria.php" class="btn-volver">
                        <i class="fas fa-undo me-2"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectSala = document.getElementById('select-sala');
    const selectSeccion = document.getElementById('select-seccion');
    const form = document.getElementById('formGenerar');

    function cargarSecciones(sala, seleccionado) {
        selectSeccion.innerHTML = '<option value="">Cargando...</option>';
        selectSeccion.disabled = true;

        if (!sala) {
            selectSeccion.innerHTML = '<option value="">Primero seleccione un grado</option>';
            selectSeccion.disabled = false;
            return;
        }

        fetch('ajax_secciones.php?sala=' + encodeURIComponent(sala))
            .then(response => response.json())
            .then(data => {
                selectSeccion.innerHTML = '<option value="">Seleccione...</option>';
                data.forEach(sec => {
                    const option = document.createElement('option');
                    option.value = sec.id;
                    option.textContent = sec.nombre;
                    if (seleccionado && parseInt(seleccionado) === sec.id) {
                        option.selected = true;
                    }
                    selectSeccion.appendChild(option);
                });
                selectSeccion.disabled = false;
            })
            .catch(() => {
                selectSeccion.innerHTML = '<option value="">Error al cargar</option>';
                selectSeccion.disabled = true;
            });
    }

    selectSala.addEventListener('change', function() {
        const sala = this.value;
        const seccionActual = selectSeccion.value;
        cargarSecciones(sala, seccionActual);
    });

    // Cargar secciones iniciales si hay sala seleccionada
    const salaInicial = '<?= $sala_seleccionada ?>';
    const seccionInicial = '<?= $seccion_seleccionada ?>';
    if (salaInicial) {
        cargarSecciones(salaInicial, seccionInicial);
    }
});
</script>

<?php include '../includes/footer.php'; ?>