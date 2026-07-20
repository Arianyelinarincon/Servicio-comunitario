<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "control_estudios_uebn";
$port = 3306;

$conexion = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/Servicio-comunitario/');
}

function registrarAuditoria($conexion, $usuario_id, $accion, $tabla, $registro_id, $detalles = null) {
    // ===== SI usuario_id es NULL o 0, intentar obtenerlo de la sesión =====
    if (empty($usuario_id) || $usuario_id == 0) {
        $usuario_id = $_SESSION['usuario_id'] ?? 0;
    }
    
    // ===== SI sigue siendo 0, intentar obtener por nombre de usuario =====
    if ($usuario_id == 0 && isset($_SESSION['usuario'])) {
        $sql = "SELECT id FROM secretaria WHERE usuario = ?";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $_SESSION['usuario']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $usuario_id = intval($row['id']);
                $_SESSION['usuario_id'] = $usuario_id;
                $_SESSION['tipo_usuario'] = 'secretaria';
            }
            $stmt->close();
        }
    }
    
    // ===== SI sigue siendo 0, buscar en profesores =====
    if ($usuario_id == 0 && isset($_SESSION['usuario'])) {
        $sql = "SELECT id FROM profesores WHERE usuario = ?";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $_SESSION['usuario']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $usuario_id = intval($row['id']);
                $_SESSION['usuario_id'] = $usuario_id;
                $_SESSION['tipo_usuario'] = 'profesor';
            }
            $stmt->close();
        }
    }
    
    // ===== SI usuario_id es NULL o 0, usar 0 =====
    if (empty($usuario_id)) {
        $usuario_id = 0;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // ========== VERIFICAR QUE EL USUARIO EXISTA ==========
    $usuario_valido = null;
    $usuario_tipo = null;
    
    if ($usuario_id > 0) {
        // Primero buscar en secretaria
        $check = $conexion->prepare("SELECT id FROM secretaria WHERE id = ?");
        if ($check) {
            $check->bind_param("i", $usuario_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $usuario_valido = $usuario_id;
                $usuario_tipo = 'secretaria';
            }
            $check->close();
        }
        
        // Si no está en secretaria, buscar en profesores
        if ($usuario_valido === null) {
            $check = $conexion->prepare("SELECT id FROM profesores WHERE id = ?");
            if ($check) {
                $check->bind_param("i", $usuario_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $usuario_valido = $usuario_id;
                    $usuario_tipo = 'profesor';
                }
                $check->close();
            }
        }
    }

    // ========== CREAR TABLA SI NO EXISTE ==========
    $check = $conexion->query("SHOW TABLES LIKE 'auditoria'");
    if ($check && $check->num_rows == 0) {
        $conexion->query("CREATE TABLE IF NOT EXISTS auditoria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            usuario_tipo ENUM('secretaria','profesor') NULL,
            accion VARCHAR(100) NOT NULL,
            tabla_afectada VARCHAR(50),
            registro_id INT,
            detalles TEXT,
            ip VARCHAR(45),
            user_agent VARCHAR(255),
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_usuario (usuario_id),
            INDEX idx_fecha (fecha),
            INDEX idx_accion (accion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // ========== INSERTAR REGISTRO ==========
    $stmt = $conexion->prepare("INSERT INTO auditoria (usuario_id, usuario_tipo, accion, tabla_afectada, registro_id, detalles, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $null_usuario = $usuario_valido ?: null;
        $stmt->bind_param("isssisss", $null_usuario, $usuario_tipo, $accion, $tabla, $registro_id, $detalles, $ip, $user_agent);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
}
// ========== FUNCIÓN PARA OBTENER EL ID DEL USUARIO ACTUAL ==========
function obtenerUsuarioId() {
    return $_SESSION['usuario_id'] ?? 0;
}

// ========== FUNCIÓN PARA SANITIZAR ==========
if (!function_exists('sanitizarEntrada')) {
    function sanitizarEntrada($dato, $tipo = 'string') {
        if (is_array($dato)) {
            return array_map(function($item) use ($tipo) {
                return sanitizarEntrada($item, $tipo);
            }, $dato);
        }
        $dato = trim($dato);
        switch ($tipo) {
            case 'int': return filter_var($dato, FILTER_VALIDATE_INT) ? (int)$dato : 0;
            case 'email': return filter_var($dato, FILTER_VALIDATE_EMAIL) ? $dato : '';
            default: return htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
        }
    }
}
?>