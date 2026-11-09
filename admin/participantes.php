<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();

if (is_post() && ($_POST['accion'] ?? '') === 'retirar') {
    $pid = (int)($_POST['participante_id'] ?? 0);
    $s = db()->prepare('SELECT p.alias, p.equipo_id, e.nombre FROM participantes p JOIN equipos e ON e.id = p.equipo_id WHERE p.id = ?');
    $s->execute([$pid]);
    if ($x = $s->fetch()) {
        db()->prepare('DELETE FROM participantes WHERE id = ?')->execute([$pid]);   // sus respuestas se borran en cascada
        recalcular_equipo((int)$x['equipo_id']);
        bitacora('sistema', "Participante retirado: {$x['alias']} ({$x['nombre']})");
        flash('Participante retirado y puntos del equipo recalculados.');
    }
    redirect('admin/participantes.php');
}

$equipos = db()->query('SELECT * FROM equipos WHERE activo = 1 ORDER BY id')->fetchAll();
$part = [];
foreach (db()->query('SELECT id, equipo_id, alias, (last_seen >= NOW() - INTERVAL 15 SECOND) AS on_ FROM participantes ORDER BY alias') as $r) $part[$r['equipo_id']][] = $r;
$ind = [];   // [participante][reto] => puntos
foreach (db()->query('SELECT participante_id, reto_id, SUM(puntos) p FROM respuestas GROUP BY participante_id, reto_id') as $r) $ind[$r['participante_id']][$r['reto_id']] = (int)$r['p'];
foreach (db()->query('SELECT participante_id, SUM(puntos) p FROM intentos_codigo GROUP BY participante_id') as $r) $ind[$r['participante_id']][3] = (int)$r['p'];
$eqPts = matriz_puntos();
$total = (int)db()->query('SELECT COUNT(*) FROM participantes')->fetchColumn();
$conect = (int)db()->query('SELECT COUNT(*) FROM participantes WHERE last_seen >= NOW() - INTERVAL 15 SECOND')->fetchColumn();
$cols = [1 => '🧠 R1', 2 => '👁 R2', 3 => '🔒 R3', 5 => '⚡ R5'];

$titulo = 'Participantes'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Seguimiento</div><h1>Participantes</h1>
  <p><strong><?= $total ?></strong> registrados · <strong><?= $conect ?></strong> conectados ahora. Recarga para actualizar. La última fila de cada equipo muestra cómo se obtiene el promedio.
  Enlace de acceso por equipo (sirve para un QR): se arma con <code>login.php?e=ID&amp;c=CÓDIGO</code>.</p></div>

<?php foreach ($equipos as $e): $lista = $part[$e['id']] ?? []; $base = base_equipo((int)$e['id']); ?>
  <div class="card" style="border-top:5px solid <?= e($e['color']) ?>;margin-bottom:18px">
    <h3 style="margin-bottom:6px"><?= e($e['nombre']) ?> <small class="muted">· código <strong style="color:var(--gold)"><?= e($e['pin']) ?></strong> · <?= count($lista) ?> registrados de <?= (int)$e['miembros'] ?> declarados</small></h3>
    <p style="margin:0 0 10px"><input readonly value="<?= e((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . url('login.php?e=' . (int)$e['id'] . '&c=' . $e['pin'])) ?>"
       style="font-size:.8rem;padding:5px 9px" onclick="this.select()" aria-label="Enlace de acceso <?= e($e['nombre']) ?>"></p>
    <div class="table-wrap"><table style="min-width:520px"><thead><tr><th>Alias</th>
      <?php foreach ($cols as $t): ?><th class="num"><?= $t ?></th><?php endforeach; ?><th class="num">Suma</th><th></th></tr></thead><tbody>
    <?php foreach ($lista as $x): $sum = 0; ?>
      <tr><td><i class="<?= $x['on_'] ? 'on-dot' : 'off-dot' ?>" style="display:inline-block;margin-right:8px"></i><?= e($x['alias']) ?></td>
        <?php foreach ($cols as $rid => $t): $pt = $ind[$x['id']][$rid] ?? 0; $sum += $pt; ?><td class="num"><?= $pt ?></td><?php endforeach; ?>
        <td class="num total"><?= $sum ?></td>
        <td><form method="post" data-confirm="¿Retirar a <?= e($x['alias']) ?>? Se borran sus respuestas."><?= csrf_field() ?><input type="hidden" name="accion" value="retirar"><input type="hidden" name="participante_id" value="<?= (int)$x['id'] ?>">
          <button class="btn btn-ghost btn-sm">Retirar</button></form></td></tr>
    <?php endforeach; if (!$lista): ?><tr><td colspan="7" class="muted center">Nadie se ha unido todavía.</td></tr><?php endif; ?>
      <tr style="background:#0b1f3a"><td><strong>Equipo (suma ÷ <?= $base ?>)</strong></td>
        <?php foreach ($cols as $rid => $t): ?><td class="num total"><?= (int)($eqPts[$e['id']][$rid] ?? 0) ?></td><?php endforeach; ?><td class="num"></td><td></td></tr>
    </tbody></table></div>
  </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
