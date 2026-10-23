<?php
require "conexion.php";

$error = "";
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// Buscar el reporte actual
$res = $conexion->query("SELECT * FROM reportes WHERE id = $id");
if (!$res) {
    die("Error en la consulta: " . $conexion->error);
}
$actual = $res->fetch_assoc();
if (!$actual) {
    die("El reporte no existe. <a href='index.php'>Volver</a>");
}

$cliente   = $actual['cliente'];
$telefono  = $actual['telefono'];
$equipo    = $actual['equipo'];
$problema  = $actual['problema'];
$prioridad = $actual['prioridad'];
$estado    = $actual['estado'];

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
        $stmt = $conexion->prepare("UPDATE reportes SET cliente=?, telefono=?, equipo=?, problema=?, prioridad=?, estado=? WHERE id=?");
        if (!$stmt) {
            die("Error al preparar: " . $conexion->error);
        }
        $stmt->bind_param("ssssssi", $cliente, $telefono, $equipo, $problema, $prioridad, $estado, $id);
        if ($stmt->execute()) {
            header("Location: index.php?msg=" . urlencode("Reporte actualizado con éxito"));
            exit;
        }
        $error = "No se pudo actualizar: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editar reporte</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header class="barra">🛠 TechFix · Soporte técnico</header>
<main class="contenedor">
  <h1>Editar reporte #<?php echo $id; ?></h1>
  <?php if ($error != ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>

  <form method="POST" action="editar.php" class="formulario">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
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
      <button type="submit">Actualizar</button>
      <a href="index.php">Cancelar</a>
    </div>
  </form>
</main>
</body>
</html>
