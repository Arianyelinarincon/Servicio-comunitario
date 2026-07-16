<?php
// ============================================================================
// CONFIGURACIÓN Y SEGURIDAD
// ============================================================================
require_once "config_db.php";

// --- Funciones auxiliares (definidas aquí por si no existen en otro lado) ---
function sanitizarEntrada($dato, $tipo = 'string') {
    if (is_null($dato)) return '';
    if ($tipo === 'int') return (int)$dato;
    if ($tipo === 'float') return (float)$dato;
    // Limpieza general para strings
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    return $dato;
}

function responderJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function logError($mensaje, $contexto = []) {
    error_log("ERROR: $mensaje " . json_encode($contexto));
}
// ----------------------------------------------------------------------------

// Autenticación
if (session_status() === PHP_SESSION_NONE) session_start();
$esAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

if (!isset($_SESSION['usuario'])) {
    if ($esAjax) {
        responderJSON(['success' => false, 'error' => 'No autenticado'], 401);
    } else {
        header('Location: /login.php');
        exit;
    }
}

// Prefill (para rellenar el formulario de inscripción)
$prefill = $_GET['prefill'] ?? '';
$prefill_data = [];
if ($prefill === '1') {
    $prefill_data = [
        'apellido' => $_GET['apellido'] ?? '',
        'nombre' => $_GET['nombre'] ?? '',
        'genero' => $_GET['genero'] ?? '',
        'nacionalidad' => $_GET['nacionalidad'] ?? '',
        'ci' => $_GET['ci'] ?? '',
        'fn' => $_GET['fn'] ?? '',
        'fi' => $_GET['fi'] ?? '',
    ];
}

// ============================================================================
// AJAX HANDLER
// ============================================================================
if ($esAjax) {
    $action = sanitizarEntrada($_POST['action'] ?? '');
    
    if ($action === 'eliminar_ingreso') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) {
            responderJSON(['success' => false, 'error' => 'ID inválido'], 400);
        }
        
        try {
            $stmt = $conexion->prepare("DELETE FROM ingresos WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Error en la preparación: " . $conexion->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                responderJSON(['success' => true]);
            } else {
                responderJSON(['success' => false, 'error' => 'No se encontró el registro o ya fue eliminado'], 404);
            }
        } catch (Exception $e) {
            logError("Error eliminando ingreso", ['id' => $id, 'error' => $e->getMessage()]);
            responderJSON(['success' => false, 'error' => 'Error interno del servidor'], 500);
        }
        exit;
    }
    
    if ($action === 'cargar_secciones') {
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        if (!$sala) {
            responderJSON(['secciones' => []]);
        }
        $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
        $stmt->bind_param("s", $sala);
        $stmt->execute();
        $result = $stmt->get_result();
        $secciones = [];
        while ($row = $result->fetch_assoc()) {
            $secciones[] = $row;
        }
        responderJSON(['secciones' => $secciones]);
        exit;
    }
    
    responderJSON(['error' => 'Acción no válida'], 400);
}

// ============================================================================
// FILTROS Y PAGINACIÓN (para la vista HTML)
// ============================================================================
$filtro_sala = sanitizarEntrada($_GET['sala'] ?? '');
$filtro_seccion = filter_var($_GET['seccion'] ?? 0, FILTER_VALIDATE_INT);
$filtro_periodo = sanitizarEntrada($_GET['periodo'] ?? '');
$filtro_busqueda = sanitizarEntrada($_GET['busqueda'] ?? '');
$pagina = max(1, filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

// Construcción de WHERE con JOIN a estudiantes
$where = [];
$params = [];
$tipos = '';

if ($filtro_sala) {
    $where[] = "i.sala = ?";
    $params[] = $filtro_sala;
    $tipos .= 's';
}
if ($filtro_seccion) {
    $where[] = "i.seccion_id = ?";
    $params[] = $filtro_seccion;
    $tipos .= 'i';
}
if ($filtro_periodo) {
    $where[] = "i.periodo = ?";
    $params[] = $filtro_periodo;
    $tipos .= 's';
}
if ($filtro_busqueda) {
    $termino = "%$filtro_busqueda%";
    $where[] = "(e.nombre LIKE ? OR e.apellido LIKE ? OR e.cedula LIKE ? OR CONCAT(e.apellido, ' ', e.nombre) LIKE ?)";
    $params[] = $termino;
    $params[] = $termino;
    $params[] = $termino;
    $params[] = $termino;
    $tipos .= 'ssss';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total de registros (con JOIN)
$sql_count = "SELECT COUNT(*) as total 
              FROM ingresos i 
              JOIN estudiantes e ON i.id = e.id 
              $whereSQL";
$stmt_count = $conexion->prepare($sql_count);
if ($params) {
    $stmt_count->bind_param($tipos, ...$params);
}
$stmt_count->execute();
$total = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total / $por_pagina);

// Consulta de datos con paginación (seleccionar campos necesarios)
$sql = "SELECT i.*, 
               e.apellido, e.nombre, 
               COALESCE(e.cedula, e.cedula_escolar, '') AS ci,
               e.nacionalidad, 
               e.fecha_nacimiento
        FROM ingresos i
        JOIN estudiantes e ON i.id = e.id
        $whereSQL 
        ORDER BY i.created_at DESC 
        LIMIT ? OFFSET ?";
$params_paginados = array_merge($params, [$por_pagina, $offset]);
$tipos_paginados = $tipos . 'ii';
$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos_paginados, ...$params_paginados);
$stmt->execute();
$ingresos = $stmt->get_result();

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Ingresos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --navy: #002d54;
            --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%);
        }
        body { background-color: #f5f7fa; font-family: 'Segoe UI', sans-serif; }
        .page-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 12px;
            padding: 20px 28px;
            margin-bottom: 24px;
        }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .card-header { background: var(--primary-gradient) !important; color: white; border-radius: 12px 12px 0 0 !important; padding: 14px 20px; font-weight: 600; }
        .table-ingresos { font-size: 0.875rem; vertical-align: middle; }
        .table-ingresos thead th { background-color: #f0f4f8; color: var(--navy); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
        .table-ingresos tbody tr:hover { background-color: #e8f4f8; }
        .badge-sala { background-color: #e9ecef; color: var(--navy); font-weight: 600; padding: 4px 10px; border-radius: 6px; }
        .btn-accion { font-size: 0.78rem; padding: 4px 12px; }
        .btn-terminar { background: var(--primary-gradient); border: none; color: white; }
        .btn-terminar:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(0,45,84,0.3); }
        .btn-eliminar { background-color: #dc3545; border: none; color: white; }
        .btn-eliminar:hover { background-color: #bb2d3b; }
        .btn-filtro { background: var(--primary-gradient); color: white; border: none; }
        .btn-filtro:hover { background: #003f66; color: white; }
        .btn-limpiar { background: #6c757d; color: white; border: none; }
        .btn-limpiar:hover { background: #5a6268; color: white; }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-user-plus me-2"></i> Historial de Ingresos</h4>
            <small class="opacity-75">Estudiantes pendientes por inscribir</small>
        </div>
        <a href="index.php" class="btn btn-light fw-bold"><i class="fas fa-arrow-left me-2"></i> Volver</a>
    </div>

    <!-- Filtros -->
    <div class="card">
        <div class="card-body p-4">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Sala / Grado</label>
                    <select name="sala" id="filtro-sala" class="form-select" onchange="cargarSecciones()">
                        <option value="">Todas</option>
                        <?php
                        $salas = ['sala4'=>'Sala 4','sala5'=>'Sala 5','1ro'=>'1°','2do'=>'2°','3ro'=>'3°','4to'=>'4°','5to'=>'5°','6to'=>'6°'];
                        foreach ($salas as $k => $v) {
                            echo '<option value="'.$k.'" '.($filtro_sala==$k?'selected':'').'>'.$v.'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Sección</label>
                    <select name="seccion" id="filtro-seccion" class="form-select" <?= $filtro_sala ? '' : 'disabled' ?>>
                        <option value="">Todas</option>
                        <!-- se cargará dinámicamente con JavaScript -->
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted">Período</label>
                    <input type="month" name="periodo" class="form-control" value="<?= $filtro_periodo ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Buscar</label>
                    <input type="text" name="busqueda" class="form-control" placeholder="Nombre, apellido o CI" value="<?= $filtro_busqueda ?>">
                </div>
                <div class="col-md-3 text-end d-flex gap-2 justify-content-end align-self-end">
                    <button type="submit" class="btn btn-filtro px-4"><i class="fas fa-filter me-2"></i>Filtrar</button>
                    <a href="ingresos.php" class="btn btn-limpiar px-3"><i class="fas fa-eraser me-2"></i>Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i>Registros de ingreso <span class="badge bg-light text-dark ms-2" id="contador-total"><?= $total ?></span></h6>
        </div>
        <div class="card-body p-0">
            <?php if ($total > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-ingresos mb-0">
                    <thead>
                        <tr>
                            <th>#</th><th>Estudiante</th><th>CI</th><th>Género</th><th>Sala/Sección</th><th>Período</th><th>F. Ingreso</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-ingresos-body">
                        <?php $cont = $offset + 1; while ($ing = $ingresos->fetch_assoc()): 
                            $nombre_sec = '';
                            $sec = $conexion->query("SELECT nombre FROM secciones WHERE id=".$ing['seccion_id'])->fetch_assoc();
                            if ($sec) $nombre_sec = $sec['nombre'];
                        ?>
                        <tr id="fila-ingreso-<?= $ing['id'] ?>">
                            <td><?= $cont++ ?></td>
                            <td><strong><?= htmlspecialchars($ing['apellido'].' '.$ing['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($ing['ci'] ?: 'N/A') ?></td>
                            <td><?= $ing['genero']=='V' ? 'Varón' : 'Hembra' ?></td>
                            <td><span class="badge-sala"><?= $ing['sala'] ?></span> <?= $nombre_sec ? 'Sec. '.$nombre_sec : '' ?></td>
                            <td><?= $ing['periodo'] ?></td>
                            <td><?= date('d/m/Y', strtotime($ing['fecha_ingreso'])) ?></td>
                            <td style="white-space: nowrap;">
                                <a href="../estudiantes/inscripcion.php?prefill=1&apellido=<?= urlencode($ing['apellido']) ?>&nombre=<?= urlencode($ing['nombre']) ?>&genero=<?= $ing['genero'] ?>&nacionalidad=<?= urlencode($ing['nacionalidad']) ?>&ci=<?= urlencode($ing['ci']) ?>&fn=<?= $ing['fecha_nacimiento'] ?>&fi=<?= $ing['fecha_ingreso'] ?>"
                                class="btn btn-terminar btn-accion" title="Completar inscripción">
                                    <i class="fas fa-edit me-1"></i> Terminar Inscripción
                                </a>
                                <button onclick="eliminarIngreso(<?= $ing['id'] ?>)" class="btn btn-eliminar btn-accion ms-1">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <!-- Paginación -->
            <div class="d-flex justify-content-between align-items-center p-3">
                <div>Mostrando <?= ($offset + 1) ?> - <?= min($offset + $por_pagina, $total) ?> de <?= $total ?></div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">No hay registros de ingreso pendientes</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title">Confirmar eliminación</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de eliminar este ingreso permanentemente?</p>
                <p class="text-danger small">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-eliminar">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Variables globales
let idEliminar = null;
const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));

// Mostrar modal y guardar ID
function eliminarIngreso(id) {
    idEliminar = id;
    modalEliminar.show();
}

// Confirmar eliminación (evento)
document.getElementById('btn-confirmar-eliminar').addEventListener('click', function() {
    if (!idEliminar) return;
    
    // Deshabilitar botón para evitar doble clic
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Eliminando...';
    
    fetch('ingresos.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=eliminar_ingreso&id=' + idEliminar
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => { throw new Error(text || 'Error en la respuesta del servidor'); });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const fila = document.getElementById('fila-ingreso-' + idEliminar);
            if (fila) fila.remove();
            
            const contador = document.getElementById('contador-total');
            if (contador) {
                let actual = parseInt(contador.textContent);
                contador.textContent = actual - 1;
            }
            
            const tbody = document.getElementById('tabla-ingresos-body');
            if (tbody && tbody.children.length === 0) {
                location.reload();
            }
            
            modalEliminar.hide();
        } else {
            alert('Error al eliminar: ' + (data.error || 'No se pudo eliminar el registro'));
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
        alert('Ocurrió un error al comunicarse con el servidor. Por favor, intente nuevamente.');
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = 'Eliminar';
    });
});

// Cargar secciones según la sala seleccionada
function cargarSecciones() {
    const sala = document.getElementById('filtro-sala').value;
    const selectSec = document.getElementById('filtro-seccion');
    if (!sala) {
        selectSec.innerHTML = '<option value="">Todas</option>';
        selectSec.disabled = true;
        return;
    }
    
    fetch('ingresos.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=cargar_secciones&sala=' + encodeURIComponent(sala)
    })
    .then(r => r.json())
    .then(data => {
        selectSec.innerHTML = '<option value="">Todas</option>';
        if (data.secciones && data.secciones.length > 0) {
            data.secciones.forEach(s => {
                const option = document.createElement('option');
                option.value = s.id;
                option.textContent = s.nombre;
                selectSec.appendChild(option);
            });
            selectSec.disabled = false;
        } else {
            selectSec.disabled = true;
        }
        const valorActual = new URLSearchParams(window.location.search).get('seccion');
        if (valorActual) {
            selectSec.value = valorActual;
        }
    })
    .catch(err => console.error('Error cargando secciones:', err));
}

// Ejecutar al cargar la página para inicializar secciones
document.addEventListener('DOMContentLoaded', function() {
    cargarSecciones();
});
</script>

<?php include "../includes/footer.php"; ?>