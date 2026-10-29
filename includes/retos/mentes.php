<?php
require_once __DIR__ . '/engine.php';
function mentes_post(array $reto, array $equipo): void { quiz_post($reto, $equipo); }
function mentes_render(array $reto, array $equipo): void { quiz_render($reto, $equipo); }
