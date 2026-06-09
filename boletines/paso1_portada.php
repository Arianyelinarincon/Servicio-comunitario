<?php
session_start();

// Limpiar solo las variables del boletín anterior (no la sesión del usuario)
$vars_boletin = ['estudiante', 'ce', 'grupo', 'ano_escolar', 'docente', 'representante',
                 'observacion', 'm1_proyecto', 'm1_formacion', 'm1_relacion', 'm1_sugerencias',
                 'm2_proyecto', 'm2_formacion', 'm2_relacion', 'm2_sugerencias',
                 'm3_proyecto', 'm3_formacion', 'm3_relacion', 'm3_sugerencias'];
foreach ($vars_boletin as $var) unset($_SESSION[$var]);

require_once "../estadisticas/config_db.php"; // para conexión a BD

// Guardar el tipo de boletín (inicial/primaria) en sesión
if (isset($_GET['tipo'])) {
    $_SESSION['tipo_boletin'] = $_GET['tipo'];
} elseif (!isset($_SESSION['tipo_boletin'])) {
    $_SESSION['tipo_boletin'] = 'inicial'; // valor por defecto
}

// Búsqueda AJAX para autocompletado
if (isset($_GET['buscar_estudiante'])) {
    $termino = $_GET['buscar_estudiante'] . '%';
    $sql = "SELECT e.nombre, e.apellido, e.cedula_escolar, r.nombre_completo AS rep_nombre,
                   s.nombre AS grupo, p.nombre AS doc_nombre, p.apellido AS doc_apellido
            FROM estudiantes e
            LEFT JOIN representantes r ON e.representante_id = r.id
            LEFT JOIN secciones s ON e.seccion_id = s.id
            LEFT JOIN profesores p ON p.seccion = s.id
            WHERE CONCAT(e.nombre, ' ', e.apellido) LIKE ?
            LIMIT 10";
    $stmt = $conexion->prepare($sql);
    $buscar = "%$termino%";
    $stmt->bind_param("s", $buscar);
    $stmt->execute();
    $result = $stmt->get_result();
    $sugerencias = [];
    while ($row = $result->fetch_assoc()) {
        $sugerencias[] = [
            'nombre_completo' => $row['nombre'] . ' ' . $row['apellido'],
            'ce' => $row['cedula_escolar'],
            'grupo' => $row['grupo'] ?? 'N/A',
            'docente' => ($row['doc_nombre'] ?? '') . ' ' . ($row['doc_apellido'] ?? ''),
            'representante' => $row['rep_nombre'] ?? 'N/A'
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($sugerencias);
    exit;
}

// Procesar el formulario y guardar en sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['estudiante'] = htmlspecialchars($_POST['estudiante']);
    $_SESSION['ce'] = htmlspecialchars($_POST['ce']);
    $_SESSION['grupo'] = htmlspecialchars($_POST['grupo']);
    $_SESSION['ano_escolar'] = htmlspecialchars($_POST['ano_escolar']);
    $_SESSION['docente'] = htmlspecialchars($_POST['docente']);
    $_SESSION['representante'] = htmlspecialchars($_POST['representante']);
    header('Location: paso2_observaciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Paso 1: Datos de Portada</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin:0; }
        .contenedor { background: white; padding: 30px; border-radius: 8px; max-width: 900px; margin: 0 auto; }
        h2 { color: #1a237e; text-align: center; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .caja-busqueda { position: relative; }
        .sugerencias {
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            display: none;
        }
        .sugerencias div { padding: 8px; cursor: pointer; }
        .sugerencias div:hover { background: #e0e0e0; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        input[readonly] { background: #e9ecef; }
        .btn-siguiente { background: #1a237e; color: white; padding: 10px 20px; border: none; border-radius: 4px; float: right; margin-top: 20px; opacity: 0.5; }
        .btn-siguiente:enabled { opacity: 1; cursor: pointer; }
        .alerta-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-top: 20px; display: none; }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="contenedor">
    <h2>Paso 1: Datos de Portada</h2>
    <form method="POST">
        <div class="grid">
            <div>
                <p>Estudiante:</p>
                <div class="caja-busqueda">
                    <input type="text" name="estudiante" id="estudiante" autocomplete="off" placeholder="Escriba el nombre...">
                    <div id="sugerencias" class="sugerencias"></div>
                </div>
            </div>
            <div><p>C.E (Cédula Escolar):</p><input type="text" name="ce" id="ce" readonly></div>
            <div><p>Grupo:</p><input type="text" name="grupo" id="grupo" readonly></div>
            <div><p>Año Escolar:</p><input type="text" name="ano_escolar" id="ano_escolar" value="2025-2026" readonly></div>
            <div><p>Docente:</p><input type="text" name="docente" id="docente" readonly></div>
            <div><p>Representante:</p><input type="text" name="representante" id="representante" readonly></div>
        </div>
        <div id="mensaje_error" class="alerta-error">Estudiante no encontrado.</div>
        <div style="overflow:hidden;"><button type="submit" id="btn_siguiente" class="btn-siguiente" disabled>Siguiente Paso</button></div>
    </form>
</div>
<script>
    const inputEstudiante = document.getElementById('estudiante');
    const sugerenciasDiv = document.getElementById('sugerencias');
    const ceInput = document.getElementById('ce');
    const grupoInput = document.getElementById('grupo');
    const docenteInput = document.getElementById('docente');
    const representanteInput = document.getElementById('representante');
    const btnSiguiente = document.getElementById('btn_siguiente');
    const mensajeError = document.getElementById('mensaje_error');

    function limpiarCampos() {
        ceInput.value = ''; grupoInput.value = ''; docenteInput.value = ''; representanteInput.value = '';
        btnSiguiente.disabled = true; mensajeError.style.display = 'none';
    }
    function seleccionarEstudiante(data) {
        inputEstudiante.value = data.nombre_completo;
        ceInput.value = data.ce;
        grupoInput.value = data.grupo;
        docenteInput.value = data.docente;
        representanteInput.value = data.representante;
        btnSiguiente.disabled = false;
        sugerenciasDiv.style.display = 'none';
        mensajeError.style.display = 'none';
    }
    inputEstudiante.addEventListener('input', function() {
        const texto = this.value.trim();
        if (texto.length < 2) { sugerenciasDiv.style.display = 'none'; limpiarCampos(); return; }
        fetch('?buscar_estudiante=' + encodeURIComponent(texto))
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) { sugerenciasDiv.style.display = 'none'; limpiarCampos(); mensajeError.style.display = 'block'; return; }
                sugerenciasDiv.innerHTML = '';
                data.forEach(est => {
                    const div = document.createElement('div');
                    div.textContent = est.nombre_completo;
                    div.onclick = () => seleccionarEstudiante(est);
                    sugerenciasDiv.appendChild(div);
                });
                sugerenciasDiv.style.display = 'block';
                mensajeError.style.display = 'none';
            })
            .catch(() => { sugerenciasDiv.style.display = 'none'; mensajeError.style.display = 'block'; });
    });
    document.addEventListener('click', function(e) {
        if (!sugerenciasDiv.contains(e.target) && e.target !== inputEstudiante) sugerenciasDiv.style.display = 'none';
    });
</script>
</body>
</html>