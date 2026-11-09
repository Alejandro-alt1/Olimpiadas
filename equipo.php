<?php
require __DIR__ . '/includes/bootstrap.php';
$equipo = require_team();
$yo = participante_actual();
$retos  = retos_all();
$ind = puntos_individuales_todos((int)$yo['id'], (int)$equipo['id']);
$mis = [];
$s = db()->prepare('SELECT reto_id, puntos FROM puntuaciones WHERE equipo_id = ?'); $s->execute([$equipo['id']]);
foreach ($s as $p) $mis[$p['reto_id']] = (int)$p['puntos'];
$total = array_sum($mis);
$rank = ranking(); $miPos = null; $miEmpate = false;
foreach ($rank as $f) if ($f['id'] == $equipo['id']) { $miPos = $f['pos']; $miEmpate = $f['empate']; }
$visible = config_get('marcador_visible', '1') === '1';
$s = db()->prepare('SELECT alias, (last_seen >= NOW() - INTERVAL 15 SECOND) AS on_ FROM participantes WHERE equipo_id = ? ORDER BY alias');
$s->execute([$equipo['id']]); $miembros = $s->fetchAll();
$base = base_equipo((int)$equipo['id']);

$titulo = $equipo['nombre']; $pagina = 'equipo';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="eyebrow">Mi equipo</div>
  <h1><i class="dot" style="--c:<?= e($equipo['color']) ?>;width:20px;height:20px"></i> <?= e($equipo['nombre']) ?></h1>
  <p>Tú: <strong><?= e($yo['alias']) ?></strong> · Registrados: <strong><?= count($miembros) ?></strong> de <?= (int)$equipo['miembros'] ?> integrantes.
  No hay un único líder: <strong>todos juegan a su ritmo</strong> y el puntaje del equipo en cada reto es el <strong>promedio</strong> de los puntajes individuales (se promedia entre <?= $base ?> personas).</p>
  <p><a class="btn btn-primary btn-sm" href="<?= url('retos.php') ?>">▶ Ir a los retos</a></p>
</div>
<div class="stats" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat"><b><?= $total ?></b><span>Puntos del equipo</span></div>
  <div class="stat"><b><?= $visible ? $miPos . '°' : '—' ?></b><span>Posición<?= $visible && $miEmpate ? ' (empate)' : '' ?></span></div>
  <div class="stat"><b><?= count($miembros) ?></b><span>Compañeros registrados</span></div>
</div>
<div class="roster" style="margin-bottom:26px"><?php foreach ($miembros as $m): ?><span class="rchip" style="padding-right:12px"><i class="<?= $m['on_'] ? 'on-dot' : 'off-dot' ?>"></i><?= e($m['alias']) ?></span><?php endforeach; ?></div>
<?php if (torneo_activo()):
  $fila = null; foreach ($rank as $f) if ((int)$f['id'] === (int)$equipo['id']) $fila = $f;
  $pm = torneo_partido_equipo((int)$equipo['id']); ?>
<div class="card" style="margin-bottom:26px;border-top:5px solid var(--gold)">
  <h3 style="margin-top:0">🏆 Torneo por enfrentamientos</h3>
  <p style="margin:0 0 10px"><strong style="color:var(--gold);font-size:1.4rem"><?= (int)($fila['tp'] ?? 0) ?></strong> puntos de torneo ·
    G <?= (int)($fila['g'] ?? 0) ?> · E <?= (int)($fila['e'] ?? 0) ?> · P <?= (int)($fila['p'] ?? 0) ?>
    · <a href="<?= url('torneo.php') ?>">ver torneo completo</a></p>
  <?php if ($pm): ?>
    <div class="match mine"><div class="tm"><i class="dot" style="--c:<?= e($equipo['color']) ?>"></i><?= e($equipo['nombre']) ?></div>
      <div class="sc"><?= $pm['mi_pts'] === null ? '–' : (int)$pm['mi_pts'] ?> : <?= $pm['su_pts'] === null ? '–' : (int)$pm['su_pts'] ?></div>
      <div class="tm r"><?= e($pm['rival']) ?><i class="dot" style="--c:<?= e($pm['rival_color']) ?>"></i></div>
      <div class="res">Ronda <?= (int)$pm['numero'] ?> · <?= $pm['resultado'] === 'victoria' ? '🏆 ¡Ganaron!' : ($pm['resultado'] === 'empate' ? '🤝 Empate' : ($pm['resultado'] === 'derrota' ? 'Derrota' : 'En curso')) ?></div></div>
  <?php else: ?><p class="muted" style="margin:0">Todavía no hay un enfrentamiento asignado.</p><?php endif; ?>
</div>
<?php endif; ?>
<h2>Puntos por reto</h2>
<div class="team-score">
<?php foreach ($retos as $r): $esEq = (int)$r['id'] === 4; ?>
  <a class="score-pill" style="--c:<?= e($r['color']) ?>;color:inherit;text-decoration:none" href="<?= url('reto.php?r=' . e($r['slug'])) ?>">
    <small class="muted"><?= $r['icono'] ?> <?= e($r['nombre']) ?></small>
    <b><?= $mis[$r['id']] ?? 0 ?><small class="muted"> / <?= (int)$r['puntos_max'] ?></small></b>
    <small class="muted"><?= $esEq ? 'puntaje del equipo' : 'equipo · tú: ' . (int)$ind[$r['id']] ?></small><br>
    <span class="badge b-<?= e($r['estado']) ?>"><?= estado_texto($r['estado']) ?></span>
  </a>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
