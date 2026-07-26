<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'admin', 'super_admin', 'directiva'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';
require_once '../config/configuracion.php';

$periodo_escolar_actual = obtenerPeriodoEscolar();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========== MANEJAR PETICIONES AJAX ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $sala = isset($_GET['sala']) ? trim($_GET['sala']) : '';
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $filtro_anio = isset($_GET['anio']) ? trim($_GET['anio']) : '';

    // ===== CONSULTA PARA CONTAR =====
    $sql_count = "
        SELECT COUNT(DISTINCT e.id) AS total,
               SUM(CASE WHEN e.genero = 'V' THEN 1 ELSE 0 END) AS varones,
               SUM(CASE WHEN e.genero = 'H' THEN 1 ELSE 0 END) AS hembras
        FROM estudiantes e
        WHERE e.estatus = 'Activo'
          AND EXISTS (SELECT 1 FROM inscripciones i WHERE i.estudiante_id = e.id)
    ";
    $params_count = [];
    $types_count = "";

    if ($sala) {
        $sql_count .= " AND e.sala = ?";
        $params_count[] = $sala;
        $types_count .= "s";
    }
    if ($busqueda) {
        $sql_count .= " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.cedula_escolar LIKE ?)";
        $like = '%' . $busqueda . '%';
        $params_count[] = $like;
        $params_count[] = $like;
        $params_count[] = $like;
        $types_count .= "sss";
    }
    if ($filtro_anio) {
        $sql_count .= " AND EXISTS (SELECT 1 FROM inscripciones i2 WHERE i2.estudiante_id = e.id AND i2.ano_escolar = ?)";
        $params_count[] = $filtro_anio;
        $types_count .= "s";
    }

    $stmt_count = $conexion->prepare($sql_count);
    if ($params_count) {
        $stmt_count->bind_param($types_count, ...$params_count);
    }
    $stmt_count->execute();
    $counts = $stmt_count->get_result()->fetch_assoc();
    $stmt_count->close();

    // ===== CONSULTA PRINCIPAL CON DISTINCT Y GROUP BY =====
    $sql = "
        SELECT DISTINCT e.id, e.nombre, e.apellido, e.cedula_escolar, e.sala, e.genero,
               r.nombre_completo AS rep_nombre,
               r.cedula AS rep_cedula,
               r.telefono AS rep_telefono,
               s.nombre AS seccion_nombre,
               p.nombre AS profesor_nombre,
               (SELECT ano_escolar FROM inscripciones WHERE estudiante_id = e.id ORDER BY fecha_inscripcion DESC LIMIT 1) AS ano_escolar_actual
        FROM estudiantes e
        LEFT JOIN representantes r ON e.representante_id = r.id
        LEFT JOIN secciones s ON e.seccion_id = s.id
        LEFT JOIN profesores p ON p.seccion = s.id AND p.estatus = 'Activo'
        WHERE e.estatus = 'Activo'
          AND EXISTS (SELECT 1 FROM inscripciones i WHERE i.estudiante_id = e.id)
    ";

    $params = [];
    $types = "";

    if ($sala) {
        $sql .= " AND e.sala = ?";
        $params[] = $sala;
        $types .= "s";
    }
    if ($busqueda) {
        $sql .= " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.cedula_escolar LIKE ?)";
        $like = '%' . $busqueda . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= "sss";
    }
    if ($filtro_anio) {
        $sql .= " AND EXISTS (SELECT 1 FROM inscripciones i2 WHERE i2.estudiante_id = e.id AND i2.ano_escolar = ?)";
        $params[] = $filtro_anio;
        $types .= "s";
    }
    
    $sql .= " GROUP BY e.id ORDER BY ano_escolar_actual DESC, e.sala, e.nombre, e.apellido";

    $stmt = $conexion->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover table-listado mb-0">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:15%">Nombre Completo</th>
                    <th style="width:8%">Estado</th>
                    <th style="width:10%">Cédula Escolar</th>
                    <th style="width:10%">Sala</th>
                    <th style="width:8%">Sección</th>
                    <th style="width:14%">Profesor</th>
                    <th style="width:12%">Representante</th>
                    <th style="width:10%">Año Escolar</th>
                    <th style="width:10%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): 
                    $contador = 1;
                    while($e = $result->fetch_assoc()): 
                        $estado = obtenerEstadoCompleto($e['id'], $conexion);
                        $ficha_ok = $estado['ficha']['completa'];
                        $boletin_ok = $estado['boletin']['completo'];
                        $faltantes_ficha = $estado['ficha']['faltantes'];
                        $faltantes_bol = $estado['boletin']['faltantes'];
                        
                        $tooltip = '';
                        $clase_badge = '';
                        $icono = '';
                        $texto_estado = '';
                        
                        if (!$ficha_ok) {
                            $clase_badge = 'bg-danger';
                            $icono = 'fa-exclamation-triangle';
                            $tooltip = '⚠️ Ficha incompleta. Faltan: ' . implode(', ', $faltantes_ficha);
                            $texto_estado = 'Ficha incompleta';
                        } elseif (!$boletin_ok) {
                            $clase_badge = 'bg-warning text-dark';
                            $icono = 'fa-clock';
                            $tooltip = '✅ Ficha completa, pero faltan datos para boletín: ' . implode(', ', $faltantes_bol);
                            $texto_estado = 'Faltan datos boletín';
                        } else {
                            $clase_badge = 'bg-success';
                            $icono = 'fa-check-circle';
                            $tooltip = '✅ Completamente al día.';
                            $texto_estado = 'Completa';
                        }
                        
                        $mapa_salas = [
                            'sala4' => 'Sala 4 Años',
                            'sala5' => 'Sala 5 Años',
                            '1ro' => '1er Grado',
                            '2do' => '2do Grado',
                            '3ro' => '3er Grado',
                            '4to' => '4to Grado',
                            '5to' => '5to Grado',
                            '6to' => '6to Grado'
                        ];
                        $sala_nombre = $mapa_salas[$e['sala']] ?? $e['sala'];
                ?>
                    <tr>
                        <td class="text-center fw-bold text-muted"><?= $contador++ ?></td>
                        <td><strong><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?></strong></td>
                        <td>
                            <span class="badge <?= $clase_badge ?> estado-icono" 
                                  style="cursor:help; font-size:0.8rem; padding:5px 10px;"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="left"
                                  data-bs-html="true"
                                  title="<?= htmlspecialchars($tooltip) ?>">
                                <i class="fas <?= $icono ?> me-1"></i> <?= $texto_estado ?>
                            </span>
                            <?php if (!$ficha_ok): ?>
                                <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.6rem;">
                                    <?= count($faltantes_ficha) ?>
                                </span>
                            <?php elseif (!$boletin_ok): ?>
                                <span class="badge bg-warning text-dark rounded-pill ms-1" style="font-size:0.6rem;">
                                    <?= count($faltantes_bol) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><span class="font-monospace"><?= htmlspecialchars($e['cedula_escolar']) ?></span></td>
                        <td><span class="badge-sala"><?= htmlspecialchars($sala_nombre) ?></span></td>
                        <td><?= htmlspecialchars($e['seccion_nombre'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($e['profesor_nombre'] ?? 'Sin asignar') ?></td>
                        <td><?= htmlspecialchars($e['rep_nombre'] ?? 'No asignado') ?></td>
                        <td class="text-center"><?= htmlspecialchars($e['ano_escolar_actual'] ?? 'No registrado') ?></td>
                        <td class="text-nowrap">
                            <a href="ver_ficha.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info" target="_blank" title="Ver ficha completa"><i class="fas fa-eye"></i></a>
                            <a href="editar_estudiantes.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary" title="Editar datos"><i class="fas fa-edit"></i></a>
                            <?php if ($_SESSION['rol'] === 'super_admin' || $_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'directiva'): ?>
                                <a href="eliminar_estudiante_completo.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar estudiante" onclick="return confirm('¿Está seguro de eliminar este estudiante? Se eliminarán TODOS sus registros asociados.')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center py-4"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>No se encontraron estudiantes inscritos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html,
        'total' => (int)($counts['total'] ?? 0),
        'varones' => (int)($counts['varones'] ?? 0),
        'hembras' => (int)($counts['hembras'] ?? 0)
    ]);
    exit;
}

// ========== MANEJAR ELIMINACIÓN ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error CSRF");
    }
    $id = intval($_POST['id']);
    header("Location: eliminar_estudiante_completo.php?id=$id");
    exit();
}

include '../includes/header.php';

$sala_filtro = isset($_GET['sala']) ? trim($_GET['sala']) : '';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_anio = isset($_GET['anio']) ? trim($_GET['anio']) : '';

// ===== OBTENER AÑOS ESCOLARES DISPONIBLES =====
$anios_disponibles = [];
$res_anios = $conexion->query("SELECT DISTINCT ano_escolar FROM inscripciones ORDER BY ano_escolar DESC");
while ($row = $res_anios->fetch_assoc()) {
    $anios_disponibles[] = $row['ano_escolar'];
}
if (empty($anios_disponibles)) {
    $anios_disponibles[] = $periodo_escolar_actual;
}
?>
<!-- HTML y JavaScript (sin cambios) -->
<?php include '../includes/footer.php'; ?>