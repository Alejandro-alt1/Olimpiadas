<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();

if (is_post()) {
    $a = $_POST['accion'] ?? '';
    if (in_array($a, ['abrir', 'cerrar', 'reiniciar'], true)) {
        $id = (int)($_POST['reto_id'] ?? 0);
        $r = db()->prepare('SELECT * FROM retos WHERE id = ?'); $r->execute([$id]); $r = $r->fetch();
        if ($r) {
            if ($a === 'abrir') {
                // Solo un reto en curso a la vez
                db()->exec("UPDATE retos SET estado='cerrado' WHERE estado='abierto'");
                db()->prepare("UPDATE retos SET estado='abierto', inicio_at=NOW() WHERE id=?")->execute([$id]);
            } elseif ($a === 'cerrar') {
                db()->prepare("UPDATE retos SET estado='cerrado' WHERE id=?")->execute([$id]);
            } else {
                db()->prepare("UPDATE retos SET estado='pendiente', inicio_at=NULL WHERE id=?")->execute([$id]);
            }
            bitacora('sistema', ucfirst($a) . ' reto: ' . $r['nombre'], null, $id);
            flash('Reto "' . $r['nombre'] . '": acción "' . $a . '" aplicada.');
        }
    } elseif ($a === 'visibilidad') {
        $nuevo = config_get('marcador_visible', '1') === '1' ? '0' : '1';
        config_set('marcador_visible', $nuevo);
        bitacora('sistema', $nuevo === '1' ? 'Marcador visible para todos' : 'Marcador oculto para equipos');
        flash($nuevo === '1' ? 'El marcador ahora es visible.' : 'El marcador quedó oculto para los equipos.');
    }
    redirect('admin/index.php');
}

$retos = retos_all();
$visible = config_get('marcador_visible', '1') === '1';
$titulo = 'Panel del facilitador'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div class="eyebrow">Panel del facilitador</div><h1>Control de la sesión</h1>
  <p>Abre y cierra los retos, controla el marcador y administra equipos, puntos e incidencias.</p></div>

<div class="admin-grid" style="margin-bottom:30px">
  <a class="admin-link" href="<?= url('admin/equipos.php') ?>"><b>👥 Equipos</b><span>Altas, capitanes, integrantes y códigos de acceso.</span></a>
  <a class="admin-link" href="<?= url('admin/puntos.php') ?>"><b>🏅 Puntos</b><span>Registro de puntos por reto (validado en servidor).</span></a>
  <a class="admin-link" href="<?= url('admin/bitacora.php') ?>"><b>📒 Bitácora</b><span>Incidencias, decisiones y cambios auditados.</span></a>
  <a class="admin-link" href="<?= url('admin/preguntas.php') ?>"><b>❓ Preguntas y acertijos</b><span>Edita el contenido de los retos 1, 2, 3 y 5.</span></a>
  <a class="admin-link" href="<?= url('admin/respuestas.php') ?>"><b>📝 Respuestas</b><span>Qué envió cada capitán y reinicio de datos de prueba.</span></a>
  <a class="admin-link" href="<?= url('admin/evaluar.php') ?>"><b>💡 Evaluar creatividad</b><span>Rúbrica 40/35/25 con promedio de evaluadores.</span></a>
  <a class="admin-link" href="<?= url('admin/usuarios.php') ?>"><b>🔐 Evaluadores</b><span>Cuentas adicionales de facilitador.</span></a>
</div>

<div class="card">
  <h2>Retos</h2>
  <?php foreach ($retos as $r): $rest = reto_restante($r); ?>
  <div class="ctrl-row">
    <span class="reto-num" style="--c:<?= e($r['color']) ?>;background:<?= e($r['color']) ?>;width:38px;height:38px;border-radius:50%;display:grid;place-items:center;font-weight:800"><?= (int)$r['orden'] ?></span>
    <div class="name"><?= $r['icono'] ?> <?= e($r['nombre']) ?> <small class="muted">· <?= (int)$r['duracion_min'] ?> min · <?= (int)$r['puntos_max'] ?> pts</small></div>
    <span class="badge b-<?= e($r['estado']) ?>"><?= estado_texto($r['estado']) ?><?= $rest !== null ? ' · <span data-countdown="' . $rest . '" data-fin-text="00:00"></span>' : '' ?></span>
    <?php foreach ([['abrir', '▶ Abrir', 'btn-green', $r['estado'] !== 'abierto'], ['cerrar', '■ Cerrar', 'btn-danger', $r['estado'] === 'abierto'], ['reiniciar', '↺ Reiniciar', 'btn-ghost', $r['estado'] !== 'pendiente']] as [$ac, $tx, $cl, $on]): if (!$on) continue; ?>
      <form method="post" <?= $ac !== 'abrir' ? 'data-confirm="¿Confirmas la acción sobre este reto?"' : '' ?>>
        <?= csrf_field() ?><input type="hidden" name="accion" value="<?= $ac ?>"><input type="hidden" name="reto_id" value="<?= (int)$r['id'] ?>">
        <button class="btn btn-sm <?= $cl ?>"><?= $tx ?></button></form>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <p class="muted" style="margin:14px 0 0;font-size:.9rem">Al abrir un reto se cierra automáticamente el que estuviera en curso y arranca su cronómetro.</p>
</div>

<div class="card" style="margin-top:20px">
  <h2>Marcador</h2>
  <div class="ctrl-row">
    <div class="name">Visibilidad para los equipos: <span class="badge <?= $visible ? 'b-abierto' : 'b-cerrado' ?>"><?= $visible ? 'Visible' : 'Oculto' ?></span></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="visibilidad">
      <button class="btn btn-sm btn-gold"><?= $visible ? '🙈 Ocultar marcador' : '👁 Mostrar marcador' ?></button></form>
    <a class="btn btn-sm btn-ghost" href="<?= url('marcador.php') ?>" target="_blank">Abrir en pantalla compartida ↗</a>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
