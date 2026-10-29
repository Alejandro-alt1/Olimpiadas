<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();

$tabs = [1 => 'Mentes Brillantes', 2 => 'Memoria Extrema', 5 => 'Desafío Relámpago', 3 => 'Código Secreto'];
$t = (int)($_GET['t'] ?? $_POST['t'] ?? 1);
if (!isset($tabs[$t])) $t = 1;

if (is_post()) {
    $a = $_POST['accion'] ?? '';
    if ($a === 'preguntas') {
        $n = 0;
        $up = db()->prepare('UPDATE preguntas SET enunciado=?, opcion_a=?, opcion_b=?, opcion_c=?, opcion_d=?, correcta=?, puntos=?, tiempo_seg=? WHERE id=? AND reto_id=?');
        foreach ($_POST['p'] ?? [] as $id => $d) {
            $corr = strtoupper($d['correcta'] ?? '');
            $campos = [trim($d['enunciado'] ?? ''), trim($d['a'] ?? ''), trim($d['b'] ?? ''), trim($d['c'] ?? ''), trim($d['d'] ?? '')];
            if (in_array('', $campos, true) || !in_array($corr, ['A', 'B', 'C', 'D'], true)) { flash("Pregunta #$id incompleta: se omitió.", 'err'); continue; }
            $up->execute([...$campos, $corr, max(0, min(100, (int)$d['puntos'])), max(0, min(600, (int)$d['tiempo'])), (int)$id, $t]);
            $n++;
        }
        if ($t === 2) {
            $uo = db()->prepare('UPDATE memoria_objetos SET emoji=?, nombre=? WHERE posicion=?');
            foreach ($_POST['o'] ?? [] as $pos => $d) if (trim($d['emoji'] ?? '') !== '' && trim($d['nombre'] ?? '') !== '')
                $uo->execute([mb_substr(trim($d['emoji']), 0, 8), mb_substr(trim($d['nombre']), 0, 40), (int)$pos]);
        }
        bitacora('sistema', "Preguntas editadas ({$tabs[$t]}): $n guardadas", null, $t);
        flash("$n pregunta(s) guardadas.");
    } elseif ($a === 'acertijos') {
        $up = db()->prepare('UPDATE acertijos SET enunciado=?, respuestas=?, pista=?, fragmento=? WHERE id=?');
        foreach ($_POST['ac'] ?? [] as $id => $d) {
            $v = [trim($d['enunciado'] ?? ''), trim($d['respuestas'] ?? ''), trim($d['pista'] ?? ''), mb_substr(trim($d['fragmento'] ?? ''), 0, 4)];
            if (in_array('', $v, true)) { flash("Acertijo #$id incompleto: se omitió.", 'err'); continue; }
            $up->execute([...$v, (int)$id]);
        }
        bitacora('sistema', 'Acertijos editados', null, 3);
        flash('Acertijos guardados.');
    }
    redirect('admin/preguntas.php?t=' . $t);
}

$titulo = 'Preguntas'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Contenido de los retos</div><h1>Preguntas y acertijos</h1>
  <p>Edita el contenido antes del evento. <strong>No lo modifiques mientras los equipos juegan.</strong> Cantidad fija según la guía: 8 · 5 · 6 · 3.</p></div>
<div class="tabs" style="flex-wrap:wrap"><?php foreach ($tabs as $k => $v): ?>
  <a class="<?= $k === $t ? 'on' : '' ?>" href="<?= url('admin/preguntas.php?t=' . $k) ?>"><?= e($v) ?></a><?php endforeach; ?></div>

<?php if ($t === 3):
  $ac = db()->query('SELECT * FROM acertijos ORDER BY orden')->fetchAll(); ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="acertijos"><input type="hidden" name="t" value="3">
  <?php foreach ($ac as $a): $i = (int)$a['id']; ?>
    <div class="card" style="margin-bottom:16px"><h3>Acertijo <?= (int)$a['orden'] ?></h3>
      <div class="field"><label>Enunciado</label><textarea name="ac[<?= $i ?>][enunciado]" rows="2" maxlength="500" required><?= e($a['enunciado']) ?></textarea></div>
      <div class="field"><label>Respuestas aceptadas (separadas por | )</label><input name="ac[<?= $i ?>][respuestas]" value="<?= e($a['respuestas']) ?>" required></div>
      <div class="row"><div class="field"><label>Pista</label><input name="ac[<?= $i ?>][pista]" value="<?= e($a['pista']) ?>" required></div>
        <div class="field" style="max-width:200px"><label>Fragmento del código</label><input name="ac[<?= $i ?>][fragmento]" value="<?= e($a['fragmento']) ?>" maxlength="4" required></div></div>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-primary">💾 Guardar acertijos</button></form>

<?php else:
  $ps = db()->prepare('SELECT * FROM preguntas WHERE reto_id = ? ORDER BY orden'); $ps->execute([$t]); $ps = $ps->fetchAll(); ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="preguntas"><input type="hidden" name="t" value="<?= $t ?>">
  <?php if ($t === 2): $ob = db()->query('SELECT * FROM memoria_objetos ORDER BY posicion')->fetchAll(); ?>
    <div class="card" style="margin-bottom:18px"><h3>Tablero de objetos (4 columnas × 3 filas, por posición)</h3>
      <p class="muted">Si cambias un objeto, revisa que las preguntas de abajo sigan siendo correctas.</p>
      <div class="grid" style="grid-template-columns:repeat(4,1fr)"><?php foreach ($ob as $o): $i = (int)$o['posicion']; ?>
        <div><label>#<?= $i ?></label><div class="form-inline" style="flex-wrap:nowrap"><input name="o[<?= $i ?>][emoji]" value="<?= e($o['emoji']) ?>" style="width:70px;text-align:center"><input name="o[<?= $i ?>][nombre]" value="<?= e($o['nombre']) ?>"></div></div>
      <?php endforeach; ?></div></div>
  <?php endif; ?>
  <?php foreach ($ps as $p): $i = (int)$p['id']; ?>
    <div class="card" style="margin-bottom:16px"><h3>Pregunta <?= (int)$p['orden'] ?> <?= $p['tipo'] === 'decision' ? '<span class="tag">toma de decisiones</span>' : '' ?></h3>
      <div class="field"><label>Enunciado</label><textarea name="p[<?= $i ?>][enunciado]" rows="2" maxlength="500" required><?= e($p['enunciado']) ?></textarea></div>
      <div class="row"><?php foreach (['a', 'b', 'c', 'd'] as $l): ?>
        <div class="field"><label>Opción <?= strtoupper($l) ?></label><input name="p[<?= $i ?>][<?= $l ?>]" value="<?= e($p['opcion_' . $l]) ?>" maxlength="200" required></div>
      <?php endforeach; ?></div>
      <div class="row">
        <div class="field"><label>Correcta</label><select name="p[<?= $i ?>][correcta]"><?php foreach (['A', 'B', 'C', 'D'] as $l): ?><option <?= $p['correcta'] === $l ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Puntos</label><input type="number" name="p[<?= $i ?>][puntos]" value="<?= (int)$p['puntos'] ?>" min="0" max="100"></div>
        <div class="field"><label>Tiempo (seg, 0 = sin límite individual)</label><input type="number" name="p[<?= $i ?>][tiempo]" value="<?= (int)$p['tiempo_seg'] ?>" min="0" max="600" <?= $t === 2 ? 'readonly' : '' ?>></div>
      </div>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-primary">💾 Guardar</button></form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
