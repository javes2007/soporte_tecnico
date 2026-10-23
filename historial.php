<?php
require "conexion.php";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensaje = $_GET['msg'] ?? '';
if ($id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM historial_reportes WHERE reporte_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $id);
} else {
    $stmt = $conexion->prepare("SELECT * FROM historial_reportes ORDER BY id DESC LIMIT 500");
}
$stmt->execute();
$resultado = $stmt->get_result();
$eventos = array();
while ($fila = $resultado->fetch_assoc()) $eventos[] = $fila;
$stmt->close();
$actual = null;
if ($id > 0) {
    $stmtActual = $conexion->prepare("SELECT id FROM reportes WHERE id = ?");
    $stmtActual->bind_param("i", $id);
    $stmtActual->execute();
    $actual = $stmtActual->get_result()->fetch_assoc();
    $stmtActual->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Historial · TechFix</title><link rel="stylesheet" href="estilos.css"></head>
<body>
<header class="barra">🛠 TechFix · Soporte técnico <a class="barra-inicio" href="index.php">⌂ Inicio</a></header>
<main class="contenedor">
<?php if ($mensaje !== ''): ?><p class="aviso"><?php echo e($mensaje); ?></p><?php endif; ?>
<div class="titulo-fila"><div><p class="sobretitulo">TRAZABILIDAD</p><h1><?php echo $id > 0 ? 'Historial del reporte #'.(int)$id : 'Historial de servicios'; ?></h1></div><a class="btn-secundario" href="index.php">← Volver al panel</a></div>
<p class="texto-ayuda">Aquí se conservan las altas, las modificaciones, los cambios de estado y los reportes retirados del listado activo.</p>
<?php if ($id > 0 && $actual): ?><p><a class="btn-chico" href="editar.php?id=<?php echo $id; ?>">Editar reporte actual</a></p><?php endif; ?>
<?php if (count($eventos) === 0): ?>
<div class="vacio"><h3>No hay movimientos registrados</h3><p>Los nuevos registros y cambios aparecerán aquí automáticamente.</p><a class="btn" href="index.php">Volver a reportes</a></div>
<?php else: ?>
<div class="tabla-scroll"><table><thead><tr><th>Fecha y hora</th><th>Reporte</th><th>Movimiento</th><th>Cliente</th><th>Teléfono</th><th>Equipo</th><th>Estado registrado</th><th>Detalle</th></tr></thead><tbody>
<?php foreach ($eventos as $evento) { ?>
<tr><td><?php echo e(date("d/m/Y H:i:s", strtotime($evento['fecha_evento']))); ?></td><td>#<?php echo (int)$evento['reporte_id']; ?></td><td><span class="etiqueta <?php echo $evento['accion'] === 'Eliminado' ? 'prio-alta' : ($evento['accion'] === 'Cambio de estado' ? 'reparacion' : 'resuelto'); ?>"><?php echo e($evento['accion']); ?></span></td><td><?php echo e($evento['cliente']); ?></td><td><?php echo e($evento['telefono']); ?></td><td><?php echo e($evento['equipo']); ?></td><td><span class="etiqueta <?php echo clase_estado($evento['estado']); ?>"><?php echo $evento['estado'] === 'Resuelto' ? 'Reparado' : e($evento['estado']); ?></span></td><td class="detalle-celda"><?php echo e($evento['detalle']); ?></td></tr>
<?php } ?>
</tbody></table></div>
<?php if ($id === 0): ?><p class="texto-ayuda">Por rendimiento, esta vista muestra los últimos 500 movimientos. Para ver el historial completo de un equipo, pulsa “Historial” junto a su reporte.</p><?php endif; ?>
<?php endif; ?>
</main><footer class="pie">TechFix · Historial de movimientos</footer></body></html>
