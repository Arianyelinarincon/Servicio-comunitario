<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once '../config/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header("Location: gestionar_profesores.php");
    exit();
}

// Obtener datos del profesor
$stmt = $conexion->prepare("SELECT * FROM profesores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$profesor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profesor) {
    header("Location: gestionar_profesores.php");
    exit();
}

// Obtener nombre de la sección
$nombre_seccion = '';
$stmt_sec = $conexion->prepare("SELECT nombre FROM secciones WHERE id = ?");
$stmt_sec->bind_param("i", $profesor['seccion']);
$stmt_sec->execute();
$sec_result = $stmt_sec->get_result();
if ($sec = $sec_result->fetch_assoc()) {
    $nombre_seccion = $sec['nombre'];
}
$stmt_sec->close();

// Obtener estudiantes asignados
$sql_estudiantes = "SELECT e.id, e.nombre, e.apellido, e.sala, e.cedula_escolar, e.genero, e.estatus, e.inscripcion_completa
                    FROM estudiantes e 
                    WHERE e.sala = ? AND e.estatus = 'Activo' 
                    ORDER BY e.apellido ASC, e.nombre ASC";
$stmt2 = $conexion->prepare($sql_estudiantes);
$stmt2->bind_param("s", $profesor['sala']);
$stmt2->execute();
$estudiantes = $stmt2->get_result();
$total_estudiantes = $estudiantes->num_rows;
$stmt2->close();

include '../includes/header.php';
?>

<style>
    :root { 
        --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%);
        --navy: #002d54;
    }
    .page-header {
        background: var(--primary-gradient);
        color: white;
        border-radius: 12px;
        padding: 20px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,45,84,0.2);
    }
    .card-detalle {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 24px;
    }
    .card-detalle .card-header {
        background: var(--primary-gradient) !important;
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 14px 20px;
        font-weight: 600;
    }
    .card-detalle .card-body {
        padding: 24px;
    }
    .info-profesor {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px 20px;
        background: #f8f9fa;
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #002d54;
    }
    .info-profesor .label {
        font-weight: 600;
        color: #002d54;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-profesor .value {
        font-weight: 500;
        color: #333;
        font-size: 0.95rem;
        display: block;
        margin-top: 2px;
    }
    .table-estudiantes {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .table-estudiantes thead th {
        background-color: #f0f4f8;
        color: #002d54;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #002d54;
        text-align: center;
    }
    .table-estudiantes tbody tr:hover {
        background-color: #e8f4f8;
        cursor: pointer;
    }
    .table-estudiantes tbody tr {
        transition: background 0.2s ease;
    }
    .table-estudiantes tbody td {
        text-align: center;
        vertical-align: middle;
    }
    .table-estudiantes tbody td:first-child {
        text-align: left;
        padding-left: 15px;
    }
    .table-estudiantes tbody td:last-child {
        text-align: center;
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
    .badge-estado {
        font-size: 0.7rem;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 500;
    }
    .badge-activo { background-color: #d4edda; color: #155724; }
    .badge-inactivo { background-color: #f8d7da; color: #721c24; }
    .badge-completo { background-color: #d4edda; color: #155724; }
    .badge-incompleto { background-color: #fff3cd; color: #856404; }
    .badge-genero {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-varon { background-color: #cce5ff; color: #004085; }
    .badge-hembra { background-color: #f8d7da; color: #721c24; }
    .btn-accion {
        margin: 2px;
        border-radius: 6px;
        padding: 5px 10px;
        font-size: 0.78rem;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-accion:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .nombre-link {
        color: #002d54;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s;
    }
    .nombre-link:hover {
        color: #004a7c;
        text-decoration: underline;
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
        display: inline-block;
    }
    .btn-volver:hover {
        background-color: #5a6268;
        color: white;
    }
    .btn-editar-prof {
        background-color: #ffc107;
        color: #212529;
        border: none;
        padding: 7px 20px;
        border-radius: 5px;
        font-weight: bold;
        transition: background 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-editar-prof:hover {
        background-color: #e0a800;
        color: #212529;
    }
    .empty-state {
        padding: 50px 20px;
        text-align: center;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 3rem;
        color: #adb5bd;
        margin-bottom: 15px;
        display: block;
    }
    .fila-link {
        cursor: pointer;
    }
    .fila-link:hover {
        background-color: #e8f4f8 !important;
    }
    @media (max-width: 768px) {
        .info-profesor {
            grid-template-columns: 1fr 1fr;
        }
        .table-estudiantes {
            font-size: 0.75rem;
        }
        .btn-accion {
            padding: 3px 6px;
            font-size: 0.7rem;
        }
    }
    @media (max-width: 576px) {
        .info-profesor {
            grid-template-columns: 1fr;
        }
        .page-header {
            padding: 15px;
        }
        .page-header h4 {
            font-size: 1.1rem;
        }
    }
</style>

<div class="container-fluid px-4">
    
    <!-- Cabecera -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-chalkboard-teacher me-2"></i> Detalle del Profesor</h4>
            <small class="opacity-75"><i class="fas fa-user-graduate me-1"></i> Lista de estudiantes asignados</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="editar_profesor.php?id=<?= $id ?>" class="btn btn-editar-prof me-2">
                <i class="fas fa-edit me-2"></i> Editar Profesor
            </a>
            <a href="gestionar_profesores.php" class="btn btn-volver">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
        </div>
    </div>

    <!-- Información del Profesor -->
    <div class="card card-detalle">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i> Información del Docente</h6>
        </div>
        <div class="card-body">
            <div class="info-profesor">
                <div>
                    <span class="label"><i class="fas fa-user me-1"></i> Nombre</span>
                    <span class="value"><?= htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']) ?></span>
                </div>
                <div>
                    <span class="label"><i class="fas fa-id-card me-1"></i> Cédula</span>
                    <span class="value"><?= htmlspecialchars($profesor['cedula'] ?? 'N/A') ?></span>
                </div>
                <div>
                    <span class="label"><i class="fas fa-graduation-cap me-1"></i> Sala / Grado</span>
                    <span class="value"><?= htmlspecialchars($profesor['sala']) ?></span>
                </div>
                <div>
                    <span class="label"><i class="fas fa-layer-group me-1"></i> Sección</span>
                    <span class="value"><?= htmlspecialchars($nombre_seccion ?: 'N/A') ?></span>
                </div>
                <div>
                    <span class="label"><i class="fas fa-phone me-1"></i> Teléfono</span>
                    <span class="value"><?= htmlspecialchars($profesor['telefono'] ?? 'N/A') ?></span>
                </div>
                <div>
                    <span class="label"><i class="fas fa-users me-1"></i> Total Estudiantes</span>
                    <span class="value"><strong><?= $total_estudiantes ?></strong> estudiante(s)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Estudiantes -->
    <div class="card card-detalle">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Estudiantes Asignados 
                <span class="badge bg-light text-dark ms-2"><?= $total_estudiantes ?></span>
            </h6>
            <small class="opacity-75"><i class="fas fa-info-circle me-1"></i> Click en el nombre para ver ficha</small>
        </div>
        <div class="card-body p-0">
            <?php if ($total_estudiantes > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-estudiantes mb-0">
                        <thead>
                            <tr>
                                <th style="width:30%">Estudiante</th>
                                <th style="width:15%">Cédula Escolar</th>
                                <th style="width:8%">Género</th>
                                <th style="width:10%">Sala</th>
                                <th style="width:10%">Inscripción</th>
                                <th style="width:10%">Estatus</th>
                                <th style="width:17%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $estudiantes->fetch_assoc()): 
                                $nombre_completo = htmlspecialchars($row['nombre'] . ' ' . $row['apellido']);
                                $genero = $row['genero'] ?? '';
                                $genero_label = ($genero == 'V') ? 'Varón' : (($genero == 'H') ? 'Hembra' : 'N/A');
                                $genero_clase = ($genero == 'V') ? 'badge-varon' : 'badge-hembra';
                                $completa = $row['inscripcion_completa'] ?? 0;
                                $completa_label = $completa ? 'Completa' : 'Incompleta';
                                $completa_clase = $completa ? 'badge-completo' : 'badge-incompleto';
                                $estatus = $row['estatus'] ?? 'Activo';
                                $estatus_clase = ($estatus == 'Activo') ? 'badge-activo' : 'badge-inactivo';
                            ?>
                            <tr class="fila-link" onclick="window.location.href='../estudiantes/ver_ficha.php?id=<?= $row['id'] ?>'">
                                <td>
                                    <a href="../estudiantes/ver_ficha.php?id=<?= $row['id'] ?>" class="nombre-link" target="_blank">
                                        <?= $nombre_completo ?>
                                    </a>
                                </td>
                                <td><span class="font-monospace"><?= htmlspecialchars($row['cedula_escolar'] ?? 'N/A') ?></span></td>
                                <td><span class="badge-genero <?= $genero_clase ?>"><?= $genero_label ?></span></td>
                                <td><span class="badge-sala"><?= htmlspecialchars($row['sala']) ?></span></td>
                                <td>
                                    <span class="badge-estado <?= $completa_clase ?>">
                                        <?= $completa_label ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-estado <?= $estatus_clase ?>">
                                        <?= htmlspecialchars($estatus) ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <a href="../estudiantes/ver_ficha.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info btn-accion" target="_blank" title="Ver ficha completa">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../estudiantes/editar_estudiantes.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary btn-accion" title="Editar estudiante">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../estudiantes/generar_ficha_pdf.php?id=<?= $row['id'] ?>&preview=1" class="btn btn-sm btn-success btn-accion" target="_blank" title="Ver ficha PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-graduate"></i>
                    <h5 class="fw-bold">No hay estudiantes asignados</h5>
                    <p class="text-muted">Este profesor no tiene estudiantes asignados a su sala.</p>
                    <a href="../estudiantes/inscripcion.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus me-2"></i> Inscribir estudiante
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-database me-1"></i> Total: <?= $total_estudiantes ?> estudiante(s)</span>
            <span class="text-muted small"><i class="fas fa-user-tie me-1"></i> Profesor: <?= htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']) ?></span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hacer que toda la fila sea clickeable, pero sin interferir con los botones
    document.querySelectorAll('.fila-link').forEach(function(row) {
        row.addEventListener('click', function(e) {
            // Si el click fue en un enlace o botón, no hacer nada
            if (e.target.closest('a') || e.target.closest('.btn')) {
                return;
            }
            // Si el click fue en la fila, redirigir al primer enlace
            const link = this.querySelector('.nombre-link');
            if (link) {
                window.location.href = link.href;
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>