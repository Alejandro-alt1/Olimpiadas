<?php
const MEM_VISTA = 30;   // segundos para observar el tablero
const MEM_RESP  = 90;   // segundos para enviar las 5 respuestas

function memoria_sesion(int $pid): ?array {
    $s = db()->prepare('SELECT *, TIMESTAMPDIFF(SECOND, inicio_at, NOW()) AS el FROM sesiones WHERE participante_id = ? AND reto_id = 2');
    $s->execute([$pid]);
    return $s->fetch() ?: null;
}
function memoria_cerrar(array $part, array $respuestas): void {
    $pid = (int)$part['id']; $eq = (int)$part['equipo_id'];
    $ps = db()->query('SELECT * FROM preguntas WHERE reto_id = 2 ORDER BY orden')->fetchAll();
    $ins = db()->prepare('INSERT IGNORE INTO respuestas (participante_id, equipo_id, reto_id, pregunta_id, respuesta, correcta, puntos) VALUES (?,?,2,?,?,?,?)');
    foreach ($ps as $p) {
        $r = strtoupper(trim((string)($respuestas[$p['id']] ?? '')));
        if (!in_array($r, ['A', 'B', 'C', 'D'], true)) { $ins->execute([$pid, $eq, $p['id'], null, 0, 0]); continue; }
        $ok = hash_equals($p['correcta'], $r);
        $ins->execute([$pid, $eq, $p['id'], $r, $ok ? 1 : 0, $ok ? (int)$p['puntos'] : 0]);
    }
    db()->prepare('UPDATE sesiones SET fin_at = NOW() WHERE participante_id = ? AND reto_id = 2 AND fin_at IS NULL')->execute([$pid]);
    recalcular_puntos($eq, 2);
}
function memoria_post(array $reto, array $equipo, array $part): void {
    if (!reto_activo($reto)) { flash('El reto no está en curso.', 'err'); return; }
    $pid = (int)$part['id'];
    $ses = memoria_sesion($pid);
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'iniciar') {
        if ($ses) { flash('Ya iniciaste la observación; no se puede repetir.', 'err'); return; }
        db()->prepare('INSERT IGNORE INTO sesiones (participante_id, reto_id, equipo_id, inicio_at) VALUES (?,2,?,NOW())')->execute([$pid, $part['equipo_id']]);
        return;
    }
    if ($accion === 'enviar') {
        if (!$ses || $ses['fin_at']) { flash('Tus respuestas ya fueron enviadas.', 'err'); return; }
        if ((int)$ses['el'] < MEM_VISTA) { flash('Aún estás en la fase de observación.', 'err'); return; }
        if ((int)$ses['el'] > MEM_VISTA + MEM_RESP + 3) { memoria_cerrar($part, []); flash('Se agotó el tiempo: no se registraron respuestas.', 'err'); return; }
        memoria_cerrar($part, $_POST['r'] ?? []);
        flash('✅ Respuestas enviadas correctamente.');
    }
}
function memoria_render(array $reto, array $equipo, array $part): void {
    $pid = (int)$part['id']; $c = e($reto['color']);
    $ses = memoria_sesion($pid);
    if ($ses && !$ses['fin_at'] && (int)$ses['el'] > MEM_VISTA + MEM_RESP) { memoria_cerrar($part, []); $ses = memoria_sesion($pid); }

    if (!$ses) { ?>
      <div class="game" style="--c:<?= $c ?>"><div class="done">
        <div class="big">👁</div><h3>Memoria Extrema</h3>
        <p class="muted" style="max-width:56ch;margin:0 auto 18px">Verás 12 objetos durante <strong><?= MEM_VISTA ?> segundos</strong>. Después el tablero desaparece y tendrás <strong><?= MEM_RESP ?> segundos</strong> para responder 5 preguntas. Solo puedes ver el tablero una vez y no se permiten capturas de pantalla. Cada integrante juega a su ritmo y el equipo se puntúa con el promedio.</p>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="iniciar">
          <button class="btn btn-primary">▶ Iniciar mi observación (hazlo cuando estés listo)</button></form>
      </div></div>
    <?php return; }

    if ($ses['fin_at']) {
        $s = db()->prepare('SELECT COALESCE(SUM(correcta),0) a, COALESCE(SUM(puntos),0) p, COUNT(*) n FROM respuestas WHERE participante_id = ? AND reto_id = 2');
        $s->execute([$pid]); $r = $s->fetch();
        $q = db()->prepare('SELECT puntos FROM puntuaciones WHERE equipo_id = ? AND reto_id = 2'); $q->execute([$equipo['id']]); ?>
      <div class="game" style="--c:<?= $c ?>"><div class="done"><div class="big">✅</div><h3>Respuestas enviadas</h3>
        <p class="muted">Aciertos: <?= (int)$r['a'] ?> de <?= (int)$r['n'] ?></p><b><?= (int)$r['p'] ?> pts</b>
        <p class="muted" style="margin-top:14px">Promedio actual de tu equipo: <strong style="color:var(--gold)"><?= (int)$q->fetchColumn() ?></strong> / <?= (int)$reto['puntos_max'] ?> pts</p></div></div>
    <?php return; }

    $el = (int)$ses['el'];
    if ($el < MEM_VISTA) {
        $obj = db()->query('SELECT * FROM memoria_objetos ORDER BY posicion')->fetchAll(); ?>
      <div class="game" style="--c:<?= $c ?>">
        <div class="game-top"><span>🔎 Observa el tablero en silencio</span>
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
        <div class="game-top"><span>📝 Responde las 5 preguntas y envía una sola vez</span>
          <span class="t" data-countdown="<?= $rest ?>" data-reload="1" data-fin-text="¡Tiempo!"></span></div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="accion" value="enviar">
          <?php foreach ($ps as $i => $p): ?>
            <div class="mem-q"><h4><?= $i + 1 ?>. <?= e($p['enunciado']) ?></h4>
              <div class="opts"><?php foreach (['A' => 'opcion_a', 'B' => 'opcion_b', 'C' => 'opcion_c', 'D' => 'opcion_d'] as $l => $col): ?>
                <label class="opt"><input type="radio" name="r[<?= (int)$p['id'] ?>]" value="<?= $l ?>"><span class="l"><?= $l ?></span><span><?= e($p[$col]) ?></span></label>
              <?php endforeach; ?></div></div>
          <?php endforeach; ?>
          <div class="game-actions"><button class="btn btn-green" data-confirm-btn>Enviar mis respuestas finales</button>
            <span class="muted">Las preguntas sin responder valen 0 puntos.</span></div>
        </form>
      </div>
    <?php
}
