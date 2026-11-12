<?php
require __DIR__ . '/includes/bootstrap.php';
csrf_check();
$reto = reto_por_slug($_GET['r'] ?? '');
if (!$reto) { http_response_code(404); flash('Reto no encontrado.', 'err'); redirect('retos.php'); }

$equipo = team_actual();
$yo = participante_actual();
$modulos = ['mentes-brillantes' => 'mentes', 'memoria-extrema' => 'memoria', 'codigo-secreto' => 'codigo',
            'creatividad-sin-limites' => 'creatividad', 'desafio-relampago' => 'relampago'];
$modulo = $modulos[$reto['slug']] ?? null;
if ($modulo) require __DIR__ . '/includes/retos/' . $modulo . '.php';

// Procesa la acción de la persona (Post/Redirect/Get: recargar no duplica nada)
if (is_post() && $modulo) {
    if (!$equipo || !$yo) { flash('Ingresa con tu equipo para participar.', 'err'); redirect('login.php'); }
    call_user_func($modulo . '_post', $reto, $equipo, $yo);
    redirect('reto.php?r=' . $reto['slug']);
}

$rest = reto_restante($reto);
$ind = $yo ? puntos_individuales_todos((int)$yo['id'], (int)$equipo['id']) : [];
$miPts = $ind[(int)$reto['id']] ?? 0;
$teamPts = 0;
if ($equipo) {
    $s = db()->prepare('SELECT puntos FROM puntuaciones WHERE equipo_id=? AND reto_id=?');
    $s->execute([$equipo['id'], $reto['id']]);
    $teamPts = (int)$s->fetchColumn();
}
$esEquipo = (int)$reto['id'] === 4;   // Creatividad se puntúa por equipo
$titulo = $reto['nombre']; $pagina = 'retos';
require __DIR__ . '/includes/header.php';
?>
<p><a href="<?= url('retos.php') ?>">← Todos los retos</a></p>
<div class="reto-hero" style="--c:<?= e($reto['color']) ?>">
  <span class="big"><?= $reto['icono'] ?></span>
  <div>
    <div class="eyebrow" style="color:#fff9">Reto <?= (int)$reto['orden'] ?> de 5</div>
    <h1 style="margin:0"><?= e($reto['nombre']) ?></h1>
    <span class="badge b-<?= e($reto['estado']) ?>" style="margin-top:8px"><?= estado_texto($reto['estado']) ?></span>
  </div>
  <div class="timer">
    <?php if ($rest !== null): ?>
      <b data-countdown="<?= $rest ?>" data-fin-text="¡Tiempo!"></b><small>tiempo total del reto</small>
    <?php else: ?>
      <b><?= str_pad((string)$reto['duracion_min'], 2, '0', STR_PAD_LEFT) ?>:00</b><small>duración del reto</small>
    <?php endif; ?>
  </div>
</div>

<?php if ($equipo && torneo_activo() && ($pm = torneo_partido_equipo((int)$equipo['id'], (int)$reto['id']))): ?>
<div class="match mine" style="margin-bottom:22px">
  <div class="tm"><i class="dot" style="--c:<?= e($equipo['color']) ?>"></i><?= e($equipo['nombre']) ?></div>
  <div class="sc"><?= $pm['mi_pts'] === null ? '–' : (int)$pm['mi_pts'] ?> : <?= $pm['su_pts'] === null ? '–' : (int)$pm['su_pts'] ?></div>
  <div class="tm r"><?= e($pm['rival']) ?><i class="dot" style="--c:<?= e($pm['rival_color']) ?>"></i></div>
  <div class="res">🏆 Ronda <?= (int)$pm['numero'] ?> del torneo: tu equipo se enfrenta a <strong><?= e($pm['rival']) ?></strong> en este reto ·
    <?= $pm['resultado'] === 'victoria' ? '¡Ganaron! +' . TORNEO_PTS_VICTORIA : ($pm['resultado'] === 'empate' ? 'Empate +' . TORNEO_PTS_EMPATE : ($pm['resultado'] === 'derrota' ? 'Derrota' : 'gana el equipo con más puntos')) ?></div>
</div>
<?php endif; ?>
<?php $jugando = $equipo && reto_activo($reto); ?>
<?php if (!$jugando): ?>
<div class="grid g2">
  <div class="card">
    <h3>🎯 Propósito</h3><p><?= e($reto['proposito']) ?></p>
    <h3>⚙️ Material y mecánica</h3>
    <ul class="check"><?php foreach (preg_split('/\R/', $reto['mecanica']) as $l) if (trim($l) !== '') echo '<li>' . e($l) . '</li>'; ?></ul>
  </div>
  <div class="card">
    <h3>🏅 Puntuación</h3>
    <p><?= e($reto['regla_puntos']) ?></p>
    <p class="muted"><?= $esEquipo ? 'Todo el equipo recibe el mismo puntaje.' : 'Cada integrante suma su puntaje individual (máx. ' . (int)$reto['puntos_max'] . ') y el equipo recibe el <strong>promedio</strong>.' ?></p>
    <h3>🌟 Habilidades observables</h3><p><?= e($reto['habilidades']) ?></p>
    <?php if ($equipo): ?>
      <h3>Tus puntos</h3>
      <div class="team-score">
        <?php if (!$esEquipo): ?><div class="score-pill" style="--c:<?= e($equipo['color']) ?>"><b><?= $miPts ?></b><span class="muted">tú · de <?= (int)$reto['puntos_max'] ?></span></div><?php endif; ?>
        <div class="score-pill" style="--c:<?= e($equipo['color']) ?>"><b><?= $teamPts ?></b><span class="muted"><?= $esEquipo ? 'tu equipo' : 'promedio del equipo' ?></span></div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <h2>Área de juego</h2>
  <?php if (!$equipo && admin_actual()): ?>
    <div class="placeholder">🎙 Estás como facilitador. Para probar el reto, ingresa como participante desde otra ventana de incógnito (el panel permite reiniciar los datos de prueba).</div>
  <?php elseif (!$equipo): ?>
    <div class="placeholder"><p><strong>Ingresa con tu equipo</strong> para participar en este reto.</p><a class="btn btn-primary" href="<?= url('login.php') ?>">Ingresar</a></div>
  <?php elseif ($reto['estado'] === 'pendiente'): ?>
    <div class="placeholder" data-autorefresh="8">⏳ El facilitador aún no abre este reto. Esta página se actualizará sola cuando comience.</div>
  <?php elseif ($reto['estado'] === 'cerrado' || !reto_activo($reto)): ?>
    <div class="placeholder">🔒 Este reto ya finalizó. No se aceptan nuevas respuestas.<br>
      <?= $esEquipo ? '' : 'Tu puntaje: <strong style="color:var(--gold)">' . $miPts . ' pts</strong> · ' ?>Equipo: <strong style="color:var(--gold)"><?= $teamPts ?> pts</strong>.</div>
  <?php else: call_user_func($modulo . '_render', $reto, $equipo, $yo); endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
