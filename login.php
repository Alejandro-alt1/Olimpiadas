<?php
require __DIR__ . '/includes/bootstrap.php';
csrf_check();

$rol = ($_GET['rol'] ?? 'equipo') === 'facilitador' ? 'facilitador' : 'equipo';
$hayAdmins = (int)db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0;
$accion = $_POST['accion'] ?? '';
$espera = login_bloqueado();

if (is_post()) {
    if ($espera && $accion !== 'crear_admin') {
        flash("Demasiados intentos. Espera {$espera} s.", 'err');
        redirect('login.php?rol=' . $rol);
    }

    if ($accion === 'equipo') {
        $s = db()->prepare('SELECT * FROM equipos WHERE id = ? AND activo = 1');
        $s->execute([(int)($_POST['equipo_id'] ?? 0)]);
        $eq = $s->fetch();
        if ($eq && hash_equals($eq['pin'], trim($_POST['pin'] ?? ''))) {
            session_regenerate_id(true);
            unset($_SESSION['admin']);
            $_SESSION['equipo_id'] = (int)$eq['id'];
            $_SESSION['login_fallos'] = 0;
            redirect('equipo.php');
        }
        login_fallo(); flash('Equipo o código incorrecto.', 'err'); redirect('login.php');
    }

    if ($accion === 'facilitador') {
        $s = db()->prepare('SELECT * FROM usuarios WHERE usuario = ?');
        $s->execute([trim($_POST['usuario'] ?? '')]);
        $u = $s->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION['equipo_id']);
            $_SESSION['admin'] = ['id' => (int)$u['id'], 'usuario' => $u['usuario'], 'nombre' => $u['nombre']];
            $_SESSION['login_fallos'] = 0;
            redirect('admin/index.php');
        }
        login_fallo(); flash('Usuario o contraseña incorrectos.', 'err'); redirect('login.php?rol=facilitador');
    }

    // Primera configuración: solo si todavía no existe ningún facilitador
    if ($accion === 'crear_admin' && !$hayAdmins) {
        $usuario = trim($_POST['usuario'] ?? ''); $nombre = trim($_POST['nombre'] ?? ''); $pass = $_POST['password'] ?? '';
        if ($usuario === '' || $nombre === '' || strlen($pass) < 8) {
            flash('Completa los campos. La contraseña debe tener al menos 8 caracteres.', 'err');
            redirect('login.php?rol=facilitador');
        }
        db()->prepare('INSERT INTO usuarios (usuario, nombre, password_hash) VALUES (?,?,?)')
            ->execute([$usuario, $nombre, password_hash($pass, PASSWORD_DEFAULT)]);
        flash('Cuenta de facilitador creada. Ya puedes ingresar.');
        redirect('login.php?rol=facilitador');
    }
}

$equipos = db()->query('SELECT id, nombre, color FROM equipos WHERE activo = 1 ORDER BY id')->fetchAll();
$titulo = 'Ingresar'; $pagina = 'login';
require __DIR__ . '/includes/header.php';
?>
<div class="login-box">
  <div class="page-head center"><div class="eyebrow">Acceso</div><h1 style="font-size:2rem">Ingresar a la plataforma</h1></div>
  <div class="tabs" role="tablist">
    <a href="<?= url('login.php') ?>" class="<?= $rol === 'equipo' ? 'on' : '' ?>">👥 Capitán de equipo</a>
    <a href="<?= url('login.php?rol=facilitador') ?>" class="<?= $rol === 'facilitador' ? 'on' : '' ?>">🎙 Facilitador</a>
  </div>
  <div class="card">
  <?php if ($espera): ?>
    <div class="alert alert-err">Demasiados intentos. Espera <?= $espera ?> s e inténtalo de nuevo.</div>
  <?php endif; ?>

  <?php if ($rol === 'equipo'): ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="accion" value="equipo">
      <div class="field"><label for="equipo_id">Equipo</label>
        <select id="equipo_id" name="equipo_id" required>
          <option value="">Selecciona tu equipo…</option>
          <?php foreach ($equipos as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['nombre']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label for="pin">Código de capitán</label>
        <input id="pin" name="pin" type="password" inputmode="numeric" maxlength="8" required placeholder="Código que te dio el facilitador"></div>
      <button class="btn btn-primary btn-block">Entrar como capitán</button>
      <p class="muted center" style="margin:14px 0 0;font-size:.88rem">Solo el capitán registra las respuestas. Los demás integrantes pueden seguir el marcador sin ingresar.</p>
    </form>

  <?php elseif ($hayAdmins): ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="accion" value="facilitador">
      <div class="field"><label for="usuario">Usuario</label><input id="usuario" name="usuario" required autocomplete="username"></div>
      <div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
      <button class="btn btn-gold btn-block">Entrar al panel</button>
    </form>

  <?php else: ?>
    <h3>Primera configuración</h3>
    <p class="muted">Aún no existe ningún facilitador. Crea la cuenta principal (solo se muestra esta vez).</p>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="accion" value="crear_admin">
      <div class="field"><label>Nombre completo</label><input name="nombre" required></div>
      <div class="row">
        <div class="field"><label>Usuario</label><input name="usuario" required autocomplete="username"></div>
        <div class="field"><label>Contraseña (mín. 8)</label><input name="password" type="password" minlength="8" required autocomplete="new-password"></div>
      </div>
      <button class="btn btn-gold btn-block">Crear cuenta de facilitador</button>
    </form>
  <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
