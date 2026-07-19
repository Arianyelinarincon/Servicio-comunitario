<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once '../config/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    header("Location: listado.php");
    exit();
}

// ========== OBTENER DATOS DEL ESTUDIANTE ==========
$sql = "SELECT e.*, 
               r.nombre_completo AS rep_nombre, r.cedula AS rep_cedula, r.telefono AS rep_telefono,
               r.parentesco AS rep_parentesco, r.fecha_nacimiento AS rep_fecha_nac,
               r.estado_civil AS rep_estado_civil, r.afinidad AS rep_afinidad,
               r.sexo AS rep_sexo, r.pais_nacimiento AS rep_pais, r.estado_nacimiento AS rep_estado_nac,
               r.nacionalidad AS rep_nacionalidad, r.direccion AS rep_direccion,
               r.estado_residencia AS rep_estado_res, r.municipio AS rep_municipio,
               r.parroquia AS rep_parroquia, r.ciudad AS rep_ciudad,
               s.nombre AS seccion_nombre,
               p.nombre AS profesor_nombre
        FROM estudiantes e
        LEFT JOIN representantes r ON e.representante_id = r.id
        LEFT JOIN secciones s ON e.seccion_id = s.id
        LEFT JOIN profesores p ON p.seccion = s.id AND p.estatus = 'Activo'
        WHERE e.id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header("Location: listado.php");
    exit();
}

// ========== OBTENER HISTORIAL ESCOLAR ==========
$sql_historial = "SELECT * FROM inscripciones WHERE estudiante_id = ? ORDER BY ano_escolar DESC";
$stmt_hist = $conexion->prepare($sql_historial);
$stmt_hist->bind_param("i", $id);
$stmt_hist->execute();
$historial = $stmt_hist->get_result();
$stmt_hist->close();

include '../includes/header.php';
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
    .card-ficha {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 24px;
    }
    .card-ficha .card-header {
        background: var(--primary-gradient) !important;
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 14px 20px;
        font-weight: 600;
    }
    .table-ficha {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .table-ficha thead th {
        background-color: #f0f4f8;
        color: #002d54;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #002d54;
    }
    .table-ficha tbody tr:hover {
        background-color: #e8f4f8;
    }
    .badge-genero {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-varon { background-color: #cce5ff; color: #004085; }
    .badge-hembra { background-color: #f8d7da; color: #721c24; }
    .badge-estado {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-activo { background-color: #d4edda; color: #155724; }
    .badge-inactivo { background-color: #f8d7da; color: #721c24; }
    .badge-egresado { background-color: #fff3cd; color: #856404; }
    .badge-preinscrito { background-color: #d1ecf1; color: #0c5460; }
    .info-label {
        font-weight: 600;
        color: #002d54;
        min-width: 120px;
        display: inline-block;
    }
    .info-value {
        color: #333;
    }
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
    .btn-descargar {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 7px 20px;
        border-radius: 5px;
        font-weight: bold;
        transition: background 0.3s;
        text-decoration: none;
    }
    .btn-descargar:hover {
        background-color: #218838;
        color: white;
    }
    .btn-ver-ficha {
        background-color: #17a2b8;
        color: white;
        border: none;
        padding: 7px 20px;
        border-radius: 5px;
        font-weight: bold;
        transition: background 0.3s;
        text-decoration: none;
    }
    .btn-ver-ficha:hover {
        background-color: #138496;
        color: white;
    }
    .row-dato {
        padding: 4px 0;
        border-bottom: 1px solid #e9ecef;
    }
    .row-dato:last-child {
        border-bottom: none;
    }
</style>

<div class="container-fluid px-4">
    
    <!-- Cabecera -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-id-card me-2"></i> Ficha del Estudiante</h4>
            <small class="opacity-75">Información completa del alumno</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="generar_ficha_pdf.php?id=<?= $id ?>&preview=1" target="_blank" class="btn-ver-ficha me-2">
                <i class="fas fa-eye me-2"></i> Ver Ficha
            </a>
            <a href="generar_ficha_pdf.php?id=<?= $id ?>&download=1" class="btn-descargar me-2">
                <i class="fas fa-file-pdf me-2"></i> Descargar PDF
            </a>
            <a href="listado.php" class="btn-volver">
                <i class="fas fa-arrow-left me-2"></i> Volver al Listado
            </a>
        </div>
    </div>

    <!-- Datos del Estudiante -->
    <div class="card card-ficha">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-user-graduate me-2"></i> Datos Personales</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="row-dato">
                        <span class="info-label">Nombre Completo:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Cédula Escolar:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['cedula_escolar'] ?? 'No registrada') ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Fecha de Nacimiento:</span>
                        <span class="info-value"><?= date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Género:</span>
                        <span class="info-value">
                            <span class="badge-genero <?= ($estudiante['genero'] == 'V') ? 'badge-varon' : 'badge-hembra' ?>">
                                <?= ($estudiante['genero'] == 'V') ? 'Varón' : 'Hembra' ?>
                            </span>
                        </span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Lugar de Nacimiento:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['lugar_nacimiento'] ?? 'No registrado') ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row-dato">
                        <span class="info-label">Sala / Grado:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['sala'] ?? 'No asignado') ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Sección:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['seccion_nombre'] ?? 'No asignada') ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Profesor:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['profesor_nombre'] ?? 'Sin asignar') ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Estatus:</span>
                        <span class="info-value">
                            <span class="badge-estado <?= ($estudiante['estatus'] == 'Activo') ? 'badge-activo' : (($estudiante['estatus'] == 'Inactivo') ? 'badge-inactivo' : (($estudiante['estatus'] == 'Egresado') ? 'badge-egresado' : 'badge-preinscrito')) ?>">
                                <?= htmlspecialchars($estudiante['estatus'] ?? 'No definido') ?>
                            </span>
                        </span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Inscripción Completa:</span>
                        <span class="info-value">
                            <?= ($estudiante['inscripcion_completa'] == 1) ? '<span class="text-success"><i class="fas fa-check-circle"></i> Sí</span>' : '<span class="text-danger"><i class="fas fa-times-circle"></i> No</span>' ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($estudiante['direccion'])): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="row-dato">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value"><?= nl2br(htmlspecialchars($estudiante['direccion'])) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Datos del Representante -->
    <?php if ($estudiante['rep_nombre']): ?>
    <div class="card card-ficha">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i> Datos del Representante</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="row-dato">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['rep_nombre']) ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Cédula:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['rep_cedula']) ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['rep_telefono']) ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row-dato">
                        <span class="info-label">Parentesco:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['rep_parentesco'] ?? 'No especificado') ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Fecha Nacimiento:</span>
                        <span class="info-value"><?= $estudiante['rep_fecha_nac'] ? date('d/m/Y', strtotime($estudiante['rep_fecha_nac'])) : 'No registrada' ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value"><?= nl2br(htmlspecialchars($estudiante['rep_direccion'] ?? 'No registrada')) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Datos de los Padres -->
    <?php if ($estudiante['madre_nombre'] || $estudiante['padre_nombre']): ?>
    <div class="card card-ficha">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-users me-2"></i> Datos de los Padres</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">Madre</h6>
                    <div class="row-dato">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['madre_nombre'] ?? 'No registrado') ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Cédula:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['madre_cedula'] ?? 'No registrada') ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['madre_telefono'] ?? 'No registrado') ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">Padre</h6>
                    <div class="row-dato">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['padre_nombre'] ?? 'No registrado') ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Cédula:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['padre_cedula'] ?? 'No registrada') ?></span>
                    </div>
                    <div class="row-dato">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value"><?= htmlspecialchars($estudiante['padre_telefono'] ?? 'No registrado') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historial Escolar (SIN columna "Funcionario") -->
    <div class="card card-ficha">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-history me-2"></i> Historial Escolar</h6>
            <span class="badge bg-light text-dark"><?= $historial->num_rows ?> registro(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if ($historial && $historial->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-ficha mb-0">
                    <thead>
                        <tr>
                            <th>Año Escolar</th>
                            <th>Reg.</th>
                            <th>Rep</th>
                            <th>C</th>
                            <th>F</th>
                            <th>P</th>
                            <th>Peso</th>
                            <th>Talla</th>
                            <th>Fecha Inscripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $historial->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['ano_escolar']) ?></strong></td>
                            <td><?= htmlspecialchars($row['registro'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['repite'] ?? 'No') ?></td>
                            <td><?= htmlspecialchars($row['c'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['f'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['p'] ?? '') ?></td>
                            <td><?= $row['peso'] ? number_format($row['peso'], 2) : '' ?></td>
                            <td><?= $row['talla'] ? number_format($row['talla'], 2) : '' ?></td>
                            <td><?= date('d/m/Y', strtotime($row['fecha_inscripcion'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                No hay registros de inscripción para este estudiante.
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white text-muted small">
            <i class="fas fa-info-circle me-1"></i> La columna "Funcionario" se muestra únicamente en los reportes impresos.
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>