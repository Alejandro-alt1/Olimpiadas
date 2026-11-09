<?php
/* Creatividad Sin Límites: una sola propuesta por equipo, sin líder.
   Todos editan un BORRADOR COMPARTIDO; cuando la mayoría de los integrantes conectados marca "Listo",
   la propuesta se envía automáticamente. Cualquier cambio al borrador reinicia los "Listo". */

function cr_propuesta(int $eq): ?array {
    $s = db()->prepare('SELECT * FROM propuestas WHERE equipo_id = ?');
    $s->execute([$eq]);
    return $s->fetch() ?: null;
}
function cr_borrador(int $eq): array {
    db()->prepare('INSERT IGNORE INTO borradores (equipo_id) VALUES (?)')->execute([$eq]);
    $s = db()->prepare('SELECT b.*, p.alias AS editor FROM borradores b LEFT JOIN participantes p ON p.id = b.editado_por WHERE b.equipo_id = ?');
    $s->execute([$eq]);
    return $s->fetch();
}
function cr_completo(array $b): bool {
    foreach (['nombre', 'problema', 'descripcion', 'publico', 'beneficio'] as $k) if (trim((string)$b[$k]) === '') return false;
    return true;
}
/** Conectados = con actividad en los últimos 5 min. Se necesita mayoría estricta de conectados. */
function cr_estado(int $eq): array {
    $a = db()->prepare('SELECT COUNT(*) FROM participantes WHERE equipo_id = ? AND last_seen >= NOW() - INTERVAL 5 MINUTE');
    $a->execute([$eq]);
    $activos = max(1, (int)$a->fetchColumn());
    $l = db()->prepare('SELECT COUNT(*) FROM borrador_listos l JOIN participantes p ON p.id = l.participante_id
                        WHERE l.equipo_id = ? AND p.last_seen >= NOW() - INTERVAL 5 MINUTE');
    $l->execute([$eq]);
    return ['activos' => $activos, 'listos' => (int)$l->fetchColumn(), 'necesarios' => intdiv($activos, 2) + 1];
}
/** Convierte el borrador en la propuesta oficial del equipo (si está completo y no existe ya). */
function cr_enviar(int $eq): bool {
    $b = cr_borrador($eq);
    if (!cr_completo($b) || cr_propuesta($eq)) return false;
    try {
        db()->prepare('INSERT INTO propuestas (equipo_id, nombre, problema, descripcion, publico, beneficio, imagen) VALUES (?,?,?,?,?,?,?)')
            ->execute([$eq, $b['nombre'], $b['problema'], $b['descripcion'], $b['publico'], $b['beneficio'], $b['imagen']]);
        return true;
    } catch (PDOException $e) { return false; }
}
/** Valida y guarda la imagen opcional. Devuelve [nombre|null, error|null]. */
function cr_subir_imagen(): array {
    if (empty($_FILES['imagen']['name'])) return [null, null];
    $f = $_FILES['imagen'];
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) return [null, 'La imagen no se pudo cargar (máximo 2 MB).'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !@getimagesize($f['tmp_name'])) return [null, 'Formato no permitido. Usa JPG, PNG o WEBP.'];
    $dir = __DIR__ . '/../../uploads';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $nombre = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $nombre)) return [null, 'No se pudo guardar la imagen.'];
    return [$nombre, null];
}

function creatividad_post(array $reto, array $equipo, array $part): void {
    $eq = (int)$part['equipo_id']; $pid = (int)$part['id'];
    if (!reto_activo($reto)) { flash('El reto no está en curso: ya no se aceptan cambios.', 'err'); return; }
    if (cr_propuesta($eq)) { flash('Tu equipo ya envió su propuesta; no se puede enviar otra.', 'err'); return; }
    $accion = $_POST['accion'] ?? '';
    $b = cr_borrador($eq);

    if ($accion === 'guardar') {
        $campos = ['nombre' => 80, 'problema' => 500, 'descripcion' => 800, 'publico' => 250, 'beneficio' => 500];
        $d = [];
        foreach ($campos as $k => $max) $d[$k] = mb_substr(trim($_POST[$k] ?? ''), 0, $max);
        if ((int)($_POST['version'] ?? 0) !== (int)$b['version']) {
            $_SESSION['old_creatividad'] = $d;       // conserva lo que escribió esta persona
            flash('⚠ Un compañero guardó cambios mientras editabas. Revisa tu texto y guarda de nuevo para reemplazar su versión, o carga la versión del equipo.', 'err');
            return;
        }
        [$img, $errImg] = cr_subir_imagen();
        if ($errImg) { $_SESSION['old_creatividad'] = $d; flash($errImg, 'err'); return; }
        $u = db()->prepare('UPDATE borradores SET nombre=?, problema=?, descripcion=?, publico=?, beneficio=?, imagen=COALESCE(?, imagen),
                            editado_por=?, version=version+1 WHERE equipo_id=? AND version=?');
        $u->execute([$d['nombre'], $d['problema'], $d['descripcion'], $d['publico'], $d['beneficio'], $img, $pid, $eq, (int)$b['version']]);
        if ($u->rowCount() !== 1) { $_SESSION['old_creatividad'] = $d; flash('Otro compañero guardó al mismo tiempo. Intenta de nuevo.', 'err'); return; }
        db()->prepare('DELETE FROM borrador_listos WHERE equipo_id = ?')->execute([$eq]);   // el contenido cambió: se reinicia la confirmación
        flash('💾 Borrador guardado para todo el equipo. Se reiniciaron los "Listo".');
        return;
    }

    if ($accion === 'listo' || $accion === 'quitar') {
        if ((int)($_POST['version'] ?? 0) !== (int)$b['version']) { flash('El borrador cambió: revísalo antes de marcar “Listo”.', 'err'); return; }
        if ($accion === 'quitar') {
            db()->prepare('DELETE FROM borrador_listos WHERE equipo_id = ? AND participante_id = ?')->execute([$eq, $pid]);
            flash('Quitaste tu “Listo”.');
            return;
        }
        if (!cr_completo($b)) { flash('Completa todos los campos obligatorios y guarda el borrador antes de marcar “Listo”.', 'err'); return; }
        db()->prepare('INSERT IGNORE INTO borrador_listos (equipo_id, participante_id) VALUES (?,?)')->execute([$eq, $pid]);
        $st = cr_estado($eq);
        if ($st['listos'] >= $st['necesarios'] && cr_enviar($eq)) flash('✅ ¡La mayoría del equipo dijo “Listo”! La propuesta fue enviada.');
        else flash("Marcaste “Listo”. Faltan " . max(0, $st['necesarios'] - $st['listos']) . ' para enviar (mayoría de los conectados).');
    }
}

function creatividad_render(array $reto, array $equipo, array $part): void {
    $c = e($reto['color']);
    $eq = (int)$part['equipo_id']; $pid = (int)$part['id'];
    $p = cr_propuesta($eq);
    $rub = '<div class="rubrica"><div><b>40</b>Originalidad<br><small class="muted">Enfoque novedoso o combinación distinta</small></div>'
         . '<div><b>35</b>Utilidad<br><small class="muted">Resuelve un problema real</small></div>'
         . '<div><b>25</b>Claridad<br><small class="muted">Organizada y fácil de explicar</small></div></div>';
    if ($p) { ?>
      <div class="game" style="--c:<?= $c ?>">
        <div class="game-top"><span>✅ Propuesta de tu equipo enviada a las <?= e(date('H:i:s', strtotime($p['enviada_at']))) ?></span></div>
        <h3 class="q"><?= e($p['nombre']) ?></h3>
        <p><strong>Problema:</strong> <?= nl2br(e($p['problema'])) ?></p>
        <p><strong>Solución:</strong> <?= nl2br(e($p['descripcion'])) ?></p>
        <p><strong>Beneficia a:</strong> <?= e($p['publico']) ?></p>
        <p><strong>Beneficio y cómo probarlo:</strong> <?= nl2br(e($p['beneficio'])) ?></p>
        <?php if ($p['imagen']): ?><img class="prop-img" src="<?= url('uploads/' . e($p['imagen'])) ?>" alt="Imagen de la propuesta"><?php endif; ?>
        <p class="muted" style="margin-top:16px">Será evaluada con esta rúbrica (100 puntos) y todo el equipo recibe el mismo puntaje:</p><?= $rub ?>
      </div>
    <?php return; }

    $b = cr_borrador($eq);
    $old = $_SESSION['old_creatividad'] ?? null; unset($_SESSION['old_creatividad']);
    $v = fn($k) => e($old[$k] ?? $b[$k]);
    $st = cr_estado($eq);
    $m = db()->prepare('SELECT 1 FROM borrador_listos WHERE equipo_id = ? AND participante_id = ?'); $m->execute([$eq, $pid]);
    $yoListo = (bool)$m->fetchColumn();
    $completo = cr_completo($b);
    ?>
      <div class="game" style="--c:<?= $c ?>" id="cr-sync" data-api="<?= e(url('api/borrador.php')) ?>" data-version="<?= (int)$b['version'] ?>">
        <div class="game-top"><span>💡 Una propuesta por equipo: todos editan el mismo borrador</span>
          <span class="muted"><?= $b['editor'] ? 'Última edición: ' . e($b['editor']) . ' · ' . e(date('H:i:s', strtotime($b['updated_at']))) : 'Aún sin ediciones' ?></span></div>
        <div id="cr-news" class="alert alert-err" hidden style="margin-top:14px">🔄 <b></b> actualizó el borrador. <a href="">Recargar para verlo</a> (guarda antes lo que estés escribiendo).</div>
        <p class="muted" style="margin-top:14px">La rúbrica se conoce de antemano:</p><?= $rub ?>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="version" value="<?= (int)$b['version'] ?>">
          <div class="field"><label>Nombre del producto o servicio <span class="counter"></span></label>
            <input name="nombre" maxlength="80" value="<?= $v('nombre') ?>" placeholder="Ej.: EcoBreak"></div>
          <div class="field"><label>Problema o necesidad que atiende <span class="counter"></span></label>
            <textarea name="problema" rows="2" maxlength="500"><?= $v('problema') ?></textarea></div>
          <div class="field"><label>Descripción de la solución (2 a 4 frases) <span class="counter"></span></label>
            <textarea name="descripcion" rows="4" maxlength="800"><?= $v('descripcion') ?></textarea></div>
          <div class="field"><label>Persona o equipo que se beneficiaría <span class="counter"></span></label>
            <input name="publico" maxlength="250" value="<?= $v('publico') ?>"></div>
          <div class="field"><label>Beneficio principal y cómo se podría probar la idea <span class="counter"></span></label>
            <textarea name="beneficio" rows="3" maxlength="500"><?= $v('beneficio') ?></textarea></div>
          <div class="field"><label>Imagen o dibujo (opcional · JPG, PNG o WEBP · máx. 2 MB)</label>
            <?php if ($b['imagen']): ?><img class="prop-img" style="max-height:140px;display:block;margin-bottom:8px" src="<?= url('uploads/' . e($b['imagen'])) ?>" alt="Imagen actual del borrador"><?php endif; ?>
            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"></div>
          <button class="btn btn-primary">💾 Guardar borrador del equipo</button>
        </form>

        <div class="card" style="margin-top:22px;background:#0b1f3a">
          <h3 style="margin-top:0">✅ ¿El equipo está de acuerdo?</h3>
          <p class="muted" style="margin:0 0 12px">Cuando la mayoría de los integrantes conectados marque “Listo”, la propuesta se envía sola. Si alguien cambia el borrador, los “Listo” se reinician.</p>
          <p style="margin:0 0 12px"><strong id="cr-count"><?= $st['listos'] ?> de <?= $st['activos'] ?> conectados listos (mayoría necesaria: <?= $st['necesarios'] ?>)</strong></p>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="version" value="<?= (int)$b['version'] ?>">
            <?php if ($yoListo): ?><input type="hidden" name="accion" value="quitar"><button class="btn btn-ghost">Quitar mi “Listo”</button>
            <?php else: ?><input type="hidden" name="accion" value="listo"><button class="btn btn-green" <?= $completo ? '' : 'disabled' ?>>✔ Marcar “Listo” con el borrador guardado</button>
              <?php if (!$completo): ?><span class="muted">Primero completa y guarda todos los campos.</span><?php endif; ?><?php endif; ?>
          </form>
        </div>
      </div>
      <script src="<?= url('assets/js/borrador.js') ?>"></script>
    <?php
}
