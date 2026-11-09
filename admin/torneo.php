<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();

if (is_post()) {
    $a = $_POST['accion'] ?? '';
    $err = null; $ok = null;
    if ($a === 'config') {
        config_set('torneo_activo', isset($_POST['activo']) ? '1' : '0');
        config_set('torneo_rondas', (string)max(1, min(9, (int)($_POST['rondas'] ?? 5))));
        config_set('torneo_primera', ($_POST['primera'] ?? '') === 'sorteo' ? 'sorteo' : 'predefinido');
        bitacora('sistema', 'Torneo: configuración guardada (' . (isset($_POST['activo']) ? 'activo' : 'inactivo') . ')');
        $ok = 'Configuración del torneo guardada.';
    } elseif ($a === 'generar') {
        $err = torneo_generar_ronda((int)($_POST['reto_id'] ?? 0) ?: null, ($_POST['metodo'] ?? '') === 'mitades' ? 'mitades' : 'similares', isset($_POST['permitir']));
        $ok = 'Ronda generada. Abre el reto correspondiente para que los equipos jueguen.';
    } elseif ($a === 'manual') {
        $err = torneo_guardar_manual((int)($_POST['ronda_id'] ?? 0), $_POST['m'] ?? []);
        $ok = 'Ajustes manuales guardados.';
    } elseif ($a === 'cerrar') {
        $err = torneo_cerrar_ronda((int)($_POST['ronda_id'] ?? 0));
        $ok = 'Ronda cerrada: se asignaron los puntos de torneo.';
    } elseif ($a === 'reabrir') {
        $err = torneo_reabrir_ronda((int)($_POST['ronda_id'] ?? 0));
        $ok = 'Ronda reabierta.';
    } elseif ($a === 'eliminar') {
        $err = torneo_eliminar_ronda((int)($_POST['ronda_id'] ?? 0));
        $ok = 'Ronda eliminada.';
    } elseif ($a === 'reiniciar') {
        if (($_POST['confirmar'] ?? '') !== 'REINICIAR') $err = 'Escribe REINICIAR para confirmar.';
        else { torneo_reiniciar(); $ok = 'Torneo reiniciado.'; }
    }
    $err ? flash($err, 'err') : ($ok ? flash($ok) : null);
    redirect('admin/torneo.php');
}

$activo = torneo_activo();
$nRondas = torneo_num_rondas();
$rondas = $activo ? torneo_rondas() : [];
$rank = $activo ? ranking() : [];
$nEq = (int)db()->query('SELECT COUNT(*) FROM equipos WHERE activo = 1')->fetchColumn();
$enCurso = null; foreach ($rondas as $r) if ($r['estado'] === 'en_curso') $enCurso = $r;
$usados = array_filter(array_map(fn($r) => $r['reto_id'], $rondas));
$retos = retos_all();
$puedeGenerar = $activo && !$enCurso && count($rondas) < $nRondas;
$sigNum = count($rondas) + 1;
$titulo = 'Torneo suizo'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Torneo por enfrentamientos</div><h1>Torneo suizo</h1>
  <p>Cada ronda enfrenta a los equipos 1 contra 1 y la decide un reto: gana el equipo con más puntos en él (victoria <?= TORNEO_PTS_VICTORIA ?> · empate <?= TORNEO_PTS_EMPATE ?> · derrota <?= TORNEO_PTS_DERROTA ?>).
  Flujo: <strong>generar ronda → abrir el reto → cerrar el reto → cerrar la ronda</strong>. Público: <a href="<?= url('torneo.php') ?>" target="_blank">torneo.php ↗</a></p></div>

<div class="card" style="margin-bottom:22px"><h3>⚙️ Configuración</h3>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="config">
    <div class="row">
      <div class="field"><label style="display:flex;gap:10px;align-items:center"><input type="checkbox" name="activo" <?= $activo ? 'checked' : '' ?> style="width:auto"> Torneo activo (cambia el orden del marcador)</label></div>
      <div class="field"><label>Número de rondas (1–9)</label><input type="number" name="rondas" min="1" max="9" value="<?= $nRondas ?>"></div>
      <div class="field"><label>Primera ronda</label><select name="primera"><option value="predefinido" <?= config_get('torneo_primera') !== 'sorteo' ? 'selected' : '' ?>>Predefinida (1-6, 2-7, 3-8…)</option><option value="sorteo" <?= config_get('torneo_primera') === 'sorteo' ? 'selected' : '' ?>>Sorteo al azar</option></select></div>
    </div>
    <button class="btn btn-primary btn-sm">💾 Guardar</button>
    <span class="muted" style="margin-left:10px">Equipos activos: <strong><?= $nEq ?></strong><?= $nEq % 2 ? ' ⚠ se necesita un número par' : '' ?></span>
  </form></div>

<?php if (!$activo): ?>
  <div class="placeholder">Activa el torneo para generar rondas.</div>
<?php else: ?>

<?php if ($puedeGenerar): ?>
<div class="card" style="margin-bottom:22px;border-color:var(--gold)"><h3>➕ Generar ronda <?= $sigNum ?> de <?= $nRondas ?></h3>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="generar">
    <div class="row">
      <div class="field"><label>Reto que decide la ronda</label><select name="reto_id">
        <?php foreach ($retos as $r): if (in_array($r['id'], $usados)) continue; ?><option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $sigNum ? 'selected' : '' ?>><?= $r['icono'] ?> <?= e($r['nombre']) ?></option><?php endforeach; ?>
        <option value="0">Sin reto (resultado manual)</option></select></div>
      <?php if ($sigNum > 1): ?>
      <div class="field"><label>Método de emparejamiento</label><select name="metodo">
        <option value="similares">Equipos con puntuación parecida (suizo clásico)</option>
        <option value="mitades">Mitad superior vs. mitad inferior</option></select></div>
      <?php endif; ?>
    </div>
    <?php if ($sigNum > 1): ?><label style="display:flex;gap:10px;align-items:center;font-weight:600"><input type="checkbox" name="permitir" style="width:auto"> Permitir repetir rivales si no hay otra combinación</label><br><?php endif; ?>
    <button class="btn btn-gold">🎲 Generar enfrentamientos</button></form></div>
<?php endif; ?>

<h2>Clasificación</h2>
<div class="table-wrap"><table>
  <thead><tr><th>#</th><th>Equipo</th><th class="num">🏆 PT</th><th class="num">PJ</th><th class="num">G</th><th class="num">E</th><th class="num">P</th><th class="num">Pruebas</th></tr></thead><tbody>
  <?php foreach ($rank as $f): ?><tr><td class="pos"><?= medalla((int)$f['pos']) ?></td>
    <td><span class="team"><i class="dot" style="--c:<?= e($f['color']) ?>"></i><?= e($f['nombre']) ?><?= $f['empate'] ? ' <small class="tag">empate</small>' : '' ?></span></td>
    <td class="num total"><?= (int)$f['tp'] ?></td><td class="num"><?= (int)$f['pj'] ?></td><td class="num"><?= (int)$f['g'] ?></td><td class="num"><?= (int)$f['e'] ?></td><td class="num"><?= (int)$f['p'] ?></td><td class="num"><?= (int)$f['total'] ?></td></tr>
  <?php endforeach; ?></tbody></table></div>

<?php if (!$rondas): ?><div class="placeholder" style="margin-top:22px">Aún no hay rondas. Genera la primera arriba.</div><?php endif; ?>
<?php $ultimo = $rondas ? max(array_column($rondas, 'numero')) : 0;
foreach (array_reverse($rondas) as $r):
  $partidos = torneo_partidos((int)$r['id']);
  $live = $r['estado'] === 'en_curso' ? torneo_puntos_reto($r['reto_id'] ? (int)$r['reto_id'] : null) : []; ?>
  <div class="ronda-head"><h3>Ronda <?= (int)$r['numero'] ?></h3>
    <span class="tag"><?= $r['reto_id'] ? e($r['reto_icono'] . ' ' . $r['reto_nombre']) . ' · reto ' . e(estado_texto($r['reto_estado'])) : 'Sin reto: resultado manual' ?></span>
    <span class="badge <?= $r['estado'] === 'cerrada' ? 'b-cerrado' : 'b-abierto' ?>"><?= $r['estado'] === 'cerrada' ? 'Cerrada' : 'En curso' ?></span></div>

  <?php if ($r['estado'] === 'en_curso'): ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="manual"><input type="hidden" name="ronda_id" value="<?= (int)$r['id'] ?>">
  <div class="table-wrap"><table style="min-width:760px"><thead><tr><th>Equipo A</th><th class="num">Pts A</th><th class="num">Pts B</th><th>Equipo B</th><th>Resultado</th><th>Nota</th></tr></thead><tbody>
  <?php foreach ($partidos as $m): $pa = $live[(int)$m['equipo_a']] ?? 0; $pb = $live[(int)$m['equipo_b']] ?? 0; $auto = torneo_resultado_auto($pa, $pb);
        $sel = $m['manual'] ? ((int)$m['empate'] ? 'empate' : ((int)$m['ganador_id'] === (int)$m['equipo_a'] ? 'a' : 'b')) : 'auto'; ?>
    <tr><td><span class="team"><i class="dot" style="--c:<?= e($m['color_a']) ?>"></i><?= e($m['nombre_a']) ?></span></td>
      <td class="num total"><?= $r['reto_id'] ? $pa : '–' ?></td><td class="num total"><?= $r['reto_id'] ? $pb : '–' ?></td>
      <td><span class="team"><i class="dot" style="--c:<?= e($m['color_b']) ?>"></i><?= e($m['nombre_b']) ?></span></td>
      <td><select name="m[<?= (int)$m['id'] ?>][res]" aria-label="Resultado">
        <option value="auto" <?= $sel === 'auto' ? 'selected' : '' ?>><?= $r['reto_id'] ? 'Automático (hoy: ' . ($auto === 'a' ? 'gana A' : ($auto === 'b' ? 'gana B' : 'empate')) . ')' : 'Elegir…' ?></option>
        <option value="a" <?= $sel === 'a' ? 'selected' : '' ?>>Gana <?= e($m['nombre_a']) ?></option>
        <option value="b" <?= $sel === 'b' ? 'selected' : '' ?>>Gana <?= e($m['nombre_b']) ?></option>
        <option value="empate" <?= $sel === 'empate' ? 'selected' : '' ?>>Empate</option></select></td>
      <td><input name="m[<?= (int)$m['id'] ?>][nota]" value="<?= e($m['nota'] ?? '') ?>" maxlength="255" placeholder="Motivo (opcional)" aria-label="Nota"></td></tr>
  <?php endforeach; ?></tbody></table></div>
  <div class="sala-ctrl" style="margin-top:12px"><button class="btn btn-ghost btn-sm">💾 Guardar ajustes manuales</button></form>
    <?php if ($r['reto_id']): $abierto = $r['reto_estado'] === 'abierto'; ?>
      <form method="post" action="<?= url('admin/index.php') ?>"><?= csrf_field() ?><input type="hidden" name="volver" value="torneo"><input type="hidden" name="reto_id" value="<?= (int)$r['reto_id'] ?>">
        <?php if ($r['reto_estado'] === 'pendiente'): ?><input type="hidden" name="accion" value="abrir"><button class="btn btn-green btn-sm">▶ Abrir reto</button>
        <?php elseif ($abierto): ?><input type="hidden" name="accion" value="cerrar"><button class="btn btn-danger btn-sm" data-confirm-btn="¿Cerrar el reto ahora?">■ Cerrar reto</button><?php endif; ?></form>
    <?php endif; ?>
    <form method="post" data-confirm="Se asignarán los puntos de torneo de esta ronda. ¿Cerrar la ronda?"><?= csrf_field() ?><input type="hidden" name="accion" value="cerrar"><input type="hidden" name="ronda_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-gold btn-sm">✔ Cerrar ronda y asignar puntos</button></form>
    <form method="post" data-confirm="Se borra esta ronda para poder generarla de nuevo. ¿Continuar?"><?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="ronda_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-ghost btn-sm">🗑 Eliminar ronda</button></form>
  </div>

  <?php else: ?>
  <div class="match-list"><?php foreach ($partidos as $m): $emp = (int)$m['empate'] === 1; $gan = (int)$m['ganador_id']; $cls = $emp ? '' : ($gan === (int)$m['equipo_a'] ? 'win-a' : 'win-b'); ?>
    <div class="match <?= $cls ?>"><div class="tm"><i class="dot" style="--c:<?= e($m['color_a']) ?>"></i><?= e($m['nombre_a']) ?></div>
      <div class="sc"><?= $m['puntos_a'] === null ? '–' : (int)$m['puntos_a'] ?> : <?= $m['puntos_b'] === null ? '–' : (int)$m['puntos_b'] ?></div>
      <div class="tm r"><?= e($m['nombre_b']) ?><i class="dot" style="--c:<?= e($m['color_b']) ?>"></i></div>
      <div class="res"><?= $emp ? '🤝 Empate' : '🏆 Gana ' . e($gan === (int)$m['equipo_a'] ? $m['nombre_a'] : $m['nombre_b']) ?><?= $m['manual'] ? ' · fijado a mano' : '' ?><?= $m['nota'] ? ' · ' . e($m['nota']) : '' ?></div></div>
  <?php endforeach; ?></div>
  <?php if ((int)$r['numero'] === $ultimo): ?>
    <form method="post" style="margin-top:10px" data-confirm="Los puntos de torneo de esta ronda se quitan hasta que la vuelvas a cerrar. ¿Reabrir?"><?= csrf_field() ?><input type="hidden" name="accion" value="reabrir"><input type="hidden" name="ronda_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-ghost btn-sm">↺ Reabrir esta ronda</button></form>
  <?php endif; endif; endforeach; ?>

<div class="card" style="margin-top:34px;border-color:#ef444488"><h3>⚠️ Reiniciar torneo</h3>
  <p class="muted">Borra todas las rondas y enfrentamientos (no toca respuestas ni puntos de los retos).</p>
  <form method="post" class="form-inline"><?= csrf_field() ?><input type="hidden" name="accion" value="reiniciar">
    <input name="confirmar" placeholder="Escribe REINICIAR" style="max-width:220px" autocomplete="off"><button class="btn btn-danger btn-sm">Reiniciar torneo</button></form></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
