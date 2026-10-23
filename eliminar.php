<?php
require "conexion.php";

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

$res = $conexion->query("SELECT * FROM reportes WHERE id = $id");
if (!$res) {
    die("Error en la consulta: " . $conexion->error);
}
$fila = $res->fetch_assoc();
if (!$fila) {
    die("El reporte no existe. <a href='index.php'>Volver</a>");
}

// Si confirmó (botón enviado por POST) se borra
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conexion->prepare("DELETE FROM reportes WHERE id = ?");
    if (!$stmt) {
        die("Error al preparar: " . $conexion->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: index.php?msg=" . urlencode("Reporte eliminado"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Eliminar reporte</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header class="barra">🛠 TechFix · Soporte técnico</header>
<main class="contenedor">
  <h1>Eliminar reporte</h1>
  <p>¿Seguro que quieres eliminar el reporte #<?php echo (int)$fila['id']; ?> de <strong><?php echo e($fila['cliente']); ?></strong> (<?php echo e($fila['equipo']); ?>)? No se puede deshacer.</p>
  <form method="POST" action="eliminar.php" class="fila-botones">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <button type="submit" class="peligro">Sí, eliminar</button>
    <a href="index.php">Cancelar</a>
  </form>
</main>
</body>
</html>
