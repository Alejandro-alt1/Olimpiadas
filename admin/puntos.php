<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();

$retos = retos_all();
$porId = array_column($retos, null, 'id');
$retoId = (int)($_GET['reto'] ?? $_POST['reto_id'] ?? $retos[0]['id']);
if (!isset($porId[$retoId])) $retoId = (int)$retos[0]['id'];
$reto = $porId[$retoId];

if (is_post()) {
    $cambios = 0; $errores = 0;
    $actual = [];
    $q = db()->prepare('SELECT equipo_id, puntos FROM puntuaciones WHERE reto_id = ?'); $q->execute([$retoId]);
    foreach ($q as $p) $actual[$p['equipo_id']] = (int)$p['puntos'];

    $up = db()->prepare('INSERT INTO puntuaciones (equipo_id, reto_id, puntos, detalle) VALUES (?,?,?,?)
                         ON DUPLICATE KEY UPDATE puntos = VALUES(puntos), detalle = VALUES(detalle)');
    foreach ($_POST['p'] ?? [] as $eqId => $d) {
        $eqId = (int)$eqId;
        $pts = filter_var($d['puntos'] ?? '', FILTER_VALIDATE_INT);
        // Validación en servidor: entero entre 0 y el máximo del reto
        if ($pts === false || $pts < 0 || $pts > (int)$reto['puntos_max']) { $errores++; continue; }
        $nota = mb_substr(trim($d['detalle'] ?? ''), 0, 255);
        $antes = $actual[$eqId] ?? 0;
        $up->execute([$eqId, $retoId, $pts, $nota ?: null]);
        if ($antes !== $pts) {
            $cambios++;
            bitacora('puntos', "Puntos {$reto['nombre']}: {$antes} → {$pts}", $nota ?: null, $retoId, $eqId);
        }
    }
    if ($errores) flash("$errores valor(es) inválido(s) se omitieron (rango 0–{$reto['puntos_max']}).", 'err');
    flash($cambios ? "$cambios cambio(s) de puntos guardados." : 'Sin cambios en los puntos.');
    redirect('admin/puntos.php?reto=' . $retoId);
}

$s = db()->prepare('SELECT e.id, e.nombre, e.color, COALESCE(p.puntos,0) puntos, p.detalle
                    FROM equipos e LEFT JOIN puntuaciones p ON p.equipo_id = e.id AND p.reto_id = ?
                    WHERE e.activo = 1 ORDER BY e.id');
$s->execute([$retoId]);
$filas = $s->fetchAll();
$titulo = 'Registro de puntos'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Registro de resultados</div><h1>Puntos por reto</h1>
  <p>El servidor valida cada valor (entero entre 0 y el máximo del reto) y guarda una sola fila por equipo y reto: los puntos nunca se duplican al recargar. Cada cambio queda en la bitácora. <strong>Los retos se calculan solos al responder los equipos; usa esta pantalla solo para ajustes o respaldo manual</strong> (el siguiente envío de un equipo recalcula su puntaje de ese reto).</p></div>

<div class="tabs" style="flex-wrap:wrap">
  <?php foreach ($retos as $r): ?>
    <a class="<?= $r['id'] == $retoId ? 'on' : '' ?>" href="<?= url('admin/puntos.php?reto=' . (int)$r['id']) ?>"><?= $r['icono'] ?> <?= e($r['nombre']) ?></a>
  <?php endforeach; ?>
</div>

<form method="post">
  <?= csrf_field() ?><input type="hidden" name="reto_id" value="<?= $retoId ?>">
  <div class="card" style="margin-bottom:16px"><strong><?= e($reto['nombre']) ?></strong> · máximo <strong style="color:var(--gold)"><?= (int)$reto['puntos_max'] ?></strong> pts · <span class="muted"><?= e($reto['regla_puntos']) ?></span></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Equipo</th><th class="num">Puntos (0–<?= (int)$reto['puntos_max'] ?>)</th><th>Nota / criterio aplicado</th></tr></thead><tbody>
    <?php foreach ($filas as $f): ?>
      <tr>
        <td><span class="team"><i class="dot" style="--c:<?= e($f['color']) ?>"></i><?= e($f['nombre']) ?></span></td>
        <td class="num"><input type="number" min="0" max="<?= (int)$reto['puntos_max'] ?>" step="1" name="p[<?= (int)$f['id'] ?>][puntos]" value="<?= (int)$f['puntos'] ?>" style="width:110px;text-align:center" aria-label="Puntos <?= e($f['nombre']) ?>"></td>
        <td><input name="p[<?= (int)$f['id'] ?>][detalle]" value="<?= e($f['detalle']) ?>" maxlength="255" placeholder="Opcional" aria-label="Nota"></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <div style="margin-top:16px"><button class="btn btn-primary">💾 Guardar puntos</button></div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
