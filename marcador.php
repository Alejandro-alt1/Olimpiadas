<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Marcador'; $pagina = 'marcador';
$visible = config_get('marcador_visible', '1') === '1' || admin_actual();
$retos = retos_all();
$matriz = matriz_puntos();
$rank = $visible ? ranking() : [];
$yo = $_SESSION['equipo_id'] ?? 0;
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="eyebrow">Marcador general</div>
  <h1>Marcador en tiempo real</h1>
  <p><span class="live">En vivo</span> · actualizado a las <span data-updated><?= date('H:i:s') ?></span> · Máximo posible: <?= puntos_max_total() ?> pts.</p>
</div>
<?php if (!$visible): ?>
  <div class="placeholder">🙈 El facilitador ocultó temporalmente el marcador. ¡Pronto se revelará!</div>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Equipo</th>
    <?php foreach ($retos as $r): ?><th class="num" title="<?= e($r['nombre']) ?>"><?= $r['icono'] ?> R<?= (int)$r['orden'] ?></th><?php endforeach; ?>
    <th class="num">Total</th></tr></thead>
  <tbody data-live-scoreboard data-api="<?= url('api/marcador.php') ?>">
  <?php foreach ($rank as $f): ?>
    <tr class="<?= $f['pos'] <= 3 ? 'top top-' . $f['pos'] : '' ?>">
      <td class="pos"><?= medalla((int)$f['pos']) ?></td>
      <td><span class="team"><i class="dot" style="--c:<?= e($f['color']) ?>"></i><?= e($f['nombre']) ?><?= $f['empate'] ? ' <small class="tag">empate</small>' : '' ?></span></td>
      <?php foreach ($retos as $r): ?><td class="num"><?= $matriz[$f['id']][$r['id']] ?? 0 ?></td><?php endforeach; ?>
      <td class="num total"><?= (int)$f['total'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<p class="muted" style="margin-top:14px;font-size:.9rem"><strong>Desempate:</strong> 1) total de puntos · 2) puntos en Mentes Brillantes · 3) puntos en Creatividad Sin Límites · 4) pregunta final para los equipos empatados.</p>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
