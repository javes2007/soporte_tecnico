<?php
require "conexion.php";

$error = "";
$cliente = ""; $telefono = ""; $equipo = "Computador"; $problema = "";
$prioridad = "Media"; $estado = "Pendiente";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente   = trim($_POST['cliente'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $equipo    = $_POST['equipo'] ?? '';
    $problema  = trim($_POST['problema'] ?? '');
    $prioridad = $_POST['prioridad'] ?? '';
    $estado    = $_POST['estado'] ?? '';

    if ($cliente == "" || $problema == "") {
        $error = "Escribe el nombre del cliente y el problema.";
    } elseif ($telefono != "" && !preg_match('/^[0-9 +()-]{7,20}$/', $telefono)) {
        $error = "El teléfono solo puede tener números (7 a 20 dígitos).";
    } elseif (!in_array($equipo, $EQUIPOS) || !in_array($prioridad, $PRIORIDADES) || !in_array($estado, $ESTADOS)) {
        $error = "Hay un valor no válido en el formulario.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO reportes (cliente, telefono, equipo, problema, prioridad, estado) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Error al preparar: " . $conexion->error);
        }
        $stmt->bind_param("ssssss", $cliente, $telefono, $equipo, $problema, $prioridad, $estado);
        if ($stmt->execute()) {
            header("Location: index.php?msg=" . urlencode("Reporte guardado con éxito"));
            exit;
        }
        $error = "No se pudo guardar: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuevo reporte</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header class="barra">🛠 TechFix · Soporte técnico</header>
<main class="contenedor">
  <h1>Nuevo reporte</h1>
  <?php if ($error != ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>

  <form method="POST" action="crear.php" class="formulario">
    <label>Nombre del cliente
      <input type="text" name="cliente" maxlength="100" required value="<?php echo e($cliente); ?>">
    </label>
    <label>Teléfono (opcional)
      <input type="text" name="telefono" maxlength="20" value="<?php echo e($telefono); ?>">
    </label>
    <label>Equipo
      <select name="equipo">
        <?php foreach ($EQUIPOS as $op) { ?>
          <option value="<?php echo e($op); ?>" <?php if ($equipo == $op) echo "selected"; ?>><?php echo e($op); ?></option>
        <?php } ?>
      </select>
    </label>
    <label>Problema
      <textarea name="problema" rows="4" required><?php echo e($problema); ?></textarea>
    </label>
    <label>Prioridad
      <select name="prioridad">
        <?php foreach ($PRIORIDADES as $op) { ?>
          <option value="<?php echo e($op); ?>" <?php if ($prioridad == $op) echo "selected"; ?>><?php echo e($op); ?></option>
        <?php } ?>
      </select>
    </label>
    <label>Estado
      <select name="estado">
        <?php foreach ($ESTADOS as $op) { ?>
          <option value="<?php echo e($op); ?>" <?php if ($estado == $op) echo "selected"; ?>><?php echo e($op); ?></option>
        <?php } ?>
      </select>
    </label>
    <div class="fila-botones">
      <button type="submit">Guardar</button>
      <a href="index.php">Cancelar</a>
    </div>
  </form>
</main>
</body>
</html>
