<?php
function creatividad_propuesta(int $eq): ?array {
    $s = db()->prepare('SELECT * FROM propuestas WHERE equipo_id = ?');
    $s->execute([$eq]);
    return $s->fetch() ?: null;
}
function creatividad_post(array $reto, array $equipo): void {
    $eq = (int)$equipo['id'];
    $_SESSION['old_creatividad'] = $_POST;
    if (!reto_activo($reto)) { flash('El reto no está en curso: ya no se aceptan propuestas.', 'err'); return; }
    if (creatividad_propuesta($eq)) { flash('Su equipo ya envió una propuesta; no se puede enviar otra.', 'err'); return; }

    $campos = ['nombre' => 80, 'problema' => 500, 'descripcion' => 800, 'publico' => 250, 'beneficio' => 500];
    $d = [];
    foreach ($campos as $k => $max) {
        $v = trim($_POST[$k] ?? '');
        if ($v === '') { flash('Completa todos los campos obligatorios.', 'err'); return; }
        $d[$k] = mb_substr($v, 0, $max);
    }

    $img = null;
    if (!empty($_FILES['imagen']['name'])) {
        $f = $_FILES['imagen'];
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) { flash('La imagen no se pudo cargar (máximo 2 MB).', 'err'); return; }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (!$ext || !@getimagesize($f['tmp_name'])) { flash('Formato no permitido. Usa JPG, PNG o WEBP.', 'err'); return; }
        $dir = __DIR__ . '/../../uploads';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $img = bin2hex(random_bytes(12)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $img)) { flash('No se pudo guardar la imagen.', 'err'); return; }
    }
    try {
        db()->prepare('INSERT INTO propuestas (equipo_id, nombre, problema, descripcion, publico, beneficio, imagen) VALUES (?,?,?,?,?,?,?)')
            ->execute([$eq, $d['nombre'], $d['problema'], $d['descripcion'], $d['publico'], $d['beneficio'], $img]);
        unset($_SESSION['old_creatividad']);
        flash('✅ Propuesta enviada. Será evaluada con la rúbrica publicada.');
    } catch (PDOException $e) { flash('Su equipo ya había enviado una propuesta.', 'err'); }
}
function creatividad_render(array $reto, array $equipo): void {
    $c = e($reto['color']);
    $p = creatividad_propuesta((int)$equipo['id']);
    $old = $_SESSION['old_creatividad'] ?? []; unset($_SESSION['old_creatividad']);
    $rub = '<div class="rubrica"><div><b>40</b>Originalidad<br><small class="muted">Enfoque novedoso o combinación distinta</small></div>'
         . '<div><b>35</b>Utilidad<br><small class="muted">Resuelve un problema real</small></div>'
         . '<div><b>25</b>Claridad<br><small class="muted">Organizada y fácil de explicar</small></div></div>';
    if ($p) { ?>
      <div class="game" style="--c:<?= $c ?>">
        <div class="game-top"><span>✅ Propuesta enviada el <?= e(date('H:i:s', strtotime($p['enviada_at']))) ?></span></div>
        <h3 class="q"><?= e($p['nombre']) ?></h3>
        <p><strong>Problema:</strong> <?= nl2br(e($p['problema'])) ?></p>
        <p><strong>Solución:</strong> <?= nl2br(e($p['descripcion'])) ?></p>
        <p><strong>Beneficia a:</strong> <?= e($p['publico']) ?></p>
        <p><strong>Beneficio y cómo probarlo:</strong> <?= nl2br(e($p['beneficio'])) ?></p>
        <?php if ($p['imagen']): ?><img class="prop-img" src="<?= url('uploads/' . e($p['imagen'])) ?>" alt="Imagen de la propuesta"><?php endif; ?>
        <p class="muted" style="margin-top:16px">La evaluación la realiza el facilitador con esta rúbrica (100 puntos):</p><?= $rub ?>
      </div>
    <?php return; } ?>
      <div class="game" style="--c:<?= $c ?>">
        <div class="game-top"><span>💡 Propongan un producto o servicio innovador (una sola entrega por equipo)</span></div>
        <p class="muted" style="margin-top:14px">La rúbrica se conoce de antemano:</p><?= $rub ?>
        <form method="post" enctype="multipart/form-data" data-confirm="¿Enviar la propuesta? No se podrá modificar.">
          <?= csrf_field() ?>
          <div class="field"><label>Nombre del producto o servicio <span class="counter"></span></label>
            <input name="nombre" maxlength="80" required value="<?= e($old['nombre'] ?? '') ?>" placeholder="Ej.: EcoBreak"></div>
          <div class="field"><label>Problema o necesidad que atiende <span class="counter"></span></label>
            <textarea name="problema" rows="2" maxlength="500" required><?= e($old['problema'] ?? '') ?></textarea></div>
          <div class="field"><label>Descripción de la solución (2 a 4 frases) <span class="counter"></span></label>
            <textarea name="descripcion" rows="4" maxlength="800" required><?= e($old['descripcion'] ?? '') ?></textarea></div>
          <div class="field"><label>Persona o equipo que se beneficiaría <span class="counter"></span></label>
            <input name="publico" maxlength="250" required value="<?= e($old['publico'] ?? '') ?>"></div>
          <div class="field"><label>Beneficio principal y cómo se podría probar la idea <span class="counter"></span></label>
            <textarea name="beneficio" rows="3" maxlength="500" required><?= e($old['beneficio'] ?? '') ?></textarea></div>
          <div class="field"><label>Imagen o dibujo (opcional · JPG, PNG o WEBP · máx. 2 MB)</label>
            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"></div>
          <button class="btn btn-gold">Enviar propuesta</button>
        </form>
      </div>
    <?php
}
