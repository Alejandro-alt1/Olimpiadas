<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();

$retos = retos_all();
$porId = array_column($retos, null, 'id');
$rid = (int)($_GET['reto'] ?? $_POST['reto_id'] ?? 1);
if (!isset($porId[$rid])) $rid = 1;
$reto = $porId[$rid];

if (is_post() && ($_POST['accion'] ?? '') === 'reiniciar_datos') {
    if (($_POST['confirmar'] ?? '') !== 'REINICIAR') { flash('Escribe REINICIAR para confirmar.', 'err'); redirect('admin/respuestas.php?reto=' . $rid); }
    $pdo = db();
    if (in_array($rid, [1, 2, 5], true)) {
        $pdo->prepare('DELETE FROM progreso_pregunta WHERE pregunta_id IN (SELECT id FROM preguntas WHERE reto_id = ?)')->execute([$rid]);
        $pdo->prepare('DELETE FROM respuestas WHERE reto_id = ?')->execute([$rid]);
        if ($rid === 2) $pdo->exec('DELETE FROM sesiones WHERE reto_id = 2');
    } elseif ($rid === 3) {
        $pdo->exec('DELETE FROM intentos_codigo');
    } elseif ($rid === 4) {
        foreach ($pdo->query('SELECT imagen FROM propuestas WHERE imagen IS NOT NULL')->fetchAll() as $im) {
            $f = __DIR__ . '/../uploads/' . basename($im['imagen']); if (is_file($f)) @unlink($f);
        }
        $pdo->exec('DELETE FROM propuestas'); // evaluaciones se borran en cascada
    }
    $pdo->prepare('UPDATE puntuaciones SET puntos = 0, detalle = NULL WHERE reto_id = ?')->execute([$rid]);
    $pdo->prepare("UPDATE retos SET estado='pendiente', inicio_at=NULL WHERE id=?")->execute([$rid]);
    bitacora('sistema', 'Datos del reto reiniciados: ' . $reto['nombre'], 'Respuestas, progreso y puntos del reto puestos en cero', $rid);
    flash('Datos del reto reiniciados.');
    redirect('admin/respuestas.php?reto=' . $rid);
}

$titulo = 'Respuestas'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Registro de respuestas</div><h1>Respuestas de los capitanes</h1>
  <p>Consulta lo que envió cada equipo y verifica los puntos antes de cerrar el marcador.</p></div>
<div class="tabs" style="flex-wrap:wrap"><?php foreach ($retos as $r): ?>
  <a class="<?= $r['id'] == $rid ? 'on' : '' ?>" href="<?= url('admin/respuestas.php?reto=' . (int)$r['id']) ?>"><?= $r['icono'] ?> <?= e($r['nombre']) ?></a><?php endforeach; ?></div>

<?php if (in_array($rid, [1, 2, 5], true)):
  $s = db()->prepare('SELECT r.*, e.nombre AS equipo, p.orden, p.enunciado, p.correcta AS clave FROM respuestas r
        JOIN equipos e ON e.id = r.equipo_id JOIN preguntas p ON p.id = r.pregunta_id WHERE r.reto_id = ? ORDER BY e.id, p.orden');
  $s->execute([$rid]); $filas = $s->fetchAll(); ?>
  <div class="table-wrap"><table><thead><tr><th>Equipo</th><th class="num">#</th><th>Pregunta</th><th class="num">Respuesta</th><th class="num">Clave</th><th class="num">Pts</th><th>Justificación</th><th>Hora</th></tr></thead><tbody>
  <?php foreach ($filas as $f): ?>
    <tr><td><?= e($f['equipo']) ?></td><td class="num"><?= (int)$f['orden'] ?></td><td><?= e(mb_strimwidth($f['enunciado'], 0, 70, '…')) ?></td>
      <td class="num"><?= $f['respuesta'] ? e($f['respuesta']) : '<span class="muted">— (sin envío)</span>' ?></td><td class="num"><?= e($f['clave']) ?></td>
      <td class="num"><?= $f['correcta'] ? '✅ ' : '' ?><?= (int)$f['puntos'] ?></td><td><?= e($f['justificacion'] ?? '') ?></td>
      <td style="white-space:nowrap"><?= e(date('H:i:s', strtotime($f['created_at']))) ?></td></tr>
  <?php endforeach; if (!$filas): ?><tr><td colspan="8" class="muted center">Sin respuestas todavía.</td></tr><?php endif; ?></tbody></table></div>

<?php elseif ($rid === 3):
  $filas = db()->query('SELECT ic.*, e.nombre AS equipo, a.orden FROM intentos_codigo ic JOIN equipos e ON e.id = ic.equipo_id JOIN acertijos a ON a.id = ic.acertijo_id ORDER BY e.id, a.orden')->fetchAll(); ?>
  <div class="table-wrap"><table><thead><tr><th>Equipo</th><th class="num">Acertijo</th><th class="num">Intentos</th><th class="num">Pista</th><th class="num">Resuelto</th><th class="num">Pts</th></tr></thead><tbody>
  <?php foreach ($filas as $f): ?><tr><td><?= e($f['equipo']) ?></td><td class="num"><?= (int)$f['orden'] ?></td><td class="num"><?= (int)$f['intentos'] ?></td>
    <td class="num"><?= $f['pista_usada'] ? 'Sí' : 'No' ?></td><td class="num"><?= $f['resuelto'] ? '🔓' : '🔒' ?></td><td class="num"><?= (int)$f['puntos'] ?></td></tr>
  <?php endforeach; if (!$filas): ?><tr><td colspan="6" class="muted center">Sin intentos todavía.</td></tr><?php endif; ?></tbody></table></div>

<?php else: $n = (int)db()->query('SELECT COUNT(*) FROM propuestas')->fetchColumn(); ?>
  <div class="card"><p><strong><?= $n ?></strong> propuesta(s) enviada(s). Evalúalas con la rúbrica en el módulo de evaluación.</p>
    <a class="btn btn-gold" href="<?= url('admin/evaluar.php') ?>">Ir a evaluar creatividad</a></div>
<?php endif; ?>

<div class="card" style="margin-top:28px;border-color:#ef444488"><h3>⚠️ Reiniciar datos de este reto</h3>
  <p class="muted">Borra respuestas, progreso y puntos de <strong><?= e($reto['nombre']) ?></strong> para todos los equipos y lo regresa a “Pendiente”. Úsalo para limpiar pruebas antes del evento. No se puede deshacer.</p>
  <form method="post" class="form-inline"><?= csrf_field() ?><input type="hidden" name="accion" value="reiniciar_datos"><input type="hidden" name="reto_id" value="<?= $rid ?>">
    <input name="confirmar" placeholder="Escribe REINICIAR" style="max-width:220px" autocomplete="off"><button class="btn btn-danger btn-sm">Reiniciar datos</button></form></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
