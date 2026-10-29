<?php
/* Motor de preguntas de opción con cronómetro individual (Mentes Brillantes y Desafío Relámpago).
   Cada equipo avanza a su ritmo; el servidor controla el tiempo y valida la respuesta. */

function quiz_preguntas(int $retoId): array {
    $s = db()->prepare('SELECT * FROM preguntas WHERE reto_id = ? ORDER BY orden');
    $s->execute([$retoId]);
    return $s->fetchAll();
}

function quiz_estado(int $eq, array $reto): array {
    $pregs = quiz_preguntas((int)$reto['id']);
    $total = count($pregs);
    $s = db()->prepare('SELECT * FROM respuestas WHERE equipo_id = ? AND reto_id = ?');
    $s->execute([$eq, $reto['id']]);
    $resp = [];
    foreach ($s as $r) $resp[$r['pregunta_id']] = $r;

    $suma = function () use (&$resp) { $t = 0; $c = 0; foreach ($resp as $r) { $t += (int)$r['puntos']; $c += (int)($r['correcta'] ?? 0); } return [$t, $c]; };

    if (!reto_activo($reto)) {
        [$p, $c] = $suma();
        return ['activo' => false, 'total' => $total, 'hechas' => count($resp), 'puntos' => $p, 'aciertos' => $c,
                'actual' => null, 'terminado' => count($resp) >= $total];
    }
    foreach ($pregs as $i => $p) {
        if (isset($resp[$p['id']])) continue;
        db()->prepare('INSERT IGNORE INTO progreso_pregunta (equipo_id, pregunta_id, inicio_at) VALUES (?,?,NOW())')
            ->execute([$eq, $p['id']]);
        $q = db()->prepare('SELECT TIMESTAMPDIFF(SECOND, inicio_at, NOW()) FROM progreso_pregunta WHERE equipo_id = ? AND pregunta_id = ?');
        $q->execute([$eq, $p['id']]);
        $el = (int)$q->fetchColumn();
        if ($el >= (int)$p['tiempo_seg'] + 1) {            // tiempo agotado → 0 puntos y se pasa a la siguiente
            db()->prepare('INSERT IGNORE INTO respuestas (equipo_id, reto_id, pregunta_id, respuesta, correcta, puntos) VALUES (?,?,?,NULL,0,0)')
                ->execute([$eq, $reto['id'], $p['id']]);
            $resp[$p['id']] = ['puntos' => 0, 'correcta' => 0];
            continue;
        }
        [$pt, $ac] = $suma();
        return ['activo' => true, 'total' => $total, 'hechas' => count($resp), 'puntos' => $pt, 'aciertos' => $ac,
                'actual' => $p, 'num' => $i + 1, 'restante' => max(0, (int)$p['tiempo_seg'] - $el), 'terminado' => false];
    }
    recalcular_puntos($eq, (int)$reto['id']);
    [$pt, $ac] = $suma();
    return ['activo' => true, 'total' => $total, 'hechas' => count($resp), 'puntos' => $pt, 'aciertos' => $ac,
            'actual' => null, 'terminado' => true];
}

function quiz_post(array $reto, array $equipo): void {
    if (!reto_activo($reto)) { flash('El reto no está en curso: ya no se aceptan respuestas.', 'err'); return; }
    $resp = strtoupper(trim($_POST['respuesta'] ?? ''));
    if (!in_array($resp, ['A', 'B', 'C', 'D'], true)) { flash('Selecciona una opción antes de confirmar.', 'err'); return; }
    $st = quiz_estado((int)$equipo['id'], $reto);
    $p = $st['actual'] ?? null;
    if (!$p || (int)$p['id'] !== (int)($_POST['pregunta_id'] ?? 0)) {
        flash('Esa pregunta ya no está disponible (se agotó el tiempo o ya fue respondida).', 'err'); return;
    }
    $ok = hash_equals($p['correcta'], $resp);
    $pts = $ok ? (int)$p['puntos'] : 0;
    $just = $p['tipo'] === 'decision' ? (mb_substr(trim($_POST['justificacion'] ?? ''), 0, 500) ?: null) : null;
    $ins = db()->prepare('INSERT IGNORE INTO respuestas (equipo_id, reto_id, pregunta_id, respuesta, correcta, puntos, justificacion)
                          VALUES (?,?,?,?,?,?,?)');
    $ins->execute([$equipo['id'], $reto['id'], $p['id'], $resp, $ok ? 1 : 0, $pts, $just]);
    if ($ins->rowCount() === 0) { flash('Esa pregunta ya tenía una respuesta registrada.', 'err'); return; }
    recalcular_puntos((int)$equipo['id'], (int)$reto['id']);
    flash($ok ? "✅ Respuesta registrada: correcta · +{$pts} pts" : '❌ Respuesta registrada: incorrecta · 0 pts', $ok ? 'ok' : 'err');
}

function quiz_render(array $reto, array $equipo): void {
    $st = quiz_estado((int)$equipo['id'], $reto);
    $c = e($reto['color']);
    if (!$st['activo']) {
        echo '<div class="placeholder">⏱ El tiempo del reto terminó. Respondiste ' . (int)$st['hechas'] . ' de ' . (int)$st['total']
           . ' · <strong>' . (int)$st['puntos'] . ' pts</strong>.</div>';
        return;
    }
    if ($st['terminado']) {
        echo '<div class="game" style="--c:' . $c . '"><div class="done"><div class="big">🎉</div><h3>¡Reto completado!</h3>'
           . '<p class="muted">Aciertos: ' . (int)$st['aciertos'] . ' de ' . (int)$st['total'] . '</p><b>' . (int)$st['puntos'] . ' pts</b>'
           . '<p class="muted" style="margin-top:14px">Espera las indicaciones del facilitador para continuar.</p></div></div>';
        return;
    }
    $p = $st['actual'];
    $pct = (int)round($st['hechas'] / max(1, $st['total']) * 100);
    ?>
    <div class="game" style="--c:<?= $c ?>">
      <div class="game-top">
        <span><?= $p['tipo'] === 'decision' ? '🧭 Toma de decisiones · ' : '' ?>Pregunta <?= (int)$st['num'] ?> de <?= (int)$st['total'] ?> · <?= (int)$p['puntos'] ?> pts</span>
        <span>Tus puntos: <strong style="color:var(--gold)"><?= (int)$st['puntos'] ?></strong></span>
        <span class="t" data-countdown="<?= (int)$st['restante'] ?>" data-reload="1" data-fin-text="¡Tiempo!" aria-live="off"></span>
      </div>
      <div class="progress"><i style="width:<?= $pct ?>%"></i></div>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="pregunta_id" value="<?= (int)$p['id'] ?>">
        <h3 class="q"><?= e($p['enunciado']) ?></h3>
        <div class="opts" role="radiogroup">
          <?php foreach (['A' => 'opcion_a', 'B' => 'opcion_b', 'C' => 'opcion_c', 'D' => 'opcion_d'] as $l => $col): ?>
            <label class="opt"><input type="radio" name="respuesta" value="<?= $l ?>" required><span class="l"><?= $l ?></span><span><?= e($p[$col]) ?></span></label>
          <?php endforeach; ?>
        </div>
        <?php if ($p['tipo'] === 'decision'): ?>
          <div class="field" style="margin-top:16px"><label>Justificación del equipo (opcional) <span class="counter"></span></label>
            <textarea name="justificacion" rows="2" maxlength="500" placeholder="¿Por qué eligieron esta opción?"></textarea></div>
        <?php endif; ?>
        <div class="game-actions"><button class="btn btn-primary">Confirmar respuesta</button>
          <span class="muted">Solo el capitán envía una respuesta final por pregunta.</span></div>
      </form>
    </div>
    <?php
}
