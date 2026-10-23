<?php
// Muestra los errores en pantalla (así sabemos qué falla)
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

$host    = "127.0.0.1";       // funciona en XAMPP y en Termux
$usuario = "root";
$clave   = "";                // sin contraseña por defecto
$bd      = "soporte_tecnico";

$conexion = new mysqli($host, $usuario, $clave, $bd);
if ($conexion->connect_errno) {
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
