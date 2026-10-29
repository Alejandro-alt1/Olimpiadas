<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();
$yo = (int)admin_actual()['id'];

if (is_post()) {
    $up = db()->prepare('INSERT INTO evaluaciones (propuesta_id, usuario_id, originalidad, utilidad, claridad) VALUES (?,?,?,?,?)
                         ON DUPLICATE KEY UPDATE originalidad = VALUES(originalidad), utilidad = VALUES(utilidad), claridad = VALUES(claridad)');
    $n = 0; $err = 0;
    foreach ($_POST['ev'] ?? [] as $pid => $d) {
        if (($d['o'] ?? '') === '' && ($d['u'] ?? '') === '' && ($d['c'] ?? '') === '') continue;
        $o = filter_var($d['o'] ?? '', FILTER_VALIDATE_INT); $u = filter_var($d['u'] ?? '', FILTER_VALIDATE_INT); $c = filter_var($d['c'] ?? '', FILTER_VALIDATE_INT);
        if ($o === false || $u === false || $c === false || $o < 0 || $o > 40 || $u < 0 || $u > 35 || $c < 0 || $c > 25) { $err++; continue; }
        $p = db()->prepare('SELECT equipo_id FROM propuestas WHERE id = ?'); $p->execute([(int)$pid]); $eq = (int)$p->fetchColumn();
        if (!$eq) continue;
        $up->execute([(int)$pid, $yo, $o, $u, $c]);
        recalcular_puntos($eq, 4);
        bitacora('puntos', "Evaluación Creatividad: Orig $o · Util $u · Clar $c (total " . ($o + $u + $c) . ')', null, 4, $eq);
        $n++;
    }
    if ($err) flash("$err evaluación(es) con valores fuera de rango (Originalidad 0–40, Utilidad 0–35, Claridad 0–25).", 'err');
    flash("$n evaluación(es) guardadas. El marcador se actualizó con el promedio de evaluadores.");
    redirect('admin/evaluar.php');
}

$props = db()->query('SELECT p.*, e.nombre AS equipo, e.color FROM propuestas p JOIN equipos e ON e.id = p.equipo_id ORDER BY p.enviada_at')->fetchAll();
$mis = []; $prom = [];
foreach (db()->query('SELECT * FROM evaluaciones') as $ev) {
    if ((int)$ev['usuario_id'] === $yo) $mis[$ev['propuesta_id']] = $ev;
    $prom[$ev['propuesta_id']][] = $ev['originalidad'] + $ev['utilidad'] + $ev['claridad'];
}
$titulo = 'Evaluar creatividad'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Creatividad Sin Límites</div><h1>Evaluación con rúbrica</h1>
  <p>Cada evaluador puntúa de forma independiente (Originalidad 40 · Utilidad 35 · Claridad 25). El marcador usa el <strong>promedio</strong> de los evaluadores. Evaluando como: <strong><?= e(admin_actual()['nombre']) ?></strong>.</p></div>
<?php if (!$props): ?><div class="placeholder">Aún no hay propuestas enviadas.</div><?php else: ?>
<form method="post"><?= csrf_field() ?>
<?php foreach ($props as $p): $id = (int)$p['id']; $m = $mis[$id] ?? null; $ts = $prom[$id] ?? []; ?>
  <details class="prop" open>
    <summary><span class="team"><i class="dot" style="--c:<?= e($p['color']) ?>"></i><?= e($p['equipo']) ?></span> · <?= e($p['nombre']) ?>
      <span class="tag"><?= count($ts) ?> evaluador(es)<?= $ts ? ' · promedio ' . round(array_sum($ts) / count($ts)) : '' ?></span></summary>
    <div style="margin-top:14px">
      <p><strong>Problema:</strong> <?= nl2br(e($p['problema'])) ?></p>
      <p><strong>Solución:</strong> <?= nl2br(e($p['descripcion'])) ?></p>
      <p><strong>Beneficia a:</strong> <?= e($p['publico']) ?></p>
      <p><strong>Beneficio / cómo probarlo:</strong> <?= nl2br(e($p['beneficio'])) ?></p>
      <?php if ($p['imagen']): ?><img class="prop-img" src="<?= url('uploads/' . e($p['imagen'])) ?>" alt="Imagen de <?= e($p['equipo']) ?>"><?php endif; ?>
      <div class="row" style="margin-top:16px">
        <div class="field"><label>Originalidad (0–40)</label><input type="number" min="0" max="40" name="ev[<?= $id ?>][o]" value="<?= $m ? (int)$m['originalidad'] : '' ?>"></div>
        <div class="field"><label>Utilidad (0–35)</label><input type="number" min="0" max="35" name="ev[<?= $id ?>][u]" value="<?= $m ? (int)$m['utilidad'] : '' ?>"></div>
        <div class="field"><label>Claridad (0–25)</label><input type="number" min="0" max="25" name="ev[<?= $id ?>][c]" value="<?= $m ? (int)$m['claridad'] : '' ?>"></div>
      </div>
    </div>
  </details>
<?php endforeach; ?>
  <button class="btn btn-primary">💾 Guardar mis evaluaciones</button></form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
