<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();

if (is_post()) {
    $nombre = trim($_POST['nombre'] ?? ''); $usuario = trim($_POST['usuario'] ?? ''); $pass = $_POST['password'] ?? '';
    if ($nombre === '' || $usuario === '' || strlen($pass) < 8) { flash('Completa los campos (contraseña de 8+ caracteres).', 'err'); redirect('admin/usuarios.php'); }
    try {
        db()->prepare('INSERT INTO usuarios (usuario, nombre, password_hash) VALUES (?,?,?)')->execute([$usuario, $nombre, password_hash($pass, PASSWORD_DEFAULT)]);
        bitacora('sistema', 'Facilitador/evaluador creado: ' . $usuario);
        flash('Cuenta creada.');
    } catch (PDOException $e) { flash('Ese usuario ya existe.', 'err'); }
    redirect('admin/usuarios.php');
}
$us = db()->query('SELECT usuario, nombre, created_at FROM usuarios ORDER BY id')->fetchAll();
$titulo = 'Facilitadores'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Administración</div><h1>Facilitadores y evaluadores</h1>
  <p>Crea una cuenta por cada evaluador para que puntúen Creatividad de forma independiente (la guía recomienda dos).</p></div>
<div class="grid g2">
  <div class="table-wrap"><table style="min-width:0"><thead><tr><th>Nombre</th><th>Usuario</th><th>Alta</th></tr></thead><tbody>
    <?php foreach ($us as $u): ?><tr><td><?= e($u['nombre']) ?></td><td><?= e($u['usuario']) ?></td><td><?= e(date('d/m/Y', strtotime($u['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="card"><h3>➕ Nueva cuenta</h3><form method="post"><?= csrf_field() ?>
    <div class="field"><label>Nombre</label><input name="nombre" required></div>
    <div class="row"><div class="field"><label>Usuario</label><input name="usuario" required></div>
    <div class="field"><label>Contraseña (mín. 8)</label><input type="password" name="password" minlength="8" required autocomplete="new-password"></div></div>
    <button class="btn btn-green">Crear cuenta</button></form></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
