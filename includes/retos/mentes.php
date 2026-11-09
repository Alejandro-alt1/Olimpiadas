<?php
require_once __DIR__ . '/engine.php';
function mentes_post(array $reto, array $equipo, array $part): void { quiz_post($reto, $equipo, $part); }
function mentes_render(array $reto, array $equipo, array $part): void { quiz_render($reto, $equipo, $part); }
