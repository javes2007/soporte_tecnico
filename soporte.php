<?php
require "conexion.php";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    header("Location: index.php?msg=" . urlencode("Selecciona un reporte válido."));
    exit;
}
$stmt = $conexion->prepare("SELECT * FROM reportes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$reporte = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$reporte) {
    header("Location: index.php?msg=" . urlencode("No se encontró el reporte."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orden de soporte #<?php echo (int)$reporte['id']; ?> · TechFix</title>
<link rel="stylesheet" href="estilos.css">
<style>
.orden{max-width:820px;margin:24px auto;background:#fff;border:1px solid var(--borde);border-radius:10px;padding:28px}
.cabecera-orden{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;border-bottom:2px solid var(--acento);padding-bottom:16px;margin-bottom:20px}
.campos{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.campo{padding:12px;border:1px solid var(--borde);border-radius:6px;overflow-wrap:anywhere}
.campo strong{display:block;color:#61717f;font-size:.8rem;text-transform:uppercase;margin-bottom:4px}
.ancho{grid-column:1/-1;white-space:pre-wrap;min-height:70px}
@media(max-width:600px){.orden{padding:16px;margin:16px}.campos{grid-template-columns:1fr}.ancho{grid-column:auto}}
@media print{.barra,.pie,.no-imprimir{display:none!important}body{background:#fff}.contenedor{max-width:none;margin:0;padding:0}.orden{max-width:none;margin:0;padding:12px;border:0}}
</style>
</head>
<body>
<header class="barra">TechFix · Orden de soporte <a class="barra-inicio" href="index.php">Volver al panel</a></header>
<main class="contenedor">
<p class="texto-ayuda no-imprimir">Para imprimir o guardar esta orden como PDF, abre el menú del navegador y elige Imprimir.</p>
<section class="orden">
<div class="cabecera-orden">
<div><p class="sobretitulo">COMPROBANTE DE SERVICIO TÉCNICO</p><h1>Orden de soporte #<?php echo (int)$reporte['id']; ?></h1><p>TechFix · Seguimiento de equipos</p></div>
<div><strong>Fecha de ingreso</strong><br><?php echo e(date("d/m/Y H:i", strtotime($reporte['fecha']))); ?><br><br><strong>Estado</strong><br><span class="etiqueta <?php echo clase_estado($reporte['estado']); ?>"><?php echo $reporte['estado'] === 'Resuelto' ? 'Reparado' : e($reporte['estado']); ?></span></div>
</div>
<div class="campos">
<div class="campo"><strong>Cliente</strong><?php echo e($reporte['cliente']); ?></div>
<div class="campo"><strong>Teléfono</strong><?php echo e($reporte['telefono'] !== '' ? $reporte['telefono'] : 'No registrado'); ?></div>
<div class="campo"><strong>Equipo</strong><?php echo e($reporte['equipo']); ?></div>
<div class="campo"><strong>Prioridad</strong><?php echo e($reporte['prioridad']); ?></div>
<div class="campo ancho"><strong>Problema reportado</strong><?php echo e($reporte['problema']); ?></div>
<div class="campo ancho"><strong>Estado del servicio</strong><?php echo $reporte['estado'] === 'Resuelto' ? 'Reparado / resuelto' : e($reporte['estado']); ?></div>
<div class="campo ancho"><strong>Observaciones del técnico</strong><br><br>_________________________________________________________<br><br>_________________________________________________________</div>
<div class="campo"><strong>Firma del técnico</strong><br><br>____________________________</div>
<div class="campo"><strong>Firma de recepción</strong><br><br>____________________________</div>
</div>
</section>
</main>
<footer class="pie">TechFix · Orden de soporte #<?php echo (int)$reporte['id']; ?></footer>
</body>
</html>
