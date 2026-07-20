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
    
    // ========== CORRECCIÓN: FILTRAR SOLO PROFESORES ACTIVOS ==========
    if ($action == 'cargar_docentes') {
        $seccion = (int)$_POST['seccion'];
        $stmt = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? AND estatus = 'Activo' ORDER BY nombre ASC");
        $stmt->bind_param("i", $seccion);
        $stmt->execute();
        $result = $stmt->get_result();
        $docentes = [];
        while($row = $result->fetch_assoc()) {
            $docentes[] = ['id' => $row['id'], 'nombre' => $row['nombre']];
        }
        echo json_encode(['docentes' => $docentes]);
        $stmt->close();
        exit;
    }
    exit;
}

include "../includes/header.php";

// ========== Función para generar años ==========
function generarOpcionesAnios() {
    $anio_actual = date('Y');
    $anio_inicio = $anio_actual - 5;
    $anio_fin = $anio_actual + 5;
    $opciones = '';
    for ($i = $anio_inicio; $i <= $anio_fin; $i++) {
        $periodo = $i . '-' . ($i + 1);
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
            <h5 class="mb-0">Generar Formulario de Rendimiento - Primaria</h5>
            <a href="rendimientofinalindex.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> VOLVER
            </a>
        </div>
        <div class="card-body p-4">
            <form action="formularioprimaria.php" method="GET" target="_blank" class="row g-3 align-items-end" id="filtroForm">
    
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">GRADO</label>
                    <select name="sala" id="select-grado" class="form-select shadow-none" required onchange="pasoGrado()">
                        <option value="">Seleccione grado...</option>
                        <optgroup label="Educación Primaria">
                            <option value="1ro">Primer Grado</option>
                            <option value="2do">Segundo Grado</option>
                            <option value="3ro">Tercer Grado</option>
                            <option value="4to">Cuarto Grado</option>
                            <option value="5to">Quinto Grado</option>
                            <option value="6to">Sexto Grado</option>
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-2" id="seccion-seccion">
                    <label class="small fw-bold text-muted">SECCIÓN</label>
                    <select name="seccion" id="select-seccion" class="form-select shadow-none" disabled onchange="pasoSeccion()">
                        <option value="">Primero seleccione grado...</option>
                    </select>
                </div>

                <div class="col-md-3" id="seccion-docente">
                    <label class="small fw-bold text-muted">PROFESOR / DOCENTE</label>
                    <select name="profesor" id="select-docente" class="form-select shadow-none" disabled required onchange="pasoDocente()">
                        <option value="">Primero seleccione sección...</option>
                    </select>
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
    const docenteSelect = document.getElementById('select-docente');
    
    seccionSelect.innerHTML = '<option value="">Primero seleccione grado...</option>';
    seccionSelect.disabled = true;
    docenteSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
    docenteSelect.disabled = true;
    
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
    const docenteSelect = document.getElementById('select-docente');
    
    docenteSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
    docenteSelect.disabled = true;
    
    document.getElementById('seccion-periodo').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';

    if (seccion !== "") {
        docenteSelect.innerHTML = '<option value="">Cargando docentes...</option>';
        const formData = new FormData();
        formData.append('action', 'cargar_docentes');
        formData.append('seccion', seccion);
        fetch('?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                docenteSelect.innerHTML = '<option value="">Seleccione docente...</option>';
                if(data.docentes && data.docentes.length > 0) {
                    data.docentes.forEach(d => {
                        docenteSelect.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
                    });
                    docenteSelect.disabled = false;
                } else {
                    docenteSelect.innerHTML = '<option value="">No hay docentes</option>';
                }
            });
    }
}

window.pasoDocente = function() {
    const profesor = document.getElementById('select-docente').value;
    const periodoDiv = document.getElementById('seccion-periodo');
    const botonDiv = document.getElementById('seccion-boton');
    if (profesor !== "") {
        periodoDiv.style.display = 'block';
        botonDiv.style.display = 'block';
    } else {
        periodoDiv.style.display = 'none';
        botonDiv.style.display = 'none';
    }
};
</script>

<?php include "../includes/footer.php"; ?>