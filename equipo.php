<?php
require __DIR__ . '/includes/bootstrap.php';
$equipo = require_team();
$retos  = retos_all();
$mis = [];
$s = db()->prepare('SELECT reto_id, puntos FROM puntuaciones WHERE equipo_id = ?'); $s->execute([$equipo['id']]);
foreach ($s as $p) $mis[$p['reto_id']] = (int)$p['puntos'];
$total = array_sum($mis);
$rank = ranking(); $miPos = null; $miEmpate = false;
foreach ($rank as $f) if ($f['id'] == $equipo['id']) { $miPos = $f['pos']; $miEmpate = $f['empate']; }
$visible = config_get('marcador_visible', '1') === '1';

$titulo = $equipo['nombre']; $pagina = 'equipo';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head" style="--c:<?= e($equipo['color']) ?>">
  <div class="eyebrow">Vista del equipo</div>
  <h1><i class="dot" style="--c:<?= e($equipo['color']) ?>;width:20px;height:20px"></i> <?= e($equipo['nombre']) ?></h1>
  <p>Capitán: <strong><?= e($equipo['capitan'] ?: 'sin registrar') ?></strong> · Integrantes: <strong><?= (int)$equipo['miembros'] ?></strong></p>
</div>
<div class="stats" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat"><b><?= $total ?></b><span>Puntos totales</span></div>
  <div class="stat"><b><?= $visible ? $miPos . '°' : '—' ?></b><span>Posición<?= $visible && $miEmpate ? ' (empate)' : '' ?></span></div>
  <div class="stat"><b><?= count(array_filter($retos, fn($r) => $r['estado'] === 'abierto')) ?></b><span>Retos en curso</span></div>
</div>
<h2>Puntos por reto</h2>
<div class="team-score">
<?php foreach ($retos as $r): ?>
  <a class="score-pill" style="--c:<?= e($r['color']) ?>;color:inherit;text-decoration:none" href="<?= url('reto.php?r=' . e($r['slug'])) ?>">
    <small class="muted"><?= $r['icono'] ?> <?= e($r['nombre']) ?></small>
    <b><?= $mis[$r['id']] ?? 0 ?><small class="muted"> / <?= (int)$r['puntos_max'] ?></small></b>
    <span class="badge b-<?= e($r['estado']) ?>"><?= estado_texto($r['estado']) ?></span>
  </a>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
