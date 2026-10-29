<?php
require __DIR__ . '/includes/bootstrap.php';
csrf_check();
$reto = reto_por_slug($_GET['r'] ?? '');
if (!$reto) { http_response_code(404); flash('Reto no encontrado.', 'err'); redirect('retos.php'); }

$equipo = team_actual();
$modulos = ['mentes-brillantes' => 'mentes', 'memoria-extrema' => 'memoria', 'codigo-secreto' => 'codigo',
            'creatividad-sin-limites' => 'creatividad', 'desafio-relampago' => 'relampago'];
$modulo = $modulos[$reto['slug']] ?? null;
if ($modulo) require __DIR__ . '/includes/retos/' . $modulo . '.php';

// Procesa la respuesta del capitán (patrón Post/Redirect/Get: recargar no duplica nada)
if (is_post() && $modulo) {
    if (!$equipo) { flash('Ingresa como capitán para responder.', 'err'); redirect('login.php'); }
    call_user_func($modulo . '_post', $reto, $equipo);
    redirect('reto.php?r=' . $reto['slug']);
}

$rest  = reto_restante($reto);
$miPts = null;
if ($equipo) {
    $s = db()->prepare('SELECT puntos FROM puntuaciones WHERE equipo_id=? AND reto_id=?');
    $s->execute([$equipo['id'], $reto['id']]);
    $miPts = (int)$s->fetchColumn();
}
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
    <p class="muted">Máximo del reto: <strong style="color:var(--gold)"><?= (int)$reto['puntos_max'] ?> puntos</strong></p>
    <h3>🌟 Habilidades observables</h3><p><?= e($reto['habilidades']) ?></p>
    <?php if ($equipo): ?>
      <h3>Tu puntaje en este reto</h3>
      <div class="score-pill" style="--c:<?= e($equipo['color']) ?>"><b><?= $miPts ?></b><span class="muted">de <?= (int)$reto['puntos_max'] ?> pts</span></div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <h2>Área de juego</h2>
  <?php if (!$equipo && admin_actual()): ?>
    <div class="placeholder">🎙 Estás como facilitador. Para probar el reto, ingresa como capitán desde otra ventana de incógnito (el panel permite reiniciar los datos de prueba).</div>
  <?php elseif (!$equipo): ?>
    <div class="placeholder"><p><strong>Ingresa como capitán</strong> para participar en este reto.</p><a class="btn btn-primary" href="<?= url('login.php') ?>">Ingresar</a></div>
  <?php elseif ($reto['estado'] === 'pendiente'): ?>
    <div class="placeholder" data-autorefresh="8">⏳ El facilitador aún no abre este reto. Esta página se actualizará sola cuando comience.</div>
  <?php elseif ($reto['estado'] === 'cerrado' || !reto_activo($reto)): ?>
    <div class="placeholder">🔒 Este reto ya finalizó. No se aceptan nuevas respuestas. Tu puntaje: <strong style="color:var(--gold)"><?= (int)$miPts ?> pts</strong>.</div>
  <?php else: call_user_func($modulo . '_render', $reto, $equipo); endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
