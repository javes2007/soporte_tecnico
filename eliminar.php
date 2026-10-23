<?php
require "conexion.php";

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$stmtBuscar = $conexion->prepare("SELECT * FROM reportes WHERE id = ?");
$stmtBuscar->bind_param("i", $id);
$stmtBuscar->execute();
$res = $stmtBuscar->get_result();
$fila = $res->fetch_assoc();
$stmtBuscar->close();

if (!$fila) {
    header("Location: index.php?msg=" . urlencode("El reporte no existe o ya fue eliminado"));
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se guarda una copia en el historial antes de eliminar el reporte activo.
    $datos = array('cliente'=>$fila['cliente'], 'telefono'=>$fila['telefono'], 'equipo'=>$fila['equipo'], 'problema'=>$fila['problema'], 'prioridad'=>$fila['prioridad'], 'estado'=>$fila['estado']);
    if (registrar_historial($conexion, $id, "Eliminado", $datos, "Reporte eliminado del listado activo.")) {
        $stmt = $conexion->prepare("DELETE FROM reportes WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: historial.php?msg=" . urlencode("Reporte eliminado; su registro se conservó en el historial."));
            exit;
        }
        $error = "No se pudo eliminar el reporte.";
        error_log("Error al eliminar reporte: " . $stmt->error);
        $stmt->close();
    } else {
        $error = "No se pudo guardar el historial; por seguridad, el reporte no se eliminó.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Eliminar reporte · TechFix</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header class="barra">🛠 TechFix · Soporte técnico <a class="barra-inicio" href="index.php">← Inicio</a></header>
<main class="contenedor">
  <h1>Confirmar eliminación</h1>
  <?php if ($error !== ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
  <div class="panel-confirmacion">
    <p>¿Seguro que quieres retirar el reporte <strong>#<?php echo (int)$fila['id']; ?></strong> del listado activo?</p>
    <p><strong>Cliente:</strong> <?php echo e($fila['cliente']); ?> · <strong>Equipo:</strong> <?php echo e($fila['equipo']); ?></p>
    <p>La acción quedará guardada en el historial, pero el reporte ya no aparecerá en la lista principal.</p>
  </div>
  <form method="POST" action="eliminar.php" class="fila-botones">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <button type="submit" class="peligro">Sí, retirar reporte</button>
    <a class="btn-secundario" href="index.php">Cancelar y volver al inicio</a>
  </form>
</main>
<footer class="pie">TechFix · Seguimiento de equipos</footer>
</body>
</html>
