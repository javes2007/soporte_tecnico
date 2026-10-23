<?php
// En InfinityFree se genera config-hosting.php durante el despliegue.
// En desarrollo local se usan los valores predeterminados de XAMPP/Termux.
$archivoConfiguracion = __DIR__ . '/config-hosting.php';
$esHosting = is_file($archivoConfiguracion);

if ($esHosting) {
    $config = require $archivoConfiguracion;
    $host = $config['host'] ?? '';
    $usuario = $config['user'] ?? '';
    $clave = $config['password'] ?? '';
    $bd = $config['name'] ?? '';
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    $host = "127.0.0.1";
    $usuario = "root";
    $clave = "";
    $bd = "soporte_tecnico";
}

mysqli_report(MYSQLI_REPORT_OFF);
$conexion = new mysqli($host, $usuario, $clave, $bd);

if ($conexion->connect_errno) {
    if ($esHosting) {
        error_log("Error de conexión MySQL en soporte_tecnico: " . $conexion->connect_error);
        http_response_code(500);
        die("No se pudo conectar a la base de datos. Revisa la configuración de la base de datos del sitio.");
    }
    die("No se pudo conectar a la base de datos: " . $conexion->connect_error . " -- ¿Importaste database.sql?");
}
$conexion->set_charset("utf8mb4");

// Crear la tabla de historial si la instalación ya existente todavía no la tiene.
// No borra ni modifica los reportes actuales.
$sqlHistorial = "CREATE TABLE IF NOT EXISTS historial_reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporte_id INT NOT NULL,
    accion VARCHAR(30) NOT NULL,
    cliente VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NULL,
    equipo VARCHAR(30) NOT NULL,
    problema TEXT NOT NULL,
    prioridad VARCHAR(10) NOT NULL,
    estado VARCHAR(20) NOT NULL,
    detalle TEXT NULL,
    fecha_evento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_historial_reporte (reporte_id),
    INDEX idx_historial_fecha (fecha_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$conexion->query($sqlHistorial)) {
    error_log("No se pudo preparar historial_reportes: " . $conexion->error);
}

/* Crea una entrada inicial para los reportes que ya existían antes de activar el historial.
   Solo se ejecuta para reportes que todavía no tengan ningún evento guardado. */
$sqlInicial = "INSERT INTO historial_reportes
    (reporte_id, accion, cliente, telefono, equipo, problema, prioridad, estado, detalle)
    SELECT r.id, 'Historial inicial', r.cliente, r.telefono, r.equipo, r.problema,
           r.prioridad, r.estado, 'Datos existentes al activar el historial.'
    FROM reportes r
    WHERE NOT EXISTS (
        SELECT 1 FROM historial_reportes h WHERE h.reporte_id = r.id
    )";
if (!$conexion->query($sqlInicial)) {
    error_log("No se pudo crear el historial inicial: " . $conexion->error);
}

$EQUIPOS = array("Computador", "Portátil", "Celular", "Impresora", "Red / Router", "Otro");
$PRIORIDADES = array("Baja", "Media", "Alta");
$ESTADOS = array("Pendiente", "En reparación", "Resuelto");

function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

function clase_estado($estado) {
    if ($estado == "Resuelto") return "resuelto";
    if ($estado == "En reparación") return "reparacion";
    return "pendiente";
}

// Guarda una fotografía de los datos del reporte en cada evento importante.
function registrar_historial($conexion, $reporteId, $accion, $datos, $detalle = "") {
    $stmt = $conexion->prepare("INSERT INTO historial_reportes
        (reporte_id, accion, cliente, telefono, equipo, problema, prioridad, estado, detalle)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("No se pudo preparar el historial: " . $conexion->error);
        return false;
    }

    $cliente = (string)($datos['cliente'] ?? 'Sin nombre');
    $telefono = (string)($datos['telefono'] ?? '');
    $equipo = (string)($datos['equipo'] ?? 'Otro');
    $problema = (string)($datos['problema'] ?? '');
    $prioridad = (string)($datos['prioridad'] ?? 'Media');
    $estado = (string)($datos['estado'] ?? 'Pendiente');
    $stmt->bind_param("issssssss", $reporteId, $accion, $cliente, $telefono, $equipo, $problema, $prioridad, $estado, $detalle);
    $ok = $stmt->execute();
    if (!$ok) {
        error_log("No se pudo guardar el historial: " . $stmt->error);
    }
    $stmt->close();
    return $ok;
}
