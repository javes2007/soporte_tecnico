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

    // No mostrar detalles internos del servidor a visitantes.
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    // Configuración local para XAMPP o Termux.
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

    die("No se pudo conectar a la base de datos: " . $conexion->connect_error .
        " -- ¿Importaste database.sql?");
}
$conexion->set_charset("utf8mb4");

// Listas de valores permitidos (se usan en los formularios y para validar)
$EQUIPOS      = array("Computador", "Portátil", "Celular", "Impresora", "Red / Router", "Otro");
$PRIORIDADES  = array("Baja", "Media", "Alta");
$ESTADOS      = array("Pendiente", "En reparación", "Resuelto");

// Para mostrar texto de forma segura en HTML
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

// Clase CSS según el estado
function clase_estado($estado) {
    if ($estado == "Resuelto") return "resuelto";
    if ($estado == "En reparación") return "reparacion";
    return "pendiente";
}
