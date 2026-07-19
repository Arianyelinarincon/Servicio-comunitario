<?php
// plantilla_ficha.php - NO debe tener session_start()
// Variables esperadas: $estudiante, $inscripciones, $id, $es_pdf, $es_preview

if (!function_exists('checkbox')) {
    function checkbox($valor) {
        $marcado = ($valor === 'Si' || $valor === 'Sí') ? 'checked-box' : '';
        return '<span class="checkbox-box ' . $marcado . '"></span>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Inscripción - <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></title>
    
    <?php if ($es_preview): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php endif; ?>

    <style>
        /* ===== ESTILOS GLOBALES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: <?= $es_preview ? '#f4f6f9' : '#fff' ?>;
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9.5px;
            line-height: 1.2;
            color: #000;
            padding: <?= $es_preview ? '20px' : '0' ?>;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ===== CONTENEDOR PRINCIPAL ===== */
        .hoja-impresion {
            background: #fff;
            width: 100%;
            max-width: <?= $es_preview ? '850px' : '100%' ?>;
            margin: 0 auto;
            padding: <?= $es_preview ? '30px 40px' : '0.3cm 0.6cm' ?>;
            box-shadow: <?= $es_preview ? '0 0 10px rgba(0,0,0,0.1)' : 'none' ?>;
            box-sizing: border-box;
        }

        /* ===== ESTILO EXCLUSIVO PARA EL PDF (ancho fijo y centrado) ===== */
        .hoja-impresion.pdf {
            width: 25.4cm;            /* Ancho de letter landscape menos márgenes laterales */
            margin: 0 auto;
            padding: 0.3cm 0.6cm;      /* Mismo padding que en preview */
            max-width: none;
        }

        /* ===== CABECERA ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .header-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-top: 3px;
        }
        .fotos-box {
            text-align: right;
            white-space: nowrap;
        }
        .foto-box {
            display: inline-block;
            width: 55px;
            height: 72px;
            border: 1.2px solid #000;
            background: #fafafa;
            margin-left: 4px;
        }

        /* ===== SECCIONES ===== */
        .seccion-titulo {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 8px;
            margin-bottom: 3px;
            font-size: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
        }

        /* ===== TABLA DE DATOS ===== */
        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .tabla-datos td {
            padding: 1px 2px;
            vertical-align: baseline;
            border: none;
            font-size: 9px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .tabla-datos td label {
            font-weight: normal;
            margin-right: 2px;
        }
        .tabla-datos td .dato {
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 20px;
            padding-bottom: 0px;
            font-size: 9px;
            max-width: 100%;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .tabla-datos td .dato-line {
            display: inline-block;
            min-width: 14px;
            text-align: center;
            font-weight: bold;
        }

        .opciones-cb {
            display: inline-block;
            font-size: 11px;
            margin-left: 1px;
        }
        .checkbox-box {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 1px solid #000;
            margin-right: 2px;
            vertical-align: middle;
        }
        .checked-box {
            background-color: #000;
        }

        /* ===== TABLA DE HISTORIAL ESCOLAR ===== */
        .tabla-notas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 5.5px;
            text-align: center;
            table-layout: fixed;
        }
        .tabla-notas th,
        .tabla-notas td {
            border: 1px solid #000;
            padding: 1.5px 1px;
            vertical-align: middle;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .tabla-notas th {
            font-weight: bold;
            background: transparent;
            font-size: 5px;
            text-transform: uppercase;
        }
        .tabla-notas td {
            font-weight: bold;
            font-size: 5.5px;
        }
        .tabla-notas td .dato-line {
            min-width: 10px;
        }

        /* ===== MÁRGENES PARA IMPRESIÓN Y PDF ===== */
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .hoja-impresion {
                box-shadow: none !important;
                padding: 0.3cm 0.6cm !important;
                max-width: 100%;
                border: none !important;
                margin: 0 auto !important;
                width: 100%;
            }
            /* ===== Ajuste para PDF: sin márgenes de página, solo padding ===== */
            <?php if ($es_pdf): ?>
            @page {
                margin: 0 !important;   /* Los márgenes los da el padding del contenedor */
                size: letter landscape;
            }
            <?php else: ?>
            @page {
                margin: 0.4cm 0.4cm;
                size: letter landscape;
            }
            <?php endif; ?>
            .tabla-notas {
                font-size: 5.5px;
            }
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<?php if ($es_preview): ?>
<div class="mt-2 mb-4 d-print-none text-center">
    <button onclick="window.print()" class="btn btn-info text-white me-2">
        <i class="fas fa-print me-2"></i> Imprimir
    </button>
    <a href="generar_ficha_pdf.php?id=<?= $id ?>&download=1" class="btn btn-success me-2">
        <i class="fas fa-file-pdf me-2"></i> Descargar PDF
    </a>
    <a href="ver_ficha.php?id=<?= $id ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Volver
    </a>
</div>
<?php endif; ?>

<!-- ===== CONTENEDOR CON CLASE CONDICIONAL ===== -->
<div class="hoja-impresion <?= $es_pdf ? 'pdf' : '' ?>">
    
    <!-- ===== CABECERA ===== -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="header-title">FICHA DE INSCRIPCIÓN</div>
            </td>
            <td style="width: 30%;" class="fotos-box">
                <span class="foto-box"></span>
                <span class="foto-box"></span>
            </td>
        </tr>
    </table>

    <!-- ===== DATOS DEL ALUMNO ===== -->
    <div class="seccion-titulo">DATOS DEL ALUMNO</div>
    <table class="tabla-datos">
        <tr>
            <td style="width:45%;"><label>Nombres y Apellidos</label> <span class="dato"><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></span></td>
            <td style="width:25%;"><label>Fecha Nacimiento:</label> <span class="dato"><?= !empty($estudiante['fecha_nacimiento']) ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) : '' ?></span></td>
            <td style="width:15%;"><label>Sexo</label> <span class="dato"><?= htmlspecialchars($estudiante['genero'] ?? '') ?></span></td>
        </tr>
        <tr>
            <td style="width:30%;"><label>Cédula Escolar</label> <span class="dato"><?= htmlspecialchars($estudiante['cedula_escolar'] ?? '') ?></span></td>
            <td style="width:30%;"><label>Nacionalidad</label> <span class="dato"><?= htmlspecialchars($estudiante['nacionalidad'] ?? '') ?></span></td>
            <td style="width:40%;"><label>País de Nacimiento</label> <span class="dato"><?= htmlspecialchars($estudiante['pais_nacimiento'] ?? '') ?></span></td>
        </tr>
        <tr>
            <td style="width:25%;"><label>Estado de Nacimiento</label> <span class="dato"><?= htmlspecialchars($estudiante['estado_nacimiento'] ?? '') ?></span></td>
            <td style="width:75%;" colspan="2"><label>Dirección:</label> <span class="dato"><?= htmlspecialchars($estudiante['direccion'] ?? '') ?></span></td>
        </tr>
        <tr>
            <td style="width:20%;"><label>Estado de Residencia</label> <span class="dato"><?= htmlspecialchars($estudiante['estado_residencia'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Municipio</label> <span class="dato"><?= htmlspecialchars($estudiante['municipio'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Parroquia:</label> <span class="dato"><?= htmlspecialchars($estudiante['parroquia'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Ciudad:</label> <span class="dato"><?= htmlspecialchars($estudiante['ciudad'] ?? '') ?></span></td>
        </tr>
        <tr>
            <td style="width:25%;">
                <label>Sufre Alguna Enfermedad</label>
                <span class="opciones-cb">
                    <?= checkbox($estudiante['enfermedad'] ?? '') ?> Si
                    <?php $no = ($estudiante['enfermedad'] ?? '') == 'No' ? 'checked-box' : ''; ?>
                    <span class="checkbox-box <?= $no ?>" style="margin-left:8px;"></span> No
                </span>
            </td>
            <td style="width:35%;"><label>Cual:</label> <span class="dato"><?= htmlspecialchars($estudiante['enfermedad_cual'] ?? '') ?></span></td>
            <td style="width:30%;">
                <label>Puede Realizar Educación Física</label>
                <span class="opciones-cb">
                    <?= checkbox($estudiante['educacion_fisica'] ?? '') ?> Si
                    <?php $no2 = ($estudiante['educacion_fisica'] ?? '') == 'No' ? 'checked-box' : ''; ?>
                    <span class="checkbox-box <?= $no2 ?>" style="margin-left:8px;"></span> No
                </span>
            </td>
        </tr>
        <tr>
            <td style="width:25%;"><label>Porque</label> <span class="dato"><?= htmlspecialchars($estudiante['educacion_fisica_porque'] ?? '') ?></span></td>
            <td style="width:35%;">
                <label>Es alérgico algún medicamento:</label>
                <span class="opciones-cb">
                    <?= checkbox($estudiante['alergia'] ?? '') ?> Si
                    <?php $no3 = ($estudiante['alergia'] ?? '') == 'No' ? 'checked-box' : ''; ?>
                    <span class="checkbox-box <?= $no3 ?>" style="margin-left:8px;"></span> No
                </span>
            </td>
            <td style="width:30%;"><label>Cual:</label> <span class="dato"><?= htmlspecialchars($estudiante['alergia_cual'] ?? '') ?></span></td>
        </tr>
    </table>

    <!-- ===== DATOS DEL REPRESENTANTE ===== -->
    <div class="seccion-titulo">DATOS DEL REPRESENTANTE</div>
    <table class="tabla-datos">
        <tr>
            <td style="width:22%;"><label>Cédula de Identidad:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_cedula'] ?? '') ?></span></td>
            <td style="width:78%;" colspan="3"><label>Nombres y Apellidos:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_nombre'] ?? '') ?></span></td>
        </tr>
        <tr>
            <td style="width:20%;"><label>Fecha Nacimiento</label> <span class="dato"><?= !empty($estudiante['rep_fecha_nac']) ? date('d/m/Y', strtotime($estudiante['rep_fecha_nac'])) : '' ?></span></td>
            <td style="width:20%;"><label>Estado Civil.</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_estado_civil'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Afinidad:</label> <span class="dato"><?= htmlspecialchars($estudiante['afinidad'] ?? '') ?></span></td>
            <td style="width:25%;"><label>Teléfono:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_telefono'] ?? '') ?></span></td>
        </tr>
        <tr>
            <td style="width:15%;"><label>Sexo</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_sexo'] ?? '') ?></span></td>
            <td style="width:20%;"><label>País de Nacimiento</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_pais_nac'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Estado de Nacimiento:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_estado_nac'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Nacionalidad:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_nacionalidad'] ?? '') ?></span></td>
        </tr>
        <tr>
            <td style="width:35%;"><label>Dirección</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_direccion'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Estado de Residencia.</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_estado_res'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Municipio</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_municipio'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Parroquia</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_parroquia'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Ciudad</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_ciudad'] ?? '') ?></span></td>
        </tr>
    </table>

    <!-- ===== DATOS DE LOS PADRES ===== -->
    <div class="seccion-titulo">DATOS DE LOS PADRES</div>
    <table class="tabla-datos">
        <tr>
            <td style="width:40%;"><label>Nombres y Apellidos de la Madre</label> <span class="dato"><?= htmlspecialchars($estudiante['madre_nombre'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Cédula</label> <span class="dato"><?= htmlspecialchars($estudiante['madre_cedula'] ?? '') ?></span></td>
            <td style="width:25%;"><label>Teléfono</label> <span class="dato"><?= htmlspecialchars($estudiante['madre_telefono'] ?? '') ?></span></td>
        </tr>
        <tr>
            <td style="width:40%;"><label>Nombre y Apellido del Padre</label> <span class="dato"><?= htmlspecialchars($estudiante['padre_nombre'] ?? '') ?></span></td>
            <td style="width:20%;"><label>Cédula</label> <span class="dato"><?= htmlspecialchars($estudiante['padre_cedula'] ?? '') ?></span></td>
            <td style="width:25%;"><label>Teléfono</label> <span class="dato"><?= htmlspecialchars($estudiante['padre_telefono'] ?? '') ?></span></td>
        </tr>
    </table>

    <!-- ===== HISTORIAL ESCOLAR ===== -->
    <table class="tabla-notas">
        <thead>
            <tr>
                <th style="width:9%;">Año<br>Escolar</th>
                <th style="width:10%;">Grado y<br>Sección</th>
                <th style="width:5%;">Reg.</th>
                <th style="width:5%;">Rep</th>
                <th style="width:5%;">C</th>
                <th style="width:5%;">F</th>
                <th style="width:5%;">P</th>
                <th style="width:6%;">Peso</th>
                <th style="width:6%;">Talla</th>
                <th style="width:14%;">Firma<br>Representante</th>
                <th style="width:12%;">Fecha<br>Inscripción</th>
                <th style="width:12%;">Funcionario</th>
            </tr>
        </thead>
        <tbody>
            <?php for($i = 0; $i < 8; $i++): ?>
                <?php 
                $ins = isset($inscripciones[$i]) ? $inscripciones[$i] : null; 
                if ($ins):
                    $anos = explode('-', $ins['ano_escolar']);
                    $ano1 = isset($anos[0]) ? substr(trim($anos[0]), -2) : '';
                    $ano2 = isset($anos[1]) ? substr(trim($anos[1]), -2) : '';
                ?>
                <tr>
                    <td>20<span class="dato-line"><?= $ano1 ?></span> - 20<span class="dato-line"><?= $ano2 ?></span></td>
                    <td><?= htmlspecialchars($ins['grado_seccion'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['registro'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['repite'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['c'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['f'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['p'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['peso'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['talla'] ?? '') ?></td>
                    <td></td>
                    <td><?= !empty($ins['fecha_inscripcion']) ? date('d/m/Y', strtotime($ins['fecha_inscripcion'])) : '' ?></td>
                    <td></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td>20<span class="dato-line"></span> - 20<span class="dato-line"></span></td>
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
                <?php endif; ?>
            <?php endfor; ?>
        </tbody>
    </table>

</div>

</body>
</html>