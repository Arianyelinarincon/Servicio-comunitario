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

$filtro_periodo = isset($_GET['periodo']) ? trim($_GET['periodo']) : '';
$filtro_sala = isset($_GET['sala']) ? trim($_GET['sala']) : '';
$filtro_seccion = isset($_GET['seccion']) ? intval($_GET['seccion']) : 0;
$filtro_docente = isset($_GET['docente']) ? intval($_GET['docente']) : 0;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// ========== AJAX HANDLER ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $action = $_GET['action'] ?? '';
    $response = [];

    if ($action === 'cargar_secciones') {
        $sala = $_GET['sala'] ?? '';
        if ($sala) {
            $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
            if ($stmt) {
                $stmt->bind_param("s", $sala);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $response[] = ['id' => $row['id'], 'nombre' => $row['nombre']];
                }
                $stmt->close();
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if ($action === 'cargar_docentes') {
        $seccion = intval($_GET['seccion'] ?? 0);
        if ($seccion) {
            $stmt = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? AND estatus = 'Activo' ORDER BY nombre");
            if ($stmt) {
                $stmt->bind_param("i", $seccion);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $response[] = ['id' => $row['id'], 'nombre' => $row['nombre']];
                }
                $stmt->close();
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // ===== FILTRAR TABLA EN TIEMPO REAL =====
    if ($action === 'filtrar_tabla') {
        $filtro_periodo_ajax = $_GET['periodo'] ?? '';
        $filtro_sala_ajax = $_GET['sala'] ?? '';
        $filtro_seccion_ajax = intval($_GET['seccion'] ?? 0);
        $filtro_docente_ajax = intval($_GET['docente'] ?? 0);
        $busqueda_ajax = $_GET['busqueda'] ?? '';
        
        $sql = "SELECT 
                    b.id AS boletin_id,
                    b.tipo_boletin,
                    b.periodo,
                    b.resultado_final,
                    b.literal_final,
                    b.m1_formacion,
                    b.m2_formacion,
                    b.m3_formacion,
                    e.id AS estudiante_id,
                    e.nombre,
                    e.apellido,
                    e.cedula_escolar,
                    e.sala,
                    e.seccion_id,
                    s.nombre AS seccion_nombre,
                    p.nombre AS docente_nombre,
                    p.id AS docente_id
                FROM boletines b
                INNER JOIN estudiantes e ON b.estudiante_id = e.id
                LEFT JOIN secciones s ON e.seccion_id = s.id
                LEFT JOIN profesores p ON p.seccion = s.id AND p.estatus = 'Activo'
                WHERE 1=1
                  AND b.m1_formacion IS NOT NULL AND b.m1_formacion != ''
                  AND b.m2_formacion IS NOT NULL AND b.m2_formacion != ''
                  AND b.m3_formacion IS NOT NULL AND b.m3_formacion != ''";
        
        $params = [];
        $types = "";

        if ($filtro_periodo_ajax) {
            $sql .= " AND b.periodo = ?";
            $params[] = $filtro_periodo_ajax;
            $types .= "s";
        }
        if ($filtro_sala_ajax) {
            $sql .= " AND e.sala = ?";
            $params[] = $filtro_sala_ajax;
            $types .= "s";
        }
        if ($filtro_seccion_ajax > 0) {
            $sql .= " AND e.seccion_id = ?";
            $params[] = $filtro_seccion_ajax;
            $types .= "i";
        }
        if ($filtro_docente_ajax > 0) {
            $sql .= " AND p.id = ?";
            $params[] = $filtro_docente_ajax;
            $types .= "i";
        }
        if ($busqueda_ajax) {
            $sql .= " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.cedula_escolar LIKE ?)";
            $like = "%$busqueda_ajax%";
            $params[] = $like; $params[] = $like; $params[] = $like;
            $types .= "sss";
        }

        $sql .= " ORDER BY e.sala, e.apellido, e.nombre";

        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            header('Content-Type: application/json');
            echo json_encode(['html' => '<tr><td colspan="10" class="text-center text-danger">Error en la consulta SQL</td></tr>', 'total' => 0]);
            exit;
        }
        
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        $total = 0;
        
        if ($result && $result->num_rows > 0) {
            $total = $result->num_rows;
            $contador = 1;
            while ($row = $result->fetch_assoc()): 
                $tipo = $row['tipo_boletin'] ?? 'inicial';
                $tipo_nombre = ($tipo == 'inicial') ? 'Inicial' : 'Primaria';
                $tipo_clase = ($tipo == 'inicial') ? 'badge-inicial' : 'badge-primaria';
                
                if ($tipo == 'inicial') {
                    $aprobado = true;
                    $aplazado = false;
                    $literal = '';
                } else {
                    $resultado = $row['resultado_final'] ?? '';
                    $aprobado = ($resultado == 'Promovido');
                    $aplazado = ($resultado == 'Aplazado');
                    $literal = $row['literal_final'] ?? '';
                }
                
                $sala_nombre = $nombres_salas[$row['sala']] ?? $row['sala'];
                
                $html .= '<tr>';
                $html .= '<td class="text-center fw-bold text-muted">' . $contador++ . '</td>';
                $html .= '<td class="text-start"><strong>' . htmlspecialchars($row['apellido'] . ' ' . $row['nombre']) . '</strong></td>';
                $html .= '<td><span class="font-monospace">' . htmlspecialchars($row['cedula_escolar'] ?? '') . '</span></td>';
                $html .= '<td><span class="badge-sala">' . htmlspecialchars($sala_nombre) . '</span></td>';
                $html .= '<td>' . htmlspecialchars($row['seccion_nombre'] ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($row['docente_nombre'] ?? 'Sin asignar') . '</td>';
                $html .= '<td><span class="badge-tipo ' . $tipo_clase . '">' . $tipo_nombre . '</span></td>';
                $html .= '<td>' . ($aprobado ? '<span class="casilla-rendimiento marcada">X</span>' : '<span class="casilla-rendimiento"></span>') . '</td>';
                $html .= '<td>' . ($aplazado ? '<span class="casilla-rendimiento marcada">X</span>' : '<span class="casilla-rendimiento"></span>') . '</td>';
                $html .= '<td>' . ($literal ? '<span class="literal-box">' . htmlspecialchars($literal) . '</span>' : '<span class="texto-vacio">—</span>') . '</td>';
                $html .= '</tr>';
            endwhile;
        } else {
            $html = '<tr><td colspan="10" class="text-center py-4"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>No se encontraron estudiantes con boletín completo.</td></tr>';
        }
        
        header('Content-Type: application/json');
        echo json_encode(['html' => $html, 'total' => $total]);
        exit;
    }
    exit;
}

// ========== Obtener listas para filtros ==========
$periodos = [];
$res_periodos = $conexion->query("SELECT DISTINCT periodo FROM boletines ORDER BY periodo DESC");
if ($res_periodos) {
    while ($row = $res_periodos->fetch_assoc()) {
        $periodo_limpio = trim($row['periodo']);
        $periodo_limpio = str_replace([' / ', '/', ' '], '-', $periodo_limpio);
        if (preg_match('/^\d{4}-\d{4}$/', $periodo_limpio)) {
            $periodos[] = $periodo_limpio;
        } else {
            $periodos[] = $row['periodo'];
        }
    }
}
$periodos = array_unique($periodos);
if (empty($periodos)) {
    $periodos[] = $periodo_escolar_actual;
}

$salas = [];
$orden_salas = ['sala4', 'sala5', '1ro', '2do', '3ro', '4to', '5to', '6to'];
$res_salas = $conexion->query("SELECT DISTINCT sala FROM secciones");
if ($res_salas) {
    while ($row = $res_salas->fetch_assoc()) {
        $salas[] = $row['sala'];
    }
}
foreach ($orden_salas as $sala) {
    if (!in_array($sala, $salas)) {
        $salas[] = $sala;
    }
}
usort($salas, function($a, $b) use ($orden_salas) {
    $pos_a = array_search($a, $orden_salas);
    $pos_b = array_search($b, $orden_salas);
    if ($pos_a === false) $pos_a = 999;
    if ($pos_b === false) $pos_b = 999;
    return $pos_a - $pos_b;
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Rendimiento Final</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%); --navy: #002d54; }
        .page-header { background: var(--primary-gradient); color: white; border-radius: 12px; padding: 20px 28px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,45,84,0.2); }
        .card-filtros { border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .card-tabla { border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .card-tabla .card-header { background: var(--primary-gradient) !important; color: white; border-radius: 12px 12px 0 0 !important; padding: 14px 20px; font-weight: 600; }
        .table-rendimiento { font-size: 0.875rem; vertical-align: middle; }
        .table-rendimiento thead th { background-color: #f0f4f8; color: #002d54; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; border-bottom: 2px solid #002d54; text-align: center; }
        .table-rendimiento tbody td { text-align: center; vertical-align: middle; }
        .table-rendimiento tbody tr:hover { background-color: #e8f4f8; }
        .badge-sala { background-color: #e9ecef; color: #002d54; font-weight: 600; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; white-space: nowrap; }
        .badge-tipo { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .badge-inicial { background-color: #cce5ff; color: #004085; }
        .badge-primaria { background-color: #d4edda; color: #155724; }
        .casilla-rendimiento { display: inline-block; width: 26px; height: 26px; border: 2px solid #555; border-radius: 4px; text-align: center; line-height: 26px; font-size: 18pt; font-weight: bold; background: #fff; color: #000; }
        .casilla-rendimiento.marcada { border-color: #000; background: #f8f9fa; }
        .literal-box { display: inline-block; width: 36px; height: 36px; border: 2px solid #333; border-radius: 4px; text-align: center; line-height: 36px; font-size: 20pt; font-weight: bold; background: #f8f9fa; color: #000; }
        .texto-vacio { color: #6c757d; font-style: italic; }
        .btn-filtro { background: var(--primary-gradient); color: white; border: none; font-weight: 500; padding: 8px 24px; border-radius: 8px; transition: all 0.3s; }
        .btn-filtro:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,45,84,0.3); color: white; }
        .btn-limpiar { background: #6c757d; color: white; border: none; font-weight: 500; padding: 8px 24px; border-radius: 8px; transition: all 0.3s; }
        .btn-limpiar:hover { background: #5a6268; color: white; }
        .btn-pdf { background: #dc3545; color: white; border: none; font-weight: 500; padding: 8px 24px; border-radius: 8px; transition: all 0.3s; }
        .btn-pdf:hover { background: #b02a37; color: white; }
        .filtro-auto { font-size: 0.75rem; color: #6c757d; }
        .loading-spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #002d54; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @media print { .no-print { display: none !important; } .page-header { background: #002d54 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; } .card-tabla .card-header { background: #002d54 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; } .table-rendimiento thead th { background-color: #f0f4f8 !important; } .badge-sala { background-color: #e9ecef !important; } }
    </style>
</head>
<body>

<div class="container-fluid px-4">
    
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-clipboard-check me-2"></i> Reporte de Rendimiento Final</h4>
            <small class="opacity-75"><i class="fas fa-file-alt me-1"></i> Estudiantes con boletín completo (3 lapsos/momentos)</small>
        </div>
        <div class="mt-2 mt-md-0 no-print">
            <button onclick="window.print()" class="btn btn-pdf me-2"><i class="fas fa-print me-2"></i> Imprimir / PDF</button>
            <a href="rendimientofinalindex.php" class="btn btn-light fw-bold"><i class="fas fa-arrow-left me-2"></i> Volver</a>
        </div>
    </div>

    <div class="card card-filtros no-print">
        <div class="card-body p-4">
            <form method="GET" action="reporte_rendimiento_final.php" id="filtroForm" autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted"><i class="fas fa-calendar-alt me-1"></i> Año Escolar</label>
                        <select name="periodo" id="select-periodo" class="form-select shadow-none">
                            <option value="">Todos</option>
                            <?php foreach ($periodos as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= ($filtro_periodo == $p) ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted"><i class="fas fa-graduation-cap me-1"></i> Sala / Grado</label>
                        <select name="sala" id="select-sala" class="form-select shadow-none">
                            <option value="">Todas</option>
                            <?php foreach ($salas as $s): $nombre = $nombres_salas[$s] ?? $s; ?>
                                <option value="<?= htmlspecialchars($s) ?>" <?= ($filtro_sala == $s) ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted"><i class="fas fa-layer-group me-1"></i> Sección</label>
                        <select name="seccion" id="select-seccion" class="form-select shadow-none">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted"><i class="fas fa-chalkboard-teacher me-1"></i> Docente</label>
                        <select name="docente" id="select-docente" class="form-select shadow-none">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted"><i class="fas fa-search me-1"></i> Buscar</label>
                        <input type="text" name="busqueda" id="busquedaInput" class="form-control shadow-none" placeholder="Nombre o C.E..." value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                    <div class="col-md-2 text-end">
                        <span class="filtro-auto"><i class="fas fa-sync-alt me-1"></i> Filtro automático</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-tabla">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Rendimiento Final <span class="badge bg-light text-dark ms-2" id="contador-estudiantes">0 estudiante(s)</span></h6>
            <small class="opacity-75"><i class="fas fa-check-circle me-1"></i> Solo boletines completos (3 lapsos)</small>
        </div>
        <div class="card-body p-0" id="tabla-container">
            <div class="table-responsive">
                <table class="table table-hover table-rendimiento mb-0" id="tabla-rendimiento">
                    <thead>
                        <tr>
                            <th style="width:3%">#</th>
                            <th style="width:18%">Estudiante</th>
                            <th style="width:8%">C.E.</th>
                            <th style="width:10%">Sala / Grado</th>
                            <th style="width:8%">Sección</th>
                            <th style="width:12%">Docente</th>
                            <th style="width:8%">Tipo</th>
                            <th style="width:10%">Aprobado / Promovido</th>
                            <th style="width:8%">Aplazado</th>
                            <th style="width:8%">Literal</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-rendimiento">
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <div class="loading-spinner"></div>
                                <p class="text-muted mt-2">Cargando datos...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-database me-1"></i> Total: <span id="total-estudiantes">0</span> estudiante(s)</span>
            <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> Se muestran solo boletines con los 3 lapsos completos</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectSala = document.getElementById('select-sala');
    const selectSeccion = document.getElementById('select-seccion');
    const selectDocente = document.getElementById('select-docente');
    const selectPeriodo = document.getElementById('select-periodo');
    const busquedaInput = document.getElementById('busquedaInput');
    const tbody = document.getElementById('tbody-rendimiento');
    const contador = document.getElementById('contador-estudiantes');
    const totalEstudiantes = document.getElementById('total-estudiantes');
    let timeoutId = null;

    // ===== CARGAR SECCIONES =====
    function cargarSecciones(sala, seleccionado) {
        selectSeccion.innerHTML = '<option value="">Cargando...</option>';
        selectSeccion.disabled = true;
        selectDocente.innerHTML = '<option value="">Primero seleccione sección</option>';
        selectDocente.disabled = true;

        if (!sala) {
            selectSeccion.innerHTML = '<option value="">Todas</option>';
            selectSeccion.disabled = false;
            return;
        }

        fetch('reporte_rendimiento_final.php?ajax=1&action=cargar_secciones&sala=' + encodeURIComponent(sala))
            .then(res => res.json())
            .then(data => {
                selectSeccion.innerHTML = '<option value="">Todas</option>';
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
                if (seleccionado) {
                    cargarDocentes(seleccionado, document.querySelector('select[name="docente"]').value);
                }
                filtrarTabla();
            })
            .catch(() => {
                selectSeccion.innerHTML = '<option value="">Todas</option>';
                selectSeccion.disabled = false;
                filtrarTabla();
            });
    }

    // ===== CARGAR DOCENTES =====
    function cargarDocentes(seccion, seleccionado) {
        selectDocente.innerHTML = '<option value="">Cargando...</option>';
        selectDocente.disabled = true;

        if (!seccion) {
            selectDocente.innerHTML = '<option value="">Todos</option>';
            selectDocente.disabled = false;
            return;
        }

        fetch('reporte_rendimiento_final.php?ajax=1&action=cargar_docentes&seccion=' + seccion)
            .then(res => res.json())
            .then(data => {
                selectDocente.innerHTML = '<option value="">Todos</option>';
                data.forEach(doc => {
                    const option = document.createElement('option');
                    option.value = doc.id;
                    option.textContent = doc.nombre;
                    if (seleccionado && parseInt(seleccionado) === doc.id) {
                        option.selected = true;
                    }
                    selectDocente.appendChild(option);
                });
                selectDocente.disabled = false;
                filtrarTabla();
            })
            .catch(() => {
                selectDocente.innerHTML = '<option value="">Todos</option>';
                selectDocente.disabled = false;
                filtrarTabla();
            });
    }

    // ===== FILTRAR TABLA EN TIEMPO REAL (AJAX) =====
    function filtrarTabla() {
        const periodo = selectPeriodo.value;
        const sala = selectSala.value;
        const seccion = selectSeccion.value;
        const docente = selectDocente.value;
        const busqueda = busquedaInput.value.trim();

        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-4">
                    <div class="loading-spinner"></div>
                    <p class="text-muted mt-2">Cargando datos...</p>
                </td>
            </tr>
        `;

        const params = new URLSearchParams();
        params.append('ajax', '1');
        params.append('action', 'filtrar_tabla');
        if (periodo) params.append('periodo', periodo);
        if (sala) params.append('sala', sala);
        if (seccion) params.append('seccion', seccion);
        if (docente) params.append('docente', docente);
        if (busqueda) params.append('busqueda', busqueda);

        fetch('reporte_rendimiento_final.php?' + params.toString())
            .then(res => {
                if (!res.ok) {
                    throw new Error('HTTP error ' + res.status);
                }
                return res.json();
            })
            .then(data => {
                if (data.html) {
                    tbody.innerHTML = data.html;
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">No se encontraron estudiantes con boletín completo.</p>
                            </td>
                        </tr>
                    `;
                }
                contador.textContent = (data.total || 0) + ' estudiante(s)';
                totalEstudiantes.textContent = data.total || 0;
            })
            .catch(function(error) {
                console.error('Error:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-4 text-danger">
                            <i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>
                            Error al cargar los datos. Intente nuevamente.
                        </td>
                    </tr>
                `;
                contador.textContent = '0 estudiante(s)';
                totalEstudiantes.textContent = '0';
            });
    }

    // ===== EVENTOS EN TIEMPO REAL =====
    selectSala.addEventListener('change', function() {
        const sala = this.value;
        const seccionActual = selectSeccion.value;
        cargarSecciones(sala, seccionActual);
    });

    selectSeccion.addEventListener('change', function() {
        const seccion = this.value;
        const docenteActual = selectDocente.value;
        cargarDocentes(seccion, docenteActual);
    });

    selectPeriodo.addEventListener('change', filtrarTabla);
    selectDocente.addEventListener('change', filtrarTabla);

    if (busquedaInput) {
        busquedaInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(filtrarTabla, 400);
        });
    }

    // ===== INICIALIZAR =====
    const salaInicial = '<?= $filtro_sala ?>';
    const seccionInicial = '<?= $filtro_seccion ?>';
    const docenteInicial = '<?= $filtro_docente ?>';
    
    if (salaInicial) {
        cargarSecciones(salaInicial, seccionInicial);
        if (seccionInicial) {
            cargarDocentes(seccionInicial, docenteInicial);
        }
    } else {
        cargarSecciones('', '');
    }

    setTimeout(filtrarTabla, 500);
});
</script>

<?php include '../includes/footer.php'; ?>