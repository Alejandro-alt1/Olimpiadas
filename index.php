<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Inicio'; $pagina = 'inicio';
$retos = retos_all();
$top = config_get('marcador_visible', '1') === '1' || admin_actual() ? array_slice(ranking(), 0, 3) : [];
$totalPts = puntos_max_total();
$nEquipos = (int)db()->query('SELECT COUNT(*) FROM equipos WHERE activo=1')->fetchColumn();
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div>
    <div class="eyebrow">Plataforma de integración empresarial</div>
    <h1>Olimpiadas Virtuales <span>Corporativas</span></h1>
    <p class="lead">Una experiencia de trabajo en equipo, diversión y superación. 5 retos interactivos, <?= $nEquipos ?> equipos y 60 minutos para demostrar el talento de tu equipo.</p>
    <div class="hero-cta">
      <?php if (team_actual()): ?>
        <a class="btn btn-primary" href="<?= url('retos.php') ?>">▶ Ir a los retos</a>
      <?php else: ?>
        <a class="btn btn-primary" href="<?= url('login.php') ?>">▶ Comenzar</a>
      <?php endif; ?>
      <a class="btn btn-ghost" href="<?= url('marcador.php') ?>">🏆 Ver marcador</a>
      <a class="btn btn-ghost" href="<?= url('reglas.php') ?>">📋 Reglas</a>
    </div>
  </div>
  <div class="hero-art" aria-hidden="<?= $top ? 'false' : 'true' ?>">
    <div class="trophy">🏆</div>
    <?php if ($top): ?>
    <div class="mini-board">
      <?php foreach ($top as $t): ?>
        <div class="mini-row"><span><?= medalla($t['pos']) ?></span><i class="dot" style="--c:<?= e($t['color']) ?>"></i><?= e($t['nombre']) ?><b><?= (int)$t['total'] ?> pts</b></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="stats" aria-label="Datos del evento">
  <div class="stat"><b>5</b><span>Retos interactivos</span></div>
  <div class="stat"><b><?= $nEquipos ?></b><span>Equipos (5–10 personas)</span></div>
  <div class="stat"><b>60</b><span>Minutos de sesión</span></div>
  <div class="stat"><b><?= $totalPts ?></b><span>Puntos posibles</span></div>
  <div class="stat"><b>50–100</b><span>Participantes</span></div>
</section>

<section class="section">
  <div class="eyebrow">Cómo funciona</div>
  <h2>Cinco pasos, una sola sesión</h2>
  <div class="steps">
    <div class="step"><h3>Inicio</h3><p>El facilitador inicia la plataforma y abre el reto.</p></div>
    <div class="step"><h3>Conexión</h3><p>Los equipos entran desde computadora, tableta o celular.</p></div>
    <div class="step"><h3>Respuestas</h3><p>Los capitanes registran la respuesta final del equipo.</p></div>
    <div class="step"><h3>Puntos</h3><p>La plataforma valida y asigna los puntos de cada equipo.</p></div>
    <div class="step"><h3>Seguimiento</h3><p>El facilitador sigue el avance en el marcador y controla los tiempos.</p></div>
  </div>
</section>

<section class="section">
  <div class="eyebrow">Los retos</div>
  <h2>Lógica, memoria, descifrado, creatividad y rapidez</h2>
  <div class="grid g3">
    <?php foreach ($retos as $r): ?>
      <a class="reto-card" style="--c:<?= e($r['color']) ?>" href="<?= url('reto.php?r=' . e($r['slug'])) ?>">
        <div class="reto-head"><span class="reto-num"><?= (int)$r['orden'] ?></span><h3><?= e($r['nombre']) ?></h3><span class="reto-ico"><?= $r['icono'] ?></span></div>
        <p><?= e($r['resumen']) ?></p>
        <div class="reto-meta"><span><?= (int)$r['puntos_max'] ?> pts máx. · <?= (int)$r['duracion_min'] ?> min</span>
          <span class="badge b-<?= e($r['estado']) ?>"><?= estado_texto($r['estado']) ?></span></div>
      </a>
    <?php endforeach; ?>
    <div class="card" style="border-top:5px solid var(--gold)">
      <h3>🎯 Objetivo</h3>
      <p class="muted">Fomentar la integración, la comunicación y el pensamiento estratégico en un ambiente divertido y competitivo.</p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
