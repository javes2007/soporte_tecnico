<?php
require "conexion.php";
$conteo = array("Pendiente"=>0, "En reparación"=>0, "Resuelto"=>0);
$rc = $conexion->query("SELECT estado, COUNT(*) AS n FROM reportes GROUP BY estado");
if ($rc) { while ($f = $rc->fetch_assoc()) { if (isset($conteo[$f['estado']])) $conteo[$f['estado']] = (int)$f['n']; } }
$buscar = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? '';
if (!in_array($estado, $ESTADOS, true)) $estado = '';
$sql = "SELECT * FROM reportes WHERE 1=1";
$tipos = ""; $parametros = array();
if ($buscar !== "") {
  $sql .= " AND (cliente LIKE ? OR telefono LIKE ? OR equipo LIKE ? OR problema LIKE ?)";
  $patron = "%".$buscar."%"; $tipos .= "ssss";
  $parametros[]=$patron; $parametros[]=$patron; $parametros[]=$patron; $parametros[]=$patron;
}
if ($estado !== "") { $sql .= " AND estado = ?"; $tipos .= "s"; $parametros[]=$estado; }
$sql .= " ORDER BY id DESC";
$stmt = $conexion->prepare($sql);
if ($tipos !== "") $stmt->bind_param($tipos, ...$parametros);
$stmt->execute(); $resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>TechFix · Panel de soporte</title><link rel="stylesheet" href="estilos.css"></head>
<body>
<header class="barra">🛠 TechFix · Soporte técnico <a class="barra-inicio" href="index.php">⌂ Inicio</a></header>
<main class="contenedor">
<?php if (isset($_GET['msg'])): ?><p class="aviso"><?php echo e($_GET['msg']); ?></p><?php endif; ?>
<section class="panel-bienvenida"><div><p class="sobretitulo">PANEL DE CONTROL</p><h1>Seguimiento de equipos</h1><p class="texto-ayuda">Consulta los equipos recibidos, revisa su estado y conserva el historial de cada servicio.</p></div><a class="btn" href="crear.php">＋ Nuevo reporte</a></section>
<div class="resumen">
<a class="dato pendiente" href="index.php?estado=Pendiente"><span><?php echo $conteo["Pendiente"]; ?></span>Equipos pendientes <small>Por revisar</small></a>
<a class="dato reparacion" href="index.php?estado=En%20reparaci%C3%B3n"><span><?php echo $conteo["En reparación"]; ?></span>En reparación <small>En revisión o trabajo</small></a>
<a class="dato resuelto" href="index.php?estado=Resuelto"><span><?php echo $conteo["Resuelto"]; ?></span>Equipos reparados <small>Trabajo terminado</small></a>
</div>
<div class="titulo-fila"><h2>Reportes registrados</h2><a class="btn-secundario" href="historial.php">Ver historial completo</a></div>
<form method="GET" action="index.php" class="buscador">
<input type="search" name="q" placeholder="Buscar cliente, teléfono, equipo o problema..." value="<?php echo e($buscar); ?>">
<select name="estado" class="select-chico"><option value="">Todos los estados</option><?php foreach ($ESTADOS as $es) { ?><option value="<?php echo e($es); ?>" <?php if ($estado === $es) echo "selected"; ?>><?php echo e($es); ?></option><?php } ?></select>
<button type="submit">Buscar</button><?php if ($buscar !== "" || $estado !== ""): ?><a class="btn-secundario" href="index.php">Limpiar filtros</a><?php endif; ?>
</form>
<?php if ($resultado->num_rows === 0): ?>
<div class="vacio"><h3>No se encontraron reportes</h3><p>Prueba con otro teléfono, nombre o estado, o registra un nuevo equipo.</p><a class="btn" href="crear.php">＋ Nuevo reporte</a></div>
<?php else: ?>
<div class="tabla-scroll"><table><thead><tr><th>#</th><th>Cliente</th><th>Teléfono</th><th>Equipo</th><th>Problema</th><th>Prioridad</th><th>Estado</th><th>Fecha de ingreso</th><th>Acciones</th></tr></thead><tbody>
<?php while ($fila = $resultado->fetch_assoc()) { ?>
<tr><td><?php echo (int)$fila['id']; ?></td><td><strong><?php echo e($fila['cliente']); ?></strong></td><td><?php echo e($fila['telefono']); ?></td><td><?php echo e($fila['equipo']); ?></td><td class="problema-celda"><?php echo e($fila['problema']); ?></td><td><span class="etiqueta prio-<?php echo strtolower(e($fila['prioridad'])); ?>"><?php echo e($fila['prioridad']); ?></span></td><td><span class="etiqueta <?php echo clase_estado($fila['estado']); ?>"><?php echo $fila['estado'] === 'Resuelto' ? 'Reparado' : e($fila['estado']); ?></span></td><td><?php echo e(date("d/m/Y H:i", strtotime($fila['fecha']))); ?></td><td class="acciones"><a class="btn-chico" href="soporte.php?id=<?php echo (int)$fila['id']; ?>">Generar soporte</a> <a class="btn-chico secundario" href="editar.php?id=<?php echo (int)$fila['id']; ?>">Editar</a> <a class="btn-chico secundario" href="historial.php?id=<?php echo (int)$fila['id']; ?>">Historial</a> <a class="btn-chico peligro" href="eliminar.php?id=<?php echo (int)$fila['id']; ?>">Retirar</a></td></tr>
<?php } ?>
</tbody></table></div>
<?php endif; ?>
</main><footer class="pie">TechFix · Seguimiento de equipos</footer></body></html>
