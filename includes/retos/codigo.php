<?php
function codigo_estado(int $eq): array {
    $s = db()->prepare('SELECT a.*, COALESCE(ic.resuelto,0) resuelto, COALESCE(ic.pista_usada,0) pista_usada,
                               COALESCE(ic.intentos,0) intentos, COALESCE(ic.puntos,0) puntos
                        FROM acertijos a LEFT JOIN intentos_codigo ic ON ic.acertijo_id = a.id AND ic.equipo_id = ?
                        ORDER BY a.orden');
    $s->execute([$eq]);
    return $s->fetchAll();
}
function codigo_actual(array $filas): ?array {
    foreach ($filas as $f) if (!$f['resuelto']) return $f;
    return null;
}
function codigo_post(array $reto, array $equipo): void {
    if (!reto_activo($reto)) { flash('El reto no está en curso.', 'err'); return; }
    $eq = (int)$equipo['id'];
    $act = codigo_actual(codigo_estado($eq));
    if (!$act || (int)$act['id'] !== (int)($_POST['acertijo_id'] ?? 0)) { flash('Ese acertijo no es el actual.', 'err'); return; }
    $ac = (int)$act['id'];
    db()->prepare('INSERT IGNORE INTO intentos_codigo (equipo_id, acertijo_id) VALUES (?,?)')->execute([$eq, $ac]);

    if (($_POST['accion'] ?? '') === 'pista') {
        db()->prepare('UPDATE intentos_codigo SET pista_usada = 1 WHERE equipo_id = ? AND acertijo_id = ? AND resuelto = 0')->execute([$eq, $ac]);
        flash('💡 Pista activada: este acertijo valdrá 20 puntos en lugar de 30.');
        return;
    }

    // Anti fuerza bruta: mínimo 2 segundos entre intentos
    $q = db()->prepare('SELECT TIMESTAMPDIFF(SECOND, ultimo_intento, NOW()) FROM intentos_codigo WHERE equipo_id = ? AND acertijo_id = ?');
    $q->execute([$eq, $ac]);
    $desde = $q->fetchColumn();
    if ($desde !== null && $desde !== false && (int)$desde < 2) { flash('Esperen un par de segundos antes de volver a intentar.', 'err'); return; }

    $intento = norm_texto($_POST['respuesta'] ?? '');
    if ($intento === '') { flash('Escribe una respuesta.', 'err'); return; }
    $validas = array_map('norm_texto', explode('|', $act['respuestas']));
    if (in_array($intento, $validas, true)) {
        $u = db()->prepare('UPDATE intentos_codigo SET resuelto = 1, puntos = IF(pista_usada = 1, 20, 30), intentos = intentos + 1, ultimo_intento = NOW()
                            WHERE equipo_id = ? AND acertijo_id = ? AND resuelto = 0');
        $u->execute([$eq, $ac]);
        recalcular_puntos($eq, 3);
        flash('🔓 ¡Cerradura abierta! Se desbloquea el siguiente acertijo.');
    } else {
        db()->prepare('UPDATE intentos_codigo SET intentos = intentos + 1, ultimo_intento = NOW() WHERE equipo_id = ? AND acertijo_id = ?')->execute([$eq, $ac]);
        flash('❌ Respuesta incorrecta. Pueden intentarlo de nuevo.', 'err');
    }
}
function codigo_render(array $reto, array $equipo): void {
    $filas = codigo_estado((int)$equipo['id']);
    $act = codigo_actual($filas);
    $c = e($reto['color']);
    $pts = array_sum(array_map(fn($f) => (int)$f['puntos'], $filas));
    ?>
    <div class="game" style="--c:<?= $c ?>">
      <div class="game-top"><span>🔐 Código Secreto · Tus puntos: <strong style="color:var(--gold)"><?= $pts ?></strong></span></div>
      <div class="locks">
        <?php foreach ($filas as $f):
          $estado = $f['resuelto'] ? 's-ok' : (($act && $act['id'] === $f['id']) ? 's-now' : 's-no'); ?>
          <div class="lock <?= $estado ?>"><div class="ic"><?= $f['resuelto'] ? '🔓' : (($act && $act['id'] === $f['id']) ? '🔑' : '🔒') ?></div>
            <strong>Acertijo <?= (int)$f['orden'] ?></strong>
            <small><?= $f['resuelto'] ? 'Resuelto · ' . (int)$f['puntos'] . ' pts' : (($act && $act['id'] === $f['id']) ? 'En progreso' : 'Bloqueado') ?></small></div>
        <?php endforeach; ?>
      </div>
      <p class="center muted" style="margin:0">Código final</p>
      <div class="code-boxes" aria-label="Código final">
        <?php foreach ($filas as $f): ?><b class="<?= $f['resuelto'] ? 'on' : '' ?>"><?= $f['resuelto'] ? e($f['fragmento']) : '?' ?></b><?php endforeach; ?>
      </div>

      <?php if (!$act): ?>
        <div class="done" style="padding-top:0"><div class="big">🎉</div><h3>¡Código descifrado!</h3>
          <p class="muted">Código final: <strong style="color:var(--gold);letter-spacing:.2em"><?= e(implode('', array_column($filas, 'fragmento'))) ?></strong></p>
          <b><?= $pts ?> pts</b></div>
      <?php else: ?>
        <h3 class="q" style="margin-top:0"><?= e($act['enunciado']) ?></h3>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="acertijo_id" value="<?= (int)$act['id'] ?>">
          <div class="field"><label for="resp">Respuesta del equipo</label>
            <input id="resp" name="respuesta" autocomplete="off" maxlength="60" placeholder="Escribe la respuesta…"></div>
          <div class="game-actions">
            <button class="btn btn-primary" name="accion" value="probar">🔑 Probar respuesta</button>
            <?php if (!$act['pista_usada']): ?>
              <button class="btn btn-ghost" name="accion" value="pista" formnovalidate data-confirm-btn="La pista reduce este acertijo de 30 a 20 puntos. ¿Usarla?">💡 Usar pista (−10 pts)</button>
            <?php endif; ?>
            <span class="muted">Intentos: <?= (int)$act['intentos'] ?></span>
          </div>
          <?php if ($act['pista_usada']): ?><div class="hint">💡 <strong>Pista:</strong> <?= e($act['pista']) ?></div><?php endif; ?>
        </form>
        <p class="muted" style="margin:16px 0 0;font-size:.88rem">Reglas: no importan mayúsculas, acentos ni artículos (“el”, “un”). Si fallan pueden intentar de nuevo sin descuento. 30 pts por acertijo sin pista · 20 con pista.</p>
      <?php endif; ?>
    </div>
    <?php
}
