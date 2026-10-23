<?php
require "conexion.php";

$error = "";
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if ($id < 1) {
    header("Location: index.php?msg=" . urlencode("Selecciona un reporte válido"));
    exit;
}

$stmtActual = $conexion->prepare("SELECT * FROM reportes WHERE id = ?");
$stmtActual->bind_param("i", $id);
$stmtActual->execute();
$res = $stmtActual->get_result();
$actual = $res->fetch_assoc();
$stmtActual->close();
if (!$actual) {
    header("Location: index.php?msg=" . urlencode("El reporte no existe"));
    exit;
}

$cliente = $actual['cliente'];
$telefono = $actual['telefono'];
$equipo = $actual['equipo'];
$problema = $actual['problema'];
$prioridad = $actual['prioridad'];
$estado = $actual['estado'];

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
        $cambios = array();
        foreach (array('cliente'=>'cliente','telefono'=>'teléfono','equipo'=>'equipo','problema'=>'problema','prioridad'=>'prioridad','estado'=>'estado') as $campo=>$etiqueta) {
            if ((string)$actual[$campo] !== (string)($$campo)) {
                $cambios[] = $etiqueta . ': "' . $actual[$campo] . '" → "' . $$campo . '"';
            }
        }
        $stmt = $conexion->prepare("UPDATE reportes SET cliente=?, telefono=?, equipo=?, problema=?, prioridad=?, estado=? WHERE id=?");
        if (!$stmt) {
            $error = "No se pudo preparar la actualización. Intenta de nuevo.";
        } else {
            $stmt->bind_param("ssssssi", $cliente, $telefono, $equipo, $problema, $prioridad, $estado, $id);
            if ($stmt->execute()) {
                $datos = array('cliente'=>$cliente, 'telefono'=>$telefono, 'equipo'=>$equipo, 'problema'=>$problema, 'prioridad'=>$prioridad, 'estado'=>$estado);
                $accion = ($actual['estado'] !== $estado) ? "Cambio de estado" : "Actualizado";
                $detalle = count($cambios) ? implode(" | ", $cambios) : "Se guardó el reporte sin cambios en los campos.";
                registrar_historial($conexion, $id, $accion, $datos, $detalle);
                $stmt->close();
                header("Location: index.php?msg=" . urlencode("Reporte #".$id." actualizado correctamente"));
                exit;
            }
            $error = "No se pudo actualizar el reporte.";
            error_log("Error al actualizar reporte: " . $stmt->error);
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
  <title>Editar reporte · TechFix</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header class="barra">🛠 TechFix · Soporte técnico <a class="barra-inicio" href="index.php">← Inicio</a></header>
<main class="contenedor">
  <div class="titulo-fila"><h1>Editar reporte #<?php echo $id; ?></h1><a class="btn-secundario" href="historial.php?id=<?php echo $id; ?>">Ver historial</a></div>
  <?php if ($error !== ""): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>

  <form method="POST" action="editar.php" class="formulario">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <label>Nombre del cliente
      <input type="text" name="cliente" maxlength="100" required value="<?php echo e($cliente); ?>">
    </label>
    <label>Teléfono de contacto
      <input type="text" name="telefono" maxlength="20" inputmode="tel" value="<?php echo e($telefono); ?>">
    </label>
    <label>Equipo
      <select name="equipo" required>
        <?php foreach ($EQUIPOS as $op) { ?><option value="<?php echo e($op); ?>" <?php if ($equipo == $op) echo "selected"; ?>><?php echo e($op); ?></option><?php } ?>
      </select>
    </label>
    <label>Problema reportado
      <textarea name="problema" rows="4" maxlength="5000" required><?php echo e($problema); ?></textarea>
    </label>
    <label>Prioridad
      <select name="prioridad" required>
        <?php foreach ($PRIORIDADES as $op) { ?><option value="<?php echo e($op); ?>" <?php if ($prioridad == $op) echo "selected"; ?>><?php echo e($op); ?></option><?php } ?>
      </select>
    </label>
    <label>Estado del equipo
      <select name="estado" required>
        <?php foreach ($ESTADOS as $op) { ?><option value="<?php echo e($op); ?>" <?php if ($estado == $op) echo "selected"; ?>><?php echo e($op); ?></option><?php } ?>
      </select>
      <small>Usa “En reparación” mientras se trabaja en el equipo y “Resuelto” cuando ya esté reparado.</small>
    </label>
    <div class="fila-botones">
      <button type="submit">Guardar cambios</button>
      <a class="btn-secundario" href="index.php">Cancelar y volver al inicio</a>
    </div>
  </form>
</main>
<footer class="pie">TechFix · Seguimiento de equipos</footer>
</body>
</html>
