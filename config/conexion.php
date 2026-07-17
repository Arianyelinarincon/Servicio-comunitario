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

// ========== FUNCIÓN DE AUDITORÍA (CENTRALIZADA) ==========
function registrarAuditoria($conexion, $usuario_id, $accion, $tabla, $registro_id, $detalles = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Verificar si el usuario existe en profesores (si no, usar NULL)
    if ($usuario_id > 0) {
        $check = $conexion->prepare("SELECT id FROM profesores WHERE id = ?");
        if ($check) {
            $check->bind_param("i", $usuario_id);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();
            if (!$exists) {
                $usuario_id = 0; // Se guardará como NULL en la BD
            }
        }
    }

    // Crear tabla si no existe
    $check = $conexion->query("SHOW TABLES LIKE 'auditoria'");
    if ($check && $check->num_rows == 0) {
        $conexion->query("CREATE TABLE IF NOT EXISTS auditoria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            accion VARCHAR(100) NOT NULL,
            tabla_afectada VARCHAR(50),
            registro_id INT,
            detalles TEXT,
            ip VARCHAR(45),
            user_agent VARCHAR(255),
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES profesores(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // Insertar registro
    $stmt = $conexion->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalles, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $null_usuario = $usuario_id ?: null;
        $stmt->bind_param("ississs", $null_usuario, $accion, $tabla, $registro_id, $detalles, $ip, $user_agent);
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