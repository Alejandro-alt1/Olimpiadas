<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Retos'; $pagina = 'retos';
$retos = retos_all();
$equipo = team_actual();
$mis = [];
if ($equipo) foreach (db()->query('SELECT reto_id, puntos FROM puntuaciones WHERE equipo_id = ' . (int)$equipo['id']) as $p) $mis[$p['reto_id']] = $p['puntos'];
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="eyebrow">Sesión de 60 minutos</div><h1>Los 5 retos</h1>
  <p>El facilitador abre cada reto en su momento. Cuando un reto esté <strong>en curso</strong>, el capitán podrá registrar las respuestas de su equipo.</p></div>
<div class="grid g3">
<?php foreach ($retos as $r): $rest = reto_restante($r); ?>
  <a class="reto-card" style="--c:<?= e($r['color']) ?>" href="<?= url('reto.php?r=' . e($r['slug'])) ?>">
    <div class="reto-head"><span class="reto-num"><?= (int)$r['orden'] ?></span><h3><?= e($r['nombre']) ?></h3><span class="reto-ico"><?= $r['icono'] ?></span></div>
    <p><?= e($r['resumen']) ?></p>
    <p><strong style="color:var(--txt)"><?= e($r['regla_puntos']) ?></strong></p>
    <div class="reto-meta">
      <span>⏱ <?= (int)$r['duracion_min'] ?> min<?= $equipo ? ' · Tu puntaje: <b style="color:var(--gold)">' . (int)($mis[$r['id']] ?? 0) . '</b>' : '' ?></span>
      <span class="badge b-<?= e($r['estado']) ?>"><?= estado_texto($r['estado']) ?><?= $rest !== null ? ' · <span data-countdown="' . $rest . '"></span>' : '' ?></span>
    </div>
  </a>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
