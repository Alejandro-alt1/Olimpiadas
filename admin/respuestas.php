<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();
require_once __DIR__ . '/../includes/retos/creatividad.php';

$retos = retos_all();
$porId = array_column($retos, null, 'id');
$rid = (int)($_GET['reto'] ?? $_POST['reto_id'] ?? 1);
if (!isset($porId[$rid])) $rid = 1;
$reto = $porId[$rid];
$fEq = (int)($_GET['equipo'] ?? 0);

if (is_post()) {
    $a = $_POST['accion'] ?? '';
    if ($a === 'reiniciar_datos') {
        if (($_POST['confirmar'] ?? '') !== 'REINICIAR') { flash('Escribe REINICIAR para confirmar.', 'err'); redirect('admin/respuestas.php?reto=' . $rid); }
        $pdo = db();
        if (in_array($rid, [1, 2, 5], true)) {
            $pdo->prepare('DELETE FROM progreso_pregunta WHERE pregunta_id IN (SELECT id FROM preguntas WHERE reto_id = ?)')->execute([$rid]);
            $pdo->prepare('DELETE FROM respuestas WHERE reto_id = ?')->execute([$rid]);
            if ($rid === 2) $pdo->exec('DELETE FROM sesiones WHERE reto_id = 2');
        } elseif ($rid === 3) {
            $pdo->exec('DELETE FROM intentos_codigo');
        } elseif ($rid === 4) {
            foreach ([['propuestas'], ['borradores']] as [$tb]) foreach ($pdo->query("SELECT imagen FROM $tb WHERE imagen IS NOT NULL")->fetchAll() as $im) {
                $f = __DIR__ . '/../uploads/' . basename($im['imagen']); if (is_file($f)) @unlink($f);
            }
            $pdo->exec('DELETE FROM propuestas');          // evaluaciones se borran en cascada
            $pdo->exec('DELETE FROM borrador_listos');
            $pdo->exec('DELETE FROM borradores');
        }
        $pdo->prepare('UPDATE puntuaciones SET puntos = 0, detalle = NULL WHERE reto_id = ?')->execute([$rid]);
        $pdo->prepare("UPDATE retos SET estado='pendiente', inicio_at=NULL WHERE id=?")->execute([$rid]);
        bitacora('sistema', 'Datos del reto reiniciados: ' . $reto['nombre'], 'Respuestas, progreso y puntos del reto puestos en cero', $rid);
        flash('Datos del reto reiniciados.');
        redirect('admin/respuestas.php?reto=' . $rid);
    }
    if ($a === 'enviar_borradores') {
        $n = 0;
        foreach (db()->query('SELECT equipo_id FROM borradores')->fetchAll() as $b) if (cr_enviar((int)$b['equipo_id'])) $n++;
        bitacora('sistema', "Borradores completos enviados por el facilitador: $n", null, 4);
        flash("$n borrador(es) completo(s) enviados como propuesta.");
        redirect('admin/respuestas.php?reto=4');
    }
    if ($a === 'recalcular') {
        recalcular_todos();
        bitacora('sistema', 'Puntos recalculados para todos los equipos');
        flash('Marcador recalculado a partir de las respuestas.');
        redirect('admin/respuestas.php?reto=' . $rid);
    }
}

$equipos = db()->query('SELECT id, nombre FROM equipos ORDER BY id')->fetchAll();
$titulo = 'Respuestas'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Registro de respuestas</div><h1>Respuestas de los participantes</h1>
  <p>Consulta lo que envió cada persona y verifica los puntos. Máximo 500 filas por consulta: filtra por equipo si hace falta.</p></div>
<div class="tabs" style="flex-wrap:wrap"><?php foreach ($retos as $r): ?>
  <a class="<?= $r['id'] == $rid ? 'on' : '' ?>" href="<?= url('admin/respuestas.php?reto=' . (int)$r['id']) ?>"><?= $r['icono'] ?> <?= e($r['nombre']) ?></a><?php endforeach; ?></div>

<?php if ($rid !== 4): ?>
  <form method="get" class="form-inline" style="margin-bottom:14px"><input type="hidden" name="reto" value="<?= $rid ?>">
    <select name="equipo" style="max-width:240px" onchange="this.form.submit()"><option value="0">Todos los equipos</option>
      <?php foreach ($equipos as $e): ?><option value="<?= (int)$e['id'] ?>" <?= $fEq === (int)$e['id'] ? 'selected' : '' ?>><?= e($e['nombre']) ?></option><?php endforeach; ?></select></form>
<?php endif; ?>

<?php if (in_array($rid, [1, 2, 5], true)):
  $sql = 'SELECT r.*, pa.alias, e.nombre AS equipo, p.orden, p.enunciado, p.correcta AS clave FROM respuestas r
          JOIN participantes pa ON pa.id = r.participante_id JOIN equipos e ON e.id = r.equipo_id JOIN preguntas p ON p.id = r.pregunta_id
          WHERE r.reto_id = ?' . ($fEq ? ' AND r.equipo_id = ' . $fEq : '') . ' ORDER BY e.id, pa.alias, p.orden LIMIT 500';
  $s = db()->prepare($sql); $s->execute([$rid]); $filas = $s->fetchAll(); ?>
  <div class="table-wrap"><table><thead><tr><th>Equipo</th><th>Alias</th><th class="num">#</th><th>Pregunta</th><th class="num">Resp.</th><th class="num">Clave</th><th class="num">Pts</th><th>Justificación</th><th>Hora</th></tr></thead><tbody>
  <?php foreach ($filas as $f): ?>
    <tr><td><?= e($f['equipo']) ?></td><td><?= e($f['alias']) ?></td><td class="num"><?= (int)$f['orden'] ?></td><td><?= e(mb_strimwidth($f['enunciado'], 0, 55, '…')) ?></td>
      <td class="num"><?= $f['respuesta'] ? e($f['respuesta']) : '<span class="muted">—</span>' ?></td><td class="num"><?= e($f['clave']) ?></td>
      <td class="num"><?= $f['correcta'] ? '✅ ' : '' ?><?= (int)$f['puntos'] ?></td><td><?= e($f['justificacion'] ?? '') ?></td>
      <td style="white-space:nowrap"><?= e(date('H:i:s', strtotime($f['created_at']))) ?></td></tr>
  <?php endforeach; if (!$filas): ?><tr><td colspan="9" class="muted center">Sin respuestas todavía.</td></tr><?php endif; ?></tbody></table></div>

<?php elseif ($rid === 3):
  $sql = 'SELECT ic.*, pa.alias, e.nombre AS equipo, a.orden FROM intentos_codigo ic JOIN participantes pa ON pa.id = ic.participante_id
          JOIN equipos e ON e.id = ic.equipo_id JOIN acertijos a ON a.id = ic.acertijo_id' . ($fEq ? ' WHERE ic.equipo_id = ' . $fEq : '') . ' ORDER BY e.id, pa.alias, a.orden LIMIT 500';
  $filas = db()->query($sql)->fetchAll(); ?>
  <div class="table-wrap"><table><thead><tr><th>Equipo</th><th>Alias</th><th class="num">Acertijo</th><th class="num">Intentos</th><th class="num">Pista</th><th class="num">Resuelto</th><th class="num">Pts</th></tr></thead><tbody>
  <?php foreach ($filas as $f): ?><tr><td><?= e($f['equipo']) ?></td><td><?= e($f['alias']) ?></td><td class="num"><?= (int)$f['orden'] ?></td><td class="num"><?= (int)$f['intentos'] ?></td>
    <td class="num"><?= $f['pista_usada'] ? 'Sí' : 'No' ?></td><td class="num"><?= $f['resuelto'] ? '🔓' : '🔒' ?></td><td class="num"><?= (int)$f['puntos'] ?></td></tr>
  <?php endforeach; if (!$filas): ?><tr><td colspan="7" class="muted center">Sin intentos todavía.</td></tr><?php endif; ?></tbody></table></div>

<?php else:
  $filas = db()->query('SELECT e.id, e.nombre, (SELECT COUNT(*) FROM propuestas p WHERE p.equipo_id = e.id) enviada,
         (SELECT nombre FROM borradores b WHERE b.equipo_id = e.id) bnombre,
         (SELECT COUNT(*) FROM borrador_listos l WHERE l.equipo_id = e.id) listos FROM equipos e WHERE e.activo = 1 ORDER BY e.id')->fetchAll(); ?>
  <div class="table-wrap"><table><thead><tr><th>Equipo</th><th>Borrador</th><th class="num">“Listo”</th><th class="num">Propuesta enviada</th></tr></thead><tbody>
  <?php foreach ($filas as $f): ?><tr><td><?= e($f['nombre']) ?></td><td><?= $f['bnombre'] !== null && $f['bnombre'] !== '' ? e($f['bnombre']) : '<span class="muted">—</span>' ?></td>
    <td class="num"><?= (int)$f['listos'] ?></td><td class="num"><?= $f['enviada'] ? '✅' : '—' ?></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="sala-ctrl" style="margin-top:16px">
    <form method="post" data-confirm="Se enviarán como propuesta los borradores completos que aún no se hayan enviado. ¿Continuar?"><?= csrf_field() ?><input type="hidden" name="accion" value="enviar_borradores">
      <button class="btn btn-gold btn-sm">📤 Enviar borradores completos pendientes</button></form>
    <a class="btn btn-ghost btn-sm" href="<?= url('admin/evaluar.php') ?>">Ir a evaluar creatividad</a></div>
<?php endif; ?>

<div class="card" style="margin-top:28px"><h3>🧮 Recalcular marcador</h3>
  <p class="muted">Vuelve a calcular el puntaje de todos los equipos a partir de las respuestas guardadas (promedio de los individuales).</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="recalcular"><input type="hidden" name="reto_id" value="<?= $rid ?>"><button class="btn btn-ghost btn-sm">Recalcular ahora</button></form></div>

<div class="card" style="margin-top:20px;border-color:#ef444488"><h3>⚠️ Reiniciar datos de este reto</h3>
  <p class="muted">Borra respuestas, progreso y puntos de <strong><?= e($reto['nombre']) ?></strong> para todos los equipos y lo regresa a “Pendiente”. Úsalo para limpiar pruebas antes del evento. No se puede deshacer.</p>
  <form method="post" class="form-inline"><?= csrf_field() ?><input type="hidden" name="accion" value="reiniciar_datos"><input type="hidden" name="reto_id" value="<?= $rid ?>">
    <input name="confirmar" placeholder="Escribe REINICIAR" style="max-width:220px" autocomplete="off"><button class="btn btn-danger btn-sm">Reiniciar datos</button></form></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
