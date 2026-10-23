<?php
require "conexion.php";

$error = "";
$cliente = ""; $telefono = ""; $equipo = "Computador"; $problema = "";
$prioridad = "Media"; $estado = "Pendiente";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente = trim($_POST['cliente'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $equipo = $_POST['equipo'] ?? '';
    $problema = trim($_POST['problema'] ?? '');
    $prioridad = $_POST['prioridad'] ?? '';
    $estado = $_POST['estado'] ?? '';

    if ($cliente === "" || $problema === "") {
        $error = "Escribe el nombre del cliente y el problema.";
    } elseif ($telefono !== "" && !preg_match('/^[0-9 +()-]{7,20}$/', $telefono)) {
        $error = "El teléfono solo puede tener números y símbolos telefónicos (7 a 20 caracteres).";
    } elseif (!in_array($equipo, $EQUIPOS, true) || !in_array($prioridad, $PRIORIDADES, true) || !in_array($estado, $ESTADOS, true)) {
        $error = "Hay un valor no válido en el formulario.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO reportes (cliente, telefono, equipo, problema, prioridad, estado) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $error = "No se pudo preparar el reporte. Intenta de nuevo.";
        } else {
            $stmt->bind_param("ssssss", $cliente, $telefono, $equipo, $problema, $prioridad, $estado);
            if ($stmt->execute()) {
                $idNuevo = (int)$conexion->insert_id;
                $datos = array('cliente'=>$cliente, 'telefono'=>$telefono, 'equipo'=>$equipo, 'problema'=>$problema, 'prioridad'=>$prioridad, 'estado'=>$estado);
                registrar_historial($conexion, $idNuevo, "Creado", $datos, "Se registró el equipo en el sistema.");
                $stmt->close();
                header("Location: index.php?msg=" . urlencode("Reporte #".$idNuevo." guardado con éxito"));
                exit;
            }
            $error = "No se pudo guardar el reporte.";
            error_log("Error al guardar reporte: " . $stmt->error);
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuevo reporte · TechFix</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header class="barra">🛠 TechFix · Soporte técnico <a class="barra-inicio" href="index.php">← Inicio</a></header>
<main class="contenedor">
  <div class="titulo-fila"><h1>Nuevo reporte</h1><a class="btn-secundario" href="index.php">Volver a reportes</a></div>
  <p class="texto-ayuda">Registra los datos del cliente y del equipo que ingresa al servicio técnico.</p>
  <?php if ($error !== ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>

  <form method="POST" action="crear.php" class="formulario">
    <label>Nombre del cliente
      <input type="text" name="cliente" maxlength="100" required value="<?php echo e($cliente); ?>">
    </label>
    <label>Teléfono de contacto
      <input type="text" name="telefono" maxlength="20" inputmode="tel" placeholder="Ej. 3001234567" value="<?php echo e($telefono); ?>">
    </label>
    <label>Equipo
      <select name="equipo" required>
        <?php foreach ($EQUIPOS as $op) { ?>
          <option value="<?php echo e($op); ?>" <?php if ($equipo == $op) echo "selected"; ?>><?php echo e($op); ?></option>
        <?php } ?>
      </select>
    </label>
    <label>Problema reportado
      <textarea name="problema" rows="4" maxlength="5000" required><?php echo e($problema); ?></textarea>
    </label>
    <label>Prioridad
      <select name="prioridad" required>
        <?php foreach ($PRIORIDADES as $op) { ?>
          <option value="<?php echo e($op); ?>" <?php if ($prioridad == $op) echo "selected"; ?>><?php echo e($op); ?></option>
        <?php } ?>
      </select>
    </label>
    <label>Estado actual
      <select name="estado" required>
        <?php foreach ($ESTADOS as $op) { ?>
          <option value="<?php echo e($op); ?>" <?php if ($estado == $op) echo "selected"; ?>><?php echo e($op); ?></option>
        <?php } ?>
      </select>
    </label>
    <div class="fila-botones">
      <button type="submit">Guardar reporte</button>
      <a class="btn-secundario" href="index.php">Cancelar y volver al inicio</a>
    </div>
  </form>
</main>
<footer class="pie">TechFix · Seguimiento de equipos</footer>
</body>
</html>
