<?php
require "conexion.php";

// Contadores por estado (consulta con GROUP BY)
$conteo = array("Pendiente" => 0, "En reparación" => 0, "Resuelto" => 0);
$rc = $conexion->query("SELECT estado, COUNT(*) AS n FROM reportes GROUP BY estado");
if ($rc) {
    while ($f = $rc->fetch_assoc()) {
        if (isset($conteo[$f['estado']])) { $conteo[$f['estado']] = (int)$f['n']; }
    }
}

// Buscar y filtrar
$buscar = isset($_GET['q']) ? trim($_GET['q']) : "";
$estado = isset($_GET['estado']) ? $_GET['estado'] : "";
if (!in_array($estado, $ESTADOS)) { $estado = ""; }

$sql = "SELECT * FROM reportes WHERE 1=1";
if ($buscar !== "") {
    $q = $conexion->real_escape_string($buscar);
    $sql .= " AND (cliente LIKE '%$q%' OR equipo LIKE '%$q%' OR problema LIKE '%$q%')";
}
if ($estado !== "") {
    $est = $conexion->real_escape_string($estado);
    $sql .= " AND estado = '$est'";
}
$sql .= " ORDER BY id DESC";

$resultado = $conexion->query($sql);
if (!$resultado) {
    die("Error en la consulta: " . $conexion->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TechFix · Soporte técnico</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>

<header class="barra">🛠 TechFix · Soporte técnico</header>

<main class="contenedor">

  <?php if (isset($_GET['msg'])): ?>
    <p class="aviso"><?php echo e($_GET['msg']); ?></p>
  <?php endif; ?>

  <div class="resumen">
    <div class="dato pendiente"><span><?php echo $conteo["Pendiente"]; ?></span>Pendientes</div>
    <div class="dato reparacion"><span><?php echo $conteo["En reparación"]; ?></span>En reparación</div>
    <div class="dato resuelto"><span><?php echo $conteo["Resuelto"]; ?></span>Resueltos</div>
  </div>

  <div class="titulo-fila">
    <h1>Reportes de soporte</h1>
    <a class="btn" href="crear.php">+ Nuevo reporte</a>
  </div>

  <form method="GET" action="index.php" class="buscador">
    <input type="text" name="q" placeholder="Buscar por cliente, equipo o problema..." value="<?php echo e($buscar); ?>">
    <select name="estado" class="select-chico">
      <option value="">Todos los estados</option>
      <?php foreach ($ESTADOS as $es) { ?>
        <option value="<?php echo e($es); ?>" <?php if ($estado == $es) echo "selected"; ?>><?php echo e($es); ?></option>
      <?php } ?>
    </select>
    <button type="submit">Filtrar</button>
    <?php if ($buscar !== "" || $estado !== ""): ?><a href="index.php">Limpiar</a><?php endif; ?>
  </form>

  <?php if ($resultado->num_rows == 0): ?>
    <p>No hay reportes para mostrar.</p>
  <?php else: ?>
  <div class="tabla-scroll">
    <table>
      <tr>
        <th>#</th><th>Cliente</th><th>Teléfono</th><th>Equipo</th><th>Problema</th>
        <th>Prioridad</th><th>Estado</th><th>Fecha</th><th>Acciones</th>
      </tr>
      <?php while ($fila = $resultado->fetch_assoc()) { ?>
      <tr>
        <td><?php echo (int)$fila['id']; ?></td>
        <td><strong><?php echo e($fila['cliente']); ?></strong></td>
        <td><?php echo e($fila['telefono']); ?></td>
        <td><?php echo e($fila['equipo']); ?></td>
        <td><?php echo e($fila['problema']); ?></td>
        <td><span class="etiqueta prio-<?php echo strtolower(e($fila['prioridad'])); ?>"><?php echo e($fila['prioridad']); ?></span></td>
        <td><span class="etiqueta <?php echo clase_estado($fila['estado']); ?>"><?php echo e($fila['estado']); ?></span></td>
        <td><?php echo date("d/m/Y H:i", strtotime($fila['fecha'])); ?></td>
        <td class="acciones">
          <a class="btn-chico" href="editar.php?id=<?php echo (int)$fila['id']; ?>">Editar</a>
          <a class="btn-chico peligro" href="eliminar.php?id=<?php echo (int)$fila['id']; ?>">Eliminar</a>
        </td>
      </tr>
      <?php } ?>
    </table>
  </div>
  <?php endif; ?>

</main>
<footer class="pie">Proyecto de Diseño Web</footer>
</body>
</html>
