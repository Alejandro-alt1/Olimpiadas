<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();

if (is_post()) {
    $desc = trim($_POST['descripcion'] ?? '');
    if ($desc === '') { flash('Describe la incidencia.', 'err'); redirect('admin/bitacora.php'); }
    $reto = (int)($_POST['reto_id'] ?? 0) ?: null;
    $eq   = (int)($_POST['equipo_id'] ?? 0) ?: null;
    bitacora('incidencia', mb_substr($desc, 0, 500), mb_substr(trim($_POST['decision'] ?? ''), 0, 500) ?: null, $reto, $eq);
    flash('Incidencia registrada.');
    redirect('admin/bitacora.php');
}

$retos   = retos_all();
$equipos = db()->query('SELECT id, nombre FROM equipos ORDER BY id')->fetchAll();
$tipo = $_GET['tipo'] ?? '';
$sql = 'SELECT b.*, r.nombre AS reto, e.nombre AS equipo FROM bitacora b
        LEFT JOIN retos r ON r.id = b.reto_id LEFT JOIN equipos e ON e.id = b.equipo_id';
$args = [];
if (in_array($tipo, ['incidencia', 'puntos', 'sistema'], true)) { $sql .= ' WHERE b.tipo = ?'; $args[] = $tipo; }
$sql .= ' ORDER BY b.id DESC LIMIT 200';
$s = db()->prepare($sql); $s->execute($args);
$filas = $s->fetchAll();

$titulo = 'Bitácora'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Auditoría</div><h1>Bitácora e incidencias</h1>
  <p>Registra la incidencia, la hora, el reto afectado, la decisión aplicada y quién la autorizó, tal como pide la guía.</p></div>

<div class="card" style="margin-bottom:24px"><h3>➕ Nueva incidencia</h3>
  <form method="post"><?= csrf_field() ?>
    <div class="row">
      <div class="field"><label>Reto afectado</label><select name="reto_id"><option value="0">— Ninguno / general —</option>
        <?php foreach ($retos as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['nombre']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Equipo afectado</label><select name="equipo_id"><option value="0">— Todos / ninguno —</option>
        <?php foreach ($equipos as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['nombre']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="field"><label>Incidencia</label><textarea name="descripcion" rows="2" required maxlength="500" placeholder="Ej.: Equipo 4 se desconectó durante la pregunta 5"></textarea></div>
    <div class="field"><label>Decisión aplicada</label><textarea name="decision" rows="2" maxlength="500" placeholder="Ej.: Se repitió la pregunta para todos los equipos"></textarea></div>
    <p class="muted" style="font-size:.9rem">Autoriza: <strong><?= e(admin_actual()['nombre']) ?></strong> · la hora se registra automáticamente.</p>
    <button class="btn btn-primary">Registrar</button>
  </form></div>

<div class="tabs"><?php foreach (['' => 'Todo', 'incidencia' => 'Incidencias', 'puntos' => 'Puntos', 'sistema' => 'Sistema'] as $k => $v): ?>
  <a class="<?= $tipo === $k ? 'on' : '' ?>" href="<?= url('admin/bitacora.php' . ($k ? '?tipo=' . $k : '')) ?>"><?= $v ?></a><?php endforeach; ?></div>
<div class="table-wrap"><table>
  <thead><tr><th>Hora</th><th>Tipo</th><th>Reto</th><th>Equipo</th><th>Descripción</th><th>Decisión</th><th>Autorizó</th></tr></thead><tbody>
  <?php foreach ($filas as $f): ?>
    <tr><td style="white-space:nowrap"><?= e(date('d/m H:i:s', strtotime($f['created_at']))) ?></td>
      <td><span class="tag"><?= e($f['tipo']) ?></span></td><td><?= e($f['reto'] ?? '—') ?></td><td><?= e($f['equipo'] ?? '—') ?></td>
      <td><?= e($f['descripcion']) ?></td><td><?= e($f['decision'] ?? '—') ?></td><td><?= e($f['autorizado_por']) ?></td></tr>
  <?php endforeach; if (!$filas): ?><tr><td colspan="7" class="muted center">Sin registros todavía.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
