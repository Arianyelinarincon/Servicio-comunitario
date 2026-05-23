<?php
/**
 * SISTEMA DE GESTIÓN ESCOLAR (S.G.E) - MÓDULO ESTUDIANTES
 * Archivo de lógica y consultas a la base de datos (Modelo - Versión MySQLi)
 */

/**
 * 1. OBTENER LISTADO GENERAL DE ESTUDIANTES ACTIVOS
 */
function obtenerListaEstudiantes($conexion) {
    try {
        $sql = "SELECT id, nombre, apellido, cedula, cedula_escolar, genero, sala 
                FROM estudiantes 
                WHERE estatus = 'Activo' 
                ORDER BY id DESC";
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute();
        
        // En MySQLi primero obtenemos el objeto de resultados
        $resultado = $stmt->get_result();
        
        // Retornamos todos los registros como una matriz asociativa
        return $resultado->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * 2. OBTENER DATOS DE UN ESTUDIANTE Y SU REPRESENTANTE POR ID
 */
function obtenerEstudiantePorId($conexion, $id) {
    try {
        $sql = "SELECT e.id, e.nombre, e.apellido, e.cedula_escolar, e.fecha_nacimiento, 
                       e.genero, e.sala, e.alergias_condiciones, e.representante_id,
                       r.nombre_completo AS rep_nombre, r.cedula AS rep_cedula, r.telefono AS rep_telefono
                FROM estudiantes e
                LEFT JOIN representantes r ON e.representante_id = r.id
                WHERE e.id = ? AND e.estatus = 'Activo'
                LIMIT 1";
        
        $stmt = $conexion->prepare($sql);
        // Asociamos el ID como entero ("i")
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 3. REGISTRAR NUEVO ESTUDIANTE Y SU REPRESENTANTE (CON TRANSACCIÓN)
 */
function registrarNuevoEstudiante($conexion, $rep, $est) {
    try {
        // Iniciamos la transacción en MySQLi
        $conexion->begin_transaction();

        // PASO A: Insertar Representante
        $sqlRep = "INSERT INTO representantes (nombre_completo, cedula, telefono, parentesco, created_at) 
                   VALUES (?, ?, ?, 'Representante', NOW())";
        
        $stmtRep = $conexion->prepare($sqlRep);
        // "sss" significa que los 3 parámetros son cadenas de texto (strings)
        $stmtRep->bind_param("sss", $rep['nombre'], $rep['cedula'], $rep['telefono']);
        $stmtRep->execute();

        // Capturamos el ID generado con la propiedad de MySQLi
        $representante_id = $conexion->insert_id;

        // PASO B: Insertar Estudiante
        $sqlEst = "INSERT INTO estudiantes (nombre, cedula_escolar, fecha_nacimiento, genero, sala, 
                                            alergias_condiciones, representante_id, estatus, created_at) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo', NOW())";
        
        $stmtEst = $conexion->prepare($sqlEst);
        // "ssssssi" indica 6 strings y 1 entero al final (el ID del representante)
        $stmtEst->bind_param(
            "ssssssi", 
            $est['nombre'], 
            $est['cedula_escolar'], 
            $est['fecha_nacimiento'], 
            $est['genero'], 
            $est['sala'], 
            $est['alergias'], 
            $representante_id
        );
        $stmtEst->execute();

        // Si todo marchó bien, confirmamos la operación
        $conexion->commit();
        return true;

    } catch (Exception $e) {
        // Si hay algún error, deshacemos los cambios
        $conexion->rollback();
        return false;
    }
}

/**
 * 4. ACTUALIZAR DATOS DE ESTUDIANTE Y REPRESENTANTE (CON TRANSACCIÓN)
 */
function actualizarEstudiante($conexion, $id_estudiante, $rep, $est) {
    try {
        $conexion->begin_transaction();

        // PASO A: Obtener el ID del representante asociado
        $sqlBusqueda = "SELECT representante_id FROM estudiantes WHERE id = ? LIMIT 1";
        $stmtBusqueda = $conexion->prepare($sqlBusqueda);
        $stmtBusqueda->bind_param("i", $id_estudiante);
        $stmtBusqueda->execute();
        $resultadoBusqueda = $stmtBusqueda->get_result()->fetch_assoc();

        if ($resultadoBusqueda && !empty($resultadoBusqueda['representante_id'])) {
            $id_representante = $resultadoBusqueda['representante_id'];

            // PASO B: Actualizar la tabla de representantes
            $sqlRep = "UPDATE representantes 
                       SET nombre_completo = ?, cedula = ?, telefono = ? 
                       WHERE id = ?";
            
            $stmtRep = $conexion->prepare($sqlRep);
            $stmtRep->bind_param("sssi", $rep['nombre'], $rep['cedula'], $rep['telefono'], $id_representante);
            $stmtRep->execute();
        }

        // PASO C: Actualizar la tabla de estudiantes
        $sqlEst = "UPDATE estudiantes 
                   SET nombre = ?, cedula_escolar = ?, 
                       fecha_nacimiento = ?, genero = ?, 
                       sala = ?, alergias_condiciones = ? 
                   WHERE id = ?";
        
        $stmtEst = $conexion->prepare($sqlEst);
        $stmtEst->bind_param(
            "ssssssi", 
            $est['nombre'], 
            $est['cedula_escolar'], 
            $est['fecha_nacimiento'], 
            $est['genero'], 
            $est['sala'], 
            $est['alergias'], 
            $id_estudiante
        );
        $stmtEst->execute();

        $conexion->commit();
        return true;

    } catch (Exception $e) {
        $conexion->rollback();
        return false;
    }
}

/**
 * 5. ELIMINACIÓN LÓGICA DE UN ESTUDIANTE
 */
function eliminarEstudiante($conexion, $id) {
    try {
        $sql = "UPDATE estudiantes SET estatus = 'Inactivo' WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}