<?php
/**
 * SISTEMA DE GESTIÓN ESCOLAR (S.G.E) - MÓDULO ESTUDIANTES
 * Archivo Controlador (Procesamiento de Peticiones y Acciones)
 */

// 1. Incluir la conexión a la base de datos y el modelo de datos
include_once '../config/conexion.php';
include_once 'gestion.php';

// =========================================================================
// BLOQUE A: PROCESAR PETICIONES POST (Envío de Formularios)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    $accion = $_POST['accion'];

    // --- ACCIÓN 1: REGISTRAR NUEVA INSCRIPCIÓN ---
    if ($accion === 'registrar') {
        
        // Empaquetar y limpiar los datos del formulario del Representante
        $datosRepresentante = [
            'nombre'   => trim($_POST['rep_nombre']),
            'cedula'   => trim($_POST['rep_cedula']),
            'telefono' => trim($_POST['rep_telefono'])
        ];

        // Empaquetar y limpiar los datos del formulario del Estudiante
        $datosEstudiante = [
            'nombre'           => trim($_POST['nombre']),
            'cedula_escolar'   => trim($_POST['cedula_escolar']),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'],
            'genero'           => $_POST['genero'],
            'sala'             => trim($_POST['sala']),
            'alergias'         => trim($_POST['alergias'])
        ];

        // Validación básica de seguridad (Campos obligatorios que no deben ir vacíos)
        if (empty($datosEstudiante['nombre']) || empty($datosEstudiante['cedula_escolar']) || empty($datosRepresentante['nombre'])) {
            header("Location: index.php?msg=error");
            exit();
        }

        // Llamar a la función del modelo encargada de insertar en ambas tablas
        $registroExitoso = registrarNuevoEstudiante($conexion, $datosRepresentante, $datosEstudiante);

        if ($registroExitoso) {
            // Redireccionar notificando éxito al index
            header("Location: index.php?msg=registrado_exito");
        } else {
            header("Location: index.php?msg=error");
        }
        exit();
    }

    // --- ACCIÓN 2: GUARDAR CAMBIOS DE EDICIÓN ---
    if ($accion === 'actualizar') {
        
        // Capturar el ID oculto del estudiante enviado desde el formulario
        $id_estudiante = $_POST['id'];

        // Empaquetar datos modificados del Representante
        $datosRepresentante = [
            'nombre'   => trim($_POST['rep_nombre']),
            'cedula'   => trim($_POST['rep_cedula']),
            'telefono' => trim($_POST['rep_telefono'])
        ];

        // Empaquetar datos modificados del Estudiante
        $datosEstudiante = [
            'nombre'           => trim($_POST['nombre']),
            'cedula_escolar'   => trim($_POST['cedula_escolar']),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'],
            'genero'           => $_POST['genero'],
            'sala'             => trim($_POST['sala']),
            'alergias'         => trim($_POST['alergias'])
        ];

        // Validación preventiva
        if (empty($id_estudiante) || empty($datosEstudiante['nombre']) || empty($datosRepresentante['nombre'])) {
            header("Location: index.php?msg=error");
            exit();
        }

        // Llamar a la función de actualización transaccional
        $actualizacionExitosa = actualizarEstudiante($conexion, $id_estudiante, $datosRepresentante, $datosEstudiante);

        if ($actualizacionExitosa) {
            header("Location: index.php?msg=actualizado_exito");
        } else {
            header("Location: index.php?msg=error");
        }
        exit();
    }
}

// =========================================================================
// BLOQUE B: PROCESAR PETICIONES GET (Acciones por URL o Botones directos)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion'])) {
    
    $accion = $_GET['accion'];

    // --- ACCIÓN 3: DESINCORPORACIÓN / ELIMINACIÓN LÓGICA ---
    if ($accion === 'eliminar' && isset($_GET['id'])) {
        $id_eliminar = $_GET['id'];

        if (!empty($id_eliminar)) {
            // Ejecutar la actualización de estatus en gestion.php
            $borradoExitoso = eliminarEstudiante($conexion, $id_eliminar);

            if ($borradoExitoso) {
                header("Location: index.php?msg=eliminado_exito");
            } else {
                header("Location: index.php?msg=error");
            }
            exit();
        }
    }
}

// =========================================================================
// MEDIDA DE SEGURIDAD: Si intentan entrar directo a este archivo por URL sin acciones
// =========================================================================
header("Location: index.php");
exit();