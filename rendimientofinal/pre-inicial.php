<?php
require_once "../estadisticas/config_db.php";

// ========== AJAX HANDLER (Se mantiene igual) ==========
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
        $stmt = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? ORDER BY nombre ASC");
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
?>

<style>
    :root { --navy: #002d54; }
    .card { border-radius: 12px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .bg-navy { background-color: var(--navy) !important; color: white;}
</style>

<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-header bg-navy">
            <h5 class="mb-0">Generar Formulario de Rendimiento</h5>
        </div>
        <div class="card-body p-4">
            <form action="formulariopre-inicial.php" method="GET" target="_blank" class="row g-3 align-items-end" id="filtroForm">
                
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">SALA / GRADO</label>
                    <select name="sala" id="select-grado" class="form-select shadow-none" required onchange="pasoGrado()">
                        <option value="">Seleccione grado...</option>
                        <optgrou label="Educación Inicial">
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

                <div class="col-md-3" id="seccion-docente">
                    <label class="small fw-bold text-muted">PROFESOR / DOCENTE</label>
                    <select name="profesor" id="select-docente" class="form-select shadow-none" disabled required onchange="pasoDocente()">
                        <option value="">Primero seleccione sección...</option>
                    </select>
                </div>

                <div class="col-md-2" id="seccion-mes" style="display:none;">
                    <label class="small fw-bold text-muted">PERÍODO</label>
                    <input type="month" name="periodo" id="select-mes" class="form-control shadow-none" required>
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
// El JavaScript se mantiene casi intacto para manejar la cascada de los selects
function pasoGrado() {
    const sala = document.getElementById('select-grado').value;
    const seccionSelect = document.getElementById('select-seccion');
    const docenteSelect = document.getElementById('select-docente');
    
    seccionSelect.innerHTML = '<option value="">Primero seleccione grado...</option>';
    seccionSelect.disabled = true;
    docenteSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
    docenteSelect.disabled = true;
    document.getElementById('seccion-mes').style.display = 'none';
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
    document.getElementById('seccion-mes').style.display = 'none';
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
    const mesDiv = document.getElementById('seccion-mes');
    const botonDiv = document.getElementById('seccion-boton');
    if (profesor !== "") {
        mesDiv.style.display = 'block';
        botonDiv.style.display = 'block';
    } else {
        mesDiv.style.display = 'none';
        botonDiv.style.display = 'none';
    }
};
</script>

<?php include "../includes/footer.php"; ?>