<?php
session_start();
require_once '../config/conexion.php';

// ========== FUNCIÓN DE AUDITORÍA ==========
if (!function_exists('registrarAuditoria')) {
    function registrarAuditoria($conexion, $usuario_id, $accion, $tabla, $registro_id, $detalles = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $conexion->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalles, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ississs", $usuario_id, $accion, $tabla, $registro_id, $detalles, $ip, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// ========== FUNCIÓN PARA VERIFICAR CAMPOS COMPLETOS ==========
function verificarCamposCompletos($datos) {
    $obligatorios = [
        'nombre', 'apellido', 'fecha_nacimiento', 'genero', 'cedula_escolar',
        'rep_nombre', 'rep_cedula', 'rep_telefono', 'sala', 'seccion_id'
    ];
    foreach ($obligatorios as $campo) {
        if (empty($datos[$campo])) {
            return false;
        }
    }
    // Al menos uno de los padres
    if (empty($datos['madre_nombre']) && empty($datos['padre_nombre'])) {
        return false;
    }
    return true;
}

// ========== VERIFICAR AUTENTICACIÓN ==========
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ========== Validar campos obligatorios ==========
    $nombre = strtoupper(trim($_POST['nombre'] ?? ''));
    $apellido = strtoupper(trim($_POST['apellido'] ?? ''));
    $fecha_nac = $_POST['fecha_nacimiento'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $rep_cedula = trim($_POST['rep_cedula'] ?? '');
    $rep_nombre = trim($_POST['rep_nombre'] ?? '');
    $rep_telefono = trim($_POST['rep_telefono'] ?? '');
    $madre_cedula = trim($_POST['cedula_base'] ?? ''); 

    if (empty($nombre) || empty($apellido) || empty($fecha_nac) || empty($genero) || 
        empty($rep_cedula) || empty($rep_nombre) || empty($rep_telefono) || empty($madre_cedula)) {
        header("Location: inscripcion.php?error=campos_requeridos");
        exit();
    }

    $conexion->begin_transaction();
    try {
        // ========== Generar Cédula Escolar ==========
        $año = date('Y', strtotime($fecha_nac));
        $año2Dig = substr($año, -2);
        $cedulaLimpia = preg_replace('/\D/', '', $madre_cedula);
        $cedulaLimpia = str_pad($cedulaLimpia, 8, '0', STR_PAD_LEFT);
        $cedulaLimpia = substr($cedulaLimpia, -8);
        $orden_nacimiento = intval($_POST['orden_nacimiento'] ?? 1);
        $cedula_escolar = $orden_nacimiento . $año2Dig . $cedulaLimpia;
        if (strlen($cedula_escolar) != 11) {
            $cedula_escolar = str_pad($cedula_escolar, 11, '0', STR_PAD_RIGHT);
        }

        // ==================== BUSCAR ESTUDIANTE EXISTENTE ====================
        $stmt_check = $conexion->prepare("SELECT id, inscripcion_completa, representante_id FROM estudiantes WHERE cedula_escolar = ?");
        $stmt_check->bind_param("s", $cedula_escolar);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $estudiante_existente = $result_check->fetch_assoc();
        $stmt_check->close();

        if (!$estudiante_existente) {
            $stmt_check2 = $conexion->prepare("SELECT id, inscripcion_completa, representante_id FROM estudiantes WHERE nombre = ? AND apellido = ? AND fecha_nacimiento = ?");
            $stmt_check2->bind_param("sss", $nombre, $apellido, $fecha_nac);
            $stmt_check2->execute();
            $result_check2 = $stmt_check2->get_result();
            $estudiante_existente = $result_check2->fetch_assoc();
            $stmt_check2->close();
        }

        if ($estudiante_existente) {
            if ($estudiante_existente['inscripcion_completa'] == 1) {
                $mensaje = urlencode("El estudiante ya se encuentra inscrito (inscripción completa). No se puede volver a inscribir.");
                header("Location: inscripcion.php?error=duplicado&mensaje=$mensaje");
                exit();
            }
            $estudiante_id = $estudiante_existente['id'];
            $representante_existente_id = $estudiante_existente['representante_id'];
            $es_nuevo = false;
        } else {
            $estudiante_id = 0;
            $representante_existente_id = 0;
            $es_nuevo = true;
        }

        // ==================== 1. REPRESENTANTE ====================
        $rep_nombre = trim($_POST['rep_nombre']);
        $rep_cedula = trim($_POST['rep_cedula']);
        $rep_telefono = trim($_POST['rep_telefono']);
        $rep_fecha_nac = !empty($_POST['rep_fecha_nacimiento']) ? $_POST['rep_fecha_nacimiento'] : null;
        $rep_estado_civil = $_POST['rep_estado_civil'] ?? '';
        $rep_afinidad = $_POST['rep_afinidad'] ?? '';
        $rep_sexo = $_POST['rep_sexo'] ?? '';
        $rep_pais_nac = $_POST['rep_pais_nacimiento'] ?? 'Venezuela';
        $rep_estado_nac = $_POST['rep_estado_nacimiento'] ?? '';
        $rep_nacionalidad = $_POST['rep_nacionalidad'] ?? 'Venezolana';
        $rep_direccion = $_POST['rep_direccion'] ?? '';
        $rep_estado_res = $_POST['rep_estado_residencia'] ?? '';
        $rep_municipio = $_POST['rep_municipio'] ?? '';
        $rep_parroquia = $_POST['rep_parroquia'] ?? '';
        $rep_ciudad = $_POST['rep_ciudad'] ?? '';

        if ($representante_existente_id > 0) {
            $stmt_rep = $conexion->prepare("UPDATE representantes SET 
                nombre_completo = ?, cedula = ?, telefono = ?, fecha_nacimiento = ?, 
                estado_civil = ?, afinidad = ?, sexo = ?, pais_nacimiento = ?, 
                estado_nacimiento = ?, nacionalidad = ?, direccion = ?, 
                estado_residencia = ?, municipio = ?, parroquia = ?, ciudad = ? 
                WHERE id = ?");
            $stmt_rep->bind_param("sssssssssssssssi", 
                $rep_nombre, $rep_cedula, $rep_telefono, $rep_fecha_nac, 
                $rep_estado_civil, $rep_afinidad, $rep_sexo, $rep_pais_nac, 
                $rep_estado_nac, $rep_nacionalidad, $rep_direccion, 
                $rep_estado_res, $rep_municipio, $rep_parroquia, $rep_ciudad, 
                $representante_existente_id);
            $stmt_rep->execute();
            $stmt_rep->close();
            $representante_id = $representante_existente_id;
        } else {
            $stmt_rep = $conexion->prepare("INSERT INTO representantes 
                (nombre_completo, cedula, telefono, fecha_nacimiento, estado_civil, afinidad, sexo, 
                 pais_nacimiento, estado_nacimiento, nacionalidad, direccion, estado_residencia, 
                 municipio, parroquia, ciudad, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt_rep->bind_param("sssssssssssssss", 
                $rep_nombre, $rep_cedula, $rep_telefono, $rep_fecha_nac, $rep_estado_civil, 
                $rep_afinidad, $rep_sexo, $rep_pais_nac, $rep_estado_nac, $rep_nacionalidad, 
                $rep_direccion, $rep_estado_res, $rep_municipio, $rep_parroquia, $rep_ciudad);
            $stmt_rep->execute();
            $representante_id = $conexion->insert_id;
            $stmt_rep->close();
        }

        // ==================== 2. VALIDAR PADRES (AL MENOS UNO) ====================
        $madre_nombre = trim($_POST['madre_nombre'] ?? '');
        $padre_nombre = trim($_POST['padre_nombre'] ?? '');
        if (empty($madre_nombre) && empty($padre_nombre)) {
            header("Location: inscripcion.php?error=padres_requeridos");
            exit();
        }

        // ==================== 3. ESTUDIANTE ====================
        $nacionalidad = $_POST['nacionalidad'] ?? 'Venezolana';
        $pais_nac = $_POST['pais_nacimiento'] ?? 'Venezuela';
        $estado_nac = $_POST['estado_nacimiento'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $estado_res = $_POST['estado_residencia'] ?? '';
        $municipio = $_POST['municipio'] ?? '';
        $parroquia = $_POST['parroquia'] ?? '';
        $ciudad = $_POST['ciudad'] ?? '';
        $enfermedad = $_POST['enfermedad'] ?? 'No';
        $enfermedad_cual = $_POST['enfermedad_cual'] ?? '';
        $educacion_fisica = $_POST['educacion_fisica'] ?? 'Si';
        $educacion_fisica_porque = $_POST['educacion_fisica_porque'] ?? '';
        $alergia = $_POST['alergia'] ?? 'No';
        $alergia_cual = $_POST['alergia_cual'] ?? '';
        $madre_telefono = $_POST['madre_telefono'] ?? '';
        $padre_cedula = $_POST['padre_cedula'] ?? '';
        $padre_telefono = $_POST['padre_telefono'] ?? '';
        $sala = ''; // Se actualizará después con el año actual

        if ($es_nuevo) {
            $stmt_est = $conexion->prepare("INSERT INTO estudiantes 
                (nombre, apellido, cedula_escolar, fecha_nacimiento, genero, sala, 
                 representante_id, nacionalidad, pais_nacimiento, estado_nacimiento, direccion, 
                 estado_residencia, municipio, parroquia, ciudad, enfermedad, enfermedad_cual, 
                 educacion_fisica, educacion_fisica_porque, alergia, alergia_cual, 
                 madre_nombre, madre_cedula, madre_telefono, padre_nombre, padre_cedula, padre_telefono, 
                 orden_nacimiento, estatus, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activo', NOW())");
            $types = str_repeat('s', 6) . 'i' . str_repeat('s', 20) . 'i';
            $stmt_est->bind_param($types, 
                $nombre, $apellido, $cedula_escolar, $fecha_nac, $genero, $sala, $representante_id,
                $nacionalidad, $pais_nac, $estado_nac, $direccion, $estado_res, $municipio, $parroquia, $ciudad,
                $enfermedad, $enfermedad_cual, $educacion_fisica, $educacion_fisica_porque,
                $alergia, $alergia_cual, $madre_nombre, $madre_cedula, $madre_telefono,
                $padre_nombre, $padre_cedula, $padre_telefono, $orden_nacimiento);
            $stmt_est->execute();
            $estudiante_id = $conexion->insert_id;
            $stmt_est->close();
        } else {
            $stmt_est = $conexion->prepare("UPDATE estudiantes SET 
                nombre = ?, apellido = ?, cedula_escolar = ?, fecha_nacimiento = ?, genero = ?, 
                representante_id = ?, nacionalidad = ?, pais_nacimiento = ?, estado_nacimiento = ?, 
                direccion = ?, estado_residencia = ?, municipio = ?, parroquia = ?, ciudad = ?, 
                enfermedad = ?, enfermedad_cual = ?, educacion_fisica = ?, educacion_fisica_porque = ?, 
                alergia = ?, alergia_cual = ?, madre_nombre = ?, madre_cedula = ?, madre_telefono = ?, 
                padre_nombre = ?, padre_cedula = ?, padre_telefono = ?, orden_nacimiento = ? 
                WHERE id = ?");
            $stmt_est->bind_param("ssssssssssssssssssssssssssi", 
                $nombre, $apellido, $cedula_escolar, $fecha_nac, $genero, 
                $representante_id, $nacionalidad, $pais_nac, $estado_nac, 
                $direccion, $estado_res, $municipio, $parroquia, $ciudad, 
                $enfermedad, $enfermedad_cual, $educacion_fisica, $educacion_fisica_porque, 
                $alergia, $alergia_cual, $madre_nombre, $madre_cedula, $madre_telefono, 
                $padre_nombre, $padre_cedula, $padre_telefono, $orden_nacimiento, 
                $estudiante_id);
            $stmt_est->execute();
            $stmt_est->close();
        }

        // ==================== 4. INSCRIPCIONES ====================
        $ano_escolar_arr = $_POST['ano_escolar'] ?? [];
        $grado_seccion_arr = $_POST['grado_seccion'] ?? [];
        $registro_arr = $_POST['registro'] ?? [];
        $repite_arr = $_POST['repite'] ?? [];
        $c_arr = $_POST['c'] ?? [];
        $f_arr = $_POST['f'] ?? [];
        $p_arr = $_POST['p'] ?? [];
        $peso_arr = $_POST['peso'] ?? [];
        $talla_arr = $_POST['talla'] ?? [];
        $fecha_inscripcion_arr = $_POST['fecha_inscripcion'] ?? [];

        $funcionario = $_SESSION['nombre_profesor'] ?? $_SESSION['usuario'] ?? 'Sistema';

        if ($estudiante_id > 0) {
            $stmt_del_ins = $conexion->prepare("DELETE FROM inscripciones WHERE estudiante_id = ?");
            $stmt_del_ins->bind_param("i", $estudiante_id);
            $stmt_del_ins->execute();
            $stmt_del_ins->close();
        }

        $stmt_ins = $conexion->prepare("INSERT INTO inscripciones 
            (estudiante_id, ano_escolar, grado_seccion, registro, repite, c, f, p, peso, talla, 
             firma_representante, fecha_inscripcion, funcionario) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?)");

        $sala_actual = '';
        $seccion_id_actual = 0;
        for ($i = 0; $i < count($ano_escolar_arr); $i++) {
            $ano = $ano_escolar_arr[$i] ?? '';
            $grado = $grado_seccion_arr[$i] ?? '';
            $registro = $registro_arr[$i] ?? '';
            $repite = $repite_arr[$i] ?? 'No';
            $c = $c_arr[$i] ?? '';
            $f = $f_arr[$i] ?? '';
            $p = $p_arr[$i] ?? '';
            $peso = !empty($peso_arr[$i]) ? floatval($peso_arr[$i]) : null;
            $talla = !empty($talla_arr[$i]) ? floatval($talla_arr[$i]) : null;
            $fecha_ins = !empty($fecha_inscripcion_arr[$i]) ? $fecha_inscripcion_arr[$i] : date('Y-m-d');

            // Variables limpias para bind_param
            $ano_esc = $ano;
            $grado_sec = $grado;
            $reg = $registro;
            $rep = $repite;
            $c_val = $c;
            $f_val = $f;
            $p_val = $p;
            $peso_val = $peso;
            $talla_val = $talla;
            $fecha_ins_val = $fecha_ins;

            $stmt_ins->bind_param("isssssssddss", 
                $estudiante_id, $ano_esc, $grado_sec, $reg, $rep,
                $c_val, $f_val, $p_val, $peso_val, $talla_val,
                $fecha_ins_val, $funcionario);
            $stmt_ins->execute();

            // Guardar el último grado para actualizar la sala
            if ($i == count($ano_escolar_arr) - 1) {
                $ultimo_grado = $grado;
                // Extraer sala y sección
                if (!empty($ultimo_grado)) {
                    $partes = explode(' - ', $ultimo_grado);
                    $sala_actual = $partes[0] ?? '';
                    $seccion_letra = $partes[1] ?? '';
                    if (!empty($sala_actual) && !empty($seccion_letra)) {
                        $stmt_sec = $conexion->prepare("SELECT id FROM secciones WHERE sala = ? AND nombre = ?");
                        $stmt_sec->bind_param("ss", $sala_actual, $seccion_letra);
                        $stmt_sec->execute();
                        $row_sec = $stmt_sec->get_result()->fetch_assoc();
                        $seccion_id_actual = $row_sec['id'] ?? 0;
                        $stmt_sec->close();
                    }
                }
            }
        }
        $stmt_ins->close();

        // ==================== 5. ACTUALIZAR SALA Y SECCIÓN DEL ESTUDIANTE ====================
        if (!empty($sala_actual)) {
            $stmt_upd = $conexion->prepare("UPDATE estudiantes SET sala = ?, seccion_id = ? WHERE id = ?");
            $stmt_upd->bind_param("sii", $sala_actual, $seccion_id_actual, $estudiante_id);
            $stmt_upd->execute();
            $stmt_upd->close();
        }

        // ==================== 6. ELIMINAR DE INGRESOS (si existe) ====================
        $stmt_check_ingreso = $conexion->prepare("SELECT id FROM ingresos WHERE id = ?");
        $stmt_check_ingreso->bind_param("i", $estudiante_id);
        $stmt_check_ingreso->execute();
        $existe_ingreso = $stmt_check_ingreso->get_result()->fetch_assoc();
        $stmt_check_ingreso->close();

        if ($existe_ingreso) {
            $stmt_delete_ingreso = $conexion->prepare("DELETE FROM ingresos WHERE id = ?");
            $stmt_delete_ingreso->bind_param("i", $estudiante_id);
            $stmt_delete_ingreso->execute();
            $stmt_delete_ingreso->close();
        }

        // ==================== 7. CALCULAR INSCRIPCIÓN COMPLETA DINÁMICAMENTE ====================
        $datos_estudiante = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'fecha_nacimiento' => $fecha_nac,
            'genero' => $genero,
            'cedula_escolar' => $cedula_escolar,
            'rep_nombre' => $rep_nombre,
            'rep_cedula' => $rep_cedula,
            'rep_telefono' => $rep_telefono,
            'sala' => $sala_actual,
            'seccion_id' => $seccion_id_actual,
            'madre_nombre' => $madre_nombre,
            'padre_nombre' => $padre_nombre,
        ];
        $inscripcion_completa = verificarCamposCompletos($datos_estudiante) ? 1 : 0;

        $stmt_completa = $conexion->prepare("UPDATE estudiantes SET inscripcion_completa = ? WHERE id = ?");
        $stmt_completa->bind_param("ii", $inscripcion_completa, $estudiante_id);
        $stmt_completa->execute();
        $stmt_completa->close();

        // ==================== 8. AUDITORÍA ====================
        $usuario_id = $_SESSION['usuario_id'] ?? 0;
        if ($usuario_id > 0) {
            $accion = $es_nuevo ? 'INSCRIBIR_ESTUDIANTE' : 'ACTUALIZAR_INSCRIPCION';
            $detalles = ($es_nuevo ? "Nuevo" : "Actualizado") . " estudiante: $nombre $apellido (Cédula Escolar: $cedula_escolar, Sala: $sala_actual)";
            registrarAuditoria($conexion, $usuario_id, $accion, 'estudiantes', $estudiante_id, $detalles);
        }

        $conexion->commit();
        
        header("Location: inscripcion_exito.php?id=$estudiante_id");
        exit();
        
    } catch (Exception $e) {
        $conexion->rollback();
        error_log("Error en inscripción: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
        header("Location: inscripcion.php?error=1");
        exit();
    }
} else {
    header("Location: inscripcion.php");
    exit();
}
?>