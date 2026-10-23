<?php

$titulo = $titulo ?? APP_NAME;
$pagina = $pagina ?? '';
$admin  = admin_actual();
$equipo = team_actual();
$es_admin_dir = str_contains($_SERVER['SCRIPT_NAME'], '/admin/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip" href="#contenido">Saltar al contenido</a>
<header class="topbar">
  <div class="wrap topbar-in">
    <a class="brand" href="<?= url('index.php') ?>">
      <span class="brand-ico" aria-hidden="true">🏆</span>
      <span class="brand-txt"><strong>Olimpiadas Virtuales</strong><em>Corporativas</em></span>
    </a>
    <button class="burger" aria-label="Abrir menú" aria-expanded="false" data-burger>☰</button>
    <nav class="nav" data-nav aria-label="Principal">
      <a href="<?= url('index.php') ?>" class="<?= $pagina === 'inicio' ? 'on' : '' ?>">Inicio</a>
      <a href="<?= url('retos.php') ?>" class="<?= $pagina === 'retos' ? 'on' : '' ?>">Retos</a>
      <a href="<?= url('marcador.php') ?>" class="<?= $pagina === 'marcador' ? 'on' : '' ?>">Marcador</a>
      <a href="<?= url('reglas.php') ?>" class="<?= $pagina === 'reglas' ? 'on' : '' ?>">Reglas</a>
      <?php if ($admin): ?>
        <a href="<?= url('admin/index.php') ?>" class="<?= $es_admin_dir ? 'on' : '' ?>">Panel facilitador</a>
      <?php endif; ?>
    </nav>
    <div class="session">
      <?php if ($admin): ?>
        <span class="chip chip-gold">🎙 <?= e($admin['nombre']) ?></span>
        <a class="btn btn-ghost btn-sm" href="<?= url('logout.php') ?>">Salir</a>
      <?php elseif ($equipo): ?>
        <a class="chip" style="--c:<?= e($equipo['color']) ?>" href="<?= url('equipo.php') ?>"><i class="dot"></i><?= e($equipo['nombre']) ?></a>
        <a class="btn btn-ghost btn-sm" href="<?= url('logout.php') ?>">Salir</a>
      <?php else: ?>
        <a class="btn btn-primary btn-sm" href="<?= url('login.php') ?>">Ingresar</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main id="contenido" class="wrap main">
<?php foreach (flash() as $f): ?>
  <div class="alert alert-<?= $f['t'] === 'err' ? 'err' : 'ok' ?>" role="status"><?= e($f['m']) ?></div>
<?php endforeach; ?>
