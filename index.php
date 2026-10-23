<?php
declare(strict_types=1);
session_start();

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Falta configurar la conexión. Copia config.example.php como config.php y completa los datos de MySQL.');
}
$config = require $configPath;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli(
        (string)$config['host'],
        (string)$config['username'],
        (string)$config['password'],
        (string)$config['database']
    );
    $db->set_charset((string)($config['charset'] ?? 'utf8mb4'));
} catch (Throwable $e) {
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa los datos de config.php.');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function redirectHome(): never {
    header('Location: index.php');
    exit;
}
$statuses = ['Recibido', 'En diagnóstico', 'En reparación', 'Esperando repuestos', 'Listo para entregar', 'Entregado', 'No reparado'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'create') {
            $customer = trim((string)($_POST['customer_name'] ?? ''));
            $phone = trim((string)($_POST['customer_phone'] ?? ''));
            $device = trim((string)($_POST['device_type'] ?? ''));
            $brand = trim((string)($_POST['brand'] ?? ''));
            $model = trim((string)($_POST['model'] ?? ''));
            $problem = trim((string)($_POST['reported_problem'] ?? ''));
            if ($customer === '' || $phone === '' || $device === '' || $problem === '') {
                throw new RuntimeException('Completa cliente, teléfono, tipo de equipo y falla reportada.');
            }
            $code = 'ST-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $db->prepare('INSERT INTO tickets (code, customer_name, customer_phone, device_type, brand, model, reported_problem) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssss', $code, $customer, $phone, $device, $brand, $model, $problem);
            $stmt->execute();
            $id = $db->insert_id;
            $note = 'Reporte creado';
            $status = 'Recibido';
            $history = $db->prepare('INSERT INTO ticket_history (ticket_id, old_status, new_status, note) VALUES (?, NULL, ?, ?)');
            $history->bind_param('iss', $id, $status, $note);
            $history->execute();
            $_SESSION['flash'] = 'Reporte creado. Código de seguimiento: ' . $code;
            redirectHome();
        }

        if ($action === 'update_status') {
            $id = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
            $newStatus = (string)($_POST['status'] ?? '');
            $note = trim((string)($_POST['note'] ?? ''));
            if (!$id || !in_array($newStatus, $statuses, true)) {
                throw new RuntimeException('El reporte o el estado no son válidos.');
            }
            $db->begin_transaction();
            $stmt = $db->prepare('SELECT status FROM tickets WHERE id = ? FOR UPDATE');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $ticket = $stmt->get_result()->fetch_assoc();
            if (!$ticket) {
                throw new RuntimeException('No se encontró el reporte.');
            }
            $oldStatus = (string)$ticket['status'];
            $deliveredAt = $newStatus === 'Entregado' ? date('Y-m-d H:i:s') : null;
            $update = $db->prepare('UPDATE tickets SET status = ?, delivered_at = ? WHERE id = ?');
            $update->bind_param('ssi', $newStatus, $deliveredAt, $id);
            $update->execute();
            $history = $db->prepare('INSERT INTO ticket_history (ticket_id, old_status, new_status, note) VALUES (?, ?, ?, ?)');
            $history->bind_param('isss', $id, $oldStatus, $newStatus, $note);
            $history->execute();
            $db->commit();
            $_SESSION['flash'] = 'Estado actualizado correctamente.';
            redirectHome();
        }
    } catch (Throwable $ex) {
        if ($db->errno) {
            try { $db->rollback(); } catch (Throwable $ignored) {}
        }
        $error = $ex instanceof RuntimeException ? $ex->getMessage() : 'No se pudo guardar la operación. Verifica que las tablas estén creadas.';
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? '');
$where = [];
$params = [];
$types = '';
if ($search !== '') {
    $where[] = '(code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR device_type LIKE ? OR brand LIKE ? OR model LIKE ?)';
    $like = '%' . $search . '%';
    for ($i = 0; $i < 6; $i++) { $params[] = $like; $types .= 's'; }
}
if ($statusFilter !== '' && in_array($statusFilter, $statuses, true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}
$sql = 'SELECT * FROM tickets' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY received_at DESC LIMIT 200';
$stmt = $db->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$counts = array_fill_keys($statuses, 0);
$result = $db->query('SELECT status, COUNT(*) AS total FROM tickets GROUP BY status');
while ($row = $result->fetch_assoc()) { $counts[$row['status']] = (int)$row['total']; }
$historyRows = [];
$historyId = filter_input(INPUT_GET, 'history', FILTER_VALIDATE_INT);
if ($historyId) {
    $h = $db->prepare('SELECT h.*, t.code FROM ticket_history h JOIN tickets t ON t.id = h.ticket_id WHERE h.ticket_id = ? ORDER BY h.created_at DESC');
    $h->bind_param('i', $historyId);
    $h->execute();
    $historyRows = $h->get_result()->fetch_all(MYSQLI_ASSOC);
    $historyTitle = $db->prepare('SELECT code FROM tickets WHERE id = ?');
    $historyTitle->bind_param('i', $historyId);
    $historyTitle->execute();
    $historyTicket = $historyTitle->get_result()->fetch_assoc();
}
$flash = (string)($_SESSION['flash'] ?? '');
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Soporte Técnico | Seguimiento de equipos</title>
<style>
:root{font-family:system-ui,-apple-system,Segoe UI,sans-serif;color:#172033;background:#f3f6fb}
*{box-sizing:border-box}body{margin:0}header{background:#14213d;color:#fff;padding:22px max(18px,calc((100% - 1180px)/2))}header h1{margin:0;font-size:1.55rem}header p{margin:5px 0 0;color:#d5deef}.wrap{max-width:1180px;margin:24px auto;padding:0 16px}.flash{padding:12px 15px;background:#e0f7e9;border:1px solid #91d6aa;border-radius:10px;margin-bottom:16px}.error{padding:12px 15px;background:#fff0f0;border:1px solid #e8aaaa;border-radius:10px;margin-bottom:16px}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(135px,1fr));gap:12px;margin-bottom:20px}.stat,.panel,.ticket{background:#fff;border:1px solid #e0e6f0;border-radius:13px;padding:16px}.stat strong{display:block;font-size:1.6rem}.stat span{font-size:.85rem;color:#59657a}.layout{display:grid;grid-template-columns:minmax(270px,340px) minmax(0,1fr);gap:18px;align-items:start}.panel h2{font-size:1.15rem;margin:0 0 14px}label{display:block;font-size:.9rem;font-weight:650;margin:11px 0 5px}input,select,textarea{width:100%;border:1px solid #cbd4e2;border-radius:8px;padding:10px;font:inherit;background:#fff}textarea{min-height:85px;resize:vertical}button,.btn{display:inline-block;border:0;border-radius:8px;padding:10px 13px;background:#2459d3;color:#fff;font:inherit;font-weight:650;cursor:pointer;text-decoration:none}button:hover,.btn:hover{background:#1745ae}.secondary{background:#e9eef8;color:#20304c}.filters{display:grid;grid-template-columns:minmax(0,1fr) 210px auto;gap:8px;margin-bottom:12px}.ticket{margin-bottom:12px}.ticket-head{display:flex;gap:12px;justify-content:space-between;align-items:start;flex-wrap:wrap}.ticket h3{margin:0 0 5px;font-size:1.05rem}.muted{color:#657187;font-size:.9rem}.pill{display:inline-block;padding:5px 9px;background:#edf2ff;color:#214db1;border-radius:999px;font-size:.8rem;font-weight:700}.details{margin:12px 0;white-space:pre-wrap;overflow-wrap:anywhere}.status-form{display:grid;grid-template-columns:minmax(140px,1fr) minmax(140px,1fr) auto;gap:8px;align-items:end}.status-form label{margin-top:0}.history{margin-top:20px}.history-item{padding:10px 0;border-bottom:1px solid #e6ebf3}.empty{padding:25px;text-align:center;color:#657187}.small{font-size:.82rem}@media(max-width:800px){.layout{grid-template-columns:1fr}.filters{grid-template-columns:1fr}.status-form{grid-template-columns:1fr}}
</style>
</head>
<body>
<header><h1>🛠️ Soporte Técnico</h1><p>Registro y seguimiento de equipos en reparación</p></header>
<main class="wrap">
<?php if ($flash !== ''): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<section class="stats">
<?php foreach ($counts as $label => $count): ?><div class="stat"><strong><?= $count ?></strong><span><?= e($label) ?></span></div><?php endforeach; ?>
</section>
<div class="layout">
<section class="panel" id="nuevo-reporte">
<h2>➕ Nuevo reporte</h2>
<form method="post">
<input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="create">
<label for="customer_name">Nombre del cliente *</label><input id="customer_name" name="customer_name" maxlength="120" required>
<label for="customer_phone">Teléfono del cliente *</label><input id="customer_phone" name="customer_phone" maxlength="30" required>
<label for="device_type">Tipo de equipo *</label><input id="device_type" name="device_type" placeholder="Celular, portátil, televisor..." maxlength="80" required>
<label for="brand">Marca</label><input id="brand" name="brand" maxlength="80">
<label for="model">Modelo</label><input id="model" name="model" maxlength="100">
<label for="reported_problem">Falla reportada *</label><textarea id="reported_problem" name="reported_problem" required></textarea>
<button type="submit" style="width:100%;margin-top:15px">Guardar reporte</button>
<a class="btn secondary" href="index.php" style="display:block;text-align:center;margin-top:8px">Volver al inicio / Limpiar</a>
</form>
</section>
<section>
<div class="panel">
<h2>🔎 Buscar y filtrar equipos</h2>
<form class="filters" method="get">
<input name="q" value="<?= e($search) ?>" placeholder="Código, cliente, teléfono, equipo...">
<select name="status"><option value="">Todos los estados</option><?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
<button type="submit">Buscar</button>
</form>
<div class="muted small">Se muestran hasta 200 reportes, del más reciente al más antiguo.</div>
</div>
<div style="margin-top:12px">
<?php if (!$tickets): ?><div class="ticket empty">No hay reportes que coincidan con la búsqueda.</div><?php endif; ?>
<?php foreach ($tickets as $t): ?>
<article class="ticket">
<div class="ticket-head"><div><h3><?= e($t['code']) ?> · <?= e($t['device_type']) ?></h3><div><?= e($t['customer_name']) ?> · <?= e($t['customer_phone']) ?></div><div class="muted"><?= e(trim(($t['brand'] ?? '') . ' ' . ($t['model'] ?? ''))) ?> · Recibido: <?= e($t['received_at']) ?></div></div><span class="pill"><?= e($t['status']) ?></span></div>
<div class="details"><strong>Falla:</strong> <?= e($t['reported_problem']) ?><?php if ($t['diagnosis']): ?>
<strong>Diagnóstico:</strong> <?= e($t['diagnosis']) ?><?php endif; ?><?php if ($t['repair_notes']): ?>
<strong>Notas de reparación:</strong> <?= e($t['repair_notes']) ?><?php endif; ?></div>
<form method="post" class="status-form">
<input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="update_status"><input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
<div><label>Actualizar estado</label><select name="status"><?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
<div><label>Observación del cambio</label><input name="note" maxlength="1000" placeholder="Ej. cambio de pantalla"></div>
<button type="submit">Actualizar</button>
</form>
<div style="margin-top:12px"><a class="btn secondary" href="?history=<?= (int)$t['id'] ?>#historial">Ver historial</a></div>
</article>
<?php endforeach; ?>
</div>
</section>
</div>
<?php if ($historyId): ?>
<section class="panel history" id="historial">
<h2>📋 Historial del reporte <?= e($historyTicket['code'] ?? 'no encontrado') ?></h2>
<a class="btn secondary" href="index.php">← Volver al inicio</a>
<?php if (!$historyRows): ?><p class="muted">No hay movimientos o el reporte no existe.</p><?php endif; ?>
<?php foreach ($historyRows as $h): ?><div class="history-item"><strong><?= e($h['old_status'] ?: 'Creado') ?> → <?= e($h['new_status']) ?></strong><div class="muted"><?= e($h['created_at']) ?></div><?php if ($h['note']): ?><div><?= e($h['note']) ?></div><?php endif; ?></div><?php endforeach; ?>
</section>
<?php endif; ?>
</main>
</body>
</html>
