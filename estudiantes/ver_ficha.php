<?php
session_start();
require_once '../config/conexion.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: listado.php");
    exit();
}
// Obtener datos del estudiante y representante
$stmt = $conexion->prepare("SELECT e.*, 
    r.nombre_completo AS rep_nombre, r.cedula AS rep_cedula, r.telefono AS rep_telefono,
    r.fecha_nacimiento AS rep_fecha_nac, r.estado_civil AS rep_estado_civil, r.afinidad,
    r.sexo AS rep_sexo, r.pais_nacimiento AS rep_pais_nac, r.estado_nacimiento AS rep_estado_nac,
    r.nacionalidad AS rep_nacionalidad, r.direccion AS rep_direccion,
    r.estado_residencia AS rep_estado_res, r.municipio AS rep_municipio,
    r.parroquia AS rep_parroquia, r.ciudad AS rep_ciudad
    FROM estudiantes e LEFT JOIN representantes r ON e.representante_id = r.id WHERE e.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$estudiante) {
    header("Location: listado.php");
    exit();
}
// Obtener historial escolar
$stmt_ins = $conexion->prepare("SELECT * FROM inscripciones WHERE estudiante_id = ? ORDER BY ano_escolar DESC");
$stmt_ins->bind_param("i", $id);
$stmt_ins->execute();
$inscripciones = $stmt_ins->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_ins->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha del Estudiante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print { .no-print { display: none; } body { margin: 0; padding: 0; } }
        .ficha-card { border: 1px solid #ddd; border-radius: 10px; padding: 20px; background: white; margin-bottom: 20px; }
        .ficha-header { background: #003366; color: white; padding: 15px; border-radius: 8px 8px 0 0; text-align: center; }
        .table-ficha { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-ficha td, .table-ficha th { border: 1px solid #000; padding: 6px; vertical-align: top; }
        .table-ficha th { background: #f2f2f2; font-weight: bold; }
        .firma-linea { border-bottom: 1px solid black; display: inline-block; width: 150px; }
    </style>
</head>
<body class="bg-light">
<div class="container my-4">
    <!-- ========== CORRECCIÓN: Botón Descargar PDF agregado ========== -->
    <div class="text-end no-print mb-3">
        <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Imprimir / PDF</button>
        <a href="generar_ficha_pdf.php?id=<?= $id ?>" class="btn btn-success" target="_blank">
            <i class="fas fa-file-pdf"></i> Descargar Ficha PDF
        </a>
        <a href="listado.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al listado</a>
    </div>
    <div class="ficha-card">
        <div class="ficha-header">
            <h3>FICHA DE INSCRIPCIÓN</h3>
            <p>U.E.B.N. Juan Pablo Pérez Alfonzo</p>
        </div>
        <div class="p-4">
            <!-- DATOS DEL ALUMNO -->
            <h5>DATOS DEL ALUMNO</h5>
            <table class="table-ficha">
                <tr><td style="width:25%"><strong>Nombres y Apellidos:</strong></td><td colspan="3"><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></td><td style="width:15%"><strong>Fecha Nacimiento:</strong></td><td><?= $estudiante['fecha_nacimiento'] ?></td><td style="width:10%"><strong>Sexo:</strong></td><td><?= ($estudiante['genero']=='V')?'Varón':'Hembra' ?></td></tr>
                <tr><td><strong>Cédula Escolar:</strong></td><td><?= htmlspecialchars($estudiante['cedula_escolar']) ?></td><td><strong>Nacionalidad:</strong></td><td><?= htmlspecialchars($estudiante['nacionalidad']) ?></td><td><strong>País Nacimiento:</strong></td><td><?= htmlspecialchars($estudiante['pais_nacimiento']) ?></td><td colspan="2"></td></tr>
                <tr><td><strong>Estado Nacimiento:</strong></td><td colspan="7"><?= htmlspecialchars($estudiante['estado_nacimiento']) ?></td></tr>
                <tr><td><strong>Dirección:</strong></td><td colspan="7"><?= nl2br(htmlspecialchars($estudiante['direccion'])) ?></td></tr>
                <tr><td><strong>Estado Residencia:</strong></td><td><?= htmlspecialchars($estudiante['estado_residencia']) ?></td><td><strong>Municipio:</strong></td><td><?= htmlspecialchars($estudiante['municipio']) ?></td><td><strong>Parroquia:</strong></td><td><?= htmlspecialchars($estudiante['parroquia']) ?></td><td><strong>Ciudad:</strong></td><td><?= htmlspecialchars($estudiante['ciudad']) ?></td></tr>
                <tr><td><strong>Enfermedad:</strong></td><td><?= $estudiante['enfermedad'] ?></td><td><strong>¿Cuál?</strong></td><td colspan="5"><?= htmlspecialchars($estudiante['enfermedad_cual']) ?></td></tr>
                <tr><td><strong>Educación Física:</strong></td><td><?= $estudiante['educacion_fisica'] ?></td><td><strong>¿Por qué?</strong></td><td colspan="5"><?= htmlspecialchars($estudiante['educacion_fisica_porque']) ?></td></tr>
                <tr><td><strong>Alergia medicamentos:</strong></td><td><?= $estudiante['alergia'] ?></td><td><strong>¿Cuál(es)?</strong></td><td colspan="5"><?= htmlspecialchars($estudiante['alergia_cual']) ?></td></tr>
            </table>

            <!-- DATOS DEL REPRESENTANTE -->
            <h5 class="mt-4">DATOS DEL REPRESENTANTE</h5>
            <table class="table-ficha">
                <tr><td><strong>Cédula:</strong></td><td><?= htmlspecialchars($estudiante['rep_cedula']) ?></td><td><strong>Nombres y Apellidos:</strong></td><td colspan="5"><?= htmlspecialchars($estudiante['rep_nombre']) ?></td></tr>
                <tr><td><strong>Fecha Nac.:</strong></td><td><?= $estudiante['rep_fecha_nac'] ?></td><td><strong>Estado Civil:</strong></td><td><?= htmlspecialchars($estudiante['rep_estado_civil']) ?></td><td><strong>Afinidad:</strong></td><td><?= htmlspecialchars($estudiante['afinidad']) ?></td><td><strong>Teléfono:</strong></td><td><?= htmlspecialchars($estudiante['rep_telefono']) ?></td></tr>
                <tr><td><strong>Sexo:</strong></td><td><?= ($estudiante['rep_sexo']=='V')?'Varón':'Hembra' ?></td><td><strong>País Nac.:</strong></td><td><?= htmlspecialchars($estudiante['rep_pais_nac']) ?></td><td><strong>Estado Nac.:</strong></td><td><?= htmlspecialchars($estudiante['rep_estado_nac']) ?></td><td><strong>Nacionalidad:</strong></td><td><?= htmlspecialchars($estudiante['rep_nacionalidad']) ?></td></tr>
                <tr><td><strong>Dirección:</strong></td><td colspan="7"><?= nl2br(htmlspecialchars($estudiante['rep_direccion'])) ?></td></tr>
                <tr><td><strong>Estado Res.:</strong></td><td><?= htmlspecialchars($estudiante['rep_estado_res']) ?></td><td><strong>Municipio:</strong></td><td><?= htmlspecialchars($estudiante['rep_municipio']) ?></td><td><strong>Parroquia:</strong></td><td><?= htmlspecialchars($estudiante['rep_parroquia']) ?></td><td><strong>Ciudad:</strong></td><td><?= htmlspecialchars($estudiante['rep_ciudad']) ?></td></tr>
            </table>

            <!-- DATOS DE LOS PADRES -->
            <h5 class="mt-4">DATOS DE LOS PADRES</h5>
            <table class="table-ficha">
                <tr><td style="width:20%"><strong>Madre:</strong></td><td style="width:40%"><?= htmlspecialchars($estudiante['madre_nombre']) ?></td><td style="width:15%"><strong>Cédula:</strong></td><td style="width:15%"><?= htmlspecialchars($estudiante['madre_cedula']) ?></td><td style="width:10%"><strong>Teléfono:</strong></td><td><?= htmlspecialchars($estudiante['madre_telefono']) ?></td></tr>
                <tr><td><strong>Padre:</strong></td><td><?= htmlspecialchars($estudiante['padre_nombre']) ?></td><td><strong>Cédula:</strong></td><td><?= htmlspecialchars($estudiante['padre_cedula']) ?></td><td><strong>Teléfono:</strong></td><td><?= htmlspecialchars($estudiante['padre_telefono']) ?></td></tr>
            </table>

            <!-- HISTORIAL ESCOLAR -->
            <h5 class="mt-4">HISTORIAL ESCOLAR</h5>
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr><th>Año Escolar</th><th>Grado y Sección</th><th>Reg.</th><th>Rep.</th><th>C</th><th>F</th><th>P</th><th>Peso (kg)</th><th>Talla (cm)</th><th>Firma del Representante</th><th>Fecha Inscripción</th><th>Funcionario</th></tr>
                </thead>
                <tbody>
                    <?php if (count($inscripciones) > 0): ?>
                        <?php foreach ($inscripciones as $ins): ?>
                        <tr>
                            <td><?= htmlspecialchars($ins['ano_escolar']) ?></td>
                            <td><?= htmlspecialchars($ins['grado_seccion']) ?></td>
                            <td><?= htmlspecialchars($ins['registro']) ?></td>
                            <td><?= htmlspecialchars($ins['repite']) ?></td>
                            <td><?= htmlspecialchars($ins['c']) ?></td>
                            <td><?= htmlspecialchars($ins['f']) ?></td>
                            <td><?= htmlspecialchars($ins['p']) ?></td>
                            <td><?= $ins['peso'] ?> kg</td><td><?= $ins['talla'] ?> cm</td>
                            <td><span class="firma-linea"></span></td>
                            <td><?= $ins['fecha_inscripcion'] ?></td>
                            <td><?= htmlspecialchars($ins['funcionario']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="12" class="text-center">No hay registros escolares.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="text-muted text-center mt-3 no-print">
                <small>Ficha generada por el Sistema de Gestión Educativa. Las firmas deben ser colocadas después de imprimir.</small>
            </div>
        </div>
    </div>
</div>
</body>
</html>