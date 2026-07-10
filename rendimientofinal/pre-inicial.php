<?php
require_once "../estadisticas/config_db.php";

// ========== AJAX HANDLER ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['usuario'])) {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action == 'cargar_secciones') {
        $sala = $_POST['sala'] ?? '';
        $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
        $stmt->bind_param("s", $sala);
        $stmt->execute();
        $result = $stmt->get_result();
        $secciones = [];
        while($row = $result->fetch_assoc()) {
            $secciones[] = ['id' => $row['id'], 'nombre' => $row['nombre']];
        }
        echo json_encode(['secciones' => $secciones]);
        $stmt->close();
        exit;
    }
    
    if ($action == 'cargar_docentes') {
        $seccion = (int)$_POST['seccion'];
        $stmt = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? AND estatus = 'Activo' ORDER BY id LIMIT 1");
        $stmt->bind_param("i", $seccion);
        $stmt->execute();
        $result = $stmt->get_result();
        $docentes = [];
        while($row = $result->fetch_assoc()) {
            $docentes[] = $row;
        }
        echo json_encode(['docentes' => $docentes]);
        $stmt->close();
        exit;
    }
    exit;
}

include "../includes/header.php";

// ========== CORRECCIÓN: Año actual fijo 2025-2026 ==========
function generarOpcionesAnios() {
    $anio_actual = date('Y');
    $anio_inicio = $anio_actual - 5;
    $anio_fin = $anio_actual + 5;
    $opciones = '';
    for ($i = $anio_inicio; $i <= $anio_fin; $i++) {
        $periodo = $i . '-' . ($i + 1);
        // ========== CORRECCIÓN: Marcar 2025-2026 como seleccionado ==========
        $selected = ($periodo == '2025-2026') ? 'selected' : '';
        $opciones .= "<option value=\"$periodo\" $selected>$periodo</option>";
    }
    return $opciones;
}
?>

<style>
    :root { --navy: #002d54; }
    .card { border-radius: 12px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .bg-navy { background-color: var(--navy) !important; color: white;}
    .btn-volver {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 7px 20px;
        border-radius: 5px;
        font-weight: bold;
        transition: background 0.3s;
        text-decoration: none;
    }
    .btn-volver:hover {
        background-color: #5a6268;
        color: white;
    }
</style>

<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-header bg-navy d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Generar Formulario de Rendimiento Final - Pre Inicial</h5>
            <!-- ========== CORRECCIÓN: Botón VOLVER agregado ========== -->
            <a href="rendimientofinalindex.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> VOLVER
            </a>
        </div>
        <div class="card-body p-4">
            <form action="formulariopre-inicial.php" method="GET" target="_blank" class="row g-3 align-items-end" id="filtroForm">
                
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">SALA / GRADO</label>
                    <select name="sala" id="select-grado" class="form-select shadow-none" required onchange="pasoGrado()">
                        <option value="">Seleccione grado...</option>
                        <optgroup label="Educación Inicial">
                            <option value="sala4">Sala 4 Años</option>
                            <option value="sala5">Sala 5 Años</option>
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-2" id="seccion-seccion">
                    <label class="small fw-bold text-muted">SECCIÓN</label>
                    <select name="seccion" id="select-seccion" class="form-select shadow-none" disabled onchange="pasoSeccion()">
                        <option value="">Primero seleccione grado...</option>
                    </select>
                </div>

                <div class="col-md-3" id="seccion-docente" style="display:none;">
                    <div class="alert alert-info py-1 small mb-0" id="info-docente"></div>
                    <input type="hidden" name="profesor" id="select-docente" value="">
                </div>

                <div class="col-md-2" id="seccion-periodo" style="display:none;">
                    <label class="small fw-bold text-muted">AÑO ESCOLAR</label>
                    <select name="periodo" id="select-periodo" class="form-select shadow-none" required>
                        <?php echo generarOpcionesAnios(); ?>
                    </select>
                </div>

                <div class="col-md-2" id="seccion-boton" style="display:none;">
                    <button type="submit" class="btn w-100 fw-bold bg-navy border-0" style="padding: 7px 0;">
                        <i class="fas fa-external-link-alt"></i> ABRIR FORMULARIO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function pasoGrado() {
    const sala = document.getElementById('select-grado').value;
    const seccionSelect = document.getElementById('select-seccion');
    const docenteHidden = document.getElementById('select-docente');
    const infoDocente = document.getElementById('info-docente');
    const docenteDiv = document.getElementById('seccion-docente');
    
    seccionSelect.innerHTML = '<option value="">Primero seleccione grado...</option>';
    seccionSelect.disabled = true;
    
    if (docenteHidden) docenteHidden.value = '';
    if (infoDocente) infoDocente.innerHTML = '';
    if (docenteDiv) docenteDiv.style.display = 'none';
    
    document.getElementById('seccion-periodo').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';

    if (sala !== "") {
        seccionSelect.innerHTML = '<option value="">Cargando secciones...</option>';
        const formData = new FormData();
        formData.append('action', 'cargar_secciones');
        formData.append('sala', sala);
        fetch('?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                seccionSelect.innerHTML = '<option value="">Seleccione sección...</option>';
                if (data.secciones && data.secciones.length > 0) {
                    data.secciones.forEach(sec => {
                        seccionSelect.innerHTML += `<option value="${sec.id}">${sec.nombre}</option>`;
                    });
                    seccionSelect.disabled = false;
                } else {
                    seccionSelect.innerHTML = '<option value="">No hay secciones</option>';
                }
            });
    }
}

function pasoSeccion() {
    const seccion = document.getElementById('select-seccion').value;
    const docenteHidden = document.getElementById('select-docente');
    const infoDocente = document.getElementById('info-docente');
    const docenteDiv = document.getElementById('seccion-docente');
    
    if (docenteHidden) docenteHidden.value = '';
    if (infoDocente) infoDocente.innerHTML = '';
    if (docenteDiv) docenteDiv.style.display = 'none';
    
    document.getElementById('seccion-periodo').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';

    if (seccion !== "") {
        if (infoDocente) infoDocente.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando docente...';
        if (docenteDiv) docenteDiv.style.display = 'block';
        
        const formData = new FormData();
        formData.append('action', 'cargar_docentes');
        formData.append('seccion', seccion);
        fetch('?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.docentes && data.docentes.length > 0) {
                    const docente = data.docentes[0];
                    if (docenteHidden) docenteHidden.value = docente.id;
                    if (infoDocente) infoDocente.innerHTML = `<i class="fas fa-chalkboard-user"></i> Docente asignado: <strong>${docente.nombre}</strong>`;
                    document.getElementById('seccion-periodo').style.display = 'block';
                    document.getElementById('seccion-boton').style.display = 'block';
                } else {
                    if (docenteHidden) docenteHidden.value = '';
                    if (infoDocente) infoDocente.innerHTML = '<span class="text-danger">⚠️ No hay docente asignado a esta sección.</span>';
                    document.getElementById('seccion-periodo').style.display = 'none';
                    document.getElementById('seccion-boton').style.display = 'none';
                }
            })
            .catch(() => {
                if (infoDocente) infoDocente.innerHTML = '<span class="text-danger">Error al cargar docente.</span>';
                if (docenteHidden) docenteHidden.value = '';
            });
    }
}
</script>

<?php include "../includes/footer.php"; ?>