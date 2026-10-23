<?php
require "conexion.php";   // si hay error de conexión, aquí mismo sale escrito
echo "<h2>Prueba de conexión</h2>";
echo "<p>PHP versión: " . phpversion() . "</p>";
echo "<p style='color:green'>✔ Conexión correcta a la base de datos</p>";
$r = $conexion->query("SELECT COUNT(*) AS n FROM reportes");
if (!$r) {
    echo "<p style='color:red'>❌ La tabla reportes no existe: " . e($conexion->error) . "</p>";
    echo "<p>Falta importar database.sql.</p>";
    exit;
}
$f = $r->fetch_assoc();
echo "<p style='color:green'>✔ La tabla reportes tiene " . (int)$f['n'] . " registros.</p>";
echo "<p><a href='index.php'>Ir a la página principal</a></p>";
