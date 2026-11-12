<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Torneo'; $pagina = 'torneo';
$activo = torneo_activo();
$visible = config_get('marcador_visible', '1') === '1' || admin_actual();
$equipo = team_actual();
$miEq = $equipo ? (int)$equipo['id'] : 0;
$rondas = $activo ? torneo_rondas() : [];
$rank = ($activo && $visible) ? ranking() : [];
$hayEnCurso = false; foreach ($rondas as $r) if ($r['estado'] === 'en_curso') $hayEnCurso = true;
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="eyebrow">Sistema suizo</div><h1>Torneo por enfrentamientos</h1>
  <p>En cada ronda los equipos se enfrentan <strong>1 contra 1</strong> en un reto: gana quien tenga más puntos en él.
  Victoria <strong><?= TORNEO_PTS_VICTORIA ?></strong> puntos de torneo · empate <strong><?= TORNEO_PTS_EMPATE ?></strong> · derrota <strong><?= TORNEO_PTS_DERROTA ?></strong>.</p></div>
<?php if ($hayEnCurso): ?><div data-autorefresh="10"></div><?php endif; ?>

<?php if (!$activo): ?>
  <div class="placeholder">🏆 El torneo todavía no está activo. El facilitador lo habilitará cuando comience.</div>
<?php else: ?>
  <h2>Clasificación</h2>
  <?php if (!$visible): ?>
    <div class="placeholder">🙈 El facilitador ocultó temporalmente la clasificación.</div>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>#</th><th>Equipo</th><th class="num" title="Puntos de torneo">🏆 PT</th><th class="num" title="Partidos jugados">PJ</th><th class="num">G</th><th class="num">E</th><th class="num">P</th><th class="num" title="Puntos de pruebas (retos)">Pruebas</th></tr></thead><tbody>
    <?php foreach ($rank as $f): ?>
      <tr class="<?= $f['pos'] <= 3 && $f['pj'] > 0 ? 'top top-' . $f['pos'] : '' ?>" <?= (int)$f['id'] === $miEq ? 'style="outline:2px solid var(--gold)"' : '' ?>>
        <td class="pos"><?= medalla((int)$f['pos']) ?></td>
        <td><span class="team"><i class="dot" style="--c:<?= e($f['color']) ?>"></i><?= e($f['nombre']) ?><?= $f['empate'] ? ' <small class="tag">empate</small>' : '' ?></span></td>
        <td class="num total"><?= (int)$f['tp'] ?></td><td class="num"><?= (int)$f['pj'] ?></td><td class="num"><?= (int)$f['g'] ?></td><td class="num"><?= (int)$f['e'] ?></td><td class="num"><?= (int)$f['p'] ?></td>
        <td class="num"><?= (int)$f['total'] ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <p class="muted" style="margin-top:12px;font-size:.9rem"><strong>Orden:</strong> puntos de torneo · puntos de pruebas · victorias · enfrentamiento directo · Mentes Brillantes · Creatividad.</p>
  <?php endif; ?>

  <h2 style="margin-top:34px">Rondas y enfrentamientos</h2>
  <?php if (!$rondas): ?><div class="placeholder">Aún no se genera la primera ronda.</div><?php endif; ?>
  <?php foreach (array_reverse($rondas) as $r):
    $partidos = torneo_partidos((int)$r['id']);
    $live = ($r['estado'] === 'en_curso' && $visible) ? torneo_puntos_reto($r['reto_id'] ? (int)$r['reto_id'] : null) : []; ?>
    <div class="ronda-head"><h3>Ronda <?= (int)$r['numero'] ?></h3>
      <span class="tag"><?= $r['reto_id'] ? e($r['reto_icono'] . ' ' . $r['reto_nombre']) : 'Resultado definido por el facilitador' ?></span>
      <span class="badge <?= $r['estado'] === 'cerrada' ? 'b-cerrado' : 'b-abierto' ?>"><?= $r['estado'] === 'cerrada' ? 'Cerrada' : 'En curso' ?></span></div>
    <div class="match-list">
    <?php foreach ($partidos as $m):
      $a = (int)$m['equipo_a']; $b = (int)$m['equipo_b'];
      if ($r['estado'] === 'cerrada' || !$r['reto_id']) { $pa = $m['puntos_a']; $pb = $m['puntos_b']; }
      else { $pa = $visible ? ($live[$a] ?? 0) : null; $pb = $visible ? ($live[$b] ?? 0) : null; }
      $cls = ''; $txt = $r['estado'] === 'cerrada' ? '' : 'Gana el equipo con más puntos en el reto';
      if ($m['estado'] === 'jugado') {
          if ((int)$m['empate'] === 1) { $txt = '🤝 Empate · +' . TORNEO_PTS_EMPATE . ' cada equipo'; }
          else { $gan = (int)$m['ganador_id']; $cls = $gan === $a ? 'win-a' : 'win-b'; $txt = '🏆 Gana ' . e($gan === $a ? $m['nombre_a'] : $m['nombre_b']) . ' · +' . TORNEO_PTS_VICTORIA; }
          if ($m['manual']) $txt .= ' (resultado fijado por el facilitador)';
      } ?>
      <div class="match <?= $cls ?> <?= $miEq && ($a === $miEq || $b === $miEq) ? 'mine' : '' ?>">
        <div class="tm"><i class="dot" style="--c:<?= e($m['color_a']) ?>"></i><?= e($m['nombre_a']) ?></div>
        <div class="sc"><?= $pa === null ? '–' : (int)$pa ?> : <?= $pb === null ? '–' : (int)$pb ?></div>
        <div class="tm r"><?= e($m['nombre_b']) ?><i class="dot" style="--c:<?= e($m['color_b']) ?>"></i></div>
        <?php if ($txt !== ''): ?><div class="res"><?= $txt ?></div><?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <?php if ($rondas && !$hayEnCurso && count($rondas) >= torneo_num_rondas() && $rank): $camp = $rank[0]; ?>
    <div class="card" style="margin-top:34px;text-align:center;border:2px solid var(--gold)">
      <div style="font-size:3rem">🏆</div><h2 style="margin:6px 0">¡Campeón del torneo: <?= e($camp['nombre']) ?>!</h2>
      <p class="muted" style="margin:0"><?= (int)$camp['tp'] ?> puntos de torneo · <?= (int)$camp['g'] ?> victorias · <?= (int)$camp['total'] ?> puntos de pruebas</p></div>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
