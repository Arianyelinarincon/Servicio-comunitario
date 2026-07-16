<?php
session_start();
require_once '../config/conexion.php';

// ========== FUNCIÓN DE AUDITORÍA (si no está definida) ==========
if (!function_exists('registrarAuditoria')) {
    function registrarAuditoria($conexion, $usuario_id, $accion, $tabla, $registro_id, $detalles = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $conexion->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalles, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ississ", $usuario_id, $accion, $tabla, $registro_id, $detalles, $ip, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
    }
}

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
        // ========== Verificar estudiante duplicado ==========
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

        // Verificar por Cédula Escolar
        $stmt_check = $conexion->prepare("SELECT id, nombre, apellido, estatus FROM estudiantes WHERE cedula_escolar = ?");
        $stmt_check->bind_param("s", $cedula_escolar);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $duplicado = $result_check->fetch_assoc();
            $estado = ($duplicado['estatus'] == 'Activo') ? 'activo' : 'inactivo';
            $nombre_duplicado = $duplicado['nombre'] . ' ' . $duplicado['apellido'];
            $stmt_check->close();
            
            $mensaje = urlencode("El estudiante con Cédula Escolar $cedula_escolar ya existe en el sistema. Nombre: $nombre_duplicado (Estado: $estado)");
            header("Location: inscripcion.php?error=duplicado&mensaje=$mensaje");
            exit();
        }
        $stmt_check->close();

        // Verificar por Nombre + Apellido + Fecha Nacimiento
        $stmt_check2 = $conexion->prepare("SELECT id, nombre, apellido, cedula_escolar, estatus FROM estudiantes WHERE nombre = ? AND apellido = ? AND fecha_nacimiento = ?");
        $stmt_check2->bind_param("sss", $nombre, $apellido, $fecha_nac);
        $stmt_check2->execute();
        $result_check2 = $stmt_check2->get_result();
        
        if ($result_check2->num_rows > 0) {
            $duplicado2 = $result_check2->fetch_assoc();
            $estado2 = ($duplicado2['estatus'] == 'Activo') ? 'activo' : 'inactivo';
            $stmt_check2->close();
            
            $mensaje = urlencode("Ya existe un estudiante con el mismo nombre ($nombre $apellido) y fecha de nacimiento. Cédula Escolar: " . $duplicado2['cedula_escolar'] . " (Estado: $estado2)");
            header("Location: inscripcion.php?error=duplicado&mensaje=$mensaje");
            exit();
        }
        $stmt_check2->close();

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

        $stmt = $conexion->prepare("INSERT INTO representantes 
            (nombre_completo, cedula, telefono, fecha_nacimiento, estado_civil, afinidad, sexo, 
             pais_nacimiento, estado_nacimiento, nacionalidad, direccion, estado_residencia, 
             municipio, parroquia, ciudad, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssssssssssss", 
            $rep_nombre, $rep_cedula, $rep_telefono, $rep_fecha_nac, $rep_estado_civil, 
            $rep_afinidad, $rep_sexo, $rep_pais_nac, $rep_estado_nac, $rep_nacionalidad, 
            $rep_direccion, $rep_estado_res, $rep_municipio, $rep_parroquia, $rep_ciudad);
        $stmt->execute();
        $representante_id = $conexion->insert_id;
        $stmt->close();

        // ==================== 2. ESTUDIANTE ====================
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
        $madre_nombre = $_POST['madre_nombre'] ?? '';
        $madre_telefono = $_POST['madre_telefono'] ?? '';
        $padre_nombre = $_POST['padre_nombre'] ?? '';
        $padre_cedula = $_POST['padre_cedula'] ?? '';
        $padre_telefono = $_POST['padre_telefono'] ?? '';
        $sala = ''; // Se actualizará después

        $stmt = $conexion->prepare("INSERT INTO estudiantes 
            (nombre, apellido, cedula_escolar, fecha_nacimiento, genero, sala, 
             representante_id, nacionalidad, pais_nacimiento, estado_nacimiento, direccion, 
             estado_residencia, municipio, parroquia, ciudad, enfermedad, enfermedad_cual, 
             educacion_fisica, educacion_fisica_porque, alergia, alergia_cual, 
             madre_nombre, madre_cedula, madre_telefono, padre_nombre, padre_cedula, padre_telefono, 
             orden_nacimiento, estatus, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activo', NOW())");

        $types = str_repeat('s', 6) . 'i' . str_repeat('s', 20) . 'i';
        $stmt->bind_param($types, 
            $nombre, $apellido, $cedula_escolar, $fecha_nac, $genero, $sala, $representante_id,
            $nacionalidad, $pais_nac, $estado_nac, $direccion, $estado_res, $municipio, $parroquia, $ciudad,
            $enfermedad, $enfermedad_cual, $educacion_fisica, $educacion_fisica_porque,
            $alergia, $alergia_cual, $madre_nombre, $madre_cedula, $madre_telefono,
            $padre_nombre, $padre_cedula, $padre_telefono, $orden_nacimiento);
        $stmt->execute();
        $estudiante_id = $conexion->insert_id;
        $stmt->close();

        // ==================== 3. INSCRIPCIONES ====================
        $ano_escolar_arr = $_POST['ano_escolar'] ?? [];
        $grado_seccion_arr = $_POST['grado_seccion'] ?? [];
        $registro_arr = $_POST['registro'] ?? [];
        $repite_arr = $_POST['repite'] ?? [];
        $c_arr = $_POST['c'] ?? [];
        $f_arr = $_POST['f'] ?? [];
        $p_arr = $_POST['p'] ?? [];
        $peso_arr = $_POST['peso'] ?? [];
        $talla_arr = $_POST['talla'] ?? [];

        $fecha_inscripcion = date('Y-m-d');
        $funcionario = $_SESSION['nombre_profesor'] ?? $_SESSION['usuario'];

        $stmt_ins = $conexion->prepare("INSERT INTO inscripciones 
            (estudiante_id, ano_escolar, grado_seccion, registro, repite, c, f, p, peso, talla, 
             firma_representante, fecha_inscripcion, funcionario) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?)");

        for ($i = 0; $i < count($ano_escolar_arr); $i++) {
            $ano = $ano_escolar_arr[$i];
            $grado = $grado_seccion_arr[$i];
            $registro = $registro_arr[$i];
            $repite = $repite_arr[$i];
            $c = $c_arr[$i];
            $f = $f_arr[$i];
            $p = $p_arr[$i];
            $peso = !empty($peso_arr[$i]) ? floatval($peso_arr[$i]) : null;
            $talla = !empty($talla_arr[$i]) ? floatval($talla_arr[$i]) : null;

            $stmt_ins->bind_param("isssssssddss", 
                $estudiante_id, $ano, $grado, $registro, $repite, $c, $f, $p, $peso, $talla, 
                $fecha_inscripcion, $funcionario);
            $stmt_ins->execute();
        }
        $stmt_ins->close();

        // ==================== 4. ACTUALIZAR SALA ACTUAL ====================
        if (count($grado_seccion_arr) > 0) {
            $ultimo_grado = end($grado_seccion_arr);
            $sala_actual = explode(' - ', $ultimo_grado)[0];
            $stmt_upd = $conexion->prepare("UPDATE estudiantes SET sala = ? WHERE id = ?");
            $stmt_upd->bind_param("si", $sala_actual, $estudiante_id);
            $stmt_upd->execute();
            $stmt_upd->close();
        }

        // ==================== 5. AUDITORÍA ====================
        $usuario_id = $_SESSION['usuario_id'] ?? 0;
        if ($usuario_id > 0) {
            $detalles = "Nuevo estudiante: $nombre $apellido (Cédula Escolar: $cedula_escolar, Sala: $sala_actual)";
            registrarAuditoria($conexion, $usuario_id, 'INSCRIBIR_ESTUDIANTE', 'estudiantes', $estudiante_id, $detalles);
        }

        $conexion->commit();
        
        header("Location: inscripcion_exito.php?id=$estudiante_id");
        exit();
        
    } catch (Exception $e) {
        $conexion->rollback();
        error_log("Error en inscripción: " . $e->getMessage());
        header("Location: inscripcion.php?error=1");
        exit();
    }
} else {
    header("Location: inscripcion.php");
    exit();
}
?>