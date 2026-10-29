<?php
const MEM_VISTA = 30;   // segundos para observar el tablero
const MEM_RESP  = 90;   // segundos para enviar las 5 respuestas

function memoria_sesion(int $eq): ?array {
    $s = db()->prepare('SELECT *, TIMESTAMPDIFF(SECOND, inicio_at, NOW()) AS el FROM sesiones WHERE equipo_id = ? AND reto_id = 2');
    $s->execute([$eq]);
    return $s->fetch() ?: null;
}
function memoria_cerrar(int $eq, array $respuestas): void {
    $ps = db()->query('SELECT * FROM preguntas WHERE reto_id = 2 ORDER BY orden')->fetchAll();
    $ins = db()->prepare('INSERT IGNORE INTO respuestas (equipo_id, reto_id, pregunta_id, respuesta, correcta, puntos) VALUES (?,2,?,?,?,?)');
    foreach ($ps as $p) {
        $r = strtoupper(trim((string)($respuestas[$p['id']] ?? '')));
        if (!in_array($r, ['A', 'B', 'C', 'D'], true)) { $ins->execute([$eq, $p['id'], null, 0, 0]); continue; }
        $ok = hash_equals($p['correcta'], $r);
        $ins->execute([$eq, $p['id'], $r, $ok ? 1 : 0, $ok ? (int)$p['puntos'] : 0]);
    }
    db()->prepare('UPDATE sesiones SET fin_at = NOW() WHERE equipo_id = ? AND reto_id = 2 AND fin_at IS NULL')->execute([$eq]);
    recalcular_puntos($eq, 2);
}
function memoria_post(array $reto, array $equipo): void {
    if (!reto_activo($reto)) { flash('El reto no está en curso.', 'err'); return; }
    $eq = (int)$equipo['id'];
    $ses = memoria_sesion($eq);
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'iniciar') {
        if ($ses) { flash('Ya iniciaron la observación; no se puede repetir.', 'err'); return; }
        db()->prepare('INSERT IGNORE INTO sesiones (equipo_id, reto_id, inicio_at) VALUES (?,2,NOW())')->execute([$eq]);
        return;
    }
    if ($accion === 'enviar') {
        if (!$ses || $ses['fin_at']) { flash('Las respuestas ya fueron enviadas.', 'err'); return; }
        if ((int)$ses['el'] < MEM_VISTA) { flash('Aún están en la fase de observación.', 'err'); return; }
        if ((int)$ses['el'] > MEM_VISTA + MEM_RESP + 3) { memoria_cerrar($eq, []); flash('Se agotó el tiempo: no se registraron respuestas.', 'err'); return; }
        memoria_cerrar($eq, $_POST['r'] ?? []);
        flash('✅ Respuestas enviadas correctamente.');
    }
}
function memoria_render(array $reto, array $equipo): void {
    $eq = (int)$equipo['id']; $c = e($reto['color']);
    $ses = memoria_sesion($eq);
    if ($ses && !$ses['fin_at'] && (int)$ses['el'] > MEM_VISTA + MEM_RESP) { memoria_cerrar($eq, []); $ses = memoria_sesion($eq); }

    if (!$ses) { ?>
      <div class="game" style="--c:<?= $c ?>"><div class="done">
        <div class="big">👁</div><h3>Memoria Extrema</h3>
        <p class="muted" style="max-width:56ch;margin:0 auto 18px">Verán 12 objetos durante <strong><?= MEM_VISTA ?> segundos</strong>. Después el tablero desaparece y tendrán <strong><?= MEM_RESP ?> segundos</strong> para responder 5 preguntas. Solo se puede ver el tablero una vez y no se permiten capturas de pantalla.</p>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="iniciar">
          <button class="btn btn-primary">▶ Iniciar observación (solo el capitán, cuando todos estén listos)</button></form>
      </div></div>
    <?php return; }

    if ($ses['fin_at']) {
        $s = db()->prepare('SELECT COALESCE(SUM(correcta),0) a, COALESCE(SUM(puntos),0) p, COUNT(*) n FROM respuestas WHERE equipo_id=? AND reto_id=2');
        $s->execute([$eq]); $r = $s->fetch(); ?>
      <div class="game" style="--c:<?= $c ?>"><div class="done"><div class="big">✅</div><h3>Respuestas enviadas</h3>
        <p class="muted">Aciertos: <?= (int)$r['a'] ?> de <?= (int)$r['n'] ?></p><b><?= (int)$r['p'] ?> pts</b>
        <p class="muted" style="margin-top:14px">Espera las indicaciones del facilitador.</p></div></div>
    <?php return; }

    $el = (int)$ses['el'];
    if ($el < MEM_VISTA) {
        $obj = db()->query('SELECT * FROM memoria_objetos ORDER BY posicion')->fetchAll(); ?>
      <div class="game" style="--c:<?= $c ?>">
        <div class="game-top"><span>🔎 Observen el tablero en silencio</span>
          <span class="t" data-countdown="<?= MEM_VISTA - $el ?>" data-reload="1" data-fin-text="¡Listo!"></span></div>
        <div class="mem-grid"><?php foreach ($obj as $o): ?>
          <div class="tile"><span><?= e($o['emoji']) ?></span><small><?= e($o['nombre']) ?></small></div>
        <?php endforeach; ?></div>
        <p class="muted center">El tablero se ocultará automáticamente.</p>
      </div>
    <?php return; }

    $ps = db()->query('SELECT * FROM preguntas WHERE reto_id = 2 ORDER BY orden')->fetchAll();
    $rest = max(0, MEM_VISTA + MEM_RESP - $el); ?>
      <div class="game" style="--c:<?= $c ?>">
        <div class="game-top"><span>📝 Respondan las 5 preguntas y envíen una sola vez</span>
          <span class="t" data-countdown="<?= $rest ?>" data-reload="1" data-fin-text="¡Tiempo!"></span></div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="enviar">
          <?php foreach ($ps as $i => $p): ?>
            <div class="mem-q"><h4><?= $i + 1 ?>. <?= e($p['enunciado']) ?></h4>
              <div class="opts"><?php foreach (['A' => 'opcion_a', 'B' => 'opcion_b', 'C' => 'opcion_c', 'D' => 'opcion_d'] as $l => $col): ?>
                <label class="opt"><input type="radio" name="r[<?= (int)$p['id'] ?>]" value="<?= $l ?>"><span class="l"><?= $l ?></span><span><?= e($p[$col]) ?></span></label>
              <?php endforeach; ?></div></div>
          <?php endforeach; ?>
          <div class="game-actions"><button class="btn btn-green" data-confirm-btn>Enviar respuestas finales</button>
            <span class="muted">Las preguntas sin responder valen 0 puntos.</span></div>
        </form>
      </div>
    <?php
}
