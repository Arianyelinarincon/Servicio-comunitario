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

// CSRF token para el formulario de eliminación
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========== FILTROS ==========
$buscar_estudiante = trim($_GET['estudiante'] ?? '');
$periodo = trim($_GET['periodo'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');

// ========== MAPEO DE SALAS A NOMBRES LEGIBLES Y TIPOS ==========
$nombres_salas = [
    'sala4' => 'Sala 4 Años',
    'sala5' => 'Sala 5 Años',
    '1ro'   => '1er Grado',
    '2do'   => '2do Grado',
    '3ro'   => '3er Grado',
    '4to'   => '4to Grado',
    '5to'   => '5to Grado',
    '6to'   => '6to Grado'
];

function obtenerTipoBoletinPorSala($sala) {
    $inicial = ['sala4', 'sala5'];
    if (in_array($sala, $inicial)) {
        return 'inicial';
    }
    return 'primaria';
}

// ========== CONSULTA PRINCIPAL ==========
$sql = "SELECT b.*, 
               CONCAT(e.nombre, ' ', e.apellido) AS nombre_estudiante,
               e.cedula_escolar,
               e.sala,
               CASE 
                   WHEN e.sala IN ('sala4', 'sala5') THEN 'inicial'
                   ELSE 'primaria'
               END AS tipo_detectado
        FROM boletines b
        INNER JOIN estudiantes e ON b.estudiante_id = e.id
        WHERE 1=1";
$params = [];
$types = "";

if ($buscar_estudiante) {
    $sql .= " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.cedula_escolar LIKE ?)";
    $like = "%$buscar_estudiante%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}
if ($periodo) {
    $sql .= " AND b.periodo = ?";
    $params[] = $periodo;
    $types .= "s";
}
if ($tipo) {
    $sql .= " AND CASE 
                   WHEN e.sala IN ('sala4', 'sala5') THEN 'inicial'
                   ELSE 'primaria'
               END = ?";
    $params[] = $tipo;
    $types .= "s";
}
$sql .= " ORDER BY b.fecha_emision DESC";

$stmt = $conexion->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ========== OBTENER PERÍODOS DISPONIBLES ÚNICOS ==========
$periodos_disponibles = [];
$res_periodos = $conexion->query("SELECT DISTINCT periodo FROM boletines ORDER BY periodo DESC");
while ($row = $res_periodos->fetch_assoc()) {
    // Limpiar formato: convertir "2025 / 2026" a "2025-2026"
    $periodo_limpio = trim($row['periodo']);
    $periodo_limpio = str_replace(' / ', '-', $periodo_limpio);
    $periodo_limpio = str_replace('/', '-', $periodo_limpio);
    // Validar formato
    if (preg_match('/^\d{4}-\d{4}$/', $periodo_limpio)) {
        $periodos_disponibles[] = $periodo_limpio;
    } else {
        $periodos_disponibles[] = $row['periodo'];
    }
}
// Eliminar duplicados
$periodos_disponibles = array_unique($periodos_disponibles);

// Si no hay periodos, agregar el actual
if (empty($periodos_disponibles)) {
    $periodos_disponibles[] = $periodo_escolar_actual;
}
?>

<style>
    :root { --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%); }
    .page-header {
        background: var(--primary-gradient);
        color: white;
        border-radius: 12px;
        padding: 20px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,45,84,0.2);
    }
    .card-filtros {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 24px;
    }
    .card-tabla {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .card-tabla .card-header {
        background: var(--primary-gradient) !important;
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 14px 20px;
        font-weight: 600;
    }
    .table-boletines {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .table-boletines thead th {
        background-color: #f0f4f8;
        color: #002d54;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #002d54;
    }
    .table-boletines tbody tr:hover {
        background-color: #e8f4f8;
    }
    .badge-tipo {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-inicial { background-color: #cce5ff; color: #004085; }
    .badge-primaria { background-color: #d4edda; color: #155724; }
    .btn-accion {
        margin: 2px;
        padding: 4px 10px;
        font-size: 0.78rem;
        border-radius: 6px;
    }
    .btn-eliminar-boletin {
        background: none;
        border: none;
        color: #dc3545;
        padding: 4px 8px;
        border-radius: 4px;
        transition: background 0.2s;
        cursor: pointer;
    }
    .btn-eliminar-boletin:hover {
        background: #dc3545;
        color: white;
    }
    .badge-sala {
        background-color: #e9ecef;
        color: #002d54;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        white-space: nowrap;
    }
    .puntos-lapsos {
        display: flex;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }
    .punto {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background-color: #e9ecef;
        border: 1.5px solid #adb5bd;
        transition: all 0.3s ease;
        display: inline-block;
    }
    .punto.completado {
        background-color: #28a745;
        border-color: #1e7e34;
        box-shadow: 0 0 8px rgba(40, 167, 69, 0.5);
    }
    .punto.incompleto {
        background-color: #e9ecef;
        border-color: #adb5bd;
        opacity: 0.5;
    }
    .punto-label {
        font-size: 0.6rem;
        color: #6c757d;
        text-align: center;
        margin-top: 2px;
    }
    .modal-confirmacion .modal-header {
        background: #dc3545;
        color: white;
    }
    .modal-confirmacion .modal-footer {
        border-top: none;
    }
    #busquedaInput:focus {
        border-color: #002d54;
        box-shadow: 0 0 0 3px rgba(0,45,84,0.15);
    }
    .filtro-auto {
        font-size: 0.75rem;
        color: #6c757d;
    }
    .badge-periodo {
        background-color: #e9ecef;
        color: #495057;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
    }
</style>

<div class="container-fluid px-4">
    
    <!-- Cabecera -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-history me-2"></i> Historial de Boletines</h4>
            <small class="opacity-75"><i class="fas fa-file-alt me-1"></i> Consulta y administra los boletines generados</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="index.php" class="btn btn-light fw-bold"><i class="fas fa-arrow-left me-2"></i> Volver</a>
        </div>
    </div>

    <!-- Filtros con búsqueda en tiempo real -->
    <div class="card card-filtros">
        <div class="card-body p-4">
            <form method="GET" action="historial_boletines.php" id="filtroForm" autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted"><i class="fas fa-user-graduate me-1"></i> Buscar estudiante</label>
                        <input type="text" name="estudiante" id="busquedaInput" class="form-control shadow-none" placeholder="Nombre, apellido o cédula..." value="<?= htmlspecialchars($buscar_estudiante) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted"><i class="fas fa-calendar-alt me-1"></i> Período Escolar</label>
                        <select name="periodo" id="periodoSelect" class="form-select shadow-none">
                            <option value="">Todos</option>
                            <?php 
                            // Mostrar años únicos de la base de datos
                            foreach ($periodos_disponibles as $p): 
                                // Limpiar formato para mostrar
                                $p_clean = trim($p);
                                $selected = ($periodo == $p_clean) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($p_clean) ?>" <?= $selected ?>><?= htmlspecialchars($p_clean) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted"><i class="fas fa-tag me-1"></i> Tipo de Boletín</label>
                        <select name="tipo" id="tipoSelect" class="form-select shadow-none">
                            <option value="">Todos</option>
                            <option value="inicial" <?= ($tipo == 'inicial') ? 'selected' : '' ?>>Inicial (Sala 4-5 años)</option>
                            <option value="primaria" <?= ($tipo == 'primaria') ? 'selected' : '' ?>>Primaria (1ro - 6to)</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <span class="filtro-auto"><i class="fas fa-sync-alt me-1"></i> Filtro automático</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de boletines -->
    <div class="card card-tabla">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Registro de Boletines <span class="badge bg-light text-dark ms-2" id="contador-boletines"><?= $result->num_rows ?> boletín(es)</span></h6>
            <small class="opacity-75"><i class="fas fa-clock me-1"></i> Actualizado</small>
        </div>
        <div class="card-body p-0" id="tabla-container">
            <div class="table-responsive">
                <table class="table table-hover table-boletines mb-0" id="tabla-boletines">
                    <thead>
                        <tr>
                            <th style="width:14%">Estudiante</th>
                            <th style="width:9%">C.E. Escolar</th>
                            <th style="width:9%">Sala / Grado</th>
                            <th style="width:9%">Año Escolar</th>
                            <th style="width:7%">Tipo</th>
                            <th style="width:11%">Fecha Emisión</th>
                            <th style="width:12%">Lapsos Completos</th>
                            <th style="width:14%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-boletines">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): 
                                $tipo_detectado = obtenerTipoBoletinPorSala($row['sala']);
                                $tipo_clase = ($tipo_detectado == 'inicial') ? 'badge-inicial' : 'badge-primaria';
                                $tipo_nombre = ($tipo_detectado == 'inicial') ? 'Inicial' : 'Primaria';
                                $sala_nombre = $nombres_salas[$row['sala']] ?? $row['sala'];
                                
                                $lapsos_completos = 0;

// Para Inicial: verificar todos los campos del momento (proyecto, formacion, relacion, sugerencias)
if ($row['tipo_boletin'] == 'inicial' || $tipo_detectado == 'inicial') {
    // Momento 1
    if (!empty($row['m1_proyecto']) && !empty($row['m1_formacion']) && !empty($row['m1_relacion']) && !empty($row['m1_sugerencias'])) {
        $lapsos_completos++;
    }
    // Momento 2
    if (!empty($row['m2_proyecto']) && !empty($row['m2_formacion']) && !empty($row['m2_relacion']) && !empty($row['m2_sugerencias'])) {
        $lapsos_completos++;
    }
    // Momento 3
    if (!empty($row['m3_proyecto']) && !empty($row['m3_formacion']) && !empty($row['m3_relacion']) && !empty($row['m3_sugerencias'])) {
        $lapsos_completos++;
    }
} else {
    // Para Primaria: verificar proyectos, formacion y sugerencias
    if (!empty($row['m1_proyecto']) && !empty($row['m1_formacion']) && !empty($row['m1_sugerencias'])) {
        $lapsos_completos++;
    }
    if (!empty($row['m2_proyecto']) && !empty($row['m2_formacion']) && !empty($row['m2_sugerencias'])) {
        $lapsos_completos++;
    }
    if (!empty($row['m3_proyecto']) && !empty($row['m3_formacion']) && !empty($row['m3_sugerencias'])) {
        $lapsos_completos++;
    }
}
                            ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td><strong><?= htmlspecialchars($row['nombre_estudiante']) ?></strong></td>
                                <td><span class="font-monospace"><?= htmlspecialchars($row['cedula_escolar'] ?? '') ?></span></td>
                                <td><span class="badge-sala"><?= htmlspecialchars($sala_nombre) ?></span></td>
                                <td><span class="badge-periodo"><?= htmlspecialchars($row['periodo']) ?></span></td>
                                <td><span class="badge-tipo <?= $tipo_clase ?>"><?= $tipo_nombre ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['fecha_emision'])) ?></td>
                                <td>
                                    <div class="puntos-lapsos">
                                        <?php for ($i = 1; $i <= 3; $i++): ?>
                                            <span class="punto <?= ($i <= $lapsos_completos) ? 'completado' : 'incompleto' ?>" 
                                                  title="Lapso <?= $i ?> <?= ($i <= $lapsos_completos) ? 'completado' : 'pendiente' ?>"></span>
                                        <?php endfor; ?>
                                        <span class="punto-label"><?= $lapsos_completos ?>/3</span>
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <a href="ver_boletin_guardado.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info btn-accion" target="_blank" title="Ver boletín"><i class="fas fa-eye"></i></a>
                                    <?php if ($tipo_detectado == 'inicial'): ?>
                                        <a href="panel_boletin_inicial.php?editar_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning btn-accion" title="Editar boletín"><i class="fas fa-edit"></i></a>
                                    <?php else: ?>
                                        <a href="panel_boletin_primaria.php?editar_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning btn-accion" title="Editar boletín"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                    <button type="button" class="btn-eliminar-boletin" title="Eliminar boletín" data-id="<?= $row['id'] ?>" data-estudiante="<?= htmlspecialchars($row['nombre_estudiante']) ?>" data-periodo="<?= htmlspecialchars($row['periodo']) ?>" data-tipo="<?= $tipo_nombre ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>No hay boletines guardados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-database me-1"></i> Total: <span id="total-boletines"><?= $result->num_rows ?></span> boletines</span>
            <span class="text-muted small"><i class="fas fa-sync-alt me-1"></i> Filtro automático</span>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade modal-confirmacion" id="modalEliminarBoletin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold">¿Está seguro de eliminar este boletín?</p>
                <p><strong>Estudiante:</strong> <span id="modalEstudiante"></span></p>
                <p><strong>Período:</strong> <span id="modalPeriodo"></span></p>
                <p><strong>Tipo:</strong> <span id="modalTipo"></span></p>
                <div class="mt-3 text-danger small">
                    <i class="fas fa-exclamation-circle me-1"></i> Esta acción <strong>no se puede deshacer</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-eliminar-boletin">
                    <i class="fas fa-trash-alt me-2"></i> Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== BÚSQUEDA EN TIEMPO REAL =====
    const busquedaInput = document.getElementById('busquedaInput');
    const periodoSelect = document.getElementById('periodoSelect');
    const tipoSelect = document.getElementById('tipoSelect');
    const tbody = document.getElementById('tbody-boletines');
    const contador = document.getElementById('contador-boletines');
    const totalBoletines = document.getElementById('total-boletines');
    let timeoutId = null;

    function filtrarTabla() {
        const busqueda = busquedaInput.value.toLowerCase().trim();
        const periodo = periodoSelect.value;
        const tipo = tipoSelect.value;
        
        const filas = tbody.querySelectorAll('tr');
        let contadorVisible = 0;
        
        filas.forEach(function(fila) {
            // Saltar fila de "No hay boletines"
            if (fila.querySelector('td[colspan]')) return;
            
            const texto = fila.textContent.toLowerCase();
            const periodoFila = fila.querySelector('td:nth-child(4)')?.textContent?.trim() || '';
            const tipoFila = fila.querySelector('td:nth-child(5)')?.textContent?.trim() || '';
            
            let visible = true;
            
            // Filtro por búsqueda
            if (busqueda && !texto.includes(busqueda)) {
                visible = false;
            }
            
            // Filtro por período
            if (periodo && periodoFila !== periodo) {
                visible = false;
            }
            
            // Filtro por tipo
            if (tipo) {
                const tipoNormalizado = tipo === 'inicial' ? 'Inicial' : 'Primaria';
                if (tipoFila !== tipoNormalizado) {
                    visible = false;
                }
            }
            
            fila.style.display = visible ? '' : 'none';
            if (visible) contadorVisible++;
        });
        
        // Actualizar contadores
        contador.textContent = contadorVisible + ' boletín(es)';
        totalBoletines.textContent = contadorVisible;
        
        // Mostrar mensaje si no hay resultados
        let mensajeVacio = tbody.querySelector('.mensaje-vacio');
        if (contadorVisible === 0) {
            if (!mensajeVacio) {
                mensajeVacio = document.createElement('tr');
                mensajeVacio.className = 'mensaje-vacio';
                mensajeVacio.innerHTML = '<td colspan="8" class="text-center py-4"><i class="fas fa-search fa-2x text-muted mb-2 d-block"></i>No se encontraron boletines con los filtros seleccionados.</td>';
                tbody.appendChild(mensajeVacio);
            }
        } else {
            if (mensajeVacio) {
                mensajeVacio.remove();
            }
        }
    }

    // Eventos en tiempo real
    if (busquedaInput) {
        busquedaInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(filtrarTabla, 300);
        });
    }
    
    if (periodoSelect) {
        periodoSelect.addEventListener('change', filtrarTabla);
    }
    
    if (tipoSelect) {
        tipoSelect.addEventListener('change', filtrarTabla);
    }

    // ===== ELIMINAR BOLETÍN =====
    const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminarBoletin'));
    let boletinIdAEliminar = null;

    document.querySelectorAll('.btn-eliminar-boletin').forEach(btn => {
        btn.addEventListener('click', function() {
            boletinIdAEliminar = this.dataset.id;
            document.getElementById('modalEstudiante').textContent = this.dataset.estudiante;
            document.getElementById('modalPeriodo').textContent = this.dataset.periodo;
            document.getElementById('modalTipo').textContent = this.dataset.tipo;
            modalEliminar.show();
        });
    });

    document.getElementById('btn-confirmar-eliminar-boletin').addEventListener('click', function() {
        if (!boletinIdAEliminar) return;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Eliminando...';

        const formData = new FormData();
        formData.append('id', boletinIdAEliminar);
        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

        fetch('eliminar_boletin.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                modalEliminar.hide();
                mostrarNotificacion('Boletín eliminado correctamente.', 'success');
                // Eliminar fila
                const fila = document.querySelector(`tr[data-id="${boletinIdAEliminar}"]`);
                if (fila) fila.remove();
                // Actualizar contador
                filtrarTabla();
            } else {
                mostrarNotificacion('Error: ' + (data.error || 'No se pudo eliminar'), 'danger');
            }
        })
        .catch(() => {
            mostrarNotificacion('Error de conexión', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-alt me-2"></i> Sí, eliminar';
            boletinIdAEliminar = null;
        });
    });

    function mostrarNotificacion(mensaje, tipo = 'success') {
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        alerta.style.zIndex = '9999';
        alerta.style.maxWidth = '400px';
        alerta.innerHTML = `${mensaje} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alerta);
        setTimeout(() => {
            alerta.remove();
        }, 4000);
    }

    // Ejecutar filtro inicial
    filtrarTabla();
});
</script>

<?php include '../includes/footer.php'; ?>